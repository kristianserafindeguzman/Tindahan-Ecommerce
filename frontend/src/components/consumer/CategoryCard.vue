<template>
  <q-card flat class="category-tile" @click="$emit('click', category)">
    <div class="category-tile-icon">
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

  border-radius: 10px;
  border: 1px solid #e8e8e8;

  background: #ffffff;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);

  transition: background-color 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;

  cursor: pointer;
}

.category-tile:hover {
  border-color: #f3c6c7;

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

  background: linear-gradient(145deg, #fdecec 0%, #fbdbdc 100%);
  color: #bd2427;

  transition: background-color 0.2s ease, color 0.2s ease, transform 0.2s ease;
}

.category-tile:hover .category-tile-icon {
  transform: scale(1.08);
}

.category-tile-label {
  font-size: 13px;
  font-weight: 500;
  line-height: 1.3;

  color: #111111;
  text-align: center;

  min-height: calc(1.3em * 2);

  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
