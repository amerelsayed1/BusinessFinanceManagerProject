<script setup>
import { ref, computed, onMounted } from 'vue'
import { Home, CreditCard, DollarSign, FileText, User } from 'lucide-vue-next'
import HomePage from './components/HomePage.vue'
import AccountsPage from './components/AccountsPage.vue'
import ExpensesPage from './components/ExpensesPage.vue'
import ProviderInvoicesPage from './components/ProviderInvoicesPage.vue'
import ProfilePage from './components/ProfilePage.vue'

// Import API services
import accountService from './services/accountService'
import expenseService from './services/expenseService'
import billService from './services/billService'

const currentTab = ref('home')
const currency = ref('EGP')

// Data
const accounts = ref([])
const expenses = ref([])
const bills = ref([])

// Date handling
const selectedExpenseMonth = ref(new Date())
const selectedBillMonth = ref(new Date())

// Loading states
const loading = ref(false)

// Modal states
const showAddBalanceModal = ref(false)
const selectedAccountForDeposit = ref(null)
const depositAmount = ref('')
const showReceiptModal = ref(false)
const currentReceipt = ref(null)

// Computed values
const totalBalance = computed(() =>
    accounts.value.reduce((sum, acc) => sum + Number(acc.balance || 0), 0)
)

const currentMonthLabel = computed(() => {
  const now = new Date()
  return now.toLocaleDateString('en-US', { year: 'numeric', month: 'long' })
})

const expenseMonthLabel = computed(() =>
    selectedExpenseMonth.value.toLocaleDateString('en-US', { year: 'numeric', month: 'long' })
)

const billMonthLabel = computed(() =>
    selectedBillMonth.value.toLocaleDateString('en-US', { year: 'numeric', month: 'long' })
)

const totalExpensesThisMonth = computed(() => {
  const now = new Date()
  const currentYear = now.getFullYear()
  const currentMonth = now.getMonth()

  return expenses.value
      .filter(exp => {
        const expDate = new Date(exp.date)
        return expDate.getFullYear() === currentYear && expDate.getMonth() === currentMonth
      })
      .reduce((sum, exp) => sum + Number(exp.amount || 0), 0)
})

const filteredExpenses = computed(() => {
  const year = selectedExpenseMonth.value.getFullYear()
  const month = selectedExpenseMonth.value.getMonth()

  return expenses.value.filter(exp => {
    const expDate = new Date(exp.date)
    return expDate.getFullYear() === year && expDate.getMonth() === month
  })
})

const totalForExpenseMonth = computed(() =>
    filteredExpenses.value.reduce((sum, exp) => sum + Number(exp.amount || 0), 0)
)

const filteredBills = computed(() => {
  const year = selectedBillMonth.value.getFullYear()
  const month = selectedBillMonth.value.getMonth()

  return bills.value.filter(bill => {
    const billDate = new Date(bill.date)
    return billDate.getFullYear() === year && billDate.getMonth() === month
  })
})

const pendingInvoicesThisMonth = computed(() => {
  const now = new Date()
  const currentYear = now.getFullYear()
  const currentMonth = now.getMonth()

  return bills.value
      .filter(bill => {
        const billDate = new Date(bill.date)
        return (
            billDate.getFullYear() === currentYear &&
            billDate.getMonth() === currentMonth &&
            bill.status === 'pending'
        )
      })
      .reduce((sum, bill) => sum + Number(bill.amount || 0), 0)
})

const paidInvoicesThisMonth = computed(() => {
  const now = new Date()
  const currentYear = now.getFullYear()
  const currentMonth = now.getMonth()

  return bills.value
      .filter(bill => {
        const billDate = new Date(bill.date)
        return (
            billDate.getFullYear() === currentYear &&
            billDate.getMonth() === currentMonth &&
            bill.status === 'paid'
        )
      })
      .reduce((sum, bill) => sum + Number(bill.amount || 0), 0)
})

