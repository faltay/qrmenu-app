<?php
// database/migrations/xxxx_create_payment_transactions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            
            // Relations
            $table->foreignId('invoice_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_subscription_id')->nullable()->constrained()->onDelete('set null');
            
            // Transaction Identity
            $table->string('transaction_id')->unique();     // Internal transaction ID
            $table->string('gateway');                      // 'stripe', 'iyzico', 'paypal'
            $table->string('gateway_transaction_id');       // External transaction ID
            $table->string('gateway_payment_method_id')->nullable(); // Payment method ID
            
            // Financial Details
            $table->decimal('amount', 10, 2);              // Transaction amount
            $table->string('currency', 3);                 // ISO currency
            $table->decimal('fee_amount', 8, 2)->default(0); // Gateway fee
            $table->decimal('net_amount', 10, 2);          // Amount after fees
            
            // Transaction Details
            $table->enum('type', [
                'payment',          // Regular payment
                'refund',          // Refund
                'partial_refund',  // Partial refund
                'chargeback',      // Chargeback
                'adjustment',      // Manual adjustment
                'fee',             // Gateway fee
                'payout'           // Payout to merchant
            ])->default('payment');
            
            $table->enum('status', [
                'pending',         // Waiting for processing
                'processing',      // Being processed
                'succeeded',       // Successfully completed
                'failed',          // Failed
                'cancelled',       // Cancelled
                'requires_action', // Requires customer action (3D Secure)
                'disputed',        // Under dispute
                'refunded'         // Refunded
            ])->default('pending');
            
            // Payment Method Info
            $table->string('payment_method_type')->nullable(); // 'card', 'bank_transfer', 'wallet'
            $table->json('payment_method_details')->nullable(); // Card last 4, bank info, etc.
            
            // Processing Info
            $table->timestamp('processed_at')->nullable();
            $table->string('processor_reference')->nullable(); // Bank reference number
            $table->string('authorization_code')->nullable();  // Authorization code
            
            // Error Handling
            $table->string('failure_code')->nullable();        // Error code
            $table->text('failure_message')->nullable();       // Error message
            $table->integer('retry_count')->default(0);        // Retry attempts
            
            // Risk & Fraud
            $table->enum('risk_level', ['low', 'medium', 'high'])->nullable();
            $table->decimal('risk_score', 5, 2)->nullable();   // Risk score 0-100
            $table->json('fraud_details')->nullable();         // Fraud detection info
            
            // Webhook & Events
            $table->json('webhook_data')->nullable();          // Raw webhook data
            $table->timestamp('webhook_received_at')->nullable();
            
            // Metadata
            $table->json('metadata')->nullable();              // Additional data
            $table->ipAddress('customer_ip')->nullable();      // Customer IP
            $table->text('customer_user_agent')->nullable();   // Browser info
            
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'status']);
            $table->index(['gateway', 'gateway_transaction_id']);
            $table->index(['status', 'processed_at']);
            $table->index(['type', 'status']);
            $table->index(['invoice_id', 'type']);
            $table->index('transaction_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};