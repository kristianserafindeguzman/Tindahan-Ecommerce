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

      <!-- RIGHT LOGIN PANEL -->
      <div class="login-panel">
        <div class="login-content">

          <h1>Welcome!</h1>

          <p class="subtitle">
            Sign up or login to continue.
          </p>

          <q-form
            ref="loginForm"
            class="login-form"
            @submit.prevent="handleLogin"
          >

            <!-- EMAIL OR MOBILE -->
            <div class="field-group">
              <q-input
                v-model="form.identifier"
                outlined
                dense
                no-error-icon
                hide-bottom-space
                label="Email or Mobile Number"
                class="login-input"
                :rules="[
                  val => !identifierTouched || !!val || 'Email or mobile number is required.',
                  val => !identifierTouched || identifierRule(val)
                ]"
                @blur="identifierTouched = true"
              />
            </div>

            <!-- PASSWORD -->
            <div class="field-group">
              <q-input
                v-model="form.password"
                outlined
                dense
                no-error-icon
                hide-bottom-space
                :type="showPassword ? 'text' : 'password'"
                label="Password"
                class="login-input"
                :rules="[
                  val => !passwordTouched || !!val || 'Password is required.'
                ]"
                @blur="passwordTouched = true"
              >
                <template #append>
                  <q-icon
                    :name="showPassword
                      ? 'o_visibility'
                      : 'o_visibility_off'"
                    class="password-icon cursor-pointer"
                    @click="showPassword = !showPassword"
                  />
                </template>
              </q-input>

              <div class="forgot-container">
                <button
                  type="button"
                  class="text-button forgot-password"
                  @click="handleForgotPassword"
                >
                  Forgot Password?
                </button>
              </div>
            </div>

            <!-- ERROR MESSAGE -->
            <div v-if="loginError" class="error-message">
              {{ loginError }}
            </div>

            <!-- LOGIN BUTTON -->
            <q-btn
              type="submit"
              label="Login"
              no-caps
              unelevated
              class="login-button full-width"
              :loading="loading"
              :disable="!canLogin"
            />

          </q-form>

          <!-- CREATE ACCOUNT -->
          <div class="register-section">
            <span>New to Tindahan?</span>

            <button
              type="button"
              class="text-button create-account"
              @click="showRegistrationOptions = true"
            >
              Create an account
            </button>
          </div>

          <q-separator class="separator" />

          <!-- TERMS -->
          <p class="terms">
            By signing up, you agree to our
            <a href="#" @click.prevent="showTerms = true">
              Terms and Conditions
            </a>
            and
            <br />
            <a href="#" @click.prevent="showPrivacy = true">
              Privacy Policy
            </a>
          </p>

        </div>
      </div>
    </div>

    <!-- REGISTRATION TYPE DIALOG -->
    <q-dialog v-model="showRegistrationOptions">
      <q-card class="registration-dialog">

        <q-card-section class="registration-header">
          <div class="registration-title">
            Create an account
          </div>

          <div class="registration-subtitle">
            Choose how you want to register.
          </div>
        </q-card-section>

        <q-card-section class="registration-buttons">

          <q-btn
            label="Register as Consumer"
            no-caps
            unelevated
            class="login-button full-width"
            @click="goToConsumerRegister"
          />

          <q-btn
            label="Register as Vendor"
            no-caps
            outline
            class="vendor-registration-button full-width"
            @click="goToVendorRegister"
          />

        </q-card-section>

      </q-card>
    </q-dialog>

    <!-- FORGOT PASSWORD FLOW MODALS -->

    <!-- Step 1: Request OTP -->
    <q-dialog v-model="showForgotWarning">
      <q-card class="status-dialog">
        <q-form ref="forgotPhoneForm" @submit.prevent="requestResetOTP">
          <q-card-section class="status-content">
            <div class="status-icon-wrap" style="background: #bd2427;">
              <q-icon name="o_lock_reset" size="36px" color="white" />
            </div>
            <div class="status-title">Reset Password</div>
            <p class="status-message">
              Enter your registered mobile number. We will send an SMS with a 6-digit verification code.
            </p>
            <div class="field-group reset-phone-group">
              <q-input
                v-model="forgotPhone"
                outlined
                dense
                no-error-icon
                hide-bottom-space
                label="Mobile number"
                class="login-input"
                :rules="[
                  val => !forgotPhoneTouched || !!val || 'Mobile number is required.',
                  val => !forgotPhoneTouched || phoneRule(val)
                ]"
                @blur="forgotPhoneTouched = true"
              />
            </div>
            <div v-if="forgotError" class="error-message">{{ forgotError }}</div>
          </q-card-section>
          <q-card-actions class="status-actions" vertical>
            <q-btn type="submit" label="Send Code" no-caps unelevated class="login-button full-width" :loading="forgotLoading" :disable="!canRequestReset" />
            <button type="button" class="text-button cancel-link" @click="showForgotWarning = false">Cancel</button>
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>

    <!-- Step 2: Verify OTP -->
    <q-dialog v-model="showForgotOtp" persistent>
      <q-card class="status-dialog">
        <q-card-section class="status-content">
          <div class="status-icon-wrap" style="background: #bd2427;">
            <q-icon name="o_sms" size="36px" color="white" />
          </div>
          <div class="status-title">Verify Phone Number</div>
          <p class="status-message">
            We sent a 6-digit verification code to <strong>{{ maskedForgotPhone }}</strong>.
          </p>

          <!-- Same boxed OTP pattern as ConsumerVerify.vue -->
          <div class="otp-row">
            <input
              v-for="(digit, index) in forgotOtp"
              :key="index"
              :ref="el => { forgotOtpRefs[index] = el }"
              v-model="forgotOtp[index]"
              type="text"
              inputmode="numeric"
              maxlength="1"
              class="otp-box"
              :class="{ 'otp-error': forgotError }"
              @input="handleForgotOtpInput(index)"
              @keydown="handleForgotOtpKeydown(index, $event)"
              @paste="handleForgotOtpPaste"
            />
          </div>

          <div v-if="forgotError" class="error-message">{{ forgotError }}</div>

          <!-- Same resend/countdown pattern as ConsumerVerify.vue -->
          <div class="resend-section">
            <span>Didn't receive a code?</span>

            <button
              type="button"
              class="text-button resend-btn"
              :class="{ 'resend-disabled': forgotResendTimer > 0 }"
              :disabled="forgotResendTimer > 0"
              @click="resendForgotOtp"
            >
              {{ forgotResendTimer > 0
                ? `Resend in ${formattedForgotResendTimer}`
                : 'Resend Code'
              }}
            </button>
          </div>
        </q-card-section>
        <q-card-actions class="status-actions" vertical>
          <q-btn label="Verify Code" no-caps unelevated class="login-button full-width" :loading="forgotLoading" :disable="!forgotOtpComplete" @click="verifyResetOTP" />
          <button type="button" class="text-button cancel-link" @click="showForgotOtp = false">Cancel</button>
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Step 3: New Password -->
    <q-dialog v-model="showForgotReset" persistent>
      <q-card class="status-dialog">
        <q-form ref="forgotResetForm" @submit.prevent="submitNewPassword">
          <q-card-section class="status-content">
            <div class="status-icon-wrap" style="background: #bd2427;">
              <q-icon name="o_lock_reset" size="36px" color="white" />
            </div>
            <div class="status-title">Create New Password</div>
            <p class="status-message">
              Your new password must be at least 8 characters long.
            </p>

            <div class="field-group">
              <q-input
                v-model="forgotPassword1"
                outlined
                dense
                no-error-icon
                hide-bottom-space
                :type="showNewPassword ? 'text' : 'password'"
                label="New Password"
                class="login-input"
                :rules="[
                  val => !newPasswordTouched || !!val || 'Password is required.',
                  val => !newPasswordTouched || passwordRule(val)
                ]"
                @blur="newPasswordTouched = true"
              >
                <template #append>
                  <q-icon
                    :name="showNewPassword ? 'o_visibility' : 'o_visibility_off'"
                    class="password-icon cursor-pointer"
                    @click="showNewPassword = !showNewPassword"
                  />
                </template>
              </q-input>
            </div>

            <div class="field-group">
              <q-input
                v-model="forgotPassword2"
                outlined
                dense
                no-error-icon
                hide-bottom-space
                :type="showConfirmNewPassword ? 'text' : 'password'"
                label="Confirm New Password"
                class="login-input"
                :error="confirmPasswordMessage?.type === 'error'"
              >
                <template #append>
                  <q-icon
                    :name="showConfirmNewPassword ? 'o_visibility' : 'o_visibility_off'"
                    class="password-icon cursor-pointer"
                    @click="showConfirmNewPassword = !showConfirmNewPassword"
                  />
                </template>
              </q-input>
              <div v-if="confirmPasswordMessage" class="field-message" :class="`field-message-${confirmPasswordMessage.type}`">
                <q-icon v-if="confirmPasswordMessage.type === 'success'" name="o_check_circle" size="12px" />
                {{ confirmPasswordMessage.text }}
              </div>
            </div>

            <div v-if="forgotError" class="error-message">{{ forgotError }}</div>
          </q-card-section>
          <q-card-actions class="status-actions" vertical>
            <q-btn type="submit" label="Reset Password" no-caps unelevated class="login-button full-width" :loading="forgotLoading" :disable="!canSubmitNewPassword" />
            <button type="button" class="text-button cancel-link" @click="showForgotReset = false">Cancel</button>
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>

    <!-- SUCCESS NOTIFICATION DIALOG -->
    <q-dialog v-model="showResetSuccess">
      <q-card class="status-dialog">
        <q-card-section class="status-content">
          <div class="status-icon-wrap" style="background: #22c55e;">
            <q-icon name="o_check" size="36px" color="white" />
          </div>
          <div class="status-title">Password Reset Successful</div>
          <p class="status-message">You can now log in with your new password.</p>
        </q-card-section>
        <q-card-actions class="status-actions" vertical>
          <q-btn label="Login Now" no-caps unelevated class="login-button full-width" @click="showResetSuccess = false" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- SUSPENDED MODAL -->
    <q-dialog v-model="showSuspended" persistent>
      <q-card class="status-dialog">
        <q-card-section class="status-content">
          <div class="status-icon-wrap bg-red-1 text-red-6">
            <q-icon name="o_block" size="36px" />
          </div>
          <div class="modal-title">Account Suspended</div>
          <p class="modal-subtitle">Your account has been temporarily suspended.</p>

          <div class="reason-box bg-red-1">
            <strong>Notice:</strong> {{ suspensionMessage }}
          </div>
        </q-card-section>
        <q-card-actions align="center" class="status-actions">
          <q-btn
            unelevated
            label="Contact Support"
            color="red-6"
            class="full-width-btn"
            @click="showContactSupport = true"
          />
          <q-btn
            flat
            label="Back to Login"
            color="grey-7"
            class="full-width-btn q-mt-sm"
            @click="handleStatusLogout"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- INACTIVE MODAL -->
    <q-dialog v-model="showInactive" persistent>
      <q-card class="status-dialog">
        <q-card-section class="status-content">
          <div class="status-icon-wrap bg-orange-1 text-orange-6">
            <q-icon name="o_warning" size="36px" />
          </div>
          <div class="modal-title">Account Inactive</div>
          <p class="modal-subtitle">Your account is currently inactive.</p>

          <div class="reason-box bg-orange-1">
            <strong>Notice:</strong> {{ inactiveMessage }}
          </div>
        </q-card-section>
        <q-card-actions align="center" class="status-actions">
          <q-btn
            unelevated
            label="Contact Support"
            color="orange-6"
            class="full-width-btn"
            @click="showContactSupport = true"
          />
          <q-btn
            flat
            label="Back to Login"
            color="grey-7"
            class="full-width-btn q-mt-sm"
            @click="handleStatusLogout"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- LEGAL & SUPPORT MODALS -->
    <TermsModal v-model="showTerms" />
    <PrivacyModal v-model="showPrivacy" />
    <ContactSupportModal v-model="showContactSupport" />

  </q-page>
