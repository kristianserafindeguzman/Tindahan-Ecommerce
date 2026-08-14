<template>
  <div class="tracking-map-wrapper">
    <div v-if="!hasCoordinates" class="no-location-overlay flex flex-center bg-grey-2">
      <div class="text-center text-grey-6 q-pa-md">
        <q-icon name="location_off" size="48px" class="q-mb-md opacity-50" />
        <div class="text-subtitle1 text-weight-bold">Location Unavailable</div>
        <div class="text-caption">The consumer's location was not recorded for this order.</div>
      </div>
    </div>
    <div ref="mapEl" class="leaflet-map" :class="{ 'opacity-0': !hasCoordinates }"></div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

const props = defineProps({
  storeLat: { type: [Number, String], default: null },
  storeLng: { type: [Number, String], default: null },
  consumerLat: { type: [Number, String], default: null },
  consumerLng: { type: [Number, String], default: null },
  storeName: { type: String, default: 'Store' },
  consumerName: { type: String, default: 'Customer' }
})

const mapEl = ref(null)
let mapInstance = null
let storeMarker = null
let consumerMarker = null
let routeLine = null

const hasCoordinates = computed(() => {
  return props.storeLat !== null && props.storeLng !== null &&
         props.consumerLat !== null && props.consumerLng !== null
})

// Leaflet Icons
const iconUrls = {
  store: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
  storeRetina: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
  shadow: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png'
}

const storeIcon = L.icon({
  iconUrl: iconUrls.store,
  iconRetinaUrl: iconUrls.storeRetina,
  shadowUrl: iconUrls.shadow,
  iconSize: [25, 41],
  iconAnchor: [12, 41],
  popupAnchor: [1, -34],
  shadowSize: [41, 41]
})

const consumerIcon = L.divIcon({
  className: 'custom-consumer-icon',
  html: `<div style="background-color: #bd2427; width: 14px; height: 14px; border-radius: 50%; border: 2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3);"></div>`,
  iconSize: [14, 14],
  iconAnchor: [7, 7],
  popupAnchor: [0, -7]
})

const initMap = () => {
  if (!mapEl.value || !hasCoordinates.value) return

  const sLat = parseFloat(props.storeLat)
  const sLng = parseFloat(props.storeLng)
  const cLat = parseFloat(props.consumerLat)
  const cLng = parseFloat(props.consumerLng)

  mapInstance = L.map(mapEl.value, {
    zoomControl: false,
    attributionControl: false
  })
  
  L.control.zoom({ position: 'bottomright' }).addTo(mapInstance)

  L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
    maxZoom: 19
  }).addTo(mapInstance)

  storeMarker = L.marker([sLat, sLng], { icon: storeIcon })
    .bindPopup(`<b>${props.storeName}</b><br>Store Location`)
    .addTo(mapInstance)

  consumerMarker = L.marker([cLat, cLng], { icon: consumerIcon })
    .bindPopup(`<b>${props.consumerName}</b><br>Delivery/Pickup Location`)
    .addTo(mapInstance)

  routeLine = L.polyline([[cLat, cLng], [sLat, sLng]], {
    color: '#bd2427',
    weight: 3,
    dashArray: '5, 10',
    opacity: 0.7
  }).addTo(mapInstance)

  const bounds = L.latLngBounds([sLat, sLng], [cLat, cLng])
  mapInstance.fitBounds(bounds, { padding: [40, 40] })
}

const destroyMap = () => {
  if (mapInstance) {
    mapInstance.off()
    mapInstance.remove()
    mapInstance = null
  }
}

watch(() => props.consumerLat, () => {
  destroyMap()
  initMap()
})

onMounted(() => {
  // Add a small delay so container can correctly size itself inside a Quasar flex column
  setTimeout(() => {
    initMap()
  }, 100)
})

onUnmounted(() => {
  destroyMap()
})
</script>

<style scoped>
.tracking-map-wrapper {
  position: relative;
  width: 100%;
  height: 100%;
  min-height: 300px;
  border-radius: 12px;
  overflow: hidden;
}

.leaflet-map {
  width: 100%;
  height: 100%;
  position: absolute;
  top: 0;
  left: 0;
  z-index: 1;
}

.no-location-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 2;
}

.opacity-0 {
  opacity: 0;
}

.opacity-50 {
  opacity: 0.5;
}
</style>
