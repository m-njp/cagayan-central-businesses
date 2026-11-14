<?php

use App\Actions\RecordParsedFigures;
use App\Enums\FinanceUploadStatus;
use App\Enums\UserRole;
use App\Jobs\ExtractFinanceUpload;
use App\Models\FinanceFigure;
use App\Models\FinanceLine;
use App\Models\FinanceUpload;
use App\Models\Municipality;
use App\Models\User;
use App\Services\FinanceParsingProgress;
use Illuminate\Process\Exceptions\ProcessFailedException;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

it('returns scoped progress without server file paths and denies guests and unassigned staff', function () {
    $town = Municipality::factory()->create();
    $upload = FinanceUpload::factory()->for($town)->create();
    $url = route('capitol.finances.uploads.show', ['town' => $town->psgc_code, 'year' => 2025, 'upload' => $upload->public_id]);
    $this->getJson($url)->assertNotFound();
    $staff = User::factory()->create(['role' => UserRole::Staff]);
    $this->actingAs($staff)->getJson($url)->assertForbidden();
    $staff->assignedTowns()->attach($town->id);
    $this->actingAs($staff)->getJson($url)->assertJsonPath('id', $upload->public_id)
        ->assertJsonPath('status', 'queued')->assertJsonMissingPath('path')->assertJsonMissingPath('uploaded_by');
    $this->getJson(route('capitol.finances.uploads.show', ['town' => $town->psgc_code, 'year' => 2024, 'upload' => $upload->public_id]))->assertNotFound();
    $other = Municipality::factory()->create();
    $staff->assignedTowns()->attach($other->id);
    $this->getJson(route('capitol.finances.uploads.show', ['town' => $other->psgc_code, 'year' => 2025, 'upload' => $upload->public_id]))->assertNotFound();
});

it('atomically retries a failed upload once and fences out the old job and its failure callback', function () {
    Queue::fake([ExtractFinanceUpload::class]);
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $upload = FinanceUpload::factory()->create(['status' => FinanceUploadStatus::Failed]);
    $oldAttempt = $upload->progress['attempt'];
    $url = route('capitol.finances.uploads.retry', ['town' => $upload->municipality->psgc_code, 'year' => 2025, 'upload' => $upload->public_id]);

    $this->actingAs($admin)->post($url)->assertRedirect();
    $this->post($url)->assertConflict();
    (new ExtractFinanceUpload($upload->id, $oldAttempt))->handle(app(RecordParsedFigures::class));
    (new ExtractFinanceUpload($upload->id, $oldAttempt))->failed(new RuntimeException('old failure'));

    expect($upload->fresh()->status)->toBe(FinanceUploadStatus::Queued);
    expect($upload->fresh()->progress['attempt'])->not->toBe($oldAttempt);
    Queue::assertPushed(ExtractFinanceUpload::class, 1);
});

it('can parse a finished report again without uploading or publishing another file', function () {
    Queue::fake([ExtractFinanceUpload::class]);
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $upload = FinanceUpload::factory()->create(['status' => FinanceUploadStatus::Done]);
    $args = ['town' => $upload->municipality->psgc_code, 'year' => 2025, 'upload' => $upload->public_id];
    $path = $upload->path;

    $this->actingAs($admin)->post(route('capitol.finances.uploads.retry', $args))->assertRedirect();

    expect($upload->fresh()->status)->toBe(FinanceUploadStatus::Queued);
    expect($upload->fresh()->path)->toBe($path);
    $this->assertDatabaseCount('finance_uploads', 1);
    Queue::assertPushed(ExtractFinanceUpload::class, 1);
});

it('shows interrupted workers as recoverable and refuses their late progress after retry', function () {
    Queue::fake([ExtractFinanceUpload::class]);
    $this->freezeTime();
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $snapshot = FinanceParsingProgress::initial('old-attempt');
    $snapshot['startedAt'] = now()->subMinutes(5)->toIso8601String();
    $upload = FinanceUpload::factory()->create(['status' => FinanceUploadStatus::Running, 'progress' => $snapshot]);
    $args = ['town' => $upload->municipality->psgc_code, 'year' => 2025, 'upload' => $upload->public_id];

    $this->actingAs($admin)->getJson(route('capitol.finances.uploads.show', $args))->assertJsonPath('status', 'failed')->assertJsonPath('progress.errorCode', 'parser_interrupted');
    $this->post(route('capitol.finances.uploads.retry', $args))->assertRedirect();
    $progress = new FinanceParsingProgress($upload->id, 'old-attempt', $snapshot);
    expect(fn () => $progress->accept(['type' => 'stage', 'stage' => 'saving']))->toThrow(RuntimeException::class);
    expect($upload->fresh()->status)->toBe(FinanceUploadStatus::Queued);
    Queue::assertPushed(ExtractFinanceUpload::class, 1);
});

