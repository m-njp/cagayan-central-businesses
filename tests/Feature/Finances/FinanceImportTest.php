<?php

use App\Console\Commands\ImportFinancesCommand;
use App\Data\TownFigure;
use App\Enums\FinanceEntryMethod;
use App\Enums\FinanceFigureStatus;
use App\Models\FinanceDocument;
use App\Models\FinanceFigure;
use App\Models\FinanceLine;
use App\Models\Municipality;
use App\Queries\TownAtlasQuery;
use App\Services\RefreshFinanceMetrics;
use Database\Seeders\MunicipalitySeeder;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Testing\PendingCommand;

function financeTestFolder(): string
{
    return test()->financeFolder;
}

const SHA_2023 = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

const SHA_2024 = 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb';

const SHA_WORKBOOK_2024 = 'cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc';

/**
 * Two Abulug reports: the 2024 report restates 2023 revenue as 400.00, the 2023 report had 390.00.
 *
 * @param  array{extracted?: list<string>, reviews?: list<string>}  $override
 */
function writeFinanceFolder(array $override = []): void
{
    test()->financeFolder = 'storage/framework/testing/finances-'.Str::uuid();
    test()->financeFolders = [...test()->financeFolders, test()->financeFolder];
    $folder = base_path(financeTestFolder());
    File::ensureDirectoryExists("{$folder}/extracted");

    $csv = fn (string $header, array $rows) => implode("\n", [$header, ...$rows])."\n";
    File::put("{$folder}/lines.csv", $csv('code,parent_code,label,group,unit,displayed,source,aliases', [
        'performance.revenue,,Government Revenue,totals,php,1,executive_summary,income',
        'performance.expenses,,Government Expenses,totals,php,1,executive_summary,expenses?',
    ]));
    File::put("{$folder}/documents.csv", $csv('psgc_code,town,fiscal_year,kind,availability,file,source_url,sha256,checked_on', [
        '0201501000,Abulug,2023,executive_summary,available,a.pdf,https://www.coa.gov.ph/2023.pdf,'.SHA_2023.',2026-10-27',
        '0201501000,Abulug,2024,executive_summary,available,b.pdf,https://www.coa.gov.ph/2024.pdf,'.SHA_2024.',2026-10-27',
        '0201501000,Abulug,2025,executive_summary,not_published,,,,2026-10-27',
    ]));
    File::put("{$folder}/extracted/2024.csv", $csv(implode(',', ImportFinancesCommand::EXTRACTED_COLUMNS), $override['extracted'] ?? [
        '0201501000,Abulug,2024,executive_summary,2024,performance.revenue,actual,500.00,,Income,page 1,'.SHA_2024.',1',
        '0201501000,Abulug,2024,executive_summary,2023,performance.revenue,actual,400.00,,Income,page 1,'.SHA_2024.',1',
        '0201501000,Abulug,2024,executive_summary,2024,performance.expenses,actual,450.00,,Expense,page 1,'.SHA_2024.',1',
    ]));
    File::put("{$folder}/extracted/2023.csv", $csv(implode(',', ImportFinancesCommand::EXTRACTED_COLUMNS), [
        '0201501000,Abulug,2023,executive_summary,2023,performance.revenue,actual,390.00,,Income,page 1,'.SHA_2023.',1',
    ]));
    File::put("{$folder}/reviews.csv", $csv(implode(',', ImportFinancesCommand::REVIEW_COLUMNS), $override['reviews'] ?? []));
}

function importFinances(): PendingCommand
{
    return test()->artisan('finances:import', ['--path' => financeTestFolder()]);
}

function abulugRevenue(): ?TownFigure
{
    return collect(app(TownAtlasQuery::class)->get()->towns)->firstWhere('code', '0201501000')?->revenue;
}

beforeEach(function () {
    $this->financeFolders = [];
    Municipality::factory()->create(['psgc_code' => '0201501000', 'name' => 'Abulug']);
});

