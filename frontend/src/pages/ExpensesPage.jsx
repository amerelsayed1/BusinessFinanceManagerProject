import React from 'react';
import { Plus, Trash2 } from 'lucide-react';
import MonthNavigator from '../components/MonthNavigator';

export default function ExpensesPage({
  currency,
  accounts,
  monthLabel,
  onPrevMonth,
  onNextMonth,
  newExpense,
  setNewExpense,
  expenses,
  totalForMonth,
  onAddExpense,
  onDeleteExpense,
  getAccountName,
}) {
  return (
    <div className="bg-white rounded-lg shadow-lg p-4 md:p-6">
      <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <h2 className="text-2xl font-bold text-gray-800">Expenses</h2>

        <MonthNavigator
          label={monthLabel}
          onPrev={onPrevMonth}
          onNext={onNextMonth}
        />
      </div>

      <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <p className="text-sm text-gray-600">
          Total expenses for {monthLabel}
        </p>
        <p className="text-3xl font-bold text-blue-600">
          {currency} {totalForMonth.toFixed(2)}
        </p>
      </div>

      {/* Add Expense form */}
      <div className="space-y-4 mb-6">
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <input
            type="text"
            placeholder="Description"
            value={newExpense.description}
            onChange={(e) =>
              setNewExpense((prev) => ({
                ...prev,
                description: e.target.value,
              }))
            }
            className="border rounded px-4 py-2"
          />
          <input
            type="number"
            placeholder="Amount"
            value={newExpense.amount}
            onChange={(e) =>
              setNewExpense((prev) => ({
                ...prev,
                amount: e.target.value,
              }))
            }
            className="border rounded px-4 py-2"
          />
        </div>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <input
            type="date"
            value={newExpense.date}
            onChange={(e) =>
              setNewExpense((prev) => ({
                ...prev,
                date: e.target.value,
              }))
            }
            className="border rounded px-4 py-2"
          />
          <input
            type="text"
            placeholder="Category"
            value={newExpense.category}
            onChange={(e) =>
              setNewExpense((prev) => ({
                ...prev,
                category: e.target.value,
              }))
            }
            className="border rounded px-4 py-2"
          />
          <select
            value={newExpense.accountId}
            onChange={(e) =>
              setNewExpense((prev) => ({
                ...prev,
                accountId: e.target.value,
              }))
            }
            className="border rounded px-4 py-2"
          >
            {accounts.map((acc) => (
              <option key={acc.id} value={acc.id}>
                {acc.name}
              </option>
            ))}
          </select>
        </div>

        {/* Ads checkbox */}
        <div className="flex items-center gap-2">
          <input
            id="isAds"
            type="checkbox"
            checked={!!newExpense.isAds}
            onChange={(e) =>
              setNewExpense((prev) => ({
                ...prev,
                isAds: e.target.checked,
              }))
            }
            className="h-4 w-4"
          />
          <label htmlFor="isAds" className="text-sm text-gray-700">
            This is Ads cost
          </label>
        </div>

        <button
          type="button"
          onClick={onAddExpense}
          className="w-full bg-blue-500 text-white rounded px-4 py-2 hover:bg-blue-600 flex items-center justify-center"
        >
          <Plus size={20} className="mr-2" /> Add Expense
        </button>
      </div>

      {/* Monthly expenses table */}
      <div className="overflow-x-auto">
        <table className="w-full">
          <thead className="bg-gray-100">
            <tr>
              <th className="text-left p-3 text-sm">Description</th>
              <th className="text-left p-3 text-sm">Amount</th>
              <th className="text-left p-3 text-sm">Date</th>
              <th className="text-left p-3 text-sm">Category</th>
              <th className="text-left p-3 text-sm">Account</th>
              <th className="text-left p-3 text-sm">Ads</th>
              <th className="text-left p-3 text-sm">Action</th>
            </tr>
          </thead>
          <tbody>
            {expenses.map((exp) => (
              <tr key={exp.id} className="border-b hover:bg-gray-50">
                <td className="p-3">{exp.description}</td>
                <td className="p-3">
                  {currency} {exp.amount}
                </td>
                <td className="p-3">{exp.date}</td>
                <td className="p-3">{exp.category}</td>
                <td className="p-3 text-sm text-blue-600">
                  {getAccountName(exp.accountId)}
                </td>
                <td className="p-3 text-sm">
                  {exp.isAds ? (
                    <span className="px-2 py-1 rounded-full text-xs bg-purple-100 text-purple-700">
                      Ads
                    </span>
                  ) : (
                    '-'
                  )}
                </td>
                <td className="p-3">
                  <button
                    type="button"
                    onClick={() => onDeleteExpense(exp)}
                    className="text-red-500 hover:text-red-700"
                  >
                    <Trash2 size={18} />
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>

        {expenses.length === 0 && (
          <p className="text-center text-gray-500 py-8">
            No expenses for this month
          </p>
        )}
      </div>
    </div>
  );
}
