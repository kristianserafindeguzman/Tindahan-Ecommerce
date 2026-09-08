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
import { formatDistance } from '@/utils/distance'

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

  border-radius: var(--r-lg);
  border: 1px solid var(--c-border);

  background: #ffffff;

  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);

  transition: box-shadow 0.25s cubic-bezier(0.4, 0, 0.2, 1), transform 0.25s cubic-bezier(0.4, 0, 0.2, 1), border-color 0.2s;

  cursor: pointer;

}

.store-card:hover {
  border-color: var(--c-brand-tint-3);

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

  background: linear-gradient(145deg, var(--c-surface) 0%, var(--c-surface) 100%);
  color: var(--c-brand);

  transition: background 0.2s;
}

.store-card:hover .store-card-image {
  background: linear-gradient(145deg, var(--c-brand-tint) 0%, var(--c-brand-tint-2) 100%);
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

  border-radius: var(--r-pill);

  background: rgba(255, 255, 255, 0.92);
  color: var(--c-success);

  font-size: var(--fs-xs);
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.03em;

  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
}

.store-status-tag-closed {
  color: var(--c-danger);
}

.store-card-body {
  padding: 14px;
}

.store-card-name {
  font-size: var(--fs-xl);
  font-weight: 700;
  line-height: 1.3;

  color: var(--c-text);

  margin-bottom: 6px;

  overflow: hidden;

  white-space: nowrap;
  text-overflow: ellipsis;
}

.highlight-mark {
  background: var(--c-brand-tint);
  color: var(--c-brand-deep);
  font-weight: 700;

  border-radius: var(--r-xs);
}

.store-card-hours {
  margin-bottom: 12px;

  font-size: var(--fs-md);
  font-weight: 500;
  line-height: 1.3;

  color: var(--c-success);
}

.store-card-hours-closed {
  color: var(--c-danger);
}

.status-dot {
  width: 6px;
  height: 6px;
  flex-shrink: 0;

  border-radius: 50%;

  background: var(--c-success);
}

.status-dot-closed {
  background: var(--c-danger);
}

.store-card-distance {
  display: flex;
  align-items: center;

  gap: 5px;
  padding-top: 10px;

  border-top: 1px solid var(--c-hairline);

  font-size: var(--fs-sm);
  line-height: 1.3;

  color: var(--c-muted);
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
