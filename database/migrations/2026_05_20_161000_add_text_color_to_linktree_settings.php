<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('linktree_settings', function (Blueprint $table) {
            $table->string('page_text_color')->default('#ffffff')->after('background_image');
        });
    }

    public function down(): void
    {
        Schema::table('linktree_settings', function (Blueprint $table) {
            $table->dropColumn('page_text_color');
        });
    }
};
