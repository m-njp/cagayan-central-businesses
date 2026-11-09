<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE wishes ALTER COLUMN business_type_id DROP NOT NULL');

        Schema::table('wishes', function (Blueprint $table) {
            $table->string('specific_business', 120)->nullable();
            $table->string('reason', 255)->nullable()->change();
            $table->index(['municipality_id', 'specific_business']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE wishes ALTER COLUMN business_type_id SET NOT NULL');

        Schema::table('wishes', function (Blueprint $table) {
            $table->dropIndex(['municipality_id', 'specific_business']);
            $table->dropColumn('specific_business');
            $table->string('reason', 280)->nullable()->change();
        });
    }
};
