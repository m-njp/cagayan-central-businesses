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
        ->component('public/economic-performance/index')
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

test('verified years before the configured first year extend the catalog and the year filter', function () {
    $town = Municipality::factory()->create(['psgc_code' => '0201501000']);
    FinanceFigure::factory()->verified()->for($town)->line('performance.revenue', 2014, '61234567.89')->create();
    FinanceFigure::factory()->for($town)->line('performance.revenue', 2012, '11111111.11')->create();
    $this->artisan('metrics:refresh')->assertSuccessful();

    // The Pending 2012 figure is not published, so the earliest year is the verified 2014.
    $this->get(route('public.economic-performance', ['town' => '0201501000', 'year' => 2014]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('performance.catalog.periods.all', 2014)
            ->where('performance.catalog.finances.0201501000.totals.rows.0.year', 2014)
            ->where('performance.catalog.finances.0201501000.totals.rows.0.observations', fn ($points) => $points['performance.revenue']['value'] === '61234567.89'));
    $this->getJson(route('public.economic-performance', ['year' => 2013]))->assertUnprocessable()->assertJsonValidationErrors('year');
});

test('a missing year says why it is missing and never shows a value', function () {
    $town = Municipality::factory()->create(['psgc_code' => '0201501000']);
    FinanceFigure::factory()->verified()->for($town)->line('performance.revenue', 2024, '100.00')->create();
    FinanceFigure::factory()->for($town)->line('performance.revenue', 2022, '987654321.12')->create();
    FinanceFigure::factory()->verified()->for($town)->line('performance.expenses', 2021, '50.00')->create();
    FinanceDocument::factory()->notPublished()->for($town)->create(['fiscal_year' => 2025]);
    $this->artisan('metrics:refresh')->assertSuccessful();

    $reason = fn (int $row, string $line, string $expected) => fn ($points) => $points[$line]['reason'] === $expected && $points[$line]['value'] === null;
    $this->get(route('public.economic-performance', ['town' => '0201501000']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('performance.catalog.finances.0201501000.totals.rows.6.observations', $reason(6, 'performance.revenue', 'not_published'))
            ->where('performance.catalog.finances.0201501000.totals.rows.3.observations', $reason(3, 'performance.revenue', 'pending_review'))
            ->where('performance.catalog.finances.0201501000.totals.rows.2.observations', $reason(2, 'performance.revenue', 'not_reported'))
            ->where('performance.catalog.finances.0201501000.totals.rows.4.observations', $reason(4, 'performance.revenue', 'not_collected')))
        ->assertDontSee('987654321.12');
});

test('Financial Condition reaches every town with a signed-off figure and other towns say why it is missing, never a value', function () {
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
        ->where('performance.catalog.finances.0201529000.totals.rows.5.observations', fn ($points) => $points['performance.surplus']['value'] === '281080033.01'))
        ->assertDontSee('987654321.12');

    $abulugAssets = $this->getJson(route('public.economic-performance.town', '0201501000'))->assertOk()
        ->json('finances.0201501000.position.rows.5.observations')['position.assets'];
    expect($abulugAssets['reason'])->toBe('not_reported')->and($abulugAssets['value'])->toBeNull();
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

    $this->get(route('public.economic-performance', ['period' => 'recent', 'measure' => 'budget', 'dimension' => 'category', 'financeView' => 'table', 'kind' => 'specific', 'wishView' => 'bars']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('performance.filters', [
                'town' => '0201529000', 'period' => 'recent', 'measure' => 'budget',
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
        ->where('performance.catalog.guide.topics', fn ($topics) => collect($topics)->pluck('title')->doesntContain('Revenue Sources'))
        ->where('performance.catalog.guide.topics.3.title', 'Business Activity Over Time'));
    $query = ['presentation' => 'trends', 'period' => '2y', 'year' => 2024, 'metric' => 'performance.expenses'];
    $this->get(route('public.economic-performance', $query))->assertInertia(fn (Assert $page) => $page
        ->where('performance.filters.presentation', 'trends')->where('performance.filters.period', '2y')
        ->where('performance.filters.year', 2024)->where('performance.filters.metric', 'performance.expenses'));
    $this->actingAs(User::factory()->create())->get(route('capitol.analytics', $query))
        ->assertInertia(fn (Assert $page) => $page->where('filters.presentation', 'trends')
            ->where('filters.period', '2y')->where('filters.year', 2024)->where('filters.metric', 'performance.expenses'));
    $this->get(route('capitol.analytics'))->assertInertia(fn (Assert $page) => $page
        ->where('filters.presentation', 'statement')->where('filters.period', 'all'));
    $this->get(route('public.economic-performance', ['presentation' => 'summary', 'year' => 2024]))
        ->assertInertia(fn (Assert $page) => $page->where('performance.filters.presentation', 'summary')
            ->where('performance.filters.year', 2024));
});

test('public sources trace signed-off and derived figures without exposing pending amounts or review notes', function () {
    $town = Municipality::factory()->create(['psgc_code' => '0201529000', 'name' => 'Tuguegarao City']);
    $file = 'executive-summaries/2025/Tuguegarao-City/Tuguegarao-City-Executive-Summary-2025.pdf';
    $report = FinanceDocument::factory()->for($town)->create(['fiscal_year' => 2025, 'file' => $file, 'source_url' => 'https://www.coa.gov.ph/tuguegarao-2025.pdf']);
    $revenue = FinanceFigure::factory()->verified()->for($town)->line('performance.revenue', 2024, '500.25')->create([
        'finance_document_id' => $report->id, 'location' => 'page 2', 'source_label' => 'Total Revenue', 'note' => 'PRIVATE REVIEW NOTE',
    ]);
    $expenses = FinanceFigure::factory()->verified()->for($town)->line('performance.expenses', 2024, '200.10')->create();
    $anotherTown = Municipality::factory()->create(['psgc_code' => '0201501000']);
    $anotherRevenue = FinanceFigure::factory()->verified()->for($anotherTown)->line('performance.revenue', 2024, '400.00')->create();
    $anotherExpenses = FinanceFigure::factory()->verified()->for($anotherTown)->line('performance.expenses', 2024, '100.00')->create();
    FinanceFigure::factory()->verified()->for($anotherTown)->line('performance.revenue', 2023, '350.00')->create();
    FinanceFigure::factory()->for($town)->line('performance.revenue', 2025, '987654321.12')->create(['note' => 'PENDING REVIEW NOTE']);
    FinanceLine::factory()->create(['code' => 'performance.surplus', 'group' => 'totals', 'source' => 'derived']);
    $this->artisan('metrics:refresh')->assertSuccessful();
    $this->get(route('public.economic-performance'))->assertInertia(fn (Assert $page) => $page
        ->where('performance.catalog.finances.0201529000.totals.rows.5.observations', fn ($points) => $points['performance.revenue']['sourceRefs'] === [$revenue->public_id]
            && $points['performance.surplus']['value'] === '300.15'
            && $points['performance.surplus']['sourceRefs'] === [$revenue->public_id, $expenses->public_id])
        // One evidence entry per figure; the link is our stored copy of the report, never COA's address.
        ->where('performance.catalog.sources.'.$revenue->public_id, [
            'documentKey' => hash('sha256', $file),
            'kind' => 'executive_summary', 'label' => 'Executive Summary', 'town' => 'Tuguegarao City',
            'fiscalYear' => 2024, 'reportYear' => 2025, 'page' => 2, 'location' => 'page 2', 'inputs' => [],
            'url' => route('public.finance-sources.show', $revenue->public_id, absolute: false),
        ])
        // The Sum of Reporting Towns cites COA Annual Audit Reports as a whole, so the first page carries only the
        // selected town's evidence; another town's arrives with that town.
        ->where('performance.catalog.finances.0201500000.totals.rows.5.observations', fn ($points) => $points['performance.surplus']['sourceRefs'] === [])
        ->missing('performance.catalog.sources.'.$anotherRevenue->public_id))
        ->assertDontSee('987654321.12')->assertDontSee('PRIVATE REVIEW NOTE')->assertDontSee('PENDING REVIEW NOTE')
        ->assertDontSee('coa.gov.ph/tuguegarao-2025.pdf');

    $another = $this->getJson(route('public.economic-performance.town', '0201501000'))->assertOk();
    expect($another->json('sources'))->toHaveKeys([$anotherRevenue->public_id, $anotherExpenses->public_id])
        ->and($another->json('sources'))->not->toHaveKey($revenue->public_id);
    $another->assertDontSee('987654321.12')->assertDontSee('PRIVATE REVIEW NOTE');
});

test('the first page carries the Sum of Reporting Towns and the selected town only, and other towns load on demand', function () {
    $tuguegarao = Municipality::factory()->create(['psgc_code' => '0201529000']);
    $abulug = Municipality::factory()->create(['psgc_code' => '0201501000']);
    Municipality::factory()->create(['psgc_code' => '0201502000']);
    FinanceFigure::factory()->verified()->for($tuguegarao)->line('performance.revenue', 2024, '100.00')->create();
    FinanceFigure::factory()->verified()->for($abulug)->line('performance.revenue', 2024, '50.00')->create();
    $this->artisan('metrics:refresh')->assertSuccessful();

    $this->get(route('public.economic-performance', ['town' => '0201529000']))->assertInertia(fn (Assert $page) => $page
        ->where('performance.catalog.financeTowns', fn ($towns) => collect($towns)->sort()->values()->all() === ['0201500000', '0201501000', '0201529000'])
        ->where('performance.catalog.finances', fn ($finances) => collect($finances)->keys()->all() === ['0201500000', '0201529000']));

    $this->getJson(route('public.economic-performance.town', '0201501000'))->assertOk()
        ->assertJsonPath('finances.0201501000.totals.rows.5.observations', fn ($points) => $points['performance.revenue']['value'] === '50.00');
    // A town without signed-off figures is not available, never a fetched empty dataset that reads as zero.
    $this->getJson(route('public.economic-performance.town', '0201502000'))->assertOk()->assertExactJson(['finances' => [], 'sources' => []]);
    $this->getJson(route('public.economic-performance.town', '0209999000'))->assertNotFound();
});

test('a figure lists its contributing inputs and the retired measures are not offered', function () {
    $town = Municipality::factory()->create(['psgc_code' => '0201529000']);
    $inputs = [['location' => 'page 2', 'value' => '60.00'], ['location' => 'page 3', 'value' => '40.00']];
    $actual = FinanceFigure::factory()->verified()->for($town)->line('performance.revenue', 2024, '100.00')->create(['inputs' => $inputs]);
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
            expect($written)->toContain($metric['meaning'], $metric['why']);
        }
    }
    foreach ([...$guide['notes'], ...$guide['budgetSteps'], ...$guide['reasons']] as $entry) {
        expect($written)->toContain($entry['text']);
    }
});

test('the guide explains every displayed finance line and every reason a figure can be missing', function () {
    $guide = json_decode(file_get_contents(resource_path('content/analytics-guide.json')), true, flags: JSON_THROW_ON_ERROR);
    $explained = collect($guide['topics'])->flatMap(fn (array $topic) => $topic['metrics'])
        ->filter(fn (array $metric) => filled($metric['meaning']) && filled($metric['why']))->pluck('id');
    $lines = array_map('str_getcsv', file(base_path('data/towns/finances/lines.csv'), FILE_IGNORE_NEW_LINES));
    $header = array_shift($lines);
    $displayed = collect($lines)->map(fn (array $line) => array_combine($header, array_pad($line, count($header), '')))
        ->filter(fn (array $line) => $line['displayed'] === '1' && array_key_exists($line['group'], config('cagayan.finances.groups')))->pluck('code');

    expect($displayed->diff($explained)->values()->all())->toBe([])
        ->and(collect($guide['reasons'])->pluck('id')->sort()->values()->all())
        ->toBe(['not_collected', 'not_published', 'not_reported', 'pending_review']);
});

test('a measure lists the towns that have it so the picker can say where it applies', function () {
    $tuguegarao = Municipality::factory()->create(['psgc_code' => '0201529000']);
    $abulug = Municipality::factory()->create(['psgc_code' => '0201501000']);
    FinanceLine::factory()->create(['code' => 'performance.revenue', 'group' => 'totals', 'source' => 'executive_summary']);
    FinanceLine::factory()->create(['code' => 'budget.appropriations_current', 'group' => 'budget', 'source' => 'executive_summary']);
    FinanceFigure::factory()->verified()->for($tuguegarao)->line('performance.revenue', 2024, '100.00')->create();
    FinanceFigure::factory()->verified()->for($abulug)->line('performance.revenue', 2024, '50.00')->create();
    FinanceFigure::factory()->verified()->for($tuguegarao)->line('budget.appropriations_current', 2024, '10.00')->create();
    $this->artisan('metrics:refresh')->assertSuccessful();

    $this->get(route('public.economic-performance'))->assertInertia(fn (Assert $page) => $page
        ->where('performance.catalog.measures', fn ($measures) => collect($measures)->firstWhere('value', 'totals')['towns'] === null
            && collect($measures)->firstWhere('value', 'budget')['towns'] === ['0201500000', '0201529000']));
});
