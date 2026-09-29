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

/* Frosted glass over the page's photo, with a light edge so it reads on both the light and dark parts of it. */
.auth-language-switcher :deep(.language-header-btn) {
  min-height: 44px;
  padding: 0 16px;

  border: 1px solid rgba(255, 255, 255, 0.45);

  background: rgba(255, 255, 255, 0.18);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  color: #ffffff;

  box-shadow: 0 6px 18px rgba(40, 6, 8, 0.25);

  font-weight: 700;

  transition: background-color 0.15s, border-color 0.15s;
}

.auth-language-switcher :deep(.language-header-btn:hover) {
  border-color: rgba(255, 255, 255, 0.7);

  background: rgba(255, 255, 255, 0.28);
}

@media (max-width: 600px) {
  .auth-language-switcher {
    top: 12px;
    right: 12px;
  }

  .auth-language-switcher :deep(.language-header-btn) {
    min-height: 38px;
    padding: 0 12px;
  }
}
</style>
