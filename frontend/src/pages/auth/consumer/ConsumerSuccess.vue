<template>
  <q-page class="login-page">
    <div class="login-card">

      <!-- LEFT BRANDING PANEL -->
      <div class="branding-panel">
        <img
          src="@/assets/tindahan-logo.png"
          alt="Tindahan Logo"
          class="tindahan-logo tindahan-logo-desktop"
        />
        <img
          src="@/assets/tindahan-mobile.png"
          alt="Tindahan Logo"
          class="tindahan-logo tindahan-logo-mobile"
        />
      </div>

      <!-- RIGHT SUCCESS PANEL -->
      <div class="login-panel">
        <div class="login-content">

          <div class="success-wrapper">
            <div class="success-icon-wrap">
              <q-icon name="check" size="42px" color="white" />
            </div>

            <h1>Verification Successful</h1>

            <p class="subtitle">
              Your account has been verified. You can now log in to start
              exploring local sari-sari stores.
            </p>

            <q-btn
              label="Back to Login"
              no-caps
              unelevated
              class="login-button full-width"
              @click="goToLogin"
            />
          </div>

        </div>
      </div>
    </div>
  </q-page>
</template>

<script setup>
import { useRouter } from 'vue-router'

const router = useRouter()

const goToLogin = () => {
  router.push('/login')
}
</script>

<style scoped>
/* =========================
   PAGE
========================= */

.login-page {
  min-height: 100vh;

  display: flex;
  align-items: center;
  justify-content: center;

  padding: 40px 20px;

  background: #f4f4f4;

  font-family: 'Roboto', Arial, sans-serif;
}

/* =========================
   LOGIN CARD
========================= */

.login-card {
  width: 100%;
  max-width: 850px;
  min-height: 520px;

  display: flex;

  background: #ffffff;

  overflow: hidden;

  box-shadow:
    0 12px 35px rgba(0, 0, 0, 0.12);
}

/* =========================
   LEFT BRANDING PANEL
========================= */

.branding-panel {
  width: 42%;

  display: flex;
  align-items: center;
  justify-content: center;

  padding: 40px;

  background:
    linear-gradient(
      145deg,
      #c02226 0%,
      #9c171b 55%,
      #651012 100%
    );
}

.tindahan-logo {
  display: block;

  width: 260px;
  max-width: 100%;
  height: auto;

  object-fit: contain;
}

.tindahan-logo-mobile {
  display: none;
}

/* =========================
   RIGHT SUCCESS PANEL
========================= */

.login-panel {
  width: 58%;

  display: flex;
  align-items: center;
  justify-content: center;

  padding: 55px 50px;
}

.login-content {
  width: 100%;
  max-width: 390px;
}

/* =========================
   SUCCESS CONTENT
========================= */

.success-wrapper {
  text-align: center;
}

.success-icon-wrap {
  display: inline-flex;
  align-items: center;
  justify-content: center;

  width: 80px;
  height: 80px;

  border-radius: 50%;

  background: #22c55e;

  margin-bottom: 22px;
}

.login-content h1 {
  margin: 0 0 10px;

  font-size: 24px;
  line-height: 1.2;
  font-weight: 700;

  color: #111111;
}

.subtitle {
  margin: 0 0 28px;

  font-size: 13px;
  line-height: 1.6;

  color: #8992a2;
}

/* =========================
   BUTTON
========================= */

.login-button {
  height: 48px;

  border-radius: 6px;

  background: #bd2427;
  color: #ffffff;

  font-family: 'Roboto', Arial, sans-serif;

  font-size: 13px;
  font-weight: 500;
}

.login-button:hover {
  background: #a91e21;
}

/* =========================
   TABLET
========================= */

@media (max-width: 768px) {
  .login-card {
    max-width: 700px;
  }

  .branding-panel {
    width: 38%;

    padding: 25px;
  }

  .login-panel {
    width: 62%;

    padding: 45px 35px;
  }

  .tindahan-logo {
    width: 220px;
  }
}

/* =========================
   MOBILE
========================= */

@media (max-width: 600px) {
  .login-page {
    padding: 0;

    background: #ffffff;
  }

  .login-card {
    min-height: 100vh;

    display: block;

    box-shadow: none;
  }

  .branding-panel {
    width: 100%;
    height: auto;

    justify-content: center;

    padding: 30px 25px 5px;

    background: none;
  }

  .tindahan-logo-desktop {
    display: none;
  }

  .tindahan-logo-mobile {
    display: block;

    width: 130px;
  }

  .login-panel {
    width: 100%;

    padding: 15px 25px 40px;
  }

  .login-content {
    max-width: 100%;
  }

  .login-content h1 {
    font-size: 22px;
  }
}
</style>
