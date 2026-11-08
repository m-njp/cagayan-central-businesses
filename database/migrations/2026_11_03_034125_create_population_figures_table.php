<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('population_figures', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('municipality_id')->constrained()->restrictOnDelete();
            $table->foreignId('barangay_id')->nullable()->constrained()->restrictOnDelete();
            $table->smallInteger('year');
            $table->unsignedBigInteger('value');
            $table->string('source_url', 500);
            $table->string('status', 12)->default('pending');
            $table->foreignId('entered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('reviewed_on')->nullable();
            $table->string('note', 500)->nullable();
            $table->timestamps();
            $table->index(['municipality_id', 'year', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('population_figures');
    }
};