</template>

<script setup>
import { computed, nextTick, onMounted, onUnmounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api } from '@/boot/axios'
import TermsModal from '@/components/modals/TermsModal.vue'
import PrivacyModal from '@/components/modals/PrivacyModal.vue'
import ContactSupportModal from '@/components/modals/ContactSupportModal.vue'

const route = useRoute()
const router = useRouter()

const loginForm = ref(null)
const showPassword = ref(false)
const loading = ref(false)
const loginError = ref('')
const showRegistrationOptions = ref(false)

// The header's guest "Sign up" button links here with ?register=1 to jump straight to the
// registration-choice dialog instead of landing on the plain login form.
onMounted(() => {
  if (route.query.register === '1') {
    showRegistrationOptions.value = true
  }
})

// Gates each field's own rules until it's been touched (blurred once,
// or a submit attempt was made) — Quasar's `lazy-rules` only
// re-validates on the NEXT blur once triggered, not on every
// keystroke in between, which left stale error text on screen after
// the field became valid. This keeps rules permanently reactive
// (default lazy-rules behavior) while suppressing them pre-touch.
const identifierTouched = ref(false)
const passwordTouched = ref(false)

// Vendor status modals (Under Review / Rejected now live on their own pages)
const showSuspended = ref(false)
const showInactive = ref(false)
const suspensionMessage = ref('')
const inactiveMessage = ref('')
const showContactSupport = ref(false)

