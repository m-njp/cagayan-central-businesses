<?php

use App\Enums\FinanceFigureStatus;
use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\FinanceDocument;
use App\Models\FinanceFigure;
use App\Models\FinanceLine;
use App\Models\Municipality;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->town = Municipality::factory()->create(['psgc_code' => '0201501000', 'name' => 'Abulug']);
    $this->other = Municipality::factory()->create(['psgc_code' => '0201502000', 'name' => 'Alcala']);
    $this->figure = FinanceFigure::factory()->for($this->town)->line('performance.revenue', 2024, '196960688.39')->create();
});

function staffFor(Municipality ...$towns): User
{
    $user = User::factory()->create(['role' => UserRole::Staff]);
    $user->assignedTowns()->attach(collect($towns)->pluck('id'));

    return $user;
}

/** One changed cell of the report grid; cell fields go into the change, the rest are shared by the save. */
function manualEntry(array $overrides = []): array
{
    $change = ['line_code' => 'performance.revenue', 'fiscal_year' => 2024, 'basis' => 'actual', 'value' => '196960688.40', 'base' => '196960688.39'];
    $shared = ['submission' => (string) Str::uuid(), 'document_kind' => 'executive_summary'];
    foreach ($overrides as $key => $value) {
        if (in_array($key, ['line_code', 'fiscal_year', 'basis', 'value', 'base'], true)) {
            $change[$key] = $value;
        } else {
            $shared[$key] = $value;
        }
    }
    // Another cell than the seeded pending revenue figure starts empty.
    if (! array_key_exists('base', $overrides) && [$change['line_code'], $change['fiscal_year']] !== ['performance.revenue', 2024]) {
        $change['base'] = null;
    }

    return [...$shared, 'changes' => [$change]];
}

/** Cell fields are validated per change. */
function entryField(string $field): string
{
    return in_array($field, ['line_code', 'fiscal_year', 'basis', 'value'], true) ? "changes.0.{$field}" : $field;
}

it('hides finance review from guests', function () {
    $this->get(route('capitol.finances.index'))->assertNotFound();
});

it('lists every town for an admin and only assigned towns for staff', function () {
    FinanceFigure::factory()->for($this->other)->line('performance.revenue', 2024, '1.00')->create();

    $this->actingAs(User::factory()->create())->get(route('capitol.finances.index'))
        ->assertInertia(fn (Assert $page) => $page->component('capitol/finances/index')->has('list.rows', 2));

    $this->actingAs(staffFor($this->town))->get(route('capitol.finances.index'))
        ->assertInertia(fn (Assert $page) => $page->has('list.rows', 1)->where('list.rows.0.town', 'Abulug')->where('list.rows.0.pending', 1));
});

it('keeps staff out of towns they are not assigned to', function () {
    $staff = staffFor($this->other);

    $this->actingAs($staff)->get(route('capitol.finances.show', ['town' => '0201501000', 'year' => 2024]))->assertForbidden();
    $this->actingAs($staff)->post(route('capitol.finances.figures.store', ['town' => '0201501000', 'year' => 2024]), manualEntry())->assertForbidden();

    expect(FinanceFigure::query()->count())->toBe(1);
});

it('shows a report with its figures, documents, and who may review', function () {
    $this->actingAs(User::factory()->create())->get(route('capitol.finances.show', ['town' => '0201501000', 'year' => 2024]))
        ->assertInertia(fn (Assert $page) => $page->component('capitol/finances/show/index')
            ->where('report.canReview', true)
            ->where('report.figures.0.value', '196960688.39')
            ->where('report.figures.0.status', 'pending')
            ->where('report.figures.0.source', 'Executive Summary')
            ->where('report.documents.0.label', 'Executive Summary')
            ->missing('report.figures.0.enteredBy'));
});

