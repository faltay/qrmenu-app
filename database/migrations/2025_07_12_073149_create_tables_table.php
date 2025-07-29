<?php
// database/migrations/xxxx_create_tables_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tables', function (Blueprint $table) {
            $table->id();
            
            // Relations
            $table->foreignId('restaurant_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            
            // Table Info
            $table->string('number');                       // Table number/name
            $table->string('name')->nullable();             // Optional table name
            $table->json('description')->nullable();        // Table description
            
            // Physical Properties
            $table->integer('capacity');                    // Number of seats
            $table->enum('shape', ['round', 'square', 'rectangle', 'oval'])->nullable();
            $table->string('location')->nullable();         // "window", "patio", "private_room"
            $table->json('position')->nullable();           // {"x": 100, "y": 200} for floor plan
            
            // Features
            $table->json('features')->nullable();           // ["window_view", "power_outlet", "wheelchair_accessible"]
            $table->boolean('is_smoking_allowed')->default(false);
            $table->boolean('is_outdoor')->default(false);
            $table->boolean('is_private')->default(false);
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->enum('status', ['available', 'occupied', 'reserved', 'cleaning', 'out_of_order'])->default('available');
            $table->timestamp('status_updated_at')->nullable();
            
            // Reservation
            $table->boolean('accepts_reservations')->default(true);
            $table->integer('min_reservation_duration')->default(60); // minutes
            $table->integer('max_reservation_duration')->default(180); // minutes
            
            // Pricing (if different pricing per table)
            $table->decimal('service_charge', 5, 2)->default(0.00); // Percentage
            $table->decimal('minimum_order', 8, 2)->nullable();
            
            $table->timestamps();

            // Indexes
            $table->index(['restaurant_id', 'branch_id', 'is_active']);
            $table->index(['branch_id', 'status']);
            $table->index('capacity');
            $table->unique(['branch_id', 'number'], 'unique_table_number_per_branch');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tables');
    }
};