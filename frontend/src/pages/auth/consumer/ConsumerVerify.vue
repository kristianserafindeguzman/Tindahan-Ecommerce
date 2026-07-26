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

          <h1>Verify your account</h1>

          <p class="subtitle">
            We've sent a 6-digit verification code via SMS to
            <strong>{{ displayPhone }}</strong>.
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
              maxlength="1"
              class="otp-box"
              :class="{ 'otp-error': otpError }"
              @input="handleOtpInput(index)"
              @keydown="handleOtpKeydown(index, $event)"
              @paste="handleOtpPaste"
            />
          </div>

          <!-- OTP ERROR -->
          <div v-if="otpError" class="error-message">
            {{ otpError }}
          </div>

          <!-- VERIFY BUTTON -->
          <q-btn
            label="Verify"
            no-caps
            unelevated
            class="login-button full-width"
            :loading="loading"
            @click="verifyOtp"
          />

          <!-- RESEND -->
          <div class="resend-section">
            <span>Didn't receive a code?</span>

            <button
              type="button"
              class="text-button resend-btn"
              :class="{ 'resend-disabled': timer > 0 }"
              :disabled="timer > 0"
              @click="resendCode"
            >
              {{ timer > 0
                ? `Resend in ${formattedTimer}`
                : 'Resend Code'
              }}
            </button>
          </div>

          <q-separator class="separator" />

          <!-- TERMS -->
          <p class="terms">
            By continuing, you agree to our
            <a href="#" @click.prevent="showTerms = true">
              Terms and Conditions
            </a>
            and
            <a href="#" @click.prevent="showPrivacy = true">
              Privacy Policy
            </a>.
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

// The phone number passed from consumer registration, masked for privacy
const displayPhone = computed(() => {
  const phone = route.query.phone_number
  if (!phone) return 'your mobile number'
  if (phone.length >= 10) {
    // e.g. 0917***4567
    return phone.slice(0, 4) + '***' + phone.slice(-4)
  }
  return phone
})

// OTP state
const otp = ref(['', '', '', '', '', ''])
const otpRefs = ref([])
const otpError = ref('')
const loading = ref(false)

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
  startTimer()

  // Auto-focus the first OTP box
  if (otpRefs.value[0]) {
    otpRefs.value[0].focus()
  }
})

onUnmounted(() => {
  clearInterval(interval)
})

