<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// One Category per Business Type replaces overlapping Focus Tags (D39).
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::rename('focus_tags', 'business_categories');
        DB::statement('alter sequence focus_tags_id_seq rename to business_categories_id_seq');
        DB::statement('alter index focus_tags_pkey rename to business_categories_pkey');
        DB::statement('alter index focus_tags_code_unique rename to business_categories_code_unique');
        DB::statement('alter index focus_tags_name_unique rename to business_categories_name_unique');

        Schema::table('business_types', function (Blueprint $table) {
            $table->foreignId('business_category_id')->nullable()->constrained('business_categories')->restrictOnDelete();
            $table->index('business_category_id');
        });

        // A type that carried several tags keeps the first one by sort order.
        DB::statement(<<<'SQL'
            update business_types set business_category_id = (
                select p.focus_tag_id from business_type_focus_tag p
                join business_categories c on c.id = p.focus_tag_id
                where p.business_type_id = business_types.id
                order by c.sort_order, c.id limit 1
            )
            SQL);

        if (DB::table('business_types')->whereNull('business_category_id')->exists()) {
            $other = DB::table('business_categories')->insertGetId([
                'code' => 'other', 'name' => 'Other Services', 'color' => 1,
                'sort_order' => (int) DB::table('business_categories')->max('sort_order') + 1,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('business_types')->whereNull('business_category_id')->update(['business_category_id' => $other]);
        }

        DB::statement('alter table business_types alter column business_category_id set not null');
        Schema::drop('business_type_focus_tag');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('business_type_focus_tag', function (Blueprint $table) {
            $table->foreignId('business_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('focus_tag_id')->constrained('business_categories')->cascadeOnDelete();
            $table->primary(['business_type_id', 'focus_tag_id']);
            $table->index('focus_tag_id');
        });
        DB::statement('insert into business_type_focus_tag (business_type_id, focus_tag_id) select id, business_category_id from business_types');

        Schema::table('business_types', function (Blueprint $table) {
            $table->dropConstrainedForeignId('business_category_id');
        });

        DB::statement('alter index business_categories_name_unique rename to focus_tags_name_unique');
        DB::statement('alter index business_categories_code_unique rename to focus_tags_code_unique');
        DB::statement('alter index business_categories_pkey rename to focus_tags_pkey');
        DB::statement('alter sequence business_categories_id_seq rename to focus_tags_id_seq');
        Schema::rename('business_categories', 'focus_tags');
    }
};
