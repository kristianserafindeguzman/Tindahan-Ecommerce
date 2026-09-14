<template>
  <div
    class="language-switcher"
    :class="{ 'language-switcher-compact': compact }"
    @click.stop
    @keydown.stop
  >
    <span
      class="language-name"
      :class="{ 'language-name-active': lang === 'en' }"
      >English</span
    >
    <q-toggle
      :dense="compact"
      :model-value="lang"
      false-value="en"
      true-value="fil"
      color="primary"
      keep-color
      size="sm"
      :aria-label="t('Change language') + ': English / Taglish'"
      @update:model-value="setLanguage"
    />
    <span
      class="language-name"
      :class="{ 'language-name-active': lang === 'fil' }"
      >Taglish</span
    >
  </div>
</template>

<script setup>
import { useConsumerLanguage } from '@/composables/useConsumerLanguage'
defineProps({ compact: Boolean })

const { lang, setLanguage, t } = useConsumerLanguage()
</script>

<style scoped>
.language-switcher {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  min-height: 44px;
  white-space: nowrap;
}
.language-name {
  color: var(--c-muted);
  font-size: var(--fs-sm);
  font-weight: 400;
}
.language-name-active {
  color: var(--c-brand);
  font-weight: 600;
}
.language-switcher :deep(.q-toggle) {
  min-height: 44px;
}
.language-switcher-compact {
  gap: 8px;
  min-height: 32px;
}
.language-switcher-compact :deep(.q-toggle) {
  justify-content: center;
  min-width: 32px;
  min-height: 32px;
}
</style>
