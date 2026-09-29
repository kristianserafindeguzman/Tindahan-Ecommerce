<template>
  <q-page class="auth-shell">
    <AuthLanguageSwitcher />

    <!-- The brand side: logo, headline and short pitch at the left on desktop. On tablets and
         phones this box dissolves (display: contents) so the copyright can drop below the card. -->
    <aside class="auth-hero">
      <div class="auth-hero-copy">
        <!-- Back to the storefront: "/" sends a guest to the consumer home page, and anyone
             already signed in (a vendor on a status screen) to their own home instead. -->
        <router-link to="/" class="auth-hero-logo" :aria-label="t('Go to home page')">
          <img src="@/assets/tindahan-logo.png" alt="Tindahan" class="auth-hero-logo-img" />
        </router-link>
        <!-- Each word is kept whole, so the headline only wraps between words and never splits
             "sari-sari" at its hyphen. -->
        <h2 class="auth-hero-title">
          <template v-for="(word, index) in t('Your neighborhood sari-sari store, now online.').split(' ')" :key="index">{{ index ? ' ' : '' }}<span class="auth-word">{{ word }}</span></template>
        </h2>
        <p class="auth-hero-text">
          {{ t('Browse stores near you, order ahead, and pick up when it\'s ready, all while helping local store owners grow.') }}
        </p>
      </div>

      <p class="auth-hero-foot">© {{ year }} {{ t('Tindahan App. All rights reserved.') }}</p>
    </aside>

    <!-- The page's own form, in a raised card. -->
    <main class="auth-main">
      <div class="auth-card" :class="{ 'auth-card--wide': wide }">
        <slot />
      </div>
    </main>
  </q-page>
</template>

<script setup>
import AuthLanguageSwitcher from '@/components/consumer/AuthLanguageSwitcher.vue'
import { useConsumerLanguage } from '@/composables/useConsumerLanguage'

// Shared frame for the login, sign-up, verification and vendor status screens.
defineProps({
  // Room for the vendor registration wizard, which lays fields out two to a row.
  wide: { type: Boolean, default: false }
})

const { t } = useConsumerLanguage()
const year = new Date().getFullYear()
</script>

<style scoped>
.auth-shell {
  position: relative;
  isolation: isolate;

  display: grid;
  grid-template-columns: minmax(0, 1.25fr) minmax(0, 1fr);
  align-items: center;
  gap: clamp(24px, 4vw, 72px);

  min-height: 100vh;
  /* On very wide screens the side padding grows, so the text and the card stay together in a
     centred 1320px band instead of drifting to opposite edges. */
  padding: 0 max(clamp(24px, 5vw, 88px), calc((100% - 1320px) / 2));
  box-sizing: border-box;

  font-family: 'Roboto', Arial, sans-serif;
}

/* BACKGROUND — two fixed layers, so they fill the screen and stay put while a long form scrolls
   (background-attachment: fixed is ignored by iOS Safari; a fixed layer isn't).
   ::before is a deep brand-red gradient with a soft light behind the card and a dark vignette
   at the edges; ::after is the photo, kept faint and blended into the red. The photo is only
   1024px wide, so keeping it faint also hides its upscaling on large screens. */
.auth-shell::before,
.auth-shell::after {
  content: '';

  position: fixed;
  inset: 0;
  pointer-events: none;
}

