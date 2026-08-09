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

    <button type="button" class="categories-next" aria-label="More categories" @click="scrollNext">
      <q-icon name="o_chevron_right" size="20px" />
    </button>
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

.categories-next {
  display: flex;
  align-items: center;
  justify-content: center;

  width: 36px;
  min-width: 36px;
  height: 36px;

  align-self: center;

  border: 1px solid #eceef1;
  border-radius: 10px;

  background: #ffffff;
  color: #bd2427;

  transition: background-color 0.15s, border-color 0.15s;

  cursor: pointer;
}

.categories-next:hover {
  border-color: #bd2427;
  background: #fdecec;
}
</style>
