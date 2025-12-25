<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('video_banners', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();      // Judul Video
            $table->text('description')->nullable();  // Deskripsi (Text biar muat agak panjang)
            $table->string('video_url');              // URL Video (Local Storage Path)
            $table->string('link_url')->nullable();   // Link tujuan tombol (CTA)
            $table->boolean('is_active')->default(true); // Status tampil di home
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_banners');
    }
};
