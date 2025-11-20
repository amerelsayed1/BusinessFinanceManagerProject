import React, { useState } from 'react';
import { X, User2 } from 'lucide-react';

import HomePage from './pages/HomePage.jsx';
import AccountsPage from './pages/AccountsPage.jsx';
import ExpensesPage from './pages/ExpensesPage.jsx';
import ProviderInvoicesPage from './pages/ProviderInvoicesPage.jsx';
import ProfilePage from './pages/ProfilePage.jsx';

const CURRENCY = 'EGP';

const getCurrentMonth = () => new Date().toISOString().slice(0, 7); // "YYYY-MM"

// Fixed reference: current real month (e.g. "2025-11")
const CURRENT_MONTH = getCurrentMonth();

const shiftMonth = (ym, delta) => {
  let [year, month] = ym.split('-').map(Number); // "2025-11" -> [2025, 11]

  month += delta; // move by +delta / -delta

  // Normalize year/month manually
  while (month > 12) {
    month -= 12;
    year += 1;
  }
  while (month < 1) {
    month += 12;
    year -= 1;
  }

  const next = `${year}-${String(month).padStart(2, '0')}`; // "YYYY-MM"

  // ⛔ Block going *after* current month when clicking next
  if (delta > 0 && next > CURRENT_MONTH) {
    return ym; // don’t move, stay where we are
  }

  return next;
};

const formatMonthLabel = (ym) => {
  const [year, month] = ym.split('-').map(Number);
  const date = new Date(year, month - 1, 1);
  return date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
};

