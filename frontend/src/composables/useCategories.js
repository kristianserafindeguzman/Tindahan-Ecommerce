import { ref } from 'vue'
import { api } from '@/boot/axios'

// The categories table has no icon column, so icons are matched here by
// category_name. Anything not in this list (e.g. a category added later
// through the admin/vendor UI) falls back to a generic icon.
// Filled icons, not the o_ outlined variants: at 22px in a 44px disc the outlines read
// as thin and washed out. Each category also carries a tone, so the row is six distinct
// colours rather than six identical red discs.
//
// Only base names whose outlined form was already in use, so every glyph is known to
// exist in the bundled Material Icons set.
const CATEGORY_STYLES = {
  'Cooking Essentials': { icon: 'restaurant', tone: 'amber' },
  'Beverages': { icon: 'local_drink', tone: 'blue' },
  'Snacks & Sweets': { icon: 'fastfood', tone: 'orange' },
  'Personal Care': { icon: 'spa', tone: 'rose' },
  'Laundry & Cleaning': { icon: 'local_laundry_service', tone: 'teal' },
  'Others': { icon: 'category', tone: 'brand' }
}

const DEFAULT_STYLE = { icon: 'category', tone: 'brand' }

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
      icon: (CATEGORY_STYLES[category.category_name] || DEFAULT_STYLE).icon,
      tone: (CATEGORY_STYLES[category.category_name] || DEFAULT_STYLE).tone
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


