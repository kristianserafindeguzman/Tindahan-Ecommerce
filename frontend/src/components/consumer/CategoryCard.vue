<template>
  <q-card flat class="category-card" @click="$emit('click', category)">
    <div class="category-icon-wrap q--avoid-card-border">
      <q-icon :name="category.icon" size="24px" />
    </div>
    <span class="category-label">{{ category.label }}</span>
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
.category-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;

  gap: 10px;

  width: 112px;
  min-width: 112px;
  padding: 20px 10px;

  border-radius: 8px;
  border: 1px solid #f0f0f0;

  background: #ffffff;
  color: #bd2427;

  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);

  transition: box-shadow 0.2s, transform 0.2s, border-color 0.2s;

  cursor: pointer;
}

.category-card:hover {
  border-color: #f3c6c7;

  box-shadow: 0 10px 24px rgba(189, 36, 39, 0.14);
  transform: translateY(-3px);
}

.category-icon-wrap {
  display: flex;
  align-items: center;
  justify-content: center;

  width: 48px;
  height: 48px;

  border-radius: 50%;

  background: linear-gradient(145deg, #fdecec 0%, #fbdbdc 100%);
  color: #bd2427;

  transition: background 0.2s, color 0.2s, transform 0.2s;
}

.category-card:hover .category-icon-wrap {
  background: linear-gradient(145deg, #c02226 0%, #8f1417 100%);
  color: #ffffff;

  transform: scale(1.08);
}

.category-label {
  font-size: 13px;
  font-weight: 600;

  color: #333333;

  text-align: center;
}
</style>
