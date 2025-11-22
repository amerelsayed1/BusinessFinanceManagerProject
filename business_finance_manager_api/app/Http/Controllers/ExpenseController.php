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
                'isAds'      => $exp->is_ads,
            ];
        });

        return response()->json($expenses);
    }

    public function show(int $id): JsonResponse
    {
        $expense = Expense::with('account:id,name')->findOrFail($id);

        return response()->json([
            'id'         => $expense->id,
            'description'=> $expense->description,
            'amount'     => $expense->amount,
            'date'       => $expense->date->format('Y-m-d'),
            'category'   => $expense->category,
            'accountId'  => $expense->account_id,
            'accountName'=> $expense->account->name,
            'isAds'      => $expense->is_ads,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'description' => 'required|string',
            'amount'      => 'required|numeric|min:0.01',
            'date'        => 'required|date',
            'category'    => 'nullable|string',
            'accountId'   => 'required|integer|exists:accounts,id',
            'isAds'       => 'nullable|boolean',
        ]);

        return DB::transaction(function () use ($data) {
            $expense = Expense::create([
                'description' => $data['description'],
                'amount'      => $data['amount'],
                'date'        => $data['date'],
                'category'    => $data['category'] ?? null,
                'account_id'  => $data['accountId'],
                'is_ads'      => $data['isAds'] ?? false,
            ]);

            $account = Account::lockForUpdate()->findOrFail($data['accountId']);
            $account->balance -= $data['amount'];
            $account->save();

            return response()->json([
                'id'         => $expense->id,
                'description'=> $expense->description,
                'amount'     => $expense->amount,
                'date'       => $expense->date->format('Y-m-d'),
                'category'   => $expense->category,
                'accountId'  => $expense->account_id,
                'isAds'      => $expense->is_ads,
            ], 201);
        });
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'description' => 'sometimes|required|string',
            'amount'      => 'sometimes|required|numeric|min:0.01',
            'date'        => 'sometimes|required|date',
            'category'    => 'nullable|string',
            'accountId'   => 'sometimes|required|integer|exists:accounts,id',
            'isAds'       => 'nullable|boolean',
        ]);

        return DB::transaction(function () use ($data, $id) {
            $expense = Expense::lockForUpdate()->findOrFail($id);
            $oldAmount = $expense->amount;
            $oldAccountId = $expense->account_id;

            // Revert old account balance
            $oldAccount = Account::lockForUpdate()->findOrFail($oldAccountId);
            $oldAccount->balance += $oldAmount;
            $oldAccount->save();

            // Update expense
            $updateData = [];
            if (isset($data['description'])) $updateData['description'] = $data['description'];
            if (isset($data['amount'])) $updateData['amount'] = $data['amount'];
            if (isset($data['date'])) $updateData['date'] = $data['date'];
            if (isset($data['category'])) $updateData['category'] = $data['category'];
            if (isset($data['accountId'])) $updateData['account_id'] = $data['accountId'];
            if (isset($data['isAds'])) $updateData['is_ads'] = $data['isAds'];

            $expense->update($updateData);
            $expense->refresh();

            // Apply new amount to account (could be same or different account)
            $newAccount = Account::lockForUpdate()->findOrFail($expense->account_id);
            $newAccount->balance -= $expense->amount;
            $newAccount->save();

            return response()->json([
                'id'         => $expense->id,
                'description'=> $expense->description,
                'amount'     => $expense->amount,
                'date'       => $expense->date->format('Y-m-d'),
                'category'   => $expense->category,
                'accountId'  => $expense->account_id,
                'isAds'      => $expense->is_ads,
            ], 200);
        });
    }

    public function destroy(int $id): JsonResponse
    {
        return DB::transaction(function () use ($id) {
            $expense = Expense::lockForUpdate()->findOrFail($id);
            $account = Account::lockForUpdate()->find($expense->account_id);

            if ($account) {
                $account->balance += $expense->amount;
                $account->save();
            }

            $expense->delete();

            return response()->json(['success' => true, 'message' => 'Expense deleted successfully']);
        });
    }

    public function getByCategory(Request $request): JsonResponse
    {
        $month = $request->query('month'); // 'YYYY-MM'

        $query = Expense::query();

        if ($month) {
            $query->whereRaw("to_char(date, 'YYYY-MM') = ?", [$month]);
        }

        $expenses = $query->get()->groupBy('category')->map(function ($items, $category) {
            return [
                'category' => $category ?? 'Uncategorized',
                'total' => $items->sum('amount'),
                'count' => $items->count(),
            ];
        })->values();

        return response()->json($expenses);
    }

    public function getTotalByAccount(Request $request): JsonResponse
    {
        $month = $request->query('month'); // 'YYYY-MM'

        $query = Expense::query()->with('account:id,name');

        if ($month) {
            $query->whereRaw("to_char(date, 'YYYY-MM') = ?", [$month]);
        }

        $expenses = $query->get()->groupBy('account_id')->map(function ($items) {
            $account = $items->first()->account;
            return [
                'accountId' => $account->id,
                'accountName' => $account->name,
                'total' => $items->sum('amount'),
                'count' => $items->count(),
            ];
        })->values();

        return response()->json($expenses);
    }
}
