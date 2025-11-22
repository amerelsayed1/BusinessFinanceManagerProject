<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
/**
 * App\Models\Account
 *
 * @property int $id
 * @property string $name
 * @property float $balance
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|Account find($id)
 * @method static Builder|Account orderBy($id)
 * @method static Builder|Account findOrFail($id)
 * @method static Builder|Account where($column, $operator = null, $value = null)
 * @method static Builder|Account create(array $attributes = [])
 * @mixin Builder
 */

class MonthlySale extends Model
{

    use HasFactory;

    protected $fillable =[
        'year',
        'month',
        'total_sales'
    ];


    protected $casts =[
        'year' => 'integer',
        'month' => 'integer',
        'total_sales' => 'float'
    ];
}
