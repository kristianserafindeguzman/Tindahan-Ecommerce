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
            We've sent a 6-digit verification code to
            <strong>{{ displayEmail }}</strong>.
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

    <!-- SUCCESS DIALOG -->
    <q-dialog v-model="showSuccessDialog" persistent>
      <q-card class="success-dialog">

        <q-card-section class="success-content">
          <div class="success-icon-wrap">
            <q-icon name="check" size="36px" color="white" />
          </div>

          <div class="success-title">Verification Successful</div>

          <p class="success-message">
            Your account has been verified. You can now log in to start
            exploring local sari-sari stores.
          </p>
        </q-card-section>

        <q-card-actions class="success-actions">
          <q-btn
            label="Back to Login"
            no-caps
            unelevated
            class="success-btn primary-btn full-width"
            @click="goToLogin"
          />
        </q-card-actions>

      </q-card>
    </q-dialog>

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

const router = useRouter()
const route = useRoute()

// The email passed from consumer registration
const displayEmail = computed(() => route.query.email || 'your email')

// OTP state
const otp = ref(['', '', '', '', '', ''])
const otpRefs = ref([])
const otpError = ref('')
const loading = ref(false)

// Success dialog
const showSuccessDialog = ref(false)

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

// Mock OTP verification — correct code is 123456
const verifyOtp = () => {
  const code = otp.value.join('')

  if (code.length < 6) {
    otpError.value = 'Please enter the complete 6-digit code.'
    return
  }

  loading.value = true

  // Simulate a brief network delay
  setTimeout(() => {
    if (code === '123456') {
      // Success — show dialog
      showSuccessDialog.value = true
    } else {
      otpError.value = 'Invalid verification code. Please try again.'

      // Clear the OTP boxes
      otp.value = ['', '', '', '', '', '']
      otpRefs.value[0]?.focus()
    }

    loading.value = false
  }, 800)
}

const resendCode = () => {
  otpError.value = ''
  otp.value = ['', '', '', '', '', '']
  otpRefs.value[0]?.focus()
  startTimer()
}

const goToLogin = () => {
  showSuccessDialog.value = false
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
   RIGHT VERIFY PANEL
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
   SUCCESS DIALOG
========================= */

.success-dialog {
  width: 400px;
  max-width: 90vw;

  border-radius: 10px;

  font-family: 'Roboto', Arial, sans-serif;
}

.success-content {
  text-align: center;

  padding: 30px 28px 10px;
}

.success-icon-wrap {
  display: inline-flex;
  align-items: center;
  justify-content: center;

  width: 70px;
  height: 70px;

  border-radius: 50%;

  background: #22c55e;

  margin-bottom: 18px;
}

.success-title {
  font-size: 19px;
  font-weight: 700;

  color: #222222;

  margin-bottom: 10px;
}

.success-message {
  font-size: 13px;
  line-height: 1.6;

  color: #666666;

  margin: 0;
}

.success-actions {
  padding: 14px 28px 24px;
}

.success-btn {
  height: 42px;

  border-radius: 6px;

  font-size: 13px;
  font-weight: 500;
}

.primary-btn {
  background: #bd2427;
  color: #ffffff;
}

.primary-btn:hover {
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
