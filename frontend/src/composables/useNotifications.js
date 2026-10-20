import { api } from '@/boot/axios'
import { createNotificationStore } from '@/utils/notificationStore'

export const currentSession = () => ({
  token: localStorage.getItem('auth_token'),
  role: localStorage.getItem('auth_role'),
  vendorStatus: localStorage.getItem('vendor_status')
})

const stores = new Map()
export function useNotifications(role) {
  if (!stores.has(role))
    stores.set(
      role,
      createNotificationStore({ api, role, getSession: currentSession })
    )
  return stores.get(role)
}

export function resetNotifications() {
  for (const store of stores.values()) store.reset()
}
