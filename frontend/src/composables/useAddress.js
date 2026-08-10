import { ref } from 'vue'

const STORAGE_KEY = 'consumer_address'
const DEFAULT_ADDRESS = '123 Shaw Boulevard, Barangay Pleasant Hills, Mandaluyong City'

// Module-level singleton, like useCart, so every page and the header pill share one address.
const address = ref(localStorage.getItem(STORAGE_KEY) || DEFAULT_ADDRESS)

export function useAddress() {
  const setAddress = (value) => {
    address.value = value
    localStorage.setItem(STORAGE_KEY, value)
  }

  return { address, setAddress }
}
