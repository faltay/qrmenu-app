<?php
// database/migrations/xxxx_add_restaurant_fields_to_users_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Restaurant relation (foreign key sonra eklenecek)
            if (!Schema::hasColumn('users', 'restaurant_id')) {
                $table->unsignedBigInteger('restaurant_id')->nullable();
            }
            
            // User details
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable();
            }
            if (!Schema::hasColumn('users', 'country')) {
                $table->string('country', 2)->default('US');
            }
            if (!Schema::hasColumn('users', 'billing_address')) {
                $table->json('billing_address')->nullable();
            }
            if (!Schema::hasColumn('users', 'tax_id')) {
                $table->string('tax_id')->nullable();
            }
            
            // Subscription (Cashier'dan gelebilir, kontrol et)
            if (!Schema::hasColumn('users', 'trial_ends_at')) {
                $table->timestamp('trial_ends_at')->nullable();
            }
            if (!Schema::hasColumn('users', 'subscription_expires_at')) {
                $table->timestamp('subscription_expires_at')->nullable();
            }
            
            // Status
            if (!Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
            if (!Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable();
            }
        });
        
        // Indexes ayrı olarak ekle
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'restaurant_id')) {
                $table->index(['restaurant_id', 'is_active']);
            }
            if (Schema::hasColumn('users', 'country')) {
                $table->index('country');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Sadece bizim eklediğimiz sütunları sil
            $columnsToCheck = [
                'restaurant_id', 'phone', 'country', 'billing_address',
                'tax_id', 'subscription_expires_at', 'is_active', 'last_login_at'
            ];
            
            foreach ($columnsToCheck as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
            
            // trial_ends_at'i silme - Cashier'dan gelebilir
        });
    }
};