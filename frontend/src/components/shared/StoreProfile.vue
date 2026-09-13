<template>
  <!-- A store at a glance: its photo, name and owner, then hours, personal info, counts, contact, location and a map. -->
  <div class="sp">
    <div class="sp-banner">
      <img v-if="photoUrl" :src="photoUrl" :alt="`${name || 'Store'} photo`" />
      <div v-else class="sp-banner-empty">
        <q-icon name="o_storefront" size="36px" />
        <span>No store photo</span>
      </div>
      <div v-if="$slots.badge" class="sp-banner-badge"
        ><slot name="badge"
      /></div>
    </div>

    <div>
      <h2 class="sp-name">{{ name || 'Unnamed store' }}</h2>
      <div class="sp-owner">
        <q-icon name="o_person" size="18px" />{{ owner || 'Unknown owner' }}
      </div>
    </div>

    <slot name="notice" />

    <div class="sp-grid" :class="{ 'sp-grid--stats': stats.length }">
      <section class="sp-card sp-area-hours">
        <div class="sp-label"
          ><q-icon name="o_schedule" size="16px" />Operating hours</div
        >
        <ul v-if="hours.length" class="sp-hours">
          <li
            v-for="h in hours"
            :key="h.day"
            :class="{ 'sp-hours-closed': !h.open }"
          >
            <span>{{ h.day }}</span>
            <span>{{ h.text }}</span>
          </li>
        </ul>
        <p v-else class="sp-empty-text">No hours set</p>
      </section>

      <section class="sp-card sp-area-info">
        <div class="sp-label"
          ><q-icon name="o_badge" size="16px" />{{ infoTitle }}</div
        >
        <template v-for="(item, i) in info" :key="item.label">
          <div class="sp-sublabel" :class="{ 'sp-sublabel--first': i === 0 }">{{
            item.label
          }}</div>
          <div class="sp-value">{{ item.value || '—' }}</div>
        </template>
      </section>

      <!-- The store's counts, such as its products and orders, side by side above the contact card. -->
      <section v-if="stats.length" class="sp-card sp-stats sp-area-stats">
        <div v-for="stat in stats" :key="stat.label" class="sp-stat">
          <span class="sp-stat-icon"
            ><q-icon :name="stat.icon" size="18px"
          /></span>
          <span class="sp-stat-text">
            <span class="sp-stat-value">{{ stat.value ?? '—' }}</span>
            <span class="sp-stat-label">{{ stat.label }}</span>
          </span>
        </div>
      </section>

      <section class="sp-card sp-area-contact">
        <div class="sp-label"
          ><q-icon name="o_alternate_email" size="16px" />Email address</div
        >
        <div class="sp-value">{{ email || '—' }}</div>
        <div class="sp-sublabel"
          ><q-icon name="o_call" size="14px" />Phone number</div
        >
        <div class="sp-value">{{ phone || '—' }}</div>
      </section>

      <section class="sp-card sp-location sp-area-location">
        <div class="sp-label"
          ><q-icon name="o_location_on" size="16px" />Location</div
        >
        <template v-if="hasLocation">
          <div v-if="geocoding && !address" class="sp-address-loading">
            <q-skeleton type="text" width="92%" />
            <q-skeleton type="text" width="64%" />
          </div>
          <p v-else class="sp-address">{{
            address || lookedUp?.line || 'Street address unavailable'
          }}</p>
          <div class="sp-coords">{{ coordinates }}</div>
          <div v-if="lookedUp?.area" class="sp-area">{{ lookedUp.area }}</div>
          <q-btn
            outline
            no-caps
            color="primary"
            icon="o_directions"
            label="Get Directions"
            class="sp-directions"
            :href="directionsUrl"
            target="_blank"
            rel="noopener"
          />
        </template>
        <p v-else class="sp-empty-text">No location pinned</p>
      </section>

      <!-- A still picture of the neighbourhood with the store pinned in the middle; Get Directions opens the full map. -->
      <div class="sp-map sp-area-map">
        <div
          v-if="hasLocation"
          ref="mapBox"
          class="sp-static-map"
          role="img"
          :aria-label="`Map showing where ${name || 'the store'} is`"
        >
          <img
            v-for="tile in tiles"
            :key="tile.key"
            :src="tile.src"
            alt=""
            draggable="false"
            class="sp-tile"
            :style="{ transform: `translate(${tile.x}px, ${tile.y}px)` }"
          />
          <q-icon name="location_on" class="sp-pin" />
          <span class="sp-attrib">© OpenStreetMap contributors</span>
        </div>
        <div v-else class="sp-map-empty">
          <q-icon name="o_location_off" size="26px" />
          <span>No location pinned</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onBeforeUnmount } from 'vue'