it('lets assigned staff enter a figure that waits as pending', function () {
    $staff = staffFor($this->town);

    $this->actingAs($staff)->post(route('capitol.finances.figures.store', ['town' => '0201501000', 'year' => 2024]), manualEntry())
        ->assertRedirect(route('capitol.finances.show', ['town' => '0201501000', 'year' => 2024]));

    $entry = FinanceFigure::query()->where('entry_method', 'manual')->sole();
    expect($entry->status)->toBe(FinanceFigureStatus::Pending)
        ->and($entry->entered_by)->toBe($staff->id)
        ->and($entry->value)->toBe('196960688.40')
        ->and($entry->note)->toBeNull()
        ->and($entry->finance_document_id)->toBe($this->figure->finance_document_id)
        ->and(ActivityLog::EVENTS)->toHaveKey('finance_figure_entered')
        ->and(DB::table('activity_logs')->where('event', 'finance_figure_entered')->where('actor_id', $staff->id)->count())->toBe(1);
});

it('opens historical pending figures with comparative years and registered PDF page links', function () {
    $file = 'executive-summaries/2014/Abulug/Abulug-Executive-Summary-2014.pdf';
    $document = FinanceDocument::factory()->for($this->town)->create([
        'fiscal_year' => 2014, 'file' => $file, 'sha256' => hash_file('sha256', base_path('data/towns/'.$file)),
    ]);
    FinanceFigure::factory()->for($this->town)->line('performance.revenue', 2013, '100.00')->create([
        'finance_document_id' => $document->id, 'location' => 'page 2',
    ]);
    $source = route('public.source-reports.show', hash('sha256', $file), absolute: false);

    $this->actingAs(User::factory()->create())->get(route('capitol.finances.show', ['town' => $this->town->psgc_code, 'year' => 2014]))
        ->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('report.year', 2014)->where('report.entryYears', [2013, 2014])
        ->where('report.figures.0.status', 'pending')->where('report.figures.0.location', 'page 2')
        ->where('report.figures.0.sourceUrl', $source.'#page=2')
        ->where('report.documents.0.sourceUrl', $source));
    $this->get(route('capitol.finances.index', ['year' => 2014, 'status' => 'pending']))
        ->assertOk()->assertInertia(fn (Assert $page) => $page
        ->has('list.rows', 1)->where('list.rows.0.pending', 1)->where('list.rows.0.year', 2014));
    $this->assertDatabaseMissing('stats_finance_year', ['fiscal_year' => 2013, 'municipality_id' => $this->town->id]);
});

it('rejects an entry without a report or link, or with a malformed amount', function (array $overrides, string $field) {
    $this->actingAs(User::factory()->create())
        ->post(route('capitol.finances.figures.store', ['town' => '0201501000', 'year' => 2024]), manualEntry($overrides))
        ->assertSessionHasErrors(entryField($field));

    expect(FinanceFigure::query()->count())->toBe(1);
})->with([
    'no source' => [['document_kind' => null, 'source_url' => null], 'source_url'],
    'report not on file' => [['document_kind' => 'annex_workbook'], 'document_kind'],
    'commas in the amount' => [['value' => '196,960,688.40'], 'value'],
    'a line that is not displayed' => [['line_code' => 'position.assets'], 'line_code'],
    'removed note field' => [['note' => 'Unrequested entry note'], 'note'],
]);

it('lets an admin sign off pending figures and logs it', function () {
    $admin = User::factory()->create();

    $this->actingAs($admin)->post(route('capitol.finances.reviews.store', ['town' => '0201501000', 'year' => 2024]), [
        'decision' => 'verified', 'figures' => [$this->figure->public_id],
    ])->assertRedirect();

    expect($this->figure->refresh()->status)->toBe(FinanceFigureStatus::Verified)
        ->and($this->figure->reviewed_by)->toBe($admin->id)
        ->and(DB::table('activity_logs')->where('event', 'finance_figures_signed_off')->count())->toBe(1);
});

