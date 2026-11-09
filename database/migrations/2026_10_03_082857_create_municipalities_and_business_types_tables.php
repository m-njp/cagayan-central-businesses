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
        Schema::create('municipalities', function (Blueprint $table) {
            $table->id();
            $table->string('psgc_code', 10)->unique();
            $table->string('name');
            $table->enum('kind', ['city', 'municipality']);
            $table->boolean('is_connected')->default(false);
            // Nullable until the boundaries are loaded (roadmap F2).
            $table->geometry('boundary', subtype: 'multipolygon', srid: 4326)->nullable();
            $table->timestamps();

            $table->spatialIndex('boundary');
        });

        Schema::create('business_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->char('psic_section', 1);
            $table->string('psic_section_name');
            $table->smallInteger('sort_order');
            $table->timestamps();

            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_types');
        Schema::dropIfExists('municipalities');
    }
};
