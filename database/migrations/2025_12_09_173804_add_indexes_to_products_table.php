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
        Schema::table('products', function (Blueprint $table) {
            // Index untuk slug (karena sering dipakai di ProductController@show)
            $table->index('slug');

            // Index untuk category_id (karena dipakai di filtering ShopPage)
            $table->index('category_id');

            // Index untuk name (penting untuk Live Search / Search Query)
            // Menggunakan index type FULLTEXT (khusus MySQL) atau B-Tree biasa
            // B-Tree (index) lebih aman kalau lo gak yakin MySQL lo support FULLTEXT
            $table->index('name');

            // Opsional: Index untuk is_featured/is_new_arrival kalau sering difilter
            $table->index('is_featured');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['slug']);
            $table->dropIndex(['category_id']);
            $table->dropIndex(['name']);
            $table->dropIndex(['is_featured']);
        });
    }
};
