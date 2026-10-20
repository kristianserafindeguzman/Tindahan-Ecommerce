import { useNotifications } from '@/composables/useNotifications'

const feed = useNotifications('Admin')
const { notifications, loading, unreadCount, fetchNotifications } = feed
const markAsRead = notification =>
  feed
    .markAsRead(notification)
    .catch(error => console.error('Failed to mark notification as read', error))
const markAllAsRead = () =>
  feed
    .markAllAsRead()
    .catch(error =>
      console.error('Failed to mark notifications as read', error)
    )

// Short relative times, such as "5m ago" or "2d ago".
const timeAgo = dateString => {
  if (!dateString) return ''
  const seconds = Math.floor(
    (Date.now() - new Date(dateString).getTime()) / 1000
  )
  if (Number.isNaN(seconds)) return ''
  if (seconds < 60) return 'Just now'
  if (seconds < 3600) return `${Math.floor(seconds / 60)}m ago`
  if (seconds < 86400) return `${Math.floor(seconds / 3600)}h ago`
  return `${Math.floor(seconds / 86400)}d ago`
}

// Picks the kind from the title, since notifications carry no type of their own; a store application wears the red storefront tile it has everywhere else on the admin pages.
export const adminNotificationKind = notif => {
  const title = String(notif?.title || '').toLowerCase()
  if (title.includes('application'))
    return { key: 'application', tone: 'brand', icon: 'o_storefront' }
  return { key: 'general', tone: 'neutral', icon: 'o_notifications' }
}

// Where tapping one goes: a store application opens the approvals queue, where it waits for a decision.
export const adminNotificationLink = notif =>
  adminNotificationKind(notif).key === 'application' ? '/admin/approvals' : null

export function useAdminNotifications() {
  return {
    notifications,
    loading,
    unreadCount,
    fetchNotifications,
    markAsRead,
    markAllAsRead,
    timeAgo
  }
}
