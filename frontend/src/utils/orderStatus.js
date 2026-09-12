// Order status colours, icons and labels shared by every vendor page, so a status looks the same wherever it appears.
const META = {
  placed: { tone: 'placed', icon: 'o_receipt_long' },
  pending: { tone: 'preparing', icon: 'o_schedule' },
  preparing: { tone: 'preparing', icon: 'o_inventory_2' },
  ready: { tone: 'ready', icon: 'o_storefront' },
  ready_for_pickup: { tone: 'ready', icon: 'o_storefront' },
  picked_up: { tone: 'done', icon: 'o_task_alt' },
  completed: { tone: 'done', icon: 'o_task_alt' },
  cancelled: { tone: 'cancelled', icon: 'o_cancel' }
}

// Turns "Ready for pickup" or "ready_for_pickup" into the same key.
export const statusKey = status => String(status || '').trim().toLowerCase().replace(/\s+/g, '_')

export const statusTone = status => META[statusKey(status)]?.tone || 'neutral'

export const statusIcon = status => META[statusKey(status)]?.icon || 'o_help_outline'

// Sentence case, such as "Ready for pickup", the way the consumer side writes it.
export const statusLabel = status => {
  const text = statusKey(status).split('_').filter(Boolean).join(' ')
  return text ? text.charAt(0).toUpperCase() + text.slice(1) : ''
}
