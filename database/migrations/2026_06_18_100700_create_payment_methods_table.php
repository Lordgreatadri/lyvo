<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * payment_methods
 * ---------------
 * Saved customer payment instruments (mobile money, card, bank), decoupled from
 * any single gateway. Only non-sensitive metadata + a masked identifier are kept
 * here; full credentials/tokens are never stored on LYVO.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('type');             // mobile_money | card | bank
            $table->string('provider')->nullable(); // MTN MoMo, Vodafone Cash, Visa...
            $table->string('account_name')->nullable();
            $table->string('account_reference')->nullable(); // masked, e.g. ****1234
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
