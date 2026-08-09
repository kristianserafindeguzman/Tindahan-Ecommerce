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
        path: 'verification',
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

      // ----- Protected Consumer Routes (MainLayout) -----
      {
        path: 'consumer/home',
        component: () => import('@/pages/Consumer/ConsumerHome.vue'),
        meta: { requiresAuth: true, role: 'Consumer' }
      },
      {
        path: 'consumer/personalize',
        component: () => import('@/pages/Consumer/ConsumerPersonalize.vue'),
        meta: { requiresAuth: true, role: 'Consumer' }
      },
      {
        path: 'consumer/products',
        component: () => import('@/pages/Consumer/ConsumerProducts.vue'),
        meta: { requiresAuth: true, role: 'Consumer' }
      },
      {
        path: 'consumer/cart',
        component: () => import('@/pages/Consumer/ConsumerCart.vue'),
        meta: { requiresAuth: true, role: 'Consumer' }
      },
      {
        path: 'consumer/stores',
        component: () => import('@/pages/Consumer/ConsumerStores.vue'),
        meta: { requiresAuth: true, role: 'Consumer' }
      },
      {
        path: 'consumer/stores/:id',
        component: () => import('@/pages/Consumer/ConsumerStoreDetail.vue'),
        meta: { requiresAuth: true, role: 'Consumer' }
      },
      {
        path: 'consumer/search',
        component: () => import('@/pages/Consumer/ConsumerSearch.vue'),
        meta: { requiresAuth: true, role: 'Consumer' }
      },
      {
        path: 'consumer/profile',
        component: () => import('@/pages/Consumer/ConsumerProfile.vue'),
        meta: { requiresAuth: true, role: 'Consumer' }
      }
    ]
  },
  
  // ----- Protected Vendor Routes (VendorLayout) -----
  {
    path: '/vendor',
    component: () => import('@/layouts/VendorLayout.vue'),
    meta: { requiresAuth: true, role: 'Vendor' },
    children: [
      {
        path: 'dashboard',
        component: () => import('@/pages/Vendor/VendorDashboard.vue'),
      },
      {
        path: 'products/list',
        component: () => import('@/pages/Vendor/Products/ProductList.vue'),
      },
      {
        path: 'products/categories',
        component: () => import('@/pages/Vendor/Products/ProductCategory.vue'),
      },
      {
        path: 'profile',
        component: () => import('@/pages/Vendor/VendorProfile.vue'),
      },
      {
        path: 'sales',
        component: () => import('@/pages/Vendor/VendorSales.vue'),
      },
      {
        path: 'orders/list',
        component: () => import('@/pages/Vendor/Orders/OrderList.vue'),
      },
      {
        path: 'orders/customers',
        component: () => import('@/pages/Vendor/Orders/CustomerOrders.vue'),
      },
      {
        path: 'orders/:id',
        component: () => import('@/pages/Vendor/Orders/OrderDetails.vue'),
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