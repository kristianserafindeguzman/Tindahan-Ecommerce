<template>
  <q-page class="storefront-page">

    <SiteHeader :address="address" />

    <!-- MAIN CONTENT -->
    <div class="page-content">

      <div class="page-header-row">
        <div>
          <h1 class="page-title">All Products</h1>
          <p class="page-subtitle">Browse all products from sari-sari stores near you.</p>
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
            />
          </div>

          <q-btn
            outline
            no-caps
            dense
            icon="tune"
            label="Filters"
            class="filters-toggle-btn"
            @click="filtersOpen = !filtersOpen"
          >
            <span v-if="hasActiveFilters" class="filters-active-dot" />
          </q-btn>
        </div>
      </div>

      <!-- CATEGORY PILLS -->
      <div class="category-pills-row">
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
        <q-chip clickable dense class="category-pill category-pill-more" @click="filtersOpen = true">
          More
          <q-icon name="expand_more" size="16px" />
        </q-chip>
      </div>

      <div class="products-layout">

        <div class="products-main">
          <div class="products-grid" :class="{ 'products-grid-narrow': sidebarVisible }">
            <ProductCard v-for="product in paginatedProducts" :key="product.id" :product="product" />
          </div>

          <p v-if="!filteredProducts.length" class="products-empty">
            No products match your filters.
          </p>
        </div>

        <!-- FILTERS SIDEBAR (desktop) -->
        <aside v-if="filtersOpen && !isMobileFilters" class="filters-panel">
          <ProductFilters
            v-model:category="selectedCategory"
            v-model:store="selectedStore"
            v-model:price-min="priceMin"
            v-model:price-max="priceMax"
            v-model:in-stock="inStockOnly"
            v-model:sort="sortBy"
            :category-options="CATEGORY_SELECT_OPTIONS"
            :store-options="STORE_SELECT_OPTIONS"
            :sort-options="SORT_OPTIONS"
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
            v-model:store="selectedStore"
            v-model:price-min="priceMin"
            v-model:price-max="priceMax"
            v-model:in-stock="inStockOnly"
            v-model:sort="sortBy"
            :category-options="CATEGORY_SELECT_OPTIONS"
            :store-options="STORE_SELECT_OPTIONS"
            :sort-options="SORT_OPTIONS"
            @close="filtersOpen = false"
            @clear="clearFilters"
          />
        </q-card>
      </q-dialog>

    </div>

    <SiteFooter />

  </q-page>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import SiteHeader from '@/components/consumer/SiteHeader.vue'
import SiteFooter from '@/components/consumer/SiteFooter.vue'
import ProductCard from '@/components/consumer/ProductCard.vue'
import ProductFilters from '@/components/consumer/ProductFilters.vue'
import AppPagination from '@/components/consumer/AppPagination.vue'
import { useCategories } from '@/composables/useCategories'
import { MOCK_ALL_PRODUCTS } from '@/data/mockCatalog'

const $q = useQuasar()

// Placeholder until real geolocation/address selection is wired up.
const address = ref('123 Shaw Boulevard, Barangay Pleasant Hills, Mandaluyong City')

const { categories, fetchCategories } = useCategories()

onMounted(fetchCategories)

const VISIBLE_CATEGORIES = computed(() => categories.value.slice(0, 7))

const CATEGORY_SELECT_OPTIONS = computed(() => [
  { label: 'All Categories', value: 'All' },
  ...categories.value.map((category) => ({ label: category.label, value: category.label }))
])

const STORE_SELECT_OPTIONS = [
  { label: 'All Stores', value: 'All' },
  ...[...new Set(MOCK_ALL_PRODUCTS.map((product) => product.store))].map((store) => ({ label: store, value: store }))
]

const SORT_OPTIONS = [
  { label: 'Popular', value: 'popular' },
  { label: 'Price: Low to High', value: 'price_asc' },
  { label: 'Price: High to Low', value: 'price_desc' }
]

const filtersOpen = ref(false)
const selectedCategory = ref('All')
const selectedStore = ref('All')
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

// 6 columns squeeze too narrow once the filters sidebar takes up space, so drop to 5 then.
const sidebarVisible = computed(() => filtersOpen.value && !isMobileFilters.value)

const hasActiveFilters = computed(() =>
  selectedCategory.value !== 'All' ||
  selectedStore.value !== 'All' ||
  inStockOnly.value ||
  priceMin.value != null ||
  priceMax.value != null ||
  sortBy.value !== 'popular'
)

const filteredProducts = computed(() => {
  const list = MOCK_ALL_PRODUCTS.filter((product) => {
    if (selectedCategory.value !== 'All' && product.category !== selectedCategory.value) return false
    if (selectedStore.value !== 'All' && product.store !== selectedStore.value) return false
    if (inStockOnly.value && !product.inStock) return false
    if (priceMin.value != null && product.price < priceMin.value) return false
    if (priceMax.value != null && product.price > priceMax.value) return false
    return true
  })

  if (sortBy.value === 'price_asc') return [...list].sort((a, b) => a.price - b.price)
  if (sortBy.value === 'price_desc') return [...list].sort((a, b) => b.price - a.price)
  return list
})

// Client-side pagination, same convention as ConsumerPersonalize.vue.
const PAGE_SIZE = 10
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
  selectedStore.value = 'All'
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
/* width:100% needed: margin:0 auto on a flex item shrinks it to content width otherwise. */
.page-content {
  flex: 1;
  display: flex;
  flex-direction: column;

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
  width: 150px;
}

.sort-select :deep(.q-field__control) {
  border-radius: 8px;
}

/* Quasar's unstyled focus defaults to brand red, which reads as an error state here — tone it down to neutral grey. */
.sort-select.q-field--focused :deep(.q-field__control:after) {
  border-color: #9a9a9a;
}

.filters-toggle-btn {
  position: relative;

  height: 36px;
  min-height: 36px;
  padding: 0 14px;

  border: 1px solid #e2e2e2;
  border-radius: 8px;

  background: #ffffff;
  color: #333333;

  font-size: 13px;
  font-weight: 500;

  transition: border-color 0.15s, background-color 0.15s;
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
  outline: none;

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

  height: 34px;
  padding: 0 18px;

  border: 1px solid #e2e2e2;
  border-radius: 8px;

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

.category-pill-more {
  display: flex;
  align-items: center;

  gap: 2px;
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

/* 6 columns, matching ConsumerHome.vue; drops to 5 via .products-grid-narrow when the filters sidebar is visible. */
.products-grid {
  display: grid;
  grid-template-columns: repeat(6, 1fr);

  gap: 12px;
}

.products-grid.products-grid-narrow {
  grid-template-columns: repeat(5, 1fr);
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

  border: 1px solid #f0f0f0;
  border-radius: 8px;

  background: #ffffff;

  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.filters-dialog-card {
  width: 100%;
  max-width: 380px;

  border-radius: 8px;
}

/* RESPONSIVE */

@media (max-width: 1024px) {
  .products-grid {
    grid-template-columns: repeat(4, 1fr);
  }
}

@media (max-width: 900px) {
  .products-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

@media (max-width: 600px) {
  .page-content {
    padding: 16px;
  }

  .page-header-row {
    flex-wrap: wrap;
  }

  .page-subtitle {
    display: none;
  }

  .products-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>
