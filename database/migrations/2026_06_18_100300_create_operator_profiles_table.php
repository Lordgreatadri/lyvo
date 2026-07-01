<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * operator_profiles
 * -----------------
 * One-to-one extension of `users` for operator (verified business) accounts.
 * Holds the business identity and the admin-approval verification state. File
 * assets (Ghana Card front/back, verification video, logo, cover) are NOT stored
 * here — they live in the Spatie media library, attached to the OperatorProfile.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operator_profiles', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_category_id')->nullable()->constrained()->nullOnDelete();

            // Business identity
            $table->string('business_name');
            $table->string('owner_full_name');
            $table->string('business_location')->nullable();
            $table->text('business_description')->nullable();

            // Identity verification (Ghana Card). Number is sensitive — encrypted at rest.
            $table->text('ghana_card_number')->nullable();

            // Admin approval workflow
            $table->string('verification_status')->default('pending')->index();
            $table->timestamp('ghana_card_submitted_at')->nullable();
            $table->timestamp('video_submitted_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();

            // Denormalised trust metrics (computed later; defaulted now)
            $table->unsignedTinyInteger('trust_score')->default(0);

            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operator_profiles');
    }
};
