<template>
  <q-page class="storefront-page">

    <SiteHeader />

    <!-- MAIN CONTENT -->
    <div class="page-content">

      <div class="page-header-row">
        <div class="page-header-text">
          <h1 class="page-title">{{ query ? t('Search Results') : t('Search') }}</h1>
          <p class="page-subtitle">{{ subtitleText }}</p>
        </div>

        <!-- Same Sort + Filters pair as the Products page; keyed to the unfiltered matches so it stays reachable when the filters empty the page. -->
        <div v-if="query && hasQueryMatches" class="page-header-actions">
          <div class="sort-inline">
            <span class="sort-label">{{ t('Sort by:') }}</span>
            <q-select
              v-model="sortBy"
              :options="translateOptions(sortOptions)"
              dense
              outlined
              emit-value
              map-options
              hide-bottom-space
              behavior="menu"
              class="sort-select"
            >
              <template #prepend>
                <q-icon name="swap_vert" size="16px" />
              </template>
            </q-select>
          </div>

          <q-btn
            unelevated
            no-caps
            dense
            icon="o_tune"
            :label="t('Filters')"
            class="filters-toggle-btn"
            @click="filtersOpen = !filtersOpen"
          >
            <span v-if="hasActiveFilters" class="filters-active-dot" />
          </q-btn>
        </div>
      </div>

      <!-- EMPTY QUERY STATE -->
      <div v-if="!query" class="search-prompt">
        <q-icon name="o_search" size="40px" class="search-prompt-icon" />
        <p class="search-prompt-text">{{ t('Start typing to search products and stores.') }}</p>

        <div v-if="recentSearches.length" class="search-prompt-recent">
          <div class="search-prompt-recent-title">{{ t('Recent Searches') }}</div>
          <div class="search-prompt-recent-chips">
            <q-chip
              v-for="term in recentSearches"
              :key="term"
              clickable
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
      <div class="search-layout">
      <div class="search-main">

        <template v-if="isSearching || hasAnyResults">

          <!-- Store matches come first, because a typed store name means the user wants to go there. -->
          <template v-if="showStoreStrip">
            <!-- No heading, per design: the row survives only to carry the expander. -->
            <div v-if="matchedStores.length > STORE_STRIP_MAX" class="results-section-header results-section-header--bare">
              <button
                v-if="matchedStores.length > STORE_STRIP_MAX"
                type="button"
                class="section-link"
                @click="storesExpanded = !storesExpanded"
              >
                {{ storesExpanded ? t('Show less') : t('Show all {count} stores', { count: matchedStores.length }) }}
              </button>
            </div>

            <div v-if="isSearching" class="stores-grid">
              <CardSkeleton v-for="n in 4" :key="n" variant="store" />
            </div>

            <!-- Stores show as compact rows that read as places to go, switching to full cards once the list is expanded. -->
            <div v-else-if="storesExpanded" class="stores-grid">
              <StoreCard
                v-for="store in matchedStores"
                :key="store.id"
                :store="store"
                :highlight-query="query"
              />
            </div>

            <div v-else class="store-rows">
              <button
                v-for="store in strippedStores"
                :key="store.id"
                type="button"
                class="store-row"
                @click="goToStore(store)"
              >
                <span class="store-row-image">
                  <img v-if="store.image" :src="store.image" :alt="store.name" />
                  <q-icon v-else name="o_storefront" size="20px" />
                </span>

                <span class="store-row-body">
                  <span class="store-row-name">
                    <template v-for="(part, i) in storeNameParts(store)" :key="i">
                      <mark v-if="part.match" class="highlight-mark">{{ part.text }}</mark>
                      <template v-else>{{ part.text }}</template>
                    </template>
                  </span>
                  <span class="store-row-meta">
                    <span class="store-row-status" :class="{ 'store-row-status--closed': !store.isOpen }">
                      <span class="store-row-dot" :class="{ 'store-row-dot--closed': !store.isOpen }" />
                      {{ store.isOpen ? t('Open') : t('Closed') }}
                    </span>
                    <span v-if="storeMetaText(store)" class="store-row-sub">{{ storeMetaText(store) }}</span>
                  </span>
                </span>

                <q-icon name="o_chevron_right" size="20px" class="store-row-chevron" />
              </button>
            </div>
          </template>

          <!-- PRODUCTS -->
          <template v-if="showProductGrid">
            <div v-if="isSearching" class="products-grid">
              <CardSkeleton v-for="n in 6" :key="n" />
            </div>

            <!-- Infinite scroll rather than pages, since a search can return 200+ products, with QInfiniteScroll handling the sentinel. -->
            <q-infinite-scroll v-else :offset="300" :disable="allProductsShown" @load="loadMoreProducts">
              <div class="products-grid">
                <ProductCard
                  v-for="product in visibleProducts"
                  :key="product.id"
                  :product="product"
                  :highlight-query="query"
                  @add-to-cart="handleAddToCart"
                  @view-product="openProductModal"
                />
              </div>

              <template #loading>
                <div class="products-grid infinite-loading">
                  <CardSkeleton v-for="n in 4" :key="`more-${n}`" />
                </div>
              </template>
            </q-infinite-scroll>

            <p v-if="allProductsShown && filteredProducts.length > PAGE_SIZE" class="results-end">
              {{ t('That\'s all {count} results.', { count: filteredProducts.length }) }}
            </p>
          </template>

          <!-- Stores can survive a price filter that hides every product, so the page still has results; say what's missing. -->
          <div v-if="!isSearching && !hasProducts && queryProducts.length" class="results-filtered-note">
            <q-icon name="o_filter_alt_off" size="18px" />
            <span>{{ t('No products match your filters.') }}</span>
            <button type="button" class="section-link" @click="clearFilters">{{ t('Clear filters') }}</button>
          </div>

          <!-- RELATED PRODUCTS (fills out the page when the search itself only turned up 1-2 results) -->
          <div v-if="!isSearching && showRelatedProducts" class="related-section">
            <h2 class="results-section-title">{{ t('You May Also Like') }}</h2>
            <div class="products-grid">
              <ProductCard v-for="product in relatedProducts" :key="`related-${product.id}`" :product="product" @add-to-cart="handleAddToCart" @view-product="openProductModal" />
            </div>
          </div>
        </template>

        <!-- The search matched, but the filters hid every result — a different keyword would not help. -->
        <div v-else-if="hasQueryMatches" class="results-empty">
          <q-icon name="o_filter_alt_off" size="32px" class="results-empty-icon" />
          <p class="results-empty-title">{{ t('No results match your filters.') }}</p>
          <q-btn outline no-caps :label="t('Clear filters')" class="results-empty-btn" @click="clearFilters" />
        </div>

        <!-- EMPTY RESULTS STATE -->
        <div v-else class="results-empty">
          <q-icon name="o_search_off" size="32px" class="results-empty-icon" />
          <p class="results-empty-title">{{ t('No results found for "{query}".', { query }) }}</p>
          <p class="results-empty-text">{{ t('Try searching for a different keyword.') }}</p>
        </div>

      </div>

      <!-- FILTERS SIDEBAR (desktop), same component and treatment as the Products page -->
      <aside v-if="filtersOpen && !isMobileFilters" class="filters-panel">
        <ProductFilters
          v-model:max-distance="maxDistance"
          v-model:price-min="priceMin"
          v-model:price-max="priceMax"
          v-model:sort="sortBy"
          :distance-options="DISTANCE_OPTIONS"
          :distance-disabled="!hasDistanceData"
          :sort-options="sortOptions"
          hide-category
          hide-store
          hide-in-stock
          @close="filtersOpen = false"
          @clear="clearFilters"
        />
      </aside>
      </div>

      <!-- FILTERS SHEET (below the sidebar breakpoint) -->
      <q-dialog v-model="mobileFiltersOpen" position="bottom">
        <q-card class="filters-dialog-card filters-dialog-card-sheet">
          <div class="filters-drag-handle" />
          <ProductFilters
            v-model:max-distance="maxDistance"
            v-model:price-min="priceMin"
            v-model:price-max="priceMax"
            v-model:sort="sortBy"
            :distance-options="DISTANCE_OPTIONS"
            :distance-disabled="!hasDistanceData"
            :sort-options="sortOptions"
            hide-category
            hide-store
            hide-in-stock
            is-sheet
            @close="filtersOpen = false"
            @clear="clearFilters"
          />
        </q-card>
      </q-dialog>

      </template>

    </div>

    <SiteFooter />

    <ProductDetailModal v-model="showProductModal" :product="selectedProduct" />

  </q-page>
