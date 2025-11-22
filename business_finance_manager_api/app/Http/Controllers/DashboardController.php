<?php

// app/Http/Controllers/DashboardController.php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\MonthlySales;
use App\Models\ExpenseCategory;
use App\Models\PosOrder;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = auth()->id();
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        // Total Balance
        $totalBalance = Account::where('user_id', $userId)->sum('balance');

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
}
