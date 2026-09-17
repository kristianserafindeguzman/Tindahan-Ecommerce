<template>
  <div class="location-wrapper">

    <!-- MAP -->
    <div ref="mapContainer" class="map"></div>

    <!-- CURRENT LOCATION BUTTON -->
    <button
      type="button"
      class="location-button"
      @click="useCurrentLocation()"
      :disabled="loadingLocation"
    >
      <span class="location-icon">◎</span>
      {{ translate(loadingLocation ? 'Locating...' : 'Your Location') }}
    </button>

  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import '@/utils/leafletDefaultIcon'
import { getCurrentPosition, reverseGeocode } from '@/utils/geolocation'

const props = defineProps({
  translate: { type: Function, default: text => text },
  // A saved pin to open on, which also skips jumping to the device's location; left out, the map behaves as before.
  initial: { type: Object, default: null }
})

const emit = defineEmits(['location-selected', 'pin-placed'])

const mapContainer = ref(null)

let map = null
let marker = null
let unmounted = false
// Bumped on every pin move, so a slow device-location or reverse-geocode result that a newer pin has overtaken is dropped instead of moving the pin back.
let pinVersion = 0
// Leaflet lays its tiles out for the container size it saw at the time, so a container that later grows needs a re-measure.
let resizeObserver = null

const loadingLocation = ref(false)

// Default location: Metro Manila
const defaultLocation = {
  latitude: 14.5764,
  longitude: 121.0351
}


// INITIALIZE MAP

onMounted(() => {

  const hasInitial = props.initial?.latitude != null && props.initial?.longitude != null
  const start = hasInitial ? props.initial : defaultLocation

  map = L.map(mapContainer.value, {
    zoomControl: true
  }).setView(
    [start.latitude, start.longitude],
    hasInitial ? 17 : 15
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


  // Keeps the map correct when its box changes size, such as the enlarged map on vendor registration.
  resizeObserver = new ResizeObserver(() => map?.invalidateSize())
  resizeObserver.observe(mapContainer.value)


  // Click anywhere on map
  map.on('click', async (event) => {

    const latitude = event.latlng.lat
    const longitude = event.latlng.lng

    await selectLocation(
      latitude,
      longitude
    )

  })


  // A saved pin is shown as it is, and otherwise the map tries the browser's location.
  if (hasInitial) {
    marker = L.marker([start.latitude, start.longitude]).addTo(map)
  } else {
    // Flagged auto so the parent can tell this opening guess from a spot the user chose.
    useCurrentLocation(true)
  }

})


// CURRENT LOCATION

const useCurrentLocation = async (auto = false) => {

  loadingLocation.value = true

  try {

    const version = pinVersion

    const { latitude, longitude } = await getCurrentPosition()

    if (version !== pinVersion) return

    await selectLocation(
      latitude,
      longitude,
      auto
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


// SELECT LOCATION

const selectLocation = async (
  latitude,
  longitude,
  auto = false
) => {

  // Bails out if the map was unmounted while a geolocation or reverse-geocode call was still in flight, since touching a removed map or emitting into a closed panel would throw or corrupt state.
  if (unmounted) return

  showLocation(latitude, longitude)

  // The pin is already where the user put it, so the parent hears about it before the address lookup, which takes a moment and can come back empty.
  emit('pin-placed', { latitude, longitude, auto })

  const version = pinVersion


  // Reverse geocode
  const address =
    await reverseGeocode(
      latitude,
      longitude
    )

  // A newer pin replaced this one while the lookup ran, so its address would label the wrong spot.
  if (unmounted || version !== pinVersion) return

  // Send data to VendorRegistration
  emit(
    'location-selected',
    {
      latitude,
      longitude,
      address,
      auto
    }
  )

}


// SHOW LOCATION

// Moves the pin to coordinates whose address the parent already has, such as a picked search suggestion, so it skips the lookup and emits nothing.
const showLocation = (
  latitude,
  longitude
) => {

  if (unmounted) return

  pinVersion++

  map.setView(
    [latitude, longitude],
    17
  )

  if (marker) {
    map.removeLayer(marker)
  }

  marker = L.marker(
    [latitude, longitude]
  ).addTo(map)

}

defineExpose({ showLocation })


// CLEANUP

onBeforeUnmount(() => {

  unmounted = true

  resizeObserver?.disconnect()

  if (map) {
    map.remove()
  }

})

</script>


<style scoped>

.location-wrapper {
  position: relative;

  /* Keeps Leaflet's pane and control z-indexes (up to 1000) inside the map, so an address suggestion list can lie over it. */
  isolation: isolate;

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


/* LOCATION BUTTON */

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


</style>
