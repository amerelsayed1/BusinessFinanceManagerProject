<?php
// app/Http/Controllers/ExpenseController.php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseRequest;
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
        $from = $request->input('from', $request->input('start_date'));
        $to = $request->input('to', $request->input('end_date'));

        if ($from && $to) {
            $query->whereBetween('date', [$from, $to]);
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
            $account = Account::where('user_id', auth()->id())
                ->lockForUpdate()
                ->findOrFail($request->account_id);

            $expense = Expense::create([
                'user_id' => auth()->id(),
                'account_id' => $account->id,
                'category_id' => $request->category_id,
                'amount' => $request->amount,
                'date' => $request->date,
                'description' => $request->description,
            ]);

            // Decrease account balance
            $account->decrement('current_balance', $request->amount);

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
            $oldAccount = $expense->account()->lockForUpdate()->first();
            $oldAmount = $expense->amount;

            // Restore old account balance
            $oldAccount->increment('current_balance', $oldAmount);

            // Update expense
            $expense->update($request->only(['account_id', 'category_id', 'amount', 'date', 'description']));

            // Deduct from new/same account
            $newAccount = Account::where('user_id', auth()->id())
                ->lockForUpdate()
                ->findOrFail($expense->account_id);

            if ($newAccount->current_balance < $expense->amount) {
                DB::rollBack();
                return response()->json(['error' => 'Insufficient balance in account'], 400);
            }

            $newAccount->decrement('current_balance', $expense->amount);

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
            $expense->account()->lockForUpdate()->first()->increment('current_balance', $expense->amount);

            $expense->delete();

            DB::commit();

            return response()->json(['message' => 'Expense deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to delete expense: ' . $e->getMessage()], 500);
        }
    }
}
