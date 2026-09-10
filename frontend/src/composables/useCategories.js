import { ref } from 'vue'
import { api } from '@/boot/axios'

// The categories table has no icon column, so each category name maps to a filled icon and tone here, with a generic fallback.
const CATEGORY_STYLES = {
  'Cooking Essentials': { icon: 'restaurant', tone: 'amber' },
  'Beverages': { icon: 'local_drink', tone: 'blue' },
  'Snacks & Sweets': { icon: 'fastfood', tone: 'orange' },
  'Personal Care': { icon: 'spa', tone: 'rose' },
  'Laundry & Cleaning': { icon: 'local_laundry_service', tone: 'teal' },
  'Others': { icon: 'category', tone: 'brand' }
}

const DEFAULT_STYLE = { icon: 'category', tone: 'brand' }

// Shared like useProducts, so the header and the page no longer fetch the same categories twice.
const categories = ref([])
const loading = ref(false)

// No coordinates in this request, so concurrent callers always share.
let inFlight = null

const load = async () => {
  try {
    const { data } = await api.get('/categories')
    const mapped = (data || []).map((category) => ({
      id: category.category_id,
      label: category.category_name,
      icon: (CATEGORY_STYLES[category.category_name] || DEFAULT_STYLE).icon,
      tone: (CATEGORY_STYLES[category.category_name] || DEFAULT_STYLE).tone
    }))

    // Others is a catch-all, so it always sorts last instead of alphabetically.
    categories.value = mapped.sort((a, b) => {
      if (a.label === 'Others') return 1
      if (b.label === 'Others') return -1
      return 0
    })
  } catch (error) {
    console.error('Failed to load categories', error)
    categories.value = []
  }
}

export function useCategories() {
  const fetchCategories = () => {
    if (inFlight) return inFlight

    loading.value = true

    const promise = load().finally(() => {
      if (inFlight === promise) {
        inFlight = null
        loading.value = false
      }
    })
    inFlight = promise

    return promise
  }

  return { categories, loading, fetchCategories }
}
