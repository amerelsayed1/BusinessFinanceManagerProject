<?php
// app/Http/Controllers/PosOrderController.php

namespace App\Http\Controllers;

use App\Models\PosOrder;
use App\Models\PosOrderItem;
use App\Models\Product;
use App\Models\Account;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PosOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = PosOrder::with(['account', 'items.product'])
            ->where('user_id', auth()->id());

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        if ($request->has('channel')) {
            $query->where('channel', $request->channel);
        }

        $orders = $query->orderBy('date', 'desc')->get();

        return response()->json($orders);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'account_id' => 'required|exists:accounts,id',
            'date' => 'required|date',
            'payment_method' => 'required|in:Cash,Card,Wallet',
            'note' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Verify account belongs to user
        $account = Account::where('id', $request->account_id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        DB::beginTransaction();
        try {
            $totalAmount = 0;

            // Calculate total and verify stock
            foreach ($request->items as $item) {
                $product = Product::where('id', $item['product_id'])
                    ->where('user_id', auth()->id())
                    ->firstOrFail();

                if ($product->current_stock < $item['quantity']) {
                    throw new \Exception("Insufficient stock for product: {$product->name}");
                }

                $totalAmount += $item['quantity'] * $item['unit_price'];
            }

            // Create POS Order
            $order = PosOrder::create([
                'user_id' => auth()->id(),
                'account_id' => $request->account_id,
                'date' => $request->date,
                'total_amount' => $totalAmount,
                'payment_method' => $request->payment_method,
                'channel' => 'pos',
                'note' => $request->note,
            ]);

            // Create order items and stock movements
            foreach ($request->items as $item) {
                $product = Product::findOrFail($item['product_id']);
                $totalLineAmount = $item['quantity'] * $item['unit_price'];

                // Create order item
                PosOrderItem::create([
                    'pos_order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_line_amount' => $totalLineAmount,
                ]);

                // Create stock movement (negative quantity for sale)
                StockMovement::create([
                    'product_id' => $item['product_id'],
                    'type' => 'pos_sale',
                    'quantity' => -$item['quantity'],
                    'date' => $request->date,
                    'note' => "POS Order #{$order->id}",
                ]);
            }

            // Increase account balance
            $account->increment('balance', $totalAmount);

            DB::commit();

            $order->load(['items.product', 'account']);

            return response()->json([
                'message' => 'POS order created successfully',
                'order' => $order,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to create order: ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $order = PosOrder::with(['items.product', 'account'])
            ->where('user_id', auth()->id())
            ->findOrFail($id);

        return response()->json($order);
    }

    public function destroy($id)
    {
        $order = PosOrder::where('user_id', auth()->id())->findOrFail($id);

        DB::beginTransaction();
        try {
            // Reverse account balance
            $order->account->decrement('balance', $order->total_amount);

            // Reverse stock movements
            foreach ($order->items as $item) {
                StockMovement::create([
                    'product_id' => $item->product_id,
                    'type' => 'adjustment',
                    'quantity' => $item->quantity, // positive to restore stock
                    'date' => now(),
                    'note' => "Reversal of POS Order #{$order->id}",
                ]);
            }

            $order->delete();

            DB::commit();

            return response()->json(['message' => 'POS order deleted successfully']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to delete order: ' . $e->getMessage()], 500);
        }
    }
}