const pendingTotalForBillMonth = computed(() =>
    filteredBills.value
        .filter(bill => bill.status === 'pending')
        .reduce((sum, bill) => sum + Number(bill.amount || 0), 0)
)

const paidTotalForBillMonth = computed(() =>
    filteredBills.value
        .filter(bill => bill.status === 'paid')
        .reduce((sum, bill) => sum + Number(bill.amount || 0), 0)
)

// API Functions
const loadAccounts = async () => {
  try {
    loading.value = true
    const response = await accountService.getAll()
    accounts.value = response.data
  } catch (error) {
    console.error('Error loading accounts:', error)
  } finally {
    loading.value = false
  }
}

const loadExpenses = async (month = null) => {
  try {
    const monthStr = month
        ? `${month.getFullYear()}-${String(month.getMonth() + 1).padStart(2, '0')}`
        : null
    const response = await expenseService.getAll(monthStr)
    expenses.value = response.data
  } catch (error) {
    console.error('Error loading expenses:', error)
  }
}

const loadBills = async (month = null) => {
  try {
    const response = await billService.getAll()
    bills.value = response.data
  } catch (error) {
    console.error('Error loading bills:', error)
  }
}

// Account operations
const createAccount = async (data) => {
  try {
    await accountService.create({
      name: data.name,
      balance: Number(data.initialBalance) || 0,
    })
    await loadAccounts()
    console.log('Account created successfully!')
  } catch (error) {
    console.error('Error creating account:', error)
  }
}

const deleteAccount = async (account) => {
  if (!confirm(`Delete account "${account.name}"?`)) return

  try {
    await accountService.delete(account.id)
    await loadAccounts()
    console.log('Account deleted successfully!')
  } catch (error) {
    console.error('Error deleting account:', error)
  }
}

const handleDeposit = async (data) => {
  try {
    await accountService.deposit(data.accountId, Number(data.amount))
    await loadAccounts()
    console.log('Deposit successful!')
  } catch (error) {
    console.error('Error depositing:', error)
  }
}

const openAddBalanceModal = () => {
  if (accounts.value.length === 0) {
    alert('Please create an account first')
    return
  }
  selectedAccountForDeposit.value = accounts.value[0].id
  depositAmount.value = ''
  showAddBalanceModal.value = true
}

const submitAddBalance = async () => {
  if (!selectedAccountForDeposit.value || !depositAmount.value) {
    alert('Please select account and enter amount')
    return
  }

  try {
    await accountService.deposit(selectedAccountForDeposit.value, Number(depositAmount.value))
    await loadAccounts()
    showAddBalanceModal.value = false
    console.log('Balance added successfully!')
  } catch (error) {
    console.error('Error adding balance:', error)
  }
}

// Expense operations
const addExpense = async (data) => {
  if (!data.description || !data.amount || !data.accountId) {
    alert('Please fill in all required fields')
    return
  }

  try {
    await expenseService.create({
      description: data.description,
      amount: Number(data.amount),
      date: data.date,
      category: data.category || null,
      accountId: Number(data.accountId),
      isAds: data.isAds || false,
    })
    await loadExpenses()
    await loadAccounts()
    console.log('Expense added successfully!')
  } catch (error) {
    console.error('Error adding expense:', error)
  }
}

const deleteExpense = async (expense) => {
  if (!confirm('Delete this expense?')) return

  try {
    await expenseService.delete(expense.id)
    await loadExpenses()
    await loadAccounts()
    console.log('Expense deleted successfully!')
  } catch (error) {
    console.error('Error deleting expense:', error)
  }
}

// Bill operations
const addBill = async (data) => {
  if (!data.description || !data.amount || !data.accountId) {
    alert('Please fill in all required fields')
    return
  }

  try {
    await billService.create({
      description: data.description,
      amount: Number(data.amount),
      date: data.date,
      status: data.status || 'pending',
      accountId: Number(data.accountId),
      image: data.image || null,
      isMonthly: data.isMonthly || false,
    })
    await loadBills()
    await loadAccounts()
    console.log('Invoice added successfully!')
  } catch (error) {
    console.error('Error adding bill:', error)
  }
}

