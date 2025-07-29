<?php
// database/migrations/xxxx_create_subscription_plans_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();               // 'free', 'standard', 'pro'
            $table->json('name');                           // {"en": "Standard Plan", "tr": "Standart Plan"}
            $table->json('description')->nullable();        // Çok dilli açıklama
            
            // Pricing
            $table->decimal('price_usd', 8, 2);            // USD fiyat
            $table->decimal('price_try', 8, 2);            // TRY fiyat  
            $table->decimal('price_eur', 8, 2)->nullable(); // EUR fiyat
            $table->enum('billing_period', ['monthly', 'yearly'])->default('monthly');
            
            // Limits
            $table->integer('max_restaurants')->nullable(); // null = unlimited
            $table->integer('max_branches')->nullable();
            $table->integer('max_menu_items')->nullable();
            $table->integer('max_users')->nullable();
            $table->integer('max_qr_scans_monthly')->nullable();
            
            // Features (JSON array)
            $table->json('features');                       // ["basic_menu", "analytics", "api_access"]
            
            // Gateway Integration
            $table->string('stripe_price_id')->nullable();  // Stripe price ID
            $table->string('iyzico_plan_code')->nullable(); // İyzico plan code
            
            // Meta
            $table->boolean('is_popular')->default(false);  // "Most Popular" badge
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            
            $table->timestamps();

            // Indexes
            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};