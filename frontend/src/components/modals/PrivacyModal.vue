<template>
  <LegalModal
    :model-value="modelValue"
    :content="privacy"
    @update:model-value="$emit('update:modelValue', $event)"
  />
</template>

<script setup>
import LegalModal from './LegalModal.vue'
import { privacy } from '@/i18n/legalContent'

defineProps({
  modelValue: {
    type: Boolean,
    default: false
  }
})

defineEmits(['update:modelValue'])
</script>
