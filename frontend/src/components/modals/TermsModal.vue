<template>
  <LegalModal
    :model-value="modelValue"
    :content="terms"
    @update:model-value="$emit('update:modelValue', $event)"
  />
</template>

<script setup>
import LegalModal from './LegalModal.vue'
import { terms } from '@/i18n/legalContent'

defineProps({
  modelValue: {
    type: Boolean,
    default: false
  }
})

defineEmits(['update:modelValue'])
</script>
