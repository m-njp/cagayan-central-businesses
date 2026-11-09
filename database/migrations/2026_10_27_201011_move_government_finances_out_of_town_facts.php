<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Government revenue and expenses now live in the finance ledger (D35); town_facts keeps census and classification figures.
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('town_facts')->whereIn('measure', ['revenue', 'expenditure'])->delete();
        DB::statement('alter table town_facts drop constraint town_facts_measure_check');
        DB::statement(<<<'SQL'
            alter table town_facts add constraint town_facts_measure_check
            check (measure::text in ('population', 'land_area_km2', 'barangay_count', 'income_class', 'poverty_incidence'))
            SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('alter table town_facts drop constraint town_facts_measure_check');
        DB::statement(<<<'SQL'
            alter table town_facts add constraint town_facts_measure_check
            check (measure::text in ('population', 'land_area_km2', 'barangay_count', 'income_class', 'poverty_incidence', 'revenue', 'expenditure'))
            SQL);
    }
};
