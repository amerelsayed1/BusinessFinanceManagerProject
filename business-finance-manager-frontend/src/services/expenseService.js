// src/services/expenseService.js
import api from './api'

const buildMonthRange = (monthKey) => {
  if (!monthKey) return null

  const [year, month] = monthKey.split('-').map(Number)
  if (!year || !month) return null

  const startDate = `${monthKey}-01`
  const daysInMonth = new Date(year, month, 0).getDate()
  const endDate = `${monthKey}-${String(daysInMonth).padStart(2, '0')}`

  return { from: startDate, to: endDate }
}

export default {
  getAll(monthKey = null, filters = {}) {
    const params = { ...filters }
    const monthRange = buildMonthRange(monthKey)

    if (monthRange) {
      Object.assign(params, monthRange)
    }

    return api.get('/expenses', { params })
  },

  create(data) {
    return api.post('/expenses', {
      account_id: data.account_id,
      category_id: data.category_id,
      amount: data.amount,
      date: data.date,
      note: data.note ?? '',
    })
  },
}

export const useExpenseService = () => ({
  getAll: (monthKey = null, filters = {}) => {
    const params = { ...filters }
    const monthRange = buildMonthRange(monthKey)

    if (monthRange) {
      Object.assign(params, monthRange)
    }

    return api.get('/expenses', { params })
  },
  create: (data) =>
    api.post('/expenses', {
      account_id: data.account_id,
      category_id: data.category_id,
      amount: data.amount,
      date: data.date,
      note: data.note ?? '',
    }),
})
