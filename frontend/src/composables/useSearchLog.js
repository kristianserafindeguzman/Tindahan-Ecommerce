import { api } from '@/boot/axios'
import { getCurrentPosition } from '@/utils/geolocation'
import { useProducts } from './useProducts'
import { useCategories } from './useCategories'

// search_logs.category_id is what the Random Forest personalization trains on: rows without a
// category are dropped from Path A, so resolving the query to the right category is the whole
// value of the log row. Rows still get written when nothing matches, because Path B (localized
// popular searches) groups on the raw query and keeps working without a category.

// Matches only at the start of a word, so "choco" still finds "Chocolate" while "ml" no longer
// matches "250ml" and files a Cooking Essentials search the consumer never made.
const matchesAtWordStart = (haystack, needle) => {
  if (!haystack || !needle) return false
  const text = String(haystack).toLowerCase()
  let from = 0

  for (;;) {
    const at = text.indexOf(needle, from)
    if (at === -1) return false
    // Start of the string, or preceded by something that is not a letter or digit.
    if (at === 0 || !/[a-z0-9]/.test(text[at - 1])) return true
    from = at + 1
  }
}

// The description is a comma/slash separated list of what belongs in the category
// ("Softdrinks & Water, Coffee & Tea, ..."), so it is split into its terms before matching.
const descriptionTerms = description =>
  String(description || '')
    .split(/[,/&()]+/)
    .map(term => term.trim())
    .filter(Boolean)

/**
 * Resolves a search query to a category id, or null when nothing matches.
 * Categories are tried before products so a query naming a whole category is not pulled into
 * whichever product happens to contain the same substring.
 */
export function resolveSearchCategory(query, products = [], categories = []) {
  const needle = String(query || '').trim().toLowerCase()
  if (!needle) return null

  const categoryId = category => category?.id ?? category?.category_id ?? null

  // 1. The category's own name.
  const byName = categories.find(category => matchesAtWordStart(category.label, needle))
  if (byName) return categoryId(byName)

  // 2. The category's description, which is the list of things that belong in it.
  const byDescription = categories.find(category =>
    descriptionTerms(category.description).some(term => matchesAtWordStart(term, needle))
  )
  if (byDescription) return categoryId(byDescription)

  // 3. A product name, using the product's own category id rather than matching its name back.
  const byProductName = products.find(product => matchesAtWordStart(product.name, needle))
  if (byProductName) return resolveProductCategory(byProductName, categories)

  // 4. A product description, last because it is the loosest match.
  const byProductDescription = products.find(product => matchesAtWordStart(product.description, needle))
  if (byProductDescription) return resolveProductCategory(byProductDescription, categories)

  return null
}

// categoryId comes straight from the catalog; the name lookup is only a fallback for callers
// holding a product shape that predates it.
function resolveProductCategory(product, categories) {
  if (product.categoryId != null) return product.categoryId

  const name = String(product.category || '').toLowerCase()
  const match = categories.find(category => String(category.label || '').toLowerCase() === name)
  return match?.id ?? match?.category_id ?? null
}

// Coordinates are a bonus, not a requirement: they let a search feed the localized
// popular-search path, but the search itself is logged either way. A saved address is used when
// there is one, otherwise the browser is asked once; if that is refused or unavailable the
// search is still logged with null coordinates rather than being dropped or given a stand-in
// location. The result is used for this log only — the saved address is left to useAddress.
const searchCoordinates = async () => {
  const saved = {
    lat: Number(localStorage.getItem('consumer_lat')),
    lng: Number(localStorage.getItem('consumer_lng'))
  }
  if (Number.isFinite(saved.lat) && Number.isFinite(saved.lng) && (saved.lat || saved.lng)) return saved

  try {
    const { latitude, longitude } = await getCurrentPosition()
    return { lat: latitude, lng: longitude }
  } catch (error) {
    console.info('Search logged without coordinates: no consumer location available.', error.message)
    return null
  }
}

export function useSearchLog() {
  const { products } = useProducts()
  const { categories } = useCategories()

  /**
   * Saves one submitted search. Call it from the places a consumer actually starts a search,
   * not from route changes, so back/forward navigation does not inflate the history.
   */
  const logSearch = async query => {
    const search = String(query || '').trim()
    // Guest searches are not attributable to a consumer, so they are not logged.
    if (!search || !localStorage.getItem('auth_token')) return

    const coordinates = await searchCoordinates()

    try {
      await api.post('/consumer/search-logs', {
        search_query: search,
        category_id: resolveSearchCategory(search, products.value, categories.value),
        search_lat: coordinates?.lat ?? null,
        search_lng: coordinates?.lng ?? null
      })
    } catch (error) {
      console.error('Failed to save search log:', error.response?.data || error)
    }
  }

  return { logSearch }
}
