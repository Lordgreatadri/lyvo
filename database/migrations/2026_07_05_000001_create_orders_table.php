<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * orders
 * ------
 * One escrow-protected order placed by a customer against a single operator.
 * The delivery address is snapshotted onto the row so historical orders stay
 * accurate even if the customer later edits or deletes the address. The linked
 * `payment_transactions` row (via `payment_transaction_id`) is the money side;
 * this row is the fulfilment/escrow side.
 *
 * Indexed for the queries the dashboards run: an operator lists their orders by
 * status/recency, a customer lists theirs, and escrow analytics aggregate by
 * status.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('order_number')->unique();

            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('operator_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_transaction_id')->nullable()->constrained()->nullOnDelete();

            $table->string('status', 24)->default('pending_payment');

            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->string('currency', 3)->default('GHS');

            // Delivery snapshot (kept even if the source address changes/deletes).
            $table->string('delivery_recipient')->nullable();
            $table->string('delivery_phone', 20)->nullable();
            $table->string('delivery_address')->nullable();
            $table->text('delivery_note')->nullable();

            $table->timestamp('placed_at')->nullable();
            $table->timestamp('funds_held_at')->nullable();
            $table->timestamp('processing_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamp('disputed_at')->nullable();

            $table->timestamps();

            $table->index(['operator_profile_id', 'status']);
            $table->index(['customer_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
