<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('linktree_links', function (Blueprint $table) {
            $table->id();
            $table->string('icon')->default('globe'); // preset icon name
            $table->string('custom_icon')->nullable(); // URL custom icon image
            $table->string('label');
            $table->string('url');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('linktree_links');
    }
};
