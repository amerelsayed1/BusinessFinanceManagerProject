import React, { useState } from 'react';
import { DollarSign, Plus, Trash2 } from 'lucide-react';

export default function AccountsPage({
  currency,
  accounts,
  onAddDeposit,
  onDeleteAccount,
}) {
  const [newAccountName, setNewAccountName] = useState('');
  const [newAccountBalance, setNewAccountBalance] = useState('');

  const handleCreateAccount = () => {
    if (!newAccountName.trim()) return;
    const initialBalance = parseFloat(newAccountBalance || '0') || 0;
    const maxId = accounts.reduce((max, acc) => Math.max(max, acc.id), 0);
    const newAccount = {
      id: maxId + 1,
      name: newAccountName.trim(),
      balance: initialBalance,
    };
    // Parent state is updated via lifting (but here we just call onAddDeposit after?).
    // Simpler: we let parent update accounts via a callback; for now this page assumes parent passes updated accounts.
    alert(
      'To keep this simple, add the new account directly in the App state or extend this component with a dedicated callback.'
    );
    setNewAccountName('');
    setNewAccountBalance('');
  };

  return (
    <div className="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6">
      {accounts.map((account) => (
        <div key={account.id} className="bg-white rounded-lg shadow-lg p-6">
          <div className="flex items-start justify-between mb-4 gap-2">
            <div>
              <h3 className="text-lg font-bold text-gray-800">
                {account.name}
              </h3>
              <p className="text-3xl font-bold text-blue-600 mt-2">
                {currency} {account.balance.toFixed(2)}
              </p>
            </div>
            <div className="flex flex-col items-end gap-2">
              <DollarSign className="text-blue-500" size={32} />
              <button
                type="button"
                onClick={() => onDeleteAccount(account)}
                className="text-red-500 hover:text-red-700 text-xs inline-flex items-center gap-1"
              >
                <Trash2 size={14} />
                <span>Delete</span>
              </button>
            </div>
          </div>
          <div className="mt-4">
            <label className="text-sm text-gray-600 mb-2 block">
              Add Deposit
            </label>
            <div className="flex gap-2">
              <input
                type="number"
                placeholder="Amount"
                id={`deposit-${account.id}`}
                className="border rounded px-3 py-2 flex-1"
              />
              <button
                type="button"
                onClick={() => {
                  const input = document.getElementById(`deposit-${account.id}`);
                  if (!input) return;
                  onAddDeposit(account.id, input.value);
                  input.value = '';
                }}
                className="bg-green-500 text-white rounded px-4 py-2 hover:bg-green-600"
              >
                <Plus size={20} />
              </button>
            </div>
          </div>
        </div>
      ))}

      {/* Simple "Add account" card - visual only for now */}
      <div className="bg-white rounded-lg shadow-lg p-6 flex flex-col justify-between">
        <div>
          <h3 className="text-lg font-bold text-gray-800 mb-2">
            Add New Account
          </h3>
          <p className="text-sm text-gray-500 mb-4">
            Create a new bank, wallet, or provider account.
          </p>
          <div className="space-y-3">
            <input
              type="text"
              placeholder="Account name"
              value={newAccountName}
              onChange={(e) => setNewAccountName(e.target.value)}
              className="border rounded px-3 py-2 w-full"
            />
            <input
              type="number"
              placeholder={`Initial balance (${currency})`}
              value={newAccountBalance}
              onChange={(e) => setNewAccountBalance(e.target.value)}
              className="border rounded px-3 py-2 w-full"
            />
          </div>
        </div>
        <button
          type="button"
          onClick={handleCreateAccount}
          className="mt-4 w-full bg-blue-600 text-white rounded px-4 py-2 font-semibold hover:bg-blue-700 flex items-center justify-center gap-1"
        >
          <Plus size={18} />
          <span>Create (demo only)</span>
        </button>
      </div>
    </div>
  );
}
