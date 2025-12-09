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
        Schema::table('hero_sections', function (Blueprint $table) {
            // 1. Hapus kolom background_image (yang lama)
            $table->dropColumn('background_image');

            // 2. Tambah kolom background_images dengan tipe JSON
            // Ini bisa nyimpan array URL yang lu mau
            $table->json('background_images')->nullable()->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hero_sections', function (Blueprint $table) {
            // 1. Rollback: Hapus kolom background_images (JSON)
            $table->dropColumn('background_images');

            // 2. Rollback: Kembalikan kolom background_image (VARCHAR lama)
            // Sesuaikan default value jika diperlukan
            $table->string('background_image')->nullable()->after('id');
        });
    }
};
