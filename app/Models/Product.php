<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'price',
        'weight',
        'is_collab',
        'is_new_arrival',
        'is_featured', // 👈 WAJIB ADA INI BIAR BISA DI-UPDATE
    ];

    // Relasi ke Kategori
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // Relasi ke Varian (Size/Stok)
    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    // Relasi ke Gambar
    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }
}
