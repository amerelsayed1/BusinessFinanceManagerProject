<?php
// app/Models/Product.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'sku',
        'category',
        'cost_price',
        'selling_price',
        'current_stock',
        'is_active',
        'shopify_product_id',
        'shopify_variant_id',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'current_stock' => 'integer',
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function posOrderItems()
    {
        return $this->hasMany(PosOrderItem::class);
    }
}
