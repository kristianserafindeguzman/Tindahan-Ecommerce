<template>
  <q-page class="storefront-page">

    <SiteHeader />

    <!-- MAIN CONTENT -->
    <div class="page-content">

      <div class="page-header-row">
        <h1 class="page-title">{{ query ? 'Search Results' : 'Search' }}</h1>
        <p class="page-subtitle">{{ subtitleText }}</p>
      </div>

      <!-- EMPTY QUERY STATE -->
      <div v-if="!query" class="search-prompt">
        <q-icon name="o_search" size="40px" class="search-prompt-icon" />
        <p class="search-prompt-text">Start typing to search products and stores.</p>

        <div v-if="recentSearches.length" class="search-prompt-recent">
          <div class="search-prompt-recent-title">Recent Searches</div>
          <div class="search-prompt-recent-chips">
            <q-chip
              v-for="term in recentSearches"
              :key="term"
              clickablev
              dense
              class="recent-chip"
              @click="goToRecentSearch(term)"
            >
              {{ term }}
            </q-chip>
          </div>
        </div>
      </div>

      <template v-else>

        <template v-if="isSearching || hasAnyResults">

          <!-- PRODUCTS -->
          <template v-if="isSearching || hasProducts">
            <div v-if="productsSectionLabel" class="results-section-header">
              <h2 class="results-section-title">{{ productsSectionLabel }}</h2>
            </div>

            <div v-if="isSearching" class="products-grid">
              <div v-for="n in 6" :key="n" class="skeleton-card">
                <div class="skeleton-image" />
                <div class="skeleton-body">
                  <div class="skeleton-line skeleton-line-short" />
                  <div class="skeleton-line" />
                  <div class="skeleton-line skeleton-line-short" />
                </div>
              </div>
            </div>

            <div v-else class="products-grid">
              <ProductCard
                v-for="product in paginatedProducts"
                :key="product.id"
                :product="product"
                :highlight-query="query"
                @add-to-cart="handleAddToCart"
                @view-product="openProductModal"
              />
            </div>

            <AppPagination v-if="!isSearching" v-model="currentPage" :max="totalPages" />
          </template>

          <!-- STORES -->
          <template v-if="isSearching || hasStores">
            <div v-if="storesSectionLabel" class="results-section-header">
              <h2 class="results-section-title">{{ storesSectionLabel }}</h2>
            </div>

            <div v-if="isSearching" class="stores-grid">
              <div v-for="n in 4" :key="n" class="skeleton-card">
                <div class="skeleton-image" />
                <div class="skeleton-body">
                  <div class="skeleton-line skeleton-line-short" />
                  <div class="skeleton-line" />
                  <div class="skeleton-line skeleton-line-short" />
                </div>
              </div>
            </div>

            <div v-else class="stores-grid">
              <StoreCard
                v-for="store in matchedStores"
                :key="store.id"
                :store="store"
                :highlight-query="query"
              />
            </div>
          </template>

          <!-- RELATED PRODUCTS (fills out the page when the search itself only turned up 1-2 results) -->
          <div v-if="!isSearching && showRelatedProducts" class="related-section">
            <h2 class="results-section-title">You May Also Like</h2>
            <div class="products-grid">
              <ProductCard v-for="product in relatedProducts" :key="`related-${product.id}`" :product="product" @add-to-cart="handleAddToCart" @view-product="openProductModal" />
            </div>
          </div>
        </template>

        <!-- EMPTY RESULTS STATE -->
        <div v-else class="results-empty">
          <q-icon name="o_search_off" size="32px" class="results-empty-icon" />
          <p class="results-empty-title">No results found for "{{ query }}".</p>
          <p class="results-empty-text">Try searching for a different keyword.</p>
        </div>

      </template>

    </div>

    <SiteFooter />

    <ProductDetailModal v-model="showProductModal" :product="selectedProduct" />

  </q-page>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { useRoute, useRouter } from 'vue-router'
import SiteHeader from '@/components/consumer/SiteHeader.vue'
import SiteFooter from '@/components/consumer/SiteFooter.vue'
import ProductCard from '@/components/consumer/ProductCard.vue'
import StoreCard from '@/components/consumer/StoreCard.vue'
import AppPagination from '@/components/consumer/AppPagination.vue'
import ProductDetailModal from '@/components/consumer/ProductDetailModal.vue'
import { useCategories } from '@/composables/useCategories'
import { useProducts } from '@/composables/useProducts'
import { useStores } from '@/composables/useStores'
import { useCart } from '@/composables/useCart'

const $q = useQuasar()
const route = useRoute()
const router = useRouter()
const isLoggedIn = computed(() => !!localStorage.getItem('auth_token'))

const query = computed(() => (route.query.q || '').toString().trim())

const { categories, fetchCategories } = useCategories()
const { products, fetchProducts } = useProducts()
const { stores, fetchStores } = useStores()
const { addToCart } = useCart()

