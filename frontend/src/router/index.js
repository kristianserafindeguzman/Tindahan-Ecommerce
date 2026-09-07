import { defineRouter } from '#q-app'
import {
  createMemoryHistory,
  createRouter,
  createWebHashHistory,
  createWebHistory,
} from 'vue-router'

import routes from './routes.js'

/*
 * If not building with SSR mode, you can
 * directly export the Router instantiation;
 *
 * The function below can be async too; either use
 * async/await or return a Promise which resolves
 * with the Router instance.
 */

export default defineRouter((/* { store, ssrContext } */) => {
  const createHistory = import.meta.env.QUASAR_SERVER
    ? createMemoryHistory
    : (import.meta.env.QUASAR_VUE_ROUTER_MODE === 'history' ? createWebHistory : createWebHashHistory)

  const Router = createRouter({
    scrollBehavior: () => ({ left: 0, top: 0 }),
    routes,

    // Leave this as is and make changes in quasar.conf.js instead!
    // quasar.conf.js -> build -> vueRouterMode
    // quasar.conf.js -> build -> publicPath
    history: createHistory(import.meta.env.QUASAR_VUE_ROUTER_BASE)
  })

  // Navigation guard for auth & RBAC.
  //
  // Returns values rather than calling next(): vue-router 5 deprecates the next()
  // callback and logs a warning on every single navigation. Returning is also safer here
  // — with next(), an accidental fall-through calls it twice and the router throws.
  Router.beforeEach((to) => {
    const token = localStorage.getItem('auth_token')
    const role = localStorage.getItem('auth_role')

    // Clears every auth key together; leaving one behind puts the app in a half-signed-in
    // state where the guard passes but requests 401.
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

        if (accountStatus === 'suspended' || accountStatus === 'inactive') {
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
