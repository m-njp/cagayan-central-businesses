<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('barangays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('municipality_id')->constrained()->restrictOnDelete();
            $table->string('psgc_code', 10)->unique();
            $table->string('name');
            // Nullable until each is entered from the PSA listing (roadmap F2).
            $table->enum('urban_rural', ['urban', 'rural'])->nullable();
            $table->unsignedInteger('population')->nullable();
            $table->smallInteger('population_year')->nullable();
            $table->string('population_source', 500)->nullable();
            $table->geometry('boundary', subtype: 'multipolygon', srid: 4326)->nullable();
            $table->timestamps();

            $table->index(['municipality_id', 'urban_rural']);
            $table->spatialIndex('boundary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barangays');
    }
};
