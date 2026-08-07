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
            <strong>{{ displayPhone }}</strong>. Enter the code below to continue.
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
            :disable="!otpComplete"
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

// Phone number passed from consumer registration, masked for privacy.
const displayPhone = computed(() => {
  const phone = history.state?.phone_number
  if (!phone) return 'your mobile number'
  if (phone.length >= 10) {
    return phone.slice(0, 4) + '***' + phone.slice(-4)
  }
  return phone
})

// OTP state
const otp = ref(['', '', '', '', '', ''])
const otpRefs = ref([])
const otpError = ref('')
const loading = ref(false)

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
  startTimer()

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

  if (val && !/^\d$/.test(val)) {
    otp.value[index] = ''
    return
  }

  otpError.value = ''

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

  const lastIndex = Math.min(pasted.length, 5)
  otpRefs.value[lastIndex]?.focus()

  otpError.value = ''
}

const verifyOtp = async () => {
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

    router.push('/consumer/success')
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
  if (timer.value > 0) return

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

  padding: 24px;

  background: #ffffff;
  border-radius: 4px;

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

  color: #111111;
}

.subtitle {
  margin: 0 0 28px;

  font-size: 13px;
  line-height: 1.5;

  color: #8992a2;
}

.subtitle strong {
  font-weight: 600;

  color: #333333;
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

  border: 1px solid #d6d6da;
  border-radius: 8px;

  background: #ffffff;

  font-family: 'Roboto', Arial, sans-serif;
  font-size: 20px;
  font-weight: 600;
  line-height: 1;

  text-align: center;

  color: #222222;

  outline: none;

  transition: border-color 0.15s, box-shadow 0.15s;
}

.otp-box:focus {
  border-color: #bd2427;

  box-shadow: 0 0 0 1px rgba(189, 36, 39, 0.1);
}

.otp-box.otp-error {
  border-color: #ef4444;
}

/* ERROR MESSAGE */

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

/* VERIFY BUTTON */

.login-button {
  height: 48px;

  border-radius: 6px;

  background: #bd2427;
  color: #ffffff;

  font-family: 'Roboto', Arial, sans-serif;

  font-size: 13px;
  font-weight: 500;

  box-shadow: 0 2px 8px rgba(189, 36, 39, 0.25);

  transition: background-color 0.15s, box-shadow 0.2s, transform 0.2s;
}

.login-button:hover {
  background: #a91e21;

  box-shadow: 0 6px 16px rgba(189, 36, 39, 0.32);

  transform: translateY(-1px);
}

.login-button:active {
  background: #8f1a1c;

  box-shadow: 0 2px 6px rgba(189, 36, 39, 0.28);

  transform: translateY(0);
}

.login-button:focus-visible {
  outline: none;
  box-shadow: 0 0 0 3px rgba(189, 36, 39, 0.3);
}

.login-button:disabled,
.login-button.disabled {
  background: #bd2427;
  opacity: 0.45;
}

/* RESEND */

.resend-section {
  margin-top: 16px;

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

/* SEPARATOR */

.separator {
  margin: 16px 0;

  background: #eeeeee;
}

/* TERMS */

.terms {
  margin: 0;

  text-align: center;

  font-size: 13px;
  line-height: 1.5;

  color: #8e97a6;
}

.terms a {
  color: #333333;

  text-decoration: underline;
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

    padding: 32px 24px;
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

    padding: 20px 16px 24px;

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
