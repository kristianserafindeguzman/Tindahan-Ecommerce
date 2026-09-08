<template>
  <q-page class="storefront-page">

    <SiteHeader />

    <!-- MAIN CONTENT -->
    <div class="page-content">

      <!-- STORE BANNER -->
      <q-skeleton v-if="storesLoading" square class="store-banner skeleton-banner" />

      <div v-else-if="store" class="store-banner">
        <img v-if="store.image" :src="store.image" :alt="store.name" class="store-banner-img" />
        <div v-else class="store-banner-placeholder">
          <q-icon name="o_storefront" size="56px" />
        </div>

        <div class="store-banner-overlay" />

        <div class="store-banner-content">
          <h1 class="store-banner-name">{{ store.name }}</h1>
          <div class="store-banner-meta">
            <span v-if="store.address" class="store-banner-meta-item">
              <q-icon name="o_location_on" size="14px" />
              {{ store.address }} <span v-if="store.distance_meters != null" class="q-ml-xs">({{ formatDistance(store.distance_meters) }})</span>
            </span>
            <span v-if="store.address" class="store-banner-meta-sep">•</span>
            <span class="store-banner-meta-item store-banner-status" :class="{ 'store-banner-status-closed': !store.isOpen }">
              <span class="store-banner-status-dot" :class="{ 'store-banner-status-dot-closed': !store.isOpen }" />
              {{ store.scheduleStatusText || (store.isOpen ? `Open until ${store.closesAt}` : 'Closed now') }}
            </span>
          </div>
        </div>

        <q-btn unelevated no-caps icon="o_directions" label="Directions" class="store-banner-directions" :disable="!hasDirections" @click="getDirections" />
      </div>

      <p v-else class="store-not-found">
        Store not found.
        <span class="store-not-found-link" @click="router.push('/consumer/stores')">Back to Stores</span>
      </p>

      <div class="page-header-row">
        <div>
          <h2 class="page-title">Products</h2>
          <p class="page-subtitle">Browse everything this store has to offer.</p>
        </div>

        <div class="page-header-actions">
          <div class="sort-inline">
            <span class="sort-label">Sort by:</span>
            <q-select
              v-model="sortBy"
              :options="SORT_OPTIONS"
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
            label="Filters"
            class="filters-toggle-btn"
            @click="filtersOpen = !filtersOpen"
          >
            <span v-if="hasActiveFilters" class="filters-active-dot" />
          </q-btn>
        </div>
      </div>

      <!-- CATEGORY PILLS -->
      <div v-if="VISIBLE_CATEGORIES.length" class="category-pills-row">
        <q-chip
          clickable
          dense
          class="category-pill"
          :class="{ 'category-pill-active': selectedCategory === 'All' }"
          @click="selectedCategory = 'All'"
        >
          All
        </q-chip>
        <q-chip
          v-for="category in VISIBLE_CATEGORIES"
          :key="category.id"
          clickable
          dense
          class="category-pill"
          :class="{ 'category-pill-active': selectedCategory === category.label }"
          @click="selectedCategory = category.label"
        >
          {{ category.label }}
        </q-chip>
      </div>

      <div class="products-layout">

        <div class="products-main">
          <div v-if="productsLoading" ref="gridEl" class="products-grid">
            <CardSkeleton v-for="n in skeletonCount" :key="n" />
          </div>
          <div v-else ref="gridEl" class="products-grid">
            <ProductCard v-for="(product, i) in paginatedProducts" :key="product.id" v-intersection.once="onReveal" class="reveal" :style="{ '--reveal-delay': (i % 6) * 70 + 'ms' }" :product="product" @add-to-cart="handleAddToCart" @view-product="openProductModal" />
          </div>

          <p v-if="!productsLoading && !filteredProducts.length" class="products-empty">
            No products match your filters.
          </p>
        </div>

        <!-- FILTERS SIDEBAR (desktop) -->
        <aside v-if="filtersOpen && !isMobileFilters" class="filters-panel">
          <ProductFilters
            v-model:category="selectedCategory"
            v-model:price-min="priceMin"
            v-model:price-max="priceMax"
            v-model:in-stock="inStockOnly"
            v-model:sort="sortBy"
            :category-options="CATEGORY_SELECT_OPTIONS"
            :sort-options="SORT_OPTIONS"
            hide-store
            @close="filtersOpen = false"
            @clear="clearFilters"
          />
        </aside>

      </div>

      <AppPagination v-model="currentPage" :max="totalPages" />

      <!-- FILTERS POPUP (tablet: centered dialog, mobile: bottom sheet) -->
      <q-dialog v-model="mobileFiltersOpen" :position="isSheetFilters ? 'bottom' : undefined">
        <q-card class="filters-dialog-card" :class="{ 'filters-dialog-card-sheet': isSheetFilters }">
          <div v-if="isSheetFilters" class="filters-drag-handle" />
          <ProductFilters
            v-model:category="selectedCategory"
            v-model:price-min="priceMin"
            v-model:price-max="priceMax"
            v-model:in-stock="inStockOnly"
            v-model:sort="sortBy"
            :category-options="CATEGORY_SELECT_OPTIONS"
            :sort-options="SORT_OPTIONS"
            hide-store
            :is-sheet="isSheetFilters"
            @close="filtersOpen = false"
            @clear="clearFilters"
          />
        </q-card>
      </q-dialog>

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
import ProductFilters from '@/components/consumer/ProductFilters.vue'
import AppPagination from '@/components/consumer/AppPagination.vue'
import ProductDetailModal from '@/components/consumer/ProductDetailModal.vue'
import { useCategories } from '@/composables/useCategories'
import { useGridColumns } from '@/composables/useGridColumns'
import { formatDistance } from '@/utils/distance'
import { useProducts } from '@/composables/useProducts'
import { useStores } from '@/composables/useStores'
import { useCart } from '@/composables/useCart'
import { useReveal } from '@/composables/useReveal'

