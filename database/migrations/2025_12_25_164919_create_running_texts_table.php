<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('running_texts', function (Blueprint $table) {
            $table->id();
            $table->string('content'); // Isi teks
            $table->string('link_url')->nullable(); // Kalau diklik lari kemana (opsional)
            $table->boolean('is_active')->default(true); // Status tampil
            $table->integer('sort_order')->default(0); // Urutan
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('running_texts');
    }
};
