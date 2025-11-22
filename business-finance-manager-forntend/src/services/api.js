/*
export const API_BASE_URL = import.meta.env.VITE_API_BASE_URL ?? 'http://127.0.0.1:8000/api';

async function apiRequest(path, options = {}) {
  const url = `${API_BASE_URL}${path}`;
  const defaultOptions = {
    headers: {
      'Accept': 'application/json',
      ...(options.headers || {}),
    },
    ...options,
  };

  const response = await fetch(url, defaultOptions);

  let text;
  try {
    text = await response.text();
  } catch {
    text = '';
  }

  let data = null;
  if (text) {
    try {
      data = JSON.parse(text);
    } catch {
      data = text;
    }
  }

  if (!response.ok) {
    const error = new Error(`API error ${response.status}`);
    error.status = response.status;
    error.body = data;
    throw error;
  }

  return data;
}

export const apiGet = (path) => apiRequest(path, { method: 'GET' });

export const apiPost = (path, body) =>
  apiRequest(path, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body ?? {}),
  });

export const apiDelete = (path) =>
  apiRequest(path, {
    method: 'DELETE',
  });

export const apiPatch = (path, body) =>
  apiRequest(path, {
    method: 'PATCH',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body ?? {}),
  });
*/
// src/services/api.js
import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
})

// Request interceptor for adding auth token
api.interceptors.request.use(
    (config) => {
      const token = localStorage.getItem('auth_token')
      if (token) {
        config.headers.Authorization = `Bearer ${token}`
      }
      return config
    },
    (error) => {
      return Promise.reject(error)
    }
)

// Response interceptor for handling errors
api.interceptors.response.use(
    (response) => response,
    (error) => {
      if (error.response) {
        // Handle specific error codes
        switch (error.response.status) {
          case 401:
            // Unauthorized - redirect to login
            localStorage.removeItem('auth_token')
            window.location.href = '/login'
            break
          case 422:
            // Validation error - return for form handling
            return Promise.reject(error.response.data)
          case 404:
            // Not found
            console.error('Resource not found')
            break
          case 500:
            // Server error
            console.error('Server error occurred')
            break
        }
      }
      return Promise.reject(error)
    }
)

export default api