<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../services/api'
import { useStore } from 'vuex'

const store = useStore()
const currency = computed(() => store.getters['auth/defaultCurrency'])

const currentMonth = ref(new Date().toISOString().slice(0, 7))
const loading = ref(false)
const error = ref('')
const summary = ref({
  total_income: 0,
  total_expenses: 0,
  total_purchases: 0,
  net_profit: 0,
  expenses_by_category: [],
})

const loadSummary = async () => {
  loading.value = true
  error.value = ''
  try {
    const response = await api.get('/dashboard/summary', {
      params: { month: currentMonth.value },
    })
    summary.value = response.data
  } catch (e) {
    error.value = e.response?.data?.message || 'Failed to load dashboard summary.'
  } finally {
    loading.value = false
  }
}

onMounted(loadSummary)
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-semibold text-gray-900">Dashboard</h1>
        <p class="text-sm text-gray-500">Monthly performance overview</p>
      </div>
      <div class="flex items-center gap-2">
        <label for="month" class="text-sm text-gray-700">Month</label>
        <input
          id="month"
          v-model="currentMonth"
          type="month"
          class="border rounded-lg px-3 py-2"
          @change="loadSummary"
        />
      </div>
    </div>

    <div v-if="error" class="p-3 rounded bg-red-100 text-red-700">{{ error }}</div>

    <div v-if="loading" class="text-gray-600">Loading summary...</div>

    <div v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
      <div class="bg-white p-4 rounded-lg shadow-sm border">
        <p class="text-sm text-gray-500">Total Income</p>
        <p class="text-2xl font-semibold">{{ Number(summary.total_income || 0).toFixed(2) }} {{ currency }}</p>
      </div>
      <div class="bg-white p-4 rounded-lg shadow-sm border">
        <p class="text-sm text-gray-500">Total Expenses</p>
        <p class="text-2xl font-semibold">{{ Number(summary.total_expenses || 0).toFixed(2) }} {{ currency }}</p>
      </div>
      <div class="bg-white p-4 rounded-lg shadow-sm border">
        <p class="text-sm text-gray-500">Total Purchases</p>
        <p class="text-2xl font-semibold">{{ Number(summary.total_purchases || 0).toFixed(2) }} {{ currency }}</p>
      </div>
      <div class="bg-white p-4 rounded-lg shadow-sm border">
        <p class="text-sm text-gray-500">Net Profit</p>
        <p class="text-2xl font-semibold">{{ Number(summary.net_profit || 0).toFixed(2) }} {{ currency }}</p>
      </div>
    </div>

    <div class="bg-white p-4 rounded-lg shadow-sm border">
      <h2 class="text-lg font-semibold text-gray-800 mb-3">Expenses by Category</h2>
      <ul class="divide-y">
        <li v-for="item in summary.expenses_by_category" :key="item.category" class="py-2 flex justify-between">
          <span class="text-gray-700">{{ item.category }}</span>
          <span class="font-semibold">{{ Number(item.amount || 0).toFixed(2) }} {{ currency }}</span>
        </li>
        <li v-if="!summary.expenses_by_category?.length" class="text-gray-500 text-sm py-2">
          No expenses found for this month.
        </li>
      </ul>
    </div>
  </div>
</template>
