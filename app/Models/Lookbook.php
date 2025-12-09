<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lookbook extends Model
{
    protected $fillable = ['title', 'image_url'];

    // Relasi ke titik-titik hotspot di foto
    public function items()
    {
        return $this->hasMany(LookbookItem::class);
    }
}
