<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosOrder extends Model
{
    protected $fillable = [
        'user_id',
        'account_id',
        'date',
        'total_amount',
        'payment_method',
        'channel',
        'note',
        'shopify_order_id',
    ];

    protected $casts = [
        'date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function items()
    {
        return $this->hasMany(PosOrderItem::class);
    }
}
