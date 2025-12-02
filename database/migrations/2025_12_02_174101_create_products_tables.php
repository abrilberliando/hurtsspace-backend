<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Categories
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique(); // URL friendly
            $table->string('image')->nullable();
            $table->timestamps();
        });

        // 2. Products
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->decimal('price', 12, 2); // Harga (up to triliunan aman)
            $table->integer('weight'); // Berat (Gram) -> PENTING BUAT ONGKIR
            $table->boolean('is_collab')->default(false); // Buat menu Collab
            $table->boolean('is_new_arrival')->default(true); // Buat menu New Arrival
            $table->timestamps();
        });

        // 3. Product Variants (Size & Stock)
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('size'); // Contoh: S, M, L, XL
            $table->integer('stock')->default(0); // Stok per ukuran
            $table->timestamps();
        });

        // 4. Product Images (Gallery)
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('image_url');
            $table->boolean('is_primary')->default(false); // Foto utama buat thumbnail
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products_tables');
    }
};
