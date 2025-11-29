<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../services/api'
import { useStore } from 'vuex'

const store = useStore()
const currency = computed(() => store.getters['auth/defaultCurrency'])

const month = ref(new Date().toISOString().slice(0, 7))
const loading = ref(false)
const error = ref('')
const data = ref({ incomes: [], expenses: [], purchases: [], transfers: [] })

const loadReport = async () => {
  loading.value = true
  error.value = ''
  try {
    const response = await api.get('/reports/accountant-export', {
      params: { month: month.value },
    })
    data.value = response.data
  } catch (e) {
    error.value = e.response?.data?.message || 'Failed to load report.'
  } finally {
    loading.value = false
  }
}

onMounted(loadReport)
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-semibold text-gray-900">Accountant Export</h1>
        <p class="text-sm text-gray-500">Download-ready data for your accountant.</p>
      </div>
      <div class="flex items-center gap-2">
        <label class="text-sm text-gray-700">Month</label>
        <input v-model="month" type="month" class="border rounded px-3 py-2" />
        <button class="bg-blue-600 text-white px-4 py-2 rounded" @click="loadReport">Load</button>
      </div>
    </div>

    <div v-if="error" class="p-3 bg-red-100 text-red-700 rounded">{{ error }}</div>
    <div v-if="loading" class="text-gray-600">Loading...</div>

    <div v-else class="grid gap-4 md:grid-cols-2">
      <div class="bg-white p-4 rounded-lg shadow-sm border">
        <div class="flex justify-between mb-2">
          <h2 class="font-semibold">Incomes</h2>
          <span class="text-sm text-gray-600">{{ data.incomes?.length || 0 }} records</span>
        </div>
        <ul class="space-y-1 text-sm">
          <li v-for="item in data.incomes" :key="item.id" class="flex justify-between">
            <span>{{ item.date }}</span>
            <span class="font-semibold">{{ Number(item.amount || 0).toFixed(2) }} {{ currency }}</span>
          </li>
          <li v-if="!data.incomes?.length" class="text-gray-500">No incomes</li>
        </ul>
      </div>

      <div class="bg-white p-4 rounded-lg shadow-sm border">
        <div class="flex justify-between mb-2">
          <h2 class="font-semibold">Expenses</h2>
          <span class="text-sm text-gray-600">{{ data.expenses?.length || 0 }} records</span>
        </div>
        <ul class="space-y-1 text-sm">
          <li v-for="item in data.expenses" :key="item.id" class="flex justify-between">
            <span>{{ item.date }} - {{ item.category?.name || item.category_name }}</span>
            <span class="font-semibold">{{ Number(item.amount || 0).toFixed(2) }} {{ currency }}</span>
          </li>
          <li v-if="!data.expenses?.length" class="text-gray-500">No expenses</li>
        </ul>
      </div>

      <div class="bg-white p-4 rounded-lg shadow-sm border">
        <div class="flex justify-between mb-2">
          <h2 class="font-semibold">Purchases</h2>
          <span class="text-sm text-gray-600">{{ data.purchases?.length || 0 }} records</span>
        </div>
        <ul class="space-y-1 text-sm">
          <li v-for="item in data.purchases" :key="item.id" class="flex justify-between items-center">
            <div>
              <p class="font-medium">{{ item.supplier_name || 'Supplier' }}</p>
              <p class="text-xs text-gray-500">{{ item.date }} • {{ item.reference || 'No ref' }}</p>
            </div>
            <div class="text-right">
              <p class="font-semibold">{{ Number(item.total_amount || 0).toFixed(2) }} {{ currency }}</p>
              <a
                v-if="item.invoice_image_url"
                :href="item.invoice_image_url"
                target="_blank"
                class="text-blue-600 underline text-xs"
                rel="noopener"
              >
                Invoice
              </a>
            </div>
          </li>
          <li v-if="!data.purchases?.length" class="text-gray-500">No purchases</li>
        </ul>
      </div>

      <div class="bg-white p-4 rounded-lg shadow-sm border">
        <div class="flex justify-between mb-2">
          <h2 class="font-semibold">Transfers</h2>
          <span class="text-sm text-gray-600">{{ data.transfers?.length || 0 }} records</span>
        </div>
        <ul class="space-y-1 text-sm">
          <li v-for="item in data.transfers" :key="item.id" class="flex justify-between">
            <span>{{ item.date }} - {{ item.note || 'No note' }}</span>
            <span class="font-semibold">{{ Number(item.amount || 0).toFixed(2) }} {{ currency }}</span>
          </li>
          <li v-if="!data.transfers?.length" class="text-gray-500">No transfers</li>
        </ul>
      </div>
    </div>
  </div>
</template>
