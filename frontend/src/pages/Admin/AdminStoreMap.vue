<template>
  <q-page class="vp-page">
    <div class="vp-container">
      <AdminHero
        icon="o_map"
        :title="t('title')"
        :subtitle="t('subtitle')"
        :stat-label="t('openNow')"
        :stat-value="openCount"
        :stat-unit="t('ofStores').replace('{x}', stores.length)"
        :loading="loading"
      />

      <div class="vp-card sm-card">
        <div class="sm-map-wrap">
          <div ref="mapEl" class="sm-map" />

          <div class="sm-map-legend">
            <span><span class="sm-dot sm-dot--open" />{{ t('open') }}</span>
            <span><span class="sm-dot sm-dot--closed" />{{ t('closed') }}</span>
          </div>

          <div class="sm-map-controls">
            <q-btn round unelevated class="sm-map-btn" icon="o_add" :aria-label="t('zoomIn')" @click="map?.zoomIn()" />
            <q-btn round unelevated class="sm-map-btn" icon="o_remove" :aria-label="t('zoomOut')" @click="map?.zoomOut()" />
            <q-btn round unelevated class="sm-map-btn" icon="o_fit_screen" :aria-label="t('showAll')" @click="fitAll" />
          </div>
        </div>

        <aside class="sm-side">
          <div class="sm-side-head">
            <q-input
              v-model="search"
              outlined
              dense
              clearable
              clear-icon="o_close"
              hide-bottom-space
              :placeholder="t('searchPlaceholder')"
              class="vp-search sm-search"
            >
              <template #prepend>
                <q-icon name="o_search" size="18px" />
              </template>
            </q-input>

            <div class="vp-chips" role="tablist" :aria-label="t('filterStores')">
              <button
                v-for="filter in filters"
                :key="filter.key"
                type="button"
                role="tab"
                class="vp-chip"
                :class="{ 'vp-chip--active': active === filter.key }"
                :aria-selected="active === filter.key"
                @click="active = filter.key"
              >
                {{ filter.label }}
                <span class="vp-chip-count">{{ loading ? '–' : filter.count }}</span>
              </button>
            </div>

            <div class="sm-updated">
              <span class="sm-live-dot" />
              {{ t('updated').replace('{time}', updatedLabel) }}
              <q-btn
                flat
                round
                dense
                size="sm"
                icon="o_refresh"
                class="sm-refresh"
                :loading="refreshing"
                :aria-label="t('refresh')"
                @click="refresh"
              />
            </div>
          </div>

          <div v-if="loading" class="sm-list">
            <div v-for="n in 5" :key="n" class="sm-row">
              <q-skeleton type="rect" width="44px" height="44px" class="sm-skel-tile" />
              <div class="sm-row-info">
                <q-skeleton type="text" width="60%" />
                <q-skeleton type="text" width="40%" height="12px" />
              </div>
            </div>
          </div>
          <div v-else-if="!visibleStores.length" class="sm-empty">
            <q-icon name="o_storefront" size="26px" />
            <span>{{ t('noStores') }}</span>
          </div>
          <div v-else class="sm-list">
            <button
              v-for="store in visibleStores"
              :key="store.id"
              type="button"
              class="sm-row"
              :class="{ 'sm-row--selected': selectedId === store.id }"
              @click="focusStore(store)"
            >
              <span class="adm-thumb adm-thumb--lg">
                <img v-if="store.image" :src="store.image" :alt="store.name" />
                <q-icon v-else name="o_storefront" size="20px" />
              </span>
              <span class="sm-row-info">
                <span class="sm-row-name">{{ store.name }}</span>
                <span class="sm-row-sub">{{ scheduleText(store) }}</span>
              </span>
              <span
                class="vp-status"
                :class="store.isOpen ? 'vp-status--success' : 'vp-status--danger'"
              >
                {{ store.isOpen ? t('open') : t('closed') }}
              </span>
            </button>
          </div>
        </aside>
      </div>
    </div>
  </q-page>
</template>

