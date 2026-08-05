<template>
  <q-page class="storefront-page">

    <SiteHeader :address="address" />

    <!-- MAIN CONTENT -->
    <div class="page-content">

      <div class="page-header-row">
        <div>
          <h1 class="page-title">Nearby Stores</h1>
          <p class="page-subtitle">Browse all sari-sari stores near you.</p>
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

      <div class="stores-layout">

        <div class="stores-main">
          <div class="stores-grid">
            <StoreCard v-for="store in filteredStores" :key="store.id" :store="store" />
          </div>

          <p v-if="!filteredStores.length" class="stores-empty">
            No stores match your filters.
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
            <q-checkbox v-model="openNowOnly" label="Open Now" dense />
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
import { ref, computed } from 'vue'
import SiteHeader from '@/components/consumer/SiteHeader.vue'
import StoreCard from '@/components/consumer/StoreCard.vue'

// ==========================================================
// ADDRESS — placeholder until real geolocation/address selection
// is wired up. Matches the address shown in the reference screenshots.
// ==========================================================

const address = ref('123 Shaw Boulevard, Barangay Pleasant Hills, Mandaluyong City')

// ==========================================================
// MOCK DATA — replace with a real /stores endpoint once it exists.
// distanceMeters backs the "Distance: Nearest" sort; StoreCard only
// reads the formatted `distance` string.
// ==========================================================

const MOCK_STORES = [
  { id: 1, name: 'Leslie Store', isOpen: true, closesAt: '10:00 pm', distance: '5 m', distanceMeters: 5 },
  { id: 2, name: 'Jmzhai Sari Sari Store', isOpen: true, closesAt: '9:00 pm', distance: '3 m', distanceMeters: 3 },
  { id: 3, name: 'Sol A Sari Sari Store', isOpen: false, closesAt: '8:00 pm', distance: '4 m', distanceMeters: 4 },
  { id: 4, name: "David's Sari-Sari Store", isOpen: true, closesAt: '9:30 pm', distance: '6 m', distanceMeters: 6 },
  { id: 5, name: "Ate Liza's Store", isOpen: true, closesAt: '9:00 pm', distance: '2 m', distanceMeters: 2 },
  { id: 6, name: 'Kuya Pedro Store', isOpen: true, closesAt: '8:30 pm', distance: '4 m', distanceMeters: 4 },
  { id: 7, name: 'Mang Dindo Store', isOpen: false, closesAt: '7:00 pm', distance: '7 m', distanceMeters: 7 },
  { id: 8, name: "Nena's Store", isOpen: true, closesAt: '10:00 pm', distance: '8 m', distanceMeters: 8 }
]

const SORT_OPTIONS = [
  { label: 'Popular', value: 'popular' },
  { label: 'Distance: Nearest', value: 'distance' },
  { label: 'Name: A-Z', value: 'name' }
]

// ==========================================================
// FILTER / SORT STATE
// ==========================================================

const filtersOpen = ref(false)
const openNowOnly = ref(false)
const sortBy = ref('popular')

const filteredStores = computed(() => {
  const list = MOCK_STORES.filter((store) => {
    if (openNowOnly.value && !store.isOpen) return false
    return true
  })

  if (sortBy.value === 'distance') return [...list].sort((a, b) => a.distanceMeters - b.distanceMeters)
  if (sortBy.value === 'name') return [...list].sort((a, b) => a.name.localeCompare(b.name))
  return list
})

const clearFilters = () => {
  openNowOnly.value = false
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
  width: 170px;
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
   LAYOUT — MAIN + FILTERS SIDEBAR
========================= */

.stores-layout {
  display: flex;
  align-items: flex-start;

  gap: 24px;
}

.stores-main {
  flex: 1;
  min-width: 0;
}

.stores-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);

  gap: 16px;
}

.stores-empty {
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
  .stores-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

@media (max-width: 900px) {
  .stores-layout {
    flex-direction: column;
  }

  .filters-panel {
    width: 100%;
  }

  .stores-grid {
    grid-template-columns: repeat(2, 1fr);
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

  .stores-grid {
    grid-template-columns: repeat(1, 1fr);
  }
}
</style>
