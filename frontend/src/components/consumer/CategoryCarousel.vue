<template>
  <div class="categories-row">
    <q-btn flat dense round :ripple="false" icon="o_chevron_left" class="categories-nav" :disable="atStart" :aria-label="t('Previous categories')" @click="scrollByPage(-1)" />

    <div class="categories-track" ref="track" @scroll.passive="updateEnds">
      <CategoryCard
        v-for="category in categories"
        :key="category.id"
        :category="category"
        @click="$emit('select', category)"
      />
    </div>

    <q-btn flat dense round :ripple="false" icon="o_chevron_right" class="categories-nav" :disable="atEnd" :aria-label="t('More categories')" @click="scrollByPage(1)" />
  </div>
</template>

<script setup>
import { useConsumerLanguage } from '@/composables/useConsumerLanguage'

import { ref, watch, nextTick, onMounted, onBeforeUnmount } from 'vue'
import CategoryCard from '@/components/consumer/CategoryCard.vue'

const { t } = useConsumerLanguage()

const props = defineProps({
  categories: {
    type: Array,
    required: true
  }
})

defineEmits(['select'])

// The "<" and ">" buttons move the track by roughly one "page" of cards, and each dims once there is nothing further that way.
const track = ref(null)
const atStart = ref(true)
const atEnd = ref(true)

const updateEnds = () => {
  const el = track.value
  if (!el) return
  atStart.value = el.scrollLeft <= 4
  atEnd.value = el.scrollLeft + el.clientWidth >= el.scrollWidth - 4
}

const scrollByPage = direction => {
  track.value?.scrollBy({ left: direction * track.value.clientWidth, behavior: 'smooth' })
}

// The ends change when the window resizes or the categories arrive, not only on scroll.
let resizeObserver = null

onMounted(() => {
  updateEnds()
  resizeObserver = new ResizeObserver(updateEnds)
  if (track.value) resizeObserver.observe(track.value)
})

onBeforeUnmount(() => resizeObserver?.disconnect())

watch(() => props.categories.length, () => nextTick(updateEnds))
</script>

<style scoped>
.categories-row {
  display: flex;
  align-items: stretch;

  gap: 12px;
}

.categories-track {
  display: flex;
  align-items: stretch;
  flex: 1;

  gap: 12px;
  min-width: 0;
  margin: -10px -6px;
  padding: 10px 6px 16px;

  overflow-x: auto;
  scroll-behavior: smooth;
  scrollbar-width: none;
}

.categories-track::-webkit-scrollbar {
  display: none;
}

.categories-nav :deep(.q-icon) {
  font-size: 20px;
}

.categories-nav {
  min-width: auto;
  min-height: auto;

  display: flex;
  align-items: center;
  justify-content: center;

  width: 36px;
  min-width: 36px;
  height: 36px;

  align-self: center;

  border: 1px solid var(--c-border);
  border-radius: var(--r-lg);

  background: #ffffff;
  color: var(--c-brand);

  transition: background-color 0.15s, border-color 0.15s, opacity 0.15s;

  cursor: pointer;
}

.categories-nav:hover {
  border-color: var(--c-brand);
  background: var(--c-brand-tint);
}

/* Quasar's own disabled look is 0.6 opacity; a lighter fade keeps the pair visible as a set. */
.categories-nav.disabled {
  opacity: 0.4 !important;
}

.categories-nav.disabled:hover {
  border-color: var(--c-border);
  background: #ffffff;
}
</style>
