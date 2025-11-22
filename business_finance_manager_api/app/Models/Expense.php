<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $fillable = [
        'description',
        'amount',
        'date',
        'category',
        'account_id',
        'is_ads',

    ];

    protected $casts = [
        'amount' => 'float',
        'date'   => 'date',
        'is_ads' => 'boolean',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
