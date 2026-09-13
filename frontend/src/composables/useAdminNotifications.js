import { ref, computed } from 'vue'
import { api } from '@/boot/axios'

// One shared list for the whole admin area, so the phone bar, the banner bell and the notifications page always agree.
const notifications = ref([])
const loading = ref(false)

const unreadCount = computed(
  () => notifications.value.filter(n => !n.is_read).length
)

const fetchNotifications = async () => {
  loading.value = true
  try {
    const { data } = await api.get('/admin/notifications')
    notifications.value = Array.isArray(data) ? data : data?.data || []
  } catch (error) {
    console.error('Failed to fetch notifications', error)
  } finally {
    loading.value = false
  }
}

// Marks one as read straight away and puts it back if the server refuses.
const markAsRead = async notification => {
  if (notification.is_read) return
  notification.is_read = true
  try {
    await api.patch(`/admin/notifications/${notification.notification_id}/read`)
  } catch (error) {
    notification.is_read = false
    console.error('Failed to mark notification as read', error)
  }
}

const markAllAsRead = async () => {
  const unread = notifications.value.filter(n => !n.is_read)
  unread.forEach(n => {
    n.is_read = true
  })
  try {
    await api.post('/admin/notifications/read-all')
  } catch (error) {
    unread.forEach(n => {
      n.is_read = false
    })
    console.error('Failed to mark all notifications as read', error)
  }
}

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
