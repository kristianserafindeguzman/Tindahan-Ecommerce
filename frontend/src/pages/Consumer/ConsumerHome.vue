<template>
  <q-page class="storefront-page">

    <SiteHeader :address="address" />

    <!-- MAIN CONTENT -->
    <div class="home-content">

      <!-- HERO BANNER -->
      <div class="hero-banner">
        <img
          src="@/assets/tindahan-logo.png"
          alt="Tindahan Sari-Sari Store App"
          class="hero-logo"
        />
      </div>

      <!-- CATEGORIES -->
      <SectionBlock title="Categories">
        <CategoryCarousel :categories="categories" />
      </SectionBlock>

      <!-- RECOMMENDED / POPULAR PRODUCTS -->
      <SectionBlock :title="resultsSectionTitle" view-all @view-all="router.push('/consumer/personalize')">
        <div class="products-grid">
          <ProductCard v-for="product in MOCK_PRODUCTS" :key="product.id" :product="product" />
        </div>
      </SectionBlock>

      <!-- STORES NEAR YOU -->
      <SectionBlock title="Stores near You" view-all @view-all="router.push('/consumer/stores')">
        <div class="stores-row">
          <StoreCard v-for="store in MOCK_STORES" :key="store.id" :store="store" />
        </div>
      </SectionBlock>

      <!-- DISCOVER PRODUCTS -->
      <SectionBlock title="Discover Products" view-all @view-all="router.push('/consumer/products')">
        <div class="products-grid">
          <ProductCard v-for="product in visibleDiscoverProducts" :key="product.id" :product="product" />
        </div>

        <q-btn
          v-if="visibleDiscoverProducts.length < MOCK_DISCOVER_PRODUCTS.length"
          flat
          no-caps
          label="See More"
          class="see-more-btn"
          @click="discoverVisibleCount += DISCOVER_PAGE_SIZE"
        />
      </SectionBlock>

    </div>

    <SiteFooter />

  </q-page>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import SiteHeader from '@/components/consumer/SiteHeader.vue'
import SiteFooter from '@/components/consumer/SiteFooter.vue'
import SectionBlock from '@/components/consumer/SectionBlock.vue'
import CategoryCarousel from '@/components/consumer/CategoryCarousel.vue'
import ProductCard from '@/components/consumer/ProductCard.vue'
import StoreCard from '@/components/consumer/StoreCard.vue'
import { useCategories } from '@/composables/useCategories'

const router = useRouter()

// This page renders for guests and logged-in consumers alike, so SiteHeader reads localStorage directly instead of route-guarding.
const isLoggedIn = computed(() => !!localStorage.getItem('auth_token'))

// Placeholder until real geolocation/address selection is wired up.
const address = ref('123 Shaw Boulevard, Barangay Pleasant Hills, Mandaluyong City')

const { categories, fetchCategories } = useCategories()

onMounted(fetchCategories)

// Mock data — replace with real /products and /stores endpoints once they exist.
// Both states pull from the same MOCK_PRODUCTS; only the label differs until a real recommendation endpoint exists.

const resultsSectionTitle = computed(() =>
  isLoggedIn.value ? 'Recommended for You' : 'Popular Products Near You'
)

const MOCK_PRODUCTS = [
  { id: 1, name: 'Lucky Me Pancit Canton Kalamansi 80g', price: 18, distance: '5 m', store: 'Leslie Store' },
  { id: 2, name: 'Piattos Sour Cream & Onion', price: 15, distance: '5 m', store: 'Leslie Store' },
  { id: 3, name: 'Lucky Me Pancit Canton Kalamansi 80g', price: 18, distance: '5 m', store: 'Leslie Store' },
  { id: 4, name: 'Lucky Me Pancit Canton Kalamansi 80g', price: 18, distance: '5 m', store: 'Leslie Store' },
  { id: 5, name: 'Lucky Me Pancit Canton Kalamansi 80g', price: 18, distance: '5 m', store: 'Leslie Store' },
  { id: 6, name: 'Lucky Me Pancit Canton Kalamansi 80g', price: 18, distance: '5 m', store: 'Leslie Store' }
]

const MOCK_STORES = [
  { id: 1, name: 'Leslie Store', isOpen: true, closesAt: '10:00 pm', distance: '5 m' },
  { id: 2, name: 'Jmzhai Sari Sari Store', isOpen: true, closesAt: '9:00 pm', distance: '3 m' },
  { id: 3, name: 'Sol A Sari Sari Store', isOpen: false, closesAt: '8:00 pm', distance: '4 m' },
  { id: 4, name: "David's Sari-Sari Store", isOpen: true, closesAt: '9:30 pm', distance: '6 m' }
]

