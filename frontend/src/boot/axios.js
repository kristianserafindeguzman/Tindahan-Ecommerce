import { boot } from 'quasar/wrappers'
import axios from 'axios'
import { clearAuthStorage } from '@/utils/authStorage'

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

api.interceptors.response.use(
  response => response,
  error => {
    if (error.response) {
      const status = error.response.status
      const errorCode = error.response.data?.error_code

      const isAccountError = 
        errorCode === 'ACCOUNT_SUSPENDED' || 
        errorCode === 'ACCOUNT_INACTIVE' || 
        errorCode === 'VENDOR_NOT_APPROVED'

      if (status === 401 || (status === 403 && isAccountError)) {
        clearAuthStorage()

        // Only redirect if not already on a public page
        const publicPaths = ['/login', '/consumer/register', '/vendor/register', '/consumer/verify', '/consumer/success']
        const currentPath = window.location.hash.replace('#', '')

        if (!publicPaths.includes(currentPath) && !currentPath.startsWith('/auth/vendor/')) {
          window.location.href = '/#/login'
        }
      }
    }

    return Promise.reject(error)
  }
)

export default boot(({ app }) => {
  app.config.globalProperties.$api = api
})

export { api }