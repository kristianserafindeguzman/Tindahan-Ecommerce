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
          />
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
          <div class="products-grid">
            <ProductCard v-for="product in filteredProducts" :key="product.id" :product="product" />
          </div>

          <p v-if="!filteredProducts.length" class="products-empty">
            No products match your filters.
          </p>
        </div>

        <!-- FILTERS SIDEBAR -->
        <aside v-if="filtersOpen" class="filters-panel">
          <div class="filters-panel-header">
            <span class="filters-panel-title">Filters</span>
            <button type="button" class="filters-close-btn" aria-label="Close filters" @click="filtersOpen = false">
              <q-icon name="close" size="18px" />
            </button>
          </div>

          <div class="filter-group">
            <label class="filter-label">Categories</label>
            <q-select
              v-model="selectedCategory"
              :options="CATEGORY_SELECT_OPTIONS"
              dense
              outlined
              emit-value
              map-options
              hide-bottom-space
              behavior="menu"
            />
          </div>

          <div class="filter-group">
            <label class="filter-label">Price Range</label>
            <div class="price-range-row">
              <q-input v-model.number="priceMin" type="number" dense outlined hide-bottom-space placeholder="Min" />
              <span class="price-range-sep">–</span>
              <q-input v-model.number="priceMax" type="number" dense outlined hide-bottom-space placeholder="Max" />
            </div>
          </div>

          <div class="filter-group">
            <label class="filter-label">Store</label>
            <q-select
              v-model="selectedStore"
              :options="STORE_SELECT_OPTIONS"
              dense
              outlined
              emit-value
              map-options
              hide-bottom-space
              behavior="menu"
            />
          </div>

          <div class="filter-group">
            <q-checkbox v-model="inStockOnly" label="In Stock Only" dense />
          </div>

          <div class="filter-group">
            <label class="filter-label">Sort by</label>
            <q-select
              v-model="sortBy"
              :options="SORT_OPTIONS"
              dense
              outlined
              emit-value
              map-options
              hide-bottom-space
              behavior="menu"
            />
          </div>

          <q-btn
            label="Apply Filters"
            unelevated
            no-caps
            class="apply-filters-btn"
            @click="filtersOpen = false"
          />

          <div class="clear-filters-row">
            <span class="clear-filters-link" @click="clearFilters">Clear</span>
          </div>
        </aside>

      </div>

    </div>

  </q-page>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import SiteHeader from '@/components/consumer/SiteHeader.vue'
import ProductCard from '@/components/consumer/ProductCard.vue'
import { useCategories } from '@/composables/useCategories'

// ==========================================================
// ADDRESS — placeholder until real geolocation/address selection
// is wired up. Matches the address shown in the reference screenshots.
// ==========================================================

const address = ref('123 Shaw Boulevard, Barangay Pleasant Hills, Mandaluyong City')

// ==========================================================
// CATEGORIES — fetched from /categories (backed by the real
// `categories` table). See useCategories.js for the icon mapping.
// ==========================================================

const { categories, fetchCategories } = useCategories()

onMounted(fetchCategories)

const VISIBLE_CATEGORIES = computed(() => categories.value.slice(0, 7))

const CATEGORY_SELECT_OPTIONS = computed(() => [
  { label: 'All Categories', value: 'All' },
  ...categories.value.map((category) => ({ label: category.label, value: category.label }))
])

// ==========================================================
// MOCK DATA — replace with a real /products endpoint once it exists.
// Category names below match the real `categories` table
// (see backend/database/seeders/CategorySeeder.php).
// ==========================================================

const MOCK_ALL_PRODUCTS = [
  { id: 1, name: 'Coca-Cola 1.5L', category: 'Beverages', price: 75, distance: '2 m', store: 'Leslie Store', inStock: true },
  { id: 2, name: 'Lucky Me Pancit Canton Kalamansi 80g', category: 'Cooking Essentials', price: 12, distance: '3 m', store: 'Jmzhai Sari Sari Store', inStock: true },
  { id: 3, name: 'Del Monte Tuna 155g', category: 'Cooking Essentials', price: 38, distance: '3 m', store: 'Leslie Store', inStock: true },
  { id: 4, name: 'Gardenia Bread', category: 'Snacks & Sweets', price: 46, distance: '4 m', store: 'Sol A Sari Sari Store', inStock: true },
  { id: 5, name: 'Surf Powder Detergent', category: 'Laundry & Cleaning', price: 65, distance: '4 m', store: 'Jmzhai Sari Sari Store', inStock: false },
  { id: 6, name: 'Alaska Evaporada 370ml', category: 'Cooking Essentials', price: 25, distance: '2 m', store: 'Leslie Store', inStock: true },
  { id: 7, name: 'Kopiko Blanca Twin Pack', category: 'Beverages', price: 22, distance: '5 m', store: 'Sol A Sari Sari Store', inStock: true },
  { id: 8, name: 'Sanicare Bath Soap', category: 'Personal Care', price: 15, distance: '3 m', store: 'Jmzhai Sari Sari Store', inStock: true },
  { id: 9, name: 'Jasmine Rice 1kg', category: 'Cooking Essentials', price: 52, distance: '2 m', store: 'Leslie Store', inStock: true },
  { id: 10, name: 'Selecta Ice Cream 1.3L', category: 'Snacks & Sweets', price: 99, distance: '5 m', store: 'Sol A Sari Sari Store', inStock: true },
  { id: 11, name: 'Piattos Sour Cream & Onion', category: 'Snacks & Sweets', price: 15, distance: '4 m', store: 'Jmzhai Sari Sari Store', inStock: true },
  { id: 12, name: 'Century Tuna Flakes in Oil 155g', category: 'Cooking Essentials', price: 35, distance: '3 m', store: 'Leslie Store', inStock: true }
]

