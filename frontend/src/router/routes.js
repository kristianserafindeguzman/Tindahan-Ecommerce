const routes = [
  {
    path: '/',
    component: () => import('@/layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        redirect: '/login'
      },

      {
        path: 'login',
        component: () => import('@/pages/auth/LoginPage.vue')
      },

      {
        path: 'consumer/register',
        component: () => import('@/pages/auth/consumer/ConsumerRegister.vue')
      },

      {
        path: 'consumer/verify',
        component: () => import('@/pages/auth/consumer/ConsumerVerify.vue')
      },

      {
        path: 'consumer/success',
        component: () => import('@/pages/auth/consumer/ConsumerSuccess.vue')
      },

      {
        path: 'merchant/register',
        component: () => import('@/pages/auth/merchant/MerchantRegister.vue')
      }
    ]
  },

  {
    path: '/:catchAll(.*)*',
    component: () => import('@/pages/ErrorNotFound.vue')
  }
]

export default routes
