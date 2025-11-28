// src/composables/useExpensesManager.js
import { ref, computed, watch } from 'vue'
import expenseService from '../services/expenseService'

const toNumber = (value) => Number(value) || 0

const formatMonthKey = (date) =>
    `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`

const isSameMonthAndYear = (date, refDate) => {
    const d = new Date(date)
    const r = new Date(refDate)
    return d.getFullYear() === r.getFullYear() && d.getMonth() === r.getMonth()
}

export function useExpensesManager() {
    const expenses = ref([])
    const selectedExpenseMonth = ref(new Date())
    const loadingExpenses = ref(false)

    const expenseMonthLabel = computed(() =>
        selectedExpenseMonth.value.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
        }),
    )

    const filteredExpenses = computed(() =>
        expenses.value.filter((exp) =>
            isSameMonthAndYear(exp.date, selectedExpenseMonth.value),
        ),
    )

    const totalForExpenseMonth = computed(() =>
        filteredExpenses.value.reduce(
            (sum, exp) => sum + toNumber(exp.amount || 0),
            0,
        ),
    )

    const totalExpensesThisMonth = computed(() => {
        const now = new Date()
        const current = expenses.value.filter((exp) =>
            isSameMonthAndYear(exp.date, now),
        )
        return current.reduce(
            (sum, exp) => sum + toNumber(exp.amount || 0),
            0,
        )
    })

    const loadExpenses = async (month = selectedExpenseMonth.value) => {
        try {
            loadingExpenses.value = true
            const monthKey = month ? formatMonthKey(month) : null
            const response = await expenseService.getAll(monthKey)
            expenses.value = response.data
        } catch (error) {
            console.error('Error loading expenses:', error)
        } finally {
            loadingExpenses.value = false
        }
    }

    const addExpense = async (data) => {
        if (!data.description || !data.amount || !data.accountId) {
            alert('Please fill in all required fields')
            return
        }

        try {
            await expenseService.create({
                description: data.description,
                amount: toNumber(data.amount),
                date: data.date,
                categoryId: data.categoryId ?? data.category ?? null,
                accountId: Number(data.accountId),
            })
            await loadExpenses()
            console.log('Expense added successfully!')
        } catch (error) {
            console.error('Error adding expense:', error)
        }
    }

    const deleteExpense = async (expense) => {
        if (!confirm('Delete this expense?')) return

        try {
            await expenseService.delete(expense.id)
            await loadExpenses()
            console.log('Expense deleted successfully!')
        } catch (error) {
            console.error('Error deleting expense:', error)
        }
    }

    const changeMonth = (offset) => {
        const d = new Date(selectedExpenseMonth.value)
        d.setMonth(d.getMonth() + offset)
        selectedExpenseMonth.value = d
    }

    const prevExpenseMonth = () => changeMonth(-1)
    const nextExpenseMonth = () => changeMonth(1)

    watch(selectedExpenseMonth, (month) => {
        loadExpenses(month)
    })

    return {
        // state
        expenses,
        selectedExpenseMonth,
        loadingExpenses,
        // computed
        expenseMonthLabel,
        filteredExpenses,
        totalForExpenseMonth,
        totalExpensesThisMonth,
        // actions
        loadExpenses,
        addExpense,
        deleteExpense,
        prevExpenseMonth,
        nextExpenseMonth,
    }
}
