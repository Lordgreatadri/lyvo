<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * escrow_events
 * -------------
 * Immutable audit trail of every escrow state transition on an order (who moved
 * it, from which status to which, and an optional note). Powers the order
 * timeline and gives disputes/audits a tamper-evident history.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('escrow_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('from_status', 24)->nullable();
            $table->string('to_status', 24);
            $table->text('note')->nullable();

            $table->timestamps();

            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('escrow_events');
    }
};
