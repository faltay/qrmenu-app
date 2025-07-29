<?php
// database/migrations/xxxx_create_qr_code_scans_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_code_scans', function (Blueprint $table) {
            $table->id();
            
            // Relations
            $table->foreignId('qr_code_id')->constrained()->onDelete('cascade');
            
            // Scanner Info
            $table->ipAddress('ip_address');
            $table->text('user_agent')->nullable();
            $table->string('device_type', 20)->nullable();  // mobile, desktop, tablet
            $table->string('browser', 50)->nullable();
            $table->string('os', 50)->nullable();
            $table->string('country', 2)->nullable();       // Detected from IP
            $table->string('city', 100)->nullable();
            
            // Scan Context
            $table->string('referrer')->nullable();         // Where they came from
            $table->timestamp('scanned_at');                // Exact scan time
            $table->boolean('is_unique_visitor')->default(true); // First time scanning
            
            // Session Tracking
            $table->string('session_id')->nullable();       // Browser session
            $table->integer('duration_on_site')->nullable(); // Seconds spent (if tracked)
            
            $table->timestamps();

            // Indexes
            $table->index(['qr_code_id', 'scanned_at']);
            $table->index(['ip_address', 'scanned_at']);
            $table->index(['device_type', 'scanned_at']);
            $table->index(['country', 'scanned_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_code_scans');
    }
};