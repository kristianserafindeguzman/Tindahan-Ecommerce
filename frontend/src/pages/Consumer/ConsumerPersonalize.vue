<template>
  <q-page class="storefront-page">

    <SiteHeader :address="address" />

    <!-- MAIN CONTENT -->
    <div class="page-content">

      <div class="page-header-row">
        <div>
          <h1 class="page-title">{{ pageTitle }}</h1>
          <p class="page-subtitle">{{ pageSubtitle }}</p>
        </div>
      </div>

      <div class="products-grid">
        <ProductCard v-for="product in paginatedProducts" :key="product.id" :product="product" />
      </div>

      <p v-if="!MOCK_RECOMMENDED_PRODUCTS.length" class="products-empty">
        {{ hasHistory
          ? "No recommendations yet — check back after you've browsed a few products."
          : 'No popular products to show near you right now.'
        }}
      </p>

      <AppPagination v-model="currentPage" :max="totalPages" />

    </div>

    <SiteFooter />

  </q-page>
</template>

<script setup>
import { ref, computed } from 'vue'
import SiteHeader from '@/components/consumer/SiteHeader.vue'
import SiteFooter from '@/components/consumer/SiteFooter.vue'
import ProductCard from '@/components/consumer/ProductCard.vue'
import AppPagination from '@/components/consumer/AppPagination.vue'

// Placeholder until real geolocation/address selection is wired up.
const address = ref('123 Shaw Boulevard, Barangay Pleasant Hills, Mandaluyong City')

// hasHistory checks a signal nothing currently writes, so it resolves to false until a real history source exists.

const hasHistory = computed(() => !!localStorage.getItem('recent_products'))

const pageTitle = computed(() => hasHistory.value ? 'Recommended for You' : 'Popular Products Near You')
const pageSubtitle = computed(() => hasHistory.value
  ? "Products picked based on your activity and preferences."
  : "Popular picks from sari-sari stores near you."
)

// Mock data — replace with a real /recommendations endpoint; pagination below is already data-driven.

const MOCK_RECOMMENDED_PRODUCTS = [
  { id: 1, name: 'Lucky Me Pancit Canton Kalamansi 80g', price: 18, distance: '5 m', store: 'Leslie Store' },
  { id: 2, name: 'Piattos Sour Cream & Onion', price: 15, distance: '5 m', store: 'Leslie Store' },
  { id: 3, name: 'Coca-Cola 1.5L', price: 75, distance: '2 m', store: 'Leslie Store' },
  { id: 4, name: 'Del Monte Tuna 155g', price: 38, distance: '3 m', store: 'Leslie Store' },
  { id: 5, name: 'Gardenia Bread', price: 46, distance: '4 m', store: 'Sol A Sari Sari Store' },
  { id: 6, name: 'Alaska Evaporada 370ml', price: 25, distance: '2 m', store: 'Leslie Store' },
  { id: 7, name: 'Kopiko Blanca Twin Pack', price: 22, distance: '5 m', store: 'Sol A Sari Sari Store' },
  { id: 8, name: 'Sanicare Bath Soap', price: 15, distance: '3 m', store: 'Jmzhai Sari Sari Store' },
  { id: 9, name: 'Jasmine Rice 1kg', price: 52, distance: '2 m', store: 'Leslie Store' },
  { id: 10, name: 'Selecta Ice Cream 1.3L', price: 99, distance: '5 m', store: 'Sol A Sari Sari Store' },
  { id: 11, name: 'Datu Puti Soy Sauce 1L', price: 42, distance: '3 m', store: 'Jmzhai Sari Sari Store' },
  { id: 12, name: 'Century Tuna Flakes in Oil 155g', price: 35, distance: '3 m', store: 'Jmzhai Sari Sari Store' },
  { id: 13, name: 'Safeguard Bar Soap 90g', price: 25, distance: '4 m', store: 'Sol A Sari Sari Store' },
  { id: 14, name: 'Nescafe 3-in-1 Original 20g', price: 9, distance: '4 m', store: 'Sol A Sari Sari Store' },
  { id: 15, name: 'Silver Swan Soy Sauce 385ml', price: 22, distance: '5 m', store: 'Leslie Store' },
  { id: 16, name: 'Kopiko Brown Coffee 3-in-1 25g', price: 10, distance: '5 m', store: 'Leslie Store' }
]

// Client-side pagination, same convention as the other Consumer pages.
const PAGE_SIZE = 8
const currentPage = ref(1)

const totalPages = computed(() =>
  Math.max(1, Math.ceil(MOCK_RECOMMENDED_PRODUCTS.length / PAGE_SIZE))
)

const paginatedProducts = computed(() => {
  const start = (currentPage.value - 1) * PAGE_SIZE
  return MOCK_RECOMMENDED_PRODUCTS.slice(start, start + PAGE_SIZE)
})
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

/* PRODUCTS GRID */

/* 6 columns, matching ConsumerHome.vue/ConsumerProducts.vue; no filters sidebar here, so no narrower fallback needed. */
.products-grid {
  display: grid;
  grid-template-columns: repeat(6, 1fr);

  gap: 12px;
}

.products-empty {
  padding: 40px 0;

  color: #8992a2;

  font-size: 14px;
  text-align: center;
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

  .products-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>
