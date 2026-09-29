export const NOTIFICATION_READ_STATE_EVENT = 'consumer-notification-read-state'

export function applyNotificationReadState(notifications, detail = {}) {
  if (!Array.isArray(notifications)) return

  if (detail.all) {
    notifications.forEach(notification => {
      notification.is_read = detail.isRead
    })
    return
  }

  const notification = notifications.find(
    item => String(item.notification_id) === String(detail.notificationId)
  )
  if (notification) notification.is_read = detail.isRead
}

export function publishNotificationReadState(detail) {
  if (typeof window === 'undefined') return
  window.dispatchEvent(new CustomEvent(NOTIFICATION_READ_STATE_EVENT, { detail }))
}