const $q = useQuasar()

const gridEl = ref(null)
const { columns: gridColumns } = useGridColumns(gridEl)

// Two full rows of placeholders. Derived rather than hardcoded so the block never ends
// in a ragged part-row — the grid is auto-fill, so its column count changes continuously
// with width, not at breakpoints.
const SKELETON_ROWS = 2
const skeletonCount = computed(() => gridColumns.value * SKELETON_ROWS)
const { onReveal } = useReveal()
const route = useRoute()
const router = useRouter()
const isLoggedIn = computed(() => !!localStorage.getItem('auth_token'))

const showProductModal = ref(false)
const selectedProduct = ref(null)

const openProductModal = (product) => {
  selectedProduct.value = product
  showProductModal.value = true
}

const { categories, fetchCategories } = useCategories()
const { products, loading: productsLoading, fetchProducts } = useProducts()
const { stores, loading: storesLoading, fetchStores } = useStores()
const { addToCart } = useCart()

const storeParam = computed(() => route.params.id)
const store = computed(() => stores.value.find((s) => s.slug === storeParam.value || String(s.id) === String(storeParam.value)) || null)

// Same origin+destination pattern as ConsumerOrderDetails.vue's "Get Directions" button.
const hasDirections = computed(() => {
  const lat = localStorage.getItem('consumer_lat')
  const lng = localStorage.getItem('consumer_lng')
  return !!(lat != null && lng != null && store.value?.latitude != null && store.value?.longitude != null)
})

const getDirections = () => {
  if (!hasDirections.value) return
  const oLat = localStorage.getItem('consumer_lat')
  const oLng = localStorage.getItem('consumer_lng')
  const { latitude: dLat, longitude: dLng } = store.value

  window.open(`https://www.google.com/maps/dir/?api=1&origin=${oLat},${oLng}&destination=${dLat},${dLng}`, '_blank')
}

const storeProducts = computed(() => {
  if (!store.value) return []
  return products.value.filter((product) => product.storeId === store.value.id)
})

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


onMounted(() => {
  fetchCategories()
  fetchProducts()
  fetchStores()
})

