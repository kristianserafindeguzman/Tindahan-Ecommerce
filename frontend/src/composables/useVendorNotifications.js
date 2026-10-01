import { ref, computed } from 'vue'
import { api } from '@/boot/axios'
import { statusIcon } from '@/utils/orderStatus'
import { useLanguage } from '@/composables/useLanguage'

const { lang } = useLanguage()

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

// Short relative times, such as "5m ago" or "2d ago", which read the same in Taglish.
const timeAgo = (dateString) => {
  if (!dateString) return ''
  const seconds = Math.floor((Date.now() - new Date(dateString).getTime()) / 1000)
  if (Number.isNaN(seconds)) return ''
  if (seconds < 60) return lang.value === 'ph' ? 'Ngayon lang' : 'Just now'
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

// The backend writes vendor notifications in English. These are the sentences it sends (CartController,
// OrderController, AutoCancelOrders), taken apart so they can be shown in Taglish and so the pickup time
// and cancel reason can sit on their own line. Anything unrecognised is shown exactly as sent.
const TEXT = {
  en: {
    titles: {},
    placed: (id, amount) => `Order #${id} was placed for ${amount}.`,
    customerCancelled: id => `A customer cancelled order #${id}.`,
    autoCancelled: id => `Order #${id} was cancelled automatically.`,
    reasons: {},
    pickupLabel: 'Pickup',
    reasonLabel: 'Reason'
  },
  ph: {
    titles: {
      'New Order': 'Bagong Order',
      'Order Cancelled': 'Kinansela ang Order',
      'Order Cancelled Automatically': 'Na-auto-cancel ang Order'
    },
    placed: (id, amount) => `May bagong order #${id} na ${amount}.`,
    customerCancelled: id => `Kinansela ng customer ang order #${id}.`,
    autoCancelled: id => `Na-auto-cancel ang order #${id}.`,
    reasons: {
      'Vendor did not prepare the order in time.': 'Hindi naihanda ng tindahan ang order sa takdang oras.',
      'Consumer did not pick up the order in time.': 'Hindi nakuha ng customer ang order sa takdang oras.'
    },
    pickupLabel: 'Pickup',
    reasonLabel: 'Dahilan'
  }
}

const PLACED = /^Order #(\d+) was placed for (₱[\d,.]+)\.(?: Pickup: (.+?)\.)?$/
const CUSTOMER_CANCELLED = /^A customer cancelled order #(\d+)\.$/
const AUTO_CANCELLED = /^Order #(\d+) was cancelled automatically\. Reason: (.+)$/

/** { title, message, pickup, reason, pickupLabel, reasonLabel } in the current language; pickup and reason are null when absent. */
export const notificationText = (notif) => {
  const text = TEXT[lang.value] || TEXT.en
  const title = String(notif?.title || '')
  const message = String(notif?.message || '').trim()
  const result = {
    title: text.titles[title] || title,
    message,
    pickup: null,
    reason: null,
    pickupLabel: text.pickupLabel,
    reasonLabel: text.reasonLabel
  }

  let match
  if ((match = message.match(PLACED))) {
    result.message = text.placed(match[1], match[2])
    result.pickup = match[3] || null
  } else if ((match = message.match(CUSTOMER_CANCELLED))) {
    result.message = text.customerCancelled(match[1])
  } else if ((match = message.match(AUTO_CANCELLED))) {
    result.message = text.autoCancelled(match[1])
    result.reason = text.reasons[match[2]] || match[2]
  }

  return result
}

export function useVendorNotifications () {
  return { notifications, loading, unreadCount, fetchNotifications, markAsRead, markAllAsRead, timeAgo }
}
