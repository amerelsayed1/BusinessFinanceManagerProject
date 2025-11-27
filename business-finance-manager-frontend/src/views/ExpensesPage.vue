<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  currency: { type: String, required: true },
  accounts: { type: Array, required: true },
  categories: { type: Array, required: true },
  monthLabel: { type: String, required: true },
  expenses: { type: Array, required: true },
  totalForMonth: { type: Number, required: true },
})

const emit = defineEmits([
  'prev-month',
  'next-month',
  'add-expense',
  'update-expense',
  'delete-expense',
])

const form = ref({
  description: '',
  amount: '',
  date: new Date().toISOString().split('T')[0],
  accountId: '',
  categoryId: '',
})

const showEditModal = ref(false)
const editingExpense = ref(null)
const editForm = ref({
  description: '',
  amount: '',
  date: '',
  accountId: '',
  categoryId: '',
})

watch(
  () => props.accounts,
  (list) => {
    if (list.length > 0 && !form.value.accountId) {
      form.value.accountId = String(list[0].id)
    }
  },
  { immediate: true },
)

watch(
  () => props.categories,
  (list) => {
    if (list.length > 0 && !form.value.categoryId) {
      form.value.categoryId = String(list[0].id)
    }
  },
  { immediate: true },
)

const submitExpense = () => {
  emit('add-expense', { ...form.value })
}

const startEditExpense = (expense) => {
  editingExpense.value = expense
  editForm.value = {
    description: expense.description || '',
    amount: expense.amount,
    date: expense.date,
    accountId: String(expense.account_id ?? expense.accountId ?? ''),
    categoryId: expense.category_id ? String(expense.category_id) : '',
  }
  showEditModal.value = true
}

const submitEdit = () => {
  if (!editingExpense.value) return
  emit('update-expense', editingExpense.value.id, { ...editForm.value })
  showEditModal.value = false
}
</script>

<template>
  <div class="bg-white rounded-lg shadow-lg p-4 md:p-6">
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-2xl font-bold text-gray-800">Expenses</h2>
      <div class="flex items-center gap-2">
        <button
          type="button"
          class="px-2 py-1 border rounded hover:bg-gray-100"
          @click="emit('prev-month')"
        >
          ‹
        </button>
        <span class="font-semibold text-gray-700 whitespace-nowrap">
          {{ monthLabel }}
        </span>
        <button
          type="button"
          class="px-2 py-1 border rounded hover:bg-gray-100"
          @click="emit('next-month')"
        >
          ›
        </button>
      </div>
    </div>

    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
      <p class="text-sm text-gray-600">Total Expenses ({{ monthLabel }})</p>
      <p class="text-3xl font-bold text-blue-600">
        {{ totalForMonth.toFixed(2) }} {{ currency }}
      </p>
    </div>

    <div class="space-y-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <input
          v-model="form.description"
          type="text"
          placeholder="Description"
          class="border rounded px-4 py-2"
        />
        <input
          v-model="form.amount"
          type="number"
          placeholder="Amount"
          class="border rounded px-4 py-2"
        />
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <input
          v-model="form.date"
          type="date"
          class="border rounded px-4 py-2"
        />
        <select v-model="form.accountId" class="border rounded px-4 py-2">
          <option
            v-for="acc in accounts"
            :key="acc.id"
            :value="acc.id.toString()"
          >
            {{ acc.name }}
          </option>
        </select>
        <select v-model="form.categoryId" class="border rounded px-4 py-2">
          <option value="">Uncategorized</option>
          <option
            v-for="cat in categories"
            :key="cat.id"
            :value="cat.id.toString()"
          >
            {{ cat.name }}
          </option>
        </select>
      </div>

      <button
        type="button"
        class="w-full bg-blue-500 text-white rounded px-4 py-2 hover:bg-blue-600 font-semibold"
        @click="submitExpense"
      >
        Add Expense
      </button>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full">
        <thead class="bg-gray-100">
          <tr>
            <th class="text-left p-3 text-sm">Description</th>
            <th class="text-left p-3 text-sm">Amount</th>
            <th class="text-left p-3 text-sm">Date</th>
            <th class="text-left p-3 text-sm">Account</th>
            <th class="text-left p-3 text-sm">Action</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="exp in expenses"
            :key="exp.id"
            class="border-b hover:bg-gray-50"
          >
            <td class="p-3">{{ exp.description }}</td>
            <td class="p-3">
              {{ Number(exp.amount || 0).toFixed(2) }} {{ currency }}
            </td>
            <td class="p-3">{{ exp.date }}</td>
            <td class="p-3 text-sm text-blue-600">
              {{
                (accounts.find(a => a.id === Number(exp.account_id ?? exp.accountId)) || {}).name
                  || 'Unknown'
              }}
            </td>
            <td class="p-3">
              <button
                type="button"
                class="text-indigo-500 hover:text-indigo-700 text-sm mr-3"
                @click="startEditExpense(exp)"
              >
                Edit
              </button>
              <button
                type="button"
                class="text-red-500 hover:text-red-700 text-sm"
                @click="emit('delete-expense', exp)"
              >
                Delete
              </button>
            </td>
          </tr>
        </tbody>
      </table>
      <p
        v-if="expenses.length === 0"
        class="text-center text-gray-500 py-6 text-sm"
      >
        No expenses for this month.
      </p>
    </div>

    <div
      v-if="showEditModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
      @click.self="showEditModal = false"
    >
      <div class="bg-white rounded-lg p-6 max-w-lg w-full">
        <h3 class="text-xl font-bold mb-4">Edit Expense</h3>
        <div class="space-y-4">
          <input
            v-model="editForm.description"
            type="text"
            placeholder="Description"
            class="border rounded px-4 py-2 w-full"
          />
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <input
              v-model="editForm.amount"
              type="number"
              placeholder="Amount"
              class="border rounded px-4 py-2 w-full"
            />
            <input
              v-model="editForm.date"
              type="date"
              class="border rounded px-4 py-2 w-full"
            />
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <select v-model="editForm.accountId" class="border rounded px-4 py-2 w-full">
              <option
                v-for="acc in accounts"
                :key="acc.id"
                :value="acc.id.toString()"
              >
                {{ acc.name }}
              </option>
            </select>
            <select v-model="editForm.categoryId" class="border rounded px-4 py-2 w-full">
              <option value="">Uncategorized</option>
              <option
                v-for="cat in categories"
                :key="cat.id"
                :value="cat.id.toString()"
              >
                {{ cat.name }}
              </option>
            </select>
          </div>
          <div class="flex gap-2">
            <button
              type="button"
              class="flex-1 bg-blue-600 text-white rounded px-4 py-2 hover:bg-blue-700"
              @click="submitEdit"
            >
              Save Changes
            </button>
            <button
              type="button"
              class="flex-1 bg-gray-200 text-gray-700 rounded px-4 py-2 hover:bg-gray-300"
              @click="showEditModal = false"
            >
              Cancel
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
