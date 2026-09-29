// Account and application status colours and labels for the admin pages, in the same palette as the order status badges.
const TONES = {
  active: 'done',
  approved: 'done',
  inactive: 'neutral',
  suspended: 'cancelled',
  rejected: 'cancelled',
  pending: 'preparing',
  deleted: 'neutral'
}

export const accountStatusTone = status =>
  TONES[String(status || '').toLowerCase()] || 'neutral'

// Sentence case, such as "Suspended".
export const accountStatusLabel = status => {
  const text = String(status || '')
    .replace(/_/g, ' ')
    .trim()
    .toLowerCase()
  return text ? text.charAt(0).toUpperCase() + text.slice(1) : 'Unknown'
}

// Short relative times for "last active", such as "5 mins ago", then the date after a week.
export const formatActivity = timestamp => {
  if (!timestamp) return 'Never'
  const then = new Date(timestamp)
  const seconds = Math.floor((Date.now() - then.getTime()) / 1000)
  if (Number.isNaN(seconds)) return 'Never'
  if (seconds < 60) return 'Just now'
  if (seconds < 3600) return `${Math.floor(seconds / 60)} mins ago`
  if (seconds < 86400) return `${Math.floor(seconds / 3600)} hours ago`
  if (seconds < 604800) return `${Math.floor(seconds / 86400)} days ago`
  return then.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric'
  })
}

export const formatShortDate = value => {
  if (!value) return '—'
  const date = new Date(value)
  return Number.isNaN(date.getTime())
    ? '—'
    : date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
      })
}
