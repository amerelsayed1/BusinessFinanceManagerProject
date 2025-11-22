<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonthlySales extends Model
{
    protected $fillable = ['user_id', 'month', 'year', 'total_sales'];

    protected $casts = [
        'month' => 'integer',
        'year' => 'integer',
        'total_sales' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getDateAttribute()
    {
        return sprintf('%02d/%d', $this->month, $this->year);
    }
}
