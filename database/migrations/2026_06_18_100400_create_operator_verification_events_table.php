<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * operator_verification_events
 * ----------------------------
 * Immutable audit trail of every admin decision on an operator's verification
 * (submitted, in_review, approved, rejected). Keeps a full history instead of
 * only the latest status on the profile — important for trust/fraud review.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operator_verification_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('operator_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operator_verification_events');
    }
};
