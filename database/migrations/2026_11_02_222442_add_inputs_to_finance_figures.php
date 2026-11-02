<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_figures', function (Blueprint $table): void {
            $table->jsonb('inputs')->nullable();
            $table->string('location', 255)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('finance_figures', function (Blueprint $table): void {
            $table->dropColumn('inputs');
            $table->string('location', 60)->nullable()->change();
        });
    }
};
