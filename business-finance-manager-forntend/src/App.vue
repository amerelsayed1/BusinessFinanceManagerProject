<script setup>
import { ref, computed, onMounted } from 'vue'
import { X, User2 } from 'lucide-vue-next'
import HomePage from './pages/HomePage.vue'
import AccountsPage from './pages/AccountsPage.vue'
import ExpensesPage from './pages/ExpensesPage.vue'
import ProviderInvoicesPage from './pages/ProviderInvoicesPage.vue'
import ProfilePage from './pages/ProfilePage.vue'
import { apiGet, apiPost, apiDelete, apiPatch } from './api'

const CURRENCY = 'EGP'

const getCurrentMonth = () => new Date().toISOString().slice(0, 7) // YYYY-MM
const CURRENT_MONTH = getCurrentMonth()

const shiftMonth = (ym, delta) => {
  let [year, month] = ym.split('-').map(Number)
  month += delta

  while (month > 12) {
    month -= 12
    year += 1
  }
  while (month < 1) {
    month += 12
    year -= 1
  }

  const next = `${year}-${String(month).padStart(2, '0')}`

  // Do not move to future months
  if (delta > 0 && next > CURRENT_MONTH) {
    return ym
  }

  return next
}

const formatMonthLabel = (ym) => {
  const [year, month] = ym.split('-').map(Number)
  const date = new Date(year, month - 1, 1)
  return date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' })
}

// ===== STATE =====
const accounts = ref([])
const expenses = ref([])
const bills = ref([])

const activeSection = ref('home') // 'home' | 'accounts' | 'expenses' | 'providers' | 'profile'
const viewImage = ref(null)

const todayMonth = getCurrentMonth()
const expenseMonth = ref(todayMonth)
const invoiceMonth = ref(todayMonth)

const showAddBalanceModal = ref(false)
const addBalanceForm = ref({
  amount: '',
  accountId: '',
})

// ===== LOAD FROM API =====
const loadAccounts = async () => {
  accounts.value = await apiGet('/accounts')
}

const loadExpenses = async () => {
  expenses.value = await apiGet('/expenses')
}

const loadBills = async () => {
  bills.value = await apiGet('/bills')
}

onMounted(async () => {
  await Promise.all([loadAccounts(), loadExpenses(), loadBills()])
})

// ===== DERIVED: HOME SUMMARY =====
const totalBalance = computed(() =>
  accounts.value.reduce((sum, acc) => sum + Number(acc.balance || 0), 0),
)

const currentMonth = todayMonth

const expensesThisMonth = computed(() =>
  expenses.value.filter((exp) => (exp.date || '').startsWith(currentMonth)),
)

const totalExpensesThisMonth = computed(() =>
  expensesThisMonth.value.reduce(
    (sum, exp) => sum + Number(exp.amount || 0),
    0,
  ),
)

const billsThisMonth = computed(() =>
  bills.value.filter((b) => (b.date || '').startsWith(currentMonth)),
)

const pendingInvoicesThisMonth = computed(() =>
  billsThisMonth.value
    .filter((b) => b.status === 'pending')
    .reduce((sum, b) => sum + Number(b.amount || 0), 0),
)

const paidInvoicesThisMonth = computed(() =>
  billsThisMonth.value
    .filter((b) => b.status === 'paid')
    .reduce((sum, b) => sum + Number(b.amount || 0), 0),
)

// ===== EXPENSES (SELECTED MONTH) =====
const expensesForSelectedMonth = computed(() =>
  expenses.value.filter((exp) =>
    (exp.date || '').startsWith(expenseMonth.value),
  ),
)

const totalExpensesForSelectedMonth = computed(() =>
  expensesForSelectedMonth.value.reduce(
    (sum, exp) => sum + Number(exp.amount || 0),
    0,
  ),
)

// ===== BILLS (SELECTED MONTH) =====
const billsForSelectedMonth = computed(() =>
  bills.value.filter((b) => (b.date || '').startsWith(invoiceMonth.value)),
)

const pendingInvoicesForSelectedMonth = computed(() =>
  billsForSelectedMonth.value
    .filter((b) => b.status === 'pending')
    .reduce((sum, b) => sum + Number(b.amount || 0), 0),
)

