<script setup>
import { computed, onMounted, ref } from 'vue'
import { useStore } from 'vuex'
import incomeService from '../services/incomeService'
import accountService from '../services/accountService'
import ModalDialog from '../components/ModalDialog.vue'
import { formatDateTime } from '../utils/date'

const store = useStore()
const currency = computed(() => store.getters['auth/defaultCurrency'])

const incomes = ref([])
const accounts = ref([])
const loading = ref(false)
const error = ref('')

const form = ref({
  date: new Date().toISOString().slice(0, 10),
  account_id: '',
  amount: '',
  note: '',
})

const showEditModal = ref(false)
const selectedIncome = ref(null)
const editForm = ref({
  date: '',
  account_id: '',
  amount: '',
  note: '',
})

const loadAccounts = async () => {
  const response = await accountService.getAll()
  accounts.value = response.data
  if (accounts.value.length && !form.value.account_id) {
    form.value.account_id = accounts.value[0].id
  }
}

const loadIncomes = async () => {
  loading.value = true
  error.value = ''
  try {
    const response = await incomeService.getAll()
    incomes.value = response.data
  } catch (e) {
    error.value = e.response?.data?.message || 'Failed to load income.'
  } finally {
    loading.value = false
  }
}

const resetForm = () => {
  form.value = {
    date: new Date().toISOString().slice(0, 10),
    account_id: accounts.value[0]?.id || '',
    amount: '',
    note: '',
  }
}

const submitIncome = async () => {
  try {
    await incomeService.create(form.value)
    await loadIncomes()
    resetForm()
    window.alert('Income added successfully')
  } catch (e) {
    error.value = e.response?.data?.message || 'Could not create income.'
  }
}

const startEdit = (item) => {
  selectedIncome.value = item
  editForm.value = {
    date: item.date,
    account_id: item.account_id,
    amount: item.amount,
    note: item.note || '',
  }
  showEditModal.value = true
}

const closeEdit = () => {
  showEditModal.value = false
  selectedIncome.value = null
  editForm.value = {
    date: '',
    account_id: '',
    amount: '',
    note: '',
  }
}

const updateIncome = async () => {
  if (!selectedIncome.value) return
  try {
    await incomeService.update(selectedIncome.value.id, editForm.value)
    await loadIncomes()
    closeEdit()
    window.alert('Income updated successfully')
  } catch (e) {
    error.value = e.response?.data?.message || 'Could not update income.'
  }
}

const deleteIncome = async (id) => {
  if (!confirm('Are you sure you want to delete this income?')) return
  try {
    await incomeService.delete(id)
    incomes.value = incomes.value.filter((i) => i.id !== id)
    window.alert('Income deleted successfully')
  } catch (e) {
    error.value = e.response?.data?.message || 'Could not delete income.'
  }
}

onMounted(async () => {
  await loadAccounts()
  loadIncomes()
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-semibold text-gray-900">Income</h1>
        <p class="text-sm text-gray-500">Record income and keep balances updated.</p>
      </div>
    </div>

    <div v-if="error" class="p-3 bg-red-100 text-red-700 rounded">{{ error }}</div>

    <div class="bg-white p-4 rounded-lg shadow-sm border space-y-3">
      <h2 class="text-lg font-semibold">Add Income</h2>
      <div class="grid gap-3 md:grid-cols-2">
        <div class="flex flex-col gap-1">
          <label class="text-sm text-gray-700">Date</label>
          <input v-model="form.date" type="date" class="border rounded px-3 py-2" />
        </div>
        <div class="flex flex-col gap-1">
          <label class="text-sm text-gray-700">Amount</label>
          <input v-model.number="form.amount" type="number" min="0" class="border rounded px-3 py-2" />
        </div>
        <div class="flex flex-col gap-1">
          <label class="text-sm text-gray-700">Account</label>
          <select v-model="form.account_id" class="border rounded px-3 py-2">
            <option v-for="acc in accounts" :key="acc.id" :value="acc.id">{{ acc.name }}</option>
          </select>
        </div>
        <div class="md:col-span-2 flex flex-col gap-1">
          <label class="text-sm text-gray-700">Note</label>
          <textarea v-model="form.note" rows="2" class="border rounded px-3 py-2"></textarea>
        </div>
      </div>
      <div>
        <button class="bg-green-600 text-white px-4 py-2 rounded" @click="submitIncome">Save Income</button>
      </div>
    </div>

    <div class="bg-white p-4 rounded-lg shadow-sm border">
      <div class="flex items-center justify-between mb-3">
        <h2 class="text-lg font-semibold">Income List</h2>
        <span class="text-sm text-gray-600">{{ incomes.length }} items</span>
      </div>
      <div v-if="loading" class="text-gray-600">Loading incomes...</div>
      <div v-else class="overflow-x-auto">
        <table class="w-full text-left">
          <thead>
            <tr class="bg-gray-100 text-sm">
              <th class="p-2">Date</th>
              <th class="p-2">Account</th>
              <th class="p-2">Amount</th>
              <th class="p-2">Note</th>
              <th class="p-2 text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in incomes" :key="item.id" class="border-b align-top">
              <td class="p-2">{{ formatDateTime(item.date) }}</td>
              <td class="p-2">{{ item.account?.name || accounts.find((a) => a.id === item.account_id)?.name }}</td>
              <td class="p-2 font-semibold">{{ Number(item.amount || 0).toFixed(2) }} {{ currency }}</td>
              <td class="p-2 text-sm text-gray-600">{{ item.note || '-' }}</td>
              <td class="p-2 text-right space-x-3 whitespace-nowrap">
                <button class="text-blue-600" @click="startEdit(item)">Edit</button>
                <button class="text-red-600" @click="deleteIncome(item.id)">Delete</button>
              </td>
            </tr>
          </tbody>
        </table>
        <p v-if="!incomes.length" class="text-gray-500 text-sm py-4">No income found.</p>
      </div>
    </div>

    <ModalDialog v-model:modelValue="showEditModal" title="Edit Income" @close="closeEdit">
      <div class="grid gap-3 md:grid-cols-2">
        <div class="flex flex-col gap-1">
          <label class="text-sm text-gray-700">Date</label>
          <input v-model="editForm.date" type="date" class="border rounded px-3 py-2" />
        </div>
        <div class="flex flex-col gap-1">
          <label class="text-sm text-gray-700">Amount</label>
          <input v-model.number="editForm.amount" type="number" min="0" class="border rounded px-3 py-2" />
        </div>
        <div class="flex flex-col gap-1">
          <label class="text-sm text-gray-700">Account</label>
          <select v-model="editForm.account_id" class="border rounded px-3 py-2">
            <option v-for="acc in accounts" :key="acc.id" :value="acc.id">{{ acc.name }}</option>
          </select>
        </div>
        <div class="md:col-span-2 flex flex-col gap-1">
          <label class="text-sm text-gray-700">Note</label>
          <textarea v-model="editForm.note" rows="2" class="border rounded px-3 py-2"></textarea>
        </div>
      </div>
      <template #footer>
        <button class="px-4 py-2 border rounded" @click="closeEdit">Cancel</button>
        <button class="px-4 py-2 bg-blue-600 text-white rounded" @click="updateIncome">Save</button>
      </template>
    </ModalDialog>
  </div>
</template>
