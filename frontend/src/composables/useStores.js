import { ref } from 'vue'
import { api } from '@/boot/axios'

export function useStores() {
  const stores = ref([])
  const loading = ref(false)

  const fetchStores = async () => {
    loading.value = true
    try {
      const { data } = await api.get('/stores')
      stores.value = (data || []).map((store) => ({
        id: store.id,
        name: store.name,
        address: store.address,
        image: store.image,
        isOpen: store.isOpen,
        closesAt: store.closesAt
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
