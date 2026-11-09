<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Business records (roadmap F3), Focus Tags (D36), and the base summary table (D7). database.md has every column.
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('focus_tags', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name', 60);
            $table->smallInteger('sort_order');
            $table->timestamps();
        });

        Schema::create('business_type_focus_tag', function (Blueprint $table) {
            $table->foreignId('business_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('focus_tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['business_type_id', 'focus_tag_id']);
            $table->index('focus_tag_id');
        });

        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('business_name', 55);
            $table->foreignId('business_type_id')->constrained()->restrictOnDelete();
            $table->string('description', 280);
            $table->smallInteger('year_established');
            $table->foreignId('barangay_id')->constrained()->restrictOnDelete();
            $table->geometry('location', subtype: 'point', srid: 4326);
            $table->string('owner_name', 80);
            $table->string('contact_number', 20);
            $table->enum('ownership_type', ['sole_proprietorship', 'partnership', 'corporation', 'cooperative']);
            $table->enum('size_category', ['micro', 'small', 'medium']);
            $table->unsignedInteger('employee_count');
            $table->boolean('is_simulated')->default(true);
            $table->string('source', 200)->nullable();
            $table->timestamps();

            $table->index('business_type_id');
            $table->index('barangay_id');
            $table->spatialIndex('location');
        });

        Schema::create('business_years', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->smallInteger('year');
            $table->enum('status', ['new', 'renewed', 'not_renewed', 'closed']);
            $table->decimal('declared_gross_sales', 16, 2)->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'year']);
            $table->index(['year', 'status']);
        });

        DB::statement(<<<'SQL'
            alter table business_years add constraint business_years_sales_only_when_active
            check (declared_gross_sales is null or status in ('new', 'renewed'))
            SQL);

        Schema::create('stats_area_year', function (Blueprint $table) {
            $table->foreignId('barangay_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_type_id')->constrained()->cascadeOnDelete();
            $table->enum('size_category', ['micro', 'small', 'medium']);
            $table->smallInteger('year');
            $table->unsignedInteger('active_count');
            $table->unsignedInteger('new_count');
            $table->unsignedInteger('renewed_count');
            $table->unsignedInteger('not_renewed_count');
            $table->unsignedInteger('closed_count');
            $table->decimal('declared_sales_total', 18, 2);
            $table->decimal('declared_sales_max_single', 16, 2);

            $table->unique(['barangay_id', 'business_type_id', 'size_category', 'year']);
            $table->index(['year', 'barangay_id']);
            $table->index('business_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stats_area_year');
        Schema::dropIfExists('business_years');
        Schema::dropIfExists('businesses');
        Schema::dropIfExists('business_type_focus_tag');
        Schema::dropIfExists('focus_tags');
    }
};