const handleAddToCart = async (product) => {
  if (!isLoggedIn.value) {
    router.push('/login')
    return
  }

  try {
    await addToCart(product.id)
    $q.notify({ type: 'positive', message: `${product.name} added to cart.` })
  } catch (error) {
    $q.notify({ type: 'negative', message: error.response?.data?.message || 'Failed to add to cart.' })
  }
}

const showProductModal = ref(false)
const selectedProduct = ref(null)

const openProductModal = (product) => {
  selectedProduct.value = product
  showProductModal.value = true
}

onMounted(() => {
  fetchCategories()
  fetchProducts()
  fetchStores()
})

// A query that exactly names a category (e.g. "Beverages") browses that whole category instead of
// name-matching, since no product literally has the category word in its own name.
const matchedCategory = computed(() => {
  const q = query.value.toLowerCase()
  if (!q) return null
  return categories.value.find((category) => category.label.toLowerCase() === q) || null
})

// This is a universal products+stores search, not the dedicated Products page, so there's no
// Sort/Filters here — just the query itself (plus the category-name shortcut above).
const filteredProducts = computed(() => {
  const q = query.value.toLowerCase()
  const categoryMatch = matchedCategory.value

  return products.value.filter((product) => {
    if (categoryMatch) return product.category === categoryMatch.label
    return q ? product.name.toLowerCase().includes(q) : false
  })
})

const matchedStores = computed(() => {
  const q = query.value.toLowerCase()
  if (!q) return []
  return stores.value.filter((store) => store.name.toLowerCase().includes(q))
})

const hasProducts = computed(() => filteredProducts.value.length > 0)
const hasStores = computed(() => matchedStores.value.length > 0)
const hasAnyResults = computed(() => hasProducts.value || hasStores.value)

// Same breakpoint as the page's own @media (max-width: 600px) rules.
const isMobileScreen = computed(() => $q.screen.width < 600)

// Mobile: only label a section when both are present (to tell them apart) — drop the count too.
// Desktop keeps the full "Products (N)" / "Stores (N)" heading regardless.
const productsSectionLabel = computed(() => {
  if (!isMobileScreen.value) return `Products (${filteredProducts.value.length})`
  return hasProducts.value && hasStores.value ? 'Products' : ''
})

const storesSectionLabel = computed(() => {
  if (!isMobileScreen.value) return `Stores (${matchedStores.value.length})`
  return hasProducts.value && hasStores.value ? 'Stores' : ''
})

const subtitleText = computed(() => {
  if (!query.value) return 'Search for products and stores near you.'
  if (isSearching.value) return `Searching for "${query.value}"…`

  const total = filteredProducts.value.length + matchedStores.value.length
  return `Showing ${total} result${total === 1 ? '' : 's'} for "${query.value}".`
})

// Shows for any non-empty search — zero results gets its own empty state instead.
const showRelatedProducts = computed(() => {
  const total = filteredProducts.value.length + matchedStores.value.length
  return total >= 1
})

const relatedProducts = computed(() => {
  if (!showRelatedProducts.value) return []
  const shownIds = new Set(filteredProducts.value.map((p) => p.id))
  const preferredCategory = filteredProducts.value[0]?.category
  const pool = products.value.filter((p) => !shownIds.has(p.id))
  const sameCategory = preferredCategory ? pool.filter((p) => p.category === preferredCategory) : []
  const sameCategoryIds = new Set(sameCategory.map((p) => p.id))
  const rest = pool.filter((p) => !sameCategoryIds.has(p.id))
  return [...sameCategory, ...rest].slice(0, 6)
})

// Client-side pagination, same convention as ConsumerProducts.vue.
const PAGE_SIZE = 10
const currentPage = ref(1)

const totalPages = computed(() =>
  Math.max(1, Math.ceil(filteredProducts.value.length / PAGE_SIZE))
)

const paginatedProducts = computed(() => {
  const start = (currentPage.value - 1) * PAGE_SIZE
  return filteredProducts.value.slice(start, start + PAGE_SIZE)
})

// Jump back to page 1 when the search term changes, otherwise the user could be stranded on a now-empty page.
watch(filteredProducts, () => { currentPage.value = 1 })

// Brief simulated delay whenever the search term changes, so the UI has a visible "searching" state to show.
const isSearching = ref(false)
let loadingTimer = null

watch(query, () => {
  clearTimeout(loadingTimer)
  if (!query.value) {
    isSearching.value = false
    return
  }
  isSearching.value = true
  loadingTimer = setTimeout(() => { isSearching.value = false }, 350)
}, { immediate: true })

const RECENT_SEARCHES_KEY = 'recent_searches'

const recentSearches = computed(() => {
  try {
    const raw = JSON.parse(localStorage.getItem(RECENT_SEARCHES_KEY) || '[]')
    return Array.isArray(raw) ? raw : []
  } catch {
    return []
  }
})

const goToRecentSearch = (term) => {
  router.push({ path: '/consumer/search', query: { q: term } })
}
</script>

