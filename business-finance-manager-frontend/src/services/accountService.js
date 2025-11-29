// src/services/accountService.js
import api from './api'

export default {
  getAll(params = {}) {
    return api.get('/accounts', { params })
  },

  getById(id) {
    return api.get(`/accounts/${id}`)
  },

  getBalance(id) {
    return api.get(`/accounts/${id}/balance`)
  },

  create(data) {
    return api.post('/accounts', {
      name: data.name,
      type: data.type,
      opening_balance: data.opening_balance ?? 0,
    })
  },

  update(id, data) {
    return api.put(`/accounts/${id}`, {
      name: data.name,
      type: data.type,
      opening_balance: data.opening_balance,
    })
  },

  delete(id) {
    return api.delete(`/accounts/${id}`)
  },

  transfer(fromAccountId, toAccountId, amount, date, note = '') {
    return api.post('/accounts/transfers', {
      from_account_id: fromAccountId,
      to_account_id: toAccountId,
      amount,
      date,
      note,
    })
  },

  listTransfers(params = {}) {
    return api.get('/accounts/transfers', { params })
  },

  updateTransfer(id, data) {
    return api.put(`/accounts/transfers/${id}`, data)
  },

  deleteTransfer(id) {
    return api.delete(`/accounts/transfers/${id}`)
  },
}
