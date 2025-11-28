<script setup>
import { computed, onMounted, ref } from 'vue'
import purchaseService from '../services/purchaseService'
import accountService from '../services/accountService'
import { useStore } from 'vuex'

const store = useStore()
const currency = computed(() => store.getters['auth/defaultCurrency'])

const purchases = ref([])
const accounts = ref([])
const loading = ref(false)
const error = ref('')

const filters = ref({
  from: '',
  to: '',
  supplier_name: '',
})

const form = ref({
  date: new Date().toISOString().slice(0, 10),
  account_id: '',
  supplier_name: '',
  reference: '',
  total_amount: '',
  note: '',
  invoice_image: null,
})

const editId = ref(null)
const editForm = ref({
  date: '',
  account_id: '',
  supplier_name: '',
  reference: '',
  total_amount: '',
  note: '',
  invoice_image: null,
  invoice_image_url: '',
})

const loadAccounts = async () => {
  const response = await accountService.getAll()
  accounts.value = response.data
  if (accounts.value.length && !form.value.account_id) {
    form.value.account_id = accounts.value[0].id
  }
}

const loadPurchases = async () => {
  loading.value = true
  error.value = ''
  try {
    const response = await purchaseService.getAll({
      ...filters.value,
    })
    purchases.value = response.data
  } catch (e) {
    error.value = e.response?.data?.message || 'Failed to load purchases.'
  } finally {
    loading.value = false
  }
}

const handleFileChange = (event) => {
  const [file] = event.target.files
  form.value.invoice_image = file || null
}

const submitPurchase = async () => {
  const data = new FormData()
  Object.entries(form.value).forEach(([key, value]) => {
    if (value !== null && value !== '') {
      data.append(key, value)
    }
  })

  try {
    await purchaseService.create(data)
    await loadPurchases()
    form.value = {
      date: new Date().toISOString().slice(0, 10),
      account_id: accounts.value[0]?.id || '',
      supplier_name: '',
      reference: '',
      total_amount: '',
      note: '',
      invoice_image: null,
    }
    window.alert('Purchase added successfully')
  } catch (e) {
    error.value = e.response?.data?.message || 'Could not create purchase.'
  }
}

const startEdit = (purchase) => {
  editId.value = purchase.id
  editForm.value = {
    date: purchase.date,
    account_id: purchase.account_id,
    supplier_name: purchase.supplier_name || '',
    reference: purchase.reference || '',
    total_amount: purchase.total_amount,
    note: purchase.note || '',
    invoice_image: null,
    invoice_image_url: purchase.invoice_image_url || '',
  }
}

const handleEditFileChange = (event) => {
  const [file] = event.target.files
  editForm.value.invoice_image = file || null
}

const cancelEdit = () => {
  editId.value = null
  editForm.value.invoice_image = null
}

const updatePurchase = async () => {
  if (!editId.value) return
  const data = new FormData()
  Object.entries(editForm.value).forEach(([key, value]) => {
    if (key === 'invoice_image_url') return
    if (value !== null && value !== '') {
      data.append(key, value)
    }
  })

  try {
    await purchaseService.update(editId.value, data)
    await loadPurchases()
    editId.value = null
    window.alert('Purchase updated successfully')
  } catch (e) {
    error.value = e.response?.data?.message || 'Could not update purchase.'
  }
}

const deletePurchase = async (id) => {
  if (!confirm('Are you sure you want to delete this purchase?')) return
  try {
    await purchaseService.delete(id)
    purchases.value = purchases.value.filter((p) => p.id !== id)
    window.alert('Purchase deleted successfully')
  } catch (e) {
    error.value = e.response?.data?.message || 'Could not delete purchase.'
  }
}