// Forgot password flow state
const showForgotWarning = ref(false)
const showForgotOtp = ref(false)
const showForgotReset = ref(false)
const showResetSuccess = ref(false)
const forgotPhoneForm = ref(null)
const forgotPhone = ref('')
const forgotPhoneTouched = ref(false)
const forgotOtp = ref(['', '', '', '', '', ''])
const forgotOtpRefs = ref([])
const forgotOtpComplete = computed(() => forgotOtp.value.every(digit => digit !== ''))

// Same 60s resend countdown as ConsumerVerify.vue's OTP screen.
const forgotResendTimer = ref(60)
let forgotResendInterval = null

const formattedForgotResendTimer = computed(() => {
  const mins = Math.floor(forgotResendTimer.value / 60)
  const secs = forgotResendTimer.value % 60
  return `${mins}:${String(secs).padStart(2, '0')}`
})

const startForgotResendTimer = () => {
  clearInterval(forgotResendInterval)
  forgotResendTimer.value = 60

  forgotResendInterval = setInterval(() => {
    if (forgotResendTimer.value > 0) {
      forgotResendTimer.value--
    } else {
      clearInterval(forgotResendInterval)
    }
  }, 1000)
}

onUnmounted(() => {
  clearInterval(forgotResendInterval)
})

const forgotResetForm = ref(null)
const forgotPassword1 = ref('')
const forgotPassword2 = ref('')
const showNewPassword = ref(false)
const showConfirmNewPassword = ref(false)
const newPasswordTouched = ref(false)
const forgotResetToken = ref('')
const forgotLoading = ref(false)
const forgotError = ref('')

