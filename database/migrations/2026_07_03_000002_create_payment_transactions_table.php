<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * payment_transactions
 * --------------------
 * Durable ledger of every Moolre collection. One row is written when a payment
 * is initiated and updated in place as OTP/approval steps and webhook/status
 * receipts arrive. The polymorphic `payable` links a transaction to whatever it
 * funds (e.g. an escrow order) without coupling the payment domain to it.
 *
 * Indexes are chosen for the queries the app actually runs: dashboards list
 * newest-first and facet by status; reconciliation targets a single indexed row
 * by external_ref (unique) or provider_transaction_id; operators/customers read
 * their own history by user_id or payable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Our idempotency key, sent to the gateway as `externalref` and
            // echoed on the payment webhook.
            $table->string('ref')->unique();

            $table->string('provider', 32);
            $table->string('provider_transaction_id')->nullable();
            $table->string('third_party_ref')->nullable();

            $table->string('channel', 16)->nullable();
            $table->string('currency', 3)->default('GHS');
            $table->decimal('amount', 12, 2);
            // Net amount actually credited (minus fees), from status/webhook.
            $table->decimal('value', 12, 2)->nullable();

            $table->string('payer', 20);
            $table->string('account_number', 32)->nullable();

            $table->string('status', 20)->default('pending');

            // Where the payment originated (order, wallet-topup, admin-test, …).
            $table->string('context', 40)->default('order');
            $table->string('reference')->nullable();

            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // Polymorphic link to the funded record (escrow order, etc.).
            $table->nullableMorphs('payable');

            $table->boolean('otp_required')->default(false);
            $table->string('session_id')->nullable();

            $table->text('failure_reason')->nullable();
            $table->json('meta')->nullable();

            $table->timestamp('authorized_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            // Dashboards list newest-first and filter by status.
            $table->index(['status', 'created_at']);
            $table->index('provider_transaction_id');
            $table->index('payer');
            $table->index('user_id');
            $table->index('context');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