export default function App() {
  const [accounts, setAccounts] = useState([
    { id: 1, name: 'Alahly Bank', balance: 0 },
    { id: 2, name: 'EG Post Account', balance: 0 },
    { id: 3, name: 'Kashier', balance: 0 },
  ]);

  const [expenses, setExpenses] = useState([]);
  const [bills, setBills] = useState([]);

  // 'home' | 'accounts' | 'expenses' | 'providers' | 'profile'
  const [activeSection, setActiveSection] = useState('home');
  const [viewImage, setViewImage] = useState(null);

  const todayMonth = getCurrentMonth();
  const [expenseMonth, setExpenseMonth] = useState(todayMonth);
  const [invoiceMonth, setInvoiceMonth] = useState(todayMonth);

  const [newExpense, setNewExpense] = useState({
    description: '',
    amount: '',
    date: new Date().toISOString().split('T')[0],
    category: '',
    accountId: '1',
    isAds: false,
  });

  const [newBill, setNewBill] = useState({
    description: '',
    amount: '',
    date: new Date().toISOString().split('T')[0],
    status: 'pending',
    accountId: '1',
    image: null,
    isMonthly: false,
  });

  // Modal for adding balance from Home total card
  const [showAddBalanceModal, setShowAddBalanceModal] = useState(false);
  const [addBalanceForm, setAddBalanceForm] = useState({
    amount: '',
    accountId: '1',
  });

  const totalBalance = accounts.reduce((sum, acc) => sum + acc.balance, 0);

  // ===== HOME SUMMARY (current month only) =====
  const currentMonth = todayMonth;

  const expensesThisMonth = expenses.filter((exp) =>
      exp.date.startsWith(currentMonth)
  );
  const totalExpensesThisMonth = expensesThisMonth.reduce(
      (sum, exp) => sum + Number(exp.amount || 0),
      0
  );

  const billsThisMonth = bills.filter((b) => b.date.startsWith(currentMonth));
  const pendingInvoicesThisMonth = billsThisMonth
      .filter((b) => b.status === 'pending')
      .reduce((sum, b) => sum + Number(b.amount || 0), 0);
  const paidInvoicesThisMonth = billsThisMonth
      .filter((b) => b.status === 'paid')
      .reduce((sum, b) => sum + Number(b.amount || 0), 0);

  // ===== EXPENSES TAB (selected month) =====
  const expensesForSelectedMonth = expenses.filter((exp) =>
      exp.date.startsWith(expenseMonth)
  );
  const totalExpensesForSelectedMonth = expensesForSelectedMonth.reduce(
      (sum, exp) => sum + Number(exp.amount || 0),
      0
  );

  // ===== PROVIDER INVOICES TAB (selected month) =====
  const billsForSelectedMonth = bills.filter((b) =>
      b.date.startsWith(invoiceMonth)
  );
  const pendingInvoicesForSelectedMonth = billsForSelectedMonth
      .filter((b) => b.status === 'pending')
      .reduce((sum, b) => sum + Number(b.amount || 0), 0);
  const paidInvoicesForSelectedMonth = billsForSelectedMonth
      .filter((b) => b.status === 'paid')
      .reduce((sum, b) => sum + Number(b.amount || 0), 0);

  // ===== HELPERS =====

  const getAccountName = (accountId) => {
    const account = accounts.find((acc) => acc.id === parseInt(accountId, 10));
    return account ? account.name : 'Unknown';
  };

  const handleAddDeposit = (accountId, amount) => {
    const depositAmount = parseFloat(amount);
    if (!isNaN(depositAmount) && depositAmount > 0) {
      setAccounts((prev) =>
          prev.map((acc) =>
              acc.id === accountId
                  ? { ...acc, balance: acc.balance + depositAmount }
                  : acc
          )
      );
    }
  };

  const deleteAccount = (account) => {
    if (!window.confirm(`Delete account "${account.name}"?`)) {
      return;
    }
    setAccounts((prev) => prev.filter((a) => a.id !== account.id));
  };

  const addExpense = () => {
    if (newExpense.description && newExpense.amount) {
      const expenseAmount = parseFloat(newExpense.amount);
      const accountId = parseInt(newExpense.accountId, 10);

      const expense = {
        ...newExpense,
        id: Date.now(),
        amount: expenseAmount,
      };

      setExpenses((prev) => [...prev, expense]);

      setAccounts((prev) =>
          prev.map((acc) =>
              acc.id === accountId
                  ? { ...acc, balance: acc.balance - expenseAmount }
                  : acc
          )
      );

      setNewExpense((prev) => ({
        ...prev,
        description: '',
        amount: '',
        date: new Date().toISOString().split('T')[0],
        category: '',
        accountId: '1',
        isAds: false,
      }));
    }
  };

  const addBill = () => {
    if (newBill.description && newBill.amount) {
      const billAmount = parseFloat(newBill.amount);
      const accountId = parseInt(newBill.accountId, 10);

      const bill = {
        ...newBill,
        id: Date.now(),
        amount: billAmount,
      };

      setBills((prev) => [...prev, bill]);

      if (newBill.status === 'paid') {
        setAccounts((prev) =>
            prev.map((acc) =>
                acc.id === accountId
                    ? { ...acc, balance: acc.balance - billAmount }
                    : acc
            )
        );
      }

      setNewBill((prev) => ({
        ...prev,
        description: '',
        amount: '',
        date: new Date().toISOString().split('T')[0],
        status: 'pending',
        accountId: '1',
        image: null,
        isMonthly: false,
      }));
    }
  };

  const deleteExpense = (expense) => {
    setExpenses((prev) => prev.filter((e) => e.id !== expense.id));
    setAccounts((prev) =>
        prev.map((acc) =>
            acc.id === parseInt(expense.accountId, 10)
                ? { ...acc, balance: acc.balance + Number(expense.amount || 0) }
                : acc
        )
    );
  };

  const deleteBill = (bill) => {
    setBills((prev) => prev.filter((b) => b.id !== bill.id));
    if (bill.status === 'paid') {
      setAccounts((prev) =>
          prev.map((acc) =>
              acc.id === parseInt(bill.accountId, 10)
                  ? { ...acc, balance: acc.balance + Number(bill.amount || 0) }
                  : acc
          )
      );
    }
  };

  const toggleBillStatus = (bill) => {
    const newStatus = bill.status === 'paid' ? 'pending' : 'paid';
    const accountId = parseInt(bill.accountId, 10);
    const amount = Number(bill.amount || 0);

    setBills((prev) =>
        prev.map((b) => (b.id === bill.id ? { ...b, status: newStatus } : b))
    );

    if (newStatus === 'paid') {
      setAccounts((prev) =>
          prev.map((acc) =>
              acc.id === accountId
                  ? { ...acc, balance: acc.balance - amount }
                  : acc
          )
      );
    } else {
      setAccounts((prev) =>
          prev.map((acc) =>
              acc.id === accountId
                  ? { ...acc, balance: acc.balance + amount }
                  : acc
          )
      );
    }
  };

  const openAddBalanceModal = () => {
    setAddBalanceForm({
      amount: '',
      accountId: accounts[0]?.id?.toString() ?? '1',
    });
    setShowAddBalanceModal(true);
  };

  const submitAddBalance = () => {
    const amount = parseFloat(addBalanceForm.amount);
    if (isNaN(amount) || amount <= 0) {
      alert('Please enter a valid amount.');
      return;
    }
    const accountId = parseInt(addBalanceForm.accountId, 10);
    handleAddDeposit(accountId, addBalanceForm.amount);
    setShowAddBalanceModal(false);
  };

  // ===== Month change handlers so form dates follow the selected month =====
  const handleExpensePrevMonth = () => {
    const next = shiftMonth(expenseMonth, -1);
    console.log(next)
    setExpenseMonth(next);
    setNewExpense((e) => ({
      ...e,
      date: `${next}-${new Date().getDate().toString().padStart(2, '0')}`,
    }));
  };

  const handleExpenseNextMonth = () => {
    const next = shiftMonth(expenseMonth, 1);
    console.log(next)
    setExpenseMonth(next);
    setNewExpense((e) => ({
      ...e,
      date: `${next}-${new Date().getDate().toString().padStart(2, '0')}`,
    }));
  };

  const handleInvoicePrevMonth = () => {
    const next = shiftMonth(invoiceMonth, -1);
    setInvoiceMonth(next);
    setNewBill((b) => ({
      ...b,
      date: `${next}-01`,
    }));
  };

  const handleInvoiceNextMonth = () => {
    const next = shiftMonth(invoiceMonth, 1);
    setInvoiceMonth(next);
    setNewBill((b) => ({
      ...b,
      date: `${next}-01`,
    }));
  };

  const navItems = [
    { id: 'home', label: 'Home' },
    { id: 'accounts', label: 'Accounts' },
    { id: 'expenses', label: 'Expenses' },
    { id: 'providers', label: 'Provider Invoices' },
    // profile is opened from top-right icon
  ];

  return (
      <div className="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 p-4 md:p-6">
        <div className="max-w-7xl mx-auto">
          {/* Header with title + profile icon on top-right */}
          <div className="flex items-center justify-between mb-6 md:mb-8">
            <h1 className="text-3xl md:text-4xl font-bold text-gray-800">
              Business Finance Manager
            </h1>
            <button
                type="button"
                onClick={() => setActiveSection('profile')}
                className={`h-10 w-10 rounded-full flex items-center justify-center border border-gray-300 hover:bg-gray-100 transition ${
                    activeSection === 'profile'
                        ? 'bg-blue-600 text-white border-blue-600'
                        : 'text-gray-700'
                }`}
            >
              <User2 size={20} />
            </button>
          </div>

          {/* Top Navigation */}
          <nav className="bg-white rounded-lg shadow mb-6 px-4 py-2 flex flex-wrap gap-2">
            {navItems.map((item) => (
                <button
                    key={item.id}
                    type="button"
                    onClick={() => setActiveSection(item.id)}
                    className={`px-3 py-2 rounded font-semibold text-sm md:text-base ${
                        activeSection === item.id
                            ? 'bg-blue-600 text-white'
                            : 'text-gray-700 hover:bg-gray-100'
                    }`}
                >
                  {item.label}
                </button>
            ))}
          </nav>

          {/* Sections */}
          {activeSection === 'home' && (
              <HomePage
                  currency={CURRENCY}
                  totalBalance={totalBalance}
                  currentMonthLabel={formatMonthLabel(currentMonth)}
                  totalExpensesThisMonth={totalExpensesThisMonth}
                  pendingInvoicesThisMonth={pendingInvoicesThisMonth}
                  paidInvoicesThisMonth={paidInvoicesThisMonth}
                  onTotalBalanceClick={openAddBalanceModal}
              />
          )}

          {activeSection === 'accounts' && (
              <AccountsPage
                  currency={CURRENCY}
                  accounts={accounts}
                  onAddDeposit={handleAddDeposit}
                  onDeleteAccount={deleteAccount}
              />
          )}

          {activeSection === 'expenses' && (
              <ExpensesPage
                  currency={CURRENCY}
                  accounts={accounts}
                  monthLabel={formatMonthLabel(expenseMonth)}
                  onPrevMonth={handleExpensePrevMonth}
                  onNextMonth={handleExpenseNextMonth}
                  newExpense={newExpense}
                  setNewExpense={setNewExpense}
                  expenses={expensesForSelectedMonth}
                  totalForMonth={totalExpensesForSelectedMonth}
                  onAddExpense={addExpense}
                  onDeleteExpense={deleteExpense}
                  getAccountName={getAccountName}
              />
          )}

          {activeSection === 'providers' && (
              <ProviderInvoicesPage
                  currency={CURRENCY}
                  accounts={accounts}
                  monthLabel={formatMonthLabel(invoiceMonth)}
                  onPrevMonth={handleInvoicePrevMonth}
                  onNextMonth={handleInvoiceNextMonth}
                  newBill={newBill}
                  setNewBill={setNewBill}
                  bills={billsForSelectedMonth}
                  pendingTotal={pendingInvoicesForSelectedMonth}
                  paidTotal={paidInvoicesForSelectedMonth}
                  onAddBill={addBill}
                  onDeleteBill={deleteBill}
                  onToggleStatus={toggleBillStatus}
                  onUploadReceipt={(e) => {
                    const file = e.target.files && e.target.files[0];
                    if (file) {
                      const reader = new FileReader();
                      reader.onloadend = () => {
                        setNewBill((prev) => ({ ...prev, image: reader.result }));
                      };
                      reader.readAsDataURL(file);
                    }
                  }}
                  onViewReceipt={(image) => setViewImage(image)}
              />
          )}

          {activeSection === 'profile' && <ProfilePage />}

          {/* Image Viewer Modal */}
          {viewImage && (
              <div
                  className="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 p-4"
                  onClick={() => setViewImage(null)}
              >
                <div className="relative max-w-4xl max-h-full">
                  <button
                      type="button"
                      onClick={() => setViewImage(null)}
                      className="absolute -top-10 right-0 text-white hover:text-gray-300"
                  >
                    <X size={32} />
                  </button>
                  <img
                      src={viewImage}
                      alt="Receipt"
                      className="max-w-full max-h-screen object-contain rounded-lg"
                      onClick={(e) => e.stopPropagation()}
                  />
                </div>
              </div>
          )}

          {/* Add Balance Modal */}
          {showAddBalanceModal && (
              <div className="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 px-4">
                <div className="bg-white rounded-lg shadow-lg p-6 w-full max-w-md relative">
                  <button
                      type="button"
                      onClick={() => setShowAddBalanceModal(false)}
                      className="absolute right-3 top-3 text-gray-500 hover:text-gray-700"
                  >
                    <X size={20} />
                  </button>
                  <h2 className="text-xl font-bold text-gray-800 mb-4">
                    Add Balance
                  </h2>
                  <div className="space-y-4">
                    <div>
                      <label className="block text-sm text-gray-600 mb-1">
                        Amount ({CURRENCY})
                      </label>
                      <input
                          type="number"
                          value={addBalanceForm.amount}
                          onChange={(e) =>
                              setAddBalanceForm((prev) => ({
                                ...prev,
                                amount: e.target.value,
                              }))
                          }
                          className="border rounded px-3 py-2 w-full"
                          placeholder="0.00"
                      />
                    </div>
                    <div>
                      <label className="block text-sm text-gray-600 mb-1">
                        Account
                      </label>
                      <select
                          value={addBalanceForm.accountId}
                          onChange={(e) =>
                              setAddBalanceForm((prev) => ({
                                ...prev,
                                accountId: e.target.value,
                              }))
                          }
                          className="border rounded px-3 py-2 w-full"
                      >
                        {accounts.map((acc) => (
                            <option key={acc.id} value={acc.id}>
                              {acc.name}
                            </option>
                        ))}
                      </select>
                    </div>
                    <button
                        type="button"
                        onClick={submitAddBalance}
                        className="w-full bg-blue-600 text-white rounded px-4 py-2 font-semibold hover:bg-blue-700"
                    >
                      Add balance
                    </button>
                  </div>
                </div>
              </div>
          )}
        </div>
      </div>
  );
}