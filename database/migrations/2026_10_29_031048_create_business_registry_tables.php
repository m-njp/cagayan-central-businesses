<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Business registry (D40): registration fields, review state, permits, private documents and record view logs (P9).
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->string('street_address', 160)->nullable();
            $table->string('business_phone', 20)->nullable();
            $table->string('business_email', 120)->nullable();
            $table->boolean('is_storefront')->default(true);
            $table->enum('registration_body', ['dti', 'sec', 'cda'])->nullable();
            $table->string('registration_number', 30)->nullable();
            $table->date('registration_valid_until')->nullable();
            $table->string('tin', 17)->nullable();
            $table->string('rdo_code', 4)->nullable();
            // Existing rows were published before the workflow existed.
            $table->enum('record_status', ['draft', 'submitted', 'returned', 'approved'])->default('approved');
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('review_note', 280)->nullable();
            $table->date('bir_verified_on')->nullable();

            $table->index('record_status');
        });

        Schema::create('business_permits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('permit_number', 30);
            $table->smallInteger('year');
            $table->date('valid_from');
            $table->date('valid_until');
            $table->timestamps();

            $table->unique(['business_id', 'year']);
        });

        Schema::create('business_documents', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->enum('kind', [
                'business_permit', 'bir_cor', 'bir_seal_badge', 'registration', 'barangay_clearance', 'zoning_clearance',
                'sanitary_permit', 'fire_safety', 'occupancy_permit', 'cenro_certificate', 'photo', 'other',
            ]);
            $table->string('path', 300);
            $table->string('original_name', 120);
            $table->string('mime', 60);
            $table->unsignedInteger('size');
            $table->char('sha256', 64);
            $table->date('issued_on')->nullable();
            $table->date('expires_on')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('business_id');
        });

        Schema::create('record_view_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('business_id')->constrained()->restrictOnDelete();
            $table->timestamp('viewed_at');

            $table->index(['business_id', 'viewed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('record_view_logs');
        Schema::dropIfExists('business_documents');
        Schema::dropIfExists('business_permits');
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('submitted_by');
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropIndex(['record_status']);
            $table->dropColumn([
                'street_address', 'business_phone', 'business_email', 'is_storefront', 'registration_body',
                'registration_number', 'registration_valid_until', 'tin', 'rdo_code', 'record_status', 'submitted_at',
                'reviewed_at', 'review_note', 'bir_verified_on',
            ]);
        });
    }
};
