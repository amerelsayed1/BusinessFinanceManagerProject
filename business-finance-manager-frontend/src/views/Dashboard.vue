<!-- src/views/Dashboard.vue -->
<script setup>
import {computed} from 'vue'
import {
  ArrowUpRight,
  ArrowDownRight,
  Wallet,
  TrendingUp,
  Receipt,
  CreditCard,
} from 'lucide-vue-next'

const today = new Date()
const fallbackMonthLabel = today.toLocaleDateString('en-US', {
  year: 'numeric',
  month: 'long',
})

const props = defineProps({
  currency: {type: String, default: 'EGP'},
  totalBalance: {type: Number, default: 0},
  // can't use fallbackMonthLabel here, so keep it empty by default
  currentMonthLabel: {type: String, default: ''},
  totalExpensesThisMonth: {type: Number, default: 0},
  pendingInvoicesThisMonth: {type: Number, default: 0},
  paidInvoicesThisMonth: {type: Number, default: 0},
})

const emit = defineEmits([
  'open-add-balance',
  'open-add-account',
  'open-transfer',
])

const totalInvoicesAmount = computed(
    () => props.pendingInvoicesThisMonth + props.paidInvoicesThisMonth,
)

const netCashFlow = computed(
    () => props.paidInvoicesThisMonth - props.totalExpensesThisMonth,
)

const conversionRate = computed(() => {
  if (totalInvoicesAmount.value <= 0) return 0
  return (props.paidInvoicesThisMonth / totalInvoicesAmount.value) * 100
})

const isNetPositive = computed(() => netCashFlow.value >= 0)

// ROI = (net cash flow / expenses) * 100
const roiPercent = computed(() => {
  if (props.totalExpensesThisMonth <= 0) return 0
  return (netCashFlow.value / props.totalExpensesThisMonth) * 100
})

const isRoiPositive = computed(() => roiPercent.value >= 0)

// ✅ use this in template instead of props.currentMonthLabel directly
const monthLabel = computed(
    () => props.currentMonthLabel || fallbackMonthLabel,
)

const handleAddBalanceClick = () => {
  emit('open-add-balance')
}

const handleAddAccountClick = () => {
  emit('open-add-account')
}

