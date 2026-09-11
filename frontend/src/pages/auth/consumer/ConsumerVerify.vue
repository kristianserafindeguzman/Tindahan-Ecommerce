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

      <!-- RIGHT VERIFY PANEL -->
      <div class="login-panel">
        <div class="login-content">

          <template v-if="hasPhone">
            <h1>Verify your account</h1>

            <p class="subtitle">
              We sent a 6-digit verification code to <strong>{{ displayPhone }}</strong>.
            </p>

            <!-- OTP INPUT BOXES -->
            <div class="otp-row">
              <input
                v-for="(digit, index) in otp"
                :key="index"
                :ref="el => { otpRefs[index] = el }"
                v-model="otp[index]"
                type="text"
                inputmode="numeric"
                :autocomplete="index === 0 ? 'one-time-code' : 'off'"
                :aria-label="`Digit ${index + 1} of 6`"
                @focus="$event.target.select()"
                class="otp-box"
                :class="{ 'otp-error': otpError, 'otp-success': otpVerified }"
                :disabled="otpVerified"
                @input="handleOtpInput(index)"
                @keydown="handleOtpKeydown(index, $event)"
                @paste="handleOtpPaste"
              />
            </div>

            <!-- OTP ERROR -->
            <div v-if="otpError" class="error-message">
              {{ otpError }}
            </div>

            <!-- RESEND -->
            <div class="resend-section">
              <span>Didn't receive a code?</span>

              <button
                type="button"
                class="text-button resend-btn"
                :class="{ 'resend-disabled': timer > 0 }"
                :disabled="timer > 0 || otpVerified"
                @click="resendCode"
              >
                {{ timer > 0
                  ? `Resend in ${formattedTimer}`
                  : 'Resend Code'
                }}
              </button>
            </div>

            <!-- VERIFY BUTTON -->
            <q-btn
              label="Verify"
              no-caps
              unelevated
              class="login-button full-width"
              :loading="loading"
              :disable="!otpComplete || otpVerified"
              @click="verifyOtp"
            />
          </template>

          <!-- Opened without the number from sign-up, as happens in a new tab or from a bookmark. -->
          <div v-else class="missing-state">
            <div class="missing-icon">
              <q-icon name="o_sms_failed" size="32px" />
            </div>

            <h1>We don't know which number to verify</h1>

            <p class="subtitle">
              Open this page right after signing up, or sign up again with the same details and we'll text you a new code.
            </p>

            <q-btn
              label="Log in"
              no-caps
              unelevated
              class="login-button full-width"
              @click="router.push('/login')"
            />

            <button type="button" class="text-button secondary-link" @click="router.push('/consumer/register')">
              Create an account
            </button>
          </div>

          <q-separator class="separator" />

          <!-- TERMS -->
          <p class="terms">
            By continuing, you agree to our
            <a href="#" @click.prevent="showTerms = true">Terms and Conditions</a>
            and
            <a href="#" @click.prevent="showPrivacy = true">Privacy Policy</a>.
          </p>

        </div>
      </div>
    </div>

    <!-- LEGAL MODALS -->
    <TermsModal v-model="showTerms" />
    <PrivacyModal v-model="showPrivacy" />

  </q-page>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import TermsModal from '@/components/modals/TermsModal.vue'
import PrivacyModal from '@/components/modals/PrivacyModal.vue'
import { api } from '@/boot/axios'

const router = useRouter()
const route = useRoute()

// Read once, because history.state belongs to this history entry and is not reactive.
const phoneNumber = history.state?.phone_number || ''
const hasPhone = !!phoneNumber

// Phone number passed from consumer registration, masked for privacy.
const displayPhone = computed(() => {
  if (phoneNumber.length >= 10) {
    return phoneNumber.slice(0, 4) + '***' + phoneNumber.slice(-4)
  }
  return phoneNumber || 'your mobile number'
})

// OTP state
const otp = ref(['', '', '', '', '', ''])
const otpRefs = ref([])
const otpError = ref('')
const loading = ref(false)
// Turns the boxes green for a moment once the code is accepted, the same flash as the consumer profile's phone check.
const otpVerified = ref(false)
let verifiedTimer = null

