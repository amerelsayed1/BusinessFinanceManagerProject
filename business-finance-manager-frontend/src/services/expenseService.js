// src/services/salesService.js
import api from './api'

export default {
    // Get all monthly sales
    getAll() {
        return api.get('/monthly-sales')
    },

    // Get single month sales
    getById(id) {
        return api.get(`/monthly-sales/${id}`)
    },

    // Create new month sales
    create(data) {
        return api.post('/monthly-sales', {
            month:      data.month,
            year:       data.year,
            totalSales: data.totalSales,
        })
    },

    // Update existing month sales
    update(id, data) {
        return api.put(`/monthly-sales/${id}`, {
            month:      data.month,
            year:       data.year,
            totalSales: data.totalSales,
        })
    },

    // Delete month sales
    delete(id) {
        return api.delete(`/monthly-sales/${id}`)
    },
}

// Composable for Vue 3 (Optional)
export const useSalesService = () => {
    return {
        getAll: () => api.get('/monthly-sales'),
        getById: (id) => api.get(`/monthly-sales/${id}`),
        create: (data) => api.post('/monthly-sales', {
            month:      data.month,
            year:       data.year,
            totalSales: data.totalSales,
        }),
        update: (id, data) => api.put(`/monthly-sales/${id}`, {
            month:      data.month,
            year:       data.year,
            totalSales: data.totalSales,
        }),
        delete: (id) => api.delete(`/monthly-sales/${id}`),
    }
}
