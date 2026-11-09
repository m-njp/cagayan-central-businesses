<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Signed-off government finance series with derived measures and changes (metrics.md M19 to M27), rebuilt by metrics:refresh.
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stats_finance_year', function (Blueprint $table) {
            // Null means the Sum of Reporting Towns (M27), never the provincial government's own accounts.
            $table->foreignId('municipality_id')->nullable()->constrained()->cascadeOnDelete();
            $table->smallInteger('fiscal_year');
            $table->string('metric', 80);
            $table->decimal('value', 20, 2);
            $table->string('definition_variant', 60)->nullable();
            $table->decimal('change', 20, 2)->nullable();
            $table->decimal('percent_change', 10, 1)->nullable();
            $table->boolean('comparable');
            $table->smallInteger('census_year')->nullable();
            // The report year of a figure that replaced a different earlier figure (M26, restated years).
            $table->smallInteger('restated_in')->nullable();
            $table->smallInteger('reporting_towns')->nullable();
            $table->smallInteger('total_towns')->nullable();
            $table->smallInteger('matched_towns')->nullable();

            $table->index(['municipality_id', 'metric', 'fiscal_year']);
            $table->index(['metric', 'fiscal_year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stats_finance_year');
    }
};
