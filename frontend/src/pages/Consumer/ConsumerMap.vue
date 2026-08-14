<template>
  <q-dialog
    :model-value="modelValue"
    class="map-dialog"
    @update:model-value="$emit('update:modelValue', $event)"
    @show="onDialogShow"
  >
    <q-card class="map-dialog-card">

      <div class="map-container">
        <div ref="mapEl" class="leaflet-map" />

        <div class="map-address-overlay">
          <div class="map-address-label">Your Location</div>
          <div class="map-address-text">{{ address || 'Enter Address' }}</div>
        </div>

        <div class="map-controls">
          <q-btn round unelevated class="map-control-btn" icon="o_add" @click="zoomIn" />
          <q-btn round unelevated class="map-control-btn" icon="o_remove" @click="zoomOut" />
          <q-btn round unelevated class="map-control-btn map-locate-btn" icon="o_my_location" @click="locateMe" />
        </div>
      </div>

      <aside class="map-sidebar">
        <div class="sidebar-header">
          <div class="sidebar-title">Stores near you</div>
          <q-btn flat round dense icon="o_close" class="sidebar-close-btn" @click="closeDialog" />
        </div>

        <q-input
          v-model="searchQuery"
          outlined
          dense
          hide-bottom-space
          placeholder="Search Store"
          class="sidebar-search"
        >
          <template #prepend>
            <q-icon name="o_search" size="16px" />
          </template>
        </q-input>

        <div v-if="loading" class="sidebar-loading">Loading stores…</div>
        <p v-else-if="!filteredStores.length" class="sidebar-empty">No stores found.</p>

        <div v-else class="sidebar-list">
          <div
            v-for="store in filteredStores"
            :key="store.id"
            class="sidebar-store-card"
            @click="focusStore(store)"
          >
            <div class="sidebar-store-image">
              <img v-if="store.image" :src="store.image" :alt="store.name" />
              <q-icon v-else name="o_storefront" size="20px" />
            </div>
            <div class="sidebar-store-info">
              <div class="sidebar-store-name">{{ store.name }}</div>
              <div v-if="store.distance_meters != null" class="sidebar-store-distance">
                <q-icon name="o_directions_walk" size="12px" />
                {{ formatDistance(store.distance_meters) }}
              </div>
            </div>
          </div>
        </div>
      </aside>

    </q-card>
  </q-dialog>
</template>

