<?php

use App\Actions\RecordParsedFigures;
use App\Enums\FinanceFigureStatus;
use App\Enums\FinanceUploadStatus;
use App\Enums\UserRole;
use App\Jobs\ExtractFinanceUpload;
use App\Models\FinanceDocument;
use App\Models\FinanceFigure;
use App\Models\FinanceLine;
use App\Models\FinanceUpload;
use App\Models\Municipality;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('local');
    $this->town = Municipality::factory()->create(['psgc_code' => '0201529000', 'name' => 'Tuguegarao City']);
    FinanceLine::factory()->create(['code' => 'performance.revenue', 'group' => 'totals', 'source' => 'executive_summary']);
});

function uploadReport(User $user, Municipality $town, string $contents = '%PDF-1.4 report'): TestResponse
{
    return test()->actingAs($user)->post(route('capitol.finances.uploads.store', ['town' => $town->psgc_code, 'year' => 2025]), [
        'kind' => 'executive_summary',
        'file' => UploadedFile::fake()->createWithContent('Tuguegarao-Executive-Summary-2025.pdf', $contents),
    ]);
}

/** What `python -m coa.intake` prints for one Executive Summary. */
function intakeOutput(string $value = '99846291.10'): string
{
    return json_encode(['type' => 'result', 'result' => ['parser_version' => '1.1.0', 'issues' => [], 'figures' => [[
        'line_code' => 'performance.revenue', 'fiscal_year' => 2025, 'basis' => 'actual', 'value' => $value,
        'source_label' => 'Income', 'location' => 'page 2', 'definition_variant' => null,
        'inputs' => [['location' => 'page 2', 'value' => '69065950.52'], ['location' => 'page 2', 'value' => '30780340.58']],
        'document_kind' => 'executive_summary',
    ]]]], JSON_THROW_ON_ERROR)."\n";
}

function storeUploadFixture(FinanceUpload $upload): void
{
    Storage::disk('local')->put($upload->path, 'report');
    $upload->update(['sha256' => hash('sha256', 'report'), 'size' => strlen('report')]);
}

it('refuses an upload from staff not assigned to the town and keeps no file', function () {
    Queue::fake();
    $staff = User::factory()->create(['role' => UserRole::Staff]);
    $staff->assignedTowns()->attach(Municipality::factory()->create()->id);

    uploadReport($staff, $this->town)->assertForbidden();

    expect(FinanceUpload::query()->count())->toBe(0)
        ->and(Storage::disk('local')->allFiles(FinanceUpload::FOLDER))->toBe([]);
    Queue::assertNothingPushed();
});

it('reads the same file for the same report only once', function () {
    Queue::fake();
    $staff = User::factory()->create(['role' => UserRole::Staff]);
    $staff->assignedTowns()->attach($this->town->id);

    uploadReport($staff, $this->town)->assertRedirect();
    uploadReport($staff, $this->town)->assertRedirect();

    expect(FinanceUpload::query()->count())->toBe(1)
        ->and(FinanceUpload::query()->sole()->path)->toStartWith(FinanceUpload::FOLDER.'/');
    Queue::assertPushed(ExtractFinanceUpload::class, 1);
});

it('polls only the same recent uploads shown in the finance report', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $uploads = FinanceUpload::factory()->for($this->town)->count(6)->sequence(
        fn ($sequence) => ['created_at' => now()->subMinutes($sequence->index)],
    )->create(['status' => FinanceUploadStatus::Done]);
    FinanceUpload::factory()->for($this->town)->create(['fiscal_year' => 2024]);
    FinanceUpload::factory()->create();

    $this->actingAs($admin)
        ->get(route('capitol.finances.show', ['town' => $this->town->psgc_code, 'year' => 2025]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->missing('uploadStatuses')
            ->where('report.uploads', fn ($rows) => $rows->pluck('id')->all() === $uploads->take(5)->pluck('public_id')->all())
            ->reloadOnly('uploadStatuses', fn (Assert $reload) => $reload
                ->missing('report')
                ->where('uploadStatuses', $page->toArray()['props']['report']['uploads'])));
});

it('reads one uploaded report into pending figures the uploader cannot sign off', function () {
    Process::fake(['*' => Process::result(intakeOutput())]);
    $uploader = User::factory()->create(['role' => UserRole::Admin]);
    $upload = FinanceUpload::factory()->for($this->town)->create(['uploaded_by' => $uploader->id]);

    storeUploadFixture($upload);
    (new ExtractFinanceUpload($upload->id))->handle(app(RecordParsedFigures::class));

    $figure = FinanceFigure::query()->sole();
    expect($upload->fresh()->status)->toBe(FinanceUploadStatus::Done)
        ->and($upload->fresh()->figures)->toBe(1)
        ->and($figure->status)->toBe(FinanceFigureStatus::Pending)
        ->and($figure->entered_by)->toBe($uploader->id)
        ->and($figure->value)->toBe('99846291.10')
        ->and($figure->inputs)->toHaveCount(2)
        ->and($figure->document->file)->toBe($upload->path)
        ->and($figure->document->sha256)->toBe($upload->sha256);
    Process::assertRan(fn ($process) => in_array('coa.intake', $process->command, true) && in_array('2025', $process->command, true));

    $this->actingAs($uploader)->post(route('capitol.finances.reviews.store', ['town' => $this->town->psgc_code, 'year' => 2025]), [
        'decision' => 'verified', 'figures' => [$figure->public_id],
    ]);
    expect($figure->fresh()->status)->toBe(FinanceFigureStatus::Pending);
});