afterEach(function () {
    foreach ($this->financeFolders as $folder) {
        File::deleteDirectory(base_path($folder));
    }
});

it('loads parsed figures as pending and publishes none of them', function () {
    writeFinanceFolder();

    importFinances()->assertSuccessful();

    expect(FinanceFigure::query()->count())->toBe(4)
        ->and(FinanceFigure::query()->where('status', '!=', FinanceFigureStatus::Pending)->count())->toBe(0)
        ->and(abulugRevenue())->toBeNull();
});

it('stores every contributing input of a figure', function () {
    $cells = 'Sheet!K61; Sheet!K62; Sheet!K63; Sheet!K64; Sheet!K65; Sheet!K66; Sheet!K67';
    $inputs = [['location' => 'Sheet!K61', 'value' => '300.00'], ['location' => 'Sheet!K62', 'value' => '200.00']];
    writeFinanceFolder(['extracted' => [
        '0201501000,Abulug,2024,executive_summary,2024,performance.revenue,actual,500.00,,Income,'.$cells.','.SHA_2024.',1.1.0,"'.str_replace('"', '""', json_encode($inputs)).'"',
    ]]);

    importFinances()->assertSuccessful();

    $figure = FinanceFigure::query()->where('fiscal_year', 2024)->where('line_code', 'performance.revenue')->sole();
    expect($figure->location)->toBe($cells)
        ->and($figure->inputs)->toEqual($inputs);
});

it('rejects manual imports that bypass the finance entry limits', function (string $value, string $basis, string $source, bool $hasDocument = false) {
    writeFinanceFolder();
    $row = [(string) Str::uuid(), '0201501000', '2024', 'performance.revenue', $basis, $value, '', $hasDocument ? '2024' : '', $hasDocument ? 'executive_summary' : '', $source, '', 'Checked the report', 'pending', ''];
    $handle = fopen(base_path(financeTestFolder().'/manual-figures.csv'), 'w');
    fputcsv($handle, ImportFinancesCommand::MANUAL_COLUMNS, escape: '');
    fputcsv($handle, $row, escape: '');
    fclose($handle);

    importFinances()->assertFailed();

    $this->assertDatabaseCount('finance_figures', 0);
    $this->assertDatabaseCount('finance_documents', 0);
})->with([
    'negative amount' => ['-1.00', 'actual', 'https://www.coa.gov.ph/report.pdf'],
    'over maximum' => ['1000000000000.00', 'actual', 'https://www.coa.gov.ph/report.pdf'],
    'invalid basis for revenue' => ['1.00', 'budget_final', 'https://www.coa.gov.ph/report.pdf'],
    'unofficial source' => ['1.00', 'actual', 'https://example.com/report.pdf'],
    'unofficial override beside a valid document' => ['1.00', 'actual', 'https://example.com/report.pdf', true],
]);

it('imports nothing when any row is wrong', function (string $row, string $problem) {
    writeFinanceFolder(['extracted' => ['0201501000,Abulug,2024,executive_summary,2024,performance.revenue,actual,500.00,,Income,page 1,'.SHA_2024.',1', $row]]);

    importFinances()->expectsOutputToContain("extracted/2024.csv line 3: {$problem}")->assertFailed();

    expect(FinanceFigure::query()->count())->toBe(0)->and(FinanceLine::query()->count())->toBe(0);
})->with([
    'unknown line' => ['0201501000,Abulug,2024,executive_summary,2024,performance.profit,actual,1.00,,Profit,page 1,'.SHA_2024.',1', 'unknown line_code performance.profit'],
    'different file' => ['0201501000,Abulug,2024,executive_summary,2024,performance.expenses,actual,1.00,,Expense,page 1,'.SHA_2023.',1', 'document_sha256 does not match documents.csv'],
    'not a number' => ['0201501000,Abulug,2024,executive_summary,2024,performance.expenses,actual,"1,000",,Expense,page 1,'.SHA_2024.',1', 'value 1,000 is not a number'],
    'unpublished report' => ['0201501000,Abulug,2025,executive_summary,2025,performance.expenses,actual,1.00,,Expense,page 1,'.SHA_2024.',1', 'no available document for this town, report year, and kind'],
]);