const deleteBill = async (bill) => {
  if (!confirm('Delete this invoice?')) return

  try {
    await billService.delete(bill.id)
    await loadBills()
    await loadAccounts()
    console.log('Invoice deleted successfully!')
  } catch (error) {
    console.error('Error deleting bill:', error)
  }
}

const toggleBillStatus = async (bill) => {
  const newStatus = bill.status === 'paid' ? 'pending' : 'paid'

  if (!confirm(`Mark invoice as ${newStatus}?`)) return

  try {
    await billService.updateStatus(bill.id, newStatus)
    await loadBills()
    await loadAccounts()
    console.log(`Invoice marked as ${newStatus}!`)
  } catch (error) {
    console.error('Error updating bill status:', error)
  }
}

const viewReceipt = (imageData) => {
  currentReceipt.value = imageData
  showReceiptModal.value = true
}

// Month navigation
const prevExpenseMonth = () => {
  const d = new Date(selectedExpenseMonth.value)
  d.setMonth(d.getMonth() - 1)
  selectedExpenseMonth.value = d
}

const nextExpenseMonth = () => {
  const d = new Date(selectedExpenseMonth.value)
  d.setMonth(d.getMonth() + 1)
  selectedExpenseMonth.value = d
}

const prevBillMonth = () => {
  const d = new Date(selectedBillMonth.value)
  d.setMonth(d.getMonth() - 1)
  selectedBillMonth.value = d
}

const nextBillMonth = () => {
  const d = new Date(selectedBillMonth.value)
  d.setMonth(d.getMonth() + 1)
  selectedBillMonth.value = d
}

