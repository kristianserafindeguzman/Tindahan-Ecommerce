<template>
  <q-page class="storefront-page">

    <SiteHeader />

    <!-- MAIN CONTENT -->
    <div class="home-content">

      <!-- HERO BANNER -->
      <div class="hero-banner">
        <div class="hero-content">
          <h1 class="hero-title hero-title-lg">Explore sari-sari stores around you</h1>
          <q-btn unelevated no-caps label="Show Map" class="hero-cta" @click="showMapDialog = true">
            <q-icon name="o_arrow_forward" size="16px" class="q-ml-xs" />
          </q-btn>
        </div>
        <div class="hero-logo-wrap">
          <img
            src="@/assets/tindahan-logo.png"
            alt="Tindahan Sari-Sari Store App"
            class="hero-logo"
          />
        </div>
      </div>

      <!-- CATEGORIES -->
      <SectionBlock title="Categories">
        <div v-if="categoriesLoading" class="categories-skeleton-row">
          <div v-for="n in 12" :key="n" class="category-skeleton-tile">
            <q-skeleton type="circle" class="category-skeleton-icon" />
            <q-skeleton type="text" class="category-skeleton-label" />
          </div>
        </div>
        <CategoryCarousel v-else :categories="categories" @select="goToCategory" />
      </SectionBlock>

      <!-- RECOMMENDED / POPULAR PRODUCTS -->
      <SectionBlock :title="resultsSectionTitle" view-all @view-all="router.push(resultsViewAllPath)">
        <div v-if="isLoggedIn ? loadingPersonalized : productsLoading" class="products-grid">
          <CardSkeleton v-for="n in 6" :key="n" />
        </div>
        <div v-else ref="productsGridEl" class="products-grid">
          <ProductCard v-for="product in recommendedProducts" :key="product.id" :product="product" @add-to-cart="handleAddToCart" @view-product="openProductModal" />
        </div>
      </SectionBlock>

      <!-- STORES NEAR YOU -->
      <SectionBlock title="Stores near You" view-all @view-all="router.push('/consumer/stores')">
        <div v-if="storesLoading" class="stores-row">
          <CardSkeleton v-for="n in 4" :key="n" variant="store" />
        </div>
        <div v-else class="stores-row">
          <StoreCard v-for="store in nearbyStores" :key="store.id" :store="store" />
        </div>
      </SectionBlock>

      <!-- DISCOVER PRODUCTS -->
      <SectionBlock title="Discover Products" view-all @view-all="router.push('/consumer/products')">
        <div v-if="productsLoading" class="products-grid">
          <CardSkeleton v-for="n in 6" :key="n" />
        </div>
        <div v-else class="products-grid">
          <ProductCard v-for="product in visibleDiscoverProducts" :key="product.id" :product="product" @add-to-cart="handleAddToCart" @view-product="openProductModal" />
        </div>

        <q-btn
          v-if="visibleDiscoverProducts.length < discoverProducts.length"
          flat
          no-caps
          label="See More"
          class="see-more-btn"
          @click="discoverRowsShown += DISCOVER_ROWS_PER_PAGE"
        />
      </SectionBlock>

    </div>

    <SiteFooter />

    <ProductDetailModal v-model="showProductModal" :product="selectedProduct" />
    <ConsumerMap v-model="showMapDialog" />

  </q-page>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { api } from '@/boot/axios'
import SiteHeader from '@/components/consumer/SiteHeader.vue'
import CardSkeleton from '@/components/consumer/CardSkeleton.vue'
import SiteFooter from '@/components/consumer/SiteFooter.vue'
import SectionBlock from '@/components/consumer/SectionBlock.vue'
import CategoryCarousel from '@/components/consumer/CategoryCarousel.vue'
import ProductCard from '@/components/consumer/ProductCard.vue'
import StoreCard from '@/components/consumer/StoreCard.vue'
import ProductDetailModal from '@/components/consumer/ProductDetailModal.vue'
import ConsumerMap from '@/pages/Consumer/ConsumerMap.vue'
import { useCategories } from '@/composables/useCategories'
import { useProducts } from '@/composables/useProducts'
import { useStores } from '@/composables/useStores'
import { useCart } from '@/composables/useCart'
import { useGridColumns } from '@/composables/useGridColumns'

const router = useRouter()
const $q = useQuasar()
const { addToCart } = useCart()

const showProductModal = ref(false)
const selectedProduct = ref(null)
const showMapDialog = ref(false)

const openProductModal = (product) => {
  selectedProduct.value = product
  showProductModal.value = true
}

