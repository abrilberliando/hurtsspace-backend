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
        Schema::table('vouchers', function (Blueprint $table) {
            // Nentuin target: mau potong harga produk atau ongkir
            $table->enum('target', ['products', 'shipping'])->default('products')->after('discount_type');

            // Flag buat ngecek apakah voucher berlaku buat semua barang di cart
            $table->boolean('is_all_products')->default(true)->after('target');

            // (Optional) Limit maksimal diskon buat tipe percent
            $table->decimal('max_discount_amount', 12, 2)->nullable()->after('discount_amount');
        });
    }

    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropColumn(['target', 'is_all_products', 'max_discount_amount']);
        });
    }
};
