const routes = [
  {
    path: '/',
    component: () => import('@/layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        redirect: '/login'
      },

      // ----- Public Auth Routes -----
      {
        path: 'login',
        component: () => import('@/pages/auth/LoginPage.vue'),
        meta: { guest: true }
      },

      {
        path: 'consumer/register',
        component: () => import('@/pages/auth/consumer/ConsumerRegister.vue'),
        meta: { guest: true }
      },

      {
        path: 'consumer/verify',
        component: () => import('@/pages/auth/consumer/ConsumerVerify.vue'),
        meta: { guest: true }
      },

      {
        path: 'consumer/success',
        component: () => import('@/pages/auth/consumer/ConsumerSuccess.vue'),
        meta: { guest: true }
      },

      {
        path: 'vendor/register',
        component: () => import('@/pages/auth/vendor/VendorRegister.vue'),
        meta: { guest: true }
      },

      // ----- Protected Dashboard Routes -----
      {
        path: 'admin/dashboard',
        component: () => import('@/pages/Admin/AdminDashboard.vue'),
        meta: { requiresAuth: true, role: 'Admin' }
      },

      {
        path: 'vendor/dashboard',
        component: () => import('@/pages/Vendor/VendorDashboard.vue'),
        meta: { requiresAuth: true, role: 'Vendor' }
      },

      {
        path: 'consumer/home',
        component: () => import('@/pages/Consumer/ConsumerHome.vue'),
        meta: { requiresAuth: true, role: 'Consumer' }
      }
    ]
  },

  {
    path: '/:catchAll(.*)*',
    component: () => import('@/pages/ErrorNotFound.vue')
  }
]

export default routes
