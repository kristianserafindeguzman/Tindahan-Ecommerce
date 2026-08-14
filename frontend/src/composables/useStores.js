import { ref } from 'vue'
import { api } from '@/boot/axios'

export function useStores() {
  const stores = ref([])
  const loading = ref(false)

  const fetchStores = async () => {
    loading.value = true
    try {
      const lat = localStorage.getItem('consumer_lat')
      const lng = localStorage.getItem('consumer_lng')
      const params = (lat && lng) ? { lat, lng } : {}
      
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
    } finally {
      loading.value = false
    }
  }

  return { stores, loading, fetchStores }
}