// Handle typing in OTP boxes — auto-advance to next
const handleOtpInput = (index) => {
  const val = otp.value[index]

  // Only allow digits
  if (val && !/^\d$/.test(val)) {
    otp.value[index] = ''
    return
  }

  // Clear error on new input
  otpError.value = ''

  // Auto-advance to next box
  if (val && index < 5) {
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

  // Focus the last filled box
  const lastIndex = Math.min(pasted.length, 5)
  otpRefs.value[lastIndex]?.focus()

  otpError.value = ''
}

// Real OTP verification
const verifyOtp = async () => {
  // Safely parse the code whether it's an array (6 boxes) or a string (1 box)
  const parsedCode = Array.isArray(otp.value) ? otp.value.join('') : String(otp.value || '')

  if (parsedCode.length < 6) {
    otpError.value = 'Please enter the complete 6-digit code.'
    return
  }

  loading.value = true
  otpError.value = ''

  try {
    await api.post('/otp/verify', {
      phone_number: route.query.phone_number,
      code: parsedCode,
      type: 'registration'
    })
    
    // Success — go to the full-page success screen
    router.push('/consumer/success')
  } catch (error) {
    console.error('OTP Verification Error:', error)
    otpError.value = error.response?.data?.message || 'An unexpected error occurred'
    // Clear the OTP boxes appropriately
    if (Array.isArray(otp.value)) {
      otp.value = ['', '', '', '', '', '']
    } else {
      otp.value = ''
    }
    otpRefs.value[0]?.focus()
  } finally {
    loading.value = false
  }
}

const resendCode = async () => {
  if (timer.value > 0) return

  try {
    await api.post('/otp/resend', {
      phone_number: route.query.phone_number,
      type: 'registration'
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
/* =========================
   PAGE
========================= */

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

/* =========================
   LOGIN CARD (layout row, no visual chrome of its own)
========================= */

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

/* =========================
   LEFT BRANDING PANEL
========================= */

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

/* =========================
   RIGHT VERIFY PANEL
========================= */

.login-panel {
  width: 420px;
  max-width: 90vw;
  flex: 0 0 auto;
  box-sizing: border-box;

  display: flex;
  align-items: center;
  justify-content: center;

  padding: 45px 45px;

  background: #ffffff;
  border-radius: 4px;

  box-shadow:
    0 20px 50px rgba(0, 0, 0, 0.3);
}

.login-content {
  width: 100%;
  max-width: 390px;
}

/* =========================
   HEADING
========================= */

.login-content h1 {
  margin: 0 0 6px;

  font-size: 27px;
  line-height: 1.2;
  font-weight: 700;

  color: #111111;
}

.subtitle {
  margin: 0 0 28px;

  font-size: 13px;
  line-height: 1.5;

  color: #8992a2;
}

.subtitle strong {
  color: #333333;
}

/* =========================
   OTP BOXES
========================= */

.otp-row {
  display: flex;
  justify-content: center;

  gap: 10px;

  margin-bottom: 20px;
}

.otp-box {
  width: 48px;
  height: 52px;

  border: 1.5px solid #d6d6da;
  border-radius: 8px;

  background: #ffffff;

  font-family: 'Roboto', Arial, sans-serif;
  font-size: 20px;
  font-weight: 600;

  text-align: center;

  color: #222222;

  outline: none;

  transition: border-color 0.15s;
}

.otp-box:focus {
  border-color: #bd2427;

  box-shadow: 0 0 0 2px rgba(189, 36, 39, 0.12);
}

.otp-box.otp-error {
  border-color: #ef4444;
}

/* =========================
   ERROR MESSAGE
========================= */

.error-message {
  margin-bottom: 14px;
  padding: 10px 14px;

  border-radius: 6px;

  background: #fef2f2;
  border: 1px solid #fecaca;

  font-size: 12px;
  line-height: 1.4;

  color: #b91c1c;

  text-align: center;
}

/* =========================
   VERIFY BUTTON
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
   RESEND
========================= */

.resend-section {
  margin-top: 18px;

  display: flex;
  align-items: center;
  justify-content: center;

  gap: 4px;

  font-size: 12px;
}

.resend-section span {
  color: #8e97a6;
}

.text-button {
  padding: 0;

  border: none;

  background: transparent;

  font-family: 'Roboto', Arial, sans-serif;

  cursor: pointer;
}

.resend-btn {
  font-size: 12px;
  font-weight: 600;

  color: #bd2427;
}

.resend-btn:hover:not(:disabled) {
  text-decoration: underline;
}

.resend-disabled {
  color: #aaaaaa;

  cursor: default;
}

/* =========================
   SEPARATOR
========================= */

.separator {
  margin: 28px 0 18px;
}

/* =========================
   TERMS
========================= */

.terms {
  margin: 0;

  text-align: center;

  font-size: 10px;
  line-height: 1.5;

  color: #8e97a6;
}

.terms a {
  color: #333333;

  text-decoration: underline;
}

/* =========================
   TABLET
========================= */

@media (max-width: 768px) {
  .login-card {
    padding: 0 24px;
  }

  .branding-panel {
    padding: 20px;
  }

  .login-panel {
    width: 380px;

    padding: 40px 35px;
  }

  .tindahan-logo {
    width: 260px;
  }
}

/* =========================
   MOBILE
========================= */

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
    max-width: 100%;
    flex: none;

    padding: 20px 24px 32px;

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

  .otp-box {
    width: 42px;
    height: 48px;

    font-size: 18px;
  }
}
</style>
