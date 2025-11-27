<script setup>
import { ref, watch } from 'vue'
import { Eye, X } from 'lucide-vue-next'

const props = defineProps({
  currency: { type: String, required: true },
  accounts: { type: Array, required: true },
  monthLabel: { type: String, required: true },
  bills: { type: Array, required: true },
  pendingTotal: { type: Number, required: true },
  paidTotal: { type: Number, required: true },
})

const emit = defineEmits([
  'prev-month',
  'next-month',
  'add-bill',
  'update-bill',
  'delete-bill',
  'toggle-status',
  'view-receipt',
])

const form = ref({
  description: '',
  amount: '',
  date: new Date().toISOString().split('T')[0],
  status: 'pending',
  accountId: '',
  image: null,
  isMonthly: false,
})

const showEditModal = ref(false)
const editingBill = ref(null)
const editForm = ref({
  description: '',
  amount: '',
  date: '',
  status: 'pending',
  accountId: '',
  image: null,
  isMonthly: false,
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

const onFileChange = (e) => {
  const file = e.target.files && e.target.files[0]
  if (!file) return
  const reader = new FileReader()
  reader.onloadend = () => {
    form.value.image = reader.result
  }
  reader.readAsDataURL(file)
}

const submitBill = () => {
  emit('add-bill', { ...form.value })
}

const startEditBill = (bill) => {
  editingBill.value = bill
  editForm.value = {
    description: bill.description || '',
    amount: bill.amount,
    date: bill.date,
    status: bill.status,
    accountId: String(bill.account_id ?? bill.accountId ?? ''),
    image: bill.image || null,
    isMonthly: bill.is_monthly ?? bill.isMonthly ?? false,
  }
  showEditModal.value = true
}

const submitEditBill = () => {
  if (!editingBill.value) return
  emit('update-bill', editingBill.value.id, { ...editForm.value })
  showEditModal.value = false
}

const onEditFileChange = (e) => {
  const file = e.target.files && e.target.files[0]
  if (!file) return
  const reader = new FileReader()
  reader.onloadend = () => {
    editForm.value.image = reader.result
  }
  reader.readAsDataURL(file)
}
</script>

<template>
  <div class="bg-white rounded-lg shadow-lg p-4 md:p-6">
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-2xl font-bold mb-0 text-gray-800">Provider Invoices</h2>
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

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
      <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <p class="text-sm text-gray-600">
          Pending Invoices ({{ monthLabel }})
        </p>
        <p class="text-3xl font-bold text-orange-600">
          {{ pendingTotal.toFixed(2) }} {{ currency }}
        </p>
      </div>
      <div class="bg-green-50 border border-green-200 rounded-lg p-4">
        <p class="text-sm text-gray-600">
          Paid Invoices ({{ monthLabel }})
        </p>
        <p class="text-3xl font-bold text-green-600">
          {{ paidTotal.toFixed(2) }} {{ currency }}
        </p>
      </div>
    </div>

    <div class="space-y-4 mb-6 border p-4 rounded-lg bg-gray-50">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <input
          v-model="form.description"
          type="text"
          placeholder="Product / Bill Description"
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
        <select v-model="form.status" class="border rounded px-4 py-2">
          <option value="pending">Pending</option>
          <option value="paid">Paid</option>
        </select>
      </div>

      <label
        class="border rounded px-4 py-2 bg-white cursor-pointer hover:bg-gray-100 flex items-center justify-center"
      >
        <span class="mr-2">📷</span>
        <span>
          {{ form.image ? 'Receipt Selected ✓' : 'Upload Receipt Image' }}
        </span>
        <input
          type="file"
          accept="image/*"
          class="hidden"
          @change="onFileChange"
        />
      </label>

      <div v-if="form.image" class="flex items-center gap-2">
        <img
          :src="form.image"
          alt="Preview"
          class="h-20 w-20 object-cover rounded border"
        />
        <button
          type="button"
          class="text-red-500 hover:text-red-700"
          @click="form.image = null"
        >
          <X :size="20" />
        </button>
      </div>

      <button
        type="button"
        class="w-full bg-blue-500 text-white rounded px-6 py-2 hover:bg-blue-600 font-semibold"
        @click="submitBill"
      >
        Add Invoice
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
            <th class="text-left p-3 text-sm">Receipt</th>
            <th class="text-left p-3 text-sm">Status</th>
            <th class="text-left p-3 text-sm">Action</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="bill in bills"
            :key="bill.id"
            class="border-b hover:bg-gray-50"
          >
            <td class="p-3">{{ bill.description }}</td>
            <td class="p-3">
              {{ Number(bill.amount || 0).toFixed(2) }} {{ currency }}
            </td>
            <td class="p-3">{{ bill.date }}</td>
            <td class="p-3 text-sm text-blue-600">
              {{
                (accounts.find(a => a.id === Number(bill.account_id ?? bill.accountId)) || {}).name
                  || 'Unknown'
              }}
            </td>
            <td class="p-3">
              <button
                v-if="bill.image"
                type="button"
                class="text-blue-500 hover:text-blue-700 flex items-center text-sm"
                @click="emit('view-receipt', bill.image)"
              >
                <Eye :size="16" class="mr-1" /> View
              </button>
              <span v-else class="text-gray-400 text-xs">No image</span>
            </td>
            <td class="p-3">
              <span
                class="px-3 py-1 rounded-full text-xs cursor-pointer"
                :class="bill.status === 'paid'
                  ? 'bg-green-100 text-green-800'
                  : 'bg-yellow-100 text-yellow-800'"
                @click="emit('toggle-status', bill)"
              >
                {{ bill.status }}
              </span>
            </td>
            <td class="p-3">
              <button
                type="button"
                class="text-indigo-500 hover:text-indigo-700 text-sm mr-3"
                @click="startEditBill(bill)"
              >
                Edit
              </button>
              <button
                v-if="bill.image"
                type="button"
                class="text-blue-500 hover:text-blue-700 text-sm mr-3"
                @click="emit('view-receipt', bill.image)"
              >
                Show Invoice Image
              </button>
              <button
                type="button"
                class="text-red-500 hover:text-red-700 text-sm"
                @click="emit('delete-bill', bill)"
              >
                Delete
              </button>
            </td>
          </tr>
        </tbody>
      </table>

      <p
        v-if="bills.length === 0"
        class="text-center text-gray-500 py-6 text-sm"
      >
        No invoices for this month.
      </p>
    </div>

    <div
      v-if="showEditModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
      @click.self="showEditModal = false"
    >
      <div class="bg-white rounded-lg p-6 max-w-2xl w-full max-h-[90vh] overflow-auto">
        <h3 class="text-xl font-bold mb-4">Edit Invoice</h3>
        <div class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <input
              v-model="editForm.description"
              type="text"
              placeholder="Description"
              class="border rounded px-4 py-2"
            />
            <input
              v-model="editForm.amount"
              type="number"
              placeholder="Amount"
              class="border rounded px-4 py-2"
            />
          </div>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <input
              v-model="editForm.date"
              type="date"
              class="border rounded px-4 py-2"
            />
            <select v-model="editForm.accountId" class="border rounded px-4 py-2">
              <option
                v-for="acc in accounts"
                :key="acc.id"
                :value="acc.id.toString()"
              >
                {{ acc.name }}
              </option>
            </select>
            <select v-model="editForm.status" class="border rounded px-4 py-2">
              <option value="pending">Pending</option>
              <option value="paid">Paid</option>
            </select>
          </div>

          <label
            class="border rounded px-4 py-2 bg-white cursor-pointer hover:bg-gray-100 flex items-center justify-center"
          >
            <span class="mr-2">📷</span>
            <span>
              {{ editForm.image ? 'Receipt Selected ✓' : 'Upload Receipt Image' }}
            </span>
            <input
              type="file"
              accept="image/*"
              class="hidden"
              @change="onEditFileChange"
            />
          </label>

          <div v-if="editForm.image" class="flex items-center gap-2">
            <img
              :src="editForm.image"
              alt="Preview"
              class="h-20 w-20 object-cover rounded border"
            />
            <button
              type="button"
              class="text-red-500 hover:text-red-700"
              @click="editForm.image = null"
            >
              <X :size="20" />
            </button>
          </div>

          <div class="flex items-center gap-2">
            <input
              id="isMonthlyEdit"
              v-model="editForm.isMonthly"
              type="checkbox"
              class="h-4 w-4"
            />
            <label for="isMonthlyEdit" class="text-sm text-gray-700">
              Monthly invoice
            </label>
          </div>

          <div class="flex gap-2">
            <button
              type="button"
              class="flex-1 bg-blue-600 text-white rounded px-4 py-2 hover:bg-blue-700"
              @click="submitEditBill"
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
