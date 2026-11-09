<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Government finance ledger (decisions.md D35): parsed and manually entered COA figures, published only after sign-off.
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('finance_lines', function (Blueprint $table) {
            $table->id();
            $table->string('code', 80)->unique();
            $table->string('parent_code', 80)->nullable();
            $table->string('label', 120);
            $table->string('group', 40);
            $table->string('unit', 10);
            $table->boolean('displayed');
            $table->string('source', 40);
            $table->smallInteger('sort');
            $table->timestamps();
        });

        Schema::create('finance_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('municipality_id')->constrained()->restrictOnDelete();
            $table->smallInteger('fiscal_year');
            $table->enum('kind', ['executive_summary', 'fs_workbook']);
            $table->enum('availability', ['available', 'not_published']);
            $table->string('file', 300)->nullable();
            $table->string('source_url', 500)->nullable();
            $table->char('sha256', 64)->nullable();
            $table->date('checked_on');
            $table->timestamps();

            $table->unique(['municipality_id', 'fiscal_year', 'kind']);
        });

        Schema::create('finance_figures', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('municipality_id')->constrained()->restrictOnDelete();
            $table->smallInteger('fiscal_year');
            $table->string('line_code', 80);
            $table->foreign('line_code')->references('code')->on('finance_lines')->restrictOnDelete();
            $table->enum('basis', ['actual', 'budget_final']);
            $table->decimal('value', 18, 2);
            $table->string('definition_variant', 60)->nullable();
            $table->foreignId('finance_document_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('source_url', 500)->nullable();
            $table->string('source_label', 300)->nullable();
            $table->string('location', 60)->nullable();
            $table->enum('entry_method', ['parsed', 'manual']);
            $table->string('extractor_version', 10)->nullable();
            $table->enum('status', ['pending', 'verified', 'rejected', 'superseded'])->default('pending');
            $table->foreignId('entered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('reviewed_on')->nullable();
            $table->string('note', 500)->nullable();
            $table->timestamps();

            $table->index(['fiscal_year', 'line_code']);
            $table->index(['municipality_id', 'status']);
            $table->index('line_code');
            $table->index('finance_document_id');
        });

        DB::statement(<<<'SQL'
            create unique index finance_figures_one_verified_unique
            on finance_figures (municipality_id, fiscal_year, line_code, basis)
            where status = 'verified'
            SQL);
        DB::statement(<<<'SQL'
            create unique index finance_figures_parsed_unique
            on finance_figures (finance_document_id, line_code, fiscal_year, basis)
            where entry_method = 'parsed'
            SQL);
        DB::statement(<<<'SQL'
            alter table finance_figures add constraint finance_figures_has_source
            check (finance_document_id is not null or source_url is not null)
            SQL);
        DB::statement(<<<'SQL'
            alter table finance_figures add constraint finance_figures_parsed_from_document
            check (entry_method = 'manual' or finance_document_id is not null)
            SQL);
        DB::statement(<<<'SQL'
            alter table finance_figures add constraint finance_figures_reviewed_has_date
            check (status = 'pending' or reviewed_on is not null)
            SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finance_figures');
        Schema::dropIfExists('finance_documents');
        Schema::dropIfExists('finance_lines');
    }
};
