<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bill extends Model
{
    protected $fillable = [
        'description',
        'amount',
        'date',
        'status',
        'account_id',
        'image',
        'is_monthly',
    ];

    protected $casts = [
        'amount' => 'float',
        'date'   => 'date',
        'is_monthly' => 'boolean',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
