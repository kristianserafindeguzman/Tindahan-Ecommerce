import { ref } from 'vue'
import { api } from '@/boot/axios'

// Module-level, so every caller shares one list. SiteHeader mounts on each consumer
// page and fetches exactly what the page itself fetches, which used to issue two
// identical /products requests per navigation.
const products = ref([])
const loading = ref(false)

// Callers that land in the same tick (header + page onMounted) share one request.
// Keyed by the coordinates it was made with, so changing the delivery address
// mid-flight starts a fresh request instead of joining one made for the old spot.
let inFlight = null
let inFlightKey = null

const load = async (params) => {
  try {
    const { data } = await api.get('/products', { params })
    products.value = (data || []).map((product) => ({
      id: product.id,
      name: product.name,
      description: product.description,
      category: product.category,
      price: Number(product.price),
      image: product.image,
      store: product.store,
      storeId: product.storeId,
      inStock: product.inStock,
      availableQuantity: product.availableQuantity,
      variants: product.variants,
      distance_meters: product.distance_meters
    }))
  } catch (error) {
    console.error('Failed to load products', error)
    products.value = []
  }
}

export function useProducts() {
  const fetchProducts = () => {
    const lat = localStorage.getItem('consumer_lat')
    const lng = localStorage.getItem('consumer_lng')
    const params = (lat && lng) ? { lat, lng } : {}
    const key = `${lat}|${lng}`

    if (inFlight && inFlightKey === key) return inFlight

    loading.value = true
    inFlightKey = key

    // Cleared only if this is still the newest request, so a superseded one
    // can't switch the spinner off while its replacement is running.
    const promise = load(params).finally(() => {
      if (inFlight === promise) {
        inFlight = null
        inFlightKey = null
        loading.value = false
      }
    })
    inFlight = promise

    return promise
  }

  return { products, loading, fetchProducts }
}