.auth-shell::before {
  z-index: -2;

  background:
    radial-gradient(ellipse 120% 90% at 50% 40%, transparent 55%, rgba(20, 2, 3, 0.55) 100%),
    radial-gradient(ellipse 55% 65% at 76% 48%, rgba(255, 110, 100, 0.22), transparent 70%),
    linear-gradient(150deg, #9e1a1f 0%, #6e1014 50%, #300607 100%);
}

.auth-shell::after {
  z-index: -1;

  background: url('@/assets/sari-sari-store.jpg') center / cover no-repeat;

  opacity: 0.14;
  mix-blend-mode: luminosity;
}

/* HERO (desktop) */

/* Pinned to the top of its row so it stays in view beside a form taller than the screen; the
   logo, headline and pitch sit together in the middle, the copyright at the bottom. */
.auth-hero {
  position: sticky;
  top: 0;
  align-self: start;

  display: flex;
  flex-direction: column;
  justify-content: center;

  height: 100vh;
  padding: 72px 0 64px;
  box-sizing: border-box;

  color: #ffffff;
}

.auth-hero-copy {
  max-width: 660px;
}

/* tindahan-logo.png carries transparent padding (about 5.5% of its width on the left and 12% of
   it below the artwork), so the margins cancel it: the mark lines up with the headline's left
   edge and sits a steady 22px above it. */
.auth-hero-logo {
  --logo-w: clamp(190px, 16vw, 230px);

  display: block;

  width: var(--logo-w);
  margin: 0 0 calc(22px - var(--logo-w) * 0.12) calc(var(--logo-w) * -0.055);

  border-radius: 12px;

  cursor: pointer;

  transition: transform 0.2s ease, opacity 0.2s ease;
}

.auth-hero-logo:hover {
  opacity: 0.92;

  transform: translateY(-2px);
}

.auth-hero-logo:focus-visible {
  outline: 2px solid rgba(255, 255, 255, 0.85);
  outline-offset: 4px;
}

.auth-hero-logo-img {
  display: block;

  width: 100%;
  height: auto;

  filter: drop-shadow(0 8px 24px rgba(0, 0, 0, 0.35));
}

@media (prefers-reduced-motion: reduce) {
  .auth-hero-logo,
  .auth-hero-logo:hover {
    transition: none;
    transform: none;
  }
}

.auth-hero-title {
  margin: 0 0 20px;

  font-family: 'Poppins', 'Roboto', Arial, sans-serif;
  font-size: clamp(40px, 4vw, 60px);
  font-weight: 800;
  line-height: 1.08;
  letter-spacing: -0.015em;

  text-shadow: 0 4px 28px rgba(0, 0, 0, 0.35);
}

.auth-word {
  white-space: nowrap;
}

.auth-hero-text {
  margin: 0;
  max-width: 580px;

  font-size: clamp(18px, 1.4vw, 21px);
  line-height: 1.6;

  color: rgba(255, 255, 255, 0.92);
}

.auth-hero-foot {
  position: absolute;
  bottom: 32px;
  left: 0;

  margin: 0;

  font-size: 13px;

  color: rgba(255, 255, 255, 0.65);
}

/* CARD */

.auth-main {
  display: flex;
  justify-content: center;

  min-width: 0;
  padding: 88px 0 48px;
}

.auth-card {
  width: 100%;
  max-width: 460px;
  padding: 40px 40px 32px;
  box-sizing: border-box;

  border-radius: 26px;

  /* A faint warm wash instead of flat white, brightest where the heading sits. */
  background:
    radial-gradient(ellipse 90% 60% at 50% 0%, #ffffff 0%, rgba(255, 255, 255, 0) 70%),
    linear-gradient(180deg, #fffdfc 0%, #fbf1ef 100%);

  /* A warm lift off the red, with no outline of its own. */
  box-shadow:
    0 14px 34px rgba(30, 3, 5, 0.3),
    0 34px 80px rgba(30, 3, 5, 0.4);
}

.auth-card--wide {
  max-width: 660px;
}

/* The login and consumer pages cap their form at 390px; inside the card it fills the card's
   width instead, so the card never shows an empty strip down its right side. */
.auth-card :deep(.login-content) {
  max-width: none;
}

/* Browser autofill paints saved fields light blue, and only behind the text; an inset white
   shadow keeps an autofilled field looking like every other field. */
.auth-card :deep(input:-webkit-autofill),
.auth-card :deep(input:-webkit-autofill:hover),
.auth-card :deep(input:-webkit-autofill:focus) {
  -webkit-box-shadow: 0 0 0 1000px #ffffff inset;
  -webkit-text-fill-color: var(--c-text-2);
  caret-color: var(--c-text-2);
  transition: background-color 9999s ease-out 0s;
}

/* The vendor status screens wrap their content in a q-card of their own; let the tint show through. */
.auth-card :deep(.q-card) {
  background: transparent;
}

/* TEXT FIELD DEPTH — each field sits slightly raised on the card. Only the resting state is
   touched: Quasar draws the outline and focus ring itself, and the OTP boxes keep their own
   focus ring. */
.auth-card :deep(.q-field--outlined .q-field__control) {
  background: #ffffff;

  box-shadow:
    0 1px 2px rgba(74, 11, 13, 0.05),
    0 4px 12px rgba(74, 11, 13, 0.07);

  transition: box-shadow 0.2s;
}

.auth-card :deep(.q-field--outlined.q-field--focused .q-field__control) {
  box-shadow:
    0 1px 2px rgba(74, 11, 13, 0.05),
    0 8px 20px rgba(189, 36, 39, 0.14);
}

.auth-card :deep(.otp-box:not(:focus)) {
  box-shadow:
    0 1px 2px rgba(74, 11, 13, 0.05),
    0 4px 12px rgba(74, 11, 13, 0.07);
}

/* TABLET + PHONE — one compact centred column: logo, headline, the card, then the copyright.
   Kept short so the form starts high on the screen. */
@media (max-width: 1023px) {
  .auth-shell {
    grid-template-columns: minmax(0, 1fr);
    align-items: start;
    justify-items: center;
    gap: 0;

    padding: 24px 20px 24px;

    text-align: center;
  }

  .auth-shell::before {
    background:
      radial-gradient(ellipse 140% 80% at 50% 30%, transparent 50%, rgba(20, 2, 3, 0.5) 100%),
      radial-gradient(ellipse 90% 40% at 50% 62%, rgba(255, 110, 100, 0.2), transparent 70%),
      linear-gradient(170deg, #9e1a1f 0%, #6e1014 55%, #300607 100%);
  }

  .auth-hero {
    display: contents;
  }

  .auth-hero-copy {
    order: 1;

    width: 100%;
    max-width: 560px;
  }

  .auth-main {
    order: 2;

    width: 100%;
    padding: 20px 0 0;
  }

  .auth-hero-foot {
    order: 3;
    position: static;

    margin-top: 18px;
  }

  .auth-hero-logo {
    --logo-w: 170px;

    margin: 0 auto calc(10px - var(--logo-w) * 0.12);
  }

  .auth-hero-title {
    margin: 0;

    font-size: clamp(24px, 4.6vw, 34px);
  }

  .auth-hero-text {
    display: none;
  }

  /* Sized like the desktop card, which suits a single-column form on a tablet too; vendor
     registration keeps its wider card for its two-per-row fields. */
  .auth-card {
    max-width: 480px;
    margin: 0 auto;

    text-align: left;
  }

  .auth-card--wide {
    max-width: 640px;
  }
}

/* PHONE — the logo shares the top row with the language button, and everything above the form
   is kept compact so the card starts high on the screen. */
@media (max-width: 600px) {
  .auth-shell {
    padding: 14px 14px 20px;
  }

  /* Sized to the screen, so it stays clear of the language button on narrow phones. */
  .auth-hero-logo {
    --logo-w: min(170px, 44vw);

    margin-bottom: calc(10px - var(--logo-w) * 0.12);
  }

  /* Balanced lines, so the two lines of the headline come out close in length. */
  .auth-hero-title {
    font-size: clamp(21px, 6.1vw, 26px);
    line-height: 1.15;
    text-wrap: balance;
  }

  .auth-main {
    padding-top: 18px;
  }

  .auth-card {
    padding: 26px 18px 20px;

    border-radius: 20px;

    box-shadow:
      0 12px 30px rgba(30, 3, 5, 0.32),
      0 28px 60px rgba(30, 3, 5, 0.38);
  }

  .auth-hero-foot {
    margin-top: 14px;

    font-size: 11.5px;
  }
}
</style>