// Only categories this store actually carries, not the full catalog list.
const VISIBLE_CATEGORIES = computed(() => {
  const storeCategoryLabels = new Set(storeProducts.value.map((product) => product.category))
  return categories.value.filter((category) => storeCategoryLabels.has(category.label))
})

const CATEGORY_SELECT_OPTIONS = computed(() => [
  { label: 'All Categories', value: 'All' },
  ...VISIBLE_CATEGORIES.value.map((category) => ({ label: category.label, value: category.label }))
])

const SORT_OPTIONS = [
  { label: 'Popular', value: 'popular' },
  { label: 'Price: Low to High', value: 'price_asc' },
  { label: 'Price: High to Low', value: 'price_desc' }
]

const filtersOpen = ref(false)
const selectedCategory = ref('All')
const priceMin = ref(null)
const priceMax = ref(null)
const inStockOnly = ref(false)
const sortBy = ref('popular')

// Below this width the sidebar doesn't fit, so filtersOpen opens a popup dialog instead.
const isMobileFilters = computed(() => $q.screen.width < 900)

// Tablet and mobile (the whole range below the sidebar breakpoint) both get the bottom sheet.
const isSheetFilters = computed(() => isMobileFilters.value)

const mobileFiltersOpen = computed({
  get: () => filtersOpen.value && isMobileFilters.value,
  set: (val) => { filtersOpen.value = val }
})

const hasActiveFilters = computed(() =>
  selectedCategory.value !== 'All' ||
  inStockOnly.value ||
  priceMin.value != null ||
  priceMax.value != null ||
  sortBy.value !== 'popular'
)

const filteredProducts = computed(() => {
  const list = storeProducts.value.filter((product) => {
    if (selectedCategory.value !== 'All' && product.category !== selectedCategory.value) return false
    if (inStockOnly.value && !product.inStock) return false
    if (priceMin.value != null && product.price < priceMin.value) return false
    if (priceMax.value != null && product.price > priceMax.value) return false
    return true
  })

  if (sortBy.value === 'price_asc') return [...list].sort((a, b) => a.price - b.price)
  if (sortBy.value === 'price_desc') return [...list].sort((a, b) => b.price - a.price)
  return list
})

// Client-side pagination, same convention as ConsumerProducts.vue.
const PAGE_SIZE = 60
const currentPage = ref(1)

const totalPages = computed(() =>
  Math.max(1, Math.ceil(filteredProducts.value.length / PAGE_SIZE))
)

const paginatedProducts = computed(() => {
  const start = (currentPage.value - 1) * PAGE_SIZE
  return filteredProducts.value.slice(start, start + PAGE_SIZE)
})

// Jump back to page 1 when filters change shape, otherwise the user could be stranded on a now-empty page.
watch(filteredProducts, () => { currentPage.value = 1 })

const clearFilters = () => {
  selectedCategory.value = 'All'
  priceMin.value = null
  priceMax.value = null
  inStockOnly.value = false
  sortBy.value = 'popular'
}
</script>

<style scoped>
.storefront-page {
  min-height: 100vh;

  display: flex;
  flex-direction: column;

  background: #ffffff;

  font-family: 'Roboto', Arial, sans-serif;
}

/* flex column + pagination's own margin-top:auto keeps it pinned to the bottom even on a short last page. */
.page-content {
  flex: 1;
  display: flex;
  flex-direction: column;

  /* width:100% needed: margin:0 auto on a flex item shrinks it to content width otherwise. */
  width: 100%;
  max-width: 1200px;
  box-sizing: border-box;

  margin: 0 auto;

  padding: 24px;
}

.page-content :deep(.app-pagination-row) {
  margin-top: auto;
  padding-top: 32px;
}

/* STORE BANNER */

.store-banner {
  position: relative;
  overflow: hidden;

  height: 230px;
  margin-bottom: 24px;

  border-radius: var(--r-2xl);

  background: linear-gradient(145deg, var(--c-surface) 0%, var(--c-surface) 100%);
}

