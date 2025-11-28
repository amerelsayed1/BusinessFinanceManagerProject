<script setup>
import { computed, onMounted, ref } from 'vue'
import accountService from '../services/accountService'
import ModalDialog from '../components/ModalDialog.vue'
import { formatDateTime } from '../utils/date'
import { useStore } from 'vuex'

const store = useStore()
const currency = computed(() => store.getters['auth/defaultCurrency'])

const transfers = ref([])
const accounts = ref([])
const showCreateModal = ref(false)
const showEditModal = ref(false)
const submitting = ref(false)
const error = ref('')
const success = ref('')

const form = ref({
  from_account_id: '',
  to_account_id: '',
  amount: null,
  date: new Date().toISOString().slice(0, 10),
  note: '',
})

const selectedTransfer = ref(null)
const editForm = ref({
  from_account_id: '',
  to_account_id: '',
  amount: null,
  date: '',
  note: '',
})

const loadAccounts = async () => {
  const response = await accountService.getAll()
  accounts.value = response.data
}

const loadTransfers = async () => {
  try {
    const response = await accountService.listTransfers()
    const payload = response.data?.data ?? response.data ?? []
    transfers.value = Array.isArray(payload) ? payload : []
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
  success.value = ''
  try {
    await accountService.transfer(
      form.value.from_account_id,
      form.value.to_account_id,
      form.value.amount,
      form.value.date,
      form.value.note,
    )
    await Promise.all([loadTransfers(), loadAccounts()])
    closeCreateModal()
    success.value = 'Transfer created successfully.'
  } catch (e) {
    error.value = e.response?.data?.message || 'Failed to create transfer.'
  } finally {
    submitting.value = false
  }
}

const closeCreateModal = () => {
  showCreateModal.value = false
  success.value = ''
  form.value = {
    from_account_id: '',
    to_account_id: '',
    amount: null,
    date: new Date().toISOString().slice(0, 10),
    note: '',
  }
}

const startEdit = (transfer) => {
  selectedTransfer.value = transfer
  editForm.value = {
    from_account_id: transfer.from_account_id,
    to_account_id: transfer.to_account_id,
    amount: transfer.amount,
    date: transfer.date,
    note: transfer.note || '',
  }
  showEditModal.value = true
}

const closeEdit = () => {
  showEditModal.value = false
  selectedTransfer.value = null
  editForm.value = {
    from_account_id: '',
    to_account_id: '',
    amount: null,
    date: '',
    note: '',
  }
}

const updateTransfer = async () => {
  if (!selectedTransfer.value) return
  submitting.value = true
  error.value = ''
  try {
    await accountService.updateTransfer(selectedTransfer.value.id, {
      from_account_id: editForm.value.from_account_id,
      to_account_id: editForm.value.to_account_id,
      amount: editForm.value.amount,
      date: editForm.value.date,
      note: editForm.value.note,
    })
    await Promise.all([loadTransfers(), loadAccounts()])
    closeEdit()
    success.value = 'Transfer updated successfully.'
  } catch (e) {
    error.value = e.response?.data?.message || 'Failed to update transfer.'
  } finally {
    submitting.value = false
  }
}

const deleteTransfer = async (id) => {
  if (!confirm('Are you sure you want to delete this transfer?')) return
  submitting.value = true
  error.value = ''
  success.value = ''
  try {
    await accountService.deleteTransfer(id)
    await Promise.all([loadTransfers(), loadAccounts()])
    success.value = 'Transfer deleted successfully.'
  } catch (e) {
    error.value = e.response?.data?.message || 'Failed to delete transfer.'
  } finally {
    submitting.value = false
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
      <button class="bg-blue-600 text-white px-4 py-2 rounded" @click="showCreateModal = true">New Transfer</button>
    </div>

    <div v-if="error" class="p-3 bg-red-100 text-red-700 rounded">{{ error }}</div>
    <div v-if="success" class="p-3 bg-green-100 text-green-700 rounded">{{ success }}</div>

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
              <th class="p-2 text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in transfers" :key="item.id" class="border-b">
              <td class="p-2">{{ formatDateTime(item.date) }}</td>
              <td class="p-2">{{ item.from_account?.name || accounts.find(a => a.id === item.from_account_id)?.name }}</td>
              <td class="p-2">{{ item.to_account?.name || accounts.find(a => a.id === item.to_account_id)?.name }}</td>
              <td class="p-2 font-semibold">{{ Number(item.amount || 0).toFixed(2) }} {{ currency }}</td>
              <td class="p-2 text-sm text-gray-600">{{ item.note || '-' }}</td>
              <td class="p-2 text-right space-x-3 whitespace-nowrap">
                <button class="text-blue-600" @click="startEdit(item)">Edit</button>
                <button class="text-red-600" @click="deleteTransfer(item.id)">Delete</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <ModalDialog v-model:modelValue="showCreateModal" title="New Transfer" @close="closeCreateModal">
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
      <template #footer>
        <button class="px-4 py-2 border rounded" @click="closeCreateModal">Cancel</button>
        <button class="px-4 py-2 bg-blue-600 text-white rounded" :disabled="submitting" @click="createTransfer">
          {{ submitting ? 'Saving...' : 'Save' }}
        </button>
      </template>
    </ModalDialog>

    <ModalDialog v-model:modelValue="showEditModal" title="Edit Transfer" @close="closeEdit">
      <div class="space-y-3">
        <div class="flex flex-col gap-1">
          <label class="text-sm text-gray-700">From account</label>
          <select v-model="editForm.from_account_id" class="border rounded px-3 py-2">
            <option value="">Select account</option>
            <option
              v-for="acc in accounts"
              :key="acc.id"
              :value="acc.id"
              :disabled="acc.id === editForm.to_account_id"
            >
              {{ acc.name }}
            </option>
          </select>
        </div>
        <div class="flex flex-col gap-1">
          <label class="text-sm text-gray-700">To account</label>
          <select v-model="editForm.to_account_id" class="border rounded px-3 py-2">
            <option value="">Select account</option>
            <option
              v-for="acc in accounts"
              :key="acc.id"
              :value="acc.id"
              :disabled="acc.id === editForm.from_account_id"
            >
              {{ acc.name }}
            </option>
          </select>
        </div>
        <div class="flex flex-col gap-1">
          <label class="text-sm text-gray-700">Amount</label>
          <input v-model.number="editForm.amount" type="number" min="0" class="border rounded px-3 py-2" />
        </div>
        <div class="flex flex-col gap-1">
          <label class="text-sm text-gray-700">Date</label>
          <input v-model="editForm.date" type="date" class="border rounded px-3 py-2" />
        </div>
        <div class="flex flex-col gap-1">
          <label class="text-sm text-gray-700">Note</label>
          <textarea v-model="editForm.note" rows="2" class="border rounded px-3 py-2"></textarea>
        </div>
      </div>
      <template #footer>
        <button class="px-4 py-2 border rounded" @click="closeEdit">Cancel</button>
        <button class="px-4 py-2 bg-blue-600 text-white rounded" :disabled="submitting" @click="updateTransfer">
          {{ submitting ? 'Saving...' : 'Save' }}
        </button>
      </template>
    </ModalDialog>
  </div>
</template>
