<?php
namespace App\Http\Controllers;

use App\Models\MonthlySales;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PosOrder;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ROIController extends Controller
{
    public function getCurrentMonthROI()
    {
        $now = Carbon::now();
        return $this->calculateROI($now->month, $now->year);
    }

    public function getROIByMonth(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000|max:2100',
        ]);

        return $this->calculateROI($request->month, $request->year);
    }

    private function calculateROI($month, $year)
    {
        $userId = auth()->id();
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        // Get total sales from monthly_sales table
        $monthlySales = MonthlySales::where('user_id', $userId)
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        // Also add POS sales for the month
        $posSales = PosOrder::where('user_id', $userId)
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('total_amount');

        $totalSales = ($monthlySales ? $monthlySales->total_sales : 0) + $posSales;

        // Get total expenses
        $totalExpenses = Expense::where('user_id', $userId)
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');

        // Get ads expenses (from "Ads" category)
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

        // Calculate profit and ROI
        $profit = $totalSales - $adsExpenses;
        $roiPercent = $adsExpenses > 0 ? ($profit / $adsExpenses) * 100 : 0;

        return response()->json([
            'date' => sprintf('%02d/%d', $month, $year),
            'total_sales' => (float) $totalSales,
            'total_expenses' => (float) $totalExpenses,
            'ads_expenses' => (float) $adsExpenses,
            'profit' => (float) $profit,
            'roi_percent' => round($roiPercent, 2),
        ]);
    }
}