it('shows the Admin who signed a figure off and takes no sign-off note', function () {
    $admin = User::factory()->create(['name' => 'Rosa Admin']);
    $route = route('capitol.finances.reviews.store', ['town' => '0201501000', 'year' => 2024]);

    $this->actingAs($admin)->post($route, ['decision' => 'verified', 'figures' => [$this->figure->public_id], 'note' => 'Looks fine'])
        ->assertSessionHasErrors('note');
    expect($this->figure->refresh()->status)->toBe(FinanceFigureStatus::Pending);

    $this->actingAs($admin)->post($route, ['decision' => 'verified', 'figures' => [$this->figure->public_id]])->assertRedirect();

    $this->actingAs(staffFor($this->town))->get(route('capitol.finances.show', ['town' => '0201501000', 'year' => 2024]))
        ->assertInertia(fn (Assert $page) => $page->where('report.figures.0.status', 'verified')
            ->where('report.figures.0.reviewedBy', 'Rosa Admin')
            ->missing('report.figures.0.enteredBy'));
});

it('never lets a reviewer sign off a figure they entered', function () {
    $admin = User::factory()->create();
    $this->actingAs($admin)->post(route('capitol.finances.figures.store', ['town' => '0201501000', 'year' => 2024]), manualEntry());
    $entry = FinanceFigure::query()->where('entry_method', 'manual')->sole();

    $this->actingAs($admin)->post(route('capitol.finances.reviews.store', ['town' => '0201501000', 'year' => 2024]), [
        'decision' => 'verified', 'figures' => [$entry->public_id],
    ])->assertRedirect();

    expect($entry->refresh()->status)->toBe(FinanceFigureStatus::Pending);
});

it('requires a reason to reject', function () {
    $route = route('capitol.finances.reviews.store', ['town' => '0201501000', 'year' => 2024]);
    $admin = User::factory()->create();

    $this->actingAs($admin)->post($route, ['decision' => 'rejected', 'figures' => [$this->figure->public_id]])->assertSessionHasErrors('note');
    $this->actingAs($admin)->post($route, ['decision' => 'rejected', 'figures' => [$this->figure->public_id], 'note' => 'Columns swapped'])->assertRedirect();

    expect($this->figure->refresh()->status)->toBe(FinanceFigureStatus::Rejected);
});

it('rejects invalid finance inputs without writing a figure', function (array $overrides, string $field) {
    $this->actingAs(User::factory()->create())
        ->post(route('capitol.finances.figures.store', ['town' => '0201501000', 'year' => 2024]), manualEntry($overrides))
        ->assertSessionHasErrors(entryField($field));

    $this->assertDatabaseCount('finance_figures', 1);
    $this->assertDatabaseCount('activity_logs', 0);
})->with([
    'negative amount' => [['value' => '-1.00'], 'value'],
    'over the configured cap' => [['value' => '1000000000000.00'], 'value'],
    'three decimals' => [['value' => '1.001'], 'value'],
    'scientific notation' => [['value' => '1e4'], 'value'],
    'too many digits including leading zeros' => [['value' => '0000000000001.00'], 'value'],
    'later than its report' => [['fiscal_year' => 2025], 'fiscal_year'],
    'outside the supported years' => [['fiscal_year' => 2012], 'fiscal_year'],
    'final budget on a revenue line' => [['basis' => 'budget_final'], 'basis'],
    'array instead of a line' => [['line_code' => ['performance.revenue']], 'line_code'],
    'unofficial source' => [['document_kind' => null, 'source_url' => 'https://example.com/report.pdf'], 'source_url'],
    'lookalike COA domain' => [['document_kind' => null, 'source_url' => 'https://coa.gov.ph.example.com/report.pdf'], 'source_url'],
    'COA name in path' => [['document_kind' => null, 'source_url' => 'https://example.com/coa.gov.ph/report.pdf'], 'source_url'],
    'HTTP source' => [['document_kind' => null, 'source_url' => 'http://www.coa.gov.ph/report.pdf'], 'source_url'],
    'credentials in source' => [['document_kind' => null, 'source_url' => 'https://name:pass@www.coa.gov.ph/report.pdf'], 'source_url'],
]);

