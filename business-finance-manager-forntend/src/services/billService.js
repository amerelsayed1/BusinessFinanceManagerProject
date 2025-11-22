// src/services/billService.js
import api from './api'

export default {
    // Get all bills (with optional status filter)
    getAll(status = null) {
        const params = status ? { status } : {}
        return api.get('/bills', { params })
    },

    // Get single bill
    getById(id) {
        return api.get(`/bills/${id}`)
    },

    // Create new bill
    create(data) {
        return api.post('/bills', {
            description: data.description,
            amount: data.amount,
            date: data.date,
            status: data.status || 'pending',
            accountId: data.accountId,
            image: data.image || null,
            isMonthly: data.isMonthly || false,
        })
    },

    // Update bill
    update(id, data) {
        return api.put(`/bills/${id}`, {
            description: data.description,
            amount: data.amount,
            date: data.date,
            status: data.status,
            accountId: data.accountId,
            image: data.image,
            isMonthly: data.isMonthly,
        })
    },

    // Delete bill
    delete(id) {
        return api.delete(`/bills/${id}`)
    },

    // Update bill status only
    updateStatus(id, status) {
        return api.patch(`/bills/${id}/status`, {
            status: status, // 'pending' or 'paid'
        })
    },

    // Get pending bills
    getPending() {
        return api.get('/bills/pending')
    },

    // Get bills total by status
    getTotals() {
        return api.get('/bills/totals')
    },

    // Mark bill as paid
    markAsPaid(id) {
        return this.updateStatus(id, 'paid')
    },

    // Mark bill as pending
    markAsPending(id) {
        return this.updateStatus(id, 'pending')
    },
}

// Composable for Vue 3 (Optional)
export const useBillService = () => {
    return {
        getAll: (status = null) => api.get('/bills', { params: status ? { status } : {} }),
        getById: (id) => api.get(`/bills/${id}`),
        create: (data) => api.post('/bills', {
            description: data.description,
            amount: data.amount,
            date: data.date,
            status: data.status || 'pending',
            accountId: data.accountId,
            image: data.image || null,
            isMonthly: data.isMonthly || false,
        }),
        update: (id, data) => api.put(`/bills/${id}`, data),
        delete: (id) => api.delete(`/bills/${id}`),
        updateStatus: (id, status) => api.patch(`/bills/${id}/status`, { status }),
        getPending: () => api.get('/bills/pending'),
        getTotals: () => api.get('/bills/totals'),
        markAsPaid: (id) => api.patch(`/bills/${id}/status`, { status: 'paid' }),
        markAsPending: (id) => api.patch(`/bills/${id}/status`, { status: 'pending' }),
    }
}