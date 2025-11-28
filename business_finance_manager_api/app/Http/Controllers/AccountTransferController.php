<?php

namespace App\Http\Controllers;

use App\Models\AccountTransfer;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AccountTransferController extends Controller
{
    public function index()
    {
        $transfers = AccountTransfer::with(['fromAccount', 'toAccount'])
            ->where('user_id', auth()->id())
            ->orderBy('date', 'desc')
            ->get();

        return response()->json([
            'data' => $transfers,
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'from_account_id' => 'required|exists:accounts,id',
            'to_account_id' => 'required|exists:accounts,id|different:from_account_id',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Verify accounts belong to user
        $fromAccount = Account::where('id', $request->from_account_id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $toAccount = Account::where('id', $request->to_account_id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        // Check sufficient balance
        if ($fromAccount->current_balance < $request->amount) {
            return response()->json(['error' => 'Insufficient balance in source account'], 400);
        }

        DB::beginTransaction();
        try {
            // Create transfer record
            $transfer = AccountTransfer::create([
                'user_id' => auth()->id(),
                'from_account_id' => $request->from_account_id,
                'to_account_id' => $request->to_account_id,
                'amount' => $request->amount,
                'date' => $request->date,
                'note' => $request->note,
            ]);

            // Update account balances
            $fromAccount->decrement('current_balance', $request->amount);
            $toAccount->increment('current_balance', $request->amount);

            DB::commit();

            $transfer->load(['fromAccount', 'toAccount']);

            return response()->json([
                'message' => 'Transfer created successfully',
                'transfer' => $transfer,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Transfer failed: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'from_account_id' => 'required|exists:accounts,id',
            'to_account_id' => 'required|exists:accounts,id|different:from_account_id',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $transfer = AccountTransfer::where('user_id', auth()->id())
            ->with(['fromAccount', 'toAccount'])
            ->findOrFail($id);

        DB::beginTransaction();
        try {
            // Reverse previous balances
            $transfer->fromAccount->increment('current_balance', $transfer->amount);
            $transfer->toAccount->decrement('current_balance', $transfer->amount);

            $fromAccount = Account::where('id', $request->from_account_id)
                ->where('user_id', auth()->id())
                ->firstOrFail();
            $toAccount = Account::where('id', $request->to_account_id)
                ->where('user_id', auth()->id())
                ->firstOrFail();

            if ($fromAccount->current_balance < $request->amount) {
                DB::rollBack();
                return response()->json(['error' => 'Insufficient balance in source account'], 400);
            }

            $fromAccount->decrement('current_balance', $request->amount);
            $toAccount->increment('current_balance', $request->amount);

            $transfer->update([
                'from_account_id' => $request->from_account_id,
                'to_account_id' => $request->to_account_id,
                'amount' => $request->amount,
                'date' => $request->date,
                'note' => $request->note,
            ]);

            $transfer->load(['fromAccount', 'toAccount']);

            DB::commit();

            return response()->json([
                'message' => 'Transfer updated successfully',
                'transfer' => $transfer,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to update transfer: ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $transfer = AccountTransfer::with(['fromAccount', 'toAccount'])
            ->where('user_id', auth()->id())
            ->findOrFail($id);

        return response()->json([
            'transfer' => $transfer,
        ], 200);
    }

    public function destroy($id)
    {
        $transfer = AccountTransfer::where('user_id', auth()->id())->findOrFail($id);

        DB::beginTransaction();
        try {
            // Reverse the transfer
            $fromAccount = $transfer->fromAccount;
            $toAccount = $transfer->toAccount;

            $fromAccount->increment('current_balance', $transfer->amount);
            $toAccount->decrement('current_balance', $transfer->amount);

            $transfer->delete();

            DB::commit();

            return response()->json(['message' => 'Transfer deleted successfully'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to delete transfer: ' . $e->getMessage()], 500);
        }
    }
}