it('refuses workbook documents and figures because only the Executive Summary is read', function () {
    writeFinanceFolder(['extracted' => [
        '0201501000,Abulug,2024,executive_summary,2024,performance.revenue,actual,500.00,,Income,page 1,'.SHA_2024.',1',
        '0201501000,Abulug,2024,fs_workbook,2024,performance.revenue,actual,100.00,,Label,Sheet!D9,'.SHA_WORKBOOK_2024.',1',
    ]]);
    File::append(base_path(financeTestFolder().'/documents.csv'), '0201501000,Abulug,2024,fs_workbook,available,c.xlsx,https://www.coa.gov.ph/2024.zip,'.SHA_WORKBOOK_2024.",2026-11-13\n");

    importFinances()
        ->expectsOutputToContain('documents.csv line 5: kind fs_workbook is not read')
        ->expectsOutputToContain('extracted/2024.csv line 3: document_kind fs_workbook is not read')
        ->assertFailed();

    expect(FinanceFigure::query()->count())->toBe(0);
});

it('never reads the frozen workbook history', function () {
    writeFinanceFolder();
    $history = base_path(financeTestFolder().'/history/retirement');
    File::ensureDirectoryExists($history);
    File::put($history.'/figures.csv', implode(',', ImportFinancesCommand::EXTRACTED_COLUMNS)."\n"
        .'0201501000,Abulug,2024,fs_workbook,2024,performance.revenue,actual,100.00,,Label,Sheet!D9,'.SHA_WORKBOOK_2024.",1\n");

    importFinances()->assertSuccessful();

    expect(FinanceFigure::query()->count())->toBe(4)
        ->and(FinanceDocument::query()->where('kind', '!=', 'executive_summary')->exists())->toBeFalse();
});

it('publishes a signed-off figure with its report link', function () {
    writeFinanceFolder(['reviews' => ['0201501000,2024,executive_summary,performance.revenue,2024,actual,500.00,verified,2026-10-28,Checked']]);

    importFinances()->assertSuccessful();

    expect(abulugRevenue()?->value)->toBe('500.00')
        ->and(abulugRevenue()?->year)->toBe(2024)
        ->and(abulugRevenue()?->sourceUrl)->toBe('https://www.coa.gov.ph/2024.pdf');
    $this->assertDatabaseHas('stats_finance_year', ['metric' => 'performance.revenue', 'fiscal_year' => 2024, 'value' => '500.00']);
});

it('retains the previous ledger and Analytics result if an import cannot publish', function () {
    writeFinanceFolder(['reviews' => ['0201501000,2024,executive_summary,performance.revenue,2024,actual,500.00,verified,2026-10-28,Checked']]);
    importFinances()->assertSuccessful();
    DB::statement("alter table stats_finance_year add constraint test_refuse_import check (metric <> 'performance.revenue' or value <= 500)");
    writeFinanceFolder([
        'extracted' => ['0201501000,Abulug,2024,executive_summary,2024,performance.revenue,actual,600.00,,Income,page 1,'.SHA_2024.',2'],
        'reviews' => ['0201501000,2024,executive_summary,performance.revenue,2024,actual,600.00,verified,2026-10-28,Checked'],
    ]);

    expect(fn () => Artisan::call('finances:import', ['--path' => financeTestFolder()]))->toThrow(PDOException::class);

    expect(abulugRevenue()?->value)->toBe('500.00');
    $this->assertDatabaseHas('stats_finance_year', ['metric' => 'performance.revenue', 'fiscal_year' => 2024, 'value' => '500.00']);
    $this->assertDatabaseMissing('finance_figures', ['value' => '600.00']);
});

