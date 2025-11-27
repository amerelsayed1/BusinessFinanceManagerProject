<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useStore } from 'vuex'
import { useRouter } from 'vue-router'
import {
  Home,
  CreditCard,
  DollarSign,
  FileText,
  User,
  ArrowLeftRight,
  Boxes,
  BarChart3,
  ShoppingCart,
  LogOut,
  Tag,
} from 'lucide-vue-next'

import Dashboard from './views/Dashboard.vue'
import AccountsPage from './views/AccountsPage.vue'
import ExpensesPage from './views/ExpensesPage.vue'
import ProviderInvoicesPage from './views/ProviderInvoicesPage.vue'
import ProfilePage from './views/ProfileSettings.vue'
import AccountTransfersPage from './views/AccountTransfers.vue'
import InventoryPage from './views/Inventory.vue'
import MonthlySalesPage from './views/MonthlySales.vue'
import POSPage from './views/POS.vue'
import CategoriesPage from './views/CategoriesPage.vue'

import accountService from './services/accountService'
import expenseService from './services/expenseService'
import billService from './services/billService'
import expenseCategoryService from './services/expenseCategoryService'
import posOrderService from './services/posOrderService'

const store = useStore()
const router = useRouter()

const isAuthenticated = computed(() => store.getters['auth/isAuthenticated'])

const currentTab = ref('home')
const currency = ref('EGP')

// Data
const accounts = ref([])
const expenses = ref([])
const bills = ref([])
const expenseCategories = ref([])
const posOrders = ref([])

// Date handling
const selectedExpenseMonth = ref(new Date())
const selectedBillMonth = ref(new Date())

const formatMonthKey = (date) =>
    `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`

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
    accounts.value.reduce((sum, acc) => sum + Number(acc.balance || 0), 0),
)

const currentMonthLabel = computed(() => {
  const now = new Date()
  return now.toLocaleDateString('en-US', { year: 'numeric', month: 'long' })
})

const expenseMonthLabel = computed(() =>
    selectedExpenseMonth.value.toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'long',
    }),
)

const billMonthLabel = computed(() =>
    selectedBillMonth.value.toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'long',
    }),
)

const totalExpensesThisMonth = computed(() => {
  const now = new Date()
  const currentYear = now.getFullYear()
  const currentMonth = now.getMonth()

  return expenses.value
      .filter((exp) => {
        const expDate = new Date(exp.date)
        return (
            expDate.getFullYear() === currentYear &&
            expDate.getMonth() === currentMonth
        )
      })
      .reduce((sum, exp) => sum + Number(exp.amount || 0), 0)
})

const defaultExpensesThisMonth = computed(() => {
  const defaultCategoryIds = expenseCategories.value
      .filter((cat) => cat.is_default)
      .map((cat) => cat.id)

  const now = new Date()
  const currentYear = now.getFullYear()
  const currentMonth = now.getMonth()

  return expenses.value
      .filter((exp) => {
        const expDate = new Date(exp.date)
        return (
            expDate.getFullYear() === currentYear &&
            expDate.getMonth() === currentMonth &&
            defaultCategoryIds.includes(exp.category_id || exp.categoryId)
        )
      })
      .reduce((sum, exp) => sum + Number(exp.amount || 0), 0)
})

const filteredExpenses = computed(() => {
  const year = selectedExpenseMonth.value.getFullYear()
  const month = selectedExpenseMonth.value.getMonth()

  return expenses.value.filter((exp) => {
    const expDate = new Date(exp.date)
    return expDate.getFullYear() === year && expDate.getMonth() === month
  })
})

const totalForExpenseMonth = computed(() =>
    filteredExpenses.value.reduce((sum, exp) => sum + Number(exp.amount || 0), 0),
)

const filteredBills = computed(() => {
  const year = selectedBillMonth.value.getFullYear()
  const month = selectedBillMonth.value.getMonth()

  return bills.value.filter((bill) => {
    const billDate = new Date(bill.date)
    return billDate.getFullYear() === year && billDate.getMonth() === month
  })
})