// Same rules as ConsumerRegister.vue's phone/password fields.
const phoneRule = val => /^09\d{9}$/.test(val) || 'Mobile number must start with 09 and contain 11 digits.'
const passwordRule = val => val.length >= 8 || 'Minimum 8 characters'
const canRequestReset = computed(() => phoneRule(forgotPhone.value) === true)

// Same masking as ConsumerVerify.vue's displayPhone.
const maskedForgotPhone = computed(() => {
  const phone = forgotPhone.value
  if (!phone) return 'your mobile number'
  if (phone.length >= 10) {
    return phone.slice(0, 4) + '***' + phone.slice(-4)
  }
  return phone
})

// Same live match/mismatch message as ConsumerRegister.vue's Confirm Password field.
const confirmPasswordMessage = computed(() => {
  if (!forgotPassword2.value) return null
  if (forgotPassword2.value !== forgotPassword1.value) return { type: 'error', text: 'Passwords do not match.' }
  return { type: 'success', text: 'Passwords match.' }
})

const canSubmitNewPassword = computed(() =>
  !!forgotPassword1.value && passwordRule(forgotPassword1.value) === true &&
  !!forgotPassword2.value && forgotPassword2.value === forgotPassword1.value
)

// Legal modals
const showTerms = ref(false)
const showPrivacy = ref(false)

const form = reactive({
  identifier: '',
  password: ''
})

const identifierRule = val => {
  const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  const mobilePattern = /^(09\d{9}|\+639\d{9})$/

  return (
    emailPattern.test(val) ||
    mobilePattern.test(val) ||
    'Enter a valid email or mobile number'
  )
}

const canLogin = computed(() => identifierRule(form.identifier) === true && !!form.password)

