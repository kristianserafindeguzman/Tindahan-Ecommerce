// A store's saved hours as a Monday-to-Sunday list, for the admin review and vendor dialogs.
const DAYS = [
  'Monday',
  'Tuesday',
  'Wednesday',
  'Thursday',
  'Friday',
  'Saturday',
  'Sunday'
]

// "07:00:00" reads as "7:00 AM".
const clock = time => {
  if (!time) return null
  const [h, m] = String(time).split(':')
  const hours = parseInt(h, 10)
  if (Number.isNaN(hours)) return null
  return `${hours % 12 || 12}:${(m || '00').slice(0, 2)} ${hours >= 12 ? 'PM' : 'AM'}`
}

// Each day's open state and hours; stores that only kept a list of day names share one opening and closing time.
export const weeklyHours = (operatingDays, openingTime, closingTime) => {
  let raw = operatingDays
  if (typeof raw === 'string') {
    try {
      raw = JSON.parse(raw)
    } catch {
      raw = null
    }
  }
  const shared =
    clock(openingTime) && clock(closingTime)
      ? `${clock(openingTime)} – ${clock(closingTime)}`
      : null

  if (Array.isArray(raw)) {
    return DAYS.map(day => {
      const open = raw.some(d =>
        day.toLowerCase().startsWith(String(d).toLowerCase())
      )
      return { day, open, text: open ? shared || 'Open' : 'Closed' }
    })
  }

  if (raw && typeof raw === 'object') {
    return DAYS.map(day => {
      const data = raw[day] ?? raw[day.toLowerCase()]
      const open = typeof data === 'boolean' ? data : !!data?.is_open
      const from = clock(data?.opening_time)
      const to = clock(data?.closing_time)
      return {
        day,
        open,
        text: open
          ? from && to
            ? `${from} – ${to}`
            : shared || 'Open'
          : 'Closed'
      }
    })
  }

  return []
}
