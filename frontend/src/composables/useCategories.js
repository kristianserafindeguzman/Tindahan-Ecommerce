import { ref } from 'vue'
import { api } from '@/boot/axios'

// The categories table has no icon column, so icons are matched here by
// category_name. Anything not in this list (e.g. a category added later
// through the admin/vendor UI) falls back to a generic icon.
const CATEGORY_ICONS = {
  'Cooking Essentials': 'o_kitchen',
  'Beverages': 'o_local_drink',
  'Snacks & Sweets': 'o_fastfood',
  'Personal Care': 'o_spa',
  'Laundry & Cleaning': 'o_local_laundry_service',
  'Others': 'o_category'
}

const DEFAULT_ICON = 'o_category'

// Shared for the same reason as useProducts: the header refetched this on every
// consumer page alongside the page's own identical call. See useProducts.js.
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
      icon: CATEGORY_ICONS[category.category_name] || DEFAULT_ICON
    }))

    // "Others" is a catch-all and reads oddly sorted alphabetically
    // among real categories — always show it last.
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