// This page renders for guests and logged-in consumers alike, so SiteHeader reads localStorage directly instead of route-guarding.
const isLoggedIn = computed(() => !!localStorage.getItem('auth_token'))

const { categories, loading: categoriesLoading, fetchCategories } = useCategories()
const { products, loading: productsLoading, fetchProducts } = useProducts()
const { stores, loading: storesLoading, fetchStores } = useStores()

const personalizedFeed = ref([])
const loadingPersonalized = ref(false)

const fetchPersonalizedFeed = async () => {
  if (!isLoggedIn.value) return
  loadingPersonalized.value = true
  try {
    const lat = localStorage.getItem('consumer_lat')
    const lng = localStorage.getItem('consumer_lng')
    const params = lat && lng ? { lat, lng } : {}
    
    const response = await api.get('/consumer/personalized-feed', { params })
    personalizedFeed.value = response.data.products || []
  } catch (error) {
    console.error('Failed to fetch personalized feed', error)
  } finally {
    loadingPersonalized.value = false
  }
}

onMounted(() => {
  fetchCategories()
  fetchProducts()
  fetchStores()
  if (isLoggedIn.value) {
    fetchPersonalizedFeed()
  }
})

const goToCategory = (category) => {
  router.push({ path: '/consumer/products', query: { category: category.label } })
}

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

const resultsSectionTitle = computed(() =>
  isLoggedIn.value ? 'Recommended for You' : 'Popular Products Near You'
)

// Personalization is a logged-in-only route, so guests get routed to the guest-browsable catalog instead.
const resultsViewAllPath = computed(() => isLoggedIn.value ? '/consumer/personalize' : '/consumer/products')

// No real recommendation/nearby endpoint yet — these are simple slices of the same fetched
// catalog until personalization/geolocation exist.
const NEARBY_STORES_COUNT = 4
const nearbyStores = computed(() => stores.value.slice(0, NEARBY_STORES_COUNT))

// Both product sections show whole rows only, so they need the live column count — the
// grid is auto-fill, so it changes continuously with width rather than at breakpoints.
const productsGridEl = ref(null)
const { columns: gridColumns } = useGridColumns(productsGridEl, 3)

const RECOMMENDED_ROWS = 2
const recommendedCount = computed(() => gridColumns.value * RECOMMENDED_ROWS)
const recommendedProducts = computed(() => {
  if (isLoggedIn.value) {
    return personalizedFeed.value.slice(0, recommendedCount.value)
  }
  return products.value.slice(0, recommendedCount.value)
})

const discoverProducts = computed(() => {
  if (isLoggedIn.value) {
    return products.value
  }
  return products.value.slice(recommendedCount.value)
})

// "See More" reveals additional full rows in place rather than navigating away — that's what "View All" is for.
const DISCOVER_ROWS_PER_PAGE = 2
const discoverRowsShown = ref(DISCOVER_ROWS_PER_PAGE)

const visibleDiscoverProducts = computed(() =>
  discoverProducts.value.slice(0, discoverRowsShown.value * gridColumns.value)
)
</script>

<style scoped>
/* flex column so SiteFooter's margin-top:auto pins it to the viewport bottom on short pages. */
.storefront-page {
  min-height: 100vh;

  display: flex;
  flex-direction: column;

  background: #ffffff;

  font-family: 'Roboto', Arial, sans-serif;
}

/* HOME CONTENT */

/* width:100% needed: margin:0 auto on a flex item shrinks it to content width otherwise. */
.home-content {
  width: 100%;
  max-width: 1200px;
  box-sizing: border-box;

  margin: 0 auto;

  padding: 24px;
}

/* HERO BANNER */

.hero-banner {
  position: relative;
  overflow: hidden;

  display: flex;
  align-items: center;
  justify-content: space-between;

  gap: 24px;
  padding: 40px;
  margin-bottom: 24px;

  border-radius: var(--r-2xl);
  box-shadow: 0 4px 16px rgba(101, 16, 18, 0.2);

  /* Dot-grid texture over the gradient, instead of the old flat gradient + two decorative circles — subtle enough not to fight the white text. */
  background:
    radial-gradient(circle, rgba(255, 255, 255, 0.1) 1.5px, transparent 1.5px) 0 0 / 26px 26px,
    linear-gradient(
      145deg,
      var(--c-brand) 0%,
      var(--c-brand-deep) 55%,
      var(--c-brand-active) 100%
    );

  animation: home-fade-up 0.5s ease both;
}

