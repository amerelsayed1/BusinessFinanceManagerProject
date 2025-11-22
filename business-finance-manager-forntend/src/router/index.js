import Vue from 'vue';
import VueRouter from 'vue-router';
import store from '../store/index.js';

Vue.use(VueRouter);

const routes = [
    {
        path: '/login',
        name: 'Login',
        component: () => import('@/views/Login.vue'),
        meta: { guest: true },
    },
    {
        path: '/',
        name: 'Dashboard',
        component: () => import('@/components/Dashboard.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/profile',
        name: 'Profile',
        component: () => import('@/views/ProfileSettings.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/transfers',
        name: 'Transfers',
        component: () => import('@/views/AccountTransfers.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/monthly-sales',
        name: 'MonthlySales',
        component: () => import('@/views/MonthlySales.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/inventory',
        name: 'Inventory',
        component: () => import('@/views/Inventory.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/pos',
        name: 'POS',
        component: () => import('@/views/POS.vue'),
        meta: { requiresAuth: true },
    },
];

const router = new VueRouter({
    mode: 'history',
    base: process.env.BASE_URL,
    routes,
});

// Navigation guard
router.beforeEach((to, from, next) => {
    const isAuthenticated = store.getters['auth/isAuthenticated'];

    if (to.matched.some((record) => record.meta.requiresAuth)) {
        if (!isAuthenticated) {
            next('/login');
        } else {
            next();
        }
    } else if (to.matched.some((record) => record.meta.guest)) {
        if (isAuthenticated) {
            next('/');
        } else {
            next();
        }
    } else {
        next();
    }
});