<script setup>
import { ref, shallowRef, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { useQuasar } from 'quasar'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import { api } from '@/boot/axios'
import AdminHero from '@/components/admin/AdminHero.vue'
import { useLanguage } from '@/composables/useLanguage'
import '@/css/admin-pages.scss'

// The consumer's store map, for the admin: every approved store, coloured by whether it is
// open right now, refreshed each minute since opening hours change status on their own.
const REFRESH_MS = 60000
const DEFAULT_CENTER = [14.5995, 120.9842]

const storeMapDict = {
  en: {
    title: 'Store Map',
    subtitle: 'See where every approved store is and which ones are open right now.',
    openNow: 'Open now',
    ofStores: 'of {x} stores',
    open: 'Open',
    closed: 'Closed',
    all: 'All',
    searchPlaceholder: 'Search store or address',
    filterStores: 'Filter stores',
    updated: 'Live · updated {time}',
    refresh: 'Refresh',
    noStores: 'No stores match.',
    noLocation: 'No map location',
    zoomIn: 'Zoom in',
    zoomOut: 'Zoom out',
    showAll: 'Show all stores',
    failedLoad: 'Couldn’t load the stores. Please refresh.',
    openUntil: 'Open until {time}',
    closedTill: 'Opens {time}',
    closedToday: 'Closed today'
  },
  ph: {
    title: 'Store Map',
    subtitle: 'Tingnan kung nasaan ang bawat approved store at kung alin ang bukas ngayon.',
    openNow: 'Bukas ngayon',
    ofStores: 'sa {x} na stores',
    open: 'Bukas',
    closed: 'Sarado',
    all: 'Lahat',
    searchPlaceholder: 'Hanapin ang store o address',
    filterStores: 'I-filter ang stores',
    updated: 'Live · na-update {time}',
    refresh: 'I-refresh',
    noStores: 'Walang tugmang store.',
    noLocation: 'Walang lokasyon sa mapa',
    zoomIn: 'Mag-zoom in',
    zoomOut: 'Mag-zoom out',
    showAll: 'Ipakita lahat ng stores',
    failedLoad: 'Hindi ma-load ang stores. Paki-refresh.',
    openUntil: 'Bukas hanggang {time}',
    closedTill: 'Magbubukas {time}',
    closedToday: 'Sarado ngayon'
  }
}

const { t, lang } = useLanguage(storeMapDict)
const $q = useQuasar()

const mapEl = ref(null)
const map = shallowRef(null)
const stores = ref([])
const loading = ref(true)
const refreshing = ref(false)
const updatedAt = ref(null)
const search = ref('')
const active = ref('all')
const selectedId = ref(null)

const markersById = {}
let refreshTimer = null
let fittedOnce = false

const openCount = computed(() => stores.value.filter(store => store.isOpen).length)

const filters = computed(() => [
  { key: 'all', label: t('all'), count: stores.value.length },
  { key: 'open', label: t('open'), count: openCount.value },
  { key: 'closed', label: t('closed'), count: stores.value.length - openCount.value }
])

// Open stores first so the list reads as "what is trading now", then by name.
const visibleStores = computed(() => {
  const q = (search.value || '').trim().toLowerCase()
  return stores.value
    .filter(store => active.value === 'all' || (active.value === 'open') === store.isOpen)
    .filter(store => !q || `${store.name} ${store.address || ''}`.toLowerCase().includes(q))
    .sort((a, b) => Number(b.isOpen) - Number(a.isOpen) || a.name.localeCompare(b.name))
})

const updatedLabel = computed(() =>
  updatedAt.value
    ? updatedAt.value.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })
    : '—'
)

// StoreHoursService sends "Open until 9:00 PM", "Closed till Monday 7:00 AM" or "Closed now".
const scheduleText = store => {
  const text = store.scheduleStatusText || ''
  if (text.startsWith('Open until ')) return t('openUntil').replace('{time}', text.slice(11))
  if (text.startsWith('Closed till ')) return t('closedTill').replace('{time}', text.slice(12))
  if (store.latitude == null || store.longitude == null) return t('noLocation')
  return store.isOpen ? t('open') : t('closedToday')
}

