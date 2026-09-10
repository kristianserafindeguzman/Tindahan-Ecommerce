// Distance formatting and measurement, extracted from eight consumer files that each carried an identical copy.

/** Metres to a short string such as 820 m away or 1.4 km away, or an empty string for null. */
export function formatDistance(meters) {
  if (meters == null) return ''
  const rounded = Math.round(meters)
  if (rounded < 1000) return `${rounded} m away`
  return `${(meters / 1000).toFixed(1)} km away`
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
