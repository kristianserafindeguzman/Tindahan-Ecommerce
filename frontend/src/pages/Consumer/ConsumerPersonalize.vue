<template>
  <q-page class="storefront-page">

    <SiteHeader />

    <!-- MAIN CONTENT -->
    <div class="page-content">

      <div class="page-header-row">
        <div>
          <h1 class="page-title">{{ pageTitle }}</h1>
          <p class="page-subtitle">{{ pageSubtitle }}</p>
        </div>
      </div>

      <div class="products-grid">
        <ProductCard v-for="product in paginatedProducts" :key="product.id" :product="product" @add-to-cart="handleAddToCart" @view-product="openProductModal" />
      </div>

      <p v-if="!products.length" class="products-empty">
        {{ !isFallback
          ? 'No recommendations yet — check back soon.'
          : 'No popular products to show near you right now.'
        }}
      </p>

      <AppPagination v-model="currentPage" :max="totalPages" />

    </div>

    <SiteFooter />

    <ProductDetailModal v-model="showProductModal" :product="selectedProduct" />

  </q-page>
</template>

<script setup>
import { ref, computed, onMounted, inject } from 'vue'
import { useQuasar } from 'quasar'
import SiteHeader from '@/components/consumer/SiteHeader.vue'
import SiteFooter from '@/components/consumer/SiteFooter.vue'
import ProductCard from '@/components/consumer/ProductCard.vue'
import AppPagination from '@/components/consumer/AppPagination.vue'
import ProductDetailModal from '@/components/consumer/ProductDetailModal.vue'
import { useProducts } from '@/composables/useProducts'
import { useCart } from '@/composables/useCart'

const $q = useQuasar()
const api = inject('api') // Need to inject api for custom fetch
const products = ref([])
const isFallback = ref(false)

const fetchProducts = async () => {
  try {
    const lat = localStorage.getItem('consumer_lat')
    const lng = localStorage.getItem('consumer_lng')
    const params = lat && lng ? { lat, lng } : {}
    
    const response = await api.get('/consumer/personalized-feed', { params })
    products.value = response.data.products
    isFallback.value = response.data.is_fallback
  } catch (error) {
    console.error('Failed to fetch personalized feed:', error)
  }
}
const { addToCart } = useCart()

onMounted(fetchProducts)

const showProductModal = ref(false)
const selectedProduct = ref(null)

const openProductModal = (product) => {
  selectedProduct.value = product
  showProductModal.value = true
}

const handleAddToCart = async (product) => {
  try {
    await addToCart(product.id)
    $q.notify({ type: 'positive', message: `${product.name} added to cart.` })
  } catch (error) {
    $q.notify({ type: 'negative', message: error.response?.data?.message || 'Failed to add to cart.' })
  }
}

const isLoggedIn = computed(() => !!localStorage.getItem('auth_token'))

const pageTitle = computed(() => !isFallback.value ? 'Recommended for You' : 'Popular Products Near You')
const pageSubtitle = computed(() => !isFallback.value
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

/* auto-fill/minmax instead of fixed column counts, so card size shrinks smoothly as the viewport narrows. */
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
