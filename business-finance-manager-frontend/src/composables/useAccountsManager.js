// src/composables/useAccountsManager.js
import { ref, computed } from 'vue'
import accountService from '../services/accountService'

const toNumber = (value) => Number(value) || 0

export function useAccountsManager() {
    const accounts = ref([])
    const loadingAccounts = ref(false)

    const showAddBalanceModal = ref(false)
    const selectedAccountForDeposit = ref(null)
    const depositAmount = ref('')

    const totalBalance = computed(() =>
        accounts.value.reduce(
            (sum, acc) => sum + toNumber(acc.balance || 0),
            0,
        ),
    )

    const loadAccounts = async () => {
        try {
            loadingAccounts.value = true
            const response = await accountService.getAll()
            accounts.value = response.data
        } catch (error) {
            console.error('Error loading accounts:', error)
        } finally {
            loadingAccounts.value = false
        }
    }

    const createAccount = async (data) => {
        try {
            await accountService.create({
                name: data.name,
                balance: toNumber(data.initialBalance),
            })
            await loadAccounts()
            console.log('Account created successfully!')
        } catch (error) {
            console.error('Error creating account:', error)
        }
    }

    const deleteAccount = async (account) => {
        if (!confirm(`Delete account "${account.name}"?`)) return

        try {
            await accountService.delete(account.id)
            await loadAccounts()
            console.log('Account deleted successfully!')
        } catch (error) {
            console.error('Error deleting account:', error)
        }
    }

    const handleDeposit = async (data) => {
        try {
            await accountService.deposit(data.accountId, toNumber(data.amount))
            await loadAccounts()
            console.log('Deposit successful!')
        } catch (error) {
            console.error('Error depositing:', error)
        }
    }

    const openAddBalanceModal = () => {
        if (accounts.value.length === 0) {
            alert('Please create an account first')
            return
        }
        selectedAccountForDeposit.value = accounts.value[0].id
        depositAmount.value = ''
        showAddBalanceModal.value = true
    }

    const closeAddBalanceModal = () => {
        showAddBalanceModal.value = false
    }

    const submitAddBalance = async () => {
        if (!selectedAccountForDeposit.value || !depositAmount.value) {
            alert('Please select account and enter amount')
            return
        }

        try {
            await accountService.deposit(
                selectedAccountForDeposit.value,
                toNumber(depositAmount.value),
            )
            await loadAccounts()
            showAddBalanceModal.value = false
            console.log('Balance added successfully!')
        } catch (error) {
            console.error('Error adding balance:', error)
        }
    }

    return {
        // state
        accounts,
        loadingAccounts,
        showAddBalanceModal,
        selectedAccountForDeposit,
        depositAmount,
        // computed
        totalBalance,
        // actions
        loadAccounts,
        createAccount,
        deleteAccount,
        handleDeposit,
        openAddBalanceModal,
        closeAddBalanceModal,
        submitAddBalance,
        // helper for template
        toNumber,
    }
}