it('never overwrites a different report already on record', function () {
    Process::fake(['*' => Process::result(intakeOutput('1.00'))]);
    $document = FinanceDocument::factory()->for($this->town)->create(['fiscal_year' => 2025, 'sha256' => str_repeat('a', 64)]);
    $kept = FinanceFigure::factory()->verified()->for($this->town)->line('performance.revenue', 2025, '99846291.10')
        ->create(['finance_document_id' => $document->id]);
    $upload = FinanceUpload::factory()->for($this->town)->create();

    storeUploadFixture($upload);
    (new ExtractFinanceUpload($upload->id))->handle(app(RecordParsedFigures::class));

    expect($kept->fresh()->value)->toBe('99846291.10')
        ->and($kept->fresh()->status)->toBe(FinanceFigureStatus::Verified)
        ->and($upload->fresh()->issues[0]['severity'])->toBe('error');
});

it('marks a report that cannot be read so its figures are entered by hand', function () {
    $upload = FinanceUpload::factory()->for($this->town)->create();

    (new ExtractFinanceUpload($upload->id))->failed(new RuntimeException('python missing'));

    expect($upload->fresh()->status)->toBe(FinanceUploadStatus::Failed)
        ->and($upload->fresh()->message)->not->toContain('python');
});

it('keeps an uploaded report private even after its figure is signed off', function () {
    $upload = FinanceUpload::factory()->for($this->town)->create();
    Storage::disk('local')->put($upload->path, 'report');
    $document = FinanceDocument::factory()->for($this->town)->create(['fiscal_year' => 2025, 'file' => $upload->path]);
    $figure = FinanceFigure::factory()->verified()->for($this->town)->line('performance.revenue', 2025, '99846291.10')
        ->create(['finance_document_id' => $document->id]);

    $this->get(route('public.finance-sources.show', $figure->public_id))->assertNotFound();
    $this->artisan('metrics:refresh')->assertSuccessful();
    $this->get(route('public.economic-performance'))->assertInertia(fn ($page) => $page
        ->where("performance.catalog.sources.{$figure->public_id}.url", null)
        ->where("performance.catalog.sources.{$figure->public_id}.kind", 'executive_summary'));
});

it('keeps only each input location and amount and never rewrites a signed-off citation', function () {
    Process::fake(['*' => Process::result(intakeOutput())]);
    $document = FinanceDocument::factory()->for($this->town)->create(['fiscal_year' => 2025, 'sha256' => null]);
    $signed = FinanceFigure::factory()->verified()->for($this->town)->line('performance.revenue', 2025, '99846291.10')
        ->create(['finance_document_id' => $document->id, 'location' => 'Reviewed cells', 'inputs' => null]);
    $upload = FinanceUpload::factory()->for($this->town)->create();

    storeUploadFixture($upload);
    (new ExtractFinanceUpload($upload->id))->handle(app(RecordParsedFigures::class));

    expect($signed->fresh()->location)->toBe('Reviewed cells')
        ->and($signed->fresh()->inputs)->toBeNull()
        ->and(FinanceFigure::cleanInputs([['location' => 'A1', 'value' => '1.00', 'path' => 'C:\secret'], ['location' => 'A2']]))
        ->toBe([['location' => 'A1', 'value' => '1.00']]);
});

it('keeps readable figures pending when another section has an error', function () {
    $event = json_decode(intakeOutput(), true, flags: JSON_THROW_ON_ERROR);
    $event['result']['issues'][] = ['severity' => 'error', 'kind' => 'sheet_not_found', 'code' => '', 'message' => 'The other statement could not be found.'];
    Process::fake(['*' => Process::result(json_encode($event, JSON_THROW_ON_ERROR)."\n")]);
    $upload = FinanceUpload::factory()->for($this->town)->create();

    storeUploadFixture($upload);
    (new ExtractFinanceUpload($upload->id))->handle(app(RecordParsedFigures::class));

    expect($upload->fresh()->status)->toBe(FinanceUploadStatus::Done);
    expect($upload->fresh()->issues[0]['severity'])->toBe('error');
    $this->assertDatabaseHas('finance_figures', ['line_code' => 'performance.revenue', 'value' => '99846291.10', 'status' => 'pending']);
    Process::assertRan(fn ($process) => in_array('--events', $process->command, true));
});

it('refuses duplicate parser observations before any figure or document is written', function () {
    $event = json_decode(intakeOutput(), true, flags: JSON_THROW_ON_ERROR);
    $event['result']['figures'][] = $event['result']['figures'][0];
    Process::fake(['*' => Process::result(json_encode($event, JSON_THROW_ON_ERROR)."\n")]);
    $upload = FinanceUpload::factory()->for($this->town)->create();

    storeUploadFixture($upload);
    expect(fn () => (new ExtractFinanceUpload($upload->id))->handle(app(RecordParsedFigures::class)))
        ->toThrow(RuntimeException::class, 'duplicate financial observations');

    $this->assertDatabaseCount('finance_figures', 0);
    $this->assertDatabaseCount('finance_documents', 0);
    expect($upload->fresh()->status)->toBe(FinanceUploadStatus::Failed);
    Process::assertRan(fn ($process) => in_array('--events', $process->command, true));
});
