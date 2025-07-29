<?php
// database/migrations/xxxx_create_restaurants_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurants', function (Blueprint $table) {
            $table->id();
            
            // Basic Info
            $table->string('slug')->unique();               // URL slug
            $table->json('name');                           // {"en": "Pizza Palace", "tr": "Pizza Sarayı"}
            $table->json('description')->nullable();        // Çok dilli açıklama
            
            // Contact Info
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->json('address')->nullable();            // {"en": "123 Main St", "tr": "Ana Cad. 123"}
            $table->string('website')->nullable();
            
            // Location
            $table->string('country', 2);                   // ISO country code
            $table->string('city')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            
            // Business Details
            $table->json('business_hours')->nullable();      // Opening hours JSON
            $table->string('cuisine_type')->nullable();     // "italian", "turkish", etc.
            $table->decimal('average_price', 8, 2)->nullable(); // Average meal price
            
            // Branding
            $table->string('primary_color', 7)->default('#000000');    // Hex color
            $table->string('secondary_color', 7)->default('#ffffff');
            $table->string('logo_path')->nullable();        // Logo file path
            $table->string('banner_path')->nullable();      // Banner image path
            
            // Settings
            $table->string('currency', 3)->default('USD');  // ISO currency code
            $table->string('timezone')->default('UTC');
            $table->json('settings')->nullable();           // Additional settings JSON
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            
            $table->timestamps();

            // Indexes
            $table->index(['country', 'city']);
            $table->index(['is_active', 'is_verified']);
            $table->index('cuisine_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurants');
    }
};