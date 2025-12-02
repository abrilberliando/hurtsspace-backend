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
        // 1. Orders (Headernya Transaksi)
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('invoice_number')->unique(); // INV-2025-0001

            // Duit-duitan
            $table->decimal('total_price', 15, 2);
            $table->enum('status', ['pending', 'paid', 'processing', 'shipped', 'completed', 'cancelled'])->default('pending');
            $table->string('snap_token')->nullable(); // Token Midtrans

            // Info Pengiriman & Resi
            $table->decimal('shipping_cost', 12, 2)->default(0);
            $table->string('shipping_courier')->nullable(); // jne, jnt, sicepat (dari Biteship)
            $table->string('shipping_service')->nullable(); // REG, YES, BEST
            $table->string('shipping_resi')->nullable(); // Diisi Admin manual
            $table->text('shipping_address'); // Snapshot alamat user pas beli

            $table->timestamps();
        });

        // 2. Order Items (Detail Barang yg dibeli)
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained();
            $table->foreignId('product_variant_id')->constrained('product_variants'); // Biar tau user beli size apa
            $table->integer('quantity');
            $table->decimal('price', 12, 2); // Harga saat beli (biar history aman kalau harga produk naik)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders_tables');
    }
};
