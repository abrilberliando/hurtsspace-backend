<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id', 'invoice_number', 'total_price', 'status', 'snap_token',
        'shipping_cost', 'shipping_courier', 'shipping_service', 'shipping_resi', 'shipping_address', 'note'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
