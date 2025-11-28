<?php

namespace App\Http\Controllers;

use App\Models\AccountTransfer;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Purchase;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
    public function accountantExport(Request $request)
    {
        $userId = auth()->id();
        $monthParam = $request->query('month', now()->format('Y-m'));
        $start = Carbon::parse($monthParam . '-01')->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $incomes = Income::with('account')
            ->where('user_id', $userId)
            ->whereBetween('date', [$start, $end])
            ->get();

        $expenses = Expense::with(['account', 'category'])
            ->where('user_id', $userId)
            ->whereBetween('date', [$start, $end])
            ->get();

        $purchases = Purchase::with('account')
            ->where('user_id', $userId)
            ->whereBetween('date', [$start, $end])
            ->get();

        $transfers = AccountTransfer::with(['fromAccount', 'toAccount'])
            ->where('user_id', $userId)
            ->whereBetween('date', [$start, $end])
            ->get();

        return response()->json([
            'month' => $monthParam,
            'incomes' => $incomes,
            'expenses' => $expenses,
            'purchases' => $purchases,
            'transfers' => $transfers,
        ]);
    }
}
