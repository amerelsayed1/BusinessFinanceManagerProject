import api from './api'

export default {
  getAll(params = {}) {
    return api.get('/incomes', { params })
  },
  create(data) {
    return api.post('/incomes', {
      date: data.date,
      account_id: data.account_id,
      amount: data.amount,
      note: data.note ?? '',
    })
  },
  update(id, data) {
    return api.put(`/incomes/${id}`, {
      date: data.date,
      account_id: data.account_id,
      amount: data.amount,
      note: data.note ?? '',
    })
  },
  delete(id) {
    return api.delete(`/incomes/${id}`)
  },
}
