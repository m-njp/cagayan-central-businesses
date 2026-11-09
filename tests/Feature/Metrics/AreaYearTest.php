<?php

use App\Enums\BusinessStatus;
use App\Models\Barangay;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\BusinessYear;
use Illuminate\Support\Facades\DB;

it('counts statuses and sums declared sales of active years only (stats_area_year)', function () {
    $barangay = Barangay::factory()->create();
    $type = BusinessType::factory()->create();
    $first = Business::factory()->inBarangay($barangay)->ofType($type)->create();
    $second = Business::factory()->inBarangay($barangay)->ofType($type)->create();
    BusinessYear::factory()->for($first)->status(BusinessStatus::Renewed, 2024)->sales('300.00')->create();
    BusinessYear::factory()->for($second)->status(BusinessStatus::New, 2024)->sales('200.00')->create();
    BusinessYear::factory()->for($first)->status(BusinessStatus::NotRenewed, 2025)->create();
    BusinessYear::factory()->for($second)->status(BusinessStatus::Renewed, 2025)->sales('250.00')->create();

    $this->artisan('metrics:refresh')->assertSuccessful();

    // 2024: two active (one new, one renewed), 300 + 200 = 500, largest 300. 2025: one active, one not renewed, 250.
    $rows = DB::table('stats_area_year')->orderBy('year')->get()->keyBy('year');
    expect([$rows[2024]->active_count, $rows[2024]->new_count, $rows[2024]->declared_sales_total, $rows[2024]->declared_sales_max_single])
        ->toBe([2, 1, '500.00', '300.00'])
        ->and([$rows[2025]->active_count, $rows[2025]->not_renewed_count, $rows[2025]->declared_sales_total])
        ->toBe([1, 1, '250.00']);
});