const MOCK_DISCOVER_PRODUCTS = [
  { id: 1, name: 'Datu Puti Soy Sauce 1L', price: 42, distance: '3 m', store: 'Jmzhai Sari Sari Store' },
  { id: 2, name: 'Century Tuna Flakes in Oil 155g', price: 35, distance: '3 m', store: 'Jmzhai Sari Sari Store' },
  { id: 3, name: 'Safeguard Bar Soap 90g', price: 25, distance: '4 m', store: 'Sol A Sari Sari Store' },
  { id: 4, name: 'Nescafe 3-in-1 Original 20g', price: 9, distance: '4 m', store: 'Sol A Sari Sari Store' },
  { id: 5, name: 'Silver Swan Soy Sauce 385ml', price: 22, distance: '5 m', store: 'Leslie Store' },
  { id: 6, name: 'Kopiko Brown Coffee 3-in-1 25g', price: 10, distance: '5 m', store: 'Leslie Store' },
  { id: 7, name: 'Coca-Cola 1.5L', price: 75, distance: '2 m', store: 'Leslie Store' },
  { id: 8, name: 'Del Monte Tuna 155g', price: 38, distance: '3 m', store: 'Leslie Store' },
  { id: 9, name: 'Gardenia Bread', price: 46, distance: '4 m', store: "David's Sari-Sari Store" },
  { id: 10, name: 'Alaska Evaporada 370ml', price: 25, distance: '2 m', store: 'Leslie Store' },
  { id: 11, name: 'Jasmine Rice 1kg', price: 52, distance: '2 m', store: 'Leslie Store' },
  { id: 12, name: 'Selecta Ice Cream 1.3L', price: 99, distance: '5 m', store: "David's Sari-Sari Store" },
  { id: 13, name: 'Piattos Sour Cream & Onion', price: 15, distance: '5 m', store: 'Leslie Store' },
  { id: 14, name: 'Lucky Me Pancit Canton Kalamansi 80g', price: 18, distance: '3 m', store: 'Jmzhai Sari Sari Store' },
  { id: 15, name: 'Sanicare Bath Soap', price: 15, distance: '3 m', store: 'Jmzhai Sari Sari Store' },
  { id: 16, name: 'Surf Powder Detergent', price: 65, distance: '4 m', store: 'Jmzhai Sari Sari Store' },
  { id: 17, name: 'Kopiko Blanca Twin Pack', price: 22, distance: '5 m', store: "Sol A Sari Sari Store" },
  { id: 18, name: 'Selecta Ice Cream 1.3L', price: 99, distance: '5 m', store: "Sol A Sari Sari Store" }
]

// "See More" reveals additional products in place rather than navigating away — that's what "View All" is for.

const DISCOVER_PAGE_SIZE = 6
const discoverVisibleCount = ref(DISCOVER_PAGE_SIZE)

const visibleDiscoverProducts = computed(() =>
  MOCK_DISCOVER_PRODUCTS.slice(0, discoverVisibleCount.value)
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
  display: flex;
  align-items: center;
  justify-content: center;

  padding: 36px 24px;
  margin-bottom: 32px;

  border-radius: 8px;

  background:
    linear-gradient(
      145deg,
      #c02226 0%,
      #9c171b 55%,
      #651012 100%
    );
}

.hero-logo {
  width: 320px;
  max-width: 100%;
  height: auto;

  object-fit: contain;
}

/* PRODUCTS GRID */

.products-grid {
  display: grid;
  grid-template-columns: repeat(6, 1fr);

  gap: 12px;
}

.see-more-btn {
  width: 100%;
  margin-top: 16px;
  padding: 12px;

  border: 1px solid #e2e2e2;
  border-radius: 8px;

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

.stores-row {
  display: grid;
  grid-template-columns: repeat(4, 1fr);

  gap: 12px;
}

/* RESPONSIVE */

@media (max-width: 900px) {
  .products-grid,
  .stores-row {
    grid-template-columns: repeat(3, 1fr);
  }
}

@media (max-width: 600px) {
  .home-content {
    padding: 16px;
  }

  .hero-logo {
    width: 220px;
  }

  .products-grid,
  .stores-row {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>