.store-banner-img {
  width: 100%;
  height: 100%;

  object-fit: cover;
  object-position: center;
}

.store-banner-placeholder {
  display: flex;
  align-items: center;
  justify-content: center;

  height: 100%;

  color: var(--c-brand);
}

/* Darkens the bottom of the photo so the white name/meta text stays legible over any image. */
.store-banner-overlay {
  position: absolute;
  inset: 0;

  background: linear-gradient(to top, rgba(0, 0, 0, 0.75) 0%, rgba(0, 0, 0, 0.3) 40%, rgba(0, 0, 0, 0) 68%);
}

.store-banner-content {
  position: absolute;
  z-index: 1;
  left: 20px;
  right: 20px;
  bottom: 20px;
}

.store-banner-name {
  margin: 0 0 8px;

  font-size: var(--fs-3xl);
  font-weight: 700;
  line-height: 1.25;

  color: #ffffff;
  text-shadow: 0 1px 6px rgba(0, 0, 0, 0.35);
}

.store-banner-meta {
  display: flex;
  flex-wrap: wrap;
  align-items: center;

  gap: 6px;

  font-size: var(--fs-xs);
  font-weight: 400;

  color: rgba(255, 255, 255, 0.85);
}

.store-banner-meta-item {
  display: flex;
  align-items: center;

  gap: 5px;
}

.store-banner-meta-sep {
  opacity: 0.6;
}

/* Brighter than StoreCard's var(--c-success)/var(--c-danger) — these read better against the dark photo overlay here. */
.store-banner-status {
  color: #4ade80;
}

.store-banner-status-closed {
  color: #f87171;
}

.store-banner-status-dot {
  width: 6px;
  height: 6px;

  border-radius: 50%;

  background: #4ade80;
}

.store-banner-status-dot-closed {
  background: #f87171;
}

/* Same white-on-photo CTA recipe as .hero-cta (ConsumerHome.vue). */
.store-banner-directions {
  position: absolute;
  z-index: 1;
  top: 14px;
  right: 14px;

  height: 34px;
  padding: 0 14px;

  border-radius: var(--r-sm);

  background: #ffffff;
  color: var(--c-brand);

  font-size: var(--fs-sm);
  font-weight: 700;

  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);

  transition: background-color 0.15s, box-shadow 0.2s, transform 0.2s;
}

.store-banner-directions:hover {
  background: var(--c-hairline);

  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.25);
  transform: translateY(-1px);
}

.store-banner-directions:focus-visible {
  outline: none;
  box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.5);
}

.store-not-found {
  margin: 0 0 24px;

  font-size: var(--fs-md);

  color: var(--c-muted);
}

.store-not-found-link {
  margin-left: 6px;

  color: var(--c-brand);
  font-weight: 600;

  cursor: pointer;
}

.store-not-found-link:hover {
  text-decoration: underline;
}

/* PAGE HEADER */

.page-header-row {
  display: flex;
  align-items: center;
  justify-content: space-between;

  gap: 16px;
  margin-bottom: 20px;
}

.page-title {
  margin: 0 0 4px;

  font-size: var(--fs-3xl);
  font-weight: 700;
  line-height: 1.3;

  color: var(--c-text);
}

.page-subtitle {
  margin: 0;

  font-size: var(--fs-sm);

  color: var(--c-subtle);
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
  width: 170px;
}

.sort-select :deep(.q-field__control) {
  border-radius: var(--r-lg);
}

.sort-select :deep(.q-field__prepend) {
  color: var(--c-muted);
}

/* Same red hover/focus treatment as .filters-toggle-btn, so the two paired controls feel consistent. */
.sort-select:hover :deep(.q-field__control) {
  background: var(--c-brand-tint);
}

.sort-select:hover :deep(.q-field__control):before {
  border-color: var(--c-brand);
}

.sort-select.q-field--focused :deep(.q-field__control:after) {
  border-color: var(--c-brand);
}

