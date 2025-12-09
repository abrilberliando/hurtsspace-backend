<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('product_images', function (Blueprint $table) {
        // Kolom integer buat nyimpen urutan (1, 2, 3...)
        $table->integer('sort_order')->default(0)->after('is_primary');
    });
}

public function down()
{
    Schema::table('product_images', function (Blueprint $table) {
        $table->dropColumn('sort_order');
    });
}
};
