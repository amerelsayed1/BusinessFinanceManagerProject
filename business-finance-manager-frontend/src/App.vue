<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useStore } from 'vuex'
import { useRouter } from 'vue-router'
import { LogOut, User } from 'lucide-vue-next'

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

import { useTabs } from './composables/useTabs'
import { useAccountsManager } from './composables/useAccountsManager'
import { useExpensesManager } from './composables/useExpensesManager'
import { useBillsManager } from './composables/useBillsManager'

const store = useStore()
const router = useRouter()
const TAB_STORAGE_KEY = 'bfm-current-tab'
const ACCOUNTING_SECTION_STORAGE_KEY = 'bfm-accounting-open'

const isAuthenticated = computed(() => store.getters['auth/isAuthenticated'])
const currency = ref('EGP')

// Tabs / layout
const {
  TABS,
  mainTabs,
  currentTab,
  currentTabLabel,
  tabButtonClasses,
  goToAccounts,
  goToTransfers,
  goToProfile,
} = useTabs()

const dashboardTab = computed(() =>
  mainTabs.find((tab) => tab.id === TABS.HOME),
)

const accountingTabs = computed(() =>
  mainTabs.filter(
      (tab) => tab.id === TABS.ACCOUNTS || tab.id === TABS.TRANSFERS,
  ),
)

const otherTabs = computed(() =>
  mainTabs.filter(
      (tab) =>
          tab.id !== TABS.HOME &&
          tab.id !== TABS.ACCOUNTS &&
          tab.id !== TABS.TRANSFERS,
  ),
)

const isAccountingOpen = ref(true)

const restoreTabFromStorage = () => {
  if (typeof localStorage === 'undefined') return

  const savedTab = localStorage.getItem(TAB_STORAGE_KEY)
  const isValidTab =
      savedTab &&
      (mainTabs.some((tab) => tab.id === savedTab) || savedTab === TABS.PROFILE)

  if (isValidTab) {
    currentTab.value = savedTab
  }
}

const restoreAccountingState = () => {
  if (typeof localStorage === 'undefined') return

  const saved = localStorage.getItem(ACCOUNTING_SECTION_STORAGE_KEY)

  if (saved === 'true' || saved === 'false') {
    isAccountingOpen.value = saved === 'true'
  }
}

// Accounts
const {
  accounts,
  loadingAccounts,
  showAddBalanceModal,
  selectedAccountForDeposit,
  depositAmount,
  totalBalance,
  loadAccounts,
  createAccount,
  deleteAccount,
  handleDeposit,
  openAddBalanceModal,
  closeAddBalanceModal,
  submitAddBalance,
  toNumber,
} = useAccountsManager()

// Expenses
const {
  expenseMonthLabel,
  filteredExpenses,
  totalForExpenseMonth,
  totalExpensesThisMonth,
  loadExpenses,
  addExpense,
  deleteExpense,
  prevExpenseMonth,
  nextExpenseMonth,
  loadingExpenses,
} = useExpensesManager()

// Bills
const {
  billMonthLabel,
  filteredBills,
  pendingTotalForBillMonth,
  paidTotalForBillMonth,
  pendingInvoicesThisMonth,
  paidInvoicesThisMonth,
  loadBills,
  addBill,
  deleteBill,
  toggleBillStatus,
  prevBillMonth,
  nextBillMonth,
  showReceiptModal,
  currentReceipt,
  viewReceipt,
  closeReceipt,
  loadingBills,
} = useBillsManager()

const currentMonthLabel = computed(() => {
  const now = new Date()
  return now.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
  })
})

const loading = computed(
    () =>
        loadingAccounts.value ||
        loadingExpenses.value ||
        loadingBills.value,
)

const initData = async () => {
  await Promise.all([loadAccounts(), loadExpenses(), loadBills()])
}

onMounted(() => {
  restoreTabFromStorage()
  restoreAccountingState()

  if (isAuthenticated.value) {
    initData()
  }
})

watch(isAuthenticated, (loggedIn) => {
  if (loggedIn) {
    initData()
  }
})

watch(currentTab, (tab) => {
  if (typeof localStorage === 'undefined') return

  localStorage.setItem(TAB_STORAGE_KEY, tab)
})

watch(isAccountingOpen, (isOpen) => {
  if (typeof localStorage === 'undefined') return

  localStorage.setItem(ACCOUNTING_SECTION_STORAGE_KEY, String(isOpen))
})

