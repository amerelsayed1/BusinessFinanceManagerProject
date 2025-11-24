// src/services/expenseService.js
import api from './api'

const buildMonthRange = (monthKey) => {
  if (!monthKey) return null

  const [year, month] = monthKey.split('-').map(Number)
  if (!year || !month) return null

  const startDate = `${monthKey}-01`
  const daysInMonth = new Date(year, month, 0).getDate()
  const endDate = `${monthKey}-${String(daysInMonth).padStart(2, '0')}`

  return { start_date: startDate, end_date: endDate }
}

export default {
  // Get all expenses (optionally filtered by month/category/account)
  getAll(monthKey = null, filters = {}) {
    const params = { ...filters }
    const monthRange = buildMonthRange(monthKey)

    if (monthRange) {
      Object.assign(params, monthRange)
    }

    return api.get('/expenses', { params })
  },

  // Get single expense
  getById(id) {
    return api.get(`/expenses/${id}`)
  },

  // Create new expense
  create(data) {
    return api.post('/expenses', {
      account_id: data.accountId,
      category_id: data.categoryId ?? null,
      amount: data.amount,
      date: data.date,
      description: data.description ?? '',
    })
  },

  // Update an existing expense
  update(id, data) {
    return api.put(`/expenses/${id}`, {
      account_id: data.accountId,
      category_id: data.categoryId ?? null,
      amount: data.amount,
      date: data.date,
      description: data.description ?? '',
    })
  },

  // Delete expense
  delete(id) {
    return api.delete(`/expenses/${id}`)
  },
}

// Composable for Vue 3 (Optional)
export const useExpenseService = () => {
  return {
    getAll: (monthKey = null, filters = {}) => {
      const params = { ...filters }
      const monthRange = buildMonthRange(monthKey)

      if (monthRange) {
        Object.assign(params, monthRange)
      }

      return api.get('/expenses', { params })
    },
    getById: (id) => api.get(`/expenses/${id}`),
    create: (data) =>
      api.post('/expenses', {
        account_id: data.accountId,
        category_id: data.categoryId ?? null,
        amount: data.amount,
        date: data.date,
        description: data.description ?? '',
      }),
    update: (id, data) =>
      api.put(`/expenses/${id}`, {
        account_id: data.accountId,
        category_id: data.categoryId ?? null,
        amount: data.amount,
        date: data.date,
        description: data.description ?? '',
      }),
    delete: (id) => api.delete(`/expenses/${id}`),
  }
}
