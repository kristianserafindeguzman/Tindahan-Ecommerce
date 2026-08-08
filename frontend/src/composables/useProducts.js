import { ref } from 'vue'
import { api } from '@/boot/axios'

export function useProducts() {
  const products = ref([])
  const loading = ref(false)

  const fetchProducts = async () => {
    loading.value = true
    try {
      const { data } = await api.get('/products')
      products.value = (data || []).map((product) => ({
        id: product.id,
        name: product.name,
        category: product.category,
        price: Number(product.price),
        image: product.image,
        store: product.store,
        storeId: product.storeId,
        inStock: product.inStock,
        availableQuantity: product.availableQuantity,
        variants: product.variants
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
