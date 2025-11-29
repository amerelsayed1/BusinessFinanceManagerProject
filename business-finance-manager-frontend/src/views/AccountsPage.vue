<script setup>
import { onMounted, ref, computed } from 'vue'
import accountService from '../services/accountService'
import { useStore } from 'vuex'

const store = useStore()
const currency = computed(() => store.getters['auth/defaultCurrency'])

const accounts = ref([])
const loading = ref(false)
const error = ref('')

const form = ref({
  name: '',
  type: 'cash',
  opening_balance: 0,
})

const loadAccounts = async () => {
  loading.value = true
  error.value = ''
  try {
    const response = await accountService.getAll()
    accounts.value = response.data
  } catch (e) {
    error.value = e.response?.data?.message || 'Failed to load accounts.'
  } finally {
    loading.value = false
  }
}

const createAccount = async () => {
  if (!form.value.name) return
  try {
    await accountService.create(form.value)
    form.value = { name: '', type: 'cash', opening_balance: 0 }
    await loadAccounts()
  } catch (e) {
    error.value = e.response?.data?.message || 'Could not create account.'
  }
}

const deleteAccount = async (id) => {
  if (!confirm('Delete this account?')) return
  try {
    await accountService.delete(id)
    await loadAccounts()
  } catch (e) {
    error.value = e.response?.data?.message || 'Could not delete account.'
  }
}

onMounted(loadAccounts)
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-semibold text-gray-900">Accounts</h1>
        <p class="text-sm text-gray-500">Manage your cash, bank, and wallet balances.</p>
      </div>
    </div>

    <div v-if="error" class="p-3 bg-red-100 text-red-700 rounded">{{ error }}</div>

    <div class="bg-white p-4 rounded-lg shadow-sm border">
      <h2 class="text-lg font-semibold mb-3">Create Account</h2>
      <div class="grid gap-3 md:grid-cols-3">
        <input v-model="form.name" type="text" placeholder="Account name" class="border rounded px-3 py-2" />
        <select v-model="form.type" class="border rounded px-3 py-2">
          <option value="cash">Cash</option>
          <option value="bank">Bank</option>
          <option value="wallet">Wallet</option>
          <option value="other">Other</option>
        </select>
        <input v-model.number="form.opening_balance" type="number" class="border rounded px-3 py-2" placeholder="Opening balance" />
      </div>
      <div class="mt-3">
        <button class="bg-blue-600 text-white px-4 py-2 rounded" @click="createAccount">Save</button>
      </div>
    </div>

    <div class="bg-white p-4 rounded-lg shadow-sm border">
      <h2 class="text-lg font-semibold mb-3">Your Accounts</h2>
      <div v-if="loading" class="text-gray-600">Loading accounts...</div>
      <div v-else class="grid gap-4 md:grid-cols-2">
        <div v-for="account in accounts" :key="account.id" class="border rounded p-4">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-sm text-gray-500 uppercase">{{ account.type }}</p>
              <p class="text-xl font-semibold">{{ account.name }}</p>
            </div>
            <button class="text-red-600 text-sm" @click="deleteAccount(account.id)">Delete</button>
          </div>
          <p class="mt-2 text-sm text-gray-600">Opening balance: {{ Number(account.opening_balance || 0).toFixed(2) }} {{ currency }}</p>
          <p class="mt-1 text-lg font-semibold">Current: {{ Number(account.current_balance || 0).toFixed(2) }} {{ currency }}</p>
        </div>
      </div>
      <p v-if="!loading && !accounts.length" class="text-gray-500 text-sm">No accounts yet.</p>
    </div>
  </div>
</template>
