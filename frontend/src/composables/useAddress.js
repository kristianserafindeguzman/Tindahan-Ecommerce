import { ref } from 'vue'
import { getCurrentPosition, reverseGeocode } from '@/utils/geolocation'

const STORAGE_KEY = 'consumer_address'
const LAT_KEY = 'consumer_lat'
const LNG_KEY = 'consumer_lng'

// Module-level singleton so every page and the header pill share one address, empty until the user confirms one.
const address = ref(localStorage.getItem(STORAGE_KEY) || '')

// Module-level guard so the auto-detect prompt fires only once per session, however many pages mount without an address.
let autoDetectAttempted = false

export function useAddress() {
  const setAddress = (value, lat = null, lng = null) => {
    address.value = value
    
    if (value && lat && lng) {
      localStorage.setItem(STORAGE_KEY, value)
      localStorage.setItem(LAT_KEY, lat)
      localStorage.setItem(LNG_KEY, lng)
    } else if (value) {
      // If they manually type an address without coordinates, keep the string but clear coordinates
      localStorage.setItem(STORAGE_KEY, value)
      localStorage.removeItem(LAT_KEY)
      localStorage.removeItem(LNG_KEY)
    } else {
      // Cleared entirely
      localStorage.removeItem(STORAGE_KEY)
      localStorage.removeItem(LAT_KEY)
      localStorage.removeItem(LNG_KEY)
    }
  }

  // Silently detects and saves the browser's location on first load for anyone who has not set an address yet.
  const autoDetectAddress = async () => {
    if (autoDetectAttempted || address.value) return
    autoDetectAttempted = true

    try {
      const { latitude, longitude } = await getCurrentPosition()
      const resolvedAddress = await reverseGeocode(latitude, longitude)
      setAddress(resolvedAddress, latitude, longitude)
    } catch (error) {
      console.warn('Automatic location detection skipped:', error.message)
    }
  }

  return { address, setAddress, autoDetectAddress }
}
