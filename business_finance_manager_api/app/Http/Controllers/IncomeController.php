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

        $account = Account::where('user_id', auth()->id())
            ->lockForUpdate()
            ->findOrFail($data['account_id']);

        return DB::transaction(function () use ($data, $account) {
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
}
