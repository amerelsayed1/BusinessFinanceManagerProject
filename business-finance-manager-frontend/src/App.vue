<script setup>
import { computed } from 'vue'
import { useStore } from 'vuex'
import { useRouter, RouterView, RouterLink } from 'vue-router'
import { LogOut, User } from 'lucide-vue-next'

const store = useStore()
const router = useRouter()

const isAuthenticated = computed(() => store.getters['auth/isAuthenticated'])
const currentUser = computed(() => store.getters['auth/currentUser'])

const handleLogout = async () => {
  try {
    await store.dispatch('auth/logout')
  } finally {
    router.push({ name: 'Login' })
  }
}
</script>

<template>
  <div class="min-h-screen bg-gray-50 text-gray-900">
    <div v-if="!isAuthenticated" class="min-h-screen flex items-center justify-center">
      <RouterView />
    </div>

    <div v-else class="min-h-screen flex">
      <aside class="w-64 bg-white border-r border-gray-200 flex flex-col">
        <div class="h-16 flex items-center px-4 border-b border-gray-200">
          <span class="text-lg font-semibold text-gray-900">Business Finance</span>
        </div>
        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-2">
          <RouterLink
            to="/"
            class="block px-3 py-2 rounded-lg hover:bg-gray-100"
            active-class="bg-gray-100 font-semibold"
          >
            Dashboard
          </RouterLink>
          <RouterLink
            to="/accounts"
            class="block px-3 py-2 rounded-lg hover:bg-gray-100"
            active-class="bg-gray-100 font-semibold"
          >
            Accounts
          </RouterLink>
          <RouterLink
            to="/expenses"
            class="block px-3 py-2 rounded-lg hover:bg-gray-100"
            active-class="bg-gray-100 font-semibold"
          >
            Expenses
          </RouterLink>
          <RouterLink
            to="/purchases"
            class="block px-3 py-2 rounded-lg hover:bg-gray-100"
            active-class="bg-gray-100 font-semibold"
          >
            Purchases
          </RouterLink>
          <RouterLink
            to="/transfers"
            class="block px-3 py-2 rounded-lg hover:bg-gray-100"
            active-class="bg-gray-100 font-semibold"
          >
            Transfers
          </RouterLink>
          <RouterLink
            to="/reports"
            class="block px-3 py-2 rounded-lg hover:bg-gray-100"
            active-class="bg-gray-100 font-semibold"
          >
            Reports / Export
          </RouterLink>
          <RouterLink
            to="/profile"
            class="block px-3 py-2 rounded-lg hover:bg-gray-100"
            active-class="bg-gray-100 font-semibold"
          >
            Profile
          </RouterLink>
        </nav>
        <div class="border-t border-gray-200 p-3 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <User class="w-4 h-4 text-gray-600" />
            <div class="text-sm">
              <p class="font-semibold">{{ currentUser?.name || 'User' }}</p>
              <p class="text-gray-500">{{ currentUser?.email }}</p>
            </div>
          </div>
          <button
            type="button"
            class="inline-flex items-center gap-1 text-sm text-red-600 hover:text-red-700"
            @click="handleLogout"
          >
            <LogOut class="w-4 h-4" />
            Logout
          </button>
        </div>
      </aside>

      <main class="flex-1">
        <div class="max-w-6xl mx-auto p-4 md:p-8">
          <RouterView />
        </div>
      </main>
    </div>
  </div>
</template>