<script setup>
import { ref, computed, onBeforeUnmount, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import { useStores } from '@/composables/useStores'
import { useAddress } from '@/composables/useAddress'

defineProps({
  modelValue: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:modelValue'])

const router = useRouter()
const { stores, loading, fetchStores } = useStores()
const { address } = useAddress()

const mapEl = ref(null)
const searchQuery = ref('')

let map = null
let unmounted = false
const markersById = {}

const storeIcon = L.divIcon({
  className: 'store-map-marker',
  html: `
    <svg width="30" height="40" viewBox="0 0 30 40">
      <path d="M15 0C6.7 0 0 6.7 0 15c0 11.25 15 25 15 25s15-13.75 15-25C30 6.7 23.3 0 15 0z" fill="#bd2427" stroke="#ffffff" stroke-width="1.5"/>
      <circle cx="15" cy="15" r="6.5" fill="#ffffff"/>
    </svg>
  `,
  iconSize: [30, 40],
  iconAnchor: [15, 40],
  popupAnchor: [0, -36]
})

const meIcon = L.divIcon({
  className: 'me-map-marker',
  html: `
    <span class="me-map-marker-pulse"></span>
    <span class="me-map-marker-badge">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="#ffffff">
        <path d="M12 12c2.7 0 4.9-2.2 4.9-4.9S14.7 2.2 12 2.2 7.1 4.4 7.1 7.1 9.3 12 12 12zm0 2.5c-3.3 0-9.8 1.6-9.8 4.9v2.4h19.6v-2.4c0-3.3-6.5-4.9-9.8-4.9z"/>
      </svg>
    </span>
  `,
  iconSize: [34, 34],
  iconAnchor: [17, 17]
})

const formatDistance = (meters) => {
  if (meters == null) return ''
  const rounded = Math.round(meters)
  if (rounded < 1000) return `${rounded} m away`
  return `${(meters / 1000).toFixed(1)} km away`
}

const filteredStores = computed(() => {
  const q = searchQuery.value.trim().toLowerCase()
  if (!q) return stores.value
  return stores.value.filter((store) => store.name.toLowerCase().includes(q))
})

const HTML_ESCAPES = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }
const escapeHtml = (value) => String(value).replace(/[&<>"']/g, (char) => HTML_ESCAPES[char])

const buildStorePopup = (store) => {
  const name = escapeHtml(store.name)
  const image = store.image ? escapeHtml(store.image) : ''
  return `
    <div class="store-popup">
      ${image ? `<img src="${image}" alt="${name}" class="store-popup-img" />` : ''}
      <div class="store-popup-body">
        <div class="store-popup-name">${name}</div>
        ${store.distance_meters != null ? `<div class="store-popup-distance">${formatDistance(store.distance_meters)}</div>` : ''}
        <a href="#/consumer/stores/${store.slug || store.id}" class="store-popup-link">View Store →</a>
      </div>
    </div>
  `
}

const renderMarkers = () => {
  if (!map || unmounted) return

  Object.values(markersById).forEach((marker) => map.removeLayer(marker))
  Object.keys(markersById).forEach((key) => delete markersById[key])

  stores.value.forEach((store) => {
    if (store.latitude == null || store.longitude == null) return
    const marker = L.marker([store.latitude, store.longitude], { icon: storeIcon })
      .addTo(map)
      .bindPopup(buildStorePopup(store))
    markersById[store.id] = marker
  })
}

const focusStore = (store) => {
  const marker = markersById[store.id]
  if (!marker) {
    closeDialog()
    router.push(`/consumer/stores/${store.slug || store.id}`)
    return
  }
  map.setView(marker.getLatLng(), 16)
  marker.openPopup()
}

const zoomIn = () => map?.zoomIn()
const zoomOut = () => map?.zoomOut()

const closeDialog = () => emit('update:modelValue', false)

const locateMe = () => {
  if (!map) return
  const lat = localStorage.getItem('consumer_lat')
  const lng = localStorage.getItem('consumer_lng')
  if (lat && lng) {
    map.setView([Number(lat), Number(lng)], 15)
    return
  }
  if (!navigator.geolocation) return
  navigator.geolocation.getCurrentPosition((position) => {
    if (unmounted || !map) return
    map.setView([position.coords.latitude, position.coords.longitude], 15)
  })
}

const onDialogShow = async () => {
  unmounted = false

  const lat = localStorage.getItem('consumer_lat')
  const lng = localStorage.getItem('consumer_lng')
  const center = lat && lng ? [Number(lat), Number(lng)] : [14.5995, 120.9842]

  await nextTick()
  if (unmounted || !mapEl.value) return

  map = L.map(mapEl.value, { zoomControl: false }).setView(center, 15)
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors',
    maxZoom: 19
  }).addTo(map)

  if (lat && lng) {
    L.marker([Number(lat), Number(lng)], { icon: meIcon }).addTo(map)
  }

  map.invalidateSize()

  await fetchStores()
  if (unmounted) return
  renderMarkers()
}

onBeforeUnmount(() => {
  unmounted = true
  if (map) {
    map.remove()
    map = null
  }
})
</script>

<style scoped>
.map-dialog :deep(.q-dialog__inner) {
  padding: 24px;
}

.map-dialog-card {
  width: min(1200px, 94vw);
  height: min(760px, 88vh);
  max-width: none;

  display: flex;

  border-radius: 16px;

  overflow: hidden;

  font-family: 'Roboto', Arial, sans-serif;
}

.map-container {
  flex: 1;
  position: relative;
}

.leaflet-map {
  width: 100%;
  height: 100%;
}

.map-address-overlay {
  position: absolute;
  top: 16px;
  left: 16px;
  z-index: 1000;

  padding: 10px 16px;

  border-radius: 10px;

  background: #ffffff;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.12);
}

.map-address-label {
  font-size: 10.5px;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;

  color: #8992a2;
}

.map-address-text {
  margin-top: 2px;

  font-size: 13px;
  font-weight: 600;

  color: #222222;
}

.map-controls {
  position: absolute;
  bottom: 20px;
  left: 16px;
  z-index: 1000;

  display: flex;
  flex-direction: column;

  gap: 8px;
}

.map-control-btn {
  width: 38px;
  height: 38px;

  background: #ffffff;
  color: #333333;

  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);

  transition: box-shadow 0.2s, transform 0.15s, background-color 0.15s;
}

.map-control-btn:hover {
  background: #fafafa;

  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);

  transform: translateY(-1px);
}

.map-locate-btn {
  color: #bd2427;
}

.map-locate-btn:hover {
  background: #fdecec;
}

/* SIDEBAR */

.map-sidebar {
  width: 340px;
  flex-shrink: 0;

  display: flex;
  flex-direction: column;

  border-left: 1px solid #e8e8e8;

  background: #ffffff;
}

.sidebar-header {
  display: flex;
  align-items: center;
  justify-content: space-between;

  padding: 16px 16px 0;
}