const otpComplete = computed(() => otp.value.every(digit => digit !== ''))

// Legal modals
const showTerms = ref(false)
const showPrivacy = ref(false)

// Countdown timer — 60 seconds
const timer = ref(60)
let interval = null

const formattedTimer = computed(() => {
  const mins = Math.floor(timer.value / 60)
  const secs = timer.value % 60
  return `${mins}:${String(secs).padStart(2, '0')}`
})

const startTimer = () => {
  clearInterval(interval)
  timer.value = 60

  interval = setInterval(() => {
    if (timer.value > 0) {
      timer.value--
    } else {
      clearInterval(interval)
    }
  }, 1000)
}

onMounted(() => {
  // Without a number there is nothing to resend to, so a countdown would only mislead.
  if (!hasPhone) return

  startTimer()

  if (otpRefs.value[0]) {
    otpRefs.value[0].focus()
  }
})

onUnmounted(() => {
  clearInterval(interval)
  clearTimeout(verifiedTimer)
})

// Handle typing in OTP boxes — auto-advance to next
const handleOtpInput = (index) => {
  const digits = otp.value[index].replace(/\D/g, '')

  // An autofilled SMS code lands in the first box as one string, so it is spread across all six.
  if (digits.length >= 4) {
    for (let i = 0; i < 6; i++) otp.value[i] = digits[i] || ''
    otpRefs.value[Math.min(digits.length, 5)]?.focus()
    otpError.value = ''
    return
  }

  // Typing into a filled box keeps only the newest digit.
  otp.value[index] = digits.slice(-1)
  if (!digits) return

  otpError.value = ''

  if (index < 5) {
    otpRefs.value[index + 1]?.focus()
  }
}

// Handle backspace — go to previous box
const handleOtpKeydown = (index, event) => {
  if (event.key === 'Backspace' && !otp.value[index] && index > 0) {
    otpRefs.value[index - 1]?.focus()
  }
}

// Handle paste — distribute across boxes
const handleOtpPaste = (event) => {
  event.preventDefault()

  const pasted = event.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6)

  for (let i = 0; i < 6; i++) {
    otp.value[i] = pasted[i] || ''
  }

  const lastIndex = Math.min(pasted.length, 5)
  otpRefs.value[lastIndex]?.focus()

  otpError.value = ''
}

const verifyOtp = async () => {
  if (loading.value || otpVerified.value) return
  const finalOtp = otp.value.join('')

  const phoneNum = history.state?.phone_number
  const verificationType = history.state?.type || 'registration'

  if (finalOtp.length < 6) {
    otpError.value = 'Please enter the complete 6-digit code.'
    return
  }
  
  if (!phoneNum) {
    otpError.value = 'Missing phone number. Please register again.'
    return
  }

  loading.value = true
  otpError.value = ''

  try {
    await api.post('/otp/verify', {
      phone_number: phoneNum,
      code: finalOtp,
      type: verificationType
    })

    otpVerified.value = true
    // Holds the green boxes briefly before moving on to the success page.
    verifiedTimer = setTimeout(() => router.push({ path: '/consumer/success', state: { phone_number: phoneNumber } }), 450)
  } catch (error) {
    console.error('OTP Verification Error:', error)
    otpError.value = error.response?.data?.message || 'The verification code you entered is incorrect.'
    otp.value = ['', '', '', '', '', '']
    otpRefs.value[0]?.focus()
  } finally {
    loading.value = false
  }
}

const resendCode = async () => {
  if (timer.value > 0 || otpVerified.value) return

  otpError.value = ''
  
  const phoneNum = history.state?.phone_number
  if (!phoneNum) {
    otpError.value = 'Missing phone number. Please register again.'
    return
  }

  try {
    await api.post('/otp/resend', {
      phone_number: phoneNum,
      type: history.state?.type || 'registration'
    })
    otpError.value = ''
    otp.value = ['', '', '', '', '', '']
    otpRefs.value[0]?.focus()
    startTimer()
  } catch (error) {
    otpError.value = error.response?.data?.message || 'Failed to resend code.'
  }
}
</script>