import { weeklyHours } from '@/utils/storeHours'

const props = defineProps({
  name: { type: String, default: '' },
  owner: { type: String, default: '' },
  photo: { type: String, default: null },
  operatingDays: { type: [String, Object, Array], default: null },
  openingTime: { type: String, default: null },
  closingTime: { type: String, default: null },
  email: { type: String, default: '' },
  phone: { type: String, default: '' },
  latitude: { type: [String, Number], default: null },
  longitude: { type: [String, Number], default: null },
  // The store's saved address, shown in place of the looked-up one when there is one.
  address: { type: String, default: '' },
  // The middle card: a title and its label and value pairs, such as the owner's name and when they applied.
  infoTitle: { type: String, default: 'Personal info' },
  info: { type: Array, default: () => [] },
  // Counts shown above the contact card, as { label, value, icon }; left out for stores that haven't opened yet.
  stats: { type: Array, default: () => [] }
})

const photoUrl = computed(() =>
  props.photo && props.photo !== 'null' && String(props.photo).trim()
    ? props.photo
    : null
)
const hours = computed(() =>
  weeklyHours(props.operatingDays, props.openingTime, props.closingTime)
)

const lat = computed(() => parseFloat(props.latitude))
const lng = computed(() => parseFloat(props.longitude))
const hasLocation = computed(
  () =>
    Number.isFinite(lat.value) &&
    Number.isFinite(lng.value) &&
    !(lat.value === 0 && lng.value === 0)
)

const coordinates = computed(
  () =>
    `${Math.abs(lat.value).toFixed(4)}° ${lat.value >= 0 ? 'N' : 'S'}, ${Math.abs(lng.value).toFixed(4)}° ${lng.value >= 0 ? 'E' : 'W'}`
)
const directionsUrl = computed(
  () =>
    `https://www.google.com/maps/dir/?api=1&destination=${lat.value},${lng.value}`
)

// STATIC MAP — OpenStreetMap's own map squares laid side by side so the store sits in the middle; nothing moves or zooms.
const TILE = 256
const ZOOM = 16
const mapBox = ref(null)
const mapSize = ref({ w: 640, h: 280 })
const sizeWatcher =
  typeof ResizeObserver === 'undefined'
    ? null
    : new ResizeObserver(([entry]) => {
        mapSize.value = {
          w: Math.round(entry.contentRect.width),
          h: Math.round(entry.contentRect.height)
        }
      })

watch(mapBox, (el, old) => {
  if (old) sizeWatcher?.unobserve(old)
  if (el) sizeWatcher?.observe(el)
})
onBeforeUnmount(() => sizeWatcher?.disconnect())

const tiles = computed(() => {
  if (!hasLocation.value) return []
  const count = 2 ** ZOOM
  const rad = (lat.value * Math.PI) / 180
  // The store's spot on the whole-world map at this zoom, in pixels.
  const x = ((lng.value + 180) / 360) * count * TILE
  const y =
    ((1 - Math.log(Math.tan(rad) + 1 / Math.cos(rad)) / Math.PI) / 2) *
    count *
    TILE
  const { w, h } = mapSize.value
  const left = x - w / 2
  const top = y - h / 2
  const list = []
  for (
    let tx = Math.floor(left / TILE);
    tx <= Math.floor((left + w) / TILE);
    tx++
  ) {
    for (
      let ty = Math.floor(top / TILE);
      ty <= Math.floor((top + h) / TILE);
      ty++
    ) {
      list.push({
        key: `${tx}-${ty}`,
        src: `https://tile.openstreetmap.org/${ZOOM}/${((tx % count) + count) % count}/${ty}.png`,
        x: Math.round(tx * TILE - left),
        y: Math.round(ty * TILE - top)
      })
    }
  }
  return list
})

// The street address comes from OpenStreetMap's lookup, as the old admin view did; answers are kept so reopening a store doesn't ask again.
const geocodeCache = new Map()
const lookedUp = ref(null)
const geocoding = ref(false)
let lookup = 0

const readAddress = data => {
  if (!data?.display_name) return null
  const a = data.address || {}
  const street = [a.house_number, a.road].filter(Boolean).join(' ')
  const locality = a.suburb || a.neighbourhood || a.quarter || a.village
  const city = a.city || a.town || a.municipality || a.county
  const region = [a.postcode, a.state || a.region].filter(Boolean).join(' ')
  const line = [street, locality, city, region].filter(Boolean).join(', ')
  const area = [city, a.country].filter(Boolean).join(', ')
  return {
    line: line || data.display_name,
    area: [area, a.city_district].filter(Boolean).join(' • ')
  }
}

