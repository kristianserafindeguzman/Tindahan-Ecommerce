<template>
  <q-card flat class="store-card" @click="router.push(`/consumer/stores/${store.slug || store.id}`)">
    <div class="store-card-image">
      <img
        v-if="store.image && !imageFailed"
        :src="store.image"
        :alt="store.name"
        class="store-card-img"
        @error="imageFailed = true"
      />
      <q-icon v-else name="o_storefront" size="36px" />

      <span class="store-status-tag" :class="{ 'store-status-tag-closed': !store.isOpen }">
        <span class="status-dot" :class="{ 'status-dot-closed': !store.isOpen }" />
        {{ store.isOpen ? 'Open' : 'Closed' }}
      </span>
    </div>
    <q-card-section class="store-card-body">
      <div class="store-card-name">
        <template v-for="(part, i) in nameParts" :key="i">
          <mark v-if="part.match" class="highlight-mark">{{ part.text }}</mark>
          <template v-else>{{ part.text }}</template>
        </template>
      </div>
      <div class="store-card-hours" :class="{ 'store-card-hours-closed': !store.isOpen }">
        {{ store.scheduleStatusText || (store.isOpen ? `Open until ${store.closesAt}` : 'Closed now') }}
      </div>
      <div v-if="storeCardDistanceText" class="store-card-distance">
        <q-icon name="o_location_on" size="13px" />
        <span class="store-card-distance-text">{{ storeCardDistanceText }}</span>
      </div>
    </q-card-section>
  </q-card>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { splitHighlightParts } from '@/utils/textHighlight'

const router = useRouter()

const props = defineProps({
  store: {
    type: Object,
    required: true
  },
  highlightQuery: {
    type: String,
    default: ''
  }
})

const nameParts = computed(() => splitHighlightParts(props.store.name, props.highlightQuery))
const imageFailed = ref(false)

const formatDistance = (meters) => {
  if (meters == null) return ''
  const rounded = Math.round(meters)
  if (rounded < 1000) return `${rounded} m away`
  return `${(meters / 1000).toFixed(1)} km away`
}

const storeCardDistanceText = computed(() => {
  const parts = []
  if (props.store.distance_meters != null) parts.push(formatDistance(props.store.distance_meters))
  if (props.store.address) parts.push(props.store.address)
  return parts.join(' • ')
})
</script>

<style scoped>
.store-card {
  overflow: hidden;

  font-family: 'Roboto', Arial, sans-serif;

  border-radius: 10px;
  border: 1px solid #e8e8e8;

  background: #ffffff;

  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);

  transition: box-shadow 0.2s, transform 0.2s, border-color 0.2s;

  cursor: pointer;
}

.store-card:hover {
  border-color: #f3c6c7;

  box-shadow: 0 10px 24px rgba(189, 36, 39, 0.14);
  transform: translateY(-3px);
}

.store-card-image {
  position: relative;

  display: flex;
  align-items: center;
  justify-content: center;

  aspect-ratio: 4 / 3;
  overflow: hidden;

  background: linear-gradient(145deg, #f7f7f8 0%, #ececee 100%);
  color: #bd2427;

  transition: background 0.2s;
}

.store-card:hover .store-card-image {
  background: linear-gradient(145deg, #fdecec 0%, #fbdbdc 100%);
}

.store-card-img {
  width: 100%;
  height: 100%;

  object-fit: cover;

  transition: transform 0.3s ease;
}

.store-card:hover .store-card-img {
  transform: scale(1.06);
}

.store-status-tag {
  position: absolute;
  top: 8px;
  left: 8px;

  display: flex;
  align-items: center;

  gap: 5px;
  padding: 3px 9px;

  border-radius: 999px;

  background: rgba(255, 255, 255, 0.92);
  color: #16a34a;

  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.03em;

  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
}

.store-status-tag-closed {
  color: #b91c1c;
}

.store-card-body {
  padding: 14px;
}

.store-card-name {
  font-size: 16px;
  font-weight: 700;
  line-height: 1.3;

  color: #222222;

  margin-bottom: 6px;

  overflow: hidden;

  white-space: nowrap;
  text-overflow: ellipsis;
}

.highlight-mark {
  background: #fdecec;
  color: #9c171b;
  font-weight: 700;

  border-radius: 2px;
}

.store-card-hours {
  margin-bottom: 12px;

  font-size: 12.5px;
  font-weight: 500;
  line-height: 1.3;

  color: #16a34a;
}

.store-card-hours-closed {
  color: #b91c1c;
}

.status-dot {
  width: 6px;
  height: 6px;
  flex-shrink: 0;

  border-radius: 50%;

  background: #16a34a;
}

.status-dot-closed {
  background: #b91c1c;
}

.store-card-distance {
  display: flex;
  align-items: center;

  gap: 5px;
  padding-top: 10px;

  border-top: 1px solid #f4f4f4;

  font-size: 12px;
  line-height: 1.3;

  color: #8992a2;
}

.store-card-distance .q-icon {
  flex-shrink: 0;
}

.store-card-distance-text {
  min-width: 0;

  overflow: hidden;

  white-space: nowrap;
  text-overflow: ellipsis;
}
</style>