const paidInvoicesForSelectedMonth = computed(() =>
  billsForSelectedMonth.value
    .filter((b) => b.status === 'paid')
    .reduce((sum, b) => sum + Number(b.amount || 0), 0),
)

// ===== HELPERS =====
const handleAddDeposit = async ({ accountId, amount }) => {
  const parsedAmount = parseFloat(amount)
  if (isNaN(parsedAmount) || parsedAmount <= 0) {
    alert('Please enter a valid amount.')
    return
  }

  await apiPost('/accounts/deposit', {
    account_id: accountId,
    amount: parsedAmount,
  })

  await loadAccounts()
}

const deleteAccount = async (account) => {
  if (!window.confirm(`Delete account "${account.name}"?`)) return

  // Backend must have: DELETE /api/accounts/{id}
  await apiDelete(`/accounts/${account.id}`)
  await loadAccounts()
}

const createAccount = async ({ name, initialBalance }) => {
  const payload = {
    name,
  }

  const parsed = parseFloat(initialBalance)
  if (!isNaN(parsed) && parsed > 0) {
    payload.balance = parsed
  }

  await apiPost('/accounts', payload)
  await loadAccounts()
}

const addExpense = async (form) => {
  if (!form.description || !form.amount) {
    alert('Description and amount are required.')
    return
  }

  const payload = {
    description: form.description,
    amount: parseFloat(form.amount),
    date: form.date,
    category: form.category || null,
    account_id: Number(form.accountId),
    is_ads: !!form.isAds,
  }

  await apiPost('/expenses', payload)
  await loadExpenses()
}

const deleteExpense = async (expense) => {
  await apiDelete(`/expenses/${expense.id}`)
  await loadExpenses()
}

const addBill = async (form) => {
  if (!form.description || !form.amount) {
    alert('Description and amount are required.')
    return
  }

  const payload = {
    description: form.description,
    amount: parseFloat(form.amount),
    date: form.date,
    status: form.status,
    account_id: Number(form.accountId),
    image: form.image || null,
    is_monthly: !!form.isMonthly,
  }

  await apiPost('/bills', payload)
  await loadBills()
}

const deleteBill = async (bill) => {
  await apiDelete(`/bills/${bill.id}`)
  await loadBills()
}

const toggleBillStatus = async (bill) => {
  const newStatus = bill.status === 'paid' ? 'pending' : 'paid'
  await apiPatch(`/bills/${bill.id}/status`, { status: newStatus })
  await loadBills()
}

const openAddBalanceModal = () => {
  addBalanceForm.value = {
    amount: '',
    accountId:
      accounts.value[0]?.id != null
        ? String(accounts.value[0].id)
        : '',
  }
  showAddBalanceModal.value = true
}

const submitAddBalance = async () => {
  const amount = parseFloat(addBalanceForm.value.amount)
  if (isNaN(amount) || amount <= 0) {
    alert('Please enter a valid amount.')
    return
  }
  await handleAddDeposit({
    accountId: Number(addBalanceForm.value.accountId),
    amount: amount,
  })
  showAddBalanceModal.value = false
}

// Month handlers
const handleExpensePrevMonth = () => {
  expenseMonth.value = shiftMonth(expenseMonth.value, -1)
}

const handleExpenseNextMonth = () => {
  expenseMonth.value = shiftMonth(expenseMonth.value, 1)
}

const handleInvoicePrevMonth = () => {
  invoiceMonth.value = shiftMonth(invoiceMonth.value, -1)
}

const handleInvoiceNextMonth = () => {
  invoiceMonth.value = shiftMonth(invoiceMonth.value, 1)
}

