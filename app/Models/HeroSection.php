<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HeroSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'background_image',
        'subtitle',
        'title',
        'description',
        'button_text',
        'button_link',
    ];
}
