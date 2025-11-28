// src/composables/useTabs.js
import { ref, computed } from 'vue'
import {
    Home,
    CreditCard,
    DollarSign,
    FileText,
    ArrowLeftRight,
    Boxes,
    BarChart3,
    ShoppingCart,
    Tag,
} from 'lucide-vue-next'

const TABS = {
    HOME: 'home',
    ACCOUNTS: 'accounts',
    INVENTORY: 'inventory',
    CATEGORIES: 'categories',
    EXPENSES: 'expenses',
    BILLS: 'bills',
    TRANSFERS: 'transfers',
    MONTHLY_SALES: 'monthlySales',
    POS: 'pos',
    PROFILE: 'profile',
}

const mainTabs = [
    { id: TABS.HOME, label: 'Dashboard', icon: Home },
    { id: TABS.ACCOUNTS, label: 'Accounts', icon: CreditCard },
    { id: TABS.INVENTORY, label: 'Inventory', icon: Boxes },
    { id: TABS.CATEGORIES, label: 'Categories', icon: Tag },
    { id: TABS.EXPENSES, label: 'Expenses', icon: DollarSign },
    { id: TABS.BILLS, label: 'Invoices', icon: FileText },
    { id: TABS.TRANSFERS, label: 'Transfers', icon: ArrowLeftRight },
    { id: TABS.MONTHLY_SALES, label: 'Monthly Sales', icon: BarChart3 },
    { id: TABS.POS, label: 'POS', icon: ShoppingCart },
]

const tabLabels = mainTabs.reduce(
    (acc, tab) => {
        acc[tab.id] = tab.label
        return acc
    },
    { [TABS.PROFILE]: 'Profile' },
)

const navBaseClasses =
    'w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors'
const navActiveClasses = 'bg-blue-50 text-blue-700'
const navInactiveClasses = 'text-gray-700 hover:bg-gray-50 hover:text-blue-700'

export function useTabs() {
    const currentTab = ref(TABS.HOME)

    const currentTabLabel = computed(
        () => tabLabels[currentTab.value] ?? currentTab.value,
    )

    const tabButtonClasses = (tabId) => [
        navBaseClasses,
        currentTab.value === tabId ? navActiveClasses : navInactiveClasses,
    ]

    const goToAccounts = () => {
        currentTab.value = TABS.ACCOUNTS
    }

    const goToTransfers = () => {
        currentTab.value = TABS.TRANSFERS
    }

    const goToProfile = () => {
        currentTab.value = TABS.PROFILE
    }

    return {
        TABS,
        mainTabs,
        currentTab,
        currentTabLabel,
        tabButtonClasses,
        goToAccounts,
        goToTransfers,
        goToProfile,
    }
}
