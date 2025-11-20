import React from 'react';
import { Plus } from 'lucide-react';

export default function HomePage({
  currency = 'EGP',
  totalBalance,
  currentMonthLabel,
  totalExpensesThisMonth,
  pendingInvoicesThisMonth,
  paidInvoicesThisMonth,
  onTotalBalanceClick,
}) {
  return (
    <div className="grid grid-cols-1 md:grid-cols-4 gap-4 md:gap-6">
      {/* Total Balance */}
      <div
        className="bg-white rounded-lg shadow-lg p-4 md:p-6 border-l-4 border-green-500 md:col-span-2 cursor-pointer hover:shadow-xl transition-shadow"
        onClick={onTotalBalanceClick}
      >
        <div className="flex items-center justify-between gap-4">
          <div>
            <p className="text-gray-500 text-xs md:text-sm">Total Balance</p>
            <p className="text-2xl md:text-4xl font-bold text-gray-800 mt-2">
              {currency} {totalBalance.toFixed(2)}
            </p>
            <p className="text-xs text-gray-400 mt-1">
              Current month: {currentMonthLabel}
            </p>
          </div>

          <button
            type="button"
            onClick={(e) => {
              e.stopPropagation();
              onTotalBalanceClick && onTotalBalanceClick();
            }}
            className="px-3 py-2 rounded bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 inline-flex items-center gap-1"
          >
            <Plus size={16} />
            <span>Add</span>
          </button>
        </div>
      </div>

      {/* Expenses this month */}
      <div className="bg-white rounded-lg shadow-lg p-4 md:p-6 border-l-4 border-red-500">
        <p className="text-gray-500 text-xs md:text-sm">
          Expenses ({currentMonthLabel})
        </p>
        <p className="text-xl md:text-3xl font-bold text-gray-800 mt-2">
          {currency} {totalExpensesThisMonth.toFixed(2)}
        </p>
      </div>

      {/* Provider invoices this month (Pending / Paid) */}
      <div className="bg-white rounded-lg shadow-lg p-4 md:p-6 border-l-4 border-blue-500">
        <p className="text-gray-500 text-xs md:text-sm">
          Provider Invoices ({currentMonthLabel})
        </p>
        <div className="mt-2 space-y-1 text-sm">
          <p className="text-xs md:text-sm text-yellow-700">
            Pending: {currency} {pendingInvoicesThisMonth.toFixed(2)}
          </p>
          <p className="text-xs md:text-sm text-green-700">
            Paid: {currency} {paidInvoicesThisMonth.toFixed(2)}
          </p>
        </div>
      </div>
    </div>
  );
}