const STORE_SELECT_OPTIONS = [
  { label: 'All Stores', value: 'All' },
  ...[...new Set(MOCK_ALL_PRODUCTS.map((product) => product.store))].map((store) => ({ label: store, value: store }))
]

const SORT_OPTIONS = [
  { label: 'Popular', value: 'popular' },
  { label: 'Price: Low to High', value: 'price_asc' },
  { label: 'Price: High to Low', value: 'price_desc' }
]

// ==========================================================
// FILTER / SORT STATE
// ==========================================================

const filtersOpen = ref(false)
const selectedCategory = ref('All')
const selectedStore = ref('All')
const priceMin = ref(null)
const priceMax = ref(null)
const inStockOnly = ref(false)
const sortBy = ref('popular')

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

  background: #ffffff;

  font-family: 'Roboto', Arial, sans-serif;
}

.page-content {
  max-width: 1200px;

  margin: 0 auto;

  padding: 24px;
}

/* =========================
   PAGE HEADER
========================= */

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

.filters-toggle-btn {
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

/* =========================
   CATEGORY PILLS
========================= */

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
  padding: 0 16px;

  border: 1px solid #e2e2e2;
  border-radius: 8px;

  background: #ffffff;
  color: #333333;

  font-size: 13px;
  font-weight: 500;

  transition: background-color 0.15s, border-color 0.15s, color 0.15s;
}

.category-pill:hover {
  border-color: #f3c6c7;
  background: #fdecec;
}

.category-pill-active {
  border-color: #bd2427;
  background: #bd2427;
  color: #ffffff;
}

.category-pill-active:hover {
  border-color: #bd2427;
  background: #a91e21;
}

.category-pill-more {
  display: flex;
  align-items: center;

  gap: 2px;
}

/* =========================
   LAYOUT — MAIN + FILTERS SIDEBAR
========================= */

.products-layout {
  display: flex;
  align-items: flex-start;

  gap: 24px;
}

.products-main {
  flex: 1;
  min-width: 0;
}

.products-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);

  gap: 16px;
}

.products-empty {
  padding: 40px 0;

  color: #8992a2;

  font-size: 14px;
  text-align: center;
}

/* =========================
   FILTERS SIDEBAR
========================= */

.filters-panel {
  flex-shrink: 0;

  width: 260px;
  padding: 18px;

  border: 1px solid #f0f0f0;
  border-radius: 8px;

  background: #ffffff;

  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.filters-panel-header {
  display: flex;
  align-items: center;
  justify-content: space-between;

  margin-bottom: 16px;
}

.filters-panel-title {
  font-size: 16px;
  font-weight: 700;

  color: #111111;
}

.filters-close-btn {
  display: flex;
  align-items: center;
  justify-content: center;

  width: 28px;
  height: 28px;

  border: none;
  border-radius: 50%;

  background: transparent;
  color: #767676;

  cursor: pointer;

  transition: background-color 0.15s;
}

.filters-close-btn:hover {
  background: #f4f4f4;
}

.filter-group {
  margin-bottom: 16px;
}

.filter-label {
  display: block;

  margin-bottom: 6px;

  font-size: 12.5px;
  font-weight: 600;

  color: #4a4a4a;
}

.price-range-row {
  display: flex;
  align-items: center;

  gap: 8px;
}

.price-range-sep {
  color: #9ca3af;
}

.apply-filters-btn {
  width: 100%;
  margin-top: 4px;

  border-radius: 8px;

  background: #bd2427;
  color: #ffffff;

  font-size: 14px;
  font-weight: 600;
}

.clear-filters-row {
  margin-top: 10px;

  text-align: center;
}

.clear-filters-link {
  font-size: 13px;
  font-weight: 500;

  color: #bd2427;

  cursor: pointer;
}

/* =========================
   RESPONSIVE
========================= */

@media (max-width: 1024px) {
  .products-grid {
    grid-template-columns: repeat(4, 1fr);
  }
}

@media (max-width: 900px) {
  .products-layout {
    flex-direction: column;
  }

  .filters-panel {
    width: 100%;
  }

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
