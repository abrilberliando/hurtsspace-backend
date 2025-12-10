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
        Schema::table('users', function (Blueprint $table) {
            // Index untuk phone (karena sering diupdate/dicari saat checkout/profile)
            // Kalau kolom phone lo nullabe, indexnya tetap bisa dibuat
            $table->index('phone');

            // Index untuk role (kalau lo sering filter user berdasarkan role admin/member)
            $table->index('role');

            // Kolom 'email' biasanya sudah UNIQUE, tapi kita pastikan indexnya ada (atau bisa diubah jadi index biasa)
            // Karena email pasti digunakan saat login, index ini krusial untuk kecepatan Auth
            // $table->index('email'); // Biasanya sudah unik/index, jadi ini opsional.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['phone']);
            $table->dropIndex(['role']);
            // $table->dropIndex(['email']); // Hapus ini kalau di 'up' tidak ditambahkan
        });
    }
};