// Initialize
onMounted(() => {
  loadAccounts()
  loadExpenses()
  loadBills()
})
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
    <nav class="bg-white shadow-md mb-6">
      <div class="max-w-7xl mx-auto px-4 py-4">
        <div class="flex items-center justify-between">
          <h1 class="text-2xl font-bold text-gray-800">Business Finance Manager</h1>
          <div class="flex gap-2 md:gap-4">
            <button
                @click="currentTab = 'home'"
                :class="[
                'flex items-center gap-2 px-3 py-2 rounded-lg transition-colors',
                currentTab === 'home'
                  ? 'bg-blue-600 text-white'
                  : 'text-gray-600 hover:bg-gray-100'
              ]"
            >
              <Home :size="20" />
              <span class="hidden sm:inline">Home</span>
            </button>
            <button
                @click="currentTab = 'accounts'"
                :class="[
                'flex items-center gap-2 px-3 py-2 rounded-lg transition-colors',
                currentTab === 'accounts'
                  ? 'bg-blue-600 text-white'
                  : 'text-gray-600 hover:bg-gray-100'
              ]"
            >
              <CreditCard :size="20" />
              <span class="hidden sm:inline">Accounts</span>
            </button>
            <button
                @click="currentTab = 'expenses'"
                :class="[
                'flex items-center gap-2 px-3 py-2 rounded-lg transition-colors',
                currentTab === 'expenses'
                  ? 'bg-blue-600 text-white'
                  : 'text-gray-600 hover:bg-gray-100'
              ]"
            >
              <DollarSign :size="20" />
              <span class="hidden sm:inline">Expenses</span>
            </button>
            <button
                @click="currentTab = 'bills'"
                :class="[
                'flex items-center gap-2 px-3 py-2 rounded-lg transition-colors',
                currentTab === 'bills'
                  ? 'bg-blue-600 text-white'
                  : 'text-gray-600 hover:bg-gray-100'
              ]"
            >
              <FileText :size="20" />
              <span class="hidden sm:inline">Invoices</span>
            </button>
            <button
                @click="currentTab = 'profile'"
                :class="[
                'flex items-center gap-2 px-3 py-2 rounded-lg transition-colors',
                currentTab === 'profile'
                  ? 'bg-blue-600 text-white'
                  : 'text-gray-600 hover:bg-gray-100'
              ]"
            >
              <User :size="20" />
              <span class="hidden sm:inline">Profile</span>
            </button>
          </div>
        </div>
      </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 pb-8">
      <HomePage
          v-if="currentTab === 'home'"
          :currency="currency"
          :total-balance="totalBalance"
          :current-month-label="currentMonthLabel"
          :total-expenses-this-month="totalExpensesThisMonth"
          :pending-invoices-this-month="pendingInvoicesThisMonth"
          :paid-invoices-this-month="paidInvoicesThisMonth"
          @open-add-balance="openAddBalanceModal"
      />

      <AccountsPage
          v-if="currentTab === 'accounts'"
          :currency="currency"
          :accounts="accounts"
          @create-account="createAccount"
          @delete-account="deleteAccount"
          @deposit="handleDeposit"
      />

      <ExpensesPage
          v-if="currentTab === 'expenses'"
          :currency="currency"
          :accounts="accounts"
          :month-label="expenseMonthLabel"
          :expenses="filteredExpenses"
          :total-for-month="totalForExpenseMonth"
          @prev-month="prevExpenseMonth"
          @next-month="nextExpenseMonth"
          @add-expense="addExpense"
          @delete-expense="deleteExpense"
      />

      <ProviderInvoicesPage
          v-if="currentTab === 'bills'"
          :currency="currency"
          :accounts="accounts"
          :month-label="billMonthLabel"
          :bills="filteredBills"
          :pending-total="pendingTotalForBillMonth"
          :paid-total="paidTotalForBillMonth"
          @prev-month="prevBillMonth"
          @next-month="nextBillMonth"
          @add-bill="addBill"
          @delete-bill="deleteBill"
          @toggle-status="toggleBillStatus"
          @view-receipt="viewReceipt"
      />

      <ProfilePage v-if="currentTab === 'profile'" />
    </div>

    <!-- Add Balance Modal -->
    <div
        v-if="showAddBalanceModal"
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
        @click.self="showAddBalanceModal = false"
    >
      <div class="bg-white rounded-lg p-6 max-w-md w-full">
        <h3 class="text-xl font-bold mb-4">Add Balance to Account</h3>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Select Account</label>
            <select v-model="selectedAccountForDeposit" class="w-full border rounded px-4 py-2">
              <option v-for="acc in accounts" :key="acc.id" :value="acc.id">
                {{ acc.name }} (Current: {{ Number(acc.balance || 0).toFixed(2) }} {{ currency }})
              </option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Amount</label>
            <input
                v-model="depositAmount"
                type="number"
                placeholder="Enter amount"
                class="w-full border rounded px-4 py-2"
            />
          </div>
          <div class="flex gap-2">
            <button
                @click="submitAddBalance"
                class="flex-1 bg-green-600 text-white rounded px-4 py-2 hover:bg-green-700"
            >
              Add Balance
            </button>
            <button
                @click="showAddBalanceModal = false"
                class="flex-1 bg-gray-300 text-gray-700 rounded px-4 py-2 hover:bg-gray-400"
            >
              Cancel
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Receipt Modal -->
    <div
        v-if="showReceiptModal"
        class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 p-4"
        @click.self="showReceiptModal = false"
    >
      <div class="bg-white rounded-lg p-4 max-w-2xl w-full max-h-[90vh] overflow-auto">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-xl font-bold">Receipt</h3>
          <button
              @click="showReceiptModal = false"
              class="text-gray-500 hover:text-gray-700"
          >
            ✕
          </button>
        </div>
        <img :src="currentReceipt" alt="Receipt" class="w-full" />
      </div>
    </div>

    <!-- Loading Overlay -->
    <div
        v-if="loading"
        class="fixed inset-0 bg-black bg-opacity-25 flex items-center justify-center z-50"
    >
      <div class="bg-white rounded-lg p-6">
        <p class="text-lg font-semibold">Loading...</p>
      </div>
    </div>
  </div>
</template>