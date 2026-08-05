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
      <SectionBlock :title="resultsSectionTitle" view-all>
        <div class="products-grid">
          <ProductCard v-for="product in MOCK_PRODUCTS" :key="product.id" :product="product" />
        </div>
      </SectionBlock>

      <!-- STORES NEAR YOU -->
      <SectionBlock title="Stores near You" view-all>
        <div class="stores-row">
          <StoreCard v-for="store in MOCK_STORES" :key="store.id" :store="store" />
        </div>
      </SectionBlock>

      <!-- DISCOVER PRODUCTS -->
      <SectionBlock title="Discover Products" view-all>
        <div class="products-grid">
          <ProductCard v-for="product in MOCK_DISCOVER_PRODUCTS" :key="product.id" :product="product" />
        </div>

        <q-btn flat no-caps label="See More" class="see-more-btn" />
      </SectionBlock>

    </div>

  </q-page>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import SiteHeader from '@/components/consumer/SiteHeader.vue'
import SectionBlock from '@/components/consumer/SectionBlock.vue'
import CategoryCarousel from '@/components/consumer/CategoryCarousel.vue'
import ProductCard from '@/components/consumer/ProductCard.vue'
import StoreCard from '@/components/consumer/StoreCard.vue'
import { useCategories } from '@/composables/useCategories'

// ==========================================================
// AUTH STATE — this page renders for both guests and logged-in
// consumers, so SiteHeader reads localStorage directly rather than
// this page being route-guarded (see routes.js — this route no
// longer requires auth).
// ==========================================================

const isLoggedIn = computed(() => !!localStorage.getItem('auth_token'))

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

// ==========================================================
// MOCK DATA — replace with real /products and /stores endpoints
// once they exist.
// ==========================================================
// PERSONALIZATION — real personalization needs backend data that
// doesn't exist yet (order history, browsing signals, etc). For now
// both states pull from the same MOCK_PRODUCTS; only the section label
// differs. Once a real recommendation endpoint exists, swap the
// v-for source in the template based on isLoggedIn — this computed
// is the one place that needs to change.
// ==========================================================

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
  { id: 6, name: 'Kopiko Brown Coffee 3-in-1 25g', price: 10, distance: '5 m', store: 'Leslie Store' }
]
</script>

<style scoped>
.storefront-page {
  min-height: 100vh;

  background: #ffffff;

  font-family: 'Roboto', Arial, sans-serif;
}

/* =========================
   HOME CONTENT
========================= */

.home-content {
  max-width: 1200px;

  margin: 0 auto;

  padding: 24px;
}

/* =========================
   HERO BANNER
========================= */

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

/* =========================
   PRODUCTS GRID
========================= */

.products-grid {
  display: grid;
  grid-template-columns: repeat(6, 1fr);

  gap: 12px;
}

.see-more-btn {
  width: 100%;
  margin-top: 16px;
  padding: 12px;

  border: 1px solid #f0f0f0;
  border-radius: 8px;

  background: #ffffff;
  color: #bd2427;

  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);

  font-size: 14px;
  font-weight: 600;
  font-family: inherit;

  cursor: pointer;

  transition: background-color 0.15s, border-color 0.15s, box-shadow 0.2s;
}

.see-more-btn:hover {
  border-color: #f3c6c7;
  background: #fdecec;

  box-shadow: 0 10px 24px rgba(189, 36, 39, 0.14);
}

/* =========================
   STORES ROW
========================= */

.stores-row {
  display: grid;
  grid-template-columns: repeat(4, 1fr);

  gap: 12px;
}

/* =========================
   RESPONSIVE
========================= */

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
