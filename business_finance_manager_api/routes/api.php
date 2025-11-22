<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\BillController;

// ============================================
// ACCOUNT ROUTES
// ============================================
Route::prefix('accounts')->group(function () {
    Route::get('/', [AccountController::class, 'index']);
    Route::get('/{id}', [AccountController::class, 'show']);
    Route::post('/', [AccountController::class, 'store']);
    Route::put('/{id}', [AccountController::class, 'update']);
    Route::delete('/{id}', [AccountController::class, 'destroy']);

    // Account Actions
    Route::post('/deposit', [AccountController::class, 'deposit']);
    Route::post('/withdraw', [AccountController::class, 'withdraw']);
    Route::post('/transfer', [AccountController::class, 'transfer']);
    Route::get('/{id}/balance', [AccountController::class, 'balance']);
});

// ============================================
// EXPENSE ROUTES
// ============================================
Route::prefix('expenses')->group(function () {
    Route::get('/', [ExpenseController::class, 'index']);
    Route::get('/{id}', [ExpenseController::class, 'show']);
    Route::post('/', [ExpenseController::class, 'store']);
    Route::put('/{id}', [ExpenseController::class, 'update']);
    Route::delete('/{id}', [ExpenseController::class, 'destroy']);
});

// ============================================
// BILL ROUTES
// ============================================
Route::prefix('bills')->group(function () {
    Route::get('/', [BillController::class, 'index']);
    Route::get('/{id}', [BillController::class, 'show']);
    Route::post('/', [BillController::class, 'store']);
    Route::put('/{id}', [BillController::class, 'update']);
    Route::delete('/{id}', [BillController::class, 'destroy']);
    Route::patch('/{id}/status', [BillController::class, 'updateStatus']);
});
