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
            >
              <template #prepend>
                <q-icon name="swap_vert" size="16px" />
              </template>
            </q-select>
          </div>

          <q-btn
            unelevated
            no-caps
            dense
            icon="tune"
            label="Filters"
            class="filters-toggle-btn"
            @click="filtersOpen = !filtersOpen"
          >
            <span v-if="hasActiveFilters" class="filters-active-dot" />
          </q-btn>
        </div>
      </div>

      <div class="stores-layout">

        <div class="stores-main">
          <div class="stores-grid">
            <StoreCard v-for="store in paginatedStores" :key="store.id" :store="store" />
          </div>

          <p v-if="!filteredStores.length" class="stores-empty">
            No stores match your filters.
          </p>
        </div>

        <!-- FILTERS SIDEBAR (desktop) -->
        <aside v-if="filtersOpen && !isMobileFilters" class="filters-panel">
          <StoreFilters
            v-model:open-now="openNowOnly"
            v-model:sort="sortBy"
            :sort-options="SORT_OPTIONS"
            @close="filtersOpen = false"
            @clear="clearFilters"
          />
        </aside>

      </div>

      <AppPagination v-model="currentPage" :max="totalPages" />

    </div>

    <!-- FILTERS POPUP (mobile) -->
    <q-dialog v-model="mobileFiltersOpen">
      <q-card class="filters-dialog-card">
        <StoreFilters
          v-model:open-now="openNowOnly"
          v-model:sort="sortBy"
          :sort-options="SORT_OPTIONS"
          @close="filtersOpen = false"
          @clear="clearFilters"
        />
      </q-card>
    </q-dialog>

    <SiteFooter />

  </q-page>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import SiteHeader from '@/components/consumer/SiteHeader.vue'
import SiteFooter from '@/components/consumer/SiteFooter.vue'
import StoreCard from '@/components/consumer/StoreCard.vue'
import StoreFilters from '@/components/consumer/StoreFilters.vue'
import AppPagination from '@/components/consumer/AppPagination.vue'
import { useStores } from '@/composables/useStores'

const $q = useQuasar()

// Placeholder until real geolocation/address selection is wired up.
const address = ref('123 Shaw Boulevard, Barangay Pleasant Hills, Mandaluyong City')

const { stores, fetchStores } = useStores()

onMounted(fetchStores)

const SORT_OPTIONS = [
  { label: 'Popular', value: 'popular' },
  { label: 'Name: A-Z', value: 'name' }
]

const filtersOpen = ref(false)
const openNowOnly = ref(false)
const sortBy = ref('popular')

// Below this width the sidebar doesn't fit, so filtersOpen opens a popup dialog instead.
const isMobileFilters = computed(() => $q.screen.width < 900)

const mobileFiltersOpen = computed({
  get: () => filtersOpen.value && isMobileFilters.value,
  set: (val) => { filtersOpen.value = val }
})

const hasActiveFilters = computed(() => openNowOnly.value || sortBy.value !== 'popular')

const filteredStores = computed(() => {
  const list = stores.value.filter((store) => {
    if (openNowOnly.value && !store.isOpen) return false
    return true
  })

  if (sortBy.value === 'name') return [...list].sort((a, b) => a.name.localeCompare(b.name))
  return list
})

// Client-side pagination, same convention as ConsumerProducts.vue.
const PAGE_SIZE = 16
const currentPage = ref(1)

const totalPages = computed(() =>
  Math.max(1, Math.ceil(filteredStores.value.length / PAGE_SIZE))
)

const paginatedStores = computed(() => {
  const start = (currentPage.value - 1) * PAGE_SIZE
  return filteredStores.value.slice(start, start + PAGE_SIZE)
})

// Jump back to page 1 when filters change shape, otherwise the user could be stranded on a now-empty page.
watch(filteredStores, () => { currentPage.value = 1 })

const clearFilters = () => {
  openNowOnly.value = false
  sortBy.value = 'popular'
}
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

  /* width:100% needed: margin:0 auto on a flex item shrinks it to content width otherwise. */
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

.sort-select :deep(.q-field__control) {
  border-radius: 10px;
}

.sort-select :deep(.q-field__prepend) {
  color: #9ca3af;
}

/* Same red hover/focus treatment as .filters-toggle-btn, so the two paired controls feel consistent. */
.sort-select:hover :deep(.q-field__control) {
  background: #fdecec;
}

.sort-select:hover :deep(.q-field__control):before {
  border-color: #bd2427;
}

.sort-select.q-field--focused :deep(.q-field__control:after) {
  border-color: #bd2427;
}

.filters-toggle-btn {
  position: relative;

  height: 36px;
  min-height: 36px;
  padding: 0 14px;

  border: 1px solid #e2e2e2;
  border-radius: 6px;
  outline: none !important;

  background: #ffffff;
  color: #333333;

  font-size: 13px;
  font-weight: 500;

  transition: border-color 0.15s, background-color 0.15s;
}

/* Hides Quasar's own focus/ripple overlay so it can't paint a stray ring over our border/hover treatment. */
.filters-toggle-btn :deep(.q-focus-helper) {
  display: none;
}

.filters-active-dot {
  position: absolute;
  top: 6px;
  right: 6px;

  width: 7px;
  height: 7px;

  border-radius: 50%;
  border: 1.5px solid #ffffff;

  background: #bd2427;
}

.filters-toggle-btn :deep(.q-btn__content) {
  gap: 6px;
}

.filters-toggle-btn:hover {
  border-color: #bd2427;
  background: #fdecec;
}

/* Swaps the browser's default focus ring for a red glow matching the app's hover/focus treatment. */
.filters-toggle-btn:focus-visible {
  outline: none !important;

  box-shadow: 0 0 0 3px rgba(189, 36, 39, 0.25);
}

/* LAYOUT — MAIN + FILTERS SIDEBAR */

.stores-layout {
  display: flex;
  align-items: flex-start;

  gap: 24px;
}

.stores-main {
  flex: 1;
  width: 100%;
  min-width: 0;
}

/* Fixed 4 columns on desktop; auto-fill/minmax only kicks in below the tablet breakpoint (see RESPONSIVE). */
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

/* FILTERS SIDEBAR */

.filters-panel {
  flex-shrink: 0;

  width: 260px;

  border: 1px solid #e8e8e8;
  border-radius: 10px;

  background: #ffffff;

  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.filters-dialog-card {
  width: 100%;
  max-width: 380px;

  border-radius: 10px;
}

/* RESPONSIVE */

@media (max-width: 1024px) {
  .stores-grid {
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
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
}
</style>
