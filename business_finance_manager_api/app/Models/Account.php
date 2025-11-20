<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $fillable = ['name', 'balance'];

    protected $casts = [
        'balance' => 'float',
    ];

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function bills()
    {
        return $this->hasMany(Bill::class);
    }
}
