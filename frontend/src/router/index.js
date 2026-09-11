import { defineRouter } from '#q-app'
import {
  createMemoryHistory,
  createRouter,
  createWebHashHistory,
  createWebHistory,
} from 'vue-router'

import routes from './routes.js'

/* If not building with SSR mode, you can export the Router instantiation directly, and the function below may also be async or return a Promise that resolves with the Router instance. */

export default defineRouter((/* { store, ssrContext } */) => {
  const createHistory = import.meta.env.QUASAR_SERVER
    ? createMemoryHistory
    : (import.meta.env.QUASAR_VUE_ROUTER_MODE === 'history' ? createWebHistory : createWebHashHistory)

  const Router = createRouter({
    scrollBehavior: () => ({ left: 0, top: 0 }),
    routes,

    // Leave this as is and change vueRouterMode and publicPath under build in quasar.conf.js instead.
    history: createHistory(import.meta.env.QUASAR_VUE_ROUTER_BASE)
  })

  // Navigation guard for auth and roles, which returns values instead of calling next() because vue-router 5 deprecates next() and a fall-through could call it twice.
  Router.beforeEach((to) => {
    const token = localStorage.getItem('auth_token')
    const role = localStorage.getItem('auth_role')

    // Clears every auth key together, since leaving one behind puts the app in a half-signed-in state where the guard passes but requests return 401.
    const clearAuth = () => {
      localStorage.removeItem('auth_token')
      localStorage.removeItem('auth_user')
      localStorage.removeItem('auth_role')
      localStorage.removeItem('vendor_status')
    }

    // Where a signed-in user belongs when they land somewhere they should not be.
    const homeForRole = () => {
      if (role === 'Admin') return '/admin/dashboard'
      if (role === 'Vendor') return '/vendor/dashboard'
      if (role === 'Consumer') return '/consumer/home'
      return '/login'
    }

    if (to.meta.requiresAuth) {
      if (!token) return '/login'

      // Status enforcement
      try {
        const userDataStr = localStorage.getItem('auth_user')
        const userData = userDataStr ? JSON.parse(userDataStr) : {}
        const accountStatus = userData.account_status

        if (accountStatus === 'suspended' || accountStatus === 'inactive' || accountStatus === 'pending') {
          clearAuth()
          return '/login'
        }
      } catch {
        // Corrupted auth_user — force re-login rather than trusting it.
        clearAuth()
        return '/login'
      }

      // Vendor approval state
      if (to.meta.role === 'Vendor' && role === 'Vendor') {
        const vendorStatus = localStorage.getItem('vendor_status')
        if (vendorStatus === 'rejected') return '/auth/vendor/rejected'
        if (vendorStatus === 'pending') return '/auth/vendor/under-review'
      }

      // Wrong role for this route — send them to their own dashboard.
      if (to.meta.role && to.meta.role !== role) return homeForRole()
    }

    // Guest-only route (login, register) while already signed in.
    if (to.meta.guest && token && role) return homeForRole()

    return true
  })

  return Router
})
