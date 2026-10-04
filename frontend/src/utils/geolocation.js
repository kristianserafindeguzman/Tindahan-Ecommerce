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

// Two parts that differ only in case or a "Brgy." prefix name the same place twice, such as a road named after the barangay it runs through, so the first one written wins.
function joinAddressParts(parts) {
  const seen = new Set()

  return parts
    .filter(Boolean)
    .filter((value) => {
      // The same spellings barangayName recognises, so a part it left alone ("Barangay Lahug") still folds against a bare one.
      const key = value.replace(/^(?:brgy|bgy|barangay)\b\.?\s*/i, '').toLowerCase()
      if (seen.has(key)) return false
      seen.add(key)
      return true
    })
    .join(', ')
}

// Metro Manila's cities belong to "districts" rather than provinces, so the region ("Metro Manila") is what an address written by hand carries instead.
function areaName(county, state) {
  if (county && !/district$/i.test(county)) return county
  // A district with no region above it is still better than nothing, which would leave the address ending at the city.
  return state || county || ''
}

// "Brgy." is how a barangay is written on a Philippine address, while the map data carries the bare name.
function barangayName(barangay) {
  if (!barangay) return ''
  return /^(?:brgy|bgy|barangay)\b/i.test(barangay) ? barangay : 'Brgy. ' + barangay
}

// How a Philippine address is written on a form: house number and street, barangay, city or municipality, province or region, postcode. Only the country is dropped, and the coordinates these addresses travel with are untouched, so distances are unaffected.
// Empty rather than a placeholder sentence, since callers put this straight into an address field a user then saves.
export function formatAddress(data) {
  if (!data || !data.address) {
    return data?.display_name || ''
  }

  const address = data.address
  const street = [address.house_number, address.road].filter(Boolean).join(' ')

  return joinAddressParts([
    street,
    barangayName(address.neighbourhood || address.suburb || address.quarter || address.village),
    address.city || address.town || address.municipality || address.city_district,
    areaName(address.county || address.province, address.state || address.region),
    address.postcode
  ])
}

// A landmark's name leads, since "SM City Cebu" says more than the street it stands on, but not when it only repeats the street or the barangay beside it.
function formatPhotonAddress(properties) {
  const street = [properties.housenumber, properties.street].filter(Boolean).join(' ')
  const barangay = properties.locality || properties.district
  const name = properties.name !== properties.street && properties.name !== barangay ? properties.name : ''

  return joinAddressParts([
    name,
    street,
    barangayName(barangay),
    properties.city,
    areaName(properties.county, properties.state),
    properties.postcode
  ])
}

// Never shown. The area check reads this one, because a shopper who ends a query with a province or region would otherwise match nothing once the short form drops it.
function fullPhotonAddress(properties) {
  const street = [properties.housenumber, properties.street].filter(Boolean).join(' ')

  return joinAddressParts([
    properties.name,
    street,
    properties.locality,
    properties.district,
    properties.city,
    properties.county,
    properties.state,
    properties.country
  ])
}

// Block, lot, phase, purok, zone and unit parts are almost never in OpenStreetMap, and one unmatched word empties Photon's results, so they are dropped before searching.
const UNMAPPED_PART = /\b(?:blk|block|lot|phase|ph|purok|prk|zone|unit|rm|room)\b\.?\s*(?:no\.?\s*)?[\w-]+/gi
// "Brgy. Lahug" finds nothing where "Lahug" finds the barangay.
const BARANGAY_PREFIX = /\b(?:brgy|bgy|barangay)\b\.?(?!\s*hall)\s*/gi
// Formatted addresses now end in a postcode, which names no place Photon can search for and would leave the "last two parts" fallback reading province and postcode instead of barangay and town.
const POSTCODE_SEGMENT = /^\d{4}$/

// The cleaned address first, then without its most specific part, then only its last two parts, usually barangay and town.
function addressSearchQueries(query) {
  const segments = query
    .split(',')
    .map((segment) => segment.replace(UNMAPPED_PART, ' ').replace(BARANGAY_PREFIX, ' ').replace(/\s+/g, ' ').trim())
    .filter((segment) => segment && !POSTCODE_SEGMENT.test(segment))

  return [segments, segments.slice(1), segments.slice(-2)]
    .map((parts) => parts.join(', '))
    .filter((candidate, index, array) => candidate && array.indexOf(candidate) === index)
}

// Photon rather than Nominatim, whose usage policy forbids search-as-you-type from the browser; kept to the Philippines and ranked toward `near` when given.
export async function searchAddresses(query, near = null, signal = undefined) {
  for (const candidate of addressSearchQueries(query)) {
    const matches = (await fetchPhotonResults(candidate, near, signal)).filter((result) => namesArea(result.fullAddress, candidate))
    // After the area check, never before it: a result that shortens to the same line as one the check is about to throw away must not be thrown away with it.
    const results = matches.filter((result, index, array) => array.findIndex((other) => other.address === result.address) === index)
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

  // A segment with no letters, such as a postcode a caller passed straight in, has nothing to check against the full address, which carries none.
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
      address: formatPhotonAddress(feature.properties),
      fullAddress: fullPhotonAddress(feature.properties)
    }))
    // Shortening can make neighbours read alike, and a list repeating one line is worse than a shorter list, but that trimming waits until searchAddresses has checked the area.
    .filter((result) => result.address)
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
