<?php
// database/migrations/xxxx_create_branches_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            
            // Relations
            $table->foreignId('restaurant_id')->constrained()->onDelete('cascade');
            
            // Basic Info
            $table->json('name');                           // {"en": "Downtown Branch", "tr": "Merkez Şube"}
            $table->string('slug');                         // Branch slug (unique per restaurant)
            $table->json('description')->nullable();
            
            // Contact & Location
            $table->string('phone')->nullable();
            $table->json('address');                        // Full address in multiple languages
            $table->string('city');
            $table->string('district')->nullable();
            $table->string('postal_code')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            
            // Operational Details
            $table->json('business_hours')->nullable();      // Different from main restaurant
            $table->integer('table_count')->default(0);     // Number of tables
            $table->integer('capacity')->nullable();        // Total seating capacity
            
            // Staff Management
            $table->foreignId('manager_id')->nullable()->constrained('users')->onDelete('set null');
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('accepts_orders')->default(true);
            $table->timestamp('opening_date')->nullable();
            
            $table->timestamps();

            // Indexes
            $table->index(['restaurant_id', 'is_active']);
            $table->index(['city', 'district']);
            $table->unique(['restaurant_id', 'slug'], 'unique_branch_slug_per_restaurant');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};