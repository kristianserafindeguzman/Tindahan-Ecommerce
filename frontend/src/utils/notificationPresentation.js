const NOTIFICATION_STAGES = [
  { test: /cancel|reject|fail/, icon: 'o_cancel', tone: 'cancelled' },
  { test: /picked up|collected|complete/, icon: 'o_task_alt', tone: 'done' },
  { test: /ready/, icon: 'o_storefront', tone: 'ready' },
  { test: /prepar|process/, icon: 'o_inventory_2', tone: 'preparing' },
  { test: /placed|order received|confirmed/, icon: 'o_shopping_cart', tone: 'placed' }
]

const DEFAULT_STAGE = { icon: 'o_notifications', tone: 'brand' }

export function notificationPresentation(notification) {
  const text = `${notification?.title || ''} ${notification?.message || ''}`.toLowerCase()
  return NOTIFICATION_STAGES.find(stage => stage.test.test(text)) || DEFAULT_STAGE
}

export function notificationTime(value, t, locale) {
  if (!value) return ''
  const then = new Date(value)
  if (Number.isNaN(then.getTime())) return ''
  const seconds = Math.max(0, Math.floor((Date.now() - then.getTime()) / 1000))
  if (seconds < 60) return t('Just now')
  const minutes = Math.floor(seconds / 60)
  if (minutes < 60) return t(minutes === 1 ? '{count} min ago' : '{count} mins ago', { count: minutes })
  const hours = Math.floor(minutes / 60)
  if (hours < 24) return t(hours === 1 ? '{count} hour ago' : '{count} hours ago', { count: hours })
  const days = Math.floor(hours / 24)
  if (days < 7) return t(days === 1 ? '{count} day ago' : '{count} days ago', { count: days })
  return then.toLocaleDateString(locale, { month: 'short', day: 'numeric', year: 'numeric' })
}
