<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Public figures per town and year, loaded from data/towns/town-facts.csv by towns:import.
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('town_facts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('municipality_id')->constrained()->cascadeOnDelete();
            $table->enum('measure', [
                'population',
                'land_area_km2',
                'barangay_count',
                'income_class',
                'poverty_incidence',
                'revenue',
                'expenditure',
            ]);
            $table->smallInteger('year');
            $table->decimal('value', 16, 2);
            $table->string('source_url', 500);
            $table->date('checked_on')->nullable();
            $table->timestamps();

            $table->unique(['municipality_id', 'measure', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('town_facts');
    }
};
