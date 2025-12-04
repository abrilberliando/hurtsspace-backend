<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'position', // 1 = Utama (Atas), 2 = Kedua (Tengah/Bawah)
        'image_left',
        'image_right',
        'link_url',
        'is_active',
    ];

    /**
     * The "booted" method of the model.
     * Logic: Auto-deactivate other banners IN THE SAME POSITION when one is activated.
     */
    protected static function booted()
    {
        static::saving(function ($banner) {
            // Cek: Kalau banner ini mau diset AKTIF (true)
            if ($banner->is_active) {
                // Update semua banner LAIN yang POSISINYA SAMA jadi TIDAK AKTIF (false)
                static::where('id', '!=', $banner->id)
                      ->where('position', $banner->position) // 👈 Filter berdasarkan posisi
                      ->update(['is_active' => false]);
            }
        });
    }
}
