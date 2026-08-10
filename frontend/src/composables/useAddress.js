import { ref } from 'vue'

const STORAGE_KEY = 'consumer_address'
const LAT_KEY = 'consumer_lat'
const LNG_KEY = 'consumer_lng'

// Module-level singleton, like useCart, so every page and the header pill share one address.
// Empty until the user actually confirms one — SiteHeader.vue shows an "Enter Address" placeholder for this state.
const address = ref(localStorage.getItem(STORAGE_KEY) || '')

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

  return { address, setAddress }
}
