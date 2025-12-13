<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hero_sections', function (Blueprint $table) {
            $table->id();
            // Kita cuma butuh 1 baris data, jadi nanti kita update ID 1 terus.
            $table->string('background_image')->nullable(); // Foto Background
            $table->string('subtitle')->nullable();
            $table->string('title')->nullable(); // Judul Besar
            $table->text('description')->nullable(); // Deskripsi di bawah judul
            $table->string('button_text')->nullable(); // Teks Tombol
            $table->string('button_link')->nullable();// Link Tombol
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hero_sections');
    }
};