onMounted(async () => {
  await loadAccounts()
  loadPurchases()
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-semibold text-gray-900">Purchases</h1>
        <p class="text-sm text-gray-500">Record supplier purchases and invoice images.</p>
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
          <label class="text-sm text-gray-700">Supplier</label>
          <input v-model="filters.supplier_name" type="text" class="border rounded px-3 py-2" />
        </div>
        <div class="flex items-end">
          <button class="bg-blue-600 text-white px-4 py-2 rounded" @click="loadPurchases">Apply</button>
        </div>
      </div>
    </div>

    <div class="bg-white p-4 rounded-lg shadow-sm border space-y-3">
      <h2 class="text-lg font-semibold">Add Purchase</h2>
      <div class="grid gap-3 md:grid-cols-2">
        <div class="flex flex-col gap-1">
          <label class="text-sm text-gray-700">Date</label>
          <input v-model="form.date" type="date" class="border rounded px-3 py-2" />
        </div>
        <div class="flex flex-col gap-1">
          <label class="text-sm text-gray-700">Account</label>
          <select v-model="form.account_id" class="border rounded px-3 py-2">
            <option v-for="acc in accounts" :key="acc.id" :value="acc.id">{{ acc.name }}</option>
          </select>
        </div>
        <div class="flex flex-col gap-1">
          <label class="text-sm text-gray-700">Supplier Name</label>
          <input v-model="form.supplier_name" type="text" class="border rounded px-3 py-2" />
        </div>
        <div class="flex flex-col gap-1">
          <label class="text-sm text-gray-700">Reference</label>
          <input v-model="form.reference" type="text" class="border rounded px-3 py-2" />
        </div>
        <div class="flex flex-col gap-1">
          <label class="text-sm text-gray-700">Total Amount</label>
          <input v-model.number="form.total_amount" type="number" min="0" class="border rounded px-3 py-2" />
        </div>
        <div class="flex flex-col gap-1">
          <label class="text-sm text-gray-700">Invoice Image</label>
          <input type="file" accept="image/*" @change="handleFileChange" class="border rounded px-3 py-2" />
        </div>
        <div class="md:col-span-2 flex flex-col gap-1">
          <label class="text-sm text-gray-700">Note</label>
          <textarea v-model="form.note" rows="2" class="border rounded px-3 py-2"></textarea>
        </div>
      </div>
      <div>
        <button class="bg-green-600 text-white px-4 py-2 rounded" @click="submitPurchase">Save Purchase</button>
      </div>
    </div>

    <div class="bg-white p-4 rounded-lg shadow-sm border">
      <div class="flex items-center justify-between mb-3">
        <h2 class="text-lg font-semibold">Purchase List</h2>
        <span class="text-sm text-gray-600">{{ purchases.length }} items</span>
      </div>
      <div v-if="loading" class="text-gray-600">Loading purchases...</div>
      <div v-else class="overflow-x-auto">
        <table class="w-full text-left">
          <thead>
            <tr class="bg-gray-100 text-sm">
              <th class="p-2">Date</th>
              <th class="p-2">Supplier</th>
              <th class="p-2">Reference</th>
              <th class="p-2">Account</th>
              <th class="p-2">Amount</th>
              <th class="p-2">Invoice</th>
              <th class="p-2 text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in purchases" :key="item.id" class="border-b align-top">
              <template v-if="editId === item.id">
                <td class="p-2">
                  <input v-model="editForm.date" type="date" class="border rounded px-2 py-1 w-full" />
                </td>
                <td class="p-2">
                  <input v-model="editForm.supplier_name" type="text" class="border rounded px-2 py-1 w-full" />
                </td>
                <td class="p-2">
                  <input v-model="editForm.reference" type="text" class="border rounded px-2 py-1 w-full" />
                </td>
                <td class="p-2">
                  <select v-model="editForm.account_id" class="border rounded px-2 py-1 w-full">
                    <option v-for="acc in accounts" :key="acc.id" :value="acc.id">{{ acc.name }}</option>
                  </select>
                </td>
                <td class="p-2">
                  <input v-model.number="editForm.total_amount" type="number" min="0" class="border rounded px-2 py-1 w-full" />
                </td>
                <td class="p-2 space-y-2">
                  <div class="text-sm text-gray-600" v-if="editForm.invoice_image_url">
                    <a :href="editForm.invoice_image_url" target="_blank" class="text-blue-600 underline" rel="noopener">Current</a>
                  </div>
                  <input type="file" accept="image/*" @change="handleEditFileChange" class="border rounded px-2 py-1 w-full" />
                </td>
                <td class="p-2 text-right space-x-2 whitespace-nowrap">
                  <button class="text-sm text-green-700" @click="updatePurchase">Save</button>
                  <button class="text-sm text-gray-600" @click="cancelEdit">Cancel</button>
                </td>
              </template>
              <template v-else>
                <td class="p-2">{{ item.date }}</td>
                <td class="p-2">{{ item.supplier_name || '-' }}</td>
                <td class="p-2">{{ item.reference || '-' }}</td>
                <td class="p-2">{{ item.account?.name || accounts.find(a => a.id === item.account_id)?.name }}</td>
                <td class="p-2 font-semibold">{{ Number(item.total_amount || 0).toFixed(2) }} {{ currency }}</td>
                <td class="p-2">
                  <a
                    v-if="item.invoice_image_url"
                    :href="item.invoice_image_url"
                    target="_blank"
                    class="text-blue-600 underline"
                    rel="noopener"
                  >
                    View invoice
                  </a>
                  <span v-else class="text-gray-500 text-sm">No file</span>
                </td>
                <td class="p-2 text-right space-x-3 whitespace-nowrap">
                  <button class="text-blue-600" @click="startEdit(item)">Edit</button>
                  <button class="text-red-600" @click="deletePurchase(item.id)">Delete</button>
                </td>
              </template>
            </tr>
          </tbody>
        </table>
        <p v-if="!purchases.length" class="text-gray-500 text-sm py-4">No purchases found.</p>
      </div>
    </div>
  </div>
</template>
