<script setup>
import { PlusCircle } from 'lucide-vue-next'

const props = defineProps({
  currency: { type: String, required: true },
  totalBalance: { type: Number, required: true },
  currentMonthLabel: { type: String, required: true },
  totalExpensesThisMonth: { type: Number, required: true },
  pendingInvoicesThisMonth: { type: Number, required: true },
  paidInvoicesThisMonth: { type: Number, required: true },
})

const emit = defineEmits(['open-add-balance'])
</script>

<template>
  <div>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-6 mb-6 md:mb-8">
      <button
        type="button"
        class="bg-white rounded-lg shadow-lg p-4 md:p-6 border-l-4 border-green-500 flex items-center justify-between gap-4 text-left"
        @click="emit('open-add-balance')"
      >
        <div>
          <p class="text-gray-500 text-xs md:text-sm">Total Balance</p>
          <p class="text-xl md:text-3xl font-bold text-gray-800">
            {{ totalBalance.toFixed(2) }} {{ currency }}
          </p>
          <p class="text-xs text-gray-400 mt-1">
            Click to add balance to any account
          </p>
        </div>
        <PlusCircle class="text-green-500 flex-shrink-0" :size="28" />
      </button>

      <div class="bg-white rounded-lg shadow-lg p-4 md:p-6 border-l-4 border-red-500">
        <p class="text-gray-500 text-xs md:text-sm">
          Expenses ({{ currentMonthLabel }})
        </p>
        <p class="text-xl md:text-3xl font-bold text-gray-800">
          {{ totalExpensesThisMonth.toFixed(2) }} {{ currency }}
        </p>
      </div>

      <div class="bg-white rounded-lg shadow-lg p-4 md:p-6 border-l-4 border-orange-500">
        <p class="text-gray-500 text-xs md:text-sm">
          Pending Invoices ({{ currentMonthLabel }})
        </p>
        <p class="text-xl md:text-3xl font-bold text-gray-800">
          {{ pendingInvoicesThisMonth.toFixed(2) }} {{ currency }}
        </p>
      </div>

      <div class="bg-white rounded-lg shadow-lg p-4 md:p-6 border-l-4 border-blue-500">
        <p class="text-gray-500 text-xs md:text-sm">
          Paid Invoices ({{ currentMonthLabel }})
        </p>
        <p class="text-xl md:text-3xl font-bold text-gray-800">
          {{ paidInvoicesThisMonth.toFixed(2) }} {{ currency }}
        </p>
      </div>
    </div>
  </div>
</template>
