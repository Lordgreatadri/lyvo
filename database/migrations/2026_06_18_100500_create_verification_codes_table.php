<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * verification_codes
 * ------------------
 * One-time passcodes (OTP) for verifying email addresses and phone numbers.
 * A single table serves every channel/purpose. Codes are stored hashed; the
 * destination (email/phone) is captured so a code stays bound to the value it
 * was sent to even if the user later changes it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verification_codes', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('channel');     // email | sms
            $table->string('purpose');     // email_verification | phone_verification
            $table->string('destination'); // the email or phone the code was sent to
            $table->string('code_hash');   // hashed OTP — never stored in plain text

            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'channel', 'purpose']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_codes');
    }
};
