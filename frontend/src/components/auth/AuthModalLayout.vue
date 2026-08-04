<template>
  <q-page class="auth-modal-page">
    <div class="auth-modal-card">

      <!-- CLOSE BUTTON -->
      <button
        type="button"
        class="close-button"
        @click="$emit('close')"
      >
        <q-icon name="close" />
      </button>

      <!-- LOGO -->
      <div class="auth-modal-logo">
        <img
          src="@/assets/tindahan-mobile.png"
          alt="Tindahan Logo"
          class="tindahan-logo"
        />
      </div>

      <!-- SLOT CONTENT -->
      <div class="auth-modal-body">
        <slot />
      </div>

    </div>
  </q-page>
</template>

<script setup>
defineEmits(['close'])
</script>

<style scoped>
/* =========================
   PAGE
========================= */

.auth-modal-page {
  min-height: 100vh;

  display: flex;
  align-items: center;
  justify-content: center;

  padding: 40px 20px;

  background: #f4f4f4;

  font-family: 'Roboto', Arial, sans-serif;
}

/* =========================
   CARD
========================= */

.auth-modal-card {
  position: relative;

  width: 100%;
  max-width: 440px;

  padding: 40px 40px 35px;

  background: #ffffff;

  box-shadow:
    0 12px 35px rgba(0, 0, 0, 0.12);

  border-radius: 4px;
}

/* =========================
   CLOSE BUTTON
========================= */

.close-button {
  position: absolute;
  top: 14px;
  right: 14px;

  display: flex;
  align-items: center;
  justify-content: center;

  width: 32px;
  height: 32px;

  border: none;
  border-radius: 50%;

  background: transparent;

  color: #888888;

  font-size: 20px;

  cursor: pointer;
}

.close-button:hover {
  background: #f0f0f0;

  color: #333333;
}

/* =========================
   LOGO
========================= */

.auth-modal-logo {
  text-align: center;

  margin-bottom: 20px;
}

.tindahan-logo {
  width: 120px;
  height: auto;

  object-fit: contain;
}

/* =========================
   BODY
========================= */

.auth-modal-body {
  width: 100%;
}

/* =========================
   MOBILE
========================= */

@media (max-width: 600px) {
  .auth-modal-page {
    padding: 0;

    background: #ffffff;
  }

  .auth-modal-card {
    min-height: 100vh;

    box-shadow: none;

    padding: 30px 25px 40px;

    border-radius: 0;
  }
}
</style>