const HTML_ESCAPES = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }
const escapeHtml = value => String(value).replace(/[&<>"']/g, char => HTML_ESCAPES[char])

const markerIcon = isOpen => L.divIcon({
  className: `sm-marker ${isOpen ? 'sm-marker--open' : 'sm-marker--closed'}`,
  html: `
    <svg width="30" height="40" viewBox="0 0 30 40">
      <path d="M15 0C6.7 0 0 6.7 0 15c0 11.25 15 25 15 25s15-13.75 15-25C30 6.7 23.3 0 15 0z" fill="currentColor" stroke="#ffffff" stroke-width="1.5"/>
      <circle cx="15" cy="15" r="6.5" fill="#ffffff"/>
    </svg>
  `,
  iconSize: [30, 40],
  iconAnchor: [15, 40],
  popupAnchor: [0, -36]
})

const buildPopup = store => {
  const name = escapeHtml(store.name)
  const image = store.image ? escapeHtml(store.image) : ''
  const status = store.isOpen ? t('open') : t('closed')
  return `
    <div class="sm-popup">
      ${image ? `<img src="${image}" alt="${name}" class="sm-popup-img" />` : ''}
      <div class="sm-popup-body">
        <div class="sm-popup-name">${name}</div>
        <span class="sm-popup-status ${store.isOpen ? 'sm-popup-status--open' : 'sm-popup-status--closed'}">${escapeHtml(status)}</span>
        <div class="sm-popup-sub">${escapeHtml(scheduleText(store))}</div>
        ${store.address ? `<div class="sm-popup-sub">${escapeHtml(store.address)}</div>` : ''}
      </div>
    </div>
  `
}

const hasCoords = store => store.latitude != null && store.longitude != null

// Updates markers in place on a refresh, so an open popup stays open when a status changes.
const renderMarkers = () => {
  if (!map.value) return
  const seen = new Set()

  for (const store of stores.value) {
    if (!hasCoords(store)) continue
    seen.add(String(store.id))
    const existing = markersById[store.id]
    if (existing) {
      existing.setLatLng([store.latitude, store.longitude])
      existing.setIcon(markerIcon(store.isOpen))
      existing.setPopupContent(buildPopup(store))
    } else {
      markersById[store.id] = L.marker([store.latitude, store.longitude], { icon: markerIcon(store.isOpen) })
        .addTo(map.value)
        .bindPopup(buildPopup(store))
    }
  }

  for (const id of Object.keys(markersById)) {
    if (!seen.has(id)) {
      map.value.removeLayer(markersById[id])
      delete markersById[id]
    }
  }
}

const fitAll = () => {
  const points = stores.value.filter(hasCoords).map(store => [store.latitude, store.longitude])
  if (!map.value || !points.length) return
  map.value.fitBounds(points, { padding: [40, 40], maxZoom: 16 })
}

const focusStore = store => {
  selectedId.value = store.id
  const marker = markersById[store.id]
  if (!marker || !map.value) return
  map.value.setView(marker.getLatLng(), 16)
  marker.openPopup()
}

// A missing or blank coordinate becomes null rather than Number('') = 0, which would pin the
// store at 0°, 0° in the Atlantic.
const toCoord = value => {
  if (value == null || value === '') return null
  const number = Number(value)
  return Number.isFinite(number) ? number : null
}

// The minute-by-minute refresh runs silently: a failed background refresh keeps the last list
// on screen instead of raising the same error toast every minute.
const loadStores = async ({ silent = false } = {}) => {
  try {
    const { data } = await api.get('/stores')
    stores.value = (Array.isArray(data) ? data : []).map(store => ({
      id: store.id,
      name: store.name || '',
      address: store.address,
      image: store.image,
      isOpen: !!store.isOpen,
      scheduleStatusText: store.scheduleStatusText,
      latitude: toCoord(store.latitude),
      longitude: toCoord(store.longitude)
    }))
    updatedAt.value = new Date()
    renderMarkers()
    if (!fittedOnce) {
      fittedOnce = true
      fitAll()
    }
  } catch (error) {
    console.error('Failed to load the store map', error)
    if (!silent) $q.notify({ type: 'negative', message: t('failedLoad') })
  } finally {
    loading.value = false
  }
}

const refresh = async () => {
  refreshing.value = true
  await loadStores()
  refreshing.value = false
}

watch(lang, () => renderMarkers())

let unmounted = false
let resizeObserver = null

onMounted(async () => {
  await nextTick()
  if (unmounted || !mapEl.value) return

  map.value = L.map(mapEl.value, { zoomControl: false }).setView(DEFAULT_CENTER, 13)
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors',
    maxZoom: 19
  }).addTo(map.value)

  // Leaflet measures its box only once, so it is told again whenever the box changes size
  // (the drawer opening, a tablet rotating); otherwise part of the map stays grey.
  resizeObserver = new ResizeObserver(() => map.value?.invalidateSize())
  resizeObserver.observe(mapEl.value)

  await loadStores()
  // The page may have been left while the first load was in flight.
  if (unmounted) return
  refreshTimer = setInterval(() => loadStores({ silent: true }), REFRESH_MS)
})

