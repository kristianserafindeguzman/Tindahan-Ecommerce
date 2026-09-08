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

          <!-- STORES — first on the All tab. A store match is navigational: the name
               was typed to go there, so it should not sit under 200 product cards. -->
          <template v-if="showStoreStrip">
            <!-- No heading, per design: the row survives only to carry the expander. -->
            <div v-if="matchedStores.length > STORE_STRIP_MAX" class="results-section-header results-section-header--bare">
              <button
                v-if="matchedStores.length > STORE_STRIP_MAX"
                type="button"
                class="section-link"
                @click="storesExpanded = !storesExpanded"
              >
                {{ storesExpanded ? 'Show less' : `Show all ${matchedStores.length} stores` }}
              </button>
            </div>

            <div v-if="isSearching" class="stores-grid">
              <CardSkeleton v-for="n in 4" :key="n" variant="store" />
            </div>

            <!-- Rows on the mixed view, cards on the dedicated tab. A row reads as
                 "go here" and a card as "buy this", so the form tells them apart
                 rather than the heading having to. -->
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
                      {{ store.isOpen ? 'Open' : 'Closed' }}
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

            <!-- Infinite scroll rather than pages: a search can return 200+ products and
                 paging through them a screen at a time is the wrong shape for browsing.
                 QInfiniteScroll handles the sentinel and the load guard. -->
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
              That's all {{ filteredProducts.length }} results.
            </p>
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
import CardSkeleton from '@/components/consumer/CardSkeleton.vue'
import SiteFooter from '@/components/consumer/SiteFooter.vue'
import ProductCard from '@/components/consumer/ProductCard.vue'
import StoreCard from '@/components/consumer/StoreCard.vue'
import ProductDetailModal from '@/components/consumer/ProductDetailModal.vue'
import { splitHighlightParts } from '@/utils/textHighlight'
import { formatDistance } from '@/utils/distance'
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

/* --------------------------------------------------------------- STORE RESULTS */

// A store match is navigational — the name was typed to go there — so stores lead the
// page rather than sitting under a product grid that can run to 200 cards. Only the
// first few show; the rest are one click away.
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

// Same breakpoint as the page's own @media (max-width: 600px) rules.
const isMobileScreen = computed(() => $q.screen.width < 600)

// Mobile: only label a section when both are present (to tell them apart) — drop the count too.
// Desktop keeps the full "Products (N)" / "Stores (N)" heading regardless.
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

// A new result set starts from the top again — otherwise narrowing the search would
// keep the previous scroll depth and render more rows than the query now has.
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
/* STORE ROWS — the mixed-view form for a store. Deliberately not a card: a row reads
   as navigation, which is what a store result is. */
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

/* "See all N stores" — a text action in the section header, not a button, so it does
   not compete with the tabs directly above it. */
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

/* With no heading between them, this margin is the only thing separating the store
   block from the products, so it lives on the block itself rather than on an
   adjacent-sibling rule that only matched some of the time. */
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

/* 8px left the heading sitting on the rule. The rule is what separates this from the
   results above, so the heading needs room below it, not to hug it. */
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

  /* Shown on phones too. It was hidden here, which left the results grid with no
     heading and no result count — the one place that tells you what was searched
     and how much came back. The type scale already steps down below 600px, so only
     the bottom margin needs tightening. */
  .page-header-row {
    margin-bottom: 16px;
  }

  .store-rows,
  .stores-grid {
    margin-bottom: 22px;
  }
}
</style>










