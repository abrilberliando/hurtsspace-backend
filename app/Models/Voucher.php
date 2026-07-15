<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Voucher extends Model
{
    protected $fillable = [
        'code',
        'discount_amount',
        'max_discount_amount', // 👈 Add this
        'discount_type',
        'target',              // 👈 Add this (products/shipping)
        'is_all_products',     // 👈 Add this (true/false)
        'stock',
        'start_date',
        'end_date'
    ];

    /**
     * Casting attribute biar Laravel otomatis ngerubah 0/1 jadi true/false.
     */
    protected $casts = [
        'is_all_products' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    /**
     * Relasi Many-to-Many ke Product.
     * Digunakan kalau is_all_products = false.
     */
    public function products(): BelongsToMany
    {
        // 'voucher_product' adalah nama tabel pivot yang kita buat tadi
        return $this->belongsToMany(Product::class, 'voucher_product');
    }
}
