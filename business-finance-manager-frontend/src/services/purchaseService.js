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
}