.sidebar-title {
  font-size: 17px;
  font-weight: 700;

  color: #111111;
}

.sidebar-close-btn {
  color: #767676;
}

.sidebar-search {
  margin: 12px 16px;
}

.sidebar-search :deep(.q-field__control) {
  height: 40px;

  border-radius: 8px;
}

.sidebar-loading,
.sidebar-empty {
  padding: 24px 16px;

  text-align: center;

  font-size: 13px;

  color: #8992a2;
}

.sidebar-list {
  flex: 1;

  overflow-y: auto;
  padding: 0 10px 10px;
}

.sidebar-store-card {
  display: flex;
  align-items: center;

  gap: 12px;
  padding: 10px 6px;

  border-radius: 10px;

  cursor: pointer;

  transition: background-color 0.15s;
}

.sidebar-store-card:hover {
  background: #fafafa;
}

.sidebar-store-image {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;

  width: 60px;
  height: 60px;

  border-radius: 8px;
  border: 1px solid #e8e8e8;

  background: linear-gradient(145deg, #f7f7f8 0%, #ececee 100%);
  color: #bd2427;

  overflow: hidden;
}

.sidebar-store-image img {
  width: 100%;
  height: 100%;

  object-fit: cover;
}

.sidebar-store-info {
  min-width: 0;
}

.sidebar-store-name {
  font-size: 13.5px;
  font-weight: 700;
  line-height: 1.3;

  color: #222222;

  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}

.sidebar-store-distance {
  display: flex;
  align-items: center;

  gap: 4px;
  margin-top: 3px;

  font-size: 12px;

  color: #8992a2;
}

/* LEAFLET MARKERS + POPUP — targets Leaflet-injected DOM outside Vue's render tree. */

.map-container :deep(.store-map-marker) {
  filter: drop-shadow(0 2px 3px rgba(0, 0, 0, 0.35));
}

.map-container :deep(.me-map-marker) {
  position: relative;

  display: flex;
  align-items: center;
  justify-content: center;
}

.map-container :deep(.me-map-marker-badge) {
  position: relative;
  z-index: 1;

  display: flex;
  align-items: center;
  justify-content: center;

  width: 30px;
  height: 30px;

  border-radius: 50%;
  border: 3px solid #ffffff;

  background: #2563eb;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.35);
}

.map-container :deep(.me-map-marker-pulse) {
  position: absolute;

  width: 46px;
  height: 46px;

  border-radius: 50%;

  background: rgba(37, 99, 235, 0.25);

  animation: me-marker-pulse 2s ease-out infinite;
}

@keyframes me-marker-pulse {
  0% {
    transform: scale(0.4);
    opacity: 1;
  }
  100% {
    transform: scale(1.4);
    opacity: 0;
  }
}

.map-container :deep(.leaflet-popup-content-wrapper) {
  padding: 0;

  border-radius: 12px;

  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);

  overflow: hidden;
}

.map-container :deep(.leaflet-popup-content) {
  width: 220px !important;
  margin: 0;
}

.map-container :deep(.leaflet-popup-tip) {
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.12);
}

.map-container :deep(.leaflet-popup-close-button) {
  top: 8px !important;
  right: 8px !important;

  width: 22px !important;
  height: 22px !important;

  display: flex;
  align-items: center;
  justify-content: center;

  border-radius: 50%;

  background: rgba(255, 255, 255, 0.9);
  color: #767676 !important;

  font-size: 16px !important;

  transition: color 0.15s, background-color 0.15s;
}

.map-container :deep(.leaflet-popup-close-button:hover) {
  background: #f4f4f4;
  color: #333333 !important;
}

.map-container :deep(.store-popup) {
  font-family: 'Roboto', Arial, sans-serif;
}

.map-container :deep(.store-popup-img) {
  display: block;

  width: 100%;
  height: 110px;

  object-fit: cover;
}

.map-container :deep(.store-popup-body) {
  padding: 10px 12px 12px;
}

.map-container :deep(.store-popup-name) {
  font-size: 13.5px;
  font-weight: 700;

  color: #222222;
}

.map-container :deep(.store-popup-distance) {
  margin-top: 2px;

  font-size: 11.5px;

  color: #8992a2;
}

.map-container :deep(.store-popup-link) {
  display: inline-block;

  margin-top: 6px;

  font-size: 12px;
  font-weight: 600;
  text-decoration: none;

  color: #bd2427;
}

.map-container :deep(.store-popup-link:hover) {
  text-decoration: underline;
}

@media (max-width: 900px) {
  .map-dialog-card {
    flex-direction: column;
  }

  .map-sidebar {
    width: 100%;
    height: 240px;

    border-left: none;
    border-top: 1px solid #e8e8e8;
  }
}
</style>
