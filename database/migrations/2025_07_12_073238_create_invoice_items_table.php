<?php
// database/migrations/xxxx_create_invoice_items_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            
            // Relations
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            
            // Item Details
            $table->json('description');                    // {"en": "Standard Plan - Monthly", "tr": "Standart Plan - Aylık"}
            $table->string('item_code')->nullable();        // SKU or product code
            $table->enum('item_type', [
                'subscription',     // Monthly/yearly subscription
                'setup_fee',       // One-time setup
                'addon',           // Additional features
                'overage',         // Usage overage
                'discount',        // Discount line item
                'tax',             // Tax line item
                'refund',          // Refund line item
                'custom'           // Custom line item
            ])->default('subscription');
            
            // Pricing
            $table->integer('quantity');                    // Quantity
            $table->decimal('unit_price', 8, 2);           // Price per unit
            $table->decimal('total_price', 10, 2);         // quantity * unit_price
            $table->decimal('discount_amount', 8, 2)->default(0); // Item discount
            
            // Period (for subscriptions)
            $table->timestamp('period_start')->nullable();  // Service period start
            $table->timestamp('period_end')->nullable();    // Service period end
            $table->boolean('is_prorated')->default(false); // Prorated amount
            
            // Tax
            $table->decimal('tax_rate', 5, 4)->default(0);  // Tax rate for this item
            $table->decimal('tax_amount', 8, 2)->default(0); // Tax amount
            $table->boolean('is_tax_exempt')->default(false);
            
            // Metadata
            $table->json('metadata')->nullable();           // Additional item data
            $table->string('external_id')->nullable();      // Reference to external system
            
            // Organization
            $table->integer('sort_order')->default(0);      // Display order
            
            $table->timestamps();

            // Indexes
            $table->index(['invoice_id', 'sort_order']);
            $table->index(['item_type', 'invoice_id']);
            $table->index(['period_start', 'period_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};