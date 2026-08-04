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

  // Navigation guard for auth & RBAC
  Router.beforeEach((to, from, next) => {
    const token = localStorage.getItem('auth_token')
    const role = localStorage.getItem('auth_role')

    // If route requires authentication
    if (to.meta.requiresAuth) {
      if (!token) {
        // Not logged in — redirect to login
        return next('/login')
      }

      // --- NEW: Status enforcement ---
      try {
        const userDataStr = localStorage.getItem('auth_user')
        const userData = userDataStr ? JSON.parse(userDataStr) : {}
        const accountStatus = userData.account_status

        if (accountStatus === 'suspended' || accountStatus === 'inactive') {
          localStorage.removeItem('auth_token')
          localStorage.removeItem('auth_user')
          localStorage.removeItem('auth_role')
          localStorage.removeItem('vendor_status')
          return next('/login')
        }
      } catch (e) {
        // Corrupted data — force re-login
        localStorage.removeItem('auth_token')
        localStorage.removeItem('auth_user')
        localStorage.removeItem('auth_role')
        localStorage.removeItem('vendor_status')
        return next('/login')
      }

      // Check vendor approval status for vendor-only routes
      if (to.meta.role === 'Vendor' && role === 'Vendor') {
        const vendorStatus = localStorage.getItem('vendor_status')
        if (vendorStatus === 'rejected') {
          return next('/auth/vendor/rejected')
        }
        if (vendorStatus === 'pending') {
          return next('/auth/vendor/under-review')
        }
      }
      // --- END NEW ---

      // If route requires a specific role
      if (to.meta.role && to.meta.role !== role) {
        // Wrong role — redirect to their own dashboard
        if (role === 'Admin') return next('/admin/dashboard')
        if (role === 'Vendor') return next('/vendor/dashboard')
        if (role === 'Consumer') return next('/consumer/home')
        return next('/login')
      }
    }

    // If route is guest-only (login, register) and user is already logged in
    if (to.meta.guest && token && role) {
      if (role === 'Admin') return next('/admin/dashboard')
      if (role === 'Vendor') return next('/vendor/dashboard')
      if (role === 'Consumer') return next('/consumer/home')
    }

    next()
  })

  return Router
})
