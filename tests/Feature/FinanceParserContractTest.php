<?php

use App\Actions\RecordParsedFigures;
use App\Enums\FinanceFigureStatus;
use App\Enums\FinanceUploadStatus;
use App\Jobs\ExtractFinanceUpload;
use App\Models\FinanceDocument;
use App\Models\FinanceFigure;
use App\Models\FinanceLine;
use App\Models\FinanceUpload;
use App\Models\Municipality;
use App\Services\FinanceParserStream;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

function contractFigure(array $patch = []): array
{
    return array_replace(['line_code' => 'performance.revenue', 'fiscal_year' => 2025, 'basis' => 'actual',
        'value' => '100.00', 'source_label' => 'Income', 'location' => 'page 2', 'document_kind' => 'executive_summary'], $patch);
}

function contractResult(array $patch = []): array
{
    return array_replace(['parser_version' => '1.3.0', 'source_sha256' => hash('sha256', 'report'), 'confidence' => 95,
        'figures' => [contractFigure()], 'issues' => []], $patch);
}

function contractOutput(array $result): string
{
    return json_encode(['type' => 'result', 'result' => $result], JSON_THROW_ON_ERROR)."\n";
}

it('accepts signed current and comparative figures and preserves explicit confidence', function () {
    $result = contractResult(['figures' => [contractFigure(['value' => '-100.00']), contractFigure(['fiscal_year' => 2024])],
        'issues' => [['severity' => 'error', 'kind' => 'net_income_mismatch', 'code' => 'performance.net_income', 'message' => 'Printed totals do not reconcile.']]]);
    $stream = new FinanceParserStream(fn () => null, 2025, 'executive_summary', hash('sha256', 'report'));

    $stream->push(contractOutput($result));
    $stream->finish();

    expect($stream->result)->toBe($result);
});

it('accepts legacy results without inventing a confidence score', function () {
    $result = contractResult();
    unset($result['confidence'], $result['source_sha256']);
    $stream = new FinanceParserStream(fn () => null, 2025, 'executive_summary', hash('sha256', 'report'));

    $stream->push(contractOutput($result));
    $stream->finish();

    expect($stream->result)->toBe($result)->not->toHaveKey('confidence');
});

it('rejects malformed or out-of-context final results before accepting observations', function (array $patch, string $exception) {
    $stream = new FinanceParserStream(fn () => null, 2025, 'executive_summary', hash('sha256', 'report'));

    expect(fn () => $stream->push(contractOutput(contractResult($patch))))->toThrow($exception);
    expect($stream->result)->toBeNull();
})->with([
    'duplicate financial observation' => [['figures' => [contractFigure(), contractFigure()]], RuntimeException::class],
    'future year' => [['figures' => [contractFigure(['fiscal_year' => 2026])]], RuntimeException::class],
    'unrelated prior year' => [['figures' => [contractFigure(['fiscal_year' => 2023])]], RuntimeException::class],
    'year string' => [['figures' => [contractFigure(['fiscal_year' => '2025'])]], RuntimeException::class],
    'workbook figure' => [['figures' => [contractFigure(['document_kind' => 'fs_workbook'])]], ValidationException::class],
    'zero PDF page' => [['figures' => [contractFigure(['location' => 'page 0'])]], RuntimeException::class],
    'absent PDF citation' => [['figures' => [contractFigure(['location' => ''])]], ValidationException::class],
    'source hash mismatch' => [['source_sha256' => str_repeat('a', 64)], RuntimeException::class],
    'source hash malformed' => [['source_sha256' => 'not-a-hash'], ValidationException::class],
    'negative confidence' => [['confidence' => -1], ValidationException::class],
    'confidence above maximum' => [['confidence' => 101], ValidationException::class],
    'fractional confidence' => [['confidence' => 9.5], ValidationException::class],
    'confidence string' => [['confidence' => '95'], RuntimeException::class],
    'untyped issue' => [['issues' => [['message' => 'Missing structured issue fields.']]], ValidationException::class],
    'issue for different source kind' => [['issues' => [['severity' => 'error', 'kind' => 'engine_disagreement', 'message' => 'Different source.', 'document_kind' => 'fs_workbook']]], ValidationException::class],
    'issue for different source hash' => [['issues' => [['severity' => 'error', 'kind' => 'engine_disagreement', 'message' => 'Different source.', 'document_kind' => 'executive_summary', 'document_sha256' => str_repeat('a', 64)]]], RuntimeException::class],
    'issue checksum without document' => [['issues' => [['severity' => 'error', 'kind' => 'engine_disagreement', 'message' => 'Unbound source.', 'document_sha256' => str_repeat('a', 64)]]], RuntimeException::class],
]);

