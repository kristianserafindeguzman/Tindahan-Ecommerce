<template>
  <q-page class="storefront-page">

    <SiteHeader />

    <!-- MAIN CONTENT -->
    <div class="page-content">

      <!-- STORE BANNER -->
      <div v-if="store" class="store-banner">
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
              {{ store.isOpen ? `Open until ${store.closesAt}` : 'Closed now' }}
            </span>
          </div>
        </div>

        <!-- Not wired up yet — no directions/maps feature exists yet, intentionally not clickable. -->
        <q-btn unelevated no-caps icon="o_directions" label="Directions" class="store-banner-directions" />
      </div>

      <p v-else-if="!storesLoading" class="store-not-found">
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
          <div class="products-grid">
            <ProductCard v-for="product in paginatedProducts" :key="product.id" :product="product" @add-to-cart="handleAddToCart" @view-product="openProductModal" />
          </div>

          <p v-if="!filteredProducts.length" class="products-empty">
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

      <!-- FILTERS POPUP (mobile) -->
      <q-dialog v-model="mobileFiltersOpen">
        <q-card class="filters-dialog-card">
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
import SiteFooter from '@/components/consumer/SiteFooter.vue'
import ProductCard from '@/components/consumer/ProductCard.vue'
import ProductFilters from '@/components/consumer/ProductFilters.vue'
import AppPagination from '@/components/consumer/AppPagination.vue'
import ProductDetailModal from '@/components/consumer/ProductDetailModal.vue'
import { useCategories } from '@/composables/useCategories'
import { useProducts } from '@/composables/useProducts'
import { useStores } from '@/composables/useStores'
import { useCart } from '@/composables/useCart'

const $q = useQuasar()
const route = useRoute()
const router = useRouter()

const showProductModal = ref(false)
const selectedProduct = ref(null)

const formatDistance = (meters) => {
  if (meters == null) return ''
  if (meters < 1000) return `${Math.round(meters)} m away`
  return `${(meters / 1000).toFixed(1)} km away`
}

const openProductModal = (product) => {
  selectedProduct.value = product
  showProductModal.value = true
}

const { categories, fetchCategories } = useCategories()
const { products, fetchProducts } = useProducts()
const { stores, loading: storesLoading, fetchStores } = useStores()
const { addToCart } = useCart()

const storeId = computed(() => Number(route.params.id))
const store = computed(() => stores.value.find((s) => s.id === storeId.value) || null)

const storeProducts = computed(() => products.value.filter((product) => product.storeId === storeId.value))

const handleAddToCart = async (product) => {
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

  border-radius: 14px;

  background: linear-gradient(145deg, #f7f7f8 0%, #ececee 100%);
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

  color: #bd2427;
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

  font-size: 23px;
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

  font-size: 12px;
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

/* Brighter than StoreCard's #16a34a/#b91c1c — these read better against the dark photo overlay here. */
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

/* Same white-on-photo CTA recipe as .hero-cta (ConsumerHome.vue). No @click — no directions/maps feature yet. */
.store-banner-directions {
  position: absolute;
  z-index: 1;
  top: 14px;
  right: 14px;

  height: 34px;
  padding: 0 14px;

  border-radius: 6px;

  background: #ffffff;
  color: #bd2427;

  font-size: 12.5px;
  font-weight: 700;

  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);

  transition: background-color 0.15s, box-shadow 0.2s, transform 0.2s;
}

.store-banner-directions:hover {
  background: #f4f4f4;

  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.25);
  transform: translateY(-1px);
}

.store-banner-directions:focus-visible {
  outline: none;
  box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.5);
}

.store-not-found {
  margin: 0 0 24px;

  font-size: 14px;

  color: #8992a2;
}

.store-not-found-link {
  margin-left: 6px;

  color: #bd2427;
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

  font-size: 22px;
  font-weight: 700;
  line-height: 1.3;

  color: #111111;
}

.page-subtitle {
  margin: 0;

  font-size: 13px;

  color: #767676;
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
  font-size: 13px;
  font-weight: 500;

  color: #4a4a4a;

  white-space: nowrap;
}

.sort-select {
  width: 170px;
}

.sort-select :deep(.q-field__control) {
  border-radius: 10px;
}

.sort-select :deep(.q-field__prepend) {
  color: #9ca3af;
}

/* Same red hover/focus treatment as .filters-toggle-btn, so the two paired controls feel consistent. */
.sort-select:hover :deep(.q-field__control) {
  background: #fdecec;
}

.sort-select:hover :deep(.q-field__control):before {
  border-color: #bd2427;
}

.sort-select.q-field--focused :deep(.q-field__control:after) {
  border-color: #bd2427;
}

.filters-toggle-btn {
  position: relative;

  height: 36px;
  min-height: 36px;
  padding: 0 14px;

  border: 1px solid #e2e2e2;
  border-radius: 6px;
  outline: none !important;

  background: #ffffff;
  color: #333333;

  font-size: 13px;
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
  border-color: #bd2427;
  background: #fdecec;
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

  background: #bd2427;
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

  border: 1px solid #e2e2e2;
  border-radius: 12px;

  background: #ffffff;
  color: #333333;

  font-size: 13px;
  font-weight: 500;

  transition: background-color 0.15s, border-color 0.15s, color 0.15s, box-shadow 0.15s;
}

.category-pill:hover {
  border-color: #f3c6c7;
  background: #fdecec;
}

.category-pill-active {
  border-color: transparent;
  background: #bd2427;
  color: #ffffff;
  font-weight: 600;

  /* !important: q-chip carries its own default elevation shadow otherwise. */
  box-shadow: none !important;
}

.category-pill-active:hover {
  border-color: transparent;
  background: #a91e21;
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

  color: #8992a2;

  font-size: 14px;
  text-align: center;
}

/* FILTERS SIDEBAR (desktop) / POPUP (mobile) — see ProductFilters.vue for shared inner content styling */

.filters-panel {
  flex-shrink: 0;

  width: 260px;

  border: 1px solid #e8e8e8;
  border-radius: 10px;

  background: #ffffff;

  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.filters-dialog-card {
  width: 100%;
  max-width: 380px;

  border-radius: 10px;
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
    font-size: 17px;
  }

  .store-banner-directions {
    height: 30px;
    padding: 0 10px;

    font-size: 12px;
  }

  .page-header-row {
    flex-wrap: wrap;
  }

  .page-subtitle {
    display: none;
  }
}
</style>
