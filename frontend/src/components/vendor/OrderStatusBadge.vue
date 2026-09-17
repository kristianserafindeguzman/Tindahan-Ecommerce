<template>
  <!-- The one status badge every vendor page uses: the status's own colour and its name. -->
  <span class="vp-status" :class="`vp-status--${statusTone(status)}`">{{ statusLabel(status) }}</span>
</template>

<script setup>
import { statusTone, statusLabel } from '@/utils/orderStatus'

defineProps({
  status: { type: String, default: '' }
})
</script>
