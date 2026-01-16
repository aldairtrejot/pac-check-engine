import axios from 'axios'

// Axios instance
const api = axios.create({
  // Base URL for all HTTP requests (VITE_BASE_URL)
  baseURL: import.meta.env.VITE_BASE_URL,
  // Request timeout
  timeout: 300000,
  // Send cookies and authentication information with requests
  withCredentials: true,
  // Default headers (NO fijar Content-Type aquí)
  headers: {
    'X-Requested-With': 'XMLHttpRequest',
    'Accept': 'application/json',
  },
})

// Interceptor para incluir el token CSRF en cada petición
api.interceptors.request.use((config) => {
  const token = document.querySelector('meta[name="csrf-token"]')

  if (token) {
    config.headers = config.headers || {}
    config.headers['X-CSRF-TOKEN'] = token.content
  }

  // ✅ Si mandas FormData, NO fuerces Content-Type (axios pone boundary)
  if (config.data instanceof FormData) {
    const headers = { ...(config.headers || {}) }
    delete headers['Content-Type']
    delete headers['content-type']
    config.headers = headers
  }

  return config
})

export default api
