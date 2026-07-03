<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * payment_settings
 * ----------------
 * Single-row, admin-editable configuration for the payment gateway: which
 * provider is active and the settlement currency. Secrets (API keys, account
 * number) are never stored here — they live in the environment only. This is the
 * standalone payment-integration migration; the platform order/escrow tables
 * (which reference payment_transactions) come afterwards.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_settings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('provider', 32)->default('log');
            $table->string('currency', 3)->default('GHS');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_settings');
    }
};
