<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * customer_profiles
 * -----------------
 * One-to-one extension of `users` for customer-specific attributes. Kept as a
 * separate table (rather than nullable columns on `users`) so the auth table
 * stays lean and customer features can grow independently.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_profiles', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('preferred_name')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->boolean('marketing_opt_in')->default(false);
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_profiles');
    }
};