<style scoped>
/* Sticky footer — .page-content grows via flex:1 so the footer never rides up on a short results page. */
.storefront-page {
  min-height: 100vh;

  display: flex;
  flex-direction: column;

  background: #ffffff;

  font-family: 'Roboto', Arial, sans-serif;
}

.page-content {
  flex: 1;
  display: flex;
  flex-direction: column;

  width: 100%;
  max-width: 1200px;
  box-sizing: border-box;

  margin: 0 auto;

  padding: 24px 24px 32px;
}

/* Same major-section gap as every other section boundary on this page. */
.page-content :deep(.app-pagination-row) {
  margin-top: 8px;
}

/* PAGE HEADER */

.page-header-row {
  margin-bottom: 0;

  /* Page load only — results below re-render on every keystroke, so only the header (which doesn't) gets the entrance animation, to avoid it retriggering while typing. */
  animation: search-fade-up 0.5s ease both;
}

@keyframes search-fade-up {
  from { opacity: 0; transform: translateY(14px); }
  to { opacity: 1; transform: translateY(0); }
}

@media (prefers-reduced-motion: reduce) {
  .page-header-row {
    animation: none;
  }
}

.page-title {
  margin: 0 0 4px;

  font-size: 22px;
  font-weight: 700;
  line-height: 1.3;

  color: #111111;

  overflow-wrap: anywhere;
}

.page-subtitle {
  margin: 0;

  font-size: 13px;

  color: #767676;
}

/* EMPTY QUERY STATE */

.search-prompt {
  display: flex;
  flex-direction: column;
  align-items: center;

  padding: 32px 24px;

  text-align: center;
}

.search-prompt-icon {
  color: #d8dce3;

  margin-bottom: 8px;
}

.search-prompt-text {
  margin: 0;

  font-size: 14px;

  color: #8992a2;
}

.search-prompt-recent {
  margin-top: 16px;

  width: 100%;
  max-width: 480px;
}

.search-prompt-recent-title {
  margin-bottom: 8px;

  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;

  color: #9ca3af;
}

.search-prompt-recent-chips {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;

  gap: 8px;
}

.recent-chip {
  border: 1px solid #e2e2e2;
  border-radius: 999px;

  background: #ffffff;
  color: #333333;

  font-size: 12.5px;

  transition: background-color 0.15s, border-color 0.15s;
}

.recent-chip:hover {
  border-color: #f3c6c7;
  background: #fdecec;
}

/* Shared by both Products and Stores headers — same gap above, same gap below, no per-section overrides. */
.results-section-header {
  display: flex;
  align-items: baseline;
  justify-content: space-between;

  margin-top: 8px;
  margin-bottom: 8px;
}

.results-section-title {
  margin: 0;

  font-size: 15px;
  font-weight: 700;

  color: #111111;
}

/* auto-fill/minmax instead of fixed column counts, so card size shrinks smoothly as the viewport narrows. */
.products-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));

  gap: 16px;
}

/* Same card gap as .products-grid; fixed 4 columns on desktop, auto-fill/minmax below the tablet breakpoint. */
.stores-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);

  gap: 16px;
}

.related-section {
  margin-top: 32px;
  padding-top: 8px;

  border-top: 1px solid #e8e8e8;
}

.related-section .results-section-title {
  margin-bottom: 8px;
}

.results-empty {
  display: flex;
  flex-direction: column;
  align-items: center;

  padding: 32px 24px;

  text-align: center;
}

.results-empty-icon {
  margin-bottom: 8px;

  color: #d8dce3;
}

.results-empty-title {
  margin: 0;

  font-size: 15px;
  font-weight: 600;

  color: #333333;
}

.results-empty-text {
  margin: 4px 0 0;

  font-size: 13px;

  color: #8992a2;
}

/* SKELETON LOADING STATE */

.skeleton-card {
  overflow: hidden;

  border-radius: 10px;
  border: 1px solid #e8e8e8;

  background: #ffffff;
}

.skeleton-image,
.skeleton-line {
  background: linear-gradient(90deg, #e0e0e0 25%, #e8e8e8 37%, #e0e0e0 63%);
  background-size: 400% 100%;

  animation: skeleton-pulse 1.4s ease infinite;
}

.skeleton-image {
  height: 110px;
}

.skeleton-body {
  display: flex;
  flex-direction: column;

  gap: 8px;
  padding: 12px;
}

.skeleton-line {
  height: 10px;

  border-radius: 4px;
}

.skeleton-line-short {
  width: 60%;
}

@keyframes skeleton-pulse {
  0% { background-position: 100% 50%; }
  100% { background-position: 0 50%; }
}

/* RESPONSIVE */

@media (max-width: 1024px) {
  .stores-grid {
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  }
}

@media (max-width: 600px) {
  .page-content {
    padding: 16px;
  }

  .page-header-row {
    display: none;
  }
}
</style>
