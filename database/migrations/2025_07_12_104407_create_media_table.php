<?php
// database/migrations/xxxx_create_media_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->bigIncrements('id');
            
            // Polymorphic relationship (otomatik index ile)
            $table->morphs('model');                // Bu zaten index oluşturur!
            
            // File details
            $table->uuid('uuid')->nullable()->unique();
            $table->string('collection_name');
            $table->string('name');
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->string('disk');
            $table->string('conversions_disk')->nullable();
            $table->unsignedBigInteger('size');
            
            // Meta data
            $table->json('manipulations');
            $table->json('custom_properties');
            $table->json('generated_conversions');
            $table->json('responsive_images');
            
            // Organization
            $table->integer('order_column')->nullable()->index();

            $table->nullableTimestamps();
            
            // Indexes (morphs() zaten model index'i oluşturdu)
            // $table->index(['model_type', 'model_id']); // ← BU SATIRI SİL!
            $table->index('collection_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};