watch(
  [lat, lng],
  async () => {
    lookedUp.value = null
    if (!hasLocation.value) return
    const key = `${lat.value.toFixed(5)},${lng.value.toFixed(5)}`
    if (geocodeCache.has(key)) {
      lookedUp.value = geocodeCache.get(key)
      return
    }
    const mine = ++lookup
    geocoding.value = true
    try {
      const response = await fetch(
        `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat.value}&lon=${lng.value}`
      )
      const found = response.ok ? readAddress(await response.json()) : null
      if (found) geocodeCache.set(key, found)
      if (mine === lookup) lookedUp.value = found
    } catch {
      // Without the lookup the coordinates and map still show where the store is.
    } finally {
      if (mine === lookup) geocoding.value = false
    }
  },
  { immediate: true }
)
</script>

<style scoped>
.sp {
  container-type: inline-size;

  display: flex;
  flex-direction: column;

  gap: 14px;
}

.sp > * {
  flex-shrink: 0;
}

/* PHOTO — a wide banner with the status in its corner. */
.sp-banner {
  position: relative;

  overflow: hidden;

  aspect-ratio: 4 / 1;
  min-height: 120px;
  max-width: 100%;

  border: 1px solid var(--c-border);
  border-radius: var(--r-control);

  background: var(--c-surface-2);
}

.sp-banner img {
  display: block;

  width: 100%;
  height: 100%;

  object-fit: cover;
}

.sp-banner-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;

  gap: 6px;
  height: 100%;

  font-size: var(--fs-xs);

  color: var(--c-muted);
}

.sp-banner-badge {
  position: absolute;
  top: 12px;
  left: 12px;

  display: flex;

  border-radius: var(--r-pill);

  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

/* The store's name in the banner's Poppins, large enough to lead the page, with the owner just under it. */
.sp-name {
  margin: 0;

  font-family: 'Poppins', 'Roboto', Arial, sans-serif;
  font-size: var(--fs-4xl);
  font-weight: 700;
  line-height: 1.2;
  letter-spacing: -0.01em;

  color: var(--c-text);
}

.sp-owner {
  display: flex;
  align-items: center;

  gap: 6px;
  margin-top: 6px;

  font-size: var(--fs-md);
  font-weight: 500;

  color: var(--c-text-3);
}

.sp-owner .q-icon {
  color: var(--c-muted);
}

/* CARDS — hours down the left, the small cards beside them and the map filling the rest. */
.sp-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  grid-template-rows: auto 1fr auto;
  grid-template-areas:
    'hours info contact'
    'hours map map'
    'location map map';

  gap: 12px;
}

/* With counts, they sit above a shorter contact card, and the personal info card runs beside both. */
.sp-grid--stats {
  grid-template-rows: auto auto 1fr auto;
  grid-template-areas:
    'hours info stats'
    'hours info contact'
    'hours map map'
    'location map map';
}

.sp-area-hours {
  grid-area: hours;
}
.sp-area-info {
  grid-area: info;
}
.sp-area-stats {
  grid-area: stats;
}
.sp-area-contact {
  grid-area: contact;
}
.sp-area-location {
  grid-area: location;
}
.sp-area-map {
  grid-area: map;
}

.sp-card {
  display: flex;
  flex-direction: column;

  min-width: 0;
  padding: 16px;

  border: 1px solid var(--c-border);
  border-radius: var(--r-control);

  background: #ffffff;
}

.sp-label {
  display: flex;
  align-items: center;

  gap: 6px;
  margin-bottom: 12px;

  font-size: var(--fs-2xs);
  font-weight: 700;
  letter-spacing: 0.05em;
  text-transform: uppercase;

  color: var(--c-brand);
}

.sp-sublabel {
  display: flex;
  align-items: center;

  gap: 5px;
  margin: 12px 0 3px;

  font-size: var(--fs-2xs);
  font-weight: 600;
  letter-spacing: 0.05em;
  text-transform: uppercase;

  color: var(--c-muted);
}

.sp-sublabel--first {
  margin-top: 0;
}

.sp-value {
  overflow-wrap: anywhere;

  font-size: var(--fs-sm);
  font-weight: 500;
  line-height: 1.5;

  color: var(--c-text);
}

/* COUNTS — two tiles in one card, each an icon beside its number. */
.sp-stats {
  flex-direction: row;

  gap: 12px;
  padding: 14px 16px;
}

.sp-stat {
  display: flex;
  align-items: center;
  flex: 1 1 0;

  gap: 10px;
  min-width: 0;
}

.sp-stat + .sp-stat {
  padding-left: 12px;

  border-left: 1px solid var(--c-hairline);
}

