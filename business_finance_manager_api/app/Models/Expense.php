<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $fillable = [
        'user_id',
        'account_id',
        'category_id',
        'amount',
        'date',
        'description',
    ];

    protected $appends = ['note'];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }

    public function stockMovement()
    {
        return $this->hasOne(StockMovement::class);
    }

    public function getNoteAttribute()
    {
        return $this->description;
    }
}
