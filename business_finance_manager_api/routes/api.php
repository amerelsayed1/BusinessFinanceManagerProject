<?php
// routes/api.php

use App\Http\Controllers\AccountController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\AccountTransferController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\BillController;
use App\Http\Controllers\MonthlySalesController;
use App\Http\Controllers\ROIController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MonthlyReportController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\PosOrderController;
use App\Http\Controllers\ShopifyController;
use App\Http\Controllers\ProductCategoryController;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:api')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Profile
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::post('/profile/logo', [ProfileController::class, 'uploadLogo']);

    // Expense Categories
    Route::get('/expense-categories', [ExpenseCategoryController::class, 'index']);
    Route::post('/expense-categories', [ExpenseCategoryController::class, 'store']);
    Route::put('/expense-categories/{id}', [ExpenseCategoryController::class, 'update']);
    Route::delete('/expense-categories/{id}', [ExpenseCategoryController::class, 'destroy']);

    // Account Transfers
    Route::get('/transfers', [AccountTransferController::class, 'index']);
    Route::post('/transfers', [AccountTransferController::class, 'store']);
    Route::get('/transfers/{id}', [AccountTransferController::class, 'show']);
    Route::delete('/transfers/{id}', [AccountTransferController::class, 'destroy']);

    // Accounts
    Route::get('/accounts', [AccountController::class, 'index']);
    Route::get('/accounts/{id}', [AccountController::class, 'show']);
    Route::post('/accounts', [AccountController::class, 'store']);
    Route::put('/accounts/{id}', [AccountController::class, 'update']);
    Route::delete('/accounts/{id}', [AccountController::class, 'destroy']);

    Route::post('/accounts/deposit', [AccountController::class, 'deposit']);
    Route::post('/accounts/withdraw', [AccountController::class, 'withdraw']);
    Route::post('/accounts/transfer', [AccountController::class, 'transfer']);
    Route::get('/accounts/{id}/balance', [AccountController::class, 'balance']);

    // Expenses (updated)
    Route::get('/expenses', [ExpenseController::class, 'index']);
    Route::post('/expenses', [ExpenseController::class, 'store']);
    Route::get('/expenses/{id}', [ExpenseController::class, 'show']);
    Route::put('/expenses/{id}', [ExpenseController::class, 'update']);
    Route::delete('/expenses/{id}', [ExpenseController::class, 'destroy']);

    // Bills / Invoices
    Route::get('/bills', [BillController::class, 'index']);
    Route::post('/bills', [BillController::class, 'store']);
    Route::get('/bills/pending', [BillController::class, 'getPending']);
    Route::get('/bills/summary/status', [BillController::class, 'getTotalByStatus']);
    Route::get('/bills/{id}', [BillController::class, 'show']);
    Route::put('/bills/{id}', [BillController::class, 'update']);
    Route::delete('/bills/{id}', [BillController::class, 'destroy']);
    Route::put('/bills/{id}/status', [BillController::class, 'updateStatus']);

    // Monthly Sales
    Route::get('/monthly-sales', [MonthlySalesController::class, 'index']);
    Route::post('/monthly-sales', [MonthlySalesController::class, 'store']);
    Route::put('/monthly-sales/{id}', [MonthlySalesController::class, 'update']);
    Route::delete('/monthly-sales/{id}', [MonthlySalesController::class, 'destroy']);

    // ROI
    Route::get('/roi/current', [ROIController::class, 'getCurrentMonthROI']);
    Route::get('/roi', [ROIController::class, 'getROIByMonth']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Monthly Report
    Route::get('/monthly-report', [MonthlyReportController::class, 'show']);

    // Products (Inventory)
    Route::get('/products', [ProductController::class, 'index']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::get('/products/{id}', [ProductController::class, 'show']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);

    // Stock Movements
    Route::get('/stock-movements', [StockMovementController::class, 'index']);
    Route::post('/stock-movements', [StockMovementController::class, 'store']);
    Route::post('/products/{id}/adjust-stock', [StockMovementController::class, 'adjustStock']);

    // POS Orders
    Route::get('/pos-orders', [PosOrderController::class, 'index']);
    Route::post('/pos-orders', [PosOrderController::class, 'store']);
    Route::get('/pos-orders/{id}', [PosOrderController::class, 'show']);
    Route::delete('/pos-orders/{id}', [PosOrderController::class, 'destroy']);

    // Shopify Integration
    Route::get('/shopify/settings', [ShopifyController::class, 'getSettings']);
    Route::post('/shopify/settings', [ShopifyController::class, 'saveSettings']);
    Route::post('/shopify/sync-products', [ShopifyController::class, 'syncProducts']);
    Route::post('/shopify/sync-orders', [ShopifyController::class, 'syncOrders']);
    Route::delete('/shopify/disconnect', [ShopifyController::class, 'disconnect']);

    // Product Categories
    Route::get('/product-categories', [ProductCategoryController::class, 'index']);
    Route::post('/product-categories', [ProductCategoryController::class, 'store']);
    Route::put('/product-categories/{id}', [ProductCategoryController::class, 'update']);
    Route::delete('/product-categories/{id}', [ProductCategoryController::class, 'destroy']);
});
