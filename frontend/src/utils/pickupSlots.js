// Pickup slots for "Schedule for later" at checkout, built from a store's weekly hours.
//
// Everything is worked out in Manila time, which is how the backend stores and checks store
// hours (StoreHoursService), whatever time zone the shopper's device is set to. The rules match
// CartController::checkout, so the picker never offers a time the order would be refused for.

export const STORE_TIME_ZONE = 'Asia/Manila'
const MANILA_OFFSET = '+08:00' // The Philippines has no daylight saving time.

export const SLOT_MINUTES = 15
export const LEAD_MINUTES = 30 // The earliest slot today starts at least this far from now.
export const DAYS_AHEAD = 3 // Today, tomorrow and the day after.

const DAY_MS = 24 * 60 * 60 * 1000

// Manila calendar date, weekday and minute of the day for a moment in time.
export function manilaParts(date) {
  const parts = Object.fromEntries(
    new Intl.DateTimeFormat('en-US', {
      timeZone: STORE_TIME_ZONE,
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      weekday: 'long',
      hour: '2-digit',
      minute: '2-digit',
      hourCycle: 'h23'
    })
      .formatToParts(date)
      .map((part) => [part.type, part.value])
  )

  return {
    ymd: `${parts.year}-${parts.month}-${parts.day}`,
    weekday: parts.weekday,
    minutes: Number(parts.hour) * 60 + Number(parts.minute)
  }
}

const toMinutes = (hm) => {
  const [h, m] = String(hm).split(':').map(Number)
  return h * 60 + m
}

// The open windows of one day as [start, end] minute ranges. Hours running past midnight open
// from the opening time to midnight and from midnight to the closing time, as the backend reads them.
export function openWindows(dayHours) {
  if (!dayHours?.opens || !dayHours?.closes) return []
  const opens = toMinutes(dayHours.opens)
  // "Open all day" is stored as 00:00–23:59; its last minute runs to midnight.
  const closes = dayHours.closes === '23:59' ? 1440 : toMinutes(dayHours.closes)

  return opens <= closes ? [[opens, closes]] : [[0, closes], [opens, 1440]]
}

const pad = (n) => String(n).padStart(2, '0')

// A Manila date and minute of the day as an ISO time the backend can parse unambiguously.
export const slotIso = (ymd, minutes) =>
  `${ymd}T${pad(Math.floor(minutes / 60))}:${pad(minutes % 60)}:00${MANILA_OFFSET}`

/**
 * A saved pickup slot for display, e.g. "Wed, Sep 30 · 3:00 PM – 3:15 PM", in Manila time.
 * With { compact: true } only the start is shown ("Wed, Sep 30 · 3:00 PM"), for tight spots
 * such as a table column. Returns '' for an ASAP order (no scheduled time), so callers can show
 * their own ASAP label.
 */
export function formatPickupSlot(value, locale = 'en-PH', { compact = false } = {}) {
  if (!value) return ''
  const start = new Date(value)
  if (Number.isNaN(start.getTime())) return ''
  const end = new Date(start.getTime() + SLOT_MINUTES * 60000)

  const day = start.toLocaleDateString(locale, { weekday: 'short', month: 'short', day: 'numeric', timeZone: STORE_TIME_ZONE })
  const time = (date) => date.toLocaleTimeString(locale, { hour: 'numeric', minute: '2-digit', hour12: true, timeZone: STORE_TIME_ZONE })

  return compact ? `${day} · ${time(start)}` : `${day} · ${time(start)} – ${time(end)}`
}

/**
 * The next DAYS_AHEAD days, each with the pickup slots the store can take: every
 * SLOT_MINUTES-long slot that starts and ends inside the store's hours, and, today, starts at
 * least LEAD_MINUTES from now. `hours` is the store's week keyed by day name, as the stores API
 * sends it. Each slot is { iso, start } where start is its minute of the day.
 */
export function buildPickupDays(hours, now = new Date()) {
  const today = manilaParts(now)
  const earliestToday = Math.ceil((today.minutes + LEAD_MINUTES) / SLOT_MINUTES) * SLOT_MINUTES

  const days = []
  for (let offset = 0; offset < DAYS_AHEAD; offset++) {
    const { ymd, weekday } = manilaParts(new Date(now.getTime() + offset * DAY_MS))
    const slots = []

    for (const [start, end] of openWindows(hours?.[weekday])) {
      let from = Math.ceil(start / SLOT_MINUTES) * SLOT_MINUTES
      if (offset === 0) from = Math.max(from, earliestToday)

      for (let slot = from; slot + SLOT_MINUTES <= end; slot += SLOT_MINUTES) {
        slots.push({ iso: slotIso(ymd, slot), start: slot })
      }
    }

    days.push({ offset, ymd, weekday, slots })
  }

  return days
}
