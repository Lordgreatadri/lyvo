<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * sms_messages
 * ------------
 * Durable log of every outbound SMS. One row is written the moment a message is
 * accepted for sending and updated as delivery receipts arrive via webhook or a
 * status poll. Indexes are chosen for the queries the app actually runs: the
 * admin log (order by created_at), status reconciliation (by ref), per-status
 * counts and per-recipient / per-user history.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_messages', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            
            // Correlation reference sent to the provider and echoed on webhooks.
            $table->string('ref')->unique();

            $table->string('provider', 32);
            $table->string('sender_id', 11)->nullable();
            $table->string('recipient', 20);
            $table->text('message');

            // Where the send originated (otp, marketing, admin-test, …).
            $table->string('context', 40)->default('manual');

            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('status', 16)->default('pending');
            $table->string('encoding', 8)->nullable();
            $table->unsignedSmallInteger('segments')->default(1);

            $table->string('provider_message_id')->nullable();
            $table->text('failure_reason')->nullable();
            $table->json('meta')->nullable();

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            // Admin log lists newest-first; status filter is the common facet.
            $table->index(['status', 'created_at']);
            $table->index('context');
            $table->index('recipient');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_messages');
    }
};