<style scoped>
/* PAGE */

.login-page {
  min-height: 100vh;
  width: 100%;
  box-sizing: border-box;

  display: flex;
  align-items: center;
  justify-content: center;

  padding: 56px clamp(24px, 6vw, 80px);

  background:
    linear-gradient(
      145deg,
      #c02226 0%,
      #9c171b 55%,
      #651012 100%
    );

  font-family: 'Roboto', Arial, sans-serif;
}

/* LOGIN CARD (layout row, no visual chrome of its own) */

.login-card {
  width: 100%;
  height: auto;
  min-height: 0;
  max-width: none;
  margin: 0 auto;
  box-sizing: border-box;

  display: flex;
  flex-wrap: nowrap;
  align-items: center;
  justify-content: center;
  gap: clamp(40px, 8vw, 140px);

  padding: 0;

  background: transparent;

  overflow: visible;
}

/* LEFT BRANDING PANEL */

.branding-panel {
  flex: 0 0 auto;
  box-sizing: border-box;

  display: flex;
  align-items: center;
  justify-content: center;

  padding: 40px;

  background: transparent;
}

.tindahan-logo {
  display: block;

  width: 380px;
  max-width: 100%;
  height: auto;

  object-fit: contain;
}

.tindahan-logo-mobile {
  display: none;
}

/* RIGHT VERIFY PANEL */

.login-panel {
  width: 420px;
  min-height: 520px;
  max-width: 90vw;
  flex: 0 0 auto;
  box-sizing: border-box;

  display: flex;
  align-items: center;
  justify-content: center;

  /* Left/right padding matches ConsumerRegister.vue's login-panel for consistency across auth pages. */
  padding: 24px 45px;

  background: #ffffff;
  border-radius: var(--r-2xl);

  box-shadow:
    0 20px 50px rgba(0, 0, 0, 0.3);
}

.login-content {
  width: 100%;
  max-width: 390px;
}

/* HEADING */

.login-content h1 {
  margin: 0 0 14px;

  font-size: 27px;
  line-height: 1.2;
  font-weight: 700;

  color: var(--c-text);
}

.subtitle {
  margin: 0 0 28px;

  font-size: var(--fs-sm);
  line-height: 1.5;

  color: var(--c-muted);
}

.subtitle strong {
  font-weight: 600;

  color: var(--c-text-2);
}

/* OTP BOXES */

.otp-row {
  display: flex;
  justify-content: center;

  gap: 10px;

  margin-bottom: 20px;
}

.otp-box {
  width: 48px;
  height: 48px;
  padding: 0;

  border: 1px solid var(--c-border);
  border-radius: var(--r-md);

  background: #ffffff;

  font-family: 'Roboto', Arial, sans-serif;
  font-size: 20px;
  font-weight: 600;
  line-height: 1;

  text-align: center;

  color: var(--c-text);

  outline: none;

  transition: border-color 0.15s, box-shadow 0.15s;
}

.otp-box:focus {
  border-color: var(--c-brand);

  box-shadow: 0 0 0 1px rgba(189, 36, 39, 0.1);
}

.otp-box.otp-error {
  border-color: var(--c-danger);
}

/* Brief green flash on the digit boxes before the success page, the same as the consumer profile's phone check. */
.otp-box.otp-success {
  border-color: var(--c-success);

  background: var(--c-success-tint);
  color: var(--c-success);

  transition: border-color 0.15s, background-color 0.2s, color 0.2s;
}

/* ERROR MESSAGE */

.error-message {
  margin-bottom: 14px;
  padding: 10px 14px;

  border-radius: var(--r-sm);

  background: var(--c-danger-tint);
  border: 1px solid var(--c-danger-line);

  font-size: var(--fs-xs);
  line-height: 1.4;

  color: var(--c-danger);

  text-align: center;
}

/* VERIFY BUTTON */

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

.login-button:not(.disabled):hover {
  background: var(--c-brand-hover);

  box-shadow: var(--sh-brand-hover);

  transform: translateY(-1px);
}

