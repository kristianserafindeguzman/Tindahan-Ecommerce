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

export function useCategories() {
  const categories = ref([])
  const loading = ref(false)

  const fetchCategories = async () => {
    loading.value = true
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
    } finally {
      loading.value = false
    }
  }

  return { categories, loading, fetchCategories }
}
