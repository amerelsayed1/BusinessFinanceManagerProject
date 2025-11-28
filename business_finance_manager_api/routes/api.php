<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    AccountController,
    AuthController,
    ProfileController,
    ExpenseCategoryController,
    AccountTransferController,
    ExpenseController,
    BillController,
    MonthlySalesController,
    ROIController,
    DashboardController,
    MonthlyReportController,
    ProductController,
    StockMovementController,
    PosOrderController,
    ShopifyController,
    ProductCategoryController,
    IncomeController,
    PurchaseController,
    ReportsController
};

// API Version 1
Route::prefix('v1')->group(function () {
    // Public routes
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    // Protected routes with rate limiting
    Route::middleware(['auth:api', 'throttle:60,1'])->group(function () {
        // Auth
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);

        // Profile
        Route::put('/profile', [ProfileController::class, 'update']);
        Route::post('/profile/logo', [ProfileController::class, 'uploadLogo']);

        // Expense Categories
        Route::apiResource('expense-categories', ExpenseCategoryController::class)
            ->except(['show']);

        // Account Transfers
        Route::prefix('accounts')->group(function () {
            Route::apiResource('transfers', AccountTransferController::class)
                ->except(['create', 'edit'])
                ->where(['transfer' => '[0-9]+']);
        });

        // Accounts
        Route::apiResource('accounts', AccountController::class)->whereNumber('account');
        Route::post('/accounts/deposit', [AccountController::class, 'deposit']);
        Route::post('/accounts/withdraw', [AccountController::class, 'withdraw']);
        Route::post('/accounts/transfer', [AccountController::class, 'transfer']);
        Route::get('/accounts/{id}/balance', [AccountController::class, 'balance']);

        // Expenses
        Route::apiResource('expenses', ExpenseController::class);

        // Bills
        Route::get('/bills/pending', [BillController::class, 'getPending']);
        Route::get('/bills/summary/status', [BillController::class, 'getTotalByStatus']);
        Route::put('/bills/{id}/status', [BillController::class, 'updateStatus']);
        Route::apiResource('bills', BillController::class);

        // Monthly Sales
        Route::apiResource('monthly-sales', MonthlySalesController::class);

        // ROI
        Route::get('/roi/current', [ROIController::class, 'getCurrentMonthROI']);
        Route::get('/roi', [ROIController::class, 'getROIByMonth']);

        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index']);
        Route::get('/dashboard/summary', [DashboardController::class, 'summary']);

        // Monthly Report
        Route::get('/monthly-report', [MonthlyReportController::class, 'show']);

        // Products (Inventory)
        Route::apiResource('products', ProductController::class);
        Route::post('/products/{id}/adjust-stock', [StockMovementController::class, 'adjustStock']);

        // Stock Movements
        Route::apiResource('stock-movements', StockMovementController::class)
            ->only(['index', 'store']);

        // POS Orders
        Route::apiResource('pos-orders', PosOrderController::class)
            ->except(['update']);

        // Incomes
        Route::apiResource('incomes', IncomeController::class);

        // Purchases
        Route::apiResource('purchases', PurchaseController::class)
            ->except(['create', 'edit']);

        // Reports
        Route::get('/reports/accountant-export', [ReportsController::class, 'accountantExport']);

        // Shopify Integration
        Route::prefix('shopify')->group(function () {
            Route::get('/settings', [ShopifyController::class, 'getSettings']);
            Route::post('/settings', [ShopifyController::class, 'saveSettings']);
            Route::post('/sync-products', [ShopifyController::class, 'syncProducts']);
            Route::post('/sync-orders', [ShopifyController::class, 'syncOrders']);
            Route::delete('/disconnect', [ShopifyController::class, 'disconnect']);
        });

        // Product Categories
        Route::apiResource('product-categories', ProductCategoryController::class)
            ->except(['show']);
    });
});

// Fallback for unversioned requests (temporary backward compatibility)
Route::middleware(['auth:api', 'throttle:60,1'])->group(function () {
    // Redirect old routes to v1
    Route::any('/{any}', function () {
        return response()->json([
            'message' => 'Please use versioned API endpoints. Example: /api/v1/accounts',
            'documentation' => url('/api/documentation')
        ], 426);
    })->where('any', '.*');
});
