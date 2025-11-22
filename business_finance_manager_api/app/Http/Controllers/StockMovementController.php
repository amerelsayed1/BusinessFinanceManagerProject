<?php

namespace App\Http\Controllers;

use App\Models\StockMovement;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StockMovementController extends Controller
{
    public function index(Request $request)
    {
        $query = StockMovement::with('product');

        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        $movements = $query->orderBy('date', 'desc')->get();

        return response()->json($movements);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'type' => 'required|in:purchase,adjustment,correction,pos_sale,shopify_order,shopify_sync',
            'quantity' => 'required|integer|not_in:0',
            'date' => 'required|date',
            'note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Verify product belongs to user
        $product = Product::where('id', $request->product_id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $movement = StockMovement::create([
            'product_id' => $request->product_id,
            'type' => $request->type,
            'quantity' => $request->quantity,
            'date' => $request->date,
            'note' => $request->note,
        ]);

        return response()->json([
            'message' => 'Stock movement created successfully',
            'movement' => $movement->load('product'),
        ], 201);
    }

    public function adjustStock(Request $request, $productId)
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|not_in:0',
            'note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $product = Product::where('id', $productId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $movement = StockMovement::create([
            'product_id' => $productId,
            'type' => 'adjustment',
            'quantity' => $request->quantity,
            'date' => now(),
            'note' => $request->note ?? 'Manual stock adjustment',
        ]);

        return response()->json([
            'message' => 'Stock adjusted successfully',
            'movement' => $movement,
            'product' => $product->fresh(),
        ]);
    }
}
