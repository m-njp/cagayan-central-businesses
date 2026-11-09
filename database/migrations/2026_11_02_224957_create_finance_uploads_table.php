<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_uploads', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('municipality_id')->constrained()->restrictOnDelete();
            $table->smallInteger('fiscal_year');
            $table->string('kind', 20);
            $table->string('path', 255);
            $table->string('original_name', 255);
            $table->char('sha256', 64);
            $table->unsignedBigInteger('size');
            $table->string('status', 10)->default('queued');
            $table->unsignedInteger('figures')->default(0);
            $table->jsonb('issues')->nullable();
            $table->string('message', 500)->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['municipality_id', 'fiscal_year', 'kind', 'sha256']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_uploads');
    }
};
