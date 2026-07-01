<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * guest_customers
 * ---------------
 * Captures call-in / walk-in customers who place orders WITHOUT registering.
 * A customer representative records their contact details here so an order can
 * be created on their behalf. If the person later registers, `user_id` links
 * the guest record to their account. (Order creation itself ships in a later
 * phase — this table is the identity foundation it will build on.)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_customers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->string('name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->text('notes')->nullable();

            // The customer rep / staff user who captured this guest.
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            // Linked once/if the guest registers a real account.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index('phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_customers');
    }
};
