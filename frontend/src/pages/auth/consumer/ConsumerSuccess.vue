<template>
  <AuthShell class="login-page">
    <div class="login-content">

      <div class="success-wrapper">
        <div class="success-icon-wrap">
          <q-icon name="o_check" size="36px" />
        </div>

        <h1>{{ t('Verification Successful') }}</h1>

        <p class="subtitle">
          {{ t('Your account has been verified. You can now log in to start exploring local sari-sari stores.') }}
        </p>

        <q-btn
          :label="t('Log in')"
          no-caps
          unelevated
          class="login-button full-width"
          @click="goToLogin"
        />
      </div>

    </div>
  </AuthShell>
</template>

<script setup>
import AuthShell from '@/components/auth/AuthShell.vue'
import { useConsumerLanguage } from '@/composables/useConsumerLanguage'
import { useRouter } from 'vue-router'

const { t } = useConsumerLanguage()

const router = useRouter()

// The verified number is carried over so the login form only needs the password.
const goToLogin = () => {
  router.push({ path: '/login', state: { identifier: history.state?.phone_number || '' } })
}
</script>

<style scoped>
.login-content {
  width: 100%;
  max-width: 390px;
}

/* SUCCESS CONTENT */

.success-wrapper {
  text-align: center;
}

/* A tinted tile, matching the success treatment on the consumer pages. */
.success-icon-wrap {
  display: inline-flex;
  align-items: center;
  justify-content: center;

  width: 72px;
  height: 72px;

  border-radius: var(--r-2xl);

  background: var(--c-success-tint);
  color: var(--c-success);

  margin-bottom: 22px;

  animation: success-icon-pop 240ms ease-out;
}

/* The same pop as the consumer profile's success dialog, scaling the tile in as the page opens. */
@keyframes success-icon-pop {
  from {
    opacity: 0;
    transform: scale(0.75);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

@media (prefers-reduced-motion: reduce) {
  .success-icon-wrap {
    animation: none;
  }
}

.login-content h1 {
  margin: 0 0 10px;

  font-size: 24px;
  line-height: 1.2;
  font-weight: 700;

  color: var(--c-text);
}

.subtitle {
  margin: 0 0 28px;

  font-size: var(--fs-sm);
  line-height: 1.6;

  color: var(--c-muted);
}

/* BUTTON */

.login-button {
  height: 48px;

  border-radius: var(--r-sm);

  background: var(--c-brand);
  color: #ffffff;

  font-family: 'Roboto', Arial, sans-serif;

  font-size: var(--fs-sm);
  font-weight: 600;

  box-shadow: var(--sh-brand);

  transition: background-color 0.15s, box-shadow 0.2s, transform 0.2s;
}

.login-button:hover {
  background: var(--c-brand-hover);

  box-shadow: var(--sh-brand-hover);

  transform: translateY(-1px);
}

.login-button:active {
  background: var(--c-brand-active);

  box-shadow: 0 2px 6px rgba(189, 36, 39, 0.28);

  transform: translateY(0);
}

.login-button:focus-visible {
  outline: none;
  box-shadow: 0 0 0 3px rgba(189, 36, 39, 0.3);
}

/* MOBILE */

@media (max-width: 600px) {

  .login-content {
    max-width: 100%;
  }

  .login-content h1 {
    font-size: 22px;
  }
}

/* Slightly larger text on the auth screens: the shared size tokens go up about 1px here and in this page's own pop-ups. */
.login-page {
  --fs-2xs: 12.5px;
  --fs-xs: 13.5px;
  --fs-sm: 15px;
  --fs-md: 16px;
}

@media (max-width: 600px) {
  .login-page {
    --fs-2xs: 11.5px;
    --fs-xs: 12.5px;
    --fs-sm: 14px;
    --fs-md: 15px;
  }
}</style>