const handleLogout = async () => {
  try {
    await store.dispatch('auth/logout')
  } finally {
    router.push({ name: 'Login' })
  }
}
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
    <!-- Guest area -->
    <div
        v-if="!isAuthenticated"
        class="min-h-screen flex items-center justify-center"
    >
      <router-view />
    </div>

    <!-- Authenticated area -->
    <div
        v-else
        class="min-h-screen flex"
    >
      <!-- Sidebar -->
      <aside class="w-64 bg-white border-r border-gray-200 flex flex-col">
        <div class="h-16 flex items-center px-4 border-b border-gray-200">
          <span class="text-lg font-semibold text-gray-900">
            Business Finance
          </span>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-4 text-purple-700">
          <div class="space-y-1">
            <button
                v-if="dashboardTab"
                @click="currentTab = dashboardTab.id"
                :class="tabButtonClasses(dashboardTab.id)"
            >
              <component
                  :is="dashboardTab.icon"
                  class="w-4 h-4"
              />
              <span>{{ dashboardTab.label }}</span>
            </button>
          </div>

          <div class="space-y-2">
            <button
                type="button"
                class="px-3 w-full flex items-center justify-between text-xs font-semibold uppercase tracking-wide text-purple-700"
                @click="isAccountingOpen = !isAccountingOpen"
            >
              <span>Accounting</span>
              <svg
                  xmlns="http://www.w3.org/2000/svg"
                  class="h-3 w-3 transition-transform duration-200"
                  :class="{ 'rotate-90': isAccountingOpen }"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </button>

            <div
                v-show="isAccountingOpen"
                class="bg-gray-50 rounded-xl p-2 space-y-1 shadow-inner"
            >
              <button
                  v-for="tab in accountingTabs"
                  :key="tab.id"
                  @click="currentTab = tab.id"
                  :class="[tabButtonClasses(tab.id), 'pl-6']"
              >
                <component
                    :is="tab.icon"
                    class="w-4 h-4"
                />
                <span>{{ tab.label }}</span>
              </button>
            </div>
          </div>

          <div class="space-y-1">
            <button
                v-for="tab in otherTabs"
                :key="tab.id"
                @click="currentTab = tab.id"
                :class="tabButtonClasses(tab.id)"
            >
              <component
                  :is="tab.icon"
                  class="w-4 h-4"
              />
              <span>{{ tab.label }}</span>
            </button>
          </div>

          <div class="pt-2 border-t border-gray-200">
            <button
                @click="handleLogout"
                class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors text-gray-700 hover:bg-red-50 hover:text-red-600"
            >
              <LogOut class="w-4 h-4" />
              <span>Logout</span>
            </button>
          </div>
        </nav>

        <div class="border-t border-gray-200 p-3">
          <button
              @click="goToProfile"
              :class="tabButtonClasses(TABS.PROFILE)"
          >
            <User class="w-4 h-4" />
            <span>Profile</span>
          </button>
        </div>
      </aside>

      <!-- Main content -->
      <div class="flex-1 flex flex-col">
        <!-- Top bar -->
        <header
            class="h-16 bg-white/80 backdrop-blur border-b border-gray-200 flex items-center justify-between px-4 md:px-6"
        >
          <div class="flex flex-col">
            <span class="text-xs text-gray-500">Overview</span>
            <span class="text-sm font-semibold text-gray-900">
              {{ currentTabLabel }}
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

        <!-- Pages -->
        <main class="flex-1 max-w-7xl w-full mx-auto px-4 pb-8 pt-4">
          <Dashboard
              v-if="currentTab === TABS.HOME"
              :currency="currency"
              :total-balance="totalBalance"
              :current-month-label="currentMonthLabel"
              :total-expenses-this-month="totalExpensesThisMonth"
              :pending-invoices-this-month="pendingInvoicesThisMonth"
              :paid-invoices-this-month="paidInvoicesThisMonth"
              @open-add-balance="openAddBalanceModal"
              @open-add-account="goToAccounts"
              @open-transfer="goToTransfers"
          />

          <AccountsPage
              v-if="currentTab === TABS.ACCOUNTS"
              :currency="currency"
              :accounts="accounts"
              @create-account="createAccount"
              @delete-account="deleteAccount"
              @deposit="handleDeposit"
          />

          <ExpensesPage
              v-if="currentTab === TABS.EXPENSES"
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
              v-if="currentTab === TABS.BILLS"
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

          <AccountTransfersPage v-if="currentTab === TABS.TRANSFERS" />

          <InventoryPage v-if="currentTab === TABS.INVENTORY" />

          <CategoriesPage v-if="currentTab === TABS.CATEGORIES" />

          <MonthlySalesPage v-if="currentTab === TABS.MONTHLY_SALES" />

          <POSPage v-if="currentTab === TABS.POS" />

          <ProfilePage v-if="currentTab === TABS.PROFILE" />
        </main>

        <!-- Add Balance Modal -->
        <div
            v-if="showAddBalanceModal"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
            @click.self="closeAddBalanceModal"
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
                    {{ toNumber(acc.balance || 0).toFixed(2) }}
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
                    @click="closeAddBalanceModal"
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
            @click.self="closeReceipt"
        >
          <div
              class="bg-white rounded-lg p-4 max-w-2xl w-full max-h-[90vh] overflow-auto"
          >
            <div class="flex justify-between items-center mb-4">
              <h3 class="text-xl font-bold">Receipt</h3>
              <button
                  @click="closeReceipt"
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
