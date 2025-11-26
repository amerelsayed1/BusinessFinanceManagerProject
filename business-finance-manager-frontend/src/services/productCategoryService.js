// src/services/productCategoryService.js
import api from './api'

export default {
  getAll() {
    return api.get('/product-categories')
  },

  create(name) {
    return api.post('/product-categories', { name })
  },

  update(id, name) {
    return api.put(`/product-categories/${id}`, { name })
  },

  delete(id) {
    return api.delete(`/product-categories/${id}`)
  },
}
