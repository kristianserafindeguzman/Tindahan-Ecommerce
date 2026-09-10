import { ref } from 'vue'
import { api } from '@/boot/axios'

// Shared like useProducts, so the header and the page no longer fetch the same stores twice.
const stores = ref([])
const loading = ref(false)

let inFlight = null
let inFlightKey = null

const load = async (params) => {
  try {
    const { data } = await api.get('/stores', { params })
    stores.value = (data || []).map((store) => ({
      id: store.id,
      slug: store.slug,
      name: store.name,
      address: store.address,
      image: store.image,
      isOpen: store.isOpen,
      closesAt: store.closesAt,
      scheduleStatusText: store.scheduleStatusText,
      distance_meters: store.distance_meters,
      latitude: store.latitude != null ? Number(store.latitude) : null,
      longitude: store.longitude != null ? Number(store.longitude) : null
    }))
  } catch (error) {
    console.error('Failed to load stores', error)
    stores.value = []
  }
}

export function useStores() {
  const fetchStores = () => {
    const lat = localStorage.getItem('consumer_lat')
    const lng = localStorage.getItem('consumer_lng')
    const params = (lat && lng) ? { lat, lng } : {}
    const key = `${lat}|${lng}`

    if (inFlight && inFlightKey === key) return inFlight

    loading.value = true
    inFlightKey = key

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

  return { stores, loading, fetchStores }
}
