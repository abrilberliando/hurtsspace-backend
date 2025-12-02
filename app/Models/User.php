<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',         // admin/member
        'points',       // society points
        'phone',
        'province_id',
        'city_id',
        'address_detail',
        'avatar',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Relasi: User punya banyak Order
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    // Relasi: User punya banyak Wishlist
    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }
}
