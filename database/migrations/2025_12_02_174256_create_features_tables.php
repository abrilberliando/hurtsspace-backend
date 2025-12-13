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
        // 1. Wishlists (Barang Inceran)
        Schema::create('wishlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        // 2. Vouchers (Diskon)
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // HURTS20
            $table->decimal('discount_amount', 12, 2);
            $table->enum('discount_type', ['percent', 'fixed']); // Potongan % atau Rupiah
            $table->integer('stock')->default(100); // Kuota voucher
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->timestamps();
        });

        // 3. Lookbooks (Galeri Gaya)
        Schema::create('lookbooks', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('image_url');
            $table->timestamps();
        });

        // 4. Lookbook Items (Titik Hotspot di Foto)
        Schema::create('lookbook_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lookbook_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade'); // Produk baju/celananya
            $table->integer('x_position'); // Koordinat X (0-100%)
            $table->integer('y_position'); // Koordinat Y (0-100%)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('features_tables');
    }
};