it('refuses changed source bytes before invoking the parser', function () {
    Storage::fake('local');
    Process::fake(['*' => Process::result(contractOutput(contractResult()))]);
    $upload = FinanceUpload::factory()->create(['sha256' => hash('sha256', 'original')]);
    Storage::disk('local')->put($upload->path, 'changed');

    expect(fn () => (new ExtractFinanceUpload($upload->id))->handle(app(RecordParsedFigures::class)))
        ->toThrow(RuntimeException::class, 'Stored report checksum changed');

    expect($upload->fresh()->status)->toBe(FinanceUploadStatus::Failed)
        ->and($upload->fresh()->progress['confidence'])->toBe(0);
    Process::assertNothingRan();
    $this->assertDatabaseCount('finance_figures', 0);
    $this->assertDatabaseCount('finance_documents', 0);
});

it('refuses source bytes that change during parsing before saving figures', function () {
    Storage::fake('local');
    $upload = FinanceUpload::factory()->create(['kind' => 'executive_summary', 'sha256' => hash('sha256', 'report')]);
    Storage::disk('local')->put($upload->path, 'report');
    Process::fake(['*' => function () use ($upload) {
        Storage::disk('local')->put($upload->path, 'changed');

        return Process::result(contractOutput(contractResult()));
    }]);

    expect(fn () => (new ExtractFinanceUpload($upload->id))->handle(app(RecordParsedFigures::class)))
        ->toThrow(RuntimeException::class, 'Stored report checksum changed');

    expect($upload->fresh()->status)->toBe(FinanceUploadStatus::Failed);
    $this->assertDatabaseCount('finance_figures', 0);
    $this->assertDatabaseCount('finance_documents', 0);
});

it('keeps omitted pending figures but marks their exact source and fiscal year unconfirmed', function () {
    Storage::fake('local');
    $town = Municipality::factory()->create(['name' => 'Tuguegarao City']);
    $upload = FinanceUpload::factory()->for($town)->create(['kind' => 'executive_summary', 'sha256' => hash('sha256', 'report')]);
    Storage::disk('local')->put($upload->path, 'report');
    $document = FinanceDocument::factory()->for($town)->create(['fiscal_year' => 2025, 'file' => $upload->path, 'sha256' => $upload->sha256]);
    $kept = FinanceFigure::factory()->for($town)->line('performance.expenses', 2024, '50.00')->create(['finance_document_id' => $document->id, 'location' => 'page 2']);
    FinanceFigure::factory()->for($town)->line('performance.revenue', 2025, '100.00')->create(['finance_document_id' => $document->id, 'location' => 'page 2']);
    Process::fake(['*' => Process::result(contractOutput(contractResult()))]);

    (new ExtractFinanceUpload($upload->id))->handle(app(RecordParsedFigures::class));

    expect($kept->fresh()->value)->toBe('50.00')->and($kept->fresh()->status)->toBe(FinanceFigureStatus::Pending);
    $issue = collect($upload->fresh()->issues)->firstWhere('code', 'previous_figure_not_reproduced');
    expect($issue)->toMatchArray(['line' => 'performance.expenses', 'fiscal_year' => 2024, 'document_kind' => 'executive_summary', 'document_sha256' => $upload->sha256]);
    expect($upload->fresh()->progress['confidence'])->toBe(0);
    Process::assertRan(fn ($process) => in_array('--town', $process->command, true) && in_array('Tuguegarao City', $process->command, true)
        && in_array('--province', $process->command, true) && in_array('Cagayan', $process->command, true));
});

