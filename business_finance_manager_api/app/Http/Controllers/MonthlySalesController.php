<?php
// app/Http/Controllers/MonthlySalesController.php

namespace App\Http\Controllers;

use App\Models\MonthlySales;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MonthlySalesController extends Controller
{
    public function index()
    {
        $sales = MonthlySales::where('user_id', auth()->id())
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get()
            ->map(function ($sale) {
                return [
                    'id' => $sale->id,
                    'date' => $sale->date,
                    'month' => $sale->month,
                    'year' => $sale->year,
                    'total_sales' => $sale->total_sales,
                ];
            });

        return response()->json($sales);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000|max:2100',
            'total_sales' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Check if record already exists
        $existing = MonthlySales::where('user_id', auth()->id())
            ->where('month', $request->month)
            ->where('year', $request->year)
            ->first();

        if ($existing) {
            return response()->json(['error' => 'Sales record for this month already exists'], 409);
        }

        $sales = MonthlySales::create([
            'user_id' => auth()->id(),
            'month' => $request->month,
            'year' => $request->year,
            'total_sales' => $request->total_sales,
        ]);

        return response()->json([
            'message' => 'Monthly sales created successfully',
            'sales' => [
                'id' => $sales->id,
                'date' => $sales->date,
                'month' => $sales->month,
                'year' => $sales->year,
                'total_sales' => $sales->total_sales,
            ],
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $sales = MonthlySales::where('user_id', auth()->id())->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'month' => 'sometimes|integer|min:1|max:12',
            'year' => 'sometimes|integer|min:2000|max:2100',
            'total_sales' => 'sometimes|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $sales->update($request->only(['month', 'year', 'total_sales']));

        return response()->json([
            'message' => 'Monthly sales updated successfully',
            'sales' => [
                'id' => $sales->id,
                'date' => $sales->date,
                'month' => $sales->month,
                'year' => $sales->year,
                'total_sales' => $sales->total_sales,
            ],
        ]);
    }

    public function destroy($id)
    {
        $sales = MonthlySales::where('user_id', auth()->id())->findOrFail($id);
        $sales->delete();

        return response()->json(['message' => 'Monthly sales deleted successfully']);
    }
}
