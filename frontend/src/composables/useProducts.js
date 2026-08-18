import { ref } from 'vue'
import { api } from '@/boot/axios'

export function useProducts() {
  const products = ref([])
  const loading = ref(false)

  const fetchProducts = async () => {
    loading.value = true
    try {
      const lat = localStorage.getItem('consumer_lat')
      const lng = localStorage.getItem('consumer_lng')
      const params = (lat && lng) ? { lat, lng } : {}
      
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
    } finally {
      loading.value = false
    }
  }

  return { products, loading, fetchProducts }
}
