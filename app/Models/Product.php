<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'category_id', 'name', 'slug', 'description',
        'price', 'weight', 'is_collab', 'is_new_arrival'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // Satu produk punya banyak ukuran (S, M, L)
    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    // Satu produk punya banyak foto
    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }
}
