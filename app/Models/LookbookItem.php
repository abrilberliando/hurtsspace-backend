<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LookbookItem extends Model
{
    protected $fillable = ['lookbook_id', 'product_id', 'x_position', 'y_position'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
