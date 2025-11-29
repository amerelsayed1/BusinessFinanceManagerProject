import api from './api'

export default {
  list(params = {}) {
    return api.get('/monthly-sales', { params })
  },
  create(data) {
    return api.post('/monthly-sales', data)
  },
  update(id, data) {
    return api.put(`/monthly-sales/${id}`, data)
  },
  destroy(id) {
    return api.delete(`/monthly-sales/${id}`)
  },
}
