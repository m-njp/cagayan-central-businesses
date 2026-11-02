<?php

use App\Console\Commands\ImportFinancesCommand;
use App\Data\TownFigure;
use App\Enums\FinanceEntryMethod;
use App\Enums\FinanceFigureStatus;
use App\Models\FinanceFigure;
use App\Models\FinanceLine;
use App\Models\Municipality;
use App\Queries\TownAtlasQuery;
use Database\Seeders\MunicipalitySeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Testing\PendingCommand;

const TEST_FINANCE_FOLDER = 'storage/framework/testing/finances';

const SHA_2023 = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

const SHA_2024 = 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb';

/**
 * Two Abulug reports: the 2024 report restates 2023 revenue as 400.00, the 2023 report had 390.00.
 *
 * @param  array{extracted?: list<string>, reviews?: list<string>}  $override
 */
function writeFinanceFolder(array $override = []): void
{
    $folder = base_path(TEST_FINANCE_FOLDER);
    File::deleteDirectory($folder);
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
    return test()->artisan('finances:import', ['--path' => TEST_FINANCE_FOLDER]);
}

function abulugRevenue(): ?TownFigure
{
    return collect(app(TownAtlasQuery::class)->get()->towns)->firstWhere('code', '0201501000')?->revenue;
}

beforeEach(fn () => Municipality::factory()->create(['psgc_code' => '0201501000', 'name' => 'Abulug']));

afterEach(fn () => File::deleteDirectory(base_path(TEST_FINANCE_FOLDER)));

it('loads parsed figures as pending and publishes none of them', function () {
    writeFinanceFolder();

    importFinances()->assertSuccessful();

    expect(FinanceFigure::query()->count())->toBe(4)
        ->and(FinanceFigure::query()->where('status', '!=', FinanceFigureStatus::Pending)->count())->toBe(0)
        ->and(abulugRevenue())->toBeNull();
});

it('rejects manual imports that bypass the finance entry limits', function (string $value, string $basis, string $source, bool $hasDocument = false) {
    writeFinanceFolder();
    $row = [(string) Str::uuid(), '0201501000', '2024', 'performance.revenue', $basis, $value, '', $hasDocument ? '2024' : '', $hasDocument ? 'executive_summary' : '', $source, '', 'Checked the report', 'pending', ''];
    $handle = fopen(base_path(TEST_FINANCE_FOLDER.'/manual-figures.csv'), 'w');
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

    expect(fn () => Artisan::call('finances:import', ['--path' => TEST_FINANCE_FOLDER]))->toThrow(PDOException::class);

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

it('lets the latest report win for a restated year whatever the review order', function () {
    writeFinanceFolder(['reviews' => [
        '0201501000,2024,executive_summary,performance.revenue,2023,actual,400.00,verified,2026-10-28,',
        '0201501000,2023,executive_summary,performance.revenue,2023,actual,390.00,verified,2026-10-29,',
    ]]);

    importFinances()->assertSuccessful();

    $statuses = FinanceFigure::query()->where('fiscal_year', 2023)->pluck('status', 'value')->map->value->all();
    expect($statuses)->toBe(['390.00' => 'superseded', '400.00' => 'verified']);
});

it('rebuilds the same sign-offs from an export after the ledger is wiped', function () {
    writeFinanceFolder(['reviews' => ['0201501000,2024,executive_summary,performance.revenue,2024,actual,500.00,verified,2026-10-28,Checked']]);
    importFinances()->assertSuccessful();
    $manual = FinanceFigure::factory()->manual()->verified()->for(Municipality::query()->sole())->line('performance.expenses', 2023, '420.00')->create();
    $before = FinanceFigure::query()->orderBy('public_id')->get()->map(fn (FinanceFigure $f) => [$f->line_code, $f->fiscal_year, $f->value, $f->status->value]);

    $this->artisan('finances:export', ['--path' => TEST_FINANCE_FOLDER])->assertSuccessful();
    FinanceFigure::query()->delete();
    importFinances()->assertSuccessful();

    $after = FinanceFigure::query()->orderBy('public_id')->get()->map(fn (FinanceFigure $f) => [$f->line_code, $f->fiscal_year, $f->value, $f->status->value]);
    expect($after->sortBy(fn ($row) => implode(' ', $row))->values()->all())->toBe($before->sortBy(fn ($row) => implode(' ', $row))->values()->all())
        ->and(FinanceFigure::query()->where('public_id', $manual->public_id)->value('entry_method'))->toBe(FinanceEntryMethod::Manual);
});

it('imports the committed COA data signed off, with workbooks only for the configured towns', function () {
    Municipality::query()->delete();
    $this->seed(MunicipalitySeeder::class);

    $this->artisan('finances:import')->assertSuccessful();

    $tuguegarao = collect(app(TownAtlasQuery::class)->get()->towns)->firstWhere('code', '0201529000');
    $gattaran = Municipality::query()->where('psgc_code', '0201513000')->sole();
    expect(FinanceFigure::query()->where('status', FinanceFigureStatus::Pending)->count())->toBe(0)
        ->and(FinanceFigure::query()->where('status', FinanceFigureStatus::Verified)->distinct()->count('municipality_id'))->toBe(29)
        ->and(FinanceFigure::query()->whereHas('document', fn ($document) => $document->whereNot('kind', 'executive_summary'))
            ->whereHas('municipality', fn ($town) => $town->whereNotIn('psgc_code', config('cagayan.finances.workbook_towns')))->exists())->toBeFalse()
        ->and(FinanceFigure::query()->whereIn('line_code', config('cagayan.finances.summary_detail_lines'))
            ->whereHas('municipality', fn ($town) => $town->whereNotIn('psgc_code', config('cagayan.finances.summary_detail_towns')))->exists())->toBeFalse()
        // Assets as printed in the 2025 Executive Summary, page 1 (metrics.md M35).
        ->and(FinanceFigure::query()->whereHas('municipality', fn ($town) => $town->where('psgc_code', '0201529000'))
            ->where(['status' => FinanceFigureStatus::Verified, 'fiscal_year' => 2025, 'line_code' => 'position.assets'])->value('value'))->toBe('5157038108.96')
        ->and($tuguegarao?->revenue?->value)->toBe('1679019473.60')
        ->and($tuguegarao?->expenditure?->value)->toBe('1403269517.37')
        // The 2024 report heads its columns 2024 then 2023 but prints 2023 first; the parser reads printed order.
        ->and(FinanceFigure::query()->whereBelongsTo($gattaran)->where(['status' => FinanceFigureStatus::Verified, 'fiscal_year' => 2024, 'line_code' => 'performance.revenue'])->value('value'))->toBe('401727932.51');
});