.sp-stat-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;

  width: 36px;
  height: 36px;

  border-radius: var(--r-control);

  background: var(--c-brand-tint);

  color: var(--c-brand);
}

.sp-stat-text {
  display: flex;
  flex-direction: column;

  min-width: 0;
}

.sp-stat-value {
  font-family: 'Poppins', 'Roboto', Arial, sans-serif;
  font-size: var(--fs-xl);
  font-weight: 700;
  line-height: 1.2;

  color: var(--c-text);
}

.sp-stat-label {
  overflow: hidden;

  font-size: var(--fs-2xs);
  font-weight: 600;
  letter-spacing: 0.04em;
  text-overflow: ellipsis;
  text-transform: uppercase;
  white-space: nowrap;

  color: var(--c-muted);
}

.sp-hours {
  margin: 0;
  padding: 0;

  list-style: none;
}

.sp-hours li {
  display: flex;
  justify-content: space-between;

  gap: 12px;
  padding: 7px 0;

  border-bottom: 1px solid var(--c-hairline);

  font-size: var(--fs-xs);
}

.sp-hours li:first-child {
  padding-top: 0;
}

.sp-hours li:last-child {
  padding-bottom: 0;

  border-bottom: none;
}

.sp-hours li span:first-child {
  color: var(--c-text-3);
}

.sp-hours li span:last-child {
  font-weight: 500;
  white-space: nowrap;

  color: var(--c-text);
}

.sp-hours-closed span:last-child {
  font-weight: 400;

  color: var(--c-muted);
}

.sp-empty-text {
  margin: 0;

  font-size: var(--fs-sm);

  color: var(--c-muted);
}

/* LOCATION */
.sp-address {
  margin: 0;

  font-size: var(--fs-sm);
  line-height: 1.5;

  color: var(--c-text-2);
}

.sp-address-loading {
  display: flex;
  flex-direction: column;

  gap: 2px;
}

.sp-coords {
  margin-top: 8px;

  font-size: var(--fs-sm);
  font-weight: 600;
  font-variant-numeric: tabular-nums;

  color: var(--c-text);
}

.sp-area {
  margin-top: 2px;

  font-size: var(--fs-xs);

  color: var(--c-muted);
}

.sp-directions {
  width: 100%;
  height: 40px;
  margin-top: 16px;

  border-radius: var(--r-control);

  font-size: var(--fs-sm);
  font-weight: 600;
}

.sp-directions :deep(.q-icon) {
  font-size: 18px;
}

/* MAP */
.sp-map {
  position: relative;

  overflow: hidden;

  min-height: 260px;

  border: 1px solid var(--c-border);
  border-radius: var(--r-control);

  background: #e8eef2;
}

.sp-static-map {
  position: absolute;
  inset: 0;

  overflow: hidden;

  user-select: none;
}

.sp-tile {
  position: absolute;
  top: 0;
  left: 0;

  width: 256px;
  height: 256px;
  max-width: none;

  pointer-events: none;
}

/* The pin's point sits exactly on the store. */
.sp-pin {
  position: absolute;
  top: 50%;
  left: 50%;

  font-size: 44px;

  color: var(--c-brand);

  filter: drop-shadow(0 2px 3px rgba(0, 0, 0, 0.35));
  transform: translate(-50%, -100%);
}

.sp-attrib {
  position: absolute;
  right: 6px;
  bottom: 6px;

  padding: 1px 6px;

  border-radius: 4px;

  background: rgba(255, 255, 255, 0.85);

  font-size: 10px;

  color: var(--c-text-3);
}

.sp-map-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;

  gap: 6px;
  height: 100%;
  min-height: 260px;

  background: var(--c-surface-2);

  font-size: var(--fs-xs);

  color: var(--c-muted);
}

/* Narrower dialogs pair the cards two to a row, and phones take them one at a time. */
@container (max-width: 760px) {
  .sp-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    grid-template-rows: auto;
    grid-template-areas:
      'hours info'
      'hours contact'
      'location map';
  }

  .sp-grid--stats {
    grid-template-areas:
      'hours info'
      'hours stats'
      'hours contact'
      'location map';
  }
}

@container (max-width: 520px) {
  .sp-grid,
  .sp-grid--stats {
    grid-template-columns: minmax(0, 1fr);
    grid-template-areas:
      'hours'
      'info'
      'stats'
      'contact'
      'location'
      'map';
  }

  .sp-banner {
    aspect-ratio: 16 / 7;
  }

  .sp-name {
    font-size: var(--fs-2xl);
  }

  .sp-map,
  .sp-map-empty {
    min-height: 200px;
  }
}
</style>
