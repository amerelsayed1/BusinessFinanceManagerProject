<script setup>
import { ref } from 'vue'

const props = defineProps({
  currency: { type: String, required: true },
  accounts: { type: Array, required: true },
})

const emit = defineEmits(['create-account', 'delete-account', 'deposit'])

const newAccountName = ref('')
const newAccountInitialBalance = ref('')

const depositAmounts = ref({})

const submitAccount = () => {
  const name = newAccountName.value.trim()
  if (!name) {
    alert('Please enter account name.')
    return
  }

  emit('create-account', {
    name,
    initialBalance: newAccountInitialBalance.value,
  })

  newAccountName.value = ''
  newAccountInitialBalance.value = ''
}

const submitDeposit = (accountId) => {
  const raw = depositAmounts.value[accountId] ?? ''
  if (!raw) return

  emit('deposit', { accountId, amount: raw })
  depositAmounts.value[accountId] = ''
}
</script>

<template>
  <div class="space-y-6">
    <div class="bg-white rounded-lg shadow-lg p-4 md:p-6">
      <h2 class="text-xl font-bold text-gray-800 mb-4">Add Account</h2>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <input
          v-model="newAccountName"
          type="text"
          placeholder="Account name (e.g. Alahly Bank)"
          class="border rounded px-4 py-2"
        />
        <input
          v-model="newAccountInitialBalance"
          type="number"
          placeholder="Initial balance (optional)"
          class="border rounded px-4 py-2"
        />
        <button
          type="button"
          class="bg-blue-600 text-white rounded px-4 py-2 font-semibold hover:bg-blue-700"
          @click="submitAccount"
        >
          Add Account
        </button>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6">
      <div
        v-for="acc in accounts"
        :key="acc.id"
        class="bg-white rounded-lg shadow-lg p-6"
      >
        <div class="flex items-center justify-between mb-4">
          <div>
            <h3 class="text-lg font-bold text-gray-800">{{ acc.name }}</h3>
            <p class="text-3xl font-bold text-blue-600 mt-2">
              {{ Number(acc.balance || 0).toFixed(2) }} {{ currency }}
            </p>
          </div>
          <button
            type="button"
            class="text-red-500 text-xs hover:text-red-700"
            @click="emit('delete-account', acc)"
          >
            Delete
          </button>
        </div>

        <div class="mt-4">
          <label class="text-sm text-gray-600 mb-2 block">Add Deposit</label>
          <div class="flex gap-2">
            <input
              v-model="depositAmounts[acc.id]"
              type="number"
              placeholder="Amount"
              class="border rounded px-3 py-2 flex-1"
            />
            <button
              type="button"
              class="bg-green-500 text-white rounded px-4 py-2 hover:bg-green-600 text-sm font-semibold"
              @click="submitDeposit(acc.id)"
            >
              Add
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
