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
    ];

    protected $casts = [
        'amount' => 'float',
        'date'   => 'date',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
