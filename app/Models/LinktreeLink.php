<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LinktreeLink extends Model
{
    protected $fillable = [
        'icon',
        'custom_icon',
        'label',
        'url',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];
}
