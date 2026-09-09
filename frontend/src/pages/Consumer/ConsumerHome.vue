<template>
  <q-page class="storefront-page">

    <SiteHeader />

    <!-- MAIN CONTENT -->
    <div class="home-content">

      <section class="hero-banner">
        <div class="hero-content">
          <span class="hero-eyebrow">
            <q-icon name="o_location_on" size="14px" />
            Discover local stores
          </span>

          <h1 class="hero-title hero-title-lg">Explore sari-sari stores around you</h1>

          <p class="hero-sub">
            Find nearby stores, discover products, and shop from your local community.
          </p>

          <div class="hero-actions">
            <q-btn unelevated no-caps label="Browse Products" class="hero-cta" @click="router.push('/consumer/products')">
              <q-icon name="o_arrow_forward" size="16px" class="q-ml-xs" />
            </q-btn>
            <q-btn unelevated no-caps label="Show Map" class="hero-cta hero-cta--ghost" @click="showMapDialog = true">
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
      <SectionBlock title="Categories">
        <div v-if="categoriesLoading" class="categories-skeleton-row">
          <div v-for="n in 12" :key="n" class="category-skeleton-tile">
            <q-skeleton type="circle" class="category-skeleton-icon" />
            <q-skeleton type="text" class="category-skeleton-label" />
          </div>
        </div>
        <CategoryCarousel v-else :categories="categories" @select="goToCategory" />
      </SectionBlock>

      <!--
        Two routes into the catalogue. Standard storefront furniture: the hero sells the
        idea, these send you somewhere. Tinted panels rather than white cards so they
        read as navigation, not as another content section.
      -->
      <div class="promo-tiles">
        <button type="button" class="promo-tile promo-tile--brand" @click="router.push('/consumer/products')">
          <span class="promo-icon"><q-icon name="o_shopping_basket" size="22px" /></span>
          <span class="promo-body">
            <span class="promo-title">Shop everyday essentials</span>
            <span class="promo-text">Rice, drinks, snacks and household goods from stores near you.</span>
          </span>
          <q-icon name="o_arrow_forward" size="20px" class="promo-arrow" />
        </button>

        <button type="button" class="promo-tile promo-tile--solid" @click="router.push('/consumer/stores')">
          <span class="promo-icon"><q-icon name="o_storefront" size="22px" /></span>
          <span class="promo-body">
            <span class="promo-title">Browse local stores</span>
            <span class="promo-text">See opening hours and how far each store is from you.</span>
          </span>
          <q-icon name="o_arrow_forward" size="20px" class="promo-arrow" />
        </button>
      </div>

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

      <!--
        Marketplaces recruit supply on the storefront, and this is the one place a store
        owner is likely to be looking.

        Guests only: /vendor/register is meta.guest, so the router bounces anyone holding
        a token back to their role home. Shown to a signed-in consumer the button would
        look broken — it would return them to this very page.
      -->
      <section v-if="!isLoggedIn" class="seller-band">
        <div class="seller-copy">
          <h2 class="seller-title">Own a sari-sari store?</h2>
          <p class="seller-text">List what you stock and reach shoppers on your street.</p>
        </div>
        <q-btn unelevated no-caps label="Start selling" class="seller-cta" @click="router.push('/vendor/register')">
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

  gap: 40px;
  padding: 44px 40px;
  margin-bottom: 28px;

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

  max-width: 560px;
}

/* Eyebrow states the model — reserve then collect — before the headline. */
.hero-eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 6px;

  margin-bottom: 14px;
  padding: 5px 12px;

  border: 1px solid rgba(255, 255, 255, 0.28);
  border-radius: var(--r-pill);

  background: rgba(255, 255, 255, 0.12);
  color: #ffffff;

  font-size: var(--fs-2xs);
  font-weight: 600;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  white-space: nowrap;
}

.hero-sub {
  max-width: 46ch;
  margin: 0 0 22px;

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

  width: 380px;
  max-width: 46%;
}

.hero-logo {
  width: 100%;
  height: auto;

  object-fit: contain;
}

.hero-title {
  margin: 0 0 8px;

  font-size: var(--fs-4xl);
  font-weight: 700;
  line-height: 1.3;

  color: #ffffff;
}

/* Display font (Poppins, loaded in index.html) — everything else on the page stays
   Roboto. Bottom margin is small because .hero-sub now sits directly under it. */
