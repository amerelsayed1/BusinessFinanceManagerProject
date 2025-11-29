<?php
namespace App\Http\Controllers;

use App\Models\MonthlySale;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Invoice;
use App\Models\PosOrder;
use Illuminate\Http\Request;
use Carbon\Carbon;

class MonthlyReportController extends Controller
{
    public function show(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000|max:2100',
        ]);

        $userId = auth()->id();
        $month = $request->month;
        $year = $request->year;

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        // Total Sales
        $monthlySales = MonthlySale::where('user_id', $userId)
            ->whereMonth('month', $month)
            ->whereYear('month', $year)
            ->first();

        $posSales = PosOrder::where('user_id', $userId)
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('total_amount');

        $totalSales = ($monthlySales ? $monthlySales->total_sales : 0) + $posSales;

        // Total Expenses
        $totalExpenses = Expense::where('user_id', $userId)
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');

        // Ads Expenses
        $adsCategory = ExpenseCategory::where('user_id', $userId)
            ->where('name', 'Ads')
            ->first();

        $adsExpenses = 0;
        if ($adsCategory) {
            $adsExpenses = Expense::where('user_id', $userId)
                ->where('category_id', $adsCategory->id)
                ->whereBetween('date', [$startDate, $endDate])
                ->sum('amount');
        }

        // Non-Ads Expenses
        $nonAdsExpenses = $totalExpenses - $adsExpenses;

        // Profit and ROI
        $profit = $totalSales - $adsExpenses;
        $roiPercent = $adsExpenses > 0 ? ($profit / $adsExpenses) * 100 : 0;

        // Pending Invoices
        $pendingInvoicesAmount = Invoice::where('user_id', $userId)
            ->where('status', 'pending')
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');

        // Paid Invoices
        $paidInvoicesAmount = Invoice::where('user_id', $userId)
            ->where('status', 'paid')
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');

        return response()->json([
            'date' => sprintf('%02d/%d', $month, $year),
            'total_sales' => (float) $totalSales,
            'total_expenses' => (float) $totalExpenses,
            'ads_expenses' => (float) $adsExpenses,
            'non_ads_expenses' => (float) $nonAdsExpenses,
            'profit' => (float) $profit,
            'roi_percent' => round($roiPercent, 2),
            'pending_invoices_amount' => (float) $pendingInvoicesAmount,
            'paid_invoices_amount' => (float) $paidInvoicesAmount,
        ]);
    }
}