it('replaces provisional figures and keeps bounded activity and safe PDF warnings', function () {
    config(['cagayan.finances.uploads.progress_events_max' => 2]);
    $upload = FinanceUpload::factory()->create(['status' => FinanceUploadStatus::Running]);
    $progress = new FinanceParsingProgress($upload->id, $upload->progress['attempt'], $upload->progress);
    foreach (range(1, 3) as $number) {
        $progress->accept(['type' => 'stage', 'stage' => 'scanning', 'location' => 'page '.$number]);
    }
    $row = ['line_code' => 'performance.revenue', 'fiscal_year' => 2025, 'basis' => 'actual', 'value' => '10.00', 'location' => 'page 2'];
    $progress->accept(['type' => 'figure', 'document_kind' => 'executive_summary', 'figure' => $row]);
    $progress->accept(['type' => 'replace', 'document_kind' => 'executive_summary', 'figures' => [array_replace($row, ['value' => '20.00'])]]);
    $progress->accept(['type' => 'issue', 'issue' => ['kind' => 'engine_failed', 'severity' => 'warning', 'message' => 'C:\private\server.py']]);

    $stored = $upload->fresh()->progress;
    expect($stored['activity'])->toHaveCount(2);
    expect($stored['rows'])->toHaveCount(1);
    expect($stored['rows'][0]['value'])->toBe('20.00');
    expect($stored['issues'][0]['message'])->not->toContain('private');
});

it('keeps info notes out of the live feed', function () {
    $upload = FinanceUpload::factory()->create(['status' => FinanceUploadStatus::Running]);
    $progress = new FinanceParsingProgress($upload->id, $upload->progress['attempt'], $upload->progress);
    $progress->accept(['type' => 'issue', 'issue' => ['kind' => 'province_unconfirmed', 'severity' => 'info', 'message' => 'The opening pages do not name the province.']]);
    $progress->accept(['type' => 'issue', 'issue' => ['kind' => 'restated', 'severity' => 'warning', 'message' => 'The 2025 report restates Income for 2024.']]);

    $stored = $upload->fresh()->progress;
    expect($stored['issues'])->toHaveCount(2);
    expect($stored['activity'])->toHaveCount(1);
    expect($stored['activity'][0]['message'])->toBe('The 2025 report restates Income for 2024.');
});

it('rolls back figures when parsing fails and exposes only a safe failure message', function () {
    Storage::fake('local');
    Process::fake(['*' => Process::result(output: "{\"type\":\"stage\",\"stage\":\"scanning\"}\n", errorOutput: 'C:\private\parser.py crashed', exitCode: 1)]);
    $upload = FinanceUpload::factory()->create();
    Storage::disk('local')->put($upload->path, 'report');
    $upload->update(['sha256' => hash('sha256', 'report')]);

    expect(fn () => (new ExtractFinanceUpload($upload->id))->handle(app(RecordParsedFigures::class)))->toThrow(ProcessFailedException::class);

    expect($upload->fresh()->status)->toBe(FinanceUploadStatus::Failed);
    expect($upload->fresh()->message)->not->toContain('private');
    $this->assertDatabaseCount('finance_figures', 0);
    Process::assertRan(fn ($process) => in_array('--events', $process->command, true));
});

it('executes a real Executive Summary through the queue and saves only pending finance figures', function () {
    Storage::fake('local');
    config(['queue.default' => 'database', 'cagayan.finances.uploads.pace_seconds' => 0]);
    $town = Municipality::factory()->create(['psgc_code' => '0201529000', 'name' => 'Tuguegarao City']);
    FinanceLine::factory()->create(['code' => 'performance.revenue', 'source' => 'executive_summary', 'group' => 'totals']);
    $upload = FinanceUpload::factory()->for($town)->create();
    $source = base_path('data/towns/executive-summaries/2025/Tuguegarao-City/Tuguegarao-City-Executive-Summary-2025.pdf');
    Storage::disk('local')->put($upload->path, file_get_contents($source));
    $upload->update(['sha256' => hash_file('sha256', $source)]);
    ExtractFinanceUpload::dispatch($upload->id, $upload->progress['attempt']);

    $this->artisan('queue:work', ['--once' => true, '--stop-when-empty' => true])->assertSuccessful();

    expect($upload->fresh()->status)->toBe(FinanceUploadStatus::Done);
    expect($upload->fresh()->progress['found'])->toBe(20);
    expect($upload->fresh()->progress['activity'])->not->toBeEmpty();
    $this->assertDatabaseHas('finance_figures', ['line_code' => 'performance.revenue', 'fiscal_year' => 2025, 'value' => '1679019473.60', 'status' => 'pending']);
    expect(FinanceFigure::query()->where('status', 'verified')->exists())->toBeFalse();
});
