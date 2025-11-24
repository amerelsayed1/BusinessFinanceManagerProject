// src/services/expenseService.js
import api from './api'

export default {

    fetchMonthlySales () {
        api.get('/monthly-sales')
    },

    // Get all expenses (with optional month filter)
    getAll(month = null) {
        const params = month ? { month } : {}
        return api.get('/expenses', { params })
    },

    // Get single expense
    getById(id) {
        return api.get(`/expenses/${id}`)
    },

    // Create new expense
    create(data) {
        return api.post('/expenses', {
            description: data.description,
            amount: data.amount,
            date: data.date,
            accountId: data.accountId,
            isAds: data.isAds || false,
        })
    },

    // Update expense
    update(id, data) {
        return api.put(`/expenses/${id}`, {
            description: data.description,
            amount: data.amount,
            date: data.date,
            accountId: data.accountId,
            isAds: data.isAds,
        })
    },

    // Delete expense
    delete(id) {
        return api.delete(`/expenses/${id}`)
    },


    // Get expenses total by account
    getByAccount(month = null) {
        const params = month ? { month } : {}
        return api.get('/expenses/by-account', { params })
    },
}

// Composable for Vue 3 (Optional)
export const useExpenseService = () => {
    return {
        getAll: (month = null) => api.get('/expenses', { params: month ? { month } : {} }),
        getById: (id) => api.get(`/expenses/${id}`),
        create: (data) => api.post('/expenses', {
            description: data.description,
            amount: data.amount,
            date: data.date,
            category: data.category || null,
            accountId: data.accountId,
            isAds: data.isAds || false,
        }),
        update: (id, data) => api.put(`/expenses/${id}`, data),
        delete: (id) => api.delete(`/expenses/${id}`),
        getByCategory: (month = null) => api.get('/expenses/by-category', { params: month ? { month } : {} }),
        getByAccount: (month = null) => api.get('/expenses/by-account', { params: month ? { month } : {} }),
    }
}