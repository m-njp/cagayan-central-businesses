<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Workbook reading is retired (decisions.md D49): the Executive Summary is the only source of figures.
// The removed rows are frozen in data/towns/finances/history; the original workbooks stay downloadable.
return new class extends Migration
{
    private const array WORKBOOK_SOURCES = ['fs_workbook', 'fs_totals', 'fs_scbaa', 'annex_c1'];

    private const array DERIVED_LINES = [
        'revenue.remaining', 'revenue.local_share', 'sector.development_fund.utilization', 'sector.economic_services.utilization',
    ];

    public function up(): void
    {
        $lines = DB::table('finance_lines')->whereIn('source', self::WORKBOOK_SOURCES)->orWhereIn('code', self::DERIVED_LINES)->pluck('code');
        $documents = DB::table('finance_documents')->where('kind', '!=', 'executive_summary')->pluck('id');

        DB::table('finance_figures')->whereIn('line_code', $lines)->orWhereIn('finance_document_id', $documents)->delete();
        DB::table('stats_finance_year')->whereIn('metric', $lines)->delete();
        DB::table('finance_documents')->whereIn('id', $documents)->delete();
        DB::table('finance_uploads')->where('kind', '!=', 'executive_summary')->delete();
        DB::table('finance_lines')->whereIn('code', $lines)->delete();
    }

    public function down(): void
    {
        // The retired rows are not restored; rebuild them from the frozen history if workbook reading ever returns.
    }
};
