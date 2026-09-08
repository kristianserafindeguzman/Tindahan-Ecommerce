<template>
  <div class="categories-row">
    <div class="categories-track" ref="track">
      <CategoryCard
        v-for="category in categories"
        :key="category.id"
        :category="category"
        @click="$emit('select', category)"
      />
    </div>

    <q-btn flat dense round :ripple="false" icon="o_chevron_right" class="categories-next" aria-label="More categories" @click="scrollNext" />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import CategoryCard from '@/components/consumer/CategoryCard.vue'

defineProps({
  categories: {
    type: Array,
    required: true
  }
})

defineEmits(['select'])

// The ">" button nudges the track forward by roughly one "page" of cards.
const track = ref(null)

const scrollNext = () => {
  const el = track.value
  if (!el) return

  const atEnd = el.scrollLeft + el.clientWidth >= el.scrollWidth - 4

  el.scrollBy({
    left: atEnd ? -el.clientWidth : el.clientWidth,
    behavior: 'smooth'
  })
}
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

.categories-next :deep(.q-icon) {
  font-size: 20px;
}

.categories-next {
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

  transition: background-color 0.15s, border-color 0.15s;

  cursor: pointer;
}

.categories-next:hover {
  border-color: var(--c-brand);
  background: var(--c-brand-tint);
}
</style>

