<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * sms_settings
 * ------------
 * A single-row table holding the admin-editable SMS configuration: the active
 * provider, the default sender ID, the low-credit alert threshold and a cached
 * snapshot of the last balance check. Secrets (API keys) live in the
 * environment only and are never stored here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_settings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('provider')->default('log');
            $table->string('sender_id', 11)->nullable();
            $table->unsignedInteger('low_credit_threshold')->default(100);

            // Cached balance snapshot to avoid hitting the gateway on every view.
            $table->decimal('cached_balance', 12, 2)->nullable();
            $table->json('cached_balance_snapshot')->nullable();
            $table->timestamp('balance_checked_at')->nullable();

            // Timestamp of the most recent low-credit alert (throttles duplicates).
            $table->timestamp('low_credit_alerted_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_settings');
    }
};
