import api from './api'

export default {
  getAll() {
    return api.get('/expense-categories')
  },
  create(name) {
    return api.post('/expense-categories', { name })
  },
  update(id, name) {
    return api.put(`/expense-categories/${id}`, { name })
  },
  delete(id) {
    return api.delete(`/expense-categories/${id}`)
  },
}
