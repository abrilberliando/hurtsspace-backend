<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('linktree_settings', function (Blueprint $table) {
            $table->id();
            $table->string('logo')->nullable();
            $table->string('page_title')->default('HURTSSPACE');
            $table->string('page_subtitle')->nullable();
            $table->enum('background_type', ['color', 'image'])->default('color');
            $table->string('background_color')->default('#0a0a0a');
            $table->string('background_image')->nullable();
            $table->string('button_bg_color')->default('#ffffff');
            $table->string('button_text_color')->default('#000000');
            $table->string('button_border_color')->default('#333333');
            $table->enum('button_style', ['solid', 'outline', 'glass'])->default('glass');
            $table->timestamps();
        });

        // Insert default row
        DB::table('linktree_settings')->insert([
            'page_title' => 'HURTSSPACE',
            'page_subtitle' => 'Minimalist Streetwear Indonesia',
            'background_type' => 'color',
            'background_color' => '#0a0a0a',
            'button_bg_color' => '#ffffff',
            'button_text_color' => '#000000',
            'button_border_color' => '#333333',
            'button_style' => 'glass',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('linktree_settings');
    }
};
