<?php
// database/migrations/xxxx_create_payment_methods_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            
            // Relations
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Gateway Info
            $table->string('gateway');                      // 'stripe', 'iyzico'
            $table->string('gateway_payment_method_id');    // External payment method ID
            $table->string('gateway_customer_id')->nullable(); // External customer ID
            
            // Payment Method Type
            $table->enum('type', ['card', 'bank_account', 'digital_wallet']);
            $table->string('subtype')->nullable();          // 'visa', 'mastercard', 'paypal'
            
            // Card Details (for cards)
            $table->string('last_four', 4)->nullable();     // Last 4 digits
            $table->string('brand')->nullable();            // visa, mastercard, amex
            $table->integer('exp_month')->nullable();       // Expiry month
            $table->integer('exp_year')->nullable();        // Expiry year
            $table->string('country', 2)->nullable();       // Card country
            $table->string('funding')->nullable();          // credit, debit, prepaid
            
            // Bank Account Details (for bank transfers)
            $table->string('bank_name')->nullable();
            $table->string('account_holder_name')->nullable();
            $table->string('account_type')->nullable();     // checking, savings
            
            // Digital Wallet Details
            $table->string('wallet_type')->nullable();      // paypal, apple_pay, google_pay
            $table->string('wallet_email')->nullable();
            
            // Status & Preferences
            $table->boolean('is_default')->default(false);  // Default payment method
            $table->boolean('is_active')->default(true);
            $table->timestamp('verified_at')->nullable();   // When verified
            $table->timestamp('last_used_at')->nullable();  // Last usage
            
            // Security
            $table->json('verification_data')->nullable();  // Verification info
            $table->boolean('requires_verification')->default(false);
            
            // Metadata
            $table->json('metadata')->nullable();
            $table->string('nickname')->nullable();         // User-given name
            
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'is_active']);
            $table->index(['gateway', 'gateway_payment_method_id']);
            $table->index(['user_id', 'is_default']);
            $table->index(['type', 'subtype']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};