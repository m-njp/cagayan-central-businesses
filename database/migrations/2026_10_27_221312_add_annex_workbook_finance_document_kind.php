<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// The Part 4 Annexes workbook is its own file with its own checksum, so it is its own document kind.
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('alter table finance_documents drop constraint finance_documents_kind_check');
        DB::statement(<<<'SQL'
            alter table finance_documents add constraint finance_documents_kind_check
            check (kind::text in ('executive_summary', 'fs_workbook', 'annex_workbook'))
            SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('finance_documents')->where('kind', 'annex_workbook')->delete();
        DB::statement('alter table finance_documents drop constraint finance_documents_kind_check');
        DB::statement(<<<'SQL'
            alter table finance_documents add constraint finance_documents_kind_check
            check (kind::text in ('executive_summary', 'fs_workbook'))
            SQL);
    }
};