onBeforeUnmount(() => {
  unmounted = true
  clearInterval(refreshTimer)
  resizeObserver?.disconnect()
  if (map.value) {
    map.value.remove()
    map.value = null
  }
})
</script>

<style scoped>
.sm-card {
  display: flex;

  height: min(680px, calc(100vh - 280px));
  min-height: 460px;

  overflow: hidden;
}

/* MAP */

.sm-map-wrap {
  position: relative;
  flex: 1;
  min-width: 0;
}

.sm-map {
  width: 100%;
  height: 100%;
}

.sm-map-legend {
  position: absolute;
  top: 14px;
  left: 14px;
  z-index: 1000;

  display: flex;
  gap: 14px;
  padding: 8px 14px;

  border-radius: var(--r-pill);

  background: #ffffff;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.12);

  font-size: var(--fs-xs);
  font-weight: 600;
  color: #334155;
}

.sm-map-legend > span {
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.sm-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
}

.sm-dot--open { background: #16a34a; }
.sm-dot--closed { background: #94a3b8; }

.sm-map-controls {
  position: absolute;
  bottom: 18px;
  left: 14px;
  z-index: 1000;

  display: flex;
  flex-direction: column;
  gap: 8px;
}

.sm-map-btn {
  width: 38px;
  height: 38px;

  background: #ffffff;
  color: #334155;

  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.sm-map-btn:hover {
  background: #f1f5f9;
}

/* SIDE LIST */

.sm-side {
  display: flex;
  flex-direction: column;
  flex-shrink: 0;

  width: 380px;

  border-left: 1px solid var(--c-hairline);
}

.sm-side-head {
  display: flex;
  flex-direction: column;
  gap: 12px;

  padding: 16px;

  border-bottom: 1px solid var(--c-hairline);
}

.sm-search {
  max-width: none;
  flex: none;
}

.sm-updated {
  display: flex;
  align-items: center;
  gap: 8px;

  font-size: var(--fs-xs);
  color: var(--c-muted);
}

.sm-live-dot {
  width: 8px;
  height: 8px;

  border-radius: 50%;

  background: #16a34a;

  animation: sm-live 2s ease-in-out infinite;
}

@keyframes sm-live {
  0%, 100% { box-shadow: 0 0 0 0 rgba(22, 163, 74, 0.45); }
  50% { box-shadow: 0 0 0 5px rgba(22, 163, 74, 0); }
}

@media (prefers-reduced-motion: reduce) {
  .sm-live-dot { animation: none; }
}

.sm-refresh {
  margin-left: auto;
  color: var(--c-muted);
}

.sm-list {
  flex: 1;
  min-height: 0;

  overflow-y: auto;
  padding: 6px 8px 10px;
}

.sm-row {
  display: flex;
  align-items: center;
  gap: 12px;

  width: 100%;
  padding: 10px 8px;

  border: none;
  border-radius: var(--r-control);

  background: transparent;

  font-family: inherit;
  text-align: left;
  color: var(--c-text-2);

  cursor: pointer;

  transition: background-color 0.15s;
}

.sm-row:hover,
.sm-row--selected {
  background: var(--c-surface-2);
}

.sm-row:focus-visible {
  outline: 2px solid var(--c-brand);
  outline-offset: -2px;
}

.sm-row-info {
  display: flex;
  flex-direction: column;
  gap: 2px;

  flex: 1;
  min-width: 0;
}

.sm-row-name {
  overflow: hidden;

  font-size: var(--fs-sm);
  font-weight: 700;
  text-overflow: ellipsis;
  white-space: nowrap;

  color: var(--c-text);
}

.sm-row-sub {
  overflow: hidden;

  font-size: var(--fs-xs);
  text-overflow: ellipsis;
  white-space: nowrap;

  color: var(--c-muted);
}

.sm-skel-tile {
  flex-shrink: 0;
  border-radius: var(--r-control);
}

.sm-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;

  padding: 40px 16px;

  font-size: var(--fs-sm);
  color: var(--c-muted);
}

/* DARK MODE — the admin layout marks the body while its dark mode is on. */

:global(body.admin-dark-mode) .sm-side,
:global(body.admin-dark-mode) .sm-side-head {
  border-color: #262a32;
}

:global(body.admin-dark-mode) .sm-row-name {
  color: #f8fafc;
}

:global(body.admin-dark-mode) .sm-row-sub,
:global(body.admin-dark-mode) .sm-updated {
  color: #94a3b8;
}

:global(body.admin-dark-mode) .sm-row:hover,
:global(body.admin-dark-mode) .sm-row--selected {
  background: #1f232a;
}

/* LEAFLET MARKERS + POPUP — targets Leaflet-injected DOM outside Vue's render tree. */

.sm-map-wrap :deep(.sm-marker) {
  filter: drop-shadow(0 2px 3px rgba(0, 0, 0, 0.35));
}

.sm-map-wrap :deep(.sm-marker--open) { color: #16a34a; }
.sm-map-wrap :deep(.sm-marker--closed) { color: #94a3b8; }

.sm-map-wrap :deep(.leaflet-popup-content-wrapper) {
  padding: 0;
  overflow: hidden;
  border-radius: 14px;
}

.sm-map-wrap :deep(.leaflet-popup-content) {
  width: 220px !important;
  margin: 0;
}

.sm-map-wrap :deep(.sm-popup-img) {
  display: block;
  width: 100%;
  height: 100px;
  object-fit: cover;
}

.sm-map-wrap :deep(.sm-popup-body) {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 4px;

  padding: 10px 12px 12px;
}

.sm-map-wrap :deep(.sm-popup-name) {
  font-size: 14px;
  font-weight: 700;
  color: #1e293b;
}

.sm-map-wrap :deep(.sm-popup-status) {
  padding: 2px 8px;

  border-radius: 999px;

  font-size: 11px;
  font-weight: 700;
}

.sm-map-wrap :deep(.sm-popup-status--open) { background: #dcfce7; color: #15803d; }
.sm-map-wrap :deep(.sm-popup-status--closed) { background: #fee2e2; color: #b91c1c; }

.sm-map-wrap :deep(.sm-popup-sub) {
  font-size: 12px;
  color: #64748b;
}

/* Stacked on tablets and phones: the map on top, the list below it. */
@media (max-width: 1023px) {
  .sm-card {
    flex-direction: column;
    height: auto;
    min-height: 0;
  }

  .sm-map-wrap {
    flex: none;
    height: 52vh;
    min-height: 300px;
  }

  .sm-side {
    width: 100%;
    border-left: none;
    border-top: 1px solid var(--c-hairline);
  }

  .sm-list {
    max-height: 60vh;
  }

  .sm-map-controls {
    left: auto;
    right: 12px;
    bottom: 12px;
    flex-direction: row;
  }
}
</style>
