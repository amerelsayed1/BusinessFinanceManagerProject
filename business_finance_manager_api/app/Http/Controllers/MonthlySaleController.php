<?php

namespace App\Http\Controllers;

use App\Models\MonthlySale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


class MonthlySaleController extends Controller
{
    public function index(): JsonResponse
    {
        $sales = MonthlySale::orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get()
            ->map(function (MonthlySale $sale) {
                return [
                    'id'         => $sale->id,
                    'date'       => sprintf('%02d/%d', $sale->month, $sale->year), // "11/2025"
                    'month'      => $sale->month,
                    'year'       => $sale->year,
                    'totalSales' => (float) $sale->total_sales,
                ];
            });

        return response()->json($sales);
    }

    public function show(int $id): JsonResponse
    {
        $sale = MonthlySale::findOrFail($id);

        return response()->json([
            'id'         => $sale->id,
            'date'       => sprintf('%02d/%d', $sale->month, $sale->year),
            'month'      => $sale->month,
            'year'       => $sale->year,
            'totalSales' => (float) $sale->total_sales,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'month'      => 'required|integer|min:1|max:12',
            'year'       => 'required|integer|min:2000|max:2100',
            'totalSales' => 'required|numeric|min:0',
        ]);

        // Prevent duplicate month/year
        $exists = MonthlySale::where('year', $data['year'])
            ->where('month', $data['month'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Monthly sales for this month/year already exists.',
            ], 422);
        }

        $sale = MonthlySale::create([
            'year'        => $data['year'],
            'month'       => $data['month'],
            'total_sales' => $data['totalSales'],
        ]);

        return response()->json([
            'id'         => $sale->id,
            'date'       => sprintf('%02d/%d', $sale->month, $sale->year),
            'month'      => $sale->month,
            'year'       => $sale->year,
            'totalSales' => (float) $sale->total_sales,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'month'      => 'sometimes|required|integer|min:1|max:12',
            'year'       => 'sometimes|required|integer|min:2000|max:2100',
            'totalSales' => 'sometimes|required|numeric|min:0',
        ]);

        $sale = MonthlySale::findOrFail($id);

        if (isset($data['month'])) {
            $sale->month = $data['month'];
        }
        if (isset($data['year'])) {
            $sale->year = $data['year'];
        }
        if (isset($data['totalSales'])) {
            $sale->total_sales = $data['totalSales'];
        }

        // Check duplicates
        $exists = MonthlySale::where('year', $sale->year)
            ->where('month', $sale->month)
            ->where('id', '!=', $sale->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Monthly sales for this month/year already exists.',
            ], 422);
        }

        $sale->save();

        return response()->json([
            'id'         => $sale->id,
            'date'       => sprintf('%02d/%d', $sale->month, $sale->year),
            'month'      => $sale->month,
            'year'       => $sale->year,
            'totalSales' => (float) $sale->total_sales,
        ], 200);
    }

    public function destroy(int $id): JsonResponse
    {
        $sale = MonthlySale::findOrFail($id);
        $sale->delete();

        return response()->json([
            'success' => true,
            'message' => 'Monthly sales record deleted successfully',
        ]);
    }
}
