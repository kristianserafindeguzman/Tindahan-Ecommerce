const routes = [
  {
    path: '/',
    component: () => import('@/layouts/MainLayout.vue'),
    children: [
      {
        // The storefront is the landing screen, not the login form — browsing needs no
        // account, so sending everyone to a sign-in wall first hid the whole catalog
        // behind it.
        //
        // Kept role-aware: a signed-in vendor or admin opening the root still lands on
        // their own dashboard rather than on the consumer storefront, which is the same
        // mapping the `meta.guest` guard in router/index.js already applies.
        path: '',
        redirect: () => {
          const token = localStorage.getItem('auth_token')
          const role = localStorage.getItem('auth_role')

          if (token && role === 'Admin') return '/admin/dashboard'
          if (token && role === 'Vendor') return '/vendor/dashboard'
          return '/consumer/home'
        }
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

      // ----- Guest-browsable Consumer Routes (MainLayout) -----
      // Browsing needs no account — only actions tied to a specific consumer
      // (cart, checkout, orders, profile, personalization) require login.
      {
        path: 'consumer/home',
        component: () => import('@/pages/Consumer/ConsumerHome.vue')
      },
      {
        path: 'consumer/products',
        component: () => import('@/pages/Consumer/ConsumerProducts.vue')
      },
      {
        path: 'consumer/stores',
        component: () => import('@/pages/Consumer/ConsumerStores.vue')
      },
      {
        path: 'consumer/stores/:id',
        component: () => import('@/pages/Consumer/ConsumerStoreDetail.vue')
      },
      {
        path: 'consumer/search',
        component: () => import('@/pages/Consumer/ConsumerSearch.vue')
      },

      // ----- Protected Consumer Routes (MainLayout) -----
      {
        path: 'consumer/personalize',
        component: () => import('@/pages/Consumer/ConsumerPersonalize.vue'),
        meta: { requiresAuth: true, role: 'Consumer' }
      },
      {
        path: 'consumer/cart',
        component: () => import('@/pages/Consumer/ConsumerCart.vue'),
        meta: { requiresAuth: true, role: 'Consumer' }
      },
      {
        path: 'consumer/checkout',
        component: () => import('@/pages/Consumer/ConsumerCheckout.vue'),
        meta: { requiresAuth: true, role: 'Consumer' }
      },
      {
        path: 'consumer/profile',
        component: () => import('@/pages/Consumer/ConsumerProfile.vue'),
        meta: { requiresAuth: true, role: 'Consumer' }
      },
      {
        path: 'consumer/orders',
        component: () => import('@/pages/Consumer/ConsumerOrders.vue'),
        meta: { requiresAuth: true, role: 'Consumer' }
      },
      {
        path: 'consumer/notifications',
        component: () => import('@/pages/Consumer/ConsumerNotifications.vue'),
        meta: { requiresAuth: true, role: 'Consumer' }
      },
      {
        path: 'consumer/orders/details',
        component: () => import('@/pages/Consumer/ConsumerOrderDetails.vue'),
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
