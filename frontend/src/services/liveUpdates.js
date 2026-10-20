import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import { api } from '@/boot/axios'
import { createLiveUpdates } from '@/utils/liveUpdatesCore'
import {
  currentSession,
  resetNotifications,
  useNotifications
} from '@/composables/useNotifications'

export const liveUpdates = createLiveUpdates({
  api,
  getSession: currentSession,
  onReset: resetNotifications,
  onSession: data => {
    if (data.role === 'Vendor' && data.vendor_status)
      localStorage.setItem('vendor_status', data.vendor_status)
  },
  createEcho: data =>
    new Echo({
      broadcaster: 'reverb',
      client: new Pusher(data.key, {
        wsHost: data.host,
        wsPort: data.port,
        wssPort: data.port,
        wsPath: data.path || '',
        forceTLS: data.scheme === 'https',
        enabledTransports: ['ws', 'wss'],
        disableStats: true,
        cluster: 'mt1',
        authorizer: channel => ({
          authorize: async (socketId, callback) => {
            let response
            try {
              response = await api.post(
                '/broadcasting/auth',
                { socket_id: socketId, channel_name: channel.name },
                { timeout: 10000 }
              )
            } catch (error) {
              callback(error, null)
              return
            }
            callback(null, response.data)
          }
        })
      })
    })
})

liveUpdates.register('notifications', () => {
  const { role } = currentSession()
  if (['Consumer', 'Vendor', 'Admin'].includes(role))
    return useNotifications(role).fetchNotifications()
})

// Mutations in this tab should update every mounted view, even if delivery is delayed.
api.interceptors.response.use(response => {
  if (['post', 'patch', 'put', 'delete'].includes(response.config?.method)) {
    const url = response.config.url || ''
    if (url.includes('/notifications')) liveUpdates.invalidate('notifications')
    if (url.includes('/orders') || url.includes('/checkout'))
      liveUpdates.invalidate('orders')
    if (/\/vendors\/[^/]+\/(approve|reject)/.test(url))
      liveUpdates.invalidate('applications')
  }
  return response
})
