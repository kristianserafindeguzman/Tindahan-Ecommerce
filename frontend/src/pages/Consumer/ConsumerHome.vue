<template>
  <q-page class="storefront-page">

    <SiteHeader />

    <!-- MAIN CONTENT -->
    <div class="home-content">

      <section class="hero-banner">
        <div class="hero-content">
          <span class="hero-eyebrow">
            <q-icon name="o_location_on" size="14px" />
            {{ t('Discover local stores') }}
          </span>

          <h1 class="hero-title hero-title-lg">{{ t('Explore sari-sari stores around you') }}</h1>

          <p class="hero-sub">
            {{ t('Find nearby stores, discover products, and shop from your local community.') }}
          </p>

          <div class="hero-actions">
            <q-btn unelevated no-caps :label="t('Browse Products')" class="hero-cta" @click="router.push('/consumer/products')">
              <q-icon name="o_arrow_forward" size="16px" class="q-ml-xs" />
            </q-btn>
            <q-btn unelevated no-caps :label="t('Show Map')" class="hero-cta hero-cta--ghost" @click="showMapDialog = true">
              <q-icon name="o_map" size="16px" class="q-ml-xs" />
            </q-btn>
          </div>
        </div>

        <div class="hero-logo-wrap">
          <img
            src="@/assets/tindahan-logo.png"
            alt="Tindahan Sari-Sari Store App"
            class="hero-logo"
          />
        </div>
      </section>


      <!-- CATEGORIES -->
      <SectionBlock :title="t('Categories')">
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
      <SectionBlock :title="t('Stores near You')" view-all @view-all="router.push('/consumer/stores')">
        <div v-if="storesLoading" class="stores-row">
          <CardSkeleton v-for="n in 4" :key="n" variant="store" />
        </div>
        <div v-else class="stores-row">
          <StoreCard v-for="store in nearbyStores" :key="store.id" :store="store" />
        </div>
      </SectionBlock>

      <!-- DISCOVER PRODUCTS -->
      <SectionBlock :title="t('Discover Products')" view-all @view-all="router.push('/consumer/products')">
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
          :label="t('See More')"
          class="see-more-btn"
          @click="discoverRowsShown += DISCOVER_ROWS_PER_PAGE"
        />
      </SectionBlock>

      <!-- Seller call-to-action for guests only, because /vendor/register is guest-only and would bounce a signed-in consumer straight back here. -->
      <section v-if="!isLoggedIn" class="seller-band">
        <div class="seller-copy">
          <h2 class="seller-title">{{ t('Own a sari-sari store?') }}</h2>
          <p class="seller-text">{{ t('List what you stock and reach shoppers on your street.') }}</p>
        </div>
        <q-btn unelevated no-caps :label="t('Start selling')" class="seller-cta" @click="router.push('/vendor/register')">
          <q-icon name="o_arrow_forward" size="16px" class="q-ml-xs" />
        </q-btn>
      </section>
    </div>

    <SiteFooter />

    <ProductDetailModal v-model="showProductModal" :product="selectedProduct" />
    <ConsumerMap v-model="showMapDialog" />

  </q-page>
</template>

<script setup>
import { useConsumerLanguage } from '@/composables/useConsumerLanguage'

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

const { t } = useConsumerLanguage()

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
    $q.notify({ type: 'positive', message: t('{name} added to cart.', { name: product.name }) })
  } catch (error) {
    $q.notify({ type: 'negative', message: t(error.response?.data?.message || 'Failed to add to cart.') })
  }
}

const resultsSectionTitle = computed(() =>
  isLoggedIn.value ? t('Recommended for You') : t('Popular Products Near You')
)

// Personalization is a logged-in-only route, so guests get routed to the guest-browsable catalog instead.
const resultsViewAllPath = computed(() => isLoggedIn.value ? '/consumer/personalize' : '/consumer/products')

// Nearby stores are the first four entries of the store list fetched for the current address.
const NEARBY_STORES_COUNT = 4
const nearbyStores = computed(() => stores.value.slice(0, NEARBY_STORES_COUNT))

// Both product sections show whole rows only, so they read the live column count of the auto-fill grid.
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

  gap: 32px;
  padding: 28px 32px;
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

/* Hero entrance on page load only, since the sections below use the scroll reveal in SectionBlock.vue. */
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

  flex: 1 1 0;
  min-width: 0;
  max-width: 620px;
}

/* Eyebrow states the model — reserve then collect — before the headline. */
.hero-eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 6px;

  max-width: 100%;
  margin-bottom: 12px;
  padding: 5px 12px;

  border: 1px solid rgba(255, 255, 255, 0.28);
  border-radius: var(--r-pill);

  background: rgba(255, 255, 255, 0.12);
  color: #ffffff;

  font-size: var(--fs-2xs);
  font-weight: 600;
  line-height: 1.4;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  white-space: normal;
}

.hero-eyebrow :deep(.q-icon) {
  flex-shrink: 0;
}

.hero-sub {
  max-width: 46ch;
  margin: 0 0 20px;
  font-size: var(--fs-md);
  line-height: 1.5;
  color: rgba(255, 255, 255, 0.86);
}

.hero-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}

