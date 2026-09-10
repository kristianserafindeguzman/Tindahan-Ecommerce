<template>
  <q-card flat class="category-tile" @click="$emit('click', category)">
    <div class="category-tile-icon" :class="`category-tile-icon--${category.tone || 'brand'}`">
      <q-icon :name="category.icon" size="22px" />
    </div>
    <span class="category-tile-label">{{ category.label }}</span>
  </q-card>
</template>

<script setup>
defineProps({
  category: {
    type: Object,
    required: true
  }
})

defineEmits(['click'])
</script>

<style scoped>
.category-tile {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;

  gap: 10px;

  width: 132px;
  padding: 20px 12px;

  font-family: 'Roboto', Arial, sans-serif;

  border-radius: var(--r-lg);
  border: 1px solid var(--c-border);

  background: #ffffff;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);

  transition: background-color 0.2s ease, border-color 0.2s ease, box-shadow 0.25s cubic-bezier(0.4, 0, 0.2, 1), transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);

  cursor: pointer;

  animation: card-fade-up 0.4s ease both;
}

@keyframes card-fade-up {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

@media (prefers-reduced-motion: reduce) {
  .category-tile {
    animation: none;
  }
}

.category-tile:hover {
  border-color: var(--c-brand-tint-3);

  box-shadow: 0 10px 24px rgba(189, 36, 39, 0.14);
  transform: translateY(-3px);
}

.category-tile-icon {
  display: flex;
  align-items: center;
  justify-content: center;

  width: 44px;
  height: 44px;

  /* !important: QCard rounds a direct first-child's top corners to match its own radius by default. */
  border-radius: 50% !important;

  transition: background-color 0.2s ease, color 0.2s ease, transform 0.2s ease;
}

/* One muted tone per category, so the icons sit under the red brand bar without competing with it. */
.category-tile-icon--brand {
  background: linear-gradient(145deg, var(--c-brand-tint) 0%, var(--c-brand-tint-2) 100%);
  color: var(--c-brand);
}

.category-tile-icon--blue {
  background: linear-gradient(145deg, #e8f2fd 0%, #d6e8fa 100%);
  color: #1668ab;
}

.category-tile-icon--amber {
  background: linear-gradient(145deg, #fdf3e3 0%, #fae8cd 100%);
  color: #b06a10;
}

.category-tile-icon--orange {
  background: linear-gradient(145deg, #fdeee6 0%, #fadfd0 100%);
  color: #c1521c;
}

.category-tile-icon--rose {
  background: linear-gradient(145deg, #fdeaf2 0%, #f9d8e6 100%);
  color: #b3215f;
}

.category-tile-icon--teal {
  background: linear-gradient(145deg, #e2f5f2 0%, #cbeae5 100%);
  color: #0f766e;
}

.category-tile:hover .category-tile-icon {
  transform: scale(1.08);
}

.category-tile-label {
  font-size: var(--fs-lg);
  font-weight: 500;
  line-height: 1.3;

  color: var(--c-text);
  text-align: center;

  min-height: calc(1.3em * 2);

  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
