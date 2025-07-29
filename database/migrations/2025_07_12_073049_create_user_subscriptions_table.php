<?php
// database/migrations/xxxx_create_user_subscriptions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_subscriptions', function (Blueprint $table) {
            $table->id();
            
            // Relations
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('subscription_plan_id')->constrained()->onDelete('restrict');
            
            // Gateway Info
            $table->string('gateway');                       // 'stripe' or 'iyzico'
            $table->string('gateway_subscription_id');       // External subscription ID
            $table->string('gateway_customer_id')->nullable(); // External customer ID
            
            // Subscription Details
            $table->enum('status', ['trialing', 'active', 'past_due', 'canceled', 'unpaid']);
            $table->decimal('amount', 10, 2);               // Actual paid amount
            $table->string('currency', 3);                  // Payment currency
            
            // Billing Cycle
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('current_period_start');
            $table->timestamp('current_period_end');
            $table->timestamp('canceled_at')->nullable();
            $table->timestamp('ends_at')->nullable();       // When subscription actually ends
            
            // Usage Tracking (for limits)
            $table->integer('current_restaurants')->default(0);
            $table->integer('current_branches')->default(0);
            $table->integer('current_menu_items')->default(0);
            $table->integer('current_users')->default(0);
            $table->integer('monthly_qr_scans')->default(0);
            $table->timestamp('usage_reset_at')->nullable(); // Last usage reset
            
            // Metadata
            $table->json('metadata')->nullable();           // Additional data
            
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'status']);
            $table->index(['gateway', 'gateway_subscription_id']);
            $table->index(['current_period_end', 'status']);
            $table->unique(['user_id', 'status'], 'unique_active_subscription');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_subscriptions');
    }
};