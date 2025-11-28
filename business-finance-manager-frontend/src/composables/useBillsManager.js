// src/composables/useBillsManager.js
import { ref, computed } from 'vue'
import billService from '../services/billService'

const toNumber = (value) => Number(value) || 0

const isSameMonthAndYear = (date, refDate) => {
    const d = new Date(date)
    const r = new Date(refDate)
    return d.getFullYear() === r.getFullYear() && d.getMonth() === r.getMonth()
}

export function useBillsManager() {
    const bills = ref([])
    const selectedBillMonth = ref(new Date())
    const loadingBills = ref(false)

    const showReceiptModal = ref(false)
    const currentReceipt = ref(null)

    const billMonthLabel = computed(() =>
        selectedBillMonth.value.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
        }),
    )

    const filteredBills = computed(() =>
        bills.value.filter((bill) =>
            isSameMonthAndYear(bill.date, selectedBillMonth.value),
        ),
    )

    const pendingTotalForBillMonth = computed(() =>
        filteredBills.value
            .filter((bill) => bill.status === 'pending')
            .reduce((sum, bill) => sum + toNumber(bill.amount || 0), 0),
    )

    const paidTotalForBillMonth = computed(() =>
        filteredBills.value
            .filter((bill) => bill.status === 'paid')
            .reduce((sum, bill) => sum + toNumber(bill.amount || 0), 0),
    )

    const pendingInvoicesThisMonth = computed(() => {
        const now = new Date()
        const current = bills.value.filter(
            (bill) =>
                isSameMonthAndYear(bill.date, now) && bill.status === 'pending',
        )
        return current.reduce(
            (sum, bill) => sum + toNumber(bill.amount || 0),
            0,
        )
    })

    const paidInvoicesThisMonth = computed(() => {
        const now = new Date()
        const current = bills.value.filter(
            (bill) =>
                isSameMonthAndYear(bill.date, now) && bill.status === 'paid',
        )
        return current.reduce(
            (sum, bill) => sum + toNumber(bill.amount || 0),
            0,
        )
    })

    const loadBills = async () => {
        try {
            loadingBills.value = true
            const response = await billService.getAll()
            bills.value = response.data
        } catch (error) {
            console.error('Error loading bills:', error)
        } finally {
            loadingBills.value = false
        }
    }

    const addBill = async (data) => {
        if (!data.description || !data.amount || !data.accountId) {
            alert('Please fill in all required fields')
            return
        }

        try {
            await billService.create({
                description: data.description,
                amount: toNumber(data.amount),
                date: data.date,
                status: data.status || 'pending',
                accountId: Number(data.accountId),
                image: data.image || null,
                isMonthly: data.isMonthly || false,
            })
            await loadBills()
            console.log('Invoice added successfully!')
        } catch (error) {
            console.error('Error adding bill:', error)
        }
    }

    const deleteBill = async (bill) => {
        if (!confirm('Delete this invoice?')) return

        try {
            await billService.delete(bill.id)
            await loadBills()
            console.log('Invoice deleted successfully!')
        } catch (error) {
            console.error('Error deleting bill:', error)
        }
    }

    const toggleBillStatus = async (bill) => {
        const newStatus = bill.status === 'paid' ? 'pending' : 'paid'

        if (!confirm(`Mark invoice as ${newStatus}?`)) return

        try {
            await billService.updateStatus(bill.id, newStatus)
            await loadBills()
            console.log(`Invoice marked as ${newStatus}!`)
        } catch (error) {
            console.error('Error updating bill status:', error)
        }
    }

    const changeMonth = (offset) => {
        const d = new Date(selectedBillMonth.value)
        d.setMonth(d.getMonth() + offset)
        selectedBillMonth.value = d
    }

    const prevBillMonth = () => changeMonth(-1)
    const nextBillMonth = () => changeMonth(1)

    const viewReceipt = (imageData) => {
        currentReceipt.value = imageData
        showReceiptModal.value = true
    }

    const closeReceipt = () => {
        showReceiptModal.value = false
        currentReceipt.value = null
    }

    return {
        // state
        bills,
        selectedBillMonth,
        loadingBills,
        showReceiptModal,
        currentReceipt,
        // computed
        billMonthLabel,
        filteredBills,
        pendingTotalForBillMonth,
        paidTotalForBillMonth,
        pendingInvoicesThisMonth,
        paidInvoicesThisMonth,
        // actions
        loadBills,
        addBill,
        deleteBill,
        toggleBillStatus,
        prevBillMonth,
        nextBillMonth,
        viewReceipt,
        closeReceipt,
    }
}
