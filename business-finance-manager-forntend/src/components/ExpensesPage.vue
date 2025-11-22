<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  currency: { type: String, required: true },
  accounts: { type: Array, required: true },
  monthLabel: { type: String, required: true },
  expenses: { type: Array, required: true },
  totalForMonth: { type: Number, required: true },
})

const emit = defineEmits([
  'prev-month',
  'next-month',
  'add-expense',
  'delete-expense',
])

const form = ref({
  description: '',
  amount: '',
  date: new Date().toISOString().split('T')[0],
  accountId: '',
  isAds: false,
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

const submitExpense = () => {
  emit('add-expense', { ...form.value })
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
      </div>

      <div class="flex items-center gap-2">
        <input
          id="isAds"
          v-model="form.isAds"
          type="checkbox"
          class="h-4 w-4"
        />
        <label for="isAds" class="text-sm text-gray-700">
          This is an Ads cost
        </label>
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
  </div>
</template>