.filters-toggle-btn {
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

/* Hides Quasar's own focus/ripple overlay so it can't paint a stray ring over our border/hover treatment. */
.filters-toggle-btn :deep(.q-focus-helper) {
  display: none;
}

.filters-toggle-btn :deep(.q-btn__content) {
  gap: 6px;
}

.filters-toggle-btn:hover {
  border-color: var(--c-brand);
  background: var(--c-brand-tint);
}

/* Swaps the browser's default focus ring for a red glow matching the app's hover/focus treatment. */
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

/* CATEGORY PILLS */

.category-pills-row {
  display: flex;
  align-items: center;

  gap: 8px;
  margin-bottom: 20px;
  padding-bottom: 4px;

  overflow-x: auto;
  scrollbar-width: none;
}

.category-pills-row::-webkit-scrollbar {
  display: none;
}

.category-pill {
  display: flex;
  align-items: center;
  justify-content: center;

  flex-shrink: 0;
  margin: 0;

  height: 36px;
  padding: 0 20px;

  border: 1px solid var(--c-border);
  border-radius: var(--r-sm);

  background: #ffffff;
  color: var(--c-text-2);

  font-size: var(--fs-sm);
  font-weight: 500;

  transition: background-color 0.15s, border-color 0.15s, color 0.15s, box-shadow 0.15s;
}

.category-pill:hover {
  border-color: var(--c-brand-tint-3);
  background: var(--c-brand-tint);
}

.category-pill-active {
  border-color: transparent;
  background: var(--c-brand);
  color: #ffffff;
  font-weight: 600;

  /* !important: q-chip carries its own default elevation shadow otherwise. */
  box-shadow: none !important;
}

.category-pill-active:hover {
  border-color: transparent;
  background: var(--c-brand-hover);
}

/* LAYOUT — MAIN + FILTERS SIDEBAR */

.products-layout {
  display: flex;
  align-items: flex-start;

  gap: 24px;
}

.products-main {
  flex: 1;
  width: 100%;
  min-width: 0;
}

/* auto-fill/minmax instead of fixed column counts, so card size shrinks smoothly as available width narrows. */
.products-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));

  gap: 16px;
}

.products-empty {
  padding: 40px 0;

  color: var(--c-muted);

  font-size: var(--fs-md);
  text-align: center;
}

/* PAGE ENTRANCE — page load only (fresh DOM each navigation), opacity/transform only so it never shifts layout. */
@keyframes storedetail-fade-up {
  from { opacity: 0; transform: translateY(14px); }
  to { opacity: 1; transform: translateY(0); }
}

.store-banner,
.products-grid {
  animation: storedetail-fade-up 0.5s ease both;
}

.products-grid { animation-delay: 0.1s; }

@media (prefers-reduced-motion: reduce) {
  .store-banner,
  .products-grid {
    animation: none;
  }
}

/* QSkeleton draws the shimmer; .store-banner already supplies the box and radius. */
.skeleton-banner {
  border-radius: var(--r-2xl);
}

/* Restored alongside .skeleton-banner, which is the last user of it in this file. */
/* FILTERS SIDEBAR (desktop) / POPUP (mobile) — see ProductFilters.vue for shared inner content styling */

.filters-panel {
  flex-shrink: 0;

  width: 260px;

  border: 1px solid var(--c-border);
  border-radius: var(--r-lg);

  background: #ffffff;

  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

/* Tablet: centered dialog, same as before this page had a bottom sheet at all. */
.filters-dialog-card {
  width: 100%;
  max-width: 380px;

  border-radius: var(--r-lg);
}

/* Mobile only: the same dialog restyled as a bottom sheet. */
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

/* RESPONSIVE */

@media (max-width: 600px) {
  .page-content {
    padding: 16px;
  }

  .store-banner {
    height: 170px;
  }

  .store-banner-name {
    font-size: var(--fs-xl);
  }

  .store-banner-directions {
    height: 30px;
    padding: 0 10px;

    font-size: var(--fs-xs);
  }

  .page-header-row {
    flex-wrap: wrap;
  }

  .page-subtitle {
    display: none;
  }
}
</style>

