<?php
// app/Http/Controllers/MonthlySalesController.php

namespace App\Http\Controllers;

use App\Http\Resources\MonthlySaleResource;
use App\Models\MonthlySale;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;

class MonthlySalesController extends Controller
{
    public function index(Request $request): ResourceCollection
    {
        $user = $request->user();
        $query = MonthlySale::where('user_id', $user->id)
            ->orderBy('month', 'desc');

        if ($request->filled('year')) {
            $query->whereYear('month', $request->integer('year'));
        }

        return MonthlySaleResource::collection($query->get());
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request, true);
        $normalizedMonth = $this->normalizeMonth($data['month']);
        $attributes = $this->mapAttributes($data, $request->user()->id, $normalizedMonth);

        $monthlySale = MonthlySale::updateOrCreate(
            ['user_id' => $request->user()->id, 'month' => $normalizedMonth->toDateString()],
            $attributes
        );

        $status = $monthlySale->wasRecentlyCreated ? 201 : 200;

        return (new MonthlySaleResource($monthlySale->fresh()))
            ->response()
            ->setStatusCode($status);
    }

    public function show(Request $request, MonthlySale $monthlySale): MonthlySaleResource
    {
        $this->authorizeUser($request, $monthlySale);

        return new MonthlySaleResource($monthlySale);
    }

    public function update(Request $request, MonthlySale $monthlySale)
    {
        $this->authorizeUser($request, $monthlySale);

        $data = $this->validateData($request, false);

        if (isset($data['month'])) {
            $data['month'] = $this->normalizeMonth($data['month'])->toDateString();
        }

        $monthlySale->update($this->mapAttributes($data, $request->user()->id, $data['month'] ?? $monthlySale->month));

        return new MonthlySaleResource($monthlySale->fresh());
    }

    public function destroy(Request $request, MonthlySale $monthlySale): Response
    {
        $this->authorizeUser($request, $monthlySale);

        $monthlySale->delete();

        return response()->noContent();
    }

    private function authorizeUser(Request $request, MonthlySale $monthlySale): void
    {
        if ($monthlySale->user_id !== $request->user()->id) {
            abort(404);
        }
    }

    private function normalizeMonth(string $month): Carbon
    {
        if (preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month .= '-01';
        }

        return Carbon::parse($month)->startOfMonth();
    }

    private function validateData(Request $request, bool $isStore): array
    {
        $rules = [
            'month' => [$isStore ? 'required' : 'sometimes', 'date'],
            'total_sales' => [$isStore ? 'required' : 'sometimes', 'numeric', 'min:0'],
            'product_cost' => [$isStore ? 'required' : 'sometimes', 'numeric', 'min:0'],
            'ads_expenses' => [$isStore ? 'required' : 'sometimes', 'numeric', 'min:0'],
            'logistics_cost' => ['nullable', 'numeric', 'min:0'],
            'platform_fees' => ['nullable', 'numeric', 'min:0'],
            'other_expenses' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];

        return $request->validate($rules);
    }

    private function mapAttributes(array $data, int $userId, $month): array
    {
        return [
            'user_id' => $userId,
            'month' => is_string($month) ? $month : Carbon::parse($month)->toDateString(),
            'total_sales' => $data['total_sales'] ?? 0,
            'product_cost' => $data['product_cost'] ?? 0,
            'ads_expenses' => $data['ads_expenses'] ?? 0,
            'logistics_cost' => $data['logistics_cost'] ?? 0,
            'platform_fees' => $data['platform_fees'] ?? 0,
            'other_expenses' => $data['other_expenses'] ?? 0,
            'notes' => $data['notes'] ?? null,
        ];
    }
}
