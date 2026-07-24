import { boot } from 'quasar/wrappers'
import axios from 'axios'

const api = axios.create({
  baseURL: 'http://localhost:8000/api'
})

// Attach the Sanctum bearer token to every request
api.interceptors.request.use(config => {
  const token = localStorage.getItem('auth_token')

  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }

  return config
})

// Handle 401 responses globally — redirect to login
api.interceptors.response.use(
  response => response,
  error => {
    if (error.response && error.response.status === 401) {
      localStorage.removeItem('auth_token')
      localStorage.removeItem('auth_user')

      // Only redirect if not already on a public page
      const publicPaths = ['/login', '/consumer/register', '/vendor/register', '/consumer/verify', '/consumer/success']
      const currentPath = window.location.hash.replace('#', '')

      if (!publicPaths.includes(currentPath)) {
        window.location.href = '/#/login'
      }
    }

    return Promise.reject(error)
  }
)

export default boot(({ app }) => {
  app.config.globalProperties.$api = api
})

export { api }