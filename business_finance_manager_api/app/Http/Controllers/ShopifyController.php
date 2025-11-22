<?php
// app/Http/Controllers/ShopifyController.php

namespace App\Http\Controllers;

use App\Models\ShopifySettings;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\PosOrder;
use App\Models\PosOrderItem;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class ShopifyController extends Controller
{
    public function getSettings()
    {
        $settings = ShopifySettings::where('user_id', auth()->id())->first();

        return response()->json($settings ?? [
            'is_connected' => false,
            'store_domain' => null,
            'last_sync_at' => null,
        ]);
    }

    public function saveSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'store_domain' => 'required|string',
            'api_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Test connection
        try {
            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $request->api_token,
            ])->get("https://{$request->store_domain}/admin/api/2024-01/shop.json");

            if (!$response->successful()) {
                return response()->json(['error' => 'Invalid Shopify credentials'], 400);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to connect to Shopify: ' . $e->getMessage()], 500);
        }

        $settings = ShopifySettings::updateOrCreate(
            ['user_id' => auth()->id()],
            [
                'store_domain' => $request->store_domain,
                'api_token' => $request->api_token,
                'is_connected' => true,
            ]
        );

        return response()->json([
            'message' => 'Shopify settings saved successfully',
            'settings' => $settings,
        ]);
    }

    public function syncProducts(Request $request)
    {
        $settings = ShopifySettings::where('user_id', auth()->id())
            ->where('is_connected', true)
            ->firstOrFail();

        try {
            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $settings->api_token,
            ])->get("https://{$settings->store_domain}/admin/api/2024-01/products.json");

            if (!$response->successful()) {
                return response()->json(['error' => 'Failed to fetch products from Shopify'], 500);
            }

            $shopifyProducts = $response->json()['products'] ?? [];
            $synced = 0;

            DB::beginTransaction();
            try {
                foreach ($shopifyProducts as $shopifyProduct) {
                    foreach ($shopifyProduct['variants'] as $variant) {
                        // Check if product already exists
                        $product = Product::where('user_id', auth()->id())
                            ->where('shopify_variant_id', $variant['id'])
                            ->first();

                        $productData = [
                            'user_id' => auth()->id(),
                            'name' => $shopifyProduct['title'] . ($variant['title'] !== 'Default Title' ? ' - ' . $variant['title'] : ''),
                            'sku' => $variant['sku'] ?? 'SHOPIFY-' . $variant['id'],
                            'selling_price' => $variant['price'] ?? 0,
                            'cost_price' => $variant['compare_at_price'] ?? $variant['price'] ?? 0,
                            'current_stock' => $variant['inventory_quantity'] ?? 0,
                            'is_active' => true,
                            'shopify_product_id' => $shopifyProduct['id'],
                            'shopify_variant_id' => $variant['id'],
                        ];

                        if ($product) {
                            // Update existing
                            $oldStock = $product->current_stock;
                            $product->update($productData);

                            // Create stock movement if stock changed
                            if ($oldStock !== $productData['current_stock']) {
                                StockMovement::create([
                                    'product_id' => $product->id,
                                    'type' => 'shopify_sync',
                                    'quantity' => $productData['current_stock'] - $oldStock,
                                    'date' => now(),
                                    'note' => 'Synced from Shopify',
                                ]);
                            }
                        } else {
                            // Create new
                            Product::create($productData);
                        }

                        $synced++;
                    }
                }

                $settings->update(['last_sync_at' => now()]);

                DB::commit();

                return response()->json([
                    'message' => "Successfully synced {$synced} products",
                    'synced_count' => $synced,
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            return response()->json(['error' => 'Sync failed: ' . $e->getMessage()], 500);
        }
    }

    public function syncOrders(Request $request)
    {
        $settings = ShopifySettings::where('user_id', auth()->id())
            ->where('is_connected', true)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'account_id' => 'required|exists:accounts,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $account = Account::where('id', $request->account_id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        try {
            // Fetch paid orders from Shopify
            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $settings->api_token,
            ])->get("https://{$settings->store_domain}/admin/api/2024-01/orders.json", [
                'financial_status' => 'paid',
                'limit' => 50,
            ]);

            if (!$response->successful()) {
                return response()->json(['error' => 'Failed to fetch orders from Shopify'], 500);
            }

            $shopifyOrders = $response->json()['orders'] ?? [];
            $synced = 0;

            DB::beginTransaction();
            try {
                foreach ($shopifyOrders as $shopifyOrder) {
                    // Check if already imported
                    $exists = PosOrder::where('user_id', auth()->id())
                        ->where('shopify_order_id', $shopifyOrder['id'])
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    // Create POS order
                    $order = PosOrder::create([
                        'user_id' => auth()->id(),
                        'account_id' => $account->id,
                        'date' => $shopifyOrder['created_at'],
                        'total_amount' => $shopifyOrder['total_price'],
                        'payment_method' => 'Card',
                        'channel' => 'shopify',
                        'note' => "Shopify Order #{$shopifyOrder['order_number']}",
                        'shopify_order_id' => $shopifyOrder['id'],
                    ]);

                    // Create order items
                    foreach ($shopifyOrder['line_items'] as $lineItem) {
                        $product = Product::where('user_id', auth()->id())
                            ->where('shopify_variant_id', $lineItem['variant_id'])
                            ->first();

                        if ($product) {
                            PosOrderItem::create([
                                'pos_order_id' => $order->id,
                                'product_id' => $product->id,
                                'quantity' => $lineItem['quantity'],
                                'unit_price' => $lineItem['price'],
                                'total_line_amount' => $lineItem['quantity'] * $lineItem['price'],
                            ]);

                            // Create stock movement
                            StockMovement::create([
                                'product_id' => $product->id,
                                'type' => 'shopify_order',
                                'quantity' => -$lineItem['quantity'],
                                'date' => $shopifyOrder['created_at'],
                                'note' => "Shopify Order #{$shopifyOrder['order_number']}",
                            ]);
                        }
                    }

                    // Increase account balance
                    $account->increment('balance', $order->total_amount);

                    $synced++;
                }

                $settings->update(['last_sync_at' => now()]);

                DB::commit();

                return response()->json([
                    'message' => "Successfully synced {$synced} orders",
                    'synced_count' => $synced,
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            return response()->json(['error' => 'Order sync failed: ' . $e->getMessage()], 500);
        }
    }

    public function disconnect()
    {
        $settings = ShopifySettings::where('user_id', auth()->id())->first();

        if ($settings) {
            $settings->delete();
        }

        return response()->json(['message' => 'Shopify disconnected successfully']);
    }
}
