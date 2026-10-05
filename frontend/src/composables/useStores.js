import { ref } from 'vue'
import { api } from '@/boot/axios'

// Shared like useProducts, so the header and the page no longer fetch the same stores twice.
const stores = ref([])
const loading = ref(false)

let inFlight = null
let inFlightKey = null
let latestRequest = 0

const toCoordinate = (value, limit) => {
  if (value == null || String(value).trim() === '') return null
  const number = Number(value)
  return Number.isFinite(number) && Math.abs(number) <= limit ? number : null
}

const load = async (params, { silent, requestId }) => {
  try {
    const { data } = await api.get('/stores', { params })
    if (requestId !== latestRequest) return
    stores.value = (data || []).map((store) => ({
      id: store.id,
      slug: store.slug,
      name: store.name,
      address: store.address,
      image: store.image,
      isOpen: store.isOpen,
      closesAt: store.closesAt,
      scheduleStatusText: store.scheduleStatusText,
      hours: store.hours || null,
      distance_meters: store.distance_meters,
      latitude: toCoordinate(store.latitude, 90),
      longitude: toCoordinate(store.longitude, 180)
    }))
  } catch (error) {
    if (requestId !== latestRequest) return
    console.error('Failed to load stores', error)
    if (!silent) stores.value = []
  }
}

export function useStores() {
  const fetchStores = ({ silent = false } = {}) => {
    const lat = localStorage.getItem('consumer_lat')
    const lng = localStorage.getItem('consumer_lng')
    const params = (lat && lng) ? { lat, lng } : {}
    const key = `${lat}|${lng}`

    if (inFlight && inFlightKey === key) return inFlight

    if (!silent) loading.value = true
    inFlightKey = key

    const requestId = ++latestRequest
    const promise = load(params, { silent, requestId }).finally(() => {
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
