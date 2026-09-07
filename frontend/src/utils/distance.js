// Distance formatting and measurement.
//
// Extracted because this was copy-pasted into eight consumer files with identical bodies
// (both cards, the detail modal, the map, checkout, store detail, and both order
// screens), which is eight places to fix if the wording or rounding ever changes.

/**
 * Metres to a short human string, e.g. "820 m away" / "1.4 km away".
 * Returns '' for null/undefined so callers can drop it into a template unguarded.
 */
export function formatDistance(meters) {
  if (meters == null) return ''
  const rounded = Math.round(meters)
  if (rounded < 1000) return `${rounded} m away`
  return `${(meters / 1000).toFixed(1)} km away`
}

/**
 * Coordinate to a finite number, or null. Mirrors PHP's is_numeric(), which the
 * backend guards with: null, undefined, '' and non-numeric strings are all rejected,
 * numeric strings are accepted.
 *
 * Deliberately not Number() alone — Number(null) is 0, a perfectly valid latitude,
 * so a missing coordinate would silently measure the distance to null island off
 * the coast of Africa rather than reporting that it cannot be measured.
 */
function toCoord(value) {
  if (value === null || value === undefined || value === '') return null
  const n = Number(value)
  return Number.isFinite(n) ? n : null
}

/**
 * Great-circle distance in metres (Haversine), or null if any coordinate is missing
 * or non-numeric. Mirrors the backend's DistanceService, including its guard — the
 * orders endpoints return raw coordinates rather than a precomputed distance_meters,
 * so the order screens have to measure it client-side.
 *
 * Pass the raw values; conversion happens here.
 */
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

