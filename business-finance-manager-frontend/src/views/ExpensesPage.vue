<script setup>
import { computed, onMounted, ref } from 'vue'
import expenseService from '../services/expenseService'
import accountService from '../services/accountService'
import api from '../services/api'
import { useStore } from 'vuex'

const store = useStore()
const currency = computed(() => store.getters['auth/defaultCurrency'])

const accounts = ref([])
const categories = ref([])
const expenses = ref([])
const loading = ref(false)
const error = ref('')

const filters = ref({
  from: new Date(new Date().getFullYear(), new Date().getMonth(), 1)
    .toISOString()
    .slice(0, 10),
  to: new Date().toISOString().slice(0, 10),
  category_id: '',
})

const form = ref({
  date: new Date().toISOString().slice(0, 10),
  account_id: '',
  category_id: '',
  amount: '',
  note: '',
})

const editId = ref(null)
const editForm = ref({
  date: '',
  account_id: '',
  category_id: '',
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

const loadCategories = async () => {
  const response = await api.get('/expense-categories')
  categories.value = response.data
  if (categories.value.length && !form.value.category_id) {
    form.value.category_id = categories.value[0].id
  }
}

const loadExpenses = async () => {
  loading.value = true
  error.value = ''
  try {
    const response = await expenseService.getAll(null, {
      from: filters.value.from,
      to: filters.value.to,
      category_id: filters.value.category_id || undefined,
    })
    expenses.value = response.data
  } catch (e) {
    error.value = e.response?.data?.message || 'Failed to load expenses.'
  } finally {
    loading.value = false
  }
}

const submitExpense = async () => {
  try {
    await expenseService.create(form.value)
    await loadExpenses()
    form.value.note = ''
    form.value.amount = ''
    window.alert('Expense added successfully')
  } catch (e) {
    error.value = e.response?.data?.message || 'Could not create expense.'
  }
}

const startEdit = (expense) => {
  editId.value = expense.id
  editForm.value = {
    date: expense.date,
    account_id: expense.account_id,
    category_id: expense.category_id,
    amount: expense.amount,
    note: expense.note || '',
  }
}

const cancelEdit = () => {
  editId.value = null
}

const updateExpense = async () => {
  if (!editId.value) return
  try {
    await expenseService.update(editId.value, editForm.value)
    await loadExpenses()
    editId.value = null
    window.alert('Expense updated successfully')
  } catch (e) {
    error.value = e.response?.data?.message || 'Could not update expense.'
  }
}

const deleteExpense = async (id) => {
  if (!confirm('Are you sure you want to delete this expense?')) return
  try {
    await expenseService.delete(id)
    expenses.value = expenses.value.filter((e) => e.id !== id)
    window.alert('Expense deleted successfully')
  } catch (e) {
    error.value = e.response?.data?.message || 'Could not delete expense.'
  }
}

onMounted(async () => {
  await Promise.all([loadAccounts(), loadCategories()])
  loadExpenses()
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-semibold text-gray-900">Expenses</h1>
        <p class="text-sm text-gray-500">Track spending by category and account.</p>
      </div>
    </div>

    <div v-if="error" class="p-3 bg-red-100 text-red-700 rounded">{{ error }}</div>

    <div class="bg-white p-4 rounded-lg shadow-sm border space-y-3">
      <h2 class="text-lg font-semibold">Filters</h2>
      <div class="grid gap-3 md:grid-cols-4">
        <div class="flex flex-col gap-1">
          <label class="text-sm text-gray-700">From</label>
          <input v-model="filters.from" type="date" class="border rounded px-3 py-2" />
        </div>
        <div class="flex flex-col gap-1">
          <label class="text-sm text-gray-700">To</label>
          <input v-model="filters.to" type="date" class="border rounded px-3 py-2" />
        </div>
        <div class="flex flex-col gap-1">
          <label class="text-sm text-gray-700">Category</label>
          <select v-model="filters.category_id" class="border rounded px-3 py-2">
            <option value="">All</option>
            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
          </select>
        </div>
        <div class="flex items-end">
          <button class="bg-blue-600 text-white px-4 py-2 rounded" @click="loadExpenses">Apply</button>
        </div>
      </div>
    </div>

    <div class="bg-white p-4 rounded-lg shadow-sm border space-y-3">
      <h2 class="text-lg font-semibold">Add Expense</h2>
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
        <div class="flex flex-col gap-1">
          <label class="text-sm text-gray-700">Category</label>
          <select v-model="form.category_id" class="border rounded px-3 py-2">
            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
          </select>
        </div>
        <div class="md:col-span-2 flex flex-col gap-1">
          <label class="text-sm text-gray-700">Note</label>
          <textarea v-model="form.note" rows="2" class="border rounded px-3 py-2"></textarea>
        </div>
      </div>
      <div>
        <button class="bg-green-600 text-white px-4 py-2 rounded" @click="submitExpense">Save Expense</button>
      </div>
    </div>

    <div class="bg-white p-4 rounded-lg shadow-sm border">
      <div class="flex items-center justify-between mb-3">
        <h2 class="text-lg font-semibold">Expense List</h2>
        <span class="text-sm text-gray-600">{{ expenses.length }} items</span>
      </div>
      <div v-if="loading" class="text-gray-600">Loading expenses...</div>
      <div v-else class="overflow-x-auto">
        <table class="w-full text-left">
          <thead>
            <tr class="bg-gray-100 text-sm">
              <th class="p-2">Date</th>
              <th class="p-2">Account</th>
              <th class="p-2">Category</th>
              <th class="p-2">Amount</th>
              <th class="p-2">Note</th>
              <th class="p-2 text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in expenses" :key="item.id" class="border-b align-top">
              <template v-if="editId === item.id">
                <td class="p-2">
                  <input v-model="editForm.date" type="date" class="border rounded px-2 py-1 w-full" />
                </td>
                <td class="p-2">
                  <select v-model="editForm.account_id" class="border rounded px-2 py-1 w-full">
                    <option v-for="acc in accounts" :key="acc.id" :value="acc.id">{{ acc.name }}</option>
                  </select>
                </td>
                <td class="p-2">
                  <select v-model="editForm.category_id" class="border rounded px-2 py-1 w-full">
                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                  </select>
                </td>
                <td class="p-2">
                  <input v-model.number="editForm.amount" type="number" min="0" class="border rounded px-2 py-1 w-full" />
                </td>
                <td class="p-2">
                  <textarea v-model="editForm.note" rows="2" class="border rounded px-2 py-1 w-full"></textarea>
                </td>
                <td class="p-2 text-right space-x-2 whitespace-nowrap">
                  <button class="text-sm text-green-700" @click="updateExpense">Save</button>
                  <button class="text-sm text-gray-600" @click="cancelEdit">Cancel</button>
                </td>
              </template>
              <template v-else>
                <td class="p-2">{{ item.date }}</td>
                <td class="p-2">{{ item.account?.name || accounts.find(a => a.id === item.account_id)?.name }}</td>
                <td class="p-2">{{ item.category?.name || categories.find(c => c.id === item.category_id)?.name }}</td>
                <td class="p-2 font-semibold">{{ Number(item.amount || 0).toFixed(2) }} {{ currency }}</td>
                <td class="p-2 text-sm text-gray-600">{{ item.note }}</td>
                <td class="p-2 text-right space-x-3 whitespace-nowrap">
                  <button class="text-blue-600" @click="startEdit(item)">Edit</button>
                  <button class="text-red-600" @click="deleteExpense(item.id)">Delete</button>
                </td>
              </template>
            </tr>
          </tbody>
        </table>
        <p v-if="!expenses.length" class="text-gray-500 text-sm py-4">No expenses found.</p>
      </div>
    </div>
  </div>
</template>
