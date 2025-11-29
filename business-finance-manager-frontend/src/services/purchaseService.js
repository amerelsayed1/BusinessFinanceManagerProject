import api from './api'

export default {
  getAll(params = {}) {
    return api.get('/purchases', { params })
  },
  create(formData) {
    return api.post('/purchases', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },

  update(id, formData) {
    return api.post(`/purchases/${id}?_method=PUT`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },

  delete(id) {
    return api.delete(`/purchases/${id}`)
  },
}
