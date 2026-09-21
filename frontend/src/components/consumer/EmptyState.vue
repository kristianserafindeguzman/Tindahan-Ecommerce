<template>
  <div class="empty-state">
    <div class="empty-state-icon"><q-icon :name="icon" size="30px" /></div>

    <div class="empty-state-title">{{ title }}</div>
    <p v-if="text" class="empty-state-text">{{ text }}</p>

    <!-- The caller supplies its own button, so each empty state can send the shopper
         somewhere that makes sense for that page. -->
    <div v-if="$slots.action" class="empty-state-action">
      <slot name="action" />
    </div>
  </div>
</template>

<script setup>
// The shared empty state for the consumer pages: says what is missing, why, and what to do
// next, instead of a bare "nothing here" line.
defineProps({
  icon: { type: String, default: 'o_inbox' },
  title: { type: String, required: true },
  text: { type: String, default: '' }
})
</script>

<style scoped>
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;

  padding: 56px 24px;

  text-align: center;
}

.empty-state-icon {
  display: flex;
  align-items: center;
  justify-content: center;

  width: 64px;
  height: 64px;
  margin-bottom: 16px;

  border-radius: 50%;

  background: var(--c-brand-tint);
  color: var(--c-brand);
}

.empty-state-title {
  margin-bottom: 6px;

  font-size: var(--fs-xl);
  font-weight: 700;

  color: var(--c-text);
}

.empty-state-text {
  max-width: 380px;
  margin: 0;

  font-size: var(--fs-sm);
  line-height: 1.6;

  color: var(--c-text-3);
}

.empty-state-action {
  margin-top: 20px;
}

@media (max-width: 600px) {
  .empty-state {
    padding: 40px 16px;
  }
}
</style>