const handleLogin = async () => {
  identifierTouched.value = true
  passwordTouched.value = true

  const isValid = await loginForm.value.validate()

  if (!isValid) {
    return
  }

  loading.value = true
  loginError.value = ''

  try {
    const response = await api.post('/login', {
      email: form.identifier,
      password: form.password
    })

    const { token, role, user, vendor_status } = response.data

    // Store auth data
    localStorage.setItem('auth_token', token)
    localStorage.setItem('auth_user', JSON.stringify(user))
    localStorage.setItem('auth_role', role)

    if (role === 'Vendor') {
      localStorage.setItem('vendor_status', vendor_status || 'pending')
    }

    // Route based on role
    if (role === 'Admin') {
      router.push('/admin/dashboard')

    } else if (role === 'Consumer') {
      router.push('/consumer/home')

    } else if (role === 'Vendor') {
      router.push('/vendor/dashboard')
    }

  } catch (error) {
    localStorage.removeItem('auth_token')
    localStorage.removeItem('auth_user')
    localStorage.removeItem('auth_role')

    if (error.response && error.response.status === 422) {
      const errors = error.response.data.errors
      loginError.value = errors?.email?.[0] || 'Invalid credentials. Please try again.'
    } else if (error.response && error.response.status === 401) {
      const data = error.response.data
      
      if (data.error_code === 'ACCOUNT_REJECTED') {
        router.push({
          path: '/auth/vendor/rejected',
          query: {
            reason: data.rejection_reason || '',
            rejected_by: data.rejected_by || ''
          }
        })
        return
      } else if (data.error_code === 'ACCOUNT_PENDING') {
        router.push('/auth/vendor/under-review')
        return
      } else {
        loginError.value = data.message || 'Unauthorized.'
      }
    } else if (error.response && error.response.status === 403) {
      if (error.response.data.contact_support) {
        if (error.response.data.account_status === 'inactive') {
          inactiveMessage.value = error.response.data.message
          showInactive.value = true
        } else {
          suspensionMessage.value = error.response.data.message
          showSuspended.value = true
        }
      } else {
        loginError.value = error.response.data.message || 'Account access denied.'
      }
    } else {
      loginError.value = 'Something went wrong. Please try again later.'
    }

  } finally {
    loading.value = false
  }
}

const handleStatusLogout = async () => {
  try {
    await api.post('/logout')
  } catch {
    // Token may already be invalid — proceed
  }

  localStorage.removeItem('auth_token')
  localStorage.removeItem('auth_user')
  localStorage.removeItem('auth_role')

  showSuspended.value = false
  showInactive.value = false
}

const handleForgotPassword = () => {
  forgotError.value = ''
  forgotPhone.value = ''
  forgotPhoneTouched.value = false
  showForgotWarning.value = true
}

const requestResetOTP = async () => {
  forgotPhoneTouched.value = true

  const isValid = await forgotPhoneForm.value.validate()
  if (!isValid) return

  forgotError.value = ''
  forgotLoading.value = true
  try {
    await api.post('/forgot-password', { phone_number: forgotPhone.value })
    showForgotWarning.value = false
    forgotOtp.value = ['', '', '', '', '', '']
    showForgotOtp.value = true
    startForgotResendTimer()
    nextTick(() => forgotOtpRefs.value[0]?.focus())
  } catch (error) {
    forgotError.value = error.response?.data?.message || 'Failed to send OTP.'
  } finally {
    forgotLoading.value = false
  }
}

// Same auto-advance/backspace/paste behavior as ConsumerVerify.vue's OTP boxes.
const handleForgotOtpInput = (index) => {
  const val = forgotOtp.value[index]

  if (val && !/^\d$/.test(val)) {
    forgotOtp.value[index] = ''
    return
  }

  forgotError.value = ''

  if (val && index < 5) {
    forgotOtpRefs.value[index + 1]?.focus()
  }
}

const handleForgotOtpKeydown = (index, event) => {
  if (event.key === 'Backspace' && !forgotOtp.value[index] && index > 0) {
    forgotOtpRefs.value[index - 1]?.focus()
  }
}

const handleForgotOtpPaste = (event) => {
  event.preventDefault()

  const pasted = event.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6)

  for (let i = 0; i < 6; i++) {
    forgotOtp.value[i] = pasted[i] || ''
  }

  const lastIndex = Math.min(pasted.length, 5)
  forgotOtpRefs.value[lastIndex]?.focus()

  forgotError.value = ''
}

