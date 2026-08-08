<template>
  <div v-if="max > 1" class="app-pagination-row flex flex-center">
    <q-pagination
      :model-value="modelValue"
      :max="max"
      :max-pages="maxPages"
      direction-links
      flat
      color="grey"
      active-color="primary"
      @update:model-value="$emit('update:modelValue', $event)"
    />
  </div>
</template>

<script setup>
defineProps({
  modelValue: {
    type: Number,
    required: true
  },
  max: {
    type: Number,
    required: true
  },
  maxPages: {
    type: Number,
    default: 6
  }
})

defineEmits(['update:modelValue'])
</script>

<style scoped>
.app-pagination-row {
  margin-top: 32px;
}

/* Brands the otherwise-default Quasar pagination buttons to match the app's rounded/red button language. */
.app-pagination-row :deep(.q-btn) {
  border-radius: 6px;
  font-weight: 600;

  transition: background-color 0.15s, color 0.15s, box-shadow 0.2s;
}

.app-pagination-row :deep(.q-btn.bg-primary) {
  box-shadow: 0 2px 8px rgba(189, 36, 39, 0.25);
}

.app-pagination-row :deep(.q-btn:not(.bg-primary):hover) {
  background: #fdecec !important;
  color: #bd2427 !important;
}

.app-pagination-row :deep(.q-btn:focus-visible) {
  outline: none;
  box-shadow: 0 0 0 3px rgba(189, 36, 39, 0.3);
}
</style>
