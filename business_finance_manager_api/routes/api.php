<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\BillController;

Route::get('/accounts', [AccountController::class, 'index']);
Route::post('/accounts', [AccountController::class, 'store']);
Route::post('/accounts/deposit', [AccountController::class, 'deposit']);

Route::get('/expenses', [ExpenseController::class, 'index']);
Route::post('/expenses', [ExpenseController::class, 'store']);
Route::delete('/expenses/{id}', [ExpenseController::class, 'destroy']);

Route::get('/bills', [BillController::class, 'index']);
Route::post('/bills', [BillController::class, 'store']);
Route::delete('/bills/{id}', [BillController::class, 'destroy']);
Route::patch('/bills/{id}/status', [BillController::class, 'updateStatus']);
