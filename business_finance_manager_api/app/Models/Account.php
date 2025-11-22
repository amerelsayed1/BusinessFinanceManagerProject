<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Account
 *
 * @property int $id
 * @property string $name
 * @property float $balance
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static Builder|Account find($id)
 * @method static Builder|Account orderBy($id)
 * @method static Builder|Account findOrFail($id)
 * @method static Builder|Account where($column, $operator = null, $value = null)
 * @method static Builder|Account create(array $attributes = [])
 * @mixin Builder
 */
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
