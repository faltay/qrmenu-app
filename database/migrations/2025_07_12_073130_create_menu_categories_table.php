<?php
// database/migrations/xxxx_create_menu_categories_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_categories', function (Blueprint $table) {
            $table->id();
            
            // Relations
            $table->foreignId('restaurant_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained()->onDelete('cascade'); // null = all branches
            
            // Basic Info
            $table->json('name');                           // {"en": "Appetizers", "tr": "Başlangıçlar"}
            $table->string('slug');                         // URL-friendly name
            $table->json('description')->nullable();        // Category description
            
            // Visual
            $table->string('icon')->nullable();             // Icon class or emoji
            $table->string('color', 7)->nullable();         // Hex color code
            $table->string('image_path')->nullable();       // Category image
            
            // Organization
            $table->integer('sort_order')->default(0);      // Display order
            $table->foreignId('parent_id')->nullable()->constrained('menu_categories')->onDelete('cascade'); // Subcategories
            
            // Availability
            $table->boolean('is_active')->default(true);
            $table->json('available_times')->nullable();    // {"breakfast": true, "lunch": true, "dinner": false}
            $table->json('available_days')->nullable();     // Day restrictions
            
            // SEO & Marketing
            $table->json('meta_title')->nullable();
            $table->json('meta_description')->nullable();
            
            $table->timestamps();

            // Indexes
            $table->index(['restaurant_id', 'is_active', 'sort_order']);
            $table->index(['branch_id', 'is_active']);
            $table->index('parent_id');
            $table->unique(['restaurant_id', 'slug'], 'unique_category_slug_per_restaurant');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_categories');
    }
};