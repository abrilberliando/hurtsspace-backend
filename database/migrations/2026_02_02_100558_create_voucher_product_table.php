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
        Schema::create('voucher_product', function (Blueprint $table) {
            $table->id();
            // Foreign key ke tabel vouchers
            $table->foreignId('voucher_id')->constrained()->onDelete('cascade');
            // Foreign key ke tabel products
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_product');
    }
};