// Same resend behavior/endpoint as ConsumerVerify.vue's resendCode.
const resendForgotOtp = async () => {
  if (forgotResendTimer.value > 0) return

  forgotError.value = ''
  try {
    await api.post('/otp/resend', {
      phone_number: forgotPhone.value,
      type: 'password_reset'
    })
    forgotOtp.value = ['', '', '', '', '', '']
    forgotOtpRefs.value[0]?.focus()
    startForgotResendTimer()
  } catch (error) {
    forgotError.value = error.response?.data?.message || 'Failed to resend code.'
  }
}

const verifyResetOTP = async () => {
  if (!forgotOtpComplete.value) {
    forgotError.value = 'Please enter the complete 6-digit code.'
    return
  }
  forgotError.value = ''
  forgotLoading.value = true
  try {
    const res = await api.post('/otp/verify', {
      phone_number: forgotPhone.value,
      code: forgotOtp.value.join(''),
      type: 'password_reset'
    })
    forgotResetToken.value = res.data.reset_token
    showForgotOtp.value = false
    forgotPassword1.value = ''
    forgotPassword2.value = ''
    newPasswordTouched.value = false
    showNewPassword.value = false
    showConfirmNewPassword.value = false
    showForgotReset.value = true
  } catch (error) {
    forgotError.value = error.response?.data?.message || 'Invalid OTP code.'
    forgotOtp.value = ['', '', '', '', '', '']
    forgotOtpRefs.value[0]?.focus()
  } finally {
    forgotLoading.value = false
  }
}

const submitNewPassword = async () => {
  newPasswordTouched.value = true

  const isValid = await forgotResetForm.value.validate()

  // Confirm Password isn't part of the form's own :rules, so it needs its own guard here.
  if (!isValid || !canSubmitNewPassword.value) {
    return
  }

  forgotError.value = ''
  forgotLoading.value = true
  try {
    await api.post('/forgot-password/reset', {
      phone_number: forgotPhone.value,
      reset_token: forgotResetToken.value,
      password: forgotPassword1.value,
      password_confirmation: forgotPassword2.value
    })
    showForgotReset.value = false
    showResetSuccess.value = true
  } catch (error) {
    forgotError.value = error.response?.data?.message || 'Failed to reset password.'
  } finally {
    forgotLoading.value = false
  }
}

const goToConsumerRegister = () => {
  showRegistrationOptions.value = false
  router.push('/consumer/register')
}

