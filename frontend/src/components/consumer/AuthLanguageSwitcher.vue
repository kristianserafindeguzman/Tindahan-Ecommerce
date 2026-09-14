<template>
  <LanguageSwitcher header class="auth-language-switcher" />
</template>

<script setup>
import LanguageSwitcher from './LanguageSwitcher.vue'
</script>

<style scoped>
.auth-language-switcher {
  position: absolute;
  top: 16px;
  right: 24px;
  z-index: 1;
}

.auth-language-switcher :deep(.language-header-btn) {
  min-height: 44px;
  padding: 0 14px;
  background: rgba(255, 255, 255, 0.12);
}

@media (max-width: 600px) {
  .auth-language-switcher {
    top: 12px;
    right: 16px;
  }

  .auth-language-switcher :deep(.language-header-btn) {
    background: var(--c-brand);
    color: var(--c-white);
  }
}
</style>
