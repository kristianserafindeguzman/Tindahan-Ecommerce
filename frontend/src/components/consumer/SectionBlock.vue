<template>
  <section v-intersection.once="onReveal" class="section-block reveal">
    <div class="section-heading-row">
      <span class="section-heading">{{ title }}</span>
      <span v-if="viewAll" class="section-link" @click="$emit('view-all')">View All</span>
    </div>

    <slot />
  </section>
</template>

<script setup>
import { useReveal } from '@/composables/useReveal'

const { onReveal } = useReveal()

defineProps({
  title: {
    type: String,
    required: true
  },
  viewAll: {
    type: Boolean,
    default: false
  }
})

defineEmits(['view-all'])
</script>

<style scoped>
.section-block {
  margin-bottom: 24px;
}

.section-heading-row {
  display: flex;
  align-items: center;
  justify-content: space-between;

  margin-bottom: 20px;
}

.section-heading {
  font-size: var(--fs-2xl);
  letter-spacing: -0.01em;
  font-weight: 700;

  color: var(--c-text);
}

.section-link {
  font-size: var(--fs-lg);
  font-weight: 600;

  color: var(--c-brand);

  cursor: pointer;
}
</style>
