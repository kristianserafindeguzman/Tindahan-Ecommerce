<template>
  <div class="location-wrapper">

    <!-- MAP -->
    <div ref="mapContainer" class="map"></div>

    <!-- CURRENT LOCATION BUTTON -->
    <button
      type="button"
      class="location-button"
      @click="useCurrentLocation"
      :disabled="loadingLocation"
    >
      <span class="location-icon">◎</span>
      {{ loadingLocation ? 'Locating...' : 'Your Location' }}
    </button>

  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import { getCurrentPosition, reverseGeocode } from '@/utils/geolocation'

const emit = defineEmits(['location-selected'])

const mapContainer = ref(null)

let map = null
let marker = null
let unmounted = false

const loadingLocation = ref(false)

// Default location: Metro Manila
const defaultLocation = {
  latitude: 14.5764,
  longitude: 121.0351
}


// =========================
// INITIALIZE MAP
// =========================

onMounted(() => {

  map = L.map(mapContainer.value, {
    zoomControl: true
  }).setView(
    [defaultLocation.latitude, defaultLocation.longitude],
    15
  )

  // OpenStreetMap tiles
  L.tileLayer(
    'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
    {
      attribution:
        '&copy; OpenStreetMap contributors',
      maxZoom: 19
    }
  ).addTo(map)


  // Click anywhere on map
  map.on('click', async (event) => {

    const latitude = event.latlng.lat
    const longitude = event.latlng.lng

    await selectLocation(
      latitude,
      longitude
    )

  })


  // Try browser location
  useCurrentLocation()

})


// =========================
// CURRENT LOCATION
// =========================

const useCurrentLocation = async () => {

  loadingLocation.value = true

  try {

    const { latitude, longitude } = await getCurrentPosition()

    await selectLocation(
      latitude,
      longitude
    )

  } catch (error) {

    console.warn(
      'Unable to get location:',
      error.message
    )

  } finally {

    loadingLocation.value = false

  }
}


// =========================
// SELECT LOCATION
// =========================

const selectLocation = async (
  latitude,
  longitude
) => {

  // Bail out if the map was unmounted (e.g. address menu closed) while an
  // async geolocation/reverse-geocode call was still in flight — calling
  // Leaflet methods on a removed map, or emitting into a stale panel, would
  // otherwise silently corrupt state or throw.
  if (unmounted) return

  // Move map
  map.setView(
    [latitude, longitude],
    17
  )


  // Remove old marker
  if (marker) {
    map.removeLayer(marker)
  }


  // Create marker
  marker = L.marker(
    [latitude, longitude]
  ).addTo(map)


  // Reverse geocode
  const address =
    await reverseGeocode(
      latitude,
      longitude
    )

  if (unmounted) return

  // Send data to VendorRegistration
  emit(
    'location-selected',
    {
      latitude,
      longitude,
      address
    }
  )

}


// =========================
// CLEANUP
// =========================

onBeforeUnmount(() => {

  unmounted = true

  if (map) {
    map.remove()
  }

})

</script>


<style scoped>

.location-wrapper {
  position: relative;

  width: 100%;
  height: 100%;

  min-height: 280px;

  overflow: hidden;

  border-radius: 10px;
}


.map {
  width: 100%;
  height: 100%;

  min-height: 280px;
}


/* =========================
   LOCATION BUTTON
========================= */

.location-button {
  position: absolute;

  right: 12px;
  bottom: 12px;

  z-index: 1000;

  display: flex;
  align-items: center;

  gap: 7px;

  padding: 9px 13px;

  border: none;
  border-radius: 7px;

  background: #ffffff;

  color: #222222;

  font-size: 12px;
  font-weight: 600;

  box-shadow:
    0 2px 8px rgba(0, 0, 0, 0.18);

  cursor: pointer;
}


.location-button:hover {
  background: #f7f7f7;
}


.location-button:disabled {
  opacity: 0.7;

  cursor: wait;
}


.location-icon {
  font-size: 18px;

  line-height: 1;

  color: #111111;
}


/* =========================
   LEAFLET
========================= */

:deep(.leaflet-control-zoom) {
  border: none !important;
}


:deep(.leaflet-control-zoom a) {
  color: #333333 !important;
}

</style>