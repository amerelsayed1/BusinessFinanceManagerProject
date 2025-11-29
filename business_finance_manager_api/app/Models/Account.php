<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $fillable = ['user_id', 'name', 'type', 'opening_balance', 'current_balance'];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
    ];

    protected $appends = ['balance'];

    public function getBalanceAttribute()
    {
        return $this->current_balance;
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function bills()
    {
        return $this->hasMany(Bill::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function incomes()
    {
        return $this->hasMany(Income::class);
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }
}
