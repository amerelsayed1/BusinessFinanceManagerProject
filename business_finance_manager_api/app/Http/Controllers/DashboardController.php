<?php

// app/Http/Controllers/DashboardController.php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\MonthlySales;
use App\Models\ExpenseCategory;
use App\Models\Income;
use App\Models\Purchase;
use App\Models\PosOrder;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = auth()->id();
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        // Total Balance
        $totalBalance = Account::where('user_id', $userId)->sum('current_balance');

        // Total Expenses (current month)
        $totalExpenses = Expense::where('user_id', $userId)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        // Pending Invoices (current month) - assuming status field
        $pendingInvoices = Invoice::where('user_id', $userId)
            ->where('status', 'pending')
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        // Paid Invoices (current month)
        $paidInvoices = Invoice::where('user_id', $userId)
            ->where('status', 'paid')
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        // ROI Calculation
        $monthlySales = MonthlySales::where('user_id', $userId)
            ->where('month', $now->month)
            ->where('year', $now->year)
            ->first();

        $posSales = PosOrder::where('user_id', $userId)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->sum('total_amount');

        $totalSales = ($monthlySales ? $monthlySales->total_sales : 0) + $posSales;

        $adsCategory = ExpenseCategory::where('user_id', $userId)
            ->where('name', 'Ads')
            ->first();

        $adsExpenses = 0;
        if ($adsCategory) {
            $adsExpenses = Expense::where('user_id', $userId)
                ->where('category_id', $adsCategory->id)
                ->whereBetween('date', [$startOfMonth, $endOfMonth])
                ->sum('amount');
        }

        $profit = $totalSales - $adsExpenses;
        $roiPercent = $adsExpenses > 0 ? ($profit / $adsExpenses) * 100 : 0;

        $currency = auth()->user()->default_currency;

        return response()->json([
            'total_balance' => (float) $totalBalance,
            'total_expenses' => (float) $totalExpenses,
            'pending_invoices' => (float) $pendingInvoices,
            'paid_invoices' => (float) $paidInvoices,
            'roi' => [
                'date' => sprintf('%02d/%d', $now->month, $now->year),
                'roi_percent' => round($roiPercent, 2),
                'profit' => (float) $profit,
                'total_sales' => (float) $totalSales,
                'ads_expenses' => (float) $adsExpenses,
            ],
            'currency' => $currency,
        ]);
    }

    public function summary(Request $request)
    {
        $user = auth()->user();
        $monthParam = $request->query('month', now()->format('Y-m'));
        $start = Carbon::parse($monthParam . '-01')->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $totalIncome = Income::where('user_id', $user->id)
            ->whereBetween('date', [$start, $end])
            ->sum('amount');

        $expensesTotal = Expense::where('user_id', $user->id)
            ->whereBetween('date', [$start, $end])
            ->sum('amount');

        $purchasesTotal = Purchase::where('user_id', $user->id)
            ->whereBetween('date', [$start, $end])
            ->sum('total_amount');

        $expensesByCategory = Expense::where('user_id', $user->id)
            ->whereBetween('date', [$start, $end])
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->get();

        $categoryNames = ExpenseCategory::where('user_id', $user->id)
            ->pluck('name', 'id');

        $expensesByCategory = $expensesByCategory->map(function ($item) use ($categoryNames) {
            $name = $categoryNames[$item->category_id] ?? 'Uncategorized';
            return [
                'category' => $name,
                'amount' => (float) $item->total,
            ];
        })->values()->toArray();

        if ($purchasesTotal > 0) {
            $expensesByCategory[] = [
                'category' => 'Product Purchases',
                'amount' => (float) $purchasesTotal,
            ];
        }

        $totalExpenses = $expensesTotal + $purchasesTotal;

        return response()->json([
            'month' => $monthParam,
            'currency' => $user->default_currency,
            'total_income' => (float) $totalIncome,
            'total_expenses' => (float) $totalExpenses,
            'total_purchases' => (float) $purchasesTotal,
            'net_profit' => (float) ($totalIncome - $totalExpenses),
            'expenses_by_category' => $expensesByCategory,
        ]);
    }
}
