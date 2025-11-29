<script setup>
import { computed, ref, watch, watchEffect } from 'vue'
import { useStore } from 'vuex'
import { useRoute, useRouter, RouterView, RouterLink } from 'vue-router'
import {
  ArrowLeftRight,
  BadgeDollarSign,
  ChevronDown,
  ChevronLeft,
  ChevronRight,
  ChevronUp,
  FileBarChart2,
  LayoutDashboard,
  LogOut,
  Menu,
  Receipt,
  ShoppingCart,
  TrendingUp,
  UserRoundCog,
  Wallet2,
  X,
} from 'lucide-vue-next'

const store = useStore()
const router = useRouter()
const route = useRoute()

const accountingRoutes = ['Income', 'Expenses', 'Purchases', 'Transfers', 'AccountTransfers', 'MonthlySales']
const isAccountingOpen = ref(false)
const isCollapsed = ref(localStorage.getItem('sidebarCollapsed') === 'true')
const isMobileOpen = ref(false)

const isAuthenticated = computed(() => store.getters['auth/isAuthenticated'])
const currentUser = computed(() => store.getters['auth/currentUser'])

const mainLinks = [
  { label: 'Dashboard', icon: LayoutDashboard, routeName: 'Dashboard' },
  { label: 'Accounts', icon: Wallet2, routeName: 'Accounts' },
  { label: 'Reports / Export', icon: FileBarChart2, routeName: 'Reports' },
]

// Sidebar icon mapping and group behaviour:
// Accounting uses BadgeDollarSign (parent) with child icons TrendingUp (Income), Receipt (Expenses),
// ShoppingCart (Purchases), and ArrowLeftRight (Transfers). Collapsed mode hides labels and
// persists to localStorage; the accounting group auto-expands when a child route is active.
const accountingLinks = [
  { label: 'Income', icon: TrendingUp, routeName: 'Income' },
  { label: 'Expenses', icon: Receipt, routeName: 'Expenses' },
  { label: 'Purchases', icon: ShoppingCart, routeName: 'Purchases' },
  { label: 'Transfers', icon: ArrowLeftRight, routeName: 'Transfers' },
  { label: 'Monthly Sales', icon: BadgeDollarSign, routeName: 'MonthlySales' },
]

const settingsLinks = [{ label: 'Profile', icon: UserRoundCog, routeName: 'Profile' }]

watch(
  () => route.name,
  (name) => {
    if (accountingRoutes.includes(name)) {
      isAccountingOpen.value = true
    }
    isMobileOpen.value = false
  },
  { immediate: true },
)

watchEffect(() => {
  localStorage.setItem('sidebarCollapsed', isCollapsed.value ? 'true' : 'false')
})

const toggleAccounting = () => {
  isAccountingOpen.value = !isAccountingOpen.value
}

const toggleCollapse = () => {
  isCollapsed.value = !isCollapsed.value
}

const toggleMobile = () => {
  isMobileOpen.value = !isMobileOpen.value
}

const closeMobile = () => {
  isMobileOpen.value = false
}

const handleLogout = async () => {
  try {
    await store.dispatch('auth/logout')
  } finally {
    router.push({ name: 'Login' })
  }
}

const itemClasses = (targetName) =>
  [
    'flex items-center gap-3 px-3 py-2 rounded-lg transition-colors duration-200',
    'text-sm font-medium',
    route.name === targetName
      ? 'bg-indigo-50 text-indigo-700 border border-indigo-100'
      : 'text-gray-700 hover:bg-gray-100',
    isCollapsed.value ? 'justify-center' : 'justify-start',
  ]
</script>

