<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Income;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IncomeController extends Controller
{
    public function index(Request $request)
    {
        $query = Income::with('account')
            ->where('user_id', auth()->id());

        $from = $request->input('from');
        $to = $request->input('to');
        if ($from && $to) {
            $query->whereBetween('date', [$from, $to]);
        }

        return response()->json($query->orderByDesc('date')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'note' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($data) {
            $account = Account::where('user_id', auth()->id())
                ->lockForUpdate()
                ->findOrFail($data['account_id']);

            $income = Income::create([
                'user_id' => auth()->id(),
                'account_id' => $account->id,
                'date' => $data['date'],
                'amount' => $data['amount'],
                'note' => $data['note'] ?? null,
            ]);

            $account->increment('current_balance', $data['amount']);

            return response()->json($income, 201);
        });
    }

    public function show($id)
    {
        $income = Income::with('account')
            ->where('user_id', auth()->id())
            ->findOrFail($id);

        return response()->json($income);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'account_id' => 'sometimes|exists:accounts,id',
            'date' => 'sometimes|date',
            'amount' => 'sometimes|numeric|min:0.01',
            'note' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($id, $data) {
            $income = Income::where('user_id', auth()->id())
                ->lockForUpdate()
                ->findOrFail($id);

            $oldAccount = Account::where('user_id', auth()->id())
                ->lockForUpdate()
                ->findOrFail($income->account_id);
            $oldAmount = $income->amount;

            $newAccountId = $data['account_id'] ?? $income->account_id;
            $newAmount = $data['amount'] ?? $income->amount;

            $newAccount = Account::where('user_id', auth()->id())
                ->lockForUpdate()
                ->findOrFail($newAccountId);

            if ($oldAccount->id === $newAccount->id) {
                $oldAccount->decrement('current_balance', $oldAmount);
                $oldAccount->increment('current_balance', $newAmount);
            } else {
                $oldAccount->decrement('current_balance', $oldAmount);
                $newAccount->increment('current_balance', $newAmount);
            }

            $income->update([
                'account_id' => $newAccount->id,
                'date' => $data['date'] ?? $income->date,
                'amount' => $newAmount,
                'note' => $data['note'] ?? $income->note,
            ]);

            return response()->json([
                'message' => 'Income updated successfully',
                'income' => $income->fresh('account'),
            ]);
        });
    }

    public function destroy($id)
    {
        return DB::transaction(function () use ($id) {
            $income = Income::where('user_id', auth()->id())
                ->lockForUpdate()
                ->findOrFail($id);

            $account = Account::where('user_id', auth()->id())
                ->lockForUpdate()
                ->findOrFail($income->account_id);

            $account->decrement('current_balance', $income->amount);

            $income->delete();

            return response()->json(['message' => 'Income deleted successfully']);
        });
    }
}