.hero-title-lg {
  margin: 0 0 12px;

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

/* Ghost variant: the map is the secondary path, so it reads as an outline on the
   banner rather than competing with the white primary. */
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

/* PROMO TILES */
.promo-tiles {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;

  margin-bottom: 24px;
}

.promo-tile {
  display: flex;
  align-items: center;
  gap: 14px;

  width: 100%;
  padding: 16px 18px;

  border: none;
  border-radius: var(--r-xl);

  font-family: inherit;
  text-align: left;

  cursor: pointer;

  transition: box-shadow 0.2s, transform 0.2s;
}

.promo-tile:hover {
  box-shadow: 0 10px 24px rgba(17, 17, 17, 0.14);
  transform: translateY(-2px);
}

.promo-tile:focus-visible {
  outline: 2px solid var(--c-brand);
  outline-offset: 3px;
}

/* One tinted, one solid: two panels of the same weight would read as a single block.
   The contrast comes from depth of the same brand red rather than a second hue — the
   near-black this used to be belonged to no palette on the consumer surface. */
.promo-tile--brand {
  background: linear-gradient(135deg, var(--c-brand-tint) 0%, var(--c-brand-tint-2) 100%);
  color: var(--c-brand-deep);
}

.promo-tile--solid {
  background: linear-gradient(135deg, var(--c-brand) 0%, var(--c-brand-deep) 100%);
  color: #ffffff;
}

.promo-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;

  width: 40px;
  height: 40px;

  border-radius: var(--r-lg);
}

.promo-tile--brand .promo-icon {
  background: rgba(255, 255, 255, 0.6);
  color: var(--c-brand);
}

.promo-tile--solid .promo-icon {
  /* 0.12 read as a disc on the old near-black; on brand red it needs a touch more. */
  background: rgba(255, 255, 255, 0.18);
  color: #ffffff;
}

.promo-body {
  display: flex;
  flex-direction: column;
  gap: 3px;

  min-width: 0;
  flex: 1;
}

.promo-title {
  font-size: var(--fs-md);
  font-weight: 700;
  line-height: 1.25;
}

.promo-text {
  font-size: var(--fs-xs);
  line-height: 1.4;

  /* 0.78 white measured 4.22:1 on --c-brand, just under the bar; 0.85 clears it at
     4.76 and costs nothing on the tinted tile. */
  opacity: 0.85;
}

.promo-arrow {
  flex-shrink: 0;
  opacity: 0.6;

  transition: transform 0.2s ease;
}

.promo-tile:hover .promo-arrow {
  transform: translateX(3px);
  opacity: 1;
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

/* Below the laptop breakpoint the copy and the logo stop fitting side by side —
   the headline was wrapping to five lines with the logo floating beside it. */
@media (max-width: 1023px) {
  /* Stays a row. Stacking the logo above the copy added its full height to the hero —
     371px on a tablet. Beside the copy it costs nothing vertically. */
  .hero-banner {
    gap: 24px;
    padding: 26px 28px;
  }

  .hero-content {
    max-width: none;
  }

  .hero-eyebrow {
    margin-bottom: 10px;
  }

  .hero-title-lg {
    margin-bottom: 8px;
  }

  .hero-sub {
    margin-bottom: 16px;
  }

  .hero-logo-wrap {
    width: 250px;
    max-width: 38%;
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

  /* Icon and label sit on one line. Two tiles share ~358px here, so each has about
     172px — the icon shrinks to leave the label room, and the description and arrow
     are dropped rather than squeezed into what is left. */
  .promo-tile {
    gap: 10px;
    padding: 14px 12px;
  }

  .promo-icon {
    width: 34px;
    height: 34px;
  }

  .promo-icon :deep(.q-icon) {
    font-size: 18px;
  }

  .promo-text,
  .promo-arrow {
    display: none;
  }

  .promo-title {
    font-size: var(--fs-sm);
  }

  /* The hero was 468px on a 844px phone — over half the viewport before a single
     product was visible. Everything below trims it back to roughly a third. */
  .hero-banner {
    flex-direction: column-reverse;
    align-items: center;

    gap: 10px;
    padding: 18px;
    text-align: center;
  }

  .hero-eyebrow,
  .hero-sub {
    margin-left: auto;
    margin-right: auto;
  }

  .hero-actions {
    justify-content: center;
  }

  .hero-eyebrow {
    margin-bottom: 10px;
    padding: 4px 10px;
  }

  .hero-title-lg {
    margin-bottom: 8px;
    font-size: var(--fs-4xl);
  }

  .hero-sub {
    margin-bottom: 14px;
  }

  /* Side by side rather than stacked: two full-width rows cost ~54px of a hero that
     was already too tall, and both labels are short enough to sit on one line. */
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
    width: 150px;
    max-width: 46%;
  }
}
</style>





















