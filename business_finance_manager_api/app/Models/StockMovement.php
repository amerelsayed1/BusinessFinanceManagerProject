<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    protected $fillable = [
        'product_id',
        'type',
        'quantity',
        'date',
        'note',
        'expense_id',
    ];

    protected $casts = [
        'date' => 'date',
        'quantity' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }

    // REMOVED: The booted() method that caused race conditions
}
