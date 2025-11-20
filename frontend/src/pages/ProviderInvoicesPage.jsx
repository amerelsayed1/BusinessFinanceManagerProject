import React from 'react';
import { Plus, Trash2, Eye, X } from 'lucide-react';
import MonthNavigator from '../components/MonthNavigator';

export default function ProviderInvoicesPage({
  currency,
  accounts,
  monthLabel,
  onPrevMonth,
  onNextMonth,
  newBill,
  setNewBill,
  bills,
  pendingTotal,
  paidTotal,
  onAddBill,
  onDeleteBill,
  onToggleStatus,
  onUploadReceipt,
  onViewReceipt,
}) {
  return (
    <div className="bg-white rounded-lg shadow-lg p-4 md:p-6">
      <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <h2 className="text-2xl font-bold text-gray-800">Provider Invoices</h2>

        <MonthNavigator
          label={monthLabel}
          onPrev={onPrevMonth}
          onNext={onNextMonth}
        />
      </div>

      <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6 grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <p className="text-sm text-gray-600">
            Pending invoices for {monthLabel}
          </p>
          <p className="text-2xl font-bold text-yellow-700">
            {currency} {pendingTotal.toFixed(2)}
          </p>
        </div>
        <div>
          <p className="text-sm text-gray-600">
            Paid invoices for {monthLabel}
          </p>
          <p className="text-2xl font-bold text-green-700">
            {currency} {paidTotal.toFixed(2)}
          </p>
        </div>
      </div>

      {/* Add invoice form */}
      <div className="space-y-4 mb-6 border p-4 rounded-lg bg-gray-50">
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <input
            type="text"
            placeholder="Product/Bill Description"
            value={newBill.description}
            onChange={(e) =>
              setNewBill((prev) => ({ ...prev, description: e.target.value }))
            }
            className="border rounded px-4 py-2"
          />
          <input
            type="number"
            placeholder="Amount"
            value={newBill.amount}
            onChange={(e) =>
              setNewBill((prev) => ({ ...prev, amount: e.target.value }))
            }
            className="border rounded px-4 py-2"
          />
        </div>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <input
            type="date"
            value={newBill.date}
            onChange={(e) =>
              setNewBill((prev) => ({ ...prev, date: e.target.value }))
            }
            className="border rounded px-4 py-2"
          />
          <select
            value={newBill.accountId}
            onChange={(e) =>
              setNewBill((prev) => ({ ...prev, accountId: e.target.value }))
            }
            className="border rounded px-4 py-2"
          >
            {accounts.map((acc) => (
              <option key={acc.id} value={acc.id}>
                {acc.name}
              </option>
            ))}
          </select>
          <select
            value={newBill.status}
            onChange={(e) =>
              setNewBill((prev) => ({ ...prev, status: e.target.value }))
            }
            className="border rounded px-4 py-2"
          >
            <option value="pending">Pending</option>
            <option value="paid">Paid</option>
          </select>
        </div>

        <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
          <label className="border rounded px-4 py-2 bg-white cursor-pointer hover:bg-gray-100 flex items-center justify-center md:justify-start">
            <span className="mr-2">📷</span>
            {newBill.image ? 'Receipt Selected ✓' : 'Upload Receipt Image'}
            <input
              type="file"
              accept="image/*"
              onChange={onUploadReceipt}
              className="hidden"
            />
          </label>

          <div className="flex items-center gap-2">
            <input
              id="isMonthly"
              type="checkbox"
              checked={!!newBill.isMonthly}
              onChange={(e) =>
                setNewBill((prev) => ({
                  ...prev,
                  isMonthly: e.target.checked,
                }))
              }
              className="h-4 w-4"
            />
            <label htmlFor="isMonthly" className="text-sm text-gray-700">
              This is a monthly payment
            </label>
          </div>
        </div>

        {newBill.image && (
          <div className="flex items-center gap-2">
            <img
              src={newBill.image}
              alt="Preview"
              className="h-20 w-20 object-cover rounded border"
            />
            <button
              type="button"
              onClick={() =>
                setNewBill((prev) => ({
                  ...prev,
                  image: null,
                }))
              }
              className="text-red-500 hover:text-red-700"
            >
              <X size={20} />
            </button>
          </div>
        )}

        <button
          type="button"
          onClick={onAddBill}
          className="w-full bg-blue-500 text-white rounded px-6 py-2 hover:bg-blue-600 flex items-center justify-center"
        >
          <Plus size={20} className="mr-2" /> Add Invoice
        </button>
      </div>

      {/* Monthly invoices table */}
      <div className="overflow-x-auto">
        <table className="w-full">
          <thead className="bg-gray-100">
            <tr>
              <th className="text-left p-3 text-sm">Description</th>
              <th className="text-left p-3 text-sm">Amount</th>
              <th className="text-left p-3 text-sm">Date</th>
              <th className="text-left p-3 text-sm">Account</th>
              <th className="text-left p-3 text-sm">Receipt</th>
              <th className="text-left p-3 text-sm">Status</th>
              <th className="text-left p-3 text-sm">Monthly</th>
              <th className="text-left p-3 text-sm">Action</th>
            </tr>
          </thead>
          <tbody>
            {bills.map((bill) => (
              <tr key={bill.id} className="border-b hover:bg-gray-50">
                <td className="p-3">{bill.description}</td>
                <td className="p-3">
                  {currency} {bill.amount}
                </td>
                <td className="p-3">{bill.date}</td>
                <td className="p-3 text-sm text-blue-600">
                  {bill.accountName || bill.accountId}
                </td>
                <td className="p-3">
                  {bill.image ? (
                    <button
                      type="button"
                      onClick={() => onViewReceipt(bill.image)}
                      className="text-blue-500 hover:text-blue-700 flex items-center"
                    >
                      <Eye size={18} className="mr-1" /> View
                    </button>
                  ) : (
                    <span className="text-gray-400 text-sm">No image</span>
                  )}
                </td>
                <td className="p-3">
                  <span
                    role="button"
                    tabIndex={0}
                    onClick={() => onToggleStatus(bill)}
                    onKeyDown={(e) => {
                      if (e.key === 'Enter' || e.key === ' ') {
                        onToggleStatus(bill);
                      }
                    }}
                    className={`px-3 py-1 rounded-full text-xs cursor-pointer ${
                      bill.status === 'paid'
                        ? 'bg-green-100 text-green-800'
                        : 'bg-yellow-100 text-yellow-800'
                    }`}
                  >
                    {bill.status}
                  </span>
                </td>
                <td className="p-3 text-sm">
                  {bill.isMonthly ? (
                    <span className="px-2 py-1 rounded-full text-xs bg-purple-100 text-purple-700">
                      Monthly
                    </span>
                  ) : (
                    '-'
                  )}
                </td>
                <td className="p-3">
                  <button
                    type="button"
                    onClick={() => onDeleteBill(bill)}
                    className="text-red-500 hover:text-red-700"
                  >
                    <Trash2 size={18} />
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>

        {bills.length === 0 && (
          <p className="text-center text-gray-500 py-8">
            No invoices for this month
          </p>
        )}
      </div>
    </div>
  );
}
