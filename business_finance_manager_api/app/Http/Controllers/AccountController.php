<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountController extends Controller
{
    public function index(): JsonResponse
    {
        $accounts = Account::where('user_id', auth()->id())
            ->orderBy('id')
            ->get();

        return response()->json($accounts);
    }

    public function show($id): JsonResponse
    {
        $account = Account::where('user_id', auth()->id())->findOrFail($id);
        return response()->json($account);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'balance' => 'nullable|numeric|min:0',
        ]);

        $account = Account::create([
            'user_id' => auth()->id(),
            'name'    => $data['name'],
            'balance' => $data['balance'] ?? 0,
        ]);

        return response()->json($account, 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $account = Account::where('user_id', auth()->id())->findOrFail($id);

        $data = $request->validate([
            'name'    => 'sometimes|required|string|max:255',
            'balance' => 'sometimes|nullable|numeric|min:0',
        ]);

        $account->update($data);

        return response()->json($account);
    }

    public function destroy($id): JsonResponse
    {
        $account = Account::where('user_id', auth()->id())->findOrFail($id);

        // Check if account has related expenses or bills
        if ($account->expenses()->exists() || $account->bills()->exists()) {
            return response()->json([
                'message' => 'Cannot delete account with existing expenses or bills'
            ], 422);
        }

        $account->delete();

        return response()->json([
            'message' => 'Account deleted successfully'
        ]);
    }

    public function deposit(Request $request): JsonResponse
    {
        $data = $request->validate([
            'account_id' => 'required|integer|exists:accounts,id',
            'amount'     => 'required|numeric|min:0.01',
        ]);

        $account = Account::where('user_id', auth()->id())->findOrFail($data['account_id']);

        DB::transaction(function () use ($account, $data) {
            $account->lockForUpdate();
            $account->balance = ($account->balance ?? 0) + $data['amount'];
            $account->save();
        });

        return response()->json($account, 200);
    }

    public function withdraw(Request $request): JsonResponse
    {
        $data = $request->validate([
            'account_id' => 'required|integer|exists:accounts,id',
            'amount'     => 'required|numeric|min:0.01',
        ]);

        $account = Account::where('user_id', auth()->id())->findOrFail($data['account_id']);

        // Check if sufficient balance
        if ($account->balance < $data['amount']) {
            return response()->json([
                'message' => 'Insufficient balance'
            ], 422);
        }

        DB::transaction(function () use ($account, $data) {
            $account->lockForUpdate();
            $account->balance -= $data['amount'];
            $account->save();
        });

        return response()->json($account, 200);
    }

    public function transfer(Request $request): JsonResponse
    {
        $data = $request->validate([
            'from_account_id' => 'required|integer|exists:accounts,id',
            'to_account_id'   => 'required|integer|exists:accounts,id|different:from_account_id',
            'amount'          => 'required|numeric|min:0.01',
        ]);

        $fromAccount = Account::where('user_id', auth()->id())->findOrFail($data['from_account_id']);
        $toAccount = Account::where('user_id', auth()->id())->findOrFail($data['to_account_id']);

        // Check if sufficient balance
        if ($fromAccount->balance < $data['amount']) {
            return response()->json([
                'message' => 'Insufficient balance in source account'
            ], 422);
        }

        DB::transaction(function () use ($fromAccount, $toAccount, $data) {
            // Lock both accounts to prevent race conditions
            $fromAccount->lockForUpdate();
            $toAccount->lockForUpdate();

            $fromAccount->balance -= $data['amount'];
            $toAccount->balance += $data['amount'];

            $fromAccount->save();
            $toAccount->save();
        });

        return response()->json([
            'message' => 'Transfer completed successfully',
            'from_account' => $fromAccount,
            'to_account' => $toAccount,
        ], 200);
    }

    public function balance($id): JsonResponse
    {
        $account = Account::where('user_id', auth()->id())->findOrFail($id);

        return response()->json([
            'account_id' => $account->id,
            'name' => $account->name,
            'balance' => $account->balance,
        ]);
    }
}