it('keeps sign-offs on re-import but sends a changed value back to pending', function () {
    writeFinanceFolder(['reviews' => [
        '0201501000,2024,executive_summary,performance.revenue,2024,actual,500.00,verified,2026-10-28,',
        '0201501000,2024,executive_summary,performance.expenses,2024,actual,450.00,verified,2026-10-28,',
    ]]);
    importFinances()->assertSuccessful();

    writeFinanceFolder(['extracted' => [
        '0201501000,Abulug,2024,executive_summary,2024,performance.revenue,actual,510.00,,Income,page 1,'.SHA_2024.',2',
        '0201501000,Abulug,2024,executive_summary,2024,performance.expenses,actual,450.00,,Expense,page 1,'.SHA_2024.',2',
    ], 'reviews' => [
        '0201501000,2024,executive_summary,performance.revenue,2024,actual,500.00,verified,2026-10-28,',
    ]]);
    importFinances()->expectsOutputToContain('reviews.csv line 2: the figure is missing or its value changed')->assertSuccessful();

    $revenue = FinanceFigure::query()->where('line_code', 'performance.revenue')->where('fiscal_year', 2024)->sole();
    $expenses = FinanceFigure::query()->where('line_code', 'performance.expenses')->sole();
    expect($revenue->value)->toBe('510.00')
        ->and($revenue->status)->toBe(FinanceFigureStatus::Pending)
        ->and($expenses->status)->toBe(FinanceFigureStatus::Verified)
        ->and(abulugRevenue())->toBeNull();
    $this->assertDatabaseMissing('stats_finance_year', ['metric' => 'performance.revenue', 'fiscal_year' => 2024]);
});

it('never deletes figures entered by hand', function () {
    writeFinanceFolder();
    importFinances()->assertSuccessful();
    $manual = FinanceFigure::factory()->manual()->for(Municipality::query()->sole())->line('performance.revenue', 2022, '300.00')->create();

    importFinances()->assertSuccessful();

    expect(FinanceFigure::query()->whereKey($manual->id)->exists())->toBeTrue();
});

it('refuses partial extraction output that would remove a reviewed observation', function (FinanceFigureStatus $status) {
    writeFinanceFolder();
    importFinances()->assertSuccessful();
    $expenses = FinanceFigure::query()->where('line_code', 'performance.expenses')->sole();
    $expenses->update(['status' => $status, 'reviewed_on' => '2026-10-28']);
    app(RefreshFinanceMetrics::class)->handle();
    $published = DB::table('stats_finance_year')->orderBy('fiscal_year')->orderBy('metric')->get()->all();
    writeFinanceFolder(['extracted' => [
        '0201501000,Abulug,2024,executive_summary,2024,performance.revenue,actual,600.00,,Income,page 2,'.SHA_2024.',2',
    ]]);

    importFinances()->expectsOutputToContain('A reviewed observation is missing from the extraction: performance.expenses 2024.')
        ->assertFailed();

    $this->assertDatabaseCount('finance_figures', 4);
    $this->assertDatabaseHas('finance_figures', ['id' => $expenses->id, 'value' => '450.00', 'status' => $status->value, 'location' => 'page 1']);
    $this->assertDatabaseHas('finance_figures', ['line_code' => 'performance.revenue', 'fiscal_year' => 2024, 'value' => '500.00']);
    expect(DB::table('stats_finance_year')->orderBy('fiscal_year')->orderBy('metric')->get()->all())->toEqual($published);
})->with([FinanceFigureStatus::Verified, FinanceFigureStatus::Superseded, FinanceFigureStatus::Rejected]);

