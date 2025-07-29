<?php
// database/migrations/xxxx_create_menu_items_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            
            // Relations
            $table->foreignId('restaurant_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained()->onDelete('cascade'); // null = all branches
            $table->foreignId('menu_category_id')->constrained()->onDelete('cascade');
            
            // Basic Info
            $table->json('name');                           // {"en": "Margherita Pizza", "tr": "Margherita Pizza"}
            $table->string('slug');                         // URL-friendly name
            $table->json('description')->nullable();        // Item description
            $table->json('ingredients')->nullable();        // {"en": "Tomato, Mozzarella", "tr": "Domates, Mozzarella"}
            
            // Pricing
            $table->decimal('price', 8, 2);                // Base price
            $table->decimal('cost', 8, 2)->nullable();     // Cost price (for profit calculation)
            $table->decimal('discount_price', 8, 2)->nullable(); // Sale price
            $table->timestamp('discount_starts_at')->nullable();
            $table->timestamp('discount_ends_at')->nullable();
            
            // Nutritional Info
            $table->integer('calories')->nullable();
            $table->decimal('protein', 5, 2)->nullable();   // grams
            $table->decimal('carbs', 5, 2)->nullable();     // grams
            $table->decimal('fat', 5, 2)->nullable();       // grams
            $table->integer('prep_time')->nullable();       // minutes
            
            // Dietary & Allergen Info
            $table->json('dietary_tags')->nullable();       // ["vegetarian", "vegan", "gluten_free"]
            $table->json('allergens')->nullable();          // ["nuts", "dairy", "gluten"]
            $table->enum('spice_level', ['none', 'mild', 'medium', 'hot', 'very_hot'])->nullable();
            
            // Variants & Options
            $table->json('sizes')->nullable();              // {"small": 15, "medium": 20, "large": 25}
            $table->json('extras')->nullable();             // {"extra_cheese": 5, "mushrooms": 3}
            
            // Availability
            $table->boolean('is_available')->default(true);
            $table->boolean('is_featured')->default(false); // Featured/recommended items
            $table->integer('stock_quantity')->nullable();  // null = unlimited
            $table->json('available_times')->nullable();    // Time-based availability
            $table->json('available_days')->nullable();     // Day-based availability
            
            // Organization
            $table->integer('sort_order')->default(0);
            
            // Analytics
            $table->integer('view_count')->default(0);      // How many times viewed
            $table->integer('order_count')->default(0);     // How many times ordered
            $table->decimal('rating', 3, 2)->nullable();    // Average rating
            $table->integer('rating_count')->default(0);    // Number of ratings
            
            // SEO
            $table->json('meta_title')->nullable();
            $table->json('meta_description')->nullable();
            
            $table->timestamps();

            // Indexes
            $table->index(['restaurant_id', 'is_available', 'sort_order']);
            $table->index(['menu_category_id', 'is_available']);
            $table->index(['branch_id', 'is_available']);
            $table->index(['is_featured', 'is_available']);
            $table->index(['price', 'is_available']);
            $table->unique(['restaurant_id', 'slug'], 'unique_item_slug_per_restaurant');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};