</template>

<script setup>
import { useConsumerLanguage } from '@/composables/useConsumerLanguage'

import { ref, computed, watch, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { useRoute, useRouter } from 'vue-router'
import SiteHeader from '@/components/consumer/SiteHeader.vue'
import CardSkeleton from '@/components/consumer/CardSkeleton.vue'
import SiteFooter from '@/components/consumer/SiteFooter.vue'
import ProductCard from '@/components/consumer/ProductCard.vue'
import StoreCard from '@/components/consumer/StoreCard.vue'
import ProductDetailModal from '@/components/consumer/ProductDetailModal.vue'
import ProductFilters from '@/components/consumer/ProductFilters.vue'
import { splitHighlightParts } from '@/utils/textHighlight'
import { formatDistance } from '@/utils/distance'
import { useCategories } from '@/composables/useCategories'
import { useProducts } from '@/composables/useProducts'
import { useStores } from '@/composables/useStores'
import { useCart } from '@/composables/useCart'
import { useSearchLog } from '@/composables/useSearchLog'

const { t, translateOptions } = useConsumerLanguage()

const $q = useQuasar()
const route = useRoute()
const router = useRouter()

const { logSearch } = useSearchLog()
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
    $q.notify({ type: 'positive', message: t('{name} added to cart.', { name: product.name }) })
  } catch (error) {
    $q.notify({ type: 'negative', message: t(error.response?.data?.message || 'Failed to add to cart.') })
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

// A query that exactly names a category, such as Beverages, browses the whole category instead of matching product names.
const matchedCategory = computed(() => {
  const q = query.value.toLowerCase()
  if (!q) return null
  return categories.value.find((category) => category.label.toLowerCase() === q) || null
})

// What the query alone matches, before any filter — kept separate so the page can tell "nothing matched"
// apart from "the filters hid everything".
const queryProducts = computed(() => {
  const q = query.value.toLowerCase()
  const categoryMatch = matchedCategory.value

  return products.value.filter((product) => {
    if (categoryMatch) return product.category === categoryMatch.label
    return q ? product.name.toLowerCase().includes(q) : false
  })
})

const queryStores = computed(() => {
  const q = query.value.toLowerCase()
  if (!q) return []
  return stores.value.filter((store) => store.name.toLowerCase().includes(q))
})

const hasQueryMatches = computed(() => queryProducts.value.length > 0 || queryStores.value.length > 0)

/* --------------------------------------------------------------- FILTERS */

// 'any' rather than null, because QSelect treats a null model as empty and would not show the label.
const ANY_DISTANCE = 'any'

const DISTANCE_OPTIONS = [
  { label: 'Any distance', value: ANY_DISTANCE },
  { label: 'Within 500 m', value: 500 },
  { label: 'Within 1 km', value: 1000 },
  { label: 'Within 3 km', value: 3000 },
  { label: 'Within 5 km', value: 5000 },
  { label: 'Within 10 km', value: 10000 }
]

const filtersOpen = ref(false)
const maxDistance = ref(ANY_DISTANCE)
const priceMin = ref(null)
const priceMax = ref(null)
const sortBy = ref('relevance')

// Distances only exist once the consumer has set a location, so without one the distance controls are disabled.
const hasDistanceData = computed(() =>
  products.value.some((product) => product.distance_meters != null) ||
  stores.value.some((store) => store.distance_meters != null)
)

const sortOptions = computed(() => [
  { label: 'Relevance', value: 'relevance' },
  { label: 'Nearest', value: 'nearest', disable: !hasDistanceData.value },
  { label: 'Price: Low to High', value: 'price_asc' },
  { label: 'Price: High to Low', value: 'price_desc' }
])

// A cleared number field hands back '' rather than null, which would otherwise count as an active filter.
const toPrice = (value) => (value === '' || value == null || !Number.isFinite(Number(value)) ? null : Number(value))

const minPrice = computed(() => toPrice(priceMin.value))
const maxPrice = computed(() => toPrice(priceMax.value))
const distanceLimit = computed(() =>
  hasDistanceData.value && maxDistance.value !== ANY_DISTANCE ? maxDistance.value : null
)

const hasActiveFilters = computed(() =>
  distanceLimit.value != null ||
  minPrice.value != null ||
  maxPrice.value != null ||
  sortBy.value !== 'relevance'
)

// A result with no known distance cannot be shown to be within range, so a distance limit excludes it.
const withinDistance = (item) =>
  distanceLimit.value == null || (item.distance_meters != null && item.distance_meters <= distanceLimit.value)

// Unknown distances sort last rather than first.
const byDistance = (a, b) => (a.distance_meters ?? Infinity) - (b.distance_meters ?? Infinity)

// Below this width the sidebar doesn't fit, matching the Products page breakpoint.
const isMobileFilters = computed(() => $q.screen.width < 900)

const mobileFiltersOpen = computed({
  get: () => filtersOpen.value && isMobileFilters.value,
  set: (val) => { filtersOpen.value = val }
})

const clearFilters = () => {
  maxDistance.value = ANY_DISTANCE
  priceMin.value = null
  priceMax.value = null
  sortBy.value = 'relevance'
}

const filteredProducts = computed(() => {
  const list = queryProducts.value.filter((product) => {
    if (!withinDistance(product)) return false
    if (minPrice.value != null && product.price < minPrice.value) return false
    if (maxPrice.value != null && product.price > maxPrice.value) return false
    return true
  })

  if (sortBy.value === 'nearest') return [...list].sort(byDistance)
  if (sortBy.value === 'price_asc') return [...list].sort((a, b) => a.price - b.price)
  if (sortBy.value === 'price_desc') return [...list].sort((a, b) => b.price - a.price)
  return list
})

// Price has no meaning for a store, so only distance narrows the store rows.
const matchedStores = computed(() => {
  const list = queryStores.value.filter(withinDistance)
  return sortBy.value === 'nearest' ? [...list].sort(byDistance) : list
})

const hasProducts = computed(() => filteredProducts.value.length > 0)
const hasStores = computed(() => matchedStores.value.length > 0)
const hasAnyResults = computed(() => hasProducts.value || hasStores.value)

/* --------------------------------------------------------------- STORE RESULTS */

// Stores lead the page and only the first three show, with the rest one click away.
const STORE_STRIP_MAX = 3

const storesExpanded = ref(false)

// A new query invalidates the expanded state.
watch(query, () => { storesExpanded.value = false })

const showStoreStrip = computed(() => isSearching.value || hasStores.value)
const showProductGrid = computed(() => isSearching.value || hasProducts.value)

const strippedStores = computed(() =>
  storesExpanded.value ? matchedStores.value : matchedStores.value.slice(0, STORE_STRIP_MAX)
)

const storeNameParts = (store) => splitHighlightParts(store.name, query.value)

const storeMetaText = (store) => {
  const parts = []
  if (store.distance_meters != null) parts.push(formatDistance(store.distance_meters))
  if (store.address) parts.push(store.address)
  return parts.join(' · ')
}

const goToStore = (store) => router.push(`/consumer/stores/${store.slug || store.id}`)

// The page subtitle reports the query and its total result count.
const subtitleText = computed(() => {
  if (!query.value) return t('Search for products and stores near you.')
  if (isSearching.value) return t('Searching for "{query}"…', { query: query.value })

  const total = filteredProducts.value.length + matchedStores.value.length
  return t(total === 1 ? 'Showing {count} result for "{query}".' : 'Showing {count} results for "{query}".', { count: total, query: query.value })
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

// Results are already in memory, so "loading more" is just revealing the next slice.
const PAGE_SIZE = 12

const visibleCount = ref(PAGE_SIZE)

const visibleProducts = computed(() => filteredProducts.value.slice(0, visibleCount.value))
const allProductsShown = computed(() => visibleCount.value >= filteredProducts.value.length)

// QInfiniteScroll fires this as its sentinel scrolls into view; done(true) retires it.
const loadMoreProducts = (index, done) => {
  visibleCount.value += PAGE_SIZE
  done(allProductsShown.value)
}

// A new result set starts from the top again, otherwise narrowing the search would keep the previous scroll depth.
watch(filteredProducts, () => { visibleCount.value = PAGE_SIZE })

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

// Logged here as well as in the header, because this chip starts a search the header's submit
// path never sees — the route watcher only syncs the input, it does not record anything.
const goToRecentSearch = (term) => {
  router.push({ path: '/consumer/search', query: { q: term } })
  logSearch(term)
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
  display: flex;
  align-items: center;
  justify-content: space-between;

  gap: 16px;
  margin-bottom: 20px;

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

.page-header-text {
  min-width: 0;
}

.page-header-actions {
  display: flex;
  align-items: center;

  gap: 12px;

  flex-shrink: 0;
}

.sort-inline {
  display: flex;
  align-items: center;

  gap: 8px;
}

.sort-label {
  font-size: var(--fs-sm);
  font-weight: 500;

  color: var(--c-text-2);

  white-space: nowrap;
}

.sort-select {
  width: 150px;
}

.sort-select :deep(.q-field__control) {
  border-radius: var(--r-lg);
}

.sort-select :deep(.q-field__prepend) {
  color: var(--c-muted);
}

.sort-select:hover :deep(.q-field__control) {
  background: var(--c-brand-tint);
}

.sort-select:hover :deep(.q-field__control):before {
  border-color: var(--c-brand);
}

.sort-select.q-field--focused :deep(.q-field__control:after) {
  border-color: var(--c-brand);
}

/* FILTERS — same button, sidebar and sheet as ConsumerProducts.vue, so the two pages read as one system. */

.filters-toggle-btn {
  flex-shrink: 0;
  white-space: nowrap;
  position: relative;

  height: 36px;
  min-height: 36px;
  padding: 0 14px;

  border: 1px solid var(--c-border);
  border-radius: var(--r-sm);
  outline: none !important;

  background: #ffffff;
  color: var(--c-text-2);

  font-size: var(--fs-sm);
  font-weight: 500;

  transition: border-color 0.15s, background-color 0.15s;
}

.filters-toggle-btn :deep(.q-focus-helper) {
  display: none;
}

.filters-toggle-btn :deep(.q-btn__content) {
  flex-wrap: nowrap;
  gap: 6px;
}

.filters-toggle-btn:hover {
  border-color: var(--c-brand);
  background: var(--c-brand-tint);
}

.filters-toggle-btn:focus-visible {
  outline: none !important;

  box-shadow: 0 0 0 3px rgba(189, 36, 39, 0.25);
}

.filters-active-dot {
  position: absolute;
  top: 6px;
  right: 6px;

  width: 7px;
  height: 7px;

  border-radius: 50%;
  border: 1.5px solid #ffffff;

  background: var(--c-brand);
}

.search-layout {
  display: flex;
  align-items: flex-start;

  gap: 24px;
}

.search-main {
  flex: 1;
  width: 100%;
  min-width: 0;
}

.filters-panel {
  flex-shrink: 0;

  width: 260px;

  border: 1px solid var(--c-border);
  border-radius: var(--r-lg);

  background: #ffffff;

  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.filters-dialog-card {
  width: 100%;
  max-width: 380px;

  border-radius: var(--r-lg);
}

.filters-dialog-card-sheet {
  display: flex;
  flex-direction: column;

  max-width: 100%;
  max-height: 88vh;

  border-radius: 16px 16px 0 0;
}

.filters-drag-handle {
  flex-shrink: 0;

  width: 36px;
  height: 4px;
  margin: 10px auto 0;

  border-radius: var(--r-pill);

  background: var(--c-border-strong);
}

.page-title {
  margin: 0 0 4px;

  font-size: var(--fs-3xl);
  font-weight: 700;
  line-height: 1.3;

  color: var(--c-text);

  overflow-wrap: anywhere;
}

.page-subtitle {
  margin: 0;

  font-size: var(--fs-sm);

  color: var(--c-subtle);
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
  color: var(--c-border);

  margin-bottom: 8px;
}

.search-prompt-text {
  margin: 0;

  font-size: var(--fs-md);

  color: var(--c-muted);
}

.search-prompt-recent {
  margin-top: 16px;

  width: 100%;
  max-width: 480px;
}

.search-prompt-recent-title {
  margin-bottom: 8px;

  font-size: var(--fs-xs);
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;

  color: var(--c-muted);
}

.search-prompt-recent-chips {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;

  gap: 8px;
}

.recent-chip {
  border: 1px solid var(--c-border);
  border-radius: var(--r-pill);

  background: #ffffff;
  color: var(--c-text-2);

  font-size: var(--fs-sm);

  transition: background-color 0.15s, border-color 0.15s;
}

.recent-chip:hover {
  border-color: var(--c-brand-tint-3);
  background: var(--c-brand-tint);
}

/* Shared by both Products and Stores headers — same gap above, same gap below, no per-section overrides. */
/* Store rows are deliberately not cards, since a store result is navigation. */
.store-rows {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.store-row {
  display: flex;
  align-items: center;
  gap: 14px;

  width: 100%;
  padding: 12px 16px;

  border: 1px solid var(--c-border);
  border-radius: var(--r-lg);

  background: #ffffff;

  font-family: inherit;
  text-align: left;

  cursor: pointer;

  transition: border-color 0.15s, box-shadow 0.2s, transform 0.2s;
}

.store-row:hover {
  border-color: var(--c-brand-tint-3);
  box-shadow: var(--sh-card-hover);
  transform: translateY(-1px);
}

.store-row:focus-visible {
  outline: 2px solid var(--c-brand);
  outline-offset: 2px;
}

.store-row-image {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;

  width: 46px;
  height: 46px;
  overflow: hidden;

  border-radius: var(--r-md);

  background: var(--c-surface);
  color: var(--c-brand);
}

.store-row-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.store-row-body {
  display: flex;
  flex-direction: column;
  gap: 3px;

  min-width: 0;
  flex: 1;
}

.store-row-name {
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;

  font-size: var(--fs-md);
  font-weight: 600;

  color: var(--c-text);
}

.store-row-meta {
  display: flex;
  align-items: center;
  gap: 10px;

  min-width: 0;
}

.store-row-status {
  display: flex;
  align-items: center;
  flex-shrink: 0;
  gap: 5px;

  font-size: var(--fs-xs);
  font-weight: 600;

  color: var(--c-success);
}

.store-row-status--closed {
  color: var(--c-muted);
}

.store-row-dot {
  width: 6px;
  height: 6px;

  border-radius: var(--r-pill);
  background: var(--c-success);
}

.store-row-dot--closed {
  background: var(--c-muted);
}

.store-row-sub {
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;

  font-size: var(--fs-xs);
  color: var(--c-muted);
}

.store-row-chevron {
  flex-shrink: 0;
  color: var(--c-border-strong);
}

/* The show-all toggle is styled as plain text so it stays quieter than the results around it. */
.section-link {
  padding: 0;

  border: none;
  background: none;

  font-family: inherit;
  font-size: var(--fs-xs);
  font-weight: 600;

  color: var(--c-brand);

  cursor: pointer;
}

.section-link:hover {
  text-decoration: underline;
}

.results-section-header {
  display: flex;
  align-items: baseline;
  justify-content: space-between;

  margin-top: 26px;
  margin-bottom: 12px;
}

/* Titles are gone, so this row holds only the expander — push it back to the right. */
.results-section-header--bare {
  margin-top: 0;
  margin-bottom: 8px;
}

.results-section-header--bare .section-link {
  margin-left: auto;
}

/* With no heading between them, this margin alone separates the store block from the products. */
.store-rows,
.stores-grid {
  margin-bottom: 28px;
}

.results-section-title {
  margin: 0;

  font-size: var(--fs-xl);
  font-weight: 700;
  line-height: 1.3;

  color: var(--c-text);
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

/* Room between the rule and the heading, since the rule is what separates this section from the results above. */
.related-section {
  margin-top: 32px;
  padding-top: 22px;

  border-top: 1px solid var(--c-border);
}

/* Same heading-to-grid gap as .results-section-header, so both sections read alike. */
.related-section .results-section-title {
  margin-bottom: 12px;
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

  color: var(--c-border);
}

.results-empty-title {
  margin: 0;

  font-size: var(--fs-lg);
  font-weight: 600;

  color: var(--c-text-2);
}

.results-empty-text {
  margin: 4px 0 0;

  font-size: var(--fs-sm);

  color: var(--c-muted);
}

.results-filtered-note {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  justify-content: center;

  gap: 8px;
  padding: 20px 16px;

  border: 1px dashed var(--c-border);
  border-radius: var(--r-lg);

  font-size: var(--fs-sm);

  color: var(--c-muted);
}

/* Grey outlined secondary button, the app's standard for a non-primary action. */
.results-empty-btn {
  margin-top: 14px;

  border-radius: var(--r-sm);

  color: var(--c-text-2);

  font-size: var(--fs-sm);
  font-weight: 500;
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

  /* The page header stays visible on phones as the only place showing the query and result count, with a tighter bottom margin. */
  .page-header-row {
    flex-wrap: wrap;
    margin-bottom: 16px;
  }

  /* Same as Products: the sort box may shrink so the label, box and Filters stay on one line. */
  .page-header-actions {
    flex-shrink: 1;
    min-width: 0;
  }

  .sort-inline {
    min-width: 0;
  }

  .sort-select {
    flex: 0 1 auto;
    min-width: 0;
  }

  .store-rows,
  .stores-grid {
    margin-bottom: 22px;
  }
}
</style>
