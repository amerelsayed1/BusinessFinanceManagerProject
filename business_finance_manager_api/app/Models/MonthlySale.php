<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class MonthlySale extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'month',
        'total_sales',
        'product_cost',
        'ads_expenses',
        'logistics_cost',
        'platform_fees',
        'other_expenses',
        'notes',
    ];

    protected $casts = [
        'month' => 'date',
        'total_sales' => 'decimal:2',
        'product_cost' => 'decimal:2',
        'ads_expenses' => 'decimal:2',
        'logistics_cost' => 'decimal:2',
        'platform_fees' => 'decimal:2',
        'other_expenses' => 'decimal:2',
    ];

    protected $appends = [
        'total_cost',
        'profit',
        'roi_percent',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getTotalCostAttribute(): float
    {
        return (float) ($this->product_cost + $this->ads_expenses + $this->logistics_cost + $this->platform_fees + $this->other_expenses);
    }

    public function getProfitAttribute(): float
    {
        return (float) ($this->total_sales - $this->total_cost);
    }

    public function getRoiPercentAttribute(): float
    {
        $totalCost = $this->total_cost;

        if ($totalCost <= 0) {
            return 0.0;
        }

        return (float) (($this->profit / $totalCost) * 100);
    }

    public function setMonthAttribute($value): void
    {
        $this->attributes['month'] = $this->normalizeMonth($value)?->toDateString();
    }

    protected function normalizeMonth($value): ?Carbon
    {
        if (empty($value)) {
            return null;
        }

        if (is_string($value) && preg_match('/^\d{4}-\d{2}$/', $value)) {
            $value .= '-01';
        }

        return Carbon::parse($value)->startOfMonth();
    }
}
