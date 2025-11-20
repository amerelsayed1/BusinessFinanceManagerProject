<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ExpenseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $month = $request->query('month'); // 'YYYY-MM'

        $query = Expense::query()
            ->with('account:id,name')
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc');

        if ($month) {
            $query->whereRaw("to_char(date, 'YYYY-MM') = ?", [$month]);
        }

        $expenses = $query->get()->map(function ($exp) {
            return [
                'id'         => $exp->id,
                'description'=> $exp->description,
                'amount'     => $exp->amount,
                'date'       => $exp->date->format('Y-m-d'),
                'category'   => $exp->category,
                'accountId'  => $exp->account_id,
            ];
        });

        return response()->json($expenses);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'description' => 'required|string',
            'amount'      => 'required|numeric|min:0.01',
            'date'        => 'required|date',
            'category'    => 'nullable|string',
            'accountId'   => 'required|integer|exists:accounts,id',
        ]);

        return DB::transaction(function () use ($data) {
            $expense = Expense::create([
                'description' => $data['description'],
                'amount'      => $data['amount'],
                'date'        => $data['date'],
                'category'    => $data['category'] ?? null,
                'account_id'  => $data['accountId'],
            ]);

            $account = Account::findOrFail($data['accountId']);
            $account->balance -= $data['amount'];
            $account->save();

            return response()->json([
                'id'         => $expense->id,
                'description'=> $expense->description,
                'amount'     => $expense->amount,
                'date'       => $expense->date->format('Y-m-d'),
                'category'   => $expense->category,
                'accountId'  => $expense->account_id,
            ], 201);
        });
    }

    public function destroy(int $id): JsonResponse
    {
        return DB::transaction(function () use ($id) {
            $expense = Expense::findOrFail($id);
            $account = $expense->account;

            if ($account) {
                $account->balance += $expense->amount;
                $account->save();
            }

            $expense->delete();

            return response()->json(['success' => true]);
        });
    }
}
