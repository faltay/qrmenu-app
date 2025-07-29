<?php
// database/migrations/xxxx_create_qr_codes_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_codes', function (Blueprint $table) {
            $table->id();
            
            // Unique identifier
            $table->string('code')->unique();
            
            // Polymorphic relation (otomatik index ile)
            $table->morphs('qrcodeable');                   // Bu zaten index oluşturur!
            
            // QR Code Details
            $table->enum('type', ['restaurant', 'branch', 'table', 'category', 'item']);
            $table->text('url');
            $table->string('file_path')->nullable();
            
            // Customization
            $table->json('design_options')->nullable();
            $table->boolean('has_logo')->default(false);
            $table->string('format', 10)->default('png');
            $table->integer('size')->default(300);
            
            // Analytics
            $table->integer('scan_count')->default(0);
            $table->timestamp('last_scanned_at')->nullable();
            $table->json('scan_sources')->nullable();
            
            // Status & Lifecycle
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->string('version', 10)->default('1.0');
            
            // Security
            $table->string('access_token')->nullable();
            $table->integer('max_scans')->nullable();
            
            $table->timestamps();

            // Indexes (morphs() zaten qrcodeable index'i oluşturdu)
            // $table->index(['qrcodeable_type', 'qrcodeable_id']); // ← BU SATIRI SİL!
            $table->index(['type', 'is_active']);
            $table->index(['expires_at', 'is_active']);
            $table->index('scan_count');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_codes');
    }
};