it('refuses replacement of a reviewed document even when the amounts are unchanged', function (string $before, string $after) {
    writeFinanceFolder(['reviews' => ['0201501000,2024,executive_summary,performance.expenses,2024,actual,450.00,verified,2026-10-28,Checked']]);
    importFinances()->assertSuccessful();
    $document = FinanceDocument::query()->where('fiscal_year', 2024)->sole();
    $evidence = $document->only(['availability', 'file', 'sha256', 'source_url']);
    $path = base_path(financeTestFolder().'/documents.csv');
    File::put($path, str_replace($before, $after, File::get($path)));
    foreach (File::files(base_path(financeTestFolder().'/extracted')) as $file) {
        File::put($file->getPathname(), str_replace($before, $after, File::get($file->getPathname())));
    }

    importFinances()->expectsOutputToContain('A reviewed document would be replaced by different source evidence.')
        ->assertFailed();

    expect($document->fresh()->only(['availability', 'file', 'sha256', 'source_url']))->toEqual($evidence);
    $this->assertDatabaseHas('finance_figures', ['line_code' => 'performance.expenses', 'value' => '450.00', 'status' => 'verified']);
    $this->assertDatabaseHas('stats_finance_year', ['fiscal_year' => 2024, 'metric' => 'performance.expenses', 'value' => '450.00']);
})->with([
    'file changed' => ['b.pdf', 'replacement.pdf'],
    'checksum changed' => [SHA_2024, 'cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc'],
    'source link changed' => ['https://www.coa.gov.ph/2024.pdf', 'https://www.coa.gov.ph/replacement.pdf'],
]);

it('keeps the citation reviewed for an unchanged amount while refreshing pending evidence', function (FinanceFigureStatus $status) {
    writeFinanceFolder(['reviews' => ['0201501000,2024,executive_summary,performance.expenses,2024,actual,450.00,verified,2026-10-28,Checked']]);
    importFinances()->assertSuccessful();
    FinanceFigure::query()->where('line_code', 'performance.expenses')->sole()->update(['status' => $status]);
    $inputs = str_replace('"', '""', json_encode([['location' => 'page 9', 'value' => '450.00']]));
    writeFinanceFolder(['extracted' => [
        '0201501000,Abulug,2024,executive_summary,2024,performance.revenue,actual,500.00,,Total Income,page 7,'.SHA_2024.',2',
        '0201501000,Abulug,2024,executive_summary,2024,performance.expenses,actual,450.00,,Total Expenses,page 9,'.SHA_2024.',2,"'.$inputs.'"',
    ]]);

    importFinances()->assertSuccessful();

    $expenses = FinanceFigure::query()->where('line_code', 'performance.expenses')->sole();
    expect($expenses->location)->toBe('page 1')
        ->and($expenses->source_label)->toBe('Expense')
        ->and($expenses->extractor_version)->toBe('1')
        ->and($expenses->inputs)->toBeNull()
        ->and($expenses->status)->toBe($status);
    $this->assertDatabaseHas('finance_figures', ['line_code' => 'performance.revenue', 'fiscal_year' => 2024, 'location' => 'page 7', 'source_label' => 'Total Income', 'status' => 'pending']);
})->with([FinanceFigureStatus::Verified, FinanceFigureStatus::Superseded, FinanceFigureStatus::Rejected]);

it('lets the latest report win for a restated year whatever the review order', function () {
    writeFinanceFolder(['reviews' => [
        '0201501000,2024,executive_summary,performance.revenue,2023,actual,400.00,verified,2026-10-28,',
        '0201501000,2023,executive_summary,performance.revenue,2023,actual,390.00,verified,2026-10-29,',
    ]]);

    importFinances()->assertSuccessful();

    $statuses = FinanceFigure::query()->where('fiscal_year', 2023)->orderBy('value')->pluck('status', 'value')->map->value->all();
    expect($statuses)->toBe(['390.00' => 'superseded', '400.00' => 'verified']);
});

