import { ref } from 'vue'

const STORAGE_KEY = 'consumer_address'

// Module-level singleton, like useCart, so every page and the header pill share one address.
// Empty until the user actually confirms one — SiteHeader.vue shows an "Enter Address" placeholder for this state.
const address = ref(localStorage.getItem(STORAGE_KEY) || '')

export function useAddress() {
  const setAddress = (value) => {
    address.value = value
    localStorage.setItem(STORAGE_KEY, value)
  }

  return { address, setAddress }
}
