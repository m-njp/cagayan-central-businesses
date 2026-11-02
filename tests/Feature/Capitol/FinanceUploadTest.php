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

beforeEach(function () {
    Storage::fake('local');
    $this->town = Municipality::factory()->create(['psgc_code' => '0201529000', 'name' => 'Tuguegarao City']);
    FinanceLine::factory()->create(['code' => 'sector.economic_services', 'group' => 'development', 'source' => 'fs_scbaa']);
});

function uploadReport(User $user, Municipality $town, string $contents = 'workbook'): TestResponse
{
    return test()->actingAs($user)->post(route('capitol.finances.uploads.store', ['town' => $town->psgc_code, 'year' => 2025]), [
        'kind' => 'fs_workbook',
        'file' => UploadedFile::fake()->createWithContent('Tuguegarao-FS-2025.xlsx', $contents),
    ]);
}

/** What `python -m coa.intake` prints for one Financial Statements workbook. */
function intakeOutput(string $value = '99846291.10'): string
{
    return json_encode(['parser_version' => '1.1.0', 'issues' => [], 'children' => [], 'figures' => [[
        'line_code' => 'sector.economic_services', 'fiscal_year' => 2025, 'basis' => 'actual', 'value' => $value,
        'source_label' => 'Economic Services', 'location' => '11 - SCBAA!K61; 11 - SCBAA!K62', 'definition_variant' => null,
        'inputs' => [['location' => '11 - SCBAA!K61', 'value' => '69065950.52'], ['location' => '11 - SCBAA!K62', 'value' => '30780340.58']],
        'document_kind' => 'fs_workbook', 'member' => null,
    ]]], JSON_THROW_ON_ERROR);
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

it('reads one uploaded workbook into pending figures the uploader cannot sign off', function () {
    Process::fake(['*' => Process::result(intakeOutput())]);
    $uploader = User::factory()->create(['role' => UserRole::Admin]);
    $upload = FinanceUpload::factory()->for($this->town)->create(['uploaded_by' => $uploader->id]);

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
    Process::assertRan(fn ($process) => in_array('coa.intake', $process->command, true) && in_array('fs_workbook', $process->command, true));

    $this->actingAs($uploader)->post(route('capitol.finances.reviews.store', ['town' => $this->town->psgc_code, 'year' => 2025]), [
        'decision' => 'verified', 'figures' => [$figure->public_id],
    ]);
    expect($figure->fresh()->status)->toBe(FinanceFigureStatus::Pending);
});

it('never overwrites a different report already on record', function () {
    Process::fake(['*' => Process::result(intakeOutput('1.00'))]);
    $document = FinanceDocument::factory()->for($this->town)->create(['fiscal_year' => 2025, 'kind' => 'fs_workbook', 'sha256' => str_repeat('a', 64)]);
    $kept = FinanceFigure::factory()->verified()->for($this->town)->line('sector.economic_services', 2025, '99846291.10')
        ->create(['finance_document_id' => $document->id]);
    $upload = FinanceUpload::factory()->for($this->town)->create();

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
    Storage::disk('local')->put($upload->path, 'workbook');
    $document = FinanceDocument::factory()->for($this->town)->create(['fiscal_year' => 2025, 'kind' => 'fs_workbook', 'file' => $upload->path]);
    $figure = FinanceFigure::factory()->verified()->for($this->town)->line('sector.economic_services', 2025, '99846291.10')
        ->create(['finance_document_id' => $document->id]);

    $this->get(route('public.finance-sources.show', $figure->public_id))->assertNotFound();
    $this->artisan('metrics:refresh')->assertSuccessful();
    $this->get(route('public.economic-performance'))->assertInertia(fn ($page) => $page
        ->where("performance.catalog.sources.{$figure->public_id}.url", null)
        ->where("performance.catalog.sources.{$figure->public_id}.kind", 'fs_workbook'));
});

it('keeps only each cell and amount and never rewrites a signed-off citation', function () {
    Process::fake(['*' => Process::result(intakeOutput())]);
    $document = FinanceDocument::factory()->for($this->town)->create(['fiscal_year' => 2025, 'kind' => 'fs_workbook', 'sha256' => null]);
    $signed = FinanceFigure::factory()->verified()->for($this->town)->line('sector.economic_services', 2025, '99846291.10')
        ->create(['finance_document_id' => $document->id, 'location' => 'Reviewed cells', 'inputs' => null]);
    $upload = FinanceUpload::factory()->for($this->town)->create();

    (new ExtractFinanceUpload($upload->id))->handle(app(RecordParsedFigures::class));

    expect($signed->fresh()->location)->toBe('Reviewed cells')
        ->and($signed->fresh()->inputs)->toBeNull()
        ->and(FinanceFigure::cleanInputs([['location' => 'A1', 'value' => '1.00', 'path' => 'C:\secret'], ['location' => 'A2']]))
        ->toBe([['location' => 'A1', 'value' => '1.00']]);
});
