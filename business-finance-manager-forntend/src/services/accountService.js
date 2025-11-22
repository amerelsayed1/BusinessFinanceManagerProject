// src/services/accountService.js
import api from './api'

export default {
    // Get all accounts
    getAll() {
        return api.get('/accounts')
    },

    // Get single account
    getById(id) {
        return api.get(`/accounts/${id}`)
    },

    // Get account balance
    getBalance(id) {
        return api.get(`/accounts/${id}/balance`)
    },

    // Create new account
    create(data) {
        return api.post('/accounts', {
            name: data.name,
            balance: data.balance || 0,
        })
    },

    // Update account
    update(id, data) {
        return api.put(`/accounts/${id}`, {
            name: data.name,
            balance: data.balance,
        })
    },

    // Delete account
    delete(id) {
        return api.delete(`/accounts/${id}`)
    },

    // Deposit money
    deposit(accountId, amount) {
        return api.post('/accounts/deposit', {
            account_id: accountId,
            amount: amount,
        })
    },

    // Withdraw money
    withdraw(accountId, amount) {
        return api.post('/accounts/withdraw', {
            account_id: accountId,
            amount: amount,
        })
    },

    // Transfer money between accounts
    transfer(fromAccountId, toAccountId, amount) {
        return api.post('/accounts/transfer', {
            from_account_id: fromAccountId,
            to_account_id: toAccountId,
            amount: amount,
        })
    },
}

// Composable for Vue 3 (Optional)
export const useAccountService = () => {
    return {
        getAll: () => api.get('/accounts'),
        getById: (id) => api.get(`/accounts/${id}`),
        getBalance: (id) => api.get(`/accounts/${id}/balance`),
        create: (data) => api.post('/accounts', data),
        update: (id, data) => api.put(`/accounts/${id}`, data),
        delete: (id) => api.delete(`/accounts/${id}`),
        deposit: (accountId, amount) => api.post('/accounts/deposit', { account_id: accountId, amount }),
        withdraw: (accountId, amount) => api.post('/accounts/withdraw', { account_id: accountId, amount }),
        transfer: (fromAccountId, toAccountId, amount) =>
            api.post('/accounts/transfer', { from_account_id: fromAccountId, to_account_id: toAccountId, amount }),
    }
}