/* Hero entrance, page load only. The sections below it are handled by scroll reveal
   (v-intersection in SectionBlock.vue) rather than a mount animation, so this keyframe
   now has exactly one user. */
@keyframes home-fade-up {
  from { opacity: 0; transform: translateY(14px); }
  to { opacity: 1; transform: translateY(0); }
}


@media (prefers-reduced-motion: reduce) {
  .hero-banner {
    animation: none;
  }
}

.hero-content {
  position: relative;
  z-index: 1;

  max-width: 640px;
}

.hero-title {
  margin: 0 0 8px;

  font-size: var(--fs-4xl);
  font-weight: 700;
  line-height: 1.3;

  color: #ffffff;
}

/* Bigger now that the subtitle is gone — the only line of copy left in the hero.
   Display font (Poppins, loaded in index.html) — everything else on the page stays Roboto. */
.hero-title-lg {
  margin: 0 0 20px;

  font-family: 'Poppins', 'Roboto', Arial, sans-serif;
  font-size: var(--fs-hero);
  font-weight: 800;
  line-height: 1.2;
  letter-spacing: -0.01em;
}

.hero-cta {
  height: 42px;
  padding: 0 24px;

  border-radius: var(--r-sm);

  background: #ffffff;
  color: var(--c-brand);

  font-size: var(--fs-md);
  font-weight: 700;
  letter-spacing: 0.01em;

  /* Neutral (not brand-red) shadow — a red-tinted shadow would disappear against this red banner. */
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);

  transition: background-color 0.15s, box-shadow 0.2s, transform 0.2s;
}

.hero-cta:hover {
  background: var(--c-hairline);

  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.25);
  transform: translateY(-1px);
}

.hero-cta :deep(.q-icon) {
  transition: transform 0.2s ease;
}

.hero-cta:hover :deep(.q-icon) {
  transform: translateX(3px);
}

.hero-cta:active {
  background: var(--c-surface);

  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.22);
  transform: translateY(0);
}

.hero-cta:focus-visible {
  outline: none;
  box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.5);
}

.hero-logo-wrap {
  position: relative;
  z-index: 1;
  flex-shrink: 0;

  display: flex;
  align-items: center;
  justify-content: center;

  width: 220px;
  max-width: 40%;
}

.hero-logo {
  width: 100%;
  height: auto;

  object-fit: contain;
}

/* PRODUCTS GRID */

/* auto-fill/minmax instead of fixed column counts, so card size shrinks smoothly as the viewport narrows. */
.products-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));

  gap: 16px;
}

.see-more-btn {
  width: 100%;
  margin-top: 16px;
  padding: 12px;

  border: 1px solid var(--c-border);
  border-radius: var(--r-sm);

  background: #ffffff;
  color: var(--c-brand);

  font-size: var(--fs-md);
  font-weight: 600;
  font-family: inherit;

  cursor: pointer;

  transition: background-color 0.15s, border-color 0.15s, transform 0.15s;
}

.see-more-btn:hover {
  border-color: var(--c-brand-tint-3);
  background: var(--c-brand-tint);
  transform: translateY(-1px);
}

/* STORES ROW */

/* Fixed 4 columns on desktop; auto-fill/minmax only kicks in below the tablet breakpoint (see RESPONSIVE). */
.stores-row {
  display: grid;
  grid-template-columns: repeat(4, 1fr);

  gap: 16px;
}

.categories-skeleton-row {
  display: flex;

  gap: 12px;
  overflow: hidden;
}

.category-skeleton-tile {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;

  gap: 10px;

  width: 132px;
  padding: 20px 12px;

  border-radius: var(--r-lg);
  border: 1px solid var(--c-border);

  background: #ffffff;
}

/* QSkeleton draws the shimmer; only the geometry of the tile it stands in for
   belongs here. */
.category-skeleton-icon {
  width: 44px;
  height: 44px;
}

/* Mirrors .category-tile-label: 13px / 1.3, two reserved lines. */
.category-skeleton-label {
  width: 74%;
  height: calc(var(--fs-sm) * 1.3 * 2);

  border-radius: var(--r-xs);
}

/* RESPONSIVE */

@media (max-width: 1024px) {
  .stores-row {
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  }
}

@media (max-width: 600px) {
  .home-content {
    padding: 16px;
  }

  .hero-banner {
    flex-direction: column-reverse;

    padding: 28px;
    text-align: center;
  }

  .hero-title-lg {
    font-size: var(--fs-4xl);
  }

  .hero-content {
    max-width: none;
  }

  .hero-logo-wrap {
    width: 160px;
    max-width: 60%;
  }
}
</style>

