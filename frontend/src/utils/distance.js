// Distance formatting and measurement, extracted from eight consumer files that each carried an identical copy.
import { useConsumerLanguage } from '../composables/useConsumerLanguage.js'

const { t } = useConsumerLanguage()

// Non-breaking spaces keep "7 m ang layo" on one line when a long address beside it wraps.
const keepTogether = (text) => text.replace(/ /g, ' ')

/** Metres to a short string such as 820 m away or 1.4 km away (in the shopper's language), or an empty string for null. */
export function formatDistance(meters) {
  if (meters == null) return ''
  const rounded = Math.round(meters)
  if (rounded < 1000) return keepTogether(t('{distance} m away', { distance: rounded }))
  return keepTogether(t('{distance} km away', { distance: (meters / 1000).toFixed(1) }))
}

// Travel-time estimate. There is no routing service, only straight-line distance, so these are deliberately plain assumptions.
const ROAD_FACTOR = 1.3 // streets wind; the straight line is shorter than the walk
const WALK_M_PER_MIN = 80 // about 4.8 km/h

/** Straight-line metres to estimated walking minutes, or null for null. */
export function estimateTravel(meters) {
  if (meters == null) return null
  const minutes = Math.max(1, Math.ceil((meters * ROAD_FACTOR) / WALK_M_PER_MIN))
  return { minutes, mode: 'walk' }
}

/** Metres to "7 min walk" or "1 hr 20 min walk", or an empty string for null. Kept in English in both languages by design. */
export function formatTravelTime(meters) {
  const travel = estimateTravel(meters)
  if (!travel) return ''
  if (travel.minutes < 60) return keepTogether(`${travel.minutes} min walk`)

  const hours = Math.floor(travel.minutes / 60)
  const minutes = travel.minutes % 60
  return keepTogether(minutes ? `${hours} hr ${minutes} min walk` : `${hours} hr walk`)
}

/** Material icon for walking travel estimates. */
export function travelIcon() {
  return 'o_directions_walk'
}

/** Coordinate to a finite number or null, rejecting what PHP's is_numeric rejects, since Number(null) would give a valid 0. */
function toCoord(value) {
  if (value === null || value === undefined || value === '') return null
  const n = Number(value)
  return Number.isFinite(n) ? n : null
}

/** Haversine distance in metres, or null when any coordinate is missing, mirroring the backend's DistanceService guard. */
export function calculateDistanceMeters(lat1, lng1, lat2, lng2) {
  const a1 = toCoord(lat1)
  const o1 = toCoord(lng1)
  const a2 = toCoord(lat2)
  const o2 = toCoord(lng2)

  if (a1 === null || o1 === null || a2 === null || o2 === null) return null

  const EARTH_RADIUS_M = 6371000
  const toRad = (deg) => (deg * Math.PI) / 180

  const dLat = toRad(a2 - a1)
  const dLng = toRad(o2 - o1)

  const a =
    Math.sin(dLat / 2) ** 2 +
    Math.cos(toRad(a1)) * Math.cos(toRad(a2)) * Math.sin(dLng / 2) ** 2

  return EARTH_RADIUS_M * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a))
}
