<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MonthlySaleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'month' => optional($this->month)->toDateString(),
            'total_sales' => (float) $this->total_sales,
            'product_cost' => (float) $this->product_cost,
            'ads_expenses' => (float) $this->ads_expenses,
            'logistics_cost' => (float) $this->logistics_cost,
            'platform_fees' => (float) $this->platform_fees,
            'other_expenses' => (float) $this->other_expenses,
            'notes' => $this->notes,
            'total_cost' => $this->total_cost,
            'profit' => $this->profit,
            'roi_percent' => $this->roi_percent,
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
        ];
    }
}
