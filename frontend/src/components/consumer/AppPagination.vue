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
</style>
