import { ref } from 'vue'
import { getCurrentPosition, reverseGeocode } from '@/utils/geolocation'

const STORAGE_KEY = 'consumer_address'
const LAT_KEY = 'consumer_lat'
const LNG_KEY = 'consumer_lng'

// Module-level singleton so every page and the header pill share one address, empty until the user confirms one.
const address = ref(localStorage.getItem(STORAGE_KEY) || '')

// Bumped on every save, because the coordinates live in localStorage, which is not reactive, and they now change on their own: a lookup that came back with no text writes a pin while the address ref stays as it was.
const locationVersion = ref(0)

// Module-level guard so the auto-detect prompt fires only once per session, however many pages mount without an address.
let autoDetectAttempted = false

export function useAddress() {
  const setAddress = (value, lat = null, lng = null) => {
    address.value = value

    if (value) {
      localStorage.setItem(STORAGE_KEY, value)
    } else {
      localStorage.removeItem(STORAGE_KEY)
    }

    // Coordinates are what every distance is measured from, so they are kept even when the lookup came back with no text to show.
    // Typing an address by hand passes none, which clears them, since the words no longer describe the pin.
    if (lat != null && lng != null) {
      localStorage.setItem(LAT_KEY, lat)
      localStorage.setItem(LNG_KEY, lng)
    } else {
      localStorage.removeItem(LAT_KEY)
      localStorage.removeItem(LNG_KEY)
    }

    locationVersion.value++
  }

  // Silently detects and saves the browser's location on first load for anyone who has not set an address yet.
  const autoDetectAddress = async () => {
    if (autoDetectAttempted || address.value) return
    autoDetectAttempted = true

    try {
      const { latitude, longitude } = await getCurrentPosition()
      const resolvedAddress = await reverseGeocode(latitude, longitude)
      // An empty lookup leaves the pill asking for an address, but the coordinates are still worth keeping: they are what sorts stores by distance.
      setAddress(resolvedAddress, latitude, longitude)
    } catch (error) {
      console.warn('Automatic location detection skipped:', error.message)
    }
  }

  // Same lookup as autoDetectAddress, but user-initiated: it ignores the once-per-session guard
  // and runs even when an address string is already saved without coordinates. Resolves to true
  // when coordinates were saved. Used by checkout, which cannot proceed without them.
  const detectAddress = async () => {
    try {
      const { latitude, longitude } = await getCurrentPosition()
      const resolvedAddress = await reverseGeocode(latitude, longitude)
      // The coordinates are what checkout needs, so they are saved even when the reverse lookup
      // comes back empty; the pill then shows the coordinates rather than nothing.
      setAddress(resolvedAddress || `${latitude.toFixed(5)}, ${longitude.toFixed(5)}`, latitude, longitude)
      return true
    } catch (error) {
      console.warn('Location detection failed:', error.message)
      return false
    }
  }

  return { address, locationVersion, setAddress, autoDetectAddress, detectAddress }
}
