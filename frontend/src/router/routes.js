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

      // ----- Vendor Application Status (post-login, pre-approval) -----
      {
        path: 'auth/vendor/under-review',
        component: () => import('@/pages/auth/vendor/VendorUnderReview.vue')
      },

      {
        path: 'auth/vendor/rejected',
        component: () => import('@/pages/auth/vendor/VendorRejected.vue')
      },

      // ----- Protected Dashboard Routes (MainLayout) -----
      {
        path: 'vendor/dashboard',
        component: () => import('@/pages/Vendor/VendorDashboard.vue'),
        meta: { requiresAuth: true, role: 'Vendor' }
      },
      {
        path: 'vendor/inventory',
        component: () => import('@/pages/Vendor/VendorInventory.vue'),
        meta: { requiresAuth: true, role: 'Vendor' }
      },
      {
        path: 'consumer/home',
        component: () => import('@/pages/Consumer/ConsumerHome.vue'),
        meta: { requiresAuth: true, role: 'Consumer' }
      }
    ]
  },

  // ----- Admin Routes (AdminLayout) -----
  {
    path: '/admin',
    component: () => import('@/layouts/AdminLayout.vue'),
    meta: { requiresAuth: true, role: 'Admin' },
children: [

  {
    path: '',
    redirect: '/admin/dashboard'
  },

  {
    path: 'dashboard',
    component: () => import('@/pages/Admin/AdminDashboard.vue')
  },

  {
    path: 'approvals',
    component: () => import('@/pages/Admin/AdminApprovals.vue')
  },

  {
    path: 'vendors',
    component: () => import('@/pages/Admin/AdminVendors.vue')
  },

  {
    path: 'consumers',
    component: () => import('@/pages/Admin/AdminConsumers.vue')
  }
]
  },

  {
    path: '/:catchAll(.*)*',
    component: () => import('@/pages/ErrorNotFound.vue')
  }
]

export default routes