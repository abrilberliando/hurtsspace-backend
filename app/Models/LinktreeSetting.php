<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LinktreeSetting extends Model
{
    protected $fillable = [
        'logo',
        'page_title',
        'page_subtitle',
        'background_type',
        'background_color',
        'background_image',
        'page_text_color',
        'button_bg_color',
        'button_text_color',
        'button_border_color',
        'button_style',
    ];
}
