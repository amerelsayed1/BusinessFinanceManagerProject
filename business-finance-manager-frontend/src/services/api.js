import axios from 'axios'
import router from '../router'
import store from '../store'

const API_BASE_URL =
  import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8000/api/v1'

const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
})

// Request interceptor for adding auth token
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token') || localStorage.getItem('auth_token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => Promise.reject(error),
)

let isHandlingUnauthorized = false

// Response interceptor for handling errors
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response) {
      switch (error.response.status) {
        case 401:
          if (!isHandlingUnauthorized) {
            isHandlingUnauthorized = true
            store.commit('auth/LOGOUT')
            router.replace({ name: 'Login' })
            console.warn('Session expired. Please log in again.')
            isHandlingUnauthorized = false
          }
          break
        case 403:
          console.error('Forbidden - you do not have access to this resource')
          break
        case 404:
          console.error('Resource not found')
          break
        case 500:
          console.error('Server error occurred')
          break
        default:
          break
      }
    }

    return Promise.reject(error)
  },
)

export default api
