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
        <CategoryCarousel :categories="categories" @select="goToCategory" />
      </SectionBlock>

      <!-- RECOMMENDED / POPULAR PRODUCTS -->
      <SectionBlock :title="resultsSectionTitle" view-all @view-all="router.push('/consumer/personalize')">
        <div class="products-grid">
          <ProductCard v-for="product in recommendedProducts" :key="product.id" :product="product" @add-to-cart="handleAddToCart" @view-product="openProductModal" />
        </div>
      </SectionBlock>

      <!-- STORES NEAR YOU -->
      <SectionBlock title="Stores near You" view-all @view-all="router.push('/consumer/stores')">
        <div class="stores-row">
          <StoreCard v-for="store in nearbyStores" :key="store.id" :store="store" />
        </div>
      </SectionBlock>

      <!-- DISCOVER PRODUCTS -->
      <SectionBlock title="Discover Products" view-all @view-all="router.push('/consumer/products')">
        <div class="products-grid">
          <ProductCard v-for="product in visibleDiscoverProducts" :key="product.id" :product="product" @add-to-cart="handleAddToCart" @view-product="openProductModal" />
        </div>

        <q-btn
          v-if="visibleDiscoverProducts.length < discoverProducts.length"
          flat
          no-caps
          label="See More"
          class="see-more-btn"
          @click="discoverVisibleCount += DISCOVER_PAGE_SIZE"
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
import SiteHeader from '@/components/consumer/SiteHeader.vue'
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

const { categories, fetchCategories } = useCategories()
const { products, fetchProducts } = useProducts()
const { stores, fetchStores } = useStores()

onMounted(() => {
  fetchCategories()
  fetchProducts()
  fetchStores()
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

// No real recommendation/nearby endpoint yet — these are simple slices of the same fetched
// catalog until personalization/geolocation exist.
const RECOMMENDED_COUNT = 6
const NEARBY_STORES_COUNT = 4

const recommendedProducts = computed(() => products.value.slice(0, RECOMMENDED_COUNT))
const nearbyStores = computed(() => stores.value.slice(0, NEARBY_STORES_COUNT))
const discoverProducts = computed(() => products.value.slice(RECOMMENDED_COUNT))

// "See More" reveals additional products in place rather than navigating away — that's what "View All" is for.

const DISCOVER_PAGE_SIZE = 6
const discoverVisibleCount = ref(DISCOVER_PAGE_SIZE)

const visibleDiscoverProducts = computed(() =>
  discoverProducts.value.slice(0, discoverVisibleCount.value)
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
  padding: 32px;
  margin-bottom: 24px;

  border-radius: 14px;
  box-shadow: 0 4px 16px rgba(101, 16, 18, 0.2);

  background:
    linear-gradient(
      145deg,
      #c02226 0%,
      #9c171b 55%,
      #651012 100%
    );
}

/* Soft decorative circles for depth — purely visual, sit behind the content/logo. */
.hero-banner:before,
.hero-banner:after {
  content: '';
  position: absolute;

  border-radius: 50%;
  background: rgba(255, 255, 255, 0.07);
}

.hero-banner:before {
  top: -70px;
  right: 120px;

  width: 220px;
  height: 220px;
}

.hero-banner:after {
  bottom: -110px;
  right: -50px;

  width: 280px;
  height: 280px;

  background: rgba(255, 255, 255, 0.045);
}

.hero-content {
  position: relative;
  z-index: 1;

  max-width: 640px;
}

.hero-title {
  margin: 0 0 8px;

  font-size: 24px;
  font-weight: 700;
  line-height: 1.3;

  color: #ffffff;
}

/* Bigger now that the subtitle is gone — the only line of copy left in the hero. */
.hero-title-lg {
  margin: 0 0 24px;

  font-size: 32px;
}

.hero-cta {
  height: 40px;
  padding: 0 22px;

  border-radius: 6px;

  background: #ffffff;
  color: #bd2427;

  font-size: 13.5px;
  font-weight: 700;

  /* Neutral (not brand-red) shadow — a red-tinted shadow would disappear against this red banner. */
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);

  transition: background-color 0.15s, box-shadow 0.2s, transform 0.2s;
}

.hero-cta:hover {
  background: #f4f4f4;

  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.25);
  transform: translateY(-1px);
}

.hero-cta:active {
  background: #ececec;

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

  border: 1px solid #e2e2e2;
  border-radius: 6px;

  background: #ffffff;
  color: #bd2427;

  font-size: 14px;
  font-weight: 600;
  font-family: inherit;

  cursor: pointer;

  transition: background-color 0.15s, border-color 0.15s;
}

.see-more-btn:hover {
  border-color: #f3c6c7;
  background: #fdecec;
}

/* STORES ROW */

/* Fixed 4 columns on desktop; auto-fill/minmax only kicks in below the tablet breakpoint (see RESPONSIVE). */
.stores-row {
  display: grid;
  grid-template-columns: repeat(4, 1fr);

  gap: 16px;
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

    padding: 24px;
    text-align: center;
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
