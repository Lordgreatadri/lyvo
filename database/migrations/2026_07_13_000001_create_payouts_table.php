<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * payouts
 * -------
 * Durable ledger of every Moolre disbursement (transfer) sent from the platform
 * wallet to a recipient — most commonly an operator being paid once escrow funds
 * are released. One row is written when a payout is initiated and updated in
 * place as the transfer response, status polls and settlement webhook arrive.
 *
 * Indexes mirror the queries the app runs: the admin console lists newest-first
 * and facets by status; reconciliation targets a single indexed row by `ref`
 * (our externalref, unique); operator history reads by user_id or payable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Our idempotency key, sent to Moolre as `externalref` and echoed on
            // the transfer webhook.
            $table->string('ref')->unique();

            $table->string('provider', 32);
            $table->string('provider_transaction_id')->nullable();
            $table->string('third_party_ref')->nullable();

            $table->string('channel', 16);
            $table->string('currency', 3)->default('GHS');
            $table->decimal('amount', 12, 2);
            // Gateway fee reported on the transfer response (informational).
            $table->decimal('fee', 12, 2)->nullable();

            // Destination momo number or bank account, and the validated name.
            $table->string('recipient', 32);
            $table->string('recipient_name')->nullable();
            // The platform's Moolre account number the funds leave from.
            $table->string('account_number', 32)->nullable();
            // Bank id when channel is a bank transfer.
            $table->string('sublist_id', 16)->nullable();

            $table->string('status', 20)->default('pending');

            // Where the payout originated (escrow-release, manual, …).
            $table->string('context', 40)->default('escrow-release');
            $table->string('reference')->nullable();

            // The operator being paid.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            // The admin who initiated the payout.
            $table->foreignId('initiated_by')->nullable()->constrained('users')->nullOnDelete();

            // Polymorphic link to the settled record (escrow order, etc.).
            $table->nullableMorphs('payable');

            $table->text('failure_reason')->nullable();
            $table->json('meta')->nullable();

            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('provider_transaction_id');
            $table->index('recipient');
            $table->index('user_id');
            $table->index('context');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};