it('leaves legacy check-only pending figures unchanged without an omission error or forced zero', function () {
    Storage::fake('local');
    $town = Municipality::factory()->create(['name' => 'Tuguegarao City']);
    $upload = FinanceUpload::factory()->for($town)->create(['kind' => 'executive_summary', 'sha256' => hash('sha256', 'report')]);
    Storage::disk('local')->put($upload->path, 'report');
    $document = FinanceDocument::factory()->for($town)->create(['fiscal_year' => 2025, 'file' => $upload->path, 'sha256' => $upload->sha256]);
    FinanceLine::factory()->create(['code' => 'fs.total_revenue', 'group' => 'checks', 'displayed' => false, 'source' => 'fs_totals']);
    $checks = collect([2025 => '1679019473.60', 2024 => '1465722146.22'])->map(fn (string $value, int $year) => FinanceFigure::factory()->for($town)
        ->line('fs.total_revenue', $year, $value)->create(['finance_document_id' => $document->id, 'location' => 'Sheet!D14']));
    Process::fake(['*' => Process::result(contractOutput(contractResult()))]);

    (new ExtractFinanceUpload($upload->id))->handle(app(RecordParsedFigures::class));

    expect(collect($upload->fresh()->issues)->where('code', 'previous_figure_not_reproduced'))->toBeEmpty();
    expect($upload->fresh()->progress['confidence'])->toBe(95);
    $checks->each(fn (FinanceFigure $check) => expect($check->fresh()->status)->toBe(FinanceFigureStatus::Pending)
        ->and($check->fresh()->value)->toBe($check->value));
});

it('still flags a genuinely omitted eligible figure alongside legacy check-only rows', function () {
    Storage::fake('local');
    $town = Municipality::factory()->create(['name' => 'Tuguegarao City']);
    $upload = FinanceUpload::factory()->for($town)->create(['kind' => 'executive_summary', 'sha256' => hash('sha256', 'report')]);
    Storage::disk('local')->put($upload->path, 'report');
    $document = FinanceDocument::factory()->for($town)->create(['fiscal_year' => 2025, 'file' => $upload->path, 'sha256' => $upload->sha256]);
    FinanceLine::factory()->create(['code' => 'fs.total_revenue', 'group' => 'checks', 'displayed' => false, 'source' => 'fs_totals']);
    FinanceFigure::factory()->for($town)->line('fs.total_revenue', 2025, '1679019473.60')->create(['finance_document_id' => $document->id, 'location' => 'Sheet!D14']);
    $kept = FinanceFigure::factory()->for($town)->line('performance.expenses', 2024, '50.00')->create(['finance_document_id' => $document->id, 'location' => 'page 2']);
    Process::fake(['*' => Process::result(contractOutput(contractResult()))]);

    (new ExtractFinanceUpload($upload->id))->handle(app(RecordParsedFigures::class));

    $omissions = collect($upload->fresh()->issues)->where('code', 'previous_figure_not_reproduced');
    expect($omissions)->toHaveCount(1)
        ->and($omissions->first())->toMatchArray(['line' => 'performance.expenses', 'fiscal_year' => 2024, 'location' => 'page 2'])
        ->and($upload->fresh()->progress['confidence'])->toBe(0)
        ->and($kept->fresh()->status)->toBe(FinanceFigureStatus::Pending);
});

it('preserves the canonical report and reviewed citation when identical bytes are uploaded again', function () {
    Storage::fake('local');
    $town = Municipality::factory()->create(['name' => 'Tuguegarao City']);
    $file = 'executive-summaries/2025/Tuguegarao-City/Tuguegarao-City-Executive-Summary-2025.pdf';
    $contents = file_get_contents(base_path('data/towns/'.$file));
    $hash = hash('sha256', $contents);
    $upload = FinanceUpload::factory()->for($town)->create(['kind' => 'executive_summary', 'sha256' => $hash]);
    Storage::disk('local')->put($upload->path, $contents);
    $document = FinanceDocument::factory()->for($town)->create(['fiscal_year' => 2025, 'file' => $file, 'sha256' => $hash, 'source_url' => 'https://www.coa.gov.ph/original.pdf']);
    $figure = FinanceFigure::factory()->verified()->for($town)->line('performance.revenue', 2025, '1679019473.60')->create([
        'finance_document_id' => $document->id, 'location' => 'page 2', 'source_label' => 'Reviewed Income', 'extractor_version' => 'reviewed',
    ]);
    Process::fake(['*' => Process::result(contractOutput(contractResult(['source_sha256' => $hash,
        'figures' => [contractFigure(['value' => '1679019473.60', 'location' => 'page 1'])]])))]);

    (new ExtractFinanceUpload($upload->id))->handle(app(RecordParsedFigures::class));

    expect($document->fresh()->file)->toBe($file)->and($document->fresh()->source_url)->toBe('https://www.coa.gov.ph/original.pdf');
    expect($figure->fresh()->location)->toBe('page 2')->and($figure->fresh()->source_label)->toBe('Reviewed Income')
        ->and($figure->fresh()->extractor_version)->toBe('reviewed')->and($figure->fresh()->status)->toBe(FinanceFigureStatus::Verified);
    expect($upload->fresh()->progress['confidence'])->toBe(95);
});
