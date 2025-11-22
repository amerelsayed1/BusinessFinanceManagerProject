<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosOrderItem extends Model
{
    protected $fillable = [
        'pos_order_id',
        'product_id',
        'quantity',
        'unit_price',
        'total_line_amount',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_line_amount' => 'decimal:2',
    ];

    public function posOrder()
    {
        return $this->belongsTo(PosOrder::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
