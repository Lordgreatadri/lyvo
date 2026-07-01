<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();

            // Single auth table for everyone. account_type routes the user to the
            // correct dashboard and maps to a Spatie role of the same name.
            $table->string('account_type')->default('customer')->index();
            $table->string('status')->default('active')->index();

            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();

            // Phone is a first-class login + verification identity alongside email.
            $table->string('phone')->nullable()->unique();
            $table->timestamp('phone_verified_at')->nullable();

            $table->string('password');
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
