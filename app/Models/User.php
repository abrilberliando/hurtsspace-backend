<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\VerifyEmailCustom;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'uid',
        'name',
        'email',
        'password',
        'role',         // admin/member
        'points',       // society points
        'phone',
        'province_id',
        'city_id',
        'address_detail',
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

    public function sendEmailVerificationNotification()
    {
        // Kirim notifikasi pake Class VerifyEmailCustom yang udah lo bikin
        $this->notify(new VerifyEmailCustom());
    }
}
