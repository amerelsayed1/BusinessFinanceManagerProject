import { createRouter, createWebHistory } from 'vue-router'
import store from '../store'

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/views/Login.vue'),
    meta: { guest: true },
  },
  {
    path: '/register',
    name: 'Register',
    component: () => import('@/views/Register.vue'),
    meta: { guest: true },
  },
  {
    path: '/',
    name: 'Dashboard',
    component: () => import('@/views/Dashboard.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/accounts',
    name: 'Accounts',
    component: () => import('@/views/AccountsPage.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/expenses',
    name: 'Expenses',
    component: () => import('@/views/ExpensesPage.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/income',
    name: 'Income',
    component: () => import('@/views/IncomePage.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/purchases',
    name: 'Purchases',
    component: () => import('@/views/PurchasesPage.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/monthly-sales',
    name: 'MonthlySales',
    component: () => import('@/views/MonthlySales.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/transfers',
    name: 'Transfers',
    component: () => import('@/views/AccountTransfers.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/reports',
    name: 'Reports',
    component: () => import('@/views/ReportsExport.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/profile',
    name: 'Profile',
    component: () => import('@/views/ProfileSettings.vue'),
    meta: { requiresAuth: true },
  },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
})

router.beforeEach((to, from, next) => {
  const isAuthenticated = store.getters['auth/isAuthenticated']

  if (to.matched.some((record) => record.meta.requiresAuth)) {
    if (!isAuthenticated) {
      next({ name: 'Login' })
    } else {
      next()
    }
  } else if (to.matched.some((record) => record.meta.guest)) {
    if (isAuthenticated) {
      next({ name: 'Dashboard' })
    } else {
      next()
    }
  } else {
    next()
  }
})

export default router
