<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // Judul Besar
            $table->string('image_left'); // URL Foto Kiri
            $table->string('image_right'); // URL Foto Kanan
            $table->string('link_url'); // Link tujuan saat diklik
            $table->boolean('is_active')->default(true); // Status tayang
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
