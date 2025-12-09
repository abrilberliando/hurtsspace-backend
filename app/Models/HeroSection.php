<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HeroSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'background_images', // 👈 Ganti/Tambah field ini
        'subtitle',
        'title',
        'description',
        'button_text',
        'button_link',
    ];

    // 👇 Tambahin casting buat 'background_images' biar otomatis jadi array
    protected $casts = [
        'background_images' => 'array',
    ];
}