const goToVendorRegister = () => {
  showRegistrationOptions.value = false
  router.push('/vendor/register')
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

  padding: 0;

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
  height: 100vh;
  min-height: 100vh;
  max-width: none;
  margin: 0;
  box-sizing: border-box;

  display: flex;
  flex-wrap: nowrap;
  align-items: center;
  justify-content: center;
  gap: clamp(40px, 8vw, 140px);

  padding: 0 clamp(24px, 6vw, 80px);

  background: transparent;

  overflow: hidden;
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

/* RIGHT LOGIN PANEL */

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

/* HEADING */

.login-content h1 {
  margin: 0 0 6px;

  font-size: 27px;
  line-height: 1.2;
  font-weight: 700;

  color: #111111;
}

.subtitle {
  margin: 0 0 30px;

  font-size: 13px;

  color: #8992a2;
}

/* FORM */

.login-form {
  width: 100%;
}

.field-group {
  margin-bottom: 16px;
}

/* Exceeds .status-message's 8px margin-bottom since adjacent block margins collapse rather than sum. */
.reset-phone-group {
  margin-top: 14px;
}

/* hide-bottom-space removes this area entirely until a field message actually has something to show. */
.login-input :deep(.q-field__bottom) {
  padding-top: 6px;
  padding-bottom: 0;
}

.login-input :deep(.q-field__control) {
  height: 44px;

  border-radius: 8px;
}

.login-input :deep(.q-field__native),
.login-input :deep(.q-field__input) {
  font-family: 'Roboto', Arial, sans-serif;

  font-size: 13px;

  color: #333333;

  padding-left: 6px;
}

.login-input :deep(.q-field__label) {
  font-size: 13px;

  color: #8992a2;
}

.login-input :deep(.q-field__append) {
  height: 44px;
}

.password-icon {
  font-size: 18px;

  color: #777777;
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
}

/* FORGOT PASSWORD */

.forgot-container {
  display: flex;
  justify-content: flex-end;

  margin-top: 8px;
}

.text-button {
  padding: 0;

  border: none;

  background: transparent;

  font-family: 'Roboto', Arial, sans-serif;

  cursor: pointer;
}

.forgot-password {
  font-size: 10px;

  color: #333333;
}

.forgot-password:hover {
  text-decoration: underline;
}

/* LOGIN BUTTON */

.login-button {
  height: 48px;

  /* field-group's own 16px margin-bottom is the only spacing above this button. */
  margin-top: 0;

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

/* REGISTER */

.register-section {
  margin-top: 16px;

  display: flex;
  align-items: center;
  justify-content: center;

  gap: 4px;

  font-size: 11px;
}

.register-section span {
  color: #8e97a6;
}

.create-account {
  font-size: 11px;

  color: #222222;
}

.create-account:hover {
  text-decoration: underline;
}

/* SEPARATOR */

.separator {
  margin: 28px 0 18px;
}

/* TERMS */

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

/* REGISTRATION DIALOG */

.registration-dialog {
  width: 400px;
  max-width: 90vw;

  border-radius: 8px;

  font-family: 'Roboto', Arial, sans-serif;
}

/* Same padding rhythm as .status-content across the other LoginPage.vue dialogs. */
.registration-header {
  padding: 28px 28px 4px;
}

.registration-title {
  font-size: 19px;
  font-weight: 700;

  color: #222222;
}

.registration-subtitle {
  margin-top: 6px;

  font-size: 13px;

  color: #666666;
}

/* Same padding rhythm as .status-actions. */
.registration-buttons {
  display: flex;
  flex-direction: column;

  gap: 12px;

  padding: 20px 28px 28px;
}

/* Outline counterpart to .login-button; Quasar's `outline` prop draws the border via `color` alone. */
.vendor-registration-button {
  height: 48px;

  border-radius: 6px;

  background: #ffffff;
  color: #bd2427;

  font-family: 'Roboto', Arial, sans-serif;

  font-size: 13px;
  font-weight: 500;

  transition: background-color 0.15s, border-color 0.15s;
}

.vendor-registration-button:hover {
  background: #fdecec;
}

.vendor-registration-button:active {
  background: #f8d7d8;
}

.vendor-registration-button:focus-visible {
  outline: none;
  box-shadow: 0 0 0 3px rgba(189, 36, 39, 0.3);
}

/* VENDOR STATUS DIALOGS */

.status-dialog {
  width: 400px;
  max-width: 90vw;

  border-radius: 8px;

  font-family: 'Roboto', Arial, sans-serif;
}

.status-content {
  text-align: center;

  padding: 30px 28px 10px;
}

.status-icon-wrap {
  display: inline-flex;
  align-items: center;
  justify-content: center;

  width: 70px;
  height: 70px;

  border-radius: 50%;

  margin-bottom: 18px;
}

.status-title {
  font-size: 19px;
  font-weight: 700;

  color: #222222;

  margin-bottom: 10px;
}

.status-message {
  font-size: 13px;
  line-height: 1.6;

  color: #666666;

  margin: 0 0 8px;
}

.status-actions {
  padding: 0 28px 24px;
}

/* OTP BOXES — same pattern as ConsumerVerify.vue */

.otp-row {
  display: flex;
  justify-content: center;

  gap: 10px;

  margin-bottom: 8px;
}

.otp-box {
  width: 44px;
  height: 44px;
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

/* RESEND — same pattern as ConsumerVerify.vue */

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

/* Confirm Password's live match/mismatch message — same look as ConsumerRegister.vue's. */
.field-message {
  margin-top: 6px;

  font-size: 12px;
  line-height: 1.4;

  color: #dc2626;
}

.field-message-success {
  display: flex;
  align-items: center;

  gap: 3px;

  color: #16a34a;
  font-weight: 600;
}

/* Matches .forgot-password / .create-account's plain-text link treatment. */
.cancel-link {
  display: block;

  width: 100%;
  margin-top: 12px;
  padding: 6px 0;

  font-size: 13px;

  text-align: center;

  color: #666666;
}

.cancel-link:hover {
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
    width: 360px;

    padding: 45px 35px;
  }

  .tindahan-logo {
    width: 220px;
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

  .login-button {
    height: 48px;
  }
}
</style>