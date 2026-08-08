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

      <p v-if="!products.length" class="products-empty">
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
import { ref, computed, onMounted } from 'vue'
import SiteHeader from '@/components/consumer/SiteHeader.vue'
import SiteFooter from '@/components/consumer/SiteFooter.vue'
import ProductCard from '@/components/consumer/ProductCard.vue'
import AppPagination from '@/components/consumer/AppPagination.vue'
import { useProducts } from '@/composables/useProducts'

// Placeholder until real geolocation/address selection is wired up.
const address = ref('123 Shaw Boulevard, Barangay Pleasant Hills, Mandaluyong City')

const { products, fetchProducts } = useProducts()

onMounted(fetchProducts)

// hasHistory checks a signal nothing currently writes, so it resolves to false until a real history source exists.

const hasHistory = computed(() => !!localStorage.getItem('recent_products'))

const pageTitle = computed(() => hasHistory.value ? 'Recommended for You' : 'Popular Products Near You')
const pageSubtitle = computed(() => hasHistory.value
  ? "Products picked based on your activity and preferences."
  : "Popular picks from sari-sari stores near you."
)

// No real /recommendations endpoint yet — same full catalog as ConsumerHome.vue's "Discover" section,
// just paginated here instead of "See More"-revealed.
const PAGE_SIZE = 60
const currentPage = ref(1)

const totalPages = computed(() =>
  Math.max(1, Math.ceil(products.value.length / PAGE_SIZE))
)

const paginatedProducts = computed(() => {
  const start = (currentPage.value - 1) * PAGE_SIZE
  return products.value.slice(start, start + PAGE_SIZE)
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

/* auto-fill/minmax instead of fixed column counts — card size shrinks smoothly as the viewport
   narrows, rather than jumping at fixed breakpoints. */
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

/* RESPONSIVE */

@media (max-width: 600px) {
  .page-content {
    padding: 16px;
  }
}
</style>
