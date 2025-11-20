<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AccountController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Account::orderBy('id')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'balance' => 'nullable|numeric',
        ]);

        $account = Account::create([
            'name'    => $data['name'],
            'balance' => $data['balance'] ?? 0,
        ]);

        return response()->json($account, 201);
    }

    public function deposit(Request $request): JsonResponse
    {
        $data = $request->validate([
            'accountId' => 'required|integer|exists:accounts,id',
            'amount'    => 'required|numeric|min:0.01',
        ]);

        $account = Account::findOrFail($data['accountId']);
        $account->balance += $data['amount'];
        $account->save();

        return response()->json($account);
    }
}
