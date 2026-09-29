import { ref, computed } from 'vue'
import { api } from '@/boot/axios'
import { statusIcon } from '@/utils/orderStatus'

// One shared list for the whole vendor area, so the phone bar, the dashboard bell and the notifications page always agree.
const notifications = ref([])
const loading = ref(false)

const unreadCount = computed(() => notifications.value.filter(n => !n.is_read).length)

const fetchNotifications = async () => {
  loading.value = true
  try {
    const { data } = await api.get('/vendor/notifications')
    notifications.value = Array.isArray(data) ? data : data?.data || []
  } catch (error) {
    console.error('Failed to fetch notifications', error)
  } finally {
    loading.value = false
  }
}

// Marks one as read straight away and puts it back if the server refuses.
const markAsRead = async (notification) => {
  if (notification.is_read) return
  notification.is_read = true
  try {
    await api.patch(`/vendor/notifications/${notification.notification_id}/read`)
  } catch (error) {
    notification.is_read = false
    console.error('Failed to mark notification as read', error)
  }
}

const markAllAsRead = async () => {
  const unread = notifications.value.filter(n => !n.is_read)
  unread.forEach(n => { n.is_read = true })
  try {
    await api.post('/vendor/notifications/read-all')
  } catch (error) {
    unread.forEach(n => { n.is_read = false })
    console.error('Failed to mark all notifications as read', error)
  }
}

// Short relative times, such as "5m ago" or "2d ago".
const timeAgo = (dateString) => {
  if (!dateString) return ''
  const seconds = Math.floor((Date.now() - new Date(dateString).getTime()) / 1000)
  if (Number.isNaN(seconds)) return ''
  if (seconds < 60) return 'Just now'
  if (seconds < 3600) return `${Math.floor(seconds / 60)}m ago`
  if (seconds < 86400) return `${Math.floor(seconds / 3600)}h ago`
  return `${Math.floor(seconds / 86400)}d ago`
}

// Picks the kind from the title, since notifications carry no type of their own; the icons and colours match the order status badges.
export const notificationKind = (notif) => {
  const title = String(notif?.title || '').toLowerCase()
  if (title.includes('cancel')) return { key: 'cancelled', tone: 'cancelled', icon: statusIcon('cancelled') }
  if (title.includes('ready')) return { key: 'order', tone: 'ready', icon: statusIcon('ready_for_pickup') }
  if (title.includes('picked')) return { key: 'order', tone: 'done', icon: statusIcon('picked_up') }
  if (title.includes('order')) return { key: 'order', tone: 'placed', icon: statusIcon('placed') }
  return { key: 'general', tone: 'neutral', icon: 'o_notifications' }
}

export function useVendorNotifications () {
  return { notifications, loading, unreadCount, fetchNotifications, markAsRead, markAllAsRead, timeAgo }
}
