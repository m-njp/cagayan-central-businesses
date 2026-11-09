<?php

use App\Enums\FinanceFigureStatus;
use App\Models\FinanceDocument;
use App\Models\FinanceFigure;
use App\Models\FinanceLine;
use App\Models\Municipality;
use App\Models\User;
use App\Models\Wish;
use App\Queries\AnalyticsCatalogQuery;
use App\Queries\SampleWishesQuery;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Filesystem\Filesystem;
use Inertia\Testing\AssertableInertia as Assert;

function economyWithWishes(Closure $alter): void
{
    $demo = $alter(require base_path('data/economic-performance/demo.php'));
    $files = Mockery::mock(Filesystem::class);
    $files->shouldReceive('getRequire')->once()->andReturn($demo);
    app()->instance(SampleWishesQuery::class, new SampleWishesQuery($files));
}

test('the public catalog lists every town with the sum of reporting towns first', function () {
    Municipality::factory()->create(['psgc_code' => '0201529000', 'name' => 'Tuguegarao City']);
    Municipality::factory()->count(28)->sequence(fn (Sequence $sequence) => [
        'psgc_code' => sprintf('02015%02d000', $sequence->index + 1),
    ])->create();
    config(['cagayan.simulated' => false]);

    $this->get(route('public.economic-performance'))->assertInertia(fn (Assert $page) => $page
        ->component('public/economic-performance')
        ->where('simulated', false)
        ->where('performance.catalog.businesses', null)
        ->has('performance.catalog.towns', 30)
        ->where('performance.catalog.towns.0', ['value' => AnalyticsCatalogQuery::ALL_TOWNS, 'label' => 'Sum of Reporting Towns'])
        ->where('performance.catalog.periods', ['all' => 2019, 'recent' => 2023])
        ->where('performance.filters.measure', 'totals')
        ->where('performance.wishes.0201529000.townName', 'Tuguegarao City')
        ->where('performance.wishes.0201529000.source', 'Sample Data')
        ->has('performance.wishes.0201529000.items', 21));
});