.login-button:not(.disabled):active {
  background: var(--c-brand-active);

  box-shadow: 0 2px 6px rgba(189, 36, 39, 0.28);

  transform: translateY(0);
}

.login-button:focus-visible {
  outline: none;
  box-shadow: 0 0 0 3px rgba(189, 36, 39, 0.3);
}

/* Stays brand red for Quasar's .disabled to fade to 60%, matching the profile page's disabled buttons. */
.login-button:disabled,
.login-button.disabled {
  background: var(--c-brand);
}

/* RESEND */

.resend-section {
  margin-bottom: 16px;

  display: flex;
  align-items: center;
  justify-content: center;

  gap: 4px;

  font-size: var(--fs-xs);
}

.resend-section span {
  color: var(--c-muted);
}

.text-button {
  padding: 0;

  border: none;

  background: transparent;

  font-family: 'Roboto', Arial, sans-serif;

  cursor: pointer;
}

/* Padding cancelled by an equal negative margin grows the tap area to 44px without moving anything. */
.resend-btn {
  padding: 14px 0;
  margin: -14px 0;

  font-size: var(--fs-xs);
  font-weight: 600;

  color: var(--c-brand);
}

.resend-btn:hover:not(:disabled) {
  text-decoration: underline;
}

.resend-disabled {
  color: var(--c-muted);

  cursor: default;
}

/* SEPARATOR */

.separator {
  margin: 16px 0;

  background: var(--c-hairline);
}

/* TERMS */

.terms {
  margin: 0;

  text-align: center;

  font-size: var(--fs-2xs);
  line-height: 1.6;

  color: var(--c-muted);
}

/* Vertical padding on an inline link widens its tap area without changing the line height. */
.terms a {
  padding: 16px 0;

  color: var(--c-text-2);

  text-decoration: underline;
}

/* MISSING NUMBER */

.missing-state {
  text-align: center;
}

.missing-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;

  width: 64px;
  height: 64px;
  margin-bottom: 18px;

  border-radius: var(--r-2xl);

  background: var(--c-brand-tint);
  color: var(--c-brand);
}

.secondary-link {
  display: block;

  width: 100%;
  min-height: 44px;
  margin-top: 8px;

  font-size: var(--fs-xs);
  font-weight: 600;

  color: var(--c-brand);
}

/* TABLET */

@media (max-width: 768px) {
  .login-card {
    padding: 0 24px;
  }

  .branding-panel {
    padding: 20px;
  }

  .login-panel {
    width: 380px;

    padding: 32px 35px;
  }

  .tindahan-logo {
    width: 260px;
  }
}

/* MOBILE */

@media (max-width: 600px) {
  .login-page {
    min-height: 100vh;

    align-items: stretch;
    justify-content: flex-start;

    padding: 0;

    background: #ffffff;
  }

  .login-card {
    width: 100%;
    min-height: 100vh;
    height: auto;
    max-width: 100%;

    flex-direction: column;
    align-items: stretch;
    justify-content: flex-start;
    gap: 0;

    padding: 0;

    background: #ffffff;
  }

  .branding-panel {
    width: 100%;
    height: auto;
    flex: none;

    justify-content: center;

    padding: 28px 0 8px;
  }

  .tindahan-logo-desktop {
    display: none;
  }

  .tindahan-logo-mobile {
    display: block;

    width: 110px;
  }

  .login-panel {
    width: 100%;
    min-height: 0;
    max-width: 100%;
    flex: none;

    padding: 20px 24px 24px;

    background: #ffffff;
    border-radius: 0;
    box-shadow: none;
  }

  .login-content {
    max-width: 100%;
  }

  .login-content h1 {
    font-size: 22px;
  }

  .subtitle {
    margin-bottom: 22px;
  }

  /* Still square, just scaled down so all six fit within 16px screen margins on narrow phones. */
  .otp-row {
    gap: 8px;
  }

  .otp-box {
    width: 44px;
    height: 44px;

    font-size: 18px;
  }
}
</style>