it('rebuilds the same sign-offs from an export after the ledger is wiped', function () {
    writeFinanceFolder(['reviews' => ['0201501000,2024,executive_summary,performance.revenue,2024,actual,500.00,verified,2026-10-28,Checked']]);
    importFinances()->assertSuccessful();
    $manual = FinanceFigure::factory()->manual()->verified()->for(Municipality::query()->sole())->line('performance.expenses', 2023, '420.00')->create();
    $before = FinanceFigure::query()->orderBy('public_id')->get()->map(fn (FinanceFigure $f) => [$f->line_code, $f->fiscal_year, $f->value, $f->status->value]);

    $this->artisan('finances:export', ['--path' => financeTestFolder()])->assertSuccessful();
    FinanceFigure::query()->delete();
    importFinances()->assertSuccessful();

    $after = FinanceFigure::query()->orderBy('public_id')->get()->map(fn (FinanceFigure $f) => [$f->line_code, $f->fiscal_year, $f->value, $f->status->value]);
    expect($after->sortBy(fn ($row) => implode(' ', $row))->values()->all())->toBe($before->sortBy(fn ($row) => implode(' ', $row))->values()->all())
        ->and(FinanceFigure::query()->where('public_id', $manual->public_id)->value('entry_method'))->toBe(FinanceEntryMethod::Manual);
});

it('imports the committed COA data with every reviewed figure signed off and no workbook records', function () {
    Municipality::query()->delete();
    $this->seed(MunicipalitySeeder::class);

    $this->artisan('finances:import')->assertSuccessful();

    $tuguegarao = collect(app(TownAtlasQuery::class)->get()->towns)->firstWhere('code', '0201529000');
    $gattaran = Municipality::query()->where('psgc_code', '0201513000')->sole();
    // Nothing is half reviewed: every pending figure is a parsed one with no reviewer.
    expect(FinanceFigure::query()->where('status', FinanceFigureStatus::Pending)
        ->where(fn ($pending) => $pending->whereNotNull('reviewed_by')->orWhereNotNull('reviewed_on')->orWhere('entry_method', '!=', FinanceEntryMethod::Parsed))
        ->count())->toBe(0)
        ->and(FinanceFigure::query()->where('status', FinanceFigureStatus::Verified)->distinct()->count('municipality_id'))->toBe(29)
        ->and(FinanceDocument::query()->where('kind', '!=', 'executive_summary')->exists())->toBeFalse()
        ->and(FinanceLine::query()->whereNotIn('source', ['executive_summary', 'derived'])->exists())->toBeFalse()
        // Executive Summary lines beyond the totals load for every government (D47, 26-11-13).
        ->and(FinanceFigure::query()->where('line_code', 'position.assets')->distinct()->count('municipality_id'))->toBe(29)
        // Assets as printed in the 2025 Executive Summary, page 1 (metrics.md M35).
        ->and(FinanceFigure::query()->whereHas('municipality', fn ($town) => $town->where('psgc_code', '0201529000'))
            ->where(['status' => FinanceFigureStatus::Verified, 'fiscal_year' => 2025, 'line_code' => 'position.assets'])->value('value'))->toBe('5157038108.96')
        ->and($tuguegarao?->revenue?->value)->toBe('1679019473.60')
        ->and($tuguegarao?->expenditure?->value)->toBe('1403269517.37')
        // The 2024 report heads its columns 2024 then 2023 but prints 2023 first; the parser reads printed order.
        ->and(FinanceFigure::query()->whereBelongsTo($gattaran)->where(['status' => FinanceFigureStatus::Verified, 'fiscal_year' => 2024, 'line_code' => 'performance.revenue'])->value('value'))->toBe('401727932.51');
});

