<?php
// app/Http/Controllers/ExpenseController.php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = Expense::with(['account', 'category'])
            ->where('user_id', auth()->id());

        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by account
        if ($request->has('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        $expenses = $query->orderBy('date', 'desc')->get();

        return response()->json($expenses);
    }

    public function store(StoreExpenseRequest $request)
    {
        // Validation already done in request class
        DB::beginTransaction();
        try {
            $expense = Expense::create([
                'user_id' => auth()->id(),
                'account_id' => $request->account_id,
                'category_id' => $request->category_id,
                'amount' => $request->amount,
                'date' => $request->date,
                'description' => $request->description,
            ]);

            // Decrease account balance
            $account = Account::lockForUpdate()->findOrFail($request->account_id);
            $account->decrement('balance', $request->amount);

            DB::commit();

            $expense->load(['account', 'category']);

            return $this->success($expense, 'Expense created successfully', 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to create expense: ' . $e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        $expense = Expense::with(['account', 'category'])
            ->where('user_id', auth()->id())
            ->findOrFail($id);

        return response()->json($expense);
    }

    public function update(Request $request, $id)
    {
        $expense = Expense::where('user_id', auth()->id())->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'account_id' => 'sometimes|exists:accounts,id',
            'category_id' => 'nullable|exists:expense_categories,id',
            'amount' => 'sometimes|numeric|min:0.01',
            'date' => 'sometimes|date',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        DB::beginTransaction();
        try {
            $oldAccount = $expense->account;
            $oldAmount = $expense->amount;

            // Restore old account balance
            $oldAccount->increment('balance', $oldAmount);

            // Update expense
            $expense->update($request->only(['account_id', 'category_id', 'amount', 'date', 'description']));

            // Deduct from new/same account
            $newAccount = Account::findOrFail($expense->account_id);

            if ($newAccount->balance < $expense->amount) {
                DB::rollBack();
                return response()->json(['error' => 'Insufficient balance in account'], 400);
            }

            $newAccount->decrement('balance', $expense->amount);

            DB::commit();

            $expense->load(['account', 'category']);

            return response()->json([
                'message' => 'Expense updated successfully',
                'expense' => $expense,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to update expense: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $expense = Expense::where('user_id', auth()->id())->findOrFail($id);

        DB::beginTransaction();
        try {
            // Restore account balance
            $expense->account->increment('balance', $expense->amount);

            $expense->delete();

            DB::commit();

            return response()->json(['message' => 'Expense deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to delete expense: ' . $e->getMessage()], 500);
        }
    }
}
