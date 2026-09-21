import { ref, computed } from 'vue'
import { api } from '@/boot/axios'

// Module-level singleton so the header badge, cart dropdown, product modal and cart page all share one cart.
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
        variantName: item.variantName,
        name: item.name,
        image: item.image,
        price: Number(item.price),
        quantity: item.quantity,
        availableQuantity: item.availableQuantity,
        inStock: item.inStock,
        store: item.store,
        storeId: item.storeId,
        expiresAt: item.expiresAt,
        isExpired: item.isExpired
      }))
    } catch (error) {
      console.error('Failed to load cart', error)
      items.value = []
    } finally {
      loading.value = false
    }
  }

  const addToCart = async (inventoryId, quantity = 1, variantName = null) => {
    await api.post('/consumer/cart', {
      inventory_id: inventoryId,
      quantity,
      variant_name: variantName
    })
    await fetchCart()
  }

  // Updates the local item immediately and rolls it back if the request fails, since the server stays the source of truth.
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
    const lat = Number(localStorage.getItem('consumer_lat'))
    const lng = Number(localStorage.getItem('consumer_lng'))
    const hasLocation = Number.isFinite(lat) && Number.isFinite(lng) && (lat || lng)

    // Always sent, null included: the backend requires a location and rejects the checkout with
    // LOCATION_REQUIRED, so the keys are never quietly dropped from the payload.
    const payload = {
      store_id: storeId,
      consumer_latitude: hasLocation ? lat : null,
      consumer_longitude: hasLocation ? lng : null
    }

    const { data } = await api.post('/consumer/checkout', payload)
    await fetchCart() // Refresh cart to remove checked-out items
    return data
  }

  return { items, loading, itemCount, subtotal, fetchCart, addToCart, updateQuantity, removeFromCart, checkout }
}
