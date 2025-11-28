<script setup>
import { computed, onMounted, ref } from 'vue'
import accountService from '../services/accountService'
import { useStore } from 'vuex'

const store = useStore()
const currency = computed(() => store.getters['auth/defaultCurrency'])

const transfers = ref([])
const accounts = ref([])
const showModal = ref(false)
const submitting = ref(false)
const error = ref('')

const form = ref({
  from_account_id: '',
  to_account_id: '',
  amount: null,
  date: new Date().toISOString().slice(0, 10),
  note: '',
})

const loadAccounts = async () => {
  const response = await accountService.getAll()
  accounts.value = response.data
}

const loadTransfers = async () => {
  try {
    const response = await accountService.listTransfers()
    transfers.value = response.data
  } catch (e) {
    // listing is optional; ignore failures
  }
}

const createTransfer = async () => {
  if (form.value.from_account_id === form.value.to_account_id) {
    alert('Accounts must be different')
    return
  }
  submitting.value = true
  error.value = ''
  try {
    await accountService.transfer(
      form.value.from_account_id,
      form.value.to_account_id,
      form.value.amount,
      form.value.date,
      form.value.note,
    )
    await Promise.all([loadTransfers(), loadAccounts()])
    closeModal()
  } catch (e) {
    error.value = e.response?.data?.message || 'Failed to create transfer.'
  } finally {
    submitting.value = false
  }
}

const closeModal = () => {
  showModal.value = false
  form.value = {
    from_account_id: '',
    to_account_id: '',
    amount: null,
    date: new Date().toISOString().slice(0, 10),
    note: '',
  }
}

onMounted(async () => {
  await loadAccounts()
  await loadTransfers()
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-semibold text-gray-900">Account Transfers</h1>
        <p class="text-sm text-gray-500">Move money between accounts (does not affect profit).</p>
      </div>
      <button class="bg-blue-600 text-white px-4 py-2 rounded" @click="showModal = true">New Transfer</button>
    </div>

    <div v-if="error" class="p-3 bg-red-100 text-red-700 rounded">{{ error }}</div>

    <div class="bg-white p-4 rounded-lg shadow-sm border">
      <div class="flex items-center justify-between mb-3">
        <h2 class="text-lg font-semibold">Transfers</h2>
        <span class="text-sm text-gray-600">{{ transfers.length }} records</span>
      </div>
      <div v-if="!transfers.length" class="text-gray-500 text-sm">No transfers recorded.</div>
      <div v-else class="overflow-x-auto">
        <table class="w-full text-left">
          <thead>
            <tr class="bg-gray-100 text-sm">
              <th class="p-2">Date</th>
              <th class="p-2">From</th>
              <th class="p-2">To</th>
              <th class="p-2">Amount</th>
              <th class="p-2">Note</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in transfers" :key="item.id" class="border-b">
              <td class="p-2">{{ item.date }}</td>
              <td class="p-2">{{ item.from_account?.name || accounts.find(a => a.id === item.from_account_id)?.name }}</td>
              <td class="p-2">{{ item.to_account?.name || accounts.find(a => a.id === item.to_account_id)?.name }}</td>
              <td class="p-2 font-semibold">{{ Number(item.amount || 0).toFixed(2) }} {{ currency }}</td>
              <td class="p-2 text-sm text-gray-600">{{ item.note || '-' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50" @click.self="closeModal">
      <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-lg">
        <h3 class="text-lg font-semibold mb-4">New Transfer</h3>
        <div class="space-y-3">
          <div class="flex flex-col gap-1">
            <label class="text-sm text-gray-700">From account</label>
            <select v-model="form.from_account_id" class="border rounded px-3 py-2">
              <option value="">Select account</option>
              <option
                v-for="acc in accounts"
                :key="acc.id"
                :value="acc.id"
                :disabled="acc.id === form.to_account_id"
              >
                {{ acc.name }}
              </option>
            </select>
          </div>
          <div class="flex flex-col gap-1">
            <label class="text-sm text-gray-700">To account</label>
            <select v-model="form.to_account_id" class="border rounded px-3 py-2">
              <option value="">Select account</option>
              <option
                v-for="acc in accounts"
                :key="acc.id"
                :value="acc.id"
                :disabled="acc.id === form.from_account_id"
              >
                {{ acc.name }}
              </option>
            </select>
          </div>
          <div class="flex flex-col gap-1">
            <label class="text-sm text-gray-700">Amount</label>
            <input v-model.number="form.amount" type="number" min="0" class="border rounded px-3 py-2" />
          </div>
          <div class="flex flex-col gap-1">
            <label class="text-sm text-gray-700">Date</label>
            <input v-model="form.date" type="date" class="border rounded px-3 py-2" />
          </div>
          <div class="flex flex-col gap-1">
            <label class="text-sm text-gray-700">Note</label>
            <textarea v-model="form.note" rows="2" class="border rounded px-3 py-2"></textarea>
          </div>
        </div>
        <div class="flex justify-end gap-2 mt-4">
          <button class="px-4 py-2 border rounded" @click="closeModal">Cancel</button>
          <button class="px-4 py-2 bg-blue-600 text-white rounded" :disabled="submitting" @click="createTransfer">
            {{ submitting ? 'Saving...' : 'Save' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