const navItems = [
  { id: 'home', label: 'Home' },
  { id: 'accounts', label: 'Accounts' },
  { id: 'expenses', label: 'Expenses' },
  { id: 'providers', label: 'Provider Invoices' },
]
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 p-4 md:p-6">
    <div class="max-w-7xl mx-auto">
      <div class="flex items-center justify-between mb-6 md:mb-8">
        <h1 class="text-3xl md:text-4xl font-bold text-gray-800">
          Business Finance Manager
        </h1>
        <button
          type="button"
          class="h-10 w-10 rounded-full flex items-center justify-center border border-gray-300 hover:bg-gray-100 transition"
          :class="activeSection === 'profile'
            ? 'bg-blue-600 text-white border-blue-600'
            : 'text-gray-700'"
          @click="activeSection = 'profile'"
        >
          <User2 :size="20" />
        </button>
      </div>

      <nav class="bg-white rounded-lg shadow mb-6 px-4 py-2 flex flex-wrap gap-2">
        <button
          v-for="item in navItems"
          :key="item.id"
          type="button"
          class="px-3 py-2 rounded font-semibold text-sm md:text-base"
          :class="activeSection === item.id
            ? 'bg-blue-600 text-white'
            : 'text-gray-700 hover:bg-gray-100'"
          @click="activeSection = item.id"
        >
          {{ item.label }}
        </button>
      </nav>

      <HomePage
        v-if="activeSection === 'home'"
        :currency="CURRENCY"
        :total-balance="totalBalance"
        :current-month-label="formatMonthLabel(currentMonth)"
        :total-expenses-this-month="totalExpensesThisMonth"
        :pending-invoices-this-month="pendingInvoicesThisMonth"
        :paid-invoices-this-month="paidInvoicesThisMonth"
        @open-add-balance="openAddBalanceModal"
      />

      <AccountsPage
        v-else-if="activeSection === 'accounts'"
        :currency="CURRENCY"
        :accounts="accounts"
        @create-account="createAccount"
        @delete-account="deleteAccount"
        @deposit="handleAddDeposit"
      />

      <ExpensesPage
        v-else-if="activeSection === 'expenses'"
        :currency="CURRENCY"
        :accounts="accounts"
        :month-label="formatMonthLabel(expenseMonth)"
        :expenses="expensesForSelectedMonth"
        :total-for-month="totalExpensesForSelectedMonth"
        @prev-month="handleExpensePrevMonth"
        @next-month="handleExpenseNextMonth"
        @add-expense="addExpense"
        @delete-expense="deleteExpense"
      />

      <ProviderInvoicesPage
        v-else-if="activeSection === 'providers'"
        :currency="CURRENCY"
        :accounts="accounts"
        :month-label="formatMonthLabel(invoiceMonth)"
        :bills="billsForSelectedMonth"
        :pending-total="pendingInvoicesForSelectedMonth"
        :paid-total="paidInvoicesForSelectedMonth"
        @prev-month="handleInvoicePrevMonth"
        @next-month="handleInvoiceNextMonth"
        @add-bill="addBill"
        @delete-bill="deleteBill"
        @toggle-status="toggleBillStatus"
        @view-receipt="(img) => (viewImage = img)"
      />

      <ProfilePage v-else-if="activeSection === 'profile'" />

      <div
        v-if="viewImage"
        class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 p-4"
        @click="viewImage = null"
      >
        <div class="relative max-w-4xl max-h-full">
          <button
            type="button"
            class="absolute -top-10 right-0 text-white hover:text-gray-300"
            @click.stop="viewImage = null"
          >
            <X :size="32" />
          </button>
          <img
            :src="viewImage"
            alt="Receipt"
            class="max-w-full max-h-screen object-contain rounded-lg"
            @click.stop
          />
        </div>
      </div>

      <div
        v-if="showAddBalanceModal"
        class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 px-4"
      >
        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md relative">
          <button
            type="button"
            class="absolute right-3 top-3 text-gray-500 hover:text-gray-700"
            @click="showAddBalanceModal = false"
          >
            <X :size="20" />
          </button>
          <h2 class="text-xl font-bold text-gray-800 mb-4">
            Add Balance
          </h2>
          <div class="space-y-4">
            <div>
              <label class="block text-sm text-gray-600 mb-1">
                Amount ({{ CURRENCY }})
              </label>
              <input
                v-model="addBalanceForm.amount"
                type="number"
                class="border rounded px-3 py-2 w-full"
                placeholder="0.00"
              />
            </div>
            <div>
              <label class="block text-sm text-gray-600 mb-1">
                Account
              </label>
              <select
                v-model="addBalanceForm.accountId"
                class="border rounded px-3 py-2 w-full"
              >
                <option
                  v-for="acc in accounts"
                  :key="acc.id"
                  :value="acc.id.toString()"
                >
                  {{ acc.name }}
                </option>
              </select>
            </div>
            <button
              type="button"
              class="w-full bg-blue-600 text-white rounded px-4 py-2 font-semibold hover:bg-blue-700"
              @click="submitAddBalance"
            >
              Add balance
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
