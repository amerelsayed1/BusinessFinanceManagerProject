<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-semibold text-gray-900">Monthly Sales &amp; ROI</h1>
        <p class="text-sm text-gray-600">Track revenue, costs, and profitability per month.</p>
      </div>
      <div class="flex items-center gap-3">
        <label class="text-sm text-gray-600">Filter by year</label>
        <input
          v-model="yearFilter"
          type="number"
          min="2000"
          max="2100"
          class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
          @change="loadSales"
        />
        <button
          type="button"
          class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md shadow-sm hover:bg-indigo-700"
          @click="openCreate"
        >
          Add Monthly Entry
        </button>
      </div>
    </div>

    <div v-if="errorMessage" class="rounded-md bg-red-50 p-4 text-sm text-red-700">
      {{ errorMessage }}
    </div>
    <div v-if="successMessage" class="rounded-md bg-green-50 p-4 text-sm text-green-700">
      {{ successMessage }}
    </div>

    <div class="bg-white shadow-sm rounded-lg overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Month</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Total Sales</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Total Cost</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Profit</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">ROI %</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Breakdown</th>
              <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 bg-white">
            <tr v-for="sale in sales" :key="sale.id">
              <td class="px-4 py-3 text-sm text-gray-900">{{ formatMonthLabel(sale.month) }}</td>
              <td class="px-4 py-3 text-sm text-gray-900">{{ formatCurrency(sale.total_sales) }}</td>
              <td class="px-4 py-3 text-sm text-gray-900">{{ formatCurrency(sale.total_cost) }}</td>
              <td class="px-4 py-3 text-sm text-gray-900">{{ formatCurrency(sale.profit) }}</td>
              <td class="px-4 py-3 text-sm text-gray-900">{{ formatRoi(sale.roi_percent) }}</td>
              <td class="px-4 py-3 text-sm text-gray-700">
                <div class="space-y-1">
                  <p>Product cost: {{ formatCurrency(sale.product_cost) }}</p>
                  <p>Ads: {{ formatCurrency(sale.ads_expenses) }}</p>
                  <p>Logistics: {{ formatCurrency(sale.logistics_cost) }}</p>
                  <p>Platform fees: {{ formatCurrency(sale.platform_fees) }}</p>
                  <p>Other: {{ formatCurrency(sale.other_expenses) }}</p>
                  <p v-if="sale.notes" class="text-gray-500 text-xs">Notes: {{ sale.notes }}</p>
                </div>
              </td>
              <td class="px-4 py-3 text-right text-sm text-gray-900">
                <div class="flex justify-end gap-2">
                  <button
                    class="px-3 py-1 text-indigo-700 bg-indigo-50 rounded-md hover:bg-indigo-100"
                    @click="startEdit(sale)"
                  >
                    Edit
                  </button>
                  <button
                    class="px-3 py-1 text-red-700 bg-red-50 rounded-md hover:bg-red-100"
                    @click="deleteSale(sale.id)"
                  >
                    Delete
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!sales.length">
              <td colspan="7" class="px-4 py-6 text-center text-sm text-gray-500">
                No monthly sales yet. Add your first record to track ROI.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <ModalDialog v-model="showModal" :title="modalTitle" @close="closeModal">
      <form class="space-y-4" @submit.prevent="saveSale">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">Month</label>
            <input
              v-model="form.month"
              type="month"
              required
              class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            />
            <p v-if="fieldError('month')" class="mt-1 text-xs text-red-600">{{ fieldError('month') }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Total Sales</label>
            <input
              v-model.number="form.total_sales"
              type="number"
              min="0"
              step="0.01"
              required
              class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            />
            <p v-if="fieldError('total_sales')" class="mt-1 text-xs text-red-600">{{ fieldError('total_sales') }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Product Cost</label>
            <input
              v-model.number="form.product_cost"
              type="number"
              min="0"
              step="0.01"
              required
              class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            />
            <p v-if="fieldError('product_cost')" class="mt-1 text-xs text-red-600">{{ fieldError('product_cost') }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Ads Expenses</label>
            <input
              v-model.number="form.ads_expenses"
              type="number"
              min="0"
              step="0.01"
              required
              class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            />
            <p v-if="fieldError('ads_expenses')" class="mt-1 text-xs text-red-600">{{ fieldError('ads_expenses') }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Logistics Cost</label>
            <input
              v-model.number="form.logistics_cost"
              type="number"
              min="0"
              step="0.01"
              class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            />
            <p v-if="fieldError('logistics_cost')" class="mt-1 text-xs text-red-600">{{ fieldError('logistics_cost') }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Platform Fees</label>
            <input
              v-model.number="form.platform_fees"
              type="number"
              min="0"
              step="0.01"
              class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            />
            <p v-if="fieldError('platform_fees')" class="mt-1 text-xs text-red-600">{{ fieldError('platform_fees') }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Other Expenses</label>
            <input
              v-model.number="form.other_expenses"
              type="number"
              min="0"
              step="0.01"
              class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            />
            <p v-if="fieldError('other_expenses')" class="mt-1 text-xs text-red-600">{{ fieldError('other_expenses') }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Notes</label>
            <textarea
              v-model="form.notes"
              rows="3"
              class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            />
            <p v-if="fieldError('notes')" class="mt-1 text-xs text-red-600">{{ fieldError('notes') }}</p>
          </div>
        </div>

        <div class="flex justify-end gap-2 pt-2">
          <button
            type="button"
            class="px-4 py-2 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50"
            @click="closeModal"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="saving"
            class="px-4 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700 disabled:opacity-50"
          >
            {{ saving ? 'Saving...' : 'Save' }}
          </button>
        </div>
      </form>
    </ModalDialog>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import monthlySalesService from '../services/monthlySalesService'
import ModalDialog from '../components/ModalDialog.vue'

const sales = ref([])
const loading = ref(false)
const saving = ref(false)
const showModal = ref(false)
const editingSale = ref(null)
const errorMessage = ref('')
const successMessage = ref('')
const validationErrors = ref({})
const yearFilter = ref(new Date().getFullYear())

const defaultForm = () => ({
  month: new Date().toISOString().slice(0, 7),
  total_sales: 0,
  product_cost: 0,
  ads_expenses: 0,
  logistics_cost: 0,
  platform_fees: 0,
  other_expenses: 0,
  notes: '',
})

const form = ref(defaultForm())

const modalTitle = computed(() => (editingSale.value ? 'Edit Monthly Sales' : 'Add Monthly Sales'))

const normalizeMonthPayload = (monthValue) => {
  if (!monthValue) return null
  return monthValue.length === 7 ? `${monthValue}-01` : monthValue
}

const loadSales = async () => {
  loading.value = true
  errorMessage.value = ''
  validationErrors.value = {}
  try {
    const response = await monthlySalesService.list({ year: yearFilter.value })
    sales.value = response.data?.data || response.data || []
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Failed to load monthly sales.'
  } finally {
    loading.value = false
  }
}

const openCreate = () => {
  editingSale.value = null
  form.value = defaultForm()
  validationErrors.value = {}
  showModal.value = true
}

const startEdit = (sale) => {
  editingSale.value = sale
  form.value = {
    month: sale.month ? sale.month.slice(0, 7) : new Date().toISOString().slice(0, 7),
    total_sales: Number(sale.total_sales ?? 0),
    product_cost: Number(sale.product_cost ?? 0),
    ads_expenses: Number(sale.ads_expenses ?? 0),
    logistics_cost: Number(sale.logistics_cost ?? 0),
    platform_fees: Number(sale.platform_fees ?? 0),
    other_expenses: Number(sale.other_expenses ?? 0),
    notes: sale.notes || '',
  }
  validationErrors.value = {}
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  editingSale.value = null
  form.value = defaultForm()
  validationErrors.value = {}
}

const saveSale = async () => {
  saving.value = true
  errorMessage.value = ''
  successMessage.value = ''
  validationErrors.value = {}
  const payload = {
    ...form.value,
    month: normalizeMonthPayload(form.value.month),
  }

  try {
    if (editingSale.value) {
      await monthlySalesService.update(editingSale.value.id, payload)
      successMessage.value = 'Monthly sales updated successfully.'
    } else {
      await monthlySalesService.create(payload)
      successMessage.value = 'Monthly sales saved successfully.'
    }
    await loadSales()
    closeModal()
  } catch (error) {
    if (error.response?.status === 422) {
      validationErrors.value = error.response.data.errors || {}
      errorMessage.value = 'Please fix the highlighted errors.'
    } else {
      errorMessage.value = error.response?.data?.message || 'Failed to save monthly sales.'
    }
  } finally {
    saving.value = false
  }
}

const deleteSale = async (id) => {
  if (!confirm('Are you sure you want to delete this monthly sales record?')) return
  errorMessage.value = ''
  successMessage.value = ''
  try {
    await monthlySalesService.destroy(id)
    successMessage.value = 'Monthly sales deleted successfully.'
    await loadSales()
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Failed to delete monthly sales.'
  }
}

const formatCurrency = (value) => {
  const amount = Number(value ?? 0)
  return `${amount.toFixed(2)}`
}

const formatRoi = (value) => `${Number(value ?? 0).toFixed(2)} %`

const formatMonthLabel = (value) => {
  if (!value) return '-'
  const date = new Date(value)
  return date.toLocaleDateString(undefined, { month: 'long', year: 'numeric' })
}

const fieldError = (field) => validationErrors.value?.[field]?.[0]

onMounted(loadSales)
</script>