const pendingInvoicesThisMonth = computed(() => {
  const now = new Date()
  const currentYear = now.getFullYear()
  const currentMonth = now.getMonth()

  return bills.value
      .filter((bill) => {
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

  return posOrders.value
      .filter((order) => {
        const orderDate = new Date(order.date)
        return (
            orderDate.getFullYear() === currentYear &&
            orderDate.getMonth() === currentMonth
        )
      })
      .reduce((sum, order) => sum + Number(order.total_amount || 0), 0)
})

const pendingTotalForBillMonth = computed(() =>
    filteredBills.value
        .filter((bill) => bill.status === 'pending')
        .reduce((sum, bill) => sum + Number(bill.amount || 0), 0),
)

const paidTotalForBillMonth = computed(() =>
    filteredBills.value
        .filter((bill) => bill.status === 'paid')
        .reduce((sum, bill) => sum + Number(bill.amount || 0), 0),
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

const loadExpenses = async (month = selectedExpenseMonth.value) => {
  try {
    const monthKey = month ? formatMonthKey(month) : null
    const response = await expenseService.getAll(monthKey)
    expenses.value = response.data
  } catch (error) {
    console.error('Error loading expenses:', error)
  }
}

const loadBills = async () => {
  try {
    const response = await billService.getAll()
    bills.value = response.data
  } catch (error) {
    console.error('Error loading bills:', error)
  }
}

const loadExpenseCategories = async () => {
  try {
    const response = await expenseCategoryService.getAll()
    expenseCategories.value = response.data
  } catch (error) {
    console.error('Error loading expense categories:', error)
  }
}

const loadPosOrders = async () => {
  try {
    const now = new Date()
    const monthKey = formatMonthKey(now)
    const [year, month] = monthKey.split('-').map(Number)
    const startDate = `${monthKey}-01`
    const endDate = `${monthKey}-${String(new Date(year, month, 0).getDate()).padStart(2, '0')}`

    const response = await posOrderService.getAll({ start_date: startDate, end_date: endDate })
    posOrders.value = response.data
  } catch (error) {
    console.error('Error loading POS orders:', error)
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

const goToAccounts = () => {
  currentTab.value = 'accounts'
}

const goToTransfers = () => {
  currentTab.value = 'transfers'
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
    await accountService.deposit(
        selectedAccountForDeposit.value,
        Number(depositAmount.value),
    )
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
      categoryId: data.categoryId ?? data.category ?? null,
      accountId: Number(data.accountId),
    })
    await loadExpenses()
    await loadAccounts()
    console.log('Expense added successfully!')
  } catch (error) {
    console.error('Error adding expense:', error)
  }
}

const updateExpense = async (expenseId, data) => {
  if (!expenseId) return

  try {
    await expenseService.update(expenseId, {
      description: data.description,
      amount: Number(data.amount),
      date: data.date,
      categoryId: data.categoryId ?? null,
      accountId: Number(data.accountId),
    })
    await loadExpenses()
    await loadAccounts()
    console.log('Expense updated successfully!')
  } catch (error) {
    console.error('Error updating expense:', error)
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

const updateBill = async (id, data) => {
  if (!id) return

  try {
    await billService.update(id, {
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
    console.log('Invoice updated successfully!')
  } catch (error) {
    console.error('Error updating bill:', error)
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

const handleLogout = async () => {
  try {
    await store.dispatch('auth/logout')
  } finally {
    router.push({ name: 'Login' })
  }
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

const initData = async () => {
  await Promise.all([
    loadAccounts(),
    loadExpenses(selectedExpenseMonth.value),
    loadBills(),
    loadExpenseCategories(),
    loadPosOrders(),
  ])
}

onMounted(() => {
  if (isAuthenticated.value) {
    initData()
  }
})

watch(isAuthenticated, (loggedIn) => {
  if (loggedIn) {
    initData()
  }
})

watch(selectedExpenseMonth, (month) => {
  loadExpenses(month)
})
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
    <!-- Guest area: Login / Register -->
    <div
        v-if="!isAuthenticated"
        class="min-h-screen flex items-center justify-center"
    >
      <router-view />
    </div>

    <!-- Authenticated area: Sidebar layout -->
    <div
        v-else
        class="min-h-screen flex"
    >
      <!-- Sidebar -->
      <aside
          class="w-64 bg-white border-r border-gray-200 flex flex-col"
      >
        <div
            class="h-16 flex items-center px-4 border-b border-gray-200"
        >
          <span class="text-lg font-semibold text-gray-900">
            Business Finance
          </span>
        </div>

        <nav class="flex-1 overflow-y-auto px-2 py-4 space-y-1">
          <button
              @click="currentTab = 'home'"
              :class="[
              'w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors',
              currentTab === 'home'
                ? 'bg-blue-50 text-blue-700'
                : 'text-gray-700 hover:bg-gray-50 hover:text-blue-700',
            ]"
          >
            <Home class="w-4 h-4" />
            <span>Dashboard</span>
          </button>

          <button
              @click="currentTab = 'accounts'"
              :class="[
              'w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors',
              currentTab === 'accounts'
                ? 'bg-blue-50 text-blue-700'
                : 'text-gray-700 hover:bg-gray-50 hover:text-blue-700',
            ]"
          >
            <CreditCard class="w-4 h-4" />
            <span>Accounts</span>
          </button>

          <button
              @click="currentTab = 'expenses'"
              :class="[
              'w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors',
              currentTab === 'expenses'
                ? 'bg-blue-50 text-blue-700'
                : 'text-gray-700 hover:bg-gray-50 hover:text-blue-700',
            ]"
          >
            <DollarSign class="w-4 h-4" />
            <span>Expenses</span>
          </button>

          <button
              @click="currentTab = 'bills'"
              :class="[
              'w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors',
              currentTab === 'bills'
                ? 'bg-blue-50 text-blue-700'
                : 'text-gray-700 hover:bg-gray-50 hover:text-blue-700',
            ]"
          >
            <FileText class="w-4 h-4" />
            <span>Invoices</span>
          </button>

          <button
              @click="currentTab = 'transfers'"
              :class="[
              'w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors',
              currentTab === 'transfers'
                ? 'bg-blue-50 text-blue-700'
                : 'text-gray-700 hover:bg-gray-50 hover:text-blue-700',
            ]"
          >
            <ArrowLeftRight class="w-4 h-4" />
            <span>Transfers</span>
          </button>

          <button
              @click="currentTab = 'inventory'"
              :class="[
              'w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors',
              currentTab === 'inventory'
                ? 'bg-blue-50 text-blue-700'
                : 'text-gray-700 hover:bg-gray-50 hover:text-blue-700',
            ]"
          >
            <Boxes class="w-4 h-4" />
            <span>Inventory</span>
          </button>

          <button
              @click="currentTab = 'categories'"
              :class="[
              'w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors',
              currentTab === 'categories'
                ? 'bg-blue-50 text-blue-700'
                : 'text-gray-700 hover:bg-gray-50 hover:text-blue-700',
            ]"
          >
            <Tag class="w-4 h-4" />
            <span>Categories</span>
          </button>

          <button
              @click="currentTab = 'monthlySales'"
              :class="[
              'w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors',
              currentTab === 'monthlySales'
                ? 'bg-blue-50 text-blue-700'
                : 'text-gray-700 hover:bg-gray-50 hover:text-blue-700',
            ]"
          >
            <BarChart3 class="w-4 h-4" />
            <span>Monthly Sales</span>
          </button>

          <button
              @click="currentTab = 'pos'"
              :class="[
              'w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors',
              currentTab === 'pos'
                ? 'bg-blue-50 text-blue-700'
                : 'text-gray-700 hover:bg-gray-50 hover:text-blue-700',
            ]"
          >
            <ShoppingCart class="w-4 h-4" />
            <span>POS</span>
          </button>

          <button
              @click="handleLogout"
              class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors text-gray-700 hover:bg-red-50 hover:text-red-600"
          >
            <LogOut class="w-4 h-4" />
            <span>Logout</span>
          </button>
        </nav>

        <div class="border-t border-gray-200 p-3">
          <button
              @click="currentTab = 'profile'"
              :class="[
              'w-full flex items-center gap-2 px-2 py-2 rounded-lg text-sm transition-colors',
              currentTab === 'profile'
                ? 'bg-blue-50 text-blue-700'
                : 'text-gray-700 hover:bg-gray-50 hover:text-blue-700',
            ]"
          >
            <User class="w-4 h-4" />
            <span>Profile</span>
          </button>
        </div>
      </aside>

      <!-- Main content area -->
      <div class="flex-1 flex flex-col">
        <!-- Top bar -->
        <header
            class="h-16 bg-white/80 backdrop-blur border-b border-gray-200 flex items-center justify-between px-4 md:px-6"
        >
          <div class="flex flex-col">
            <span class="text-xs text-gray-500">Overview</span>
            <span class="text-sm font-semibold text-gray-900 capitalize">
              {{ currentTab }}
            </span>
          </div>

          <div class="flex items-center gap-3">
            <span class="hidden sm:inline text-xs text-gray-500">
              Default currency: {{ currency }}
            </span>
            <div
                class="h-8 w-8 rounded-full bg-indigo-500 text-white flex items-center justify-center text-xs font-semibold"
            >
              BF
            </div>
          </div>
        </header>

        <!-- Page content -->
        <main class="flex-1 max-w-7xl w-full mx-auto px-4 pb-8 pt-4">
          <Dashboard
              v-if="currentTab === 'home'"
              :currency="currency"
              :total-balance="totalBalance"
              :current-month-label="currentMonthLabel"
              :total-expenses-this-month="totalExpensesThisMonth"
              :default-expenses-this-month="defaultExpensesThisMonth"
              :pending-invoices-this-month="pendingInvoicesThisMonth"
              :paid-invoices-this-month="paidInvoicesThisMonth"
              @open-add-balance="openAddBalanceModal"
              @open-add-account="goToAccounts"
              @open-transfer="goToTransfers"
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
              :categories="expenseCategories"
              :month-label="expenseMonthLabel"
              :expenses="filteredExpenses"
              :total-for-month="totalForExpenseMonth"
              @prev-month="prevExpenseMonth"
              @next-month="nextExpenseMonth"
              @add-expense="addExpense"
              @update-expense="updateExpense"
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
              @update-bill="updateBill"
              @delete-bill="deleteBill"
              @toggle-status="toggleBillStatus"
              @view-receipt="viewReceipt"
          />

          <AccountTransfersPage v-if="currentTab === 'transfers'" />

          <InventoryPage v-if="currentTab === 'inventory'" />

          <CategoriesPage v-if="currentTab === 'categories'" />

          <MonthlySalesPage v-if="currentTab === 'monthlySales'" />

          <POSPage v-if="currentTab === 'pos'" />

          <ProfilePage v-if="currentTab === 'profile'" />
        </main>

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
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Select Account
                </label>
                <select
                    v-model="selectedAccountForDeposit"
                    class="w-full border rounded px-4 py-2"
                >
                  <option
                      v-for="acc in accounts"
                      :key="acc.id"
                      :value="acc.id"
                  >
                    {{ acc.name }} (Current:
                    {{ Number(acc.balance || 0).toFixed(2) }}
                    {{ currency }})
                  </option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Amount
                </label>
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
          <div
              class="bg-white rounded-lg p-4 max-w-2xl w-full max-h-[90vh] overflow-auto"
          >
            <div class="flex justify-between items-center mb-4">
              <h3 class="text-xl font-bold">Receipt</h3>
              <button
                  @click="showReceiptModal = false"
                  class="text-gray-500 hover:text-gray-700"
              >
                ✕
              </button>
            </div>
            <img
                :src="currentReceipt"
                alt="Receipt"
                class="w-full"
            />
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
    </div>
  </div>
</template>
