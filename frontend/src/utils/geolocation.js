export function getCurrentPosition(options = { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }) {
  return new Promise((resolve, reject) => {
    if (!navigator.geolocation) {
      reject(new Error('Geolocation is not supported by this browser.'))
      return
    }

    navigator.geolocation.getCurrentPosition(
      (position) => resolve({ latitude: position.coords.latitude, longitude: position.coords.longitude }),
      (error) => reject(error),
      options
    )
  })
}

// Empty rather than a placeholder sentence, since callers put this straight into an address field a user then saves.
export function formatAddress(data) {
  if (!data || !data.address) {
    return data?.display_name || ''
  }

  const address = data.address

  const parts = [
    address.house_number,
    address.road,
    address.neighbourhood,
    address.suburb,
    address.village,
    address.town,
    address.city,
    address.city_district,
    address.state,
    address.country
  ]

  return parts
    .filter(Boolean)
    .filter((value, index, array) => array.indexOf(value) === index)
    .join(', ')
}

function formatPhotonAddress(properties) {
  const street = [properties.housenumber, properties.street].filter(Boolean).join(' ')

  return [
    properties.name,
    street,
    properties.locality,
    properties.district,
    properties.city,
    properties.county,
    properties.state,
    properties.country
  ]
    .filter(Boolean)
    .filter((value, index, array) => array.indexOf(value) === index)
    .join(', ')
}

// Block, lot, phase, purok, zone and unit parts are almost never in OpenStreetMap, and one unmatched word empties Photon's results, so they are dropped before searching.
const UNMAPPED_PART = /\b(?:blk|block|lot|phase|ph|purok|prk|zone|unit|rm|room)\b\.?\s*(?:no\.?\s*)?[\w-]+/gi
// "Brgy. Lahug" finds nothing where "Lahug" finds the barangay.
const BARANGAY_PREFIX = /\b(?:brgy|bgy|barangay)\b\.?(?!\s*hall)\s*/gi

// The cleaned address first, then without its most specific part, then only its last two parts, usually barangay and town.
function addressSearchQueries(query) {
  const segments = query
    .split(',')
    .map((segment) => segment.replace(UNMAPPED_PART, ' ').replace(BARANGAY_PREFIX, ' ').replace(/\s+/g, ' ').trim())
    .filter(Boolean)

  return [segments, segments.slice(1), segments.slice(-2)]
    .map((parts) => parts.join(', '))
    .filter((candidate, index, array) => candidate && array.indexOf(candidate) === index)
}

// Photon rather than Nominatim, whose usage policy forbids search-as-you-type from the browser; kept to the Philippines and ranked toward `near` when given.
export async function searchAddresses(query, near = null, signal = undefined) {
  for (const candidate of addressSearchQueries(query)) {
    const results = (await fetchPhotonResults(candidate, near, signal)).filter((result) => namesArea(result.address, candidate))
    if (results.length) return results
  }

  return []
}

// Folds "Parañaque" and "Paranaque" together, since most keyboards type the plain n and Photon already matches either way.
function foldText(text) {
  return text.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase()
}

// Photon can match only the start of "Sitio Mahayahay, Talamban" and land on a Mahayahay in another province, so a result must name the area the query ends with.
function namesArea(address, candidate) {
  const parts = candidate.split(', ')
  if (parts.length < 2) return true

  const area = foldText(parts[parts.length - 1].replace(/\b(?:city|municipality|of)\b/gi, ' ').replace(/\s+/g, ' ').trim())

  // A trailing postcode has no letters to check, and the formatted address never carries one.
  return !/[a-z]/.test(area) || foldText(address).includes(area)
}

async function fetchPhotonResults(query, near, signal) {
  const params = new URLSearchParams({ q: query, limit: '5', bbox: '116.9,4.5,126.7,21.2' })

  if (near?.latitude != null && near?.longitude != null) {
    params.set('lat', near.latitude)
    params.set('lon', near.longitude)
  }

  const response = await fetch(`https://photon.komoot.io/api/?${params}`, { signal })

  if (!response.ok) {
    throw new Error('Address search failed')
  }

  const data = await response.json()

  return data.features
    .map((feature) => ({
      latitude: feature.geometry.coordinates[1],
      longitude: feature.geometry.coordinates[0],
      address: formatPhotonAddress(feature.properties)
    }))
    .filter((result, index, array) => result.address && array.findIndex((other) => other.address === result.address) === index)
}

// A lookup nobody is waiting on forever: the pin is already placed, and the address can be typed instead.
const REVERSE_TIMEOUT_MS = 8000

// Photon first. It is the same server as the address search and tolerates the traffic a pin-dragging session makes, while Nominatim's public service allows about one request a second and answers 429 after that.
export async function reverseGeocode(latitude, longitude) {
  const nearest = await fetchReverse(
    `https://photon.komoot.io/reverse?lat=${latitude}&lon=${longitude}&limit=1`,
    (data) => (data.features?.length ? formatPhotonAddress(data.features[0].properties) : '')
  )
  if (nearest) return nearest

  // Nominatim knows house numbers Photon does not, so it is still worth asking when Photon has nothing.
  return fetchReverse(
    `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${latitude}&lon=${longitude}&zoom=18&addressdetails=1`,
    formatAddress
  )
}

// Empty on any failure, never a placeholder sentence: callers put this straight into an address field that a user then saves.
async function fetchReverse(url, format) {
  // AbortController with a timer rather than AbortSignal.timeout, which older Safari lacks and would turn every lookup into a silent empty address.
  const controller = new AbortController()
  const timeout = setTimeout(() => controller.abort(new Error('Reverse geocoding timed out')), REVERSE_TIMEOUT_MS)

  try {
    const response = await fetch(url, { headers: { Accept: 'application/json' }, signal: controller.signal })

    if (!response.ok) {
      throw new Error('Reverse geocoding failed with status ' + response.status)
    }

    return format(await response.json()) || ''
  } catch (error) {
    console.warn('Address lookup failed:', error.message)
    return ''
  } finally {
    clearTimeout(timeout)
  }
}
