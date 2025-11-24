// src/store/auth.js (Vuex store for authentication)
import api from '../services/api'

export default {
  namespaced: true,
  state: {
    token: localStorage.getItem('token') || localStorage.getItem('auth_token') || null,
    user: JSON.parse(localStorage.getItem('user') || 'null'),
  },
  mutations: {
    SET_TOKEN(state, token) {
      state.token = token
      localStorage.setItem('token', token)
      localStorage.setItem('auth_token', token)
    },
    SET_USER(state, user) {
      state.user = user
      localStorage.setItem('user', JSON.stringify(user))
    },
    LOGOUT(state) {
      state.token = null
      state.user = null
      localStorage.removeItem('token')
      localStorage.removeItem('auth_token')
      localStorage.removeItem('user')
    },
  },
  actions: {
    async login({ commit }, credentials) {
      const response = await api.post('/login', credentials)
      const { token, user } = response.data

      commit('SET_TOKEN', token)
      commit('SET_USER', user)

      return response.data
    },
    async logout({ commit }) {
      try {
        await api.post('/logout')
      } catch (e) {
        // ignore logout errors
      } finally {
        commit('LOGOUT')
      }
    },
    async fetchUser({ commit }) {
      const response = await api.get('/me')
      commit('SET_USER', response.data)
      return response.data
    },
  },
  getters: {
    isAuthenticated: (state) => !!state.token,
    currentUser: (state) => state.user,
    defaultCurrency: (state) => state.user?.default_currency || 'EGP',
  },
}
