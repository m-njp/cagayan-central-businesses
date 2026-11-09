<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Editable Business Types and colored Focus Tags (D37). database.md has the columns.
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('focus_tags', function (Blueprint $table) {
            // A slot in the color palette (config cagayan.categories.colors), never a color value.
            $table->smallInteger('color')->default(1);
        });

        $slots = (int) config('cagayan.categories.colors');

        DB::statement(<<<SQL
            update focus_tags set color = ordered.slot
            from (
                select id, ((row_number() over (order by sort_order, id) - 1) % {$slots}) + 1 as slot from focus_tags
            ) as ordered
            where focus_tags.id = ordered.id
            SQL);

        // Names that differ only by case are the same name to a person.
        DB::statement('create unique index business_types_name_unique on business_types (lower(name))');
        DB::statement('create unique index focus_tags_name_unique on focus_tags (lower(name))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('drop index if exists focus_tags_name_unique');
        DB::statement('drop index if exists business_types_name_unique');

        Schema::table('focus_tags', function (Blueprint $table) {
            $table->dropColumn('color');
        });
    }
};
