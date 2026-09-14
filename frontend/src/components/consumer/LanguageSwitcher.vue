<template>
  <div
    class="language-switcher"
    :class="{ 'language-switcher-compact': compact, 'language-switcher-settings': settings, 'language-switcher-header': header }"
    @click.stop
    @keydown.stop
  >
    <q-btn-dropdown
      v-if="header"
      flat
      no-caps
      :ripple="false"
      icon="o_language"
      :label="lang === 'en' ? 'EN' : 'FIL'"
      dropdown-icon="o_expand_more"
      :aria-label="t('Change language') + ': ' + (lang === 'en' ? 'English' : 'Filipino')"
      :menu-offset="[0, 8]"
      class="language-header-btn"
    >
      <q-list class="language-menu" role="group" :aria-label="t('Change language')">
        <q-item
          v-for="language in languages"
          :key="language.value"
          v-close-popup
          clickable
          :active="lang === language.value"
          active-class="language-menu-active"
          @click="setLanguage(language.value)"
        >
          <q-item-section>{{ language.label }}</q-item-section>
          <q-item-section side>
            <q-icon v-if="lang === language.value" name="o_check" size="18px" class="language-menu-check" />
          </q-item-section>
        </q-item>
      </q-list>
    </q-btn-dropdown>
    <q-btn-toggle
      v-else
      :model-value="lang"
      :options="[
        { label: 'English', value: 'en' },
        { label: 'Filipino', value: 'fil' }
      ]"
      no-caps
      no-wrap
      unelevated
      spread
      :ripple="false"
      toggle-color="primary"
      toggle-text-color="white"
      class="language-options"
      role="group"
      :aria-label="t('Change language') + ': English / Filipino'"
      @update:model-value="setLanguage"
    />
  </div>
</template>

<script setup>
import { useConsumerLanguage } from '@/composables/useConsumerLanguage'
defineProps({ compact: Boolean, settings: Boolean, header: Boolean })

const { lang, languages, setLanguage, t } = useConsumerLanguage()
</script>

<style scoped>
.language-switcher {
  width: 220px;
  max-width: 100%;
  white-space: nowrap;
}


.language-options {
  width: 100%;
  padding: 3px;
  gap: 3px;
  border: 1px solid var(--c-border);
  border-radius: var(--r-pill);
  background: var(--c-surface);
  box-shadow: none;
}

.language-options :deep(.q-btn) {
  min-height: 44px;
  padding: 0 14px;
  border-radius: var(--r-pill);
  color: var(--c-subtle);
  font-family: inherit;
  font-size: var(--fs-sm);
  font-weight: 600;
  line-height: 1.2;
  transition: background-color 0.15s, color 0.15s, box-shadow 0.15s;
}

.language-options :deep(.q-btn[aria-pressed='true']) {
  background: var(--c-brand) !important;
  color: var(--c-white);
  box-shadow: 0 2px 6px rgba(189, 36, 39, 0.2);
}

.language-options :deep(.q-btn[aria-pressed='true']:hover) {
  background: var(--c-brand-hover) !important;
}

.language-options :deep(.q-btn[aria-pressed='false']:hover) {
  background: var(--c-brand-tint);
  color: var(--c-brand);
}

.language-options :deep(.q-btn:focus-visible) {
  box-shadow: inset 0 0 0 2px var(--c-brand);
}

.language-options :deep(.q-btn[aria-pressed='true']:focus-visible) {
  box-shadow: inset 0 0 0 2px var(--c-white);
}

.language-switcher-settings .language-options {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 4px;
  padding: 4px;
  border-radius: var(--r-xl);
}

.language-switcher-settings .language-options :deep(.q-btn) {
  min-width: 0;
  min-height: 38px;
  padding: 0 12px;
  border-radius: var(--r-sm);
}

.language-switcher-settings .language-options :deep(.q-btn[aria-pressed='true']:not(:focus-visible)) {
  box-shadow: none;
}

.language-switcher-compact {
  width: 200px;
}

.language-switcher-compact .language-options :deep(.q-btn) {
  min-height: 36px;
  padding: 0 12px;
  font-size: var(--fs-xs);
}

.language-switcher.language-switcher-header {
  width: auto;
  flex-shrink: 0;
}

.language-header-btn {
  min-height: 44px;
  padding: 0 10px;
  border-radius: var(--r-pill);
  color: var(--c-white);
  font-size: var(--fs-xs);
  font-weight: 600;
}

.language-header-btn :deep(.q-btn__content) {
  flex-wrap: nowrap;
}

.language-header-btn :deep(.q-icon) {
  font-size: 20px;
}

.language-header-btn :deep(.on-left) {
  margin-right: 6px;
}

.language-header-btn :deep(.q-btn-dropdown__arrow) {
  margin-left: 4px;
  font-size: 16px;
}

.language-switcher-header.language-switcher-compact .language-header-btn {
  padding: 0 4px;
}

.language-menu {
  min-width: 160px;
  padding: 6px;
  color: var(--c-text);
  font-family: 'Roboto', Arial, sans-serif;
  font-size: var(--fs-sm);
}

.language-menu .q-item {
  min-height: 44px;
  border-radius: var(--r-sm);
}

.language-menu-active {
  background: var(--c-brand-tint);
  color: var(--c-brand);
  font-weight: 600;
}

.language-menu-check {
  color: var(--c-brand);
}

@media (prefers-reduced-motion: reduce) {
  .language-options :deep(.q-btn) {
    transition: none;
  }
}
</style>