<template>
  <div class="min-h-screen bg-gray-50 text-gray-900">
    <div v-if="!isAuthenticated" class="min-h-screen flex items-center justify-center">
      <RouterView />
    </div>

    <div v-else class="min-h-screen flex">
      <div v-if="isMobileOpen" class="fixed inset-0 bg-black/40 z-30 lg:hidden" @click="closeMobile" />

      <aside
        :class="[
          'bg-white border-r border-gray-200 flex flex-col shadow-sm',
          isCollapsed ? 'w-20' : 'w-72',
          'fixed inset-y-0 left-0 z-40 transform transition-all duration-200',
          isMobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
          'lg:static',
        ]"
      >
        <div class="h-16 flex items-center justify-between px-4 border-b border-gray-200">
          <div class="flex items-center gap-3">
            <BadgeDollarSign class="w-6 h-6 text-indigo-600" />
            <span v-if="!isCollapsed" class="text-lg font-semibold text-gray-900">Business Finance</span>
          </div>
          <button
            type="button"
            class="hidden lg:inline-flex items-center justify-center w-8 h-8 rounded-full hover:bg-gray-100 text-gray-600"
            :title="isCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
            @click="toggleCollapse"
          >
            <ChevronRight v-if="isCollapsed" class="w-5 h-5" />
            <ChevronLeft v-else class="w-5 h-5" />
          </button>
          <button
            type="button"
            class="lg:hidden inline-flex items-center justify-center w-8 h-8 rounded-full hover:bg-gray-100 text-gray-600"
            @click="closeMobile"
          >
            <X class="w-5 h-5" />
          </button>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-4">
          <div class="space-y-1">
            <p v-if="!isCollapsed" class="text-xs font-semibold text-gray-500 px-3">MAIN</p>
            <RouterLink
              v-for="link in mainLinks"
              :key="link.routeName"
              :to="{ name: link.routeName }"
              :class="itemClasses(link.routeName)"
              :title="isCollapsed ? link.label : ''"
            >
              <component :is="link.icon" class="w-5 h-5" />
              <span v-if="!isCollapsed">{{ link.label }}</span>
            </RouterLink>
          </div>

          <div class="space-y-2">
            <button
              type="button"
              class="w-full flex items-center px-3 py-2 rounded-lg transition-colors duration-200 text-gray-700 hover:bg-gray-100"
              @click="toggleAccounting"
              :title="isCollapsed ? 'Accounting' : ''"
            >
              <div class="flex items-center gap-3 flex-1">
                <BadgeDollarSign class="w-5 h-5" />
                <span v-if="!isCollapsed" class="text-sm font-semibold">Accounting</span>
              </div>
              <ChevronDown v-if="!isCollapsed && isAccountingOpen" class="w-4 h-4 text-gray-500" />
              <ChevronUp v-else-if="!isCollapsed" class="w-4 h-4 text-gray-500" />
              <ChevronRight v-else class="w-4 h-4 text-gray-500" />
            </button>
            <div
              v-if="isAccountingOpen"
              class="space-y-1"
              :class="isCollapsed ? 'pl-0' : 'pl-4 border-l border-gray-200 ml-1'"
            >
              <RouterLink
                v-for="link in accountingLinks"
                :key="link.routeName"
                :to="{ name: link.routeName }"
                :class="itemClasses(link.routeName)"
                :title="isCollapsed ? link.label : ''"
              >
                <component :is="link.icon" class="w-5 h-5" />
                <span v-if="!isCollapsed">{{ link.label }}</span>
              </RouterLink>
            </div>
          </div>

          <div class="space-y-1">
            <p v-if="!isCollapsed" class="text-xs font-semibold text-gray-500 px-3">SETTINGS</p>
            <RouterLink
              v-for="link in settingsLinks"
              :key="link.routeName"
              :to="{ name: link.routeName }"
              :class="itemClasses(link.routeName)"
              :title="isCollapsed ? link.label : ''"
            >
              <component :is="link.icon" class="w-5 h-5" />
              <span v-if="!isCollapsed">{{ link.label }}</span>
            </RouterLink>
          </div>
        </nav>

        <div class="border-t border-gray-200 p-3">
          <div class="flex items-center gap-2" :class="isCollapsed ? 'justify-center' : ''">
            <div class="flex items-center gap-2">
              <BadgeDollarSign class="w-4 h-4 text-gray-500" />
              <div v-if="!isCollapsed" class="text-sm leading-tight">
                <p class="font-semibold">{{ currentUser?.name || 'User' }}</p>
                <p class="text-gray-500 text-xs">{{ currentUser?.email }}</p>
              </div>
            </div>
            <button
              type="button"
              class="inline-flex items-center gap-1 text-sm text-red-600 hover:text-red-700"
              :class="isCollapsed ? 'justify-center' : ''"
              @click="handleLogout"
              :title="isCollapsed ? 'Logout' : ''"
            >
              <LogOut class="w-4 h-4" />
              <span v-if="!isCollapsed">Logout</span>
            </button>
          </div>
        </div>
      </aside>

      <div class="flex-1 lg:ml-0">
        <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-4 lg:px-8">
          <div class="flex items-center gap-2">
            <button
              type="button"
              class="inline-flex lg:hidden items-center justify-center w-10 h-10 rounded-lg hover:bg-gray-100"
              @click="toggleMobile"
            >
              <Menu class="w-5 h-5 text-gray-700" />
            </button>
            <p class="text-lg font-semibold text-gray-800">{{ route.meta?.title || 'Business Finance Manager' }}</p>
          </div>
        </header>

        <main class="flex-1">
          <div class="max-w-6xl mx-auto p-4 md:p-8">
            <RouterView />
          </div>
        </main>
      </div>
    </div>
  </div>
</template>