it('loads review candidates without a separate unlocked read for each review', function () {
    $capturing = false;
    $reads = 0;
    DB::listen(function (QueryExecuted $query) use (&$capturing, &$reads) {
        if ($capturing && str_starts_with($query->sql, 'select')
            && str_contains($query->sql, 'from "finance_figures"')
            && ! str_contains($query->sql, 'for update')) {
            $reads++;
        }
    });
    $counts = [];
    foreach ([2, 8] as $size) {
        $years = range(2017, 2016 + $size);
        writeFinanceFolder([
            'extracted' => array_map(fn (int $year) => '0201501000,Abulug,2024,executive_summary,'.$year.',performance.revenue,actual,500.00,,Income,page 1,'.SHA_2024.',1', $years),
            'reviews' => array_map(fn (int $year) => '0201501000,2024,executive_summary,performance.revenue,'.$year.',actual,500.00,verified,2026-10-28,Checked', $years),
        ]);

        $reads = 0;
        $capturing = true;
        importFinances()->assertSuccessful();
        $capturing = false;
        $counts[] = $reads;

        expect(FinanceFigure::query()->where('status', FinanceFigureStatus::Verified)->count())->toBe($size);
    }

    expect($counts[1])->toBeLessThanOrEqual($counts[0] + 1);
});

it('keeps the last manual correction verified when an earlier replay supersedes it', function (bool $uppercaseIds) {
    writeFinanceFolder();
    $first = (string) Str::uuid();
    $last = (string) Str::uuid();
    if ($uppercaseIds) {
        $first = strtoupper($first);
        $last = strtoupper($last);
    }
    FinanceFigure::factory()->manual()->verified()->for(Municipality::query()->sole())
        ->line('performance.revenue', 2024, '700.00')->create(['public_id' => $last]);
    $handle = fopen(base_path(financeTestFolder().'/manual-figures.csv'), 'w');
    fputcsv($handle, ImportFinancesCommand::MANUAL_COLUMNS, escape: '');
    foreach ([[$first, '600.00', '2026-10-27'], [$last, '700.00', '2026-10-28']] as [$id, $value, $date]) {
        fputcsv($handle, [$id, '0201501000', '2024', 'performance.revenue', 'actual', $value, '', '', '',
            'https://www.coa.gov.ph/correction.pdf', '', 'Checked correction', 'verified', $date], escape: '');
    }
    fclose($handle);

    importFinances()->assertSuccessful();

    expect(FinanceFigure::query()->where('public_id', $first)->value('status'))->toBe(FinanceFigureStatus::Superseded)
        ->and(FinanceFigure::query()->where('public_id', $last)->value('status'))->toBe(FinanceFigureStatus::Verified)
        ->and(abulugRevenue()?->value)->toBe('700.00');
})->with([false, true]);

it('replays repeated reviews in order and retains missing and changed value warnings', function (string $year) {
    writeFinanceFolder(['reviews' => [
        '0201501000,2024,executive_summary,performance.revenue,'.$year.',actual,500.00,verified,2026-10-27,First',
        '0201501000,2024,executive_summary,performance.revenue,'.$year.',actual,500.00,rejected,2026-10-28,Second',
        '0201501000,2024,executive_summary,performance.revenue,'.$year.',actual,500.00,verified,2026-10-29,Final',
        '0201501000,2024,executive_summary,performance.expenses,2022,actual,20.00,verified,2026-10-29,Missing',
        '0201501000,2024,executive_summary,performance.revenue,2023,actual,401.00,verified,2026-10-29,Changed',
    ]]);

    importFinances()->expectsOutputToContain('reviews.csv line 5: the figure is missing or its value changed')
        ->expectsOutputToContain('reviews.csv line 6: the figure is missing or its value changed')->assertSuccessful();

    $figure = FinanceFigure::query()->where(['fiscal_year' => 2024, 'line_code' => 'performance.revenue'])->sole();
    expect($figure->status)->toBe(FinanceFigureStatus::Verified)
        ->and($figure->note)->toBe('Final')
        ->and($figure->reviewed_on?->toDateString())->toBe('2026-10-29')
        ->and(FinanceFigure::query()->where('status', FinanceFigureStatus::Verified)->count())->toBe(1);
})->with(['2024', '02024']);