it('accepts exact cents up to the cap and publishes the manual source to reviewers', function () {
    $source = 'https://www.coa.gov.ph/download/report.pdf';
    $admin = User::factory()->create();

    $this->actingAs($admin)->post(route('capitol.finances.figures.store', ['town' => '0201501000', 'year' => 2024]), manualEntry([
        'value' => config('cagayan.finances.value_max'), 'document_kind' => null, 'source_url' => $source,
    ]))->assertSessionHasNoErrors()->assertRedirect();

    $entry = FinanceFigure::query()->where('entry_method', 'manual')->sole();
    expect($entry->value)->toBe(config('cagayan.finances.value_max'));
    $this->get(route('capitol.finances.show', ['town' => '0201501000', 'year' => 2024]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('breadcrumbs.0.label', 'Government Finances')
            ->where('breadcrumbs.1.label', 'Abulug')
            ->where('breadcrumbs.2.label', 'Report 2024')
            ->where('report.limits.lastYear', 2024)
            ->where('report.figures', fn ($rows) => collect($rows)->firstWhere('id', $entry->public_id)['sourceUrl'] === $source
                && collect($rows)->firstWhere('id', $entry->public_id)['groupLabel'] === 'Results of Operation'));
});

it('allows final budget only for the development spending lines', function () {
    FinanceLine::factory()->create(['code' => 'sector.economic_services', 'group' => 'development']);

    $this->actingAs(User::factory()->create())->post(route('capitol.finances.figures.store', ['town' => '0201501000', 'year' => 2024]), manualEntry([
        'line_code' => 'sector.economic_services', 'basis' => 'budget_final',
    ]))->assertSessionHasNoErrors()->assertRedirect();

    $this->assertDatabaseHas('finance_figures', ['line_code' => 'sector.economic_services', 'basis' => 'budget_final', 'entry_method' => 'manual']);
});

it('returns reusable pagination metadata with filtered page links', function () {
    foreach (range(1, 20) as $index) {
        $town = Municipality::factory()->create(['psgc_code' => '029990'.str_pad((string) $index, 4, '0', STR_PAD_LEFT)]);
        FinanceFigure::factory()->for($town)->create();
    }
    $admin = User::factory()->create();

    $this->actingAs($admin)->get(route('capitol.finances.index', ['status' => 'pending']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('list.pagination.total', 21)->where('list.pagination.lastPage', 2)
            ->where('list.pagination.page', 1)->where('list.pagination.from', 1)->where('list.pagination.to', 20)
            ->where('list.pagination.previous', null)
            ->where('list.pagination.links.1.url', fn (string $url) => str_contains($url, 'status=pending') && str_contains($url, 'page=2')));
    $this->get(route('capitol.finances.index', ['status' => 'pending', 'page' => 2]))
        ->assertInertia(fn (Assert $page) => $page->where('list.pagination.page', 2)->where('list.pagination.from', 21)->where('list.pagination.to', 21)->where('list.pagination.next', null));
});

it('does not let staff review', function () {
    $this->actingAs(staffFor($this->town))->post(route('capitol.finances.reviews.store', ['town' => '0201501000', 'year' => 2024]), [
        'decision' => 'verified', 'figures' => [$this->figure->public_id],
    ])->assertForbidden();

    expect($this->figure->refresh()->status)->toBe(FinanceFigureStatus::Pending);
});

it('only reviews figures that belong to the town and report in the address', function () {
    $elsewhere = FinanceFigure::factory()->for($this->other)->line('performance.revenue', 2024, '5.00')->create();

    $this->actingAs(User::factory()->create())->post(route('capitol.finances.reviews.store', ['town' => '0201501000', 'year' => 2024]), [
        'decision' => 'verified', 'figures' => [$elsewhere->public_id],
    ]);

    expect($elsewhere->refresh()->status)->toBe(FinanceFigureStatus::Pending)
        ->and(FinanceDocument::query()->count())->toBe(2);
});
