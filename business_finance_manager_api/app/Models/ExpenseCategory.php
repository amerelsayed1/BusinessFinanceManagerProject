<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpenseCategory extends Model
{
    protected $fillable = ['user_id', 'name', 'is_default'];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class, 'category_id');
    }

    // Prevent deletion of default categories
    protected static function booted()
    {
        static::deleting(function ($category) {
            if ($category->is_default) {
                throw new \Exception('Default categories cannot be deleted.');
            }
        });
    }
}