const handleTransferClick = () => {
  emit('open-transfer')
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header / filters -->
    <div
        class="flex flex-col md:flex-row md:items-center md:justify-between gap-3"
    >
      <div>
        <h2 class="text-2xl font-semibold text-gray-900">Dashboard</h2>
        <p class="text-sm text-gray-500">
          Overview for
          <span class="font-medium text-gray-800">
            {{ monthLabel }}
          </span>
        </p>
      </div>

      <div class="flex flex-wrap gap-2">
        <select
            class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm text-gray-700 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option>Last 30 days</option>
          <option>This month</option>
          <option>Last month</option>
          <option>This year</option>
        </select>
        <select
            class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm text-gray-700 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option>All channels</option>
          <option>POS</option>
          <option>Online</option>
        </select>
      </div>
    </div>

    <!-- 🔹 Quick actions -->
    <div
        class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between"
    >
      <div>
        <p class="text-sm font-semibold text-gray-900">Quick actions</p>
        <p class="text-xs text-gray-500">
          Manage your accounts and cash in one click.
        </p>
      </div>
      <div class="flex flex-wrap gap-2">
        <button
            type="button"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-200 text-gray-700 text-sm font-medium px-3 py-1.5 hover:bg-gray-50 transition-colors"
            @click="handleAddAccountClick"
        >
          <Wallet class="w-4 h-4"/>
          Add account
        </button>

        <button
            type="button"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-200 text-gray-700 text-sm font-medium px-3 py-1.5 hover:bg-gray-50 transition-colors"
            @click="handleTransferClick"
        >
          <ArrowRightLeft class="w-4 h-4"/>
          Transfer between accounts
        </button>

        <button
            type="button"
            class="inline-flex items-center gap-2 rounded-lg bg-blue-600 text-white text-sm font-medium px-3 py-1.5 hover:bg-blue-700 transition-colors"
            @click="handleAddBalanceClick"
        >
          <Wallet class="w-4 h-4"/>
          Add balance
        </button>
      </div>
    </div>

    <!-- Top KPIs row -->
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
      <!-- Total Balance -->
      <div
          class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex flex-col justify-between"
      >
        <div class="flex items-center justify-between mb-3">
          <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">
            Total balance
          </p>
          <Wallet class="w-4 h-4 text-blue-500"/>
        </div>
        <p class="text-2xl font-semibold text-gray-900">
          {{ totalBalance.toFixed(2) }} {{ currency }}
        </p>
        <p class="mt-1 text-xs text-gray-500">
          Across all accounts
        </p>
      </div>

      <!-- Paid invoices (total sales) -->
      <div
          class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex flex-col justify-between"
      >
        <div class="flex items-center justify-between mb-3">
          <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">
            Total sales (paid invoices)
          </p>
          <TrendingUp class="w-4 h-4 text-green-500"/>
        </div>
        <p class="text-2xl font-semibold text-gray-900">
          {{ paidInvoicesThisMonth.toFixed(2) }} {{ currency }}
        </p>
        <p class="mt-1 text-xs text-gray-500">
          Collected this month
        </p>
      </div>

      <!-- Expenses -->
      <div
          class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex flex-col justify-between"
      >
        <div class="flex items-center justify-between mb-3">
          <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">
            Expenses
          </p>
          <Receipt class="w-4 h-4 text-orange-500"/>
        </div>
        <p class="text-2xl font-semibold text-gray-900">
          {{ totalExpensesThisMonth.toFixed(2) }} {{ currency }}
        </p>
        <p class="mt-1 text-xs text-gray-500">
          Spent this month
        </p>
      </div>

      <!-- 🔹 ROI instead of "Net cash flow" -->
      <div
          class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex flex-col justify-between"
      >
        <div class="flex items-center justify-between mb-3">
          <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">
            ROI (Return on Investment)
          </p>
          <CreditCard
              class="w-4 h-4"
              :class="isRoiPositive ? 'text-emerald-500' : 'text-red-500'"
          />
        </div>
        <div class="flex items-baseline gap-2">
          <p
              class="text-2xl font-semibold"
              :class="isRoiPositive ? 'text-emerald-600' : 'text-red-600'"
          >
            {{ roiPercent.toFixed(1) }}%
          </p>
          <div
              class="inline-flex items-center gap-1 text-xs rounded-full px-2 py-0.5"
              :class="
              isRoiPositive
                ? 'bg-emerald-50 text-emerald-700'
                : 'bg-red-50 text-red-700'
            "
          >
            <ArrowUpRight
                v-if="isRoiPositive"
                class="w-3 h-3"
            />
            <ArrowDownRight
                v-else
                class="w-3 h-3"
            />
            <span>Net {{ netCashFlow.toFixed(2) }} {{ currency }}</span>
          </div>
        </div>
        <p class="mt-1 text-xs text-gray-500">
          ROI = Net cash flow / Expenses
        </p>
      </div>
    </div>

    <!-- Bottom row -->
    <div class="grid gap-4 md:grid-cols-2">
      <div
          class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex items-center justify-between"
      >
        <div>
          <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">
            Pending invoices
          </p>
          <p class="mt-1 text-lg font-semibold text-gray-900">
            {{ pendingInvoicesThisMonth.toFixed(2) }} {{ currency }}
          </p>
          <p class="text-xs text-gray-500">
            To be collected this month
          </p>
        </div>
        <button
            type="button"
            class="text-xs font-medium text-blue-700 hover:text-blue-800 hover:bg-blue-50 rounded-lg px-3 py-1.5"
        >
          Review invoices
        </button>
      </div>

      <div
          class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex items-center justify-between"
      >
        <div>
          <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">
            Cash position
          </p>
          <p class="mt-1 text-lg font-semibold text-gray-900">
            {{ totalBalance.toFixed(2) }} {{ currency }}
          </p>
          <p class="text-xs text-gray-500">
            Including all connected accounts
          </p>
        </div>
        <button
            type="button"
            class="text-xs font-medium text-blue-700 hover:text-blue-800 hover:bg-blue-50 rounded-lg px-3 py-1.5"
            @click="handleAddBalanceClick"
        >
          Add manual adjustment
        </button>
      </div>
    </div>
  </div>
</template>