.hero-logo-wrap {
  position: relative;
  z-index: 1;
  flex-shrink: 0;

  display: flex;
  align-items: center;
  justify-content: center;

  width: 330px;
  max-width: 36%;
  padding-top: 44px;
}

.hero-logo {
  width: 100%;
  height: auto;
  max-height: 210px;

  object-fit: contain;
}

.hero-title {
  margin: 0 0 8px;

  font-size: var(--fs-4xl);
  font-weight: 700;
  line-height: 1.3;

  color: #ffffff;
}

/* Display font Poppins, loaded in index.html. */
.hero-title-lg {
  margin: 0 0 12px;

  font-family: 'Poppins', 'Roboto', Arial, sans-serif;
  font-size: var(--fs-hero);
  font-weight: 800;
  line-height: 1.2;
  letter-spacing: -0.01em;
}

.hero-cta {
  height: 44px;
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

/* Ghost variant, since the map is the secondary path and should not compete with the white primary button. */
.hero-cta--ghost {
  border: 1px solid rgba(255, 255, 255, 0.55);

  background: transparent;
  color: #ffffff;

  box-shadow: none;
}

.hero-cta--ghost:hover {
  border-color: #ffffff;
  background: rgba(255, 255, 255, 0.14);
  box-shadow: none;
}

.hero-cta--ghost:active {
  background: rgba(255, 255, 255, 0.2);
}

/* SELLER BAND */
.seller-band {
  display: flex;
  align-items: center;
  justify-content: space-between;

  gap: 20px;
  margin: 8px 0 32px;
  padding: 26px 30px;

  border: 1px solid var(--c-border);
  border-radius: var(--r-xl);

  background: var(--c-surface);
}

.seller-title {
  margin: 0 0 4px;

  font-family: 'Poppins', 'Roboto', Arial, sans-serif;
  font-size: var(--fs-2xl);
  font-weight: 700;
  line-height: 1.25;

  color: var(--c-text);
}

.seller-text {
  margin: 0;

  font-size: var(--fs-sm);
  color: var(--c-muted);
}

.seller-cta {
  flex-shrink: 0;
  height: 44px;
  padding: 0 22px;

  border-radius: var(--r-sm);

  background: var(--c-brand);
  color: #ffffff;

  font-size: var(--fs-sm);
  font-weight: 700;

  box-shadow: var(--sh-brand);

  transition: background-color 0.15s, box-shadow 0.2s, transform 0.2s;
}

.seller-cta:hover {
  background: var(--c-brand-hover);
  box-shadow: var(--sh-brand-hover);
  transform: translateY(-1px);
}

.seller-cta :deep(.q-icon) {
  transition: transform 0.2s ease;
}

.seller-cta:hover :deep(.q-icon) {
  transform: translateX(3px);
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

/* QSkeleton draws the shimmer, so only the geometry of the tile it stands in for belongs here. */
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

/* Keep the copy and logo balanced on smaller screens. */
@media (max-width: 1023px) {
  /* Stays a row, because stacking the logo above the copy added its full height to the hero, 371px on a tablet. */
  .hero-banner {
    gap: 24px;
    padding: 24px;
  }

  .hero-content {
    max-width: none;
  }

  .hero-eyebrow {
    margin-bottom: 10px;
  }

  .hero-title-lg {
    margin-bottom: 10px;
  }

  .hero-sub {
    margin-bottom: 16px;
  }

  .hero-logo-wrap {
    width: 220px;
    max-width: 30%;
  }
}

@media (max-width: 720px) {
  .seller-band {
    flex-direction: column;
    align-items: flex-start;

    padding: 22px;
  }

  .seller-cta {
    width: 100%;
  }
}

@media (max-width: 600px) {
  .home-content {
    padding: 16px;
  }

  /* Keep the logo prominent without crowding the mobile controls. */
  .hero-banner {
    flex-direction: column-reverse;
    align-items: center;

    gap: 8px;
    padding: 60px 20px 20px;
    text-align: center;
  }

  .hero-eyebrow,
  .hero-sub {
    margin-left: auto;
    margin-right: auto;
  }

  .hero-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    flex: none;
    width: 100%;
  }

  .hero-actions {
    justify-content: center;
    width: 100%;
    margin-top: 6px;
  }

  .hero-eyebrow {
    margin-bottom: 0;
    padding: 4px 10px;
  }

  .hero-title-lg {
    margin-bottom: 0;
    font-size: var(--fs-4xl);
  }

  .hero-sub {
    display: none;
  }

  /* Keep the actions together while there is room for both labels. */
  .hero-actions {
    gap: 10px;
  }

  .hero-cta {
    flex: 1;
    padding: 0 12px;
    font-size: var(--fs-sm);
  }

  /* QBtn wraps its label by default; these need to stay on one line to fit. */
  .hero-cta :deep(.q-btn__content) {
    flex-wrap: nowrap;
    white-space: nowrap;
  }

  .hero-logo-wrap {
    width: 185px;
    max-width: 100%;
    padding-top: 0;
  }

  .hero-logo {
    display: block;
    max-height: 114px;
  }
}

@media (max-width: 400px) {
  .hero-actions {
    flex-direction: column;
  }

  .hero-cta {
    flex: none;
    width: 100%;
  }
}
</style>
