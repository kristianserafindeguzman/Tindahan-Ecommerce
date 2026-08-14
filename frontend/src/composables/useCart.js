import { ref, computed } from 'vue'
import { api } from '@/boot/axios'

// Module-level singleton (unlike useProducts/useStores) so the header badge, the
// mini-cart dropdown, the product detail page, and the full cart page all observe
// the same state after any of them adds/updates/removes an item.
const items = ref([])
const loading = ref(false)

export function useCart() {
  const itemCount = computed(() => items.value.reduce((sum, item) => sum + item.quantity, 0))
  const subtotal = computed(() => items.value.reduce((sum, item) => sum + item.price * item.quantity, 0))

  const fetchCart = async () => {
    loading.value = true
    try {
      const { data } = await api.get('/consumer/cart')
      items.value = (data || []).map((item) => ({
        cartId: item.cartId,
        inventoryId: item.inventoryId,
        name: item.name,
        image: item.image,
        price: Number(item.price),
        quantity: item.quantity,
        availableQuantity: item.availableQuantity,
        inStock: item.inStock,
        store: item.store,
        storeId: item.storeId
      }))
    } catch (error) {
      console.error('Failed to load cart', error)
      items.value = []
    } finally {
      loading.value = false
    }
  }

  const addToCart = async (inventoryId, quantity = 1) => {
    await api.post('/consumer/cart', { inventory_id: inventoryId, quantity })
    await fetchCart()
  }

  // Updates the local item immediately instead of waiting on a mutation + a full re-fetch round-trip —
  // rolled back if the request fails, since the server is still the source of truth.
  const updateQuantity = async (cartId, quantity) => {
    const item = items.value.find((i) => i.cartId === cartId)
    const previousQuantity = item?.quantity
    if (item) item.quantity = quantity

    try {
      await api.patch(`/consumer/cart/${cartId}`, { quantity })
    } catch (error) {
      if (item) item.quantity = previousQuantity
      throw error
    }
  }

  const removeFromCart = async (cartId) => {
    const index = items.value.findIndex((i) => i.cartId === cartId)
    const removed = index !== -1 ? items.value.splice(index, 1)[0] : null

    try {
      await api.delete(`/consumer/cart/${cartId}`)
    } catch (error) {
      if (removed) items.value.splice(index, 0, removed)
      throw error
    }
  }

  const checkout = async (storeId) => {
    const lat = localStorage.getItem('consumer_lat')
    const lng = localStorage.getItem('consumer_lng')
    
    const payload = { store_id: storeId }
    if (lat && !isNaN(lat)) payload.consumer_latitude = parseFloat(lat)
    if (lng && !isNaN(lng)) payload.consumer_longitude = parseFloat(lng)
    
    const { data } = await api.post('/consumer/checkout', payload)
    await fetchCart() // Refresh cart to remove checked-out items
    return data
  }

  return { items, loading, itemCount, subtotal, fetchCart, addToCart, updateQuantity, removeFromCart, checkout }
}
