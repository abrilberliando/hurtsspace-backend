<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    protected $fillable = [
        'code', 'discount_amount', 'discount_type', 'stock', 'start_date', 'end_date'
    ];
}