test('only signed-off figures reach the public series, with exact cents', function () {
    $town = Municipality::factory()->create(['psgc_code' => '0201501000']);
    Municipality::factory()->create(['psgc_code' => '0201529000']);
    FinanceFigure::factory()->verified()->for($town)->line('performance.revenue', 2024, '196960688.39')->create();
    FinanceFigure::factory()->for($town)->line('performance.revenue', 2025, '987654321.12')->create();
    $this->artisan('metrics:refresh')->assertSuccessful();

    $this->get(route('public.economic-performance', ['town' => '0201501000']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('performance.catalog.finances.0201501000.totals.rows.5.year', 2024)
            ->where('performance.catalog.finances.0201501000.totals.rows.5.observations', fn ($points) => $points['performance.revenue']['value'] === '196960688.39')
            ->where('performance.catalog.finances.0201501000.totals.rows.6.observations', fn ($points) => $points['performance.revenue']['state'] === 'unavailable')
            ->missing('performance.catalog.finances.0201501000.totals.source')
            ->where('performance.catalog.finances.0201500000.totals.rows.5.observations', fn ($points) => $points['performance.revenue']['coverage'] === ['reporting' => 1, 'total' => 2, 'matched' => 0])
            ->missing('performance.catalog.finances.0201529000'))
        ->assertDontSee('987654321.12');
});

test('a missing year says why it is missing and never shows a value', function () {
    $town = Municipality::factory()->create(['psgc_code' => '0201501000']);
    FinanceFigure::factory()->verified()->for($town)->line('performance.revenue', 2024, '100.00')->create();
    FinanceFigure::factory()->for($town)->line('performance.revenue', 2022, '987654321.12')->create();
    FinanceFigure::factory()->verified()->for($town)->line('performance.expenses', 2021, '50.00')->create();
    FinanceDocument::factory()->notPublished()->for($town)->create(['fiscal_year' => 2025]);
    FinanceLine::factory()->create(['code' => 'revenue.tax', 'group' => 'revenue_sources', 'source' => 'fs_workbook']);
    $this->artisan('metrics:refresh')->assertSuccessful();

    $reason = fn (int $row, string $line, string $expected) => fn ($points) => $points[$line]['reason'] === $expected && $points[$line]['value'] === null;
    $this->get(route('public.economic-performance', ['town' => '0201501000']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('performance.catalog.finances.0201501000.totals.rows.6.observations', $reason(6, 'performance.revenue', 'not_published'))
            ->where('performance.catalog.finances.0201501000.totals.rows.3.observations', $reason(3, 'performance.revenue', 'pending_review'))
            ->where('performance.catalog.finances.0201501000.totals.rows.2.observations', $reason(2, 'performance.revenue', 'not_reported'))
            ->where('performance.catalog.finances.0201501000.totals.rows.4.observations', $reason(4, 'performance.revenue', 'not_collected'))
            ->where('performance.catalog.finances.0201501000.revenue_sources.rows.5.observations', $reason(5, 'revenue.tax', 'not_collected')))
        ->assertDontSee('987654321.12');
});

test('Financial Condition reaches the configured town and other towns say it is not loaded, never a value', function () {
    $tuguegarao = Municipality::factory()->create(['psgc_code' => '0201529000']);
    $abulug = Municipality::factory()->create(['psgc_code' => '0201501000']);
    FinanceLine::factory()->create(['code' => 'position.assets', 'group' => 'position', 'label' => 'Assets']);
    FinanceLine::factory()->create(['code' => 'performance.surplus', 'group' => 'totals', 'label' => 'Net Income', 'source' => 'derived']);
    FinanceFigure::factory()->verified()->for($tuguegarao)->line('position.assets', 2024, '4909278026.68')->create();
    FinanceFigure::factory()->verified()->for($tuguegarao)->line('performance.revenue', 2024, '1465722146.22')->create();
    FinanceFigure::factory()->verified()->for($tuguegarao)->line('performance.expenses', 2024, '1184642113.21')->create();
    FinanceFigure::factory()->verified()->for($abulug)->line('performance.revenue', 2024, '196960688.39')->create();
    FinanceFigure::factory()->for($tuguegarao)->line('position.assets', 2025, '987654321.12')->create();
    $this->artisan('metrics:refresh')->assertSuccessful();

    $this->get(route('public.economic-performance'))->assertInertia(fn (Assert $page) => $page
        ->where('performance.catalog.measures', fn ($measures) => collect($measures)->contains(['value' => 'position', 'label' => 'Financial Condition', 'towns' => ['0201500000', '0201529000']]))
        ->where('performance.catalog.finances.0201529000.position.rows.5.observations', fn ($points) => $points['position.assets']['value'] === '4909278026.68')
        ->where('performance.catalog.finances.0201529000.position.rows.6.observations', fn ($points) => $points['position.assets']['reason'] === 'pending_review' && $points['position.assets']['value'] === null)
        // 1,465,722,146.22 - 1,184,642,113.21 = 281,080,033.01, the 2024 report's Net Income.
        ->where('performance.catalog.finances.0201529000.totals.rows.5.observations', fn ($points) => $points['performance.surplus']['value'] === '281080033.01')
        ->where('performance.catalog.finances.0201501000.position.rows.5.observations', fn ($points) => $points['position.assets']['reason'] === 'not_loaded' && $points['position.assets']['value'] === null))
        ->assertDontSee('987654321.12');
});

test('a figure that replaced a different earlier one says which report restated it', function () {
    $town = Municipality::factory()->create(['psgc_code' => '0201501000']);
    FinanceFigure::factory()->for($town)->line('performance.revenue', 2023, '390.00')->create(['status' => FinanceFigureStatus::Superseded, 'reviewed_on' => '2026-10-27']);
    $report = FinanceDocument::factory()->for($town)->create(['fiscal_year' => 2024]);
    FinanceFigure::factory()->verified()->for($town)->line('performance.revenue', 2023, '400.00')->create(['finance_document_id' => $report->id]);
    FinanceFigure::factory()->verified()->for($town)->line('performance.revenue', 2024, '500.00')->create();
    $this->artisan('metrics:refresh')->assertSuccessful();

    $this->get(route('public.economic-performance', ['town' => '0201501000']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('performance.catalog.finances.0201501000.totals.rows.4.observations', fn ($points) => $points['performance.revenue']['restatedIn'] === 2024 && $points['performance.revenue']['value'] === '400.00')
            ->where('performance.catalog.finances.0201501000.totals.rows.5.observations', fn ($points) => $points['performance.revenue']['restatedIn'] === null));
});

test('selections survive a direct link and retired filters are ignored', function () {
    Municipality::factory()->create(['psgc_code' => '0201529000']);

    $this->get(route('public.economic-performance', ['period' => 'recent', 'measure' => 'revenue_sources', 'dimension' => 'category', 'financeView' => 'table', 'kind' => 'specific', 'wishView' => 'bars']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('performance.filters', [
                'town' => '0201529000', 'period' => 'recent', 'measure' => 'revenue_sources',
                'dimension' => 'category', 'group' => null,
                'presentation' => 'statement', 'year' => null, 'metric' => null,
            ]));
});

test('wishes receive curated aggregates and no raw submissions', function () {
    $town = Municipality::factory()->create(['psgc_code' => '0201529000']);
    Wish::factory()->for($town)->create(['specific_business' => 'PRIVATE COMPANY SENTINEL', 'reason' => 'PRIVATE REASON SENTINEL']);

    $this->get(route('public.economic-performance'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('performance.wishes.0201529000.items', 21)
            ->where('performance.wishes.0201529000.items.0', ['id' => 'types:pharmacy', 'label' => 'Pharmacy', 'count' => 18])
            ->where('performance.wishes.0201529000.items.6', ['id' => 'specific:mercury-drug', 'label' => 'Mercury Drug', 'count' => 8])
            ->missing('performance.wishes.0201529000.reason'))
        ->assertDontSee('PRIVATE COMPANY SENTINEL')->assertDontSee('PRIVATE REASON SENTINEL');
});

test('invalid filters fail validation before producing a catalog', function (array $filters, string $field) {
    $this->getJson(route('public.economic-performance', $filters))->assertUnprocessable()->assertJsonValidationErrors($field);
})->with([
    'unknown town' => [['town' => '0201599999'], 'town'],
    'unknown presentation' => [['presentation' => 'table'], 'presentation'],
    'future fiscal year' => [['year' => '2099'], 'year'],
    'unknown period' => [['period' => 'future'], 'period'],
    'unknown measure' => [['measure' => 'assets'], 'measure'],
    'unknown dimension' => [['dimension' => 'barangay'], 'dimension'],
    'malformed group' => [['group' => 'industry:<b>'], 'group'],
    'malformed metric' => [['metric' => '<b>revenue</b>'], 'metric'],
    'array input' => [['town' => ['0201529000']], 'town'],
]);

test('empty wish aggregates remain empty and do not become zero-filled businesses', function () {
    Municipality::factory()->create(['psgc_code' => '0201529000']);
    economyWithWishes(function (array $demo): array {
        $demo['wishes']['types'] = [];
        $demo['wishes']['specific'] = [];

        return $demo;
    });

    $this->get(route('public.economic-performance'))->assertInertia(fn (Assert $page) => $page
        ->where('performance.wishes.0201529000.items', []));
});

test('combined wishes preserve distinct identities and exact counts across equal labels and ties', function () {
    Municipality::factory()->create(['psgc_code' => '0201529000']);
    economyWithWishes(function (array $demo): array {
        $demo['wishes']['types'] = [['id' => 'same', 'label' => 'A Very Long Business Phrase That Must Remain Complete', 'count' => 7]];
        $demo['wishes']['specific'] = [['id' => 'same', 'label' => 'A Very Long Business Phrase That Must Remain Complete', 'count' => 7]];

        return $demo;
    });

    $this->get(route('public.economic-performance'))->assertInertia(fn (Assert $page) => $page
        ->has('performance.wishes.0201529000.items', 2)
        ->where('performance.wishes.0201529000.items.0.id', 'specific:same')
        ->where('performance.wishes.0201529000.items.1.id', 'types:same')
        ->where('performance.wishes.0201529000.items.0.count', 7)
        ->where('performance.wishes.0201529000.items.1.count', 7));
});

test('large combined wish lists retain every complete label', function () {
    Municipality::factory()->create(['psgc_code' => '0201529000']);
    economyWithWishes(function (array $demo): array {
        $demo['wishes']['types'] = array_map(fn (int $index) => [
            'id' => 'label-'.$index,
            'label' => 'Complete Community Business Phrase With Many Words '.$index,
            'count' => 121 - $index,
        ], range(1, 120));
        $demo['wishes']['specific'] = [];

        return $demo;
    });

    $this->get(route('public.economic-performance'))->assertInertia(fn (Assert $page) => $page
        ->has('performance.wishes.0201529000.items', 120)
        ->where('performance.wishes.0201529000.items.0.count', 120)
        ->where('performance.wishes.0201529000.items.119.count', 1));
});

test('the Executive Summary over the maximum period is the default and focused metrics survive validated public and Capitol links', function () {
    Municipality::factory()->create(['psgc_code' => '0201529000']);
    $this->get(route('public.economic-performance'))->assertInertia(fn (Assert $page) => $page
        ->where('performance.filters.presentation', 'statement')
        ->where('performance.filters.period', 'all')
        ->where('performance.filters.metric', null)
        ->where('performance.filters.year', null)
        ->where('performance.catalog.guide.topics.0.title', 'Financial Condition')
        ->where('performance.catalog.guide.topics.4.title', 'Business Taxes and Fees'));
    $query = ['presentation' => 'trends', 'period' => '2y', 'year' => 2024, 'metric' => 'revenue.tax'];
    $this->get(route('public.economic-performance', $query))->assertInertia(fn (Assert $page) => $page
        ->where('performance.filters.presentation', 'trends')->where('performance.filters.period', '2y')
        ->where('performance.filters.year', 2024)->where('performance.filters.metric', 'revenue.tax'));
    $this->actingAs(User::factory()->create())->get(route('capitol.analytics', $query))
        ->assertInertia(fn (Assert $page) => $page->where('filters.presentation', 'trends')
            ->where('filters.period', '2y')->where('filters.year', 2024)->where('filters.metric', 'revenue.tax'));
    $this->get(route('capitol.analytics'))->assertInertia(fn (Assert $page) => $page
        ->where('filters.presentation', 'statement')->where('filters.period', 'all'));
    $this->get(route('public.economic-performance', ['presentation' => 'summary', 'year' => 2024]))
        ->assertInertia(fn (Assert $page) => $page->where('performance.filters.presentation', 'summary')
            ->where('performance.filters.year', 2024));
});

test('public sources trace signed-off and derived figures without exposing pending amounts or review notes', function () {
    $town = Municipality::factory()->create(['psgc_code' => '0201529000', 'name' => 'Tuguegarao City']);
    $report = FinanceDocument::factory()->for($town)->create(['fiscal_year' => 2025, 'source_url' => 'https://www.coa.gov.ph/tuguegarao-2025.pdf']);
    $revenue = FinanceFigure::factory()->verified()->for($town)->line('performance.revenue', 2024, '500.25')->create([
        'finance_document_id' => $report->id, 'location' => 'page 2', 'source_label' => 'Total Revenue', 'note' => 'PRIVATE REVIEW NOTE',
    ]);
    $expenses = FinanceFigure::factory()->verified()->for($town)->line('performance.expenses', 2024, '200.10')->create();
    FinanceFigure::factory()->for($town)->line('performance.revenue', 2025, '987654321.12')->create(['note' => 'PENDING REVIEW NOTE']);
    FinanceLine::factory()->create(['code' => 'performance.surplus', 'group' => 'totals', 'source' => 'derived']);
    $this->artisan('metrics:refresh')->assertSuccessful();
    $this->get(route('public.economic-performance'))->assertInertia(fn (Assert $page) => $page
        ->where('performance.catalog.finances.0201529000.totals.rows.5.observations', fn ($points) => $points['performance.revenue']['sourceRefs'] === [$revenue->public_id]
            && $points['performance.surplus']['value'] === '300.15'
            && $points['performance.surplus']['sourceRefs'] === [$revenue->public_id, $expenses->public_id])
        // One evidence entry per figure; the link is our stored copy of the report, never COA's address.
        ->where('performance.catalog.sources.'.$revenue->public_id, [
            'kind' => 'executive_summary', 'label' => 'Executive Summary', 'town' => 'Tuguegarao City',
            'fiscalYear' => 2024, 'reportYear' => 2025, 'page' => 2, 'location' => 'page 2', 'inputs' => [],
            'url' => route('public.finance-sources.show', $revenue->public_id, absolute: false),
        ])
        ->where('performance.catalog.finances.0201500000.totals.rows.5.observations', fn ($points) => $points['performance.surplus']['sourceRefs'] === [$revenue->public_id, $expenses->public_id]))
        ->assertDontSee('987654321.12')->assertDontSee('PRIVATE REVIEW NOTE')->assertDontSee('PENDING REVIEW NOTE')
        ->assertDontSee('coa.gov.ph/tuguegarao-2025.pdf');
});

test('a summed workbook figure lists its contributing cells and the retired measures are not offered', function () {
    $town = Municipality::factory()->create(['psgc_code' => '0201529000']);
    $inputs = [['location' => '11 - SCBAA!K61', 'value' => '60.00'], ['location' => '11 - SCBAA!K62', 'value' => '40.00']];
    $actual = FinanceFigure::factory()->verified()->for($town)->line('sector.economic_services', 2024, '100.00')->create(['inputs' => $inputs]);
    $this->artisan('metrics:refresh')->assertSuccessful();
    $this->get(route('public.economic-performance'))->assertInertia(fn (Assert $page) => $page
        ->where("performance.catalog.sources.{$actual->public_id}.inputs", $inputs)
        ->missing('performance.catalog.finances.0201529000.per_capita')
        ->missing('performance.catalog.finances.0201529000.development_share'));
});

test('the learning guide retains the shared help definitions', function () {
    $guide = json_decode(file_get_contents(resource_path('content/analytics-guide.json')), true, flags: JSON_THROW_ON_ERROR);
    $written = file_get_contents(base_path('docs/product/analytics-guide.md'));
    foreach ($guide['topics'] as $topic) {
        expect($written)->toContain($topic['summary']);
        foreach ($topic['metrics'] as $metric) {
            expect($written)->toContain($metric['meaning'], $metric['formula']);
        }
    }
    foreach ($guide['notes'] as $note) {
        expect($written)->toContain($note['text']);
    }
});

test('a measure lists the towns that have it so the picker can say where it applies', function () {
    $tuguegarao = Municipality::factory()->create(['psgc_code' => '0201529000']);
    $abulug = Municipality::factory()->create(['psgc_code' => '0201501000']);
    FinanceLine::factory()->create(['code' => 'performance.revenue', 'group' => 'totals', 'source' => 'executive_summary']);
    FinanceLine::factory()->create(['code' => 'revenue.tax', 'group' => 'revenue_sources', 'source' => 'fs_workbook']);
    FinanceFigure::factory()->verified()->for($tuguegarao)->line('performance.revenue', 2024, '100.00')->create();
    FinanceFigure::factory()->verified()->for($abulug)->line('performance.revenue', 2024, '50.00')->create();
    FinanceFigure::factory()->verified()->for($tuguegarao)->line('revenue.tax', 2024, '10.00')->create();
    $this->artisan('metrics:refresh')->assertSuccessful();

    $this->get(route('public.economic-performance'))->assertInertia(fn (Assert $page) => $page
        ->where('performance.catalog.measures', fn ($measures) => collect($measures)->firstWhere('value', 'totals')['towns'] === null
            && collect($measures)->firstWhere('value', 'revenue_sources')['towns'] === ['0201500000', '0201529000']));
});
