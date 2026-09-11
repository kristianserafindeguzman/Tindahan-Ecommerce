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

          <h1>Welcome back!</h1>

          <p class="subtitle">
            Log in to your Tindahan account.
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
                autocomplete="username"
                autocapitalize="none"
                spellcheck="false"
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
                ref="passwordInput"
                :type="showPassword ? 'text' : 'password'"
                label="Password"
                autocomplete="current-password"
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
              label="Log in"
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
            By continuing, you agree to our
            <a href="#" @click.prevent="showTerms = true">Terms and Conditions</a>
            and
            <br />
            <a href="#" @click.prevent="showPrivacy = true">Privacy Policy</a>.
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
            <div class="status-icon-wrap status-icon-brand">
              <q-icon name="o_lock_reset" size="32px" />
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
                type="tel"
                autocomplete="tel"
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
            <q-btn outline no-caps color="grey-7" label="Cancel" class="cancel-outline full-width" @click="showForgotWarning = false" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>

    <!-- Step 2: Verify OTP -->
    <q-dialog v-model="showForgotOtp" persistent>
      <q-card class="status-dialog">
        <q-card-section class="status-content">
          <div class="status-icon-wrap status-icon-brand">
            <q-icon name="o_sms" size="32px" />
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
              :autocomplete="index === 0 ? 'one-time-code' : 'off'"
              :aria-label="`Digit ${index + 1} of 6`"
              @focus="$event.target.select()"
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
          <q-btn outline no-caps color="grey-7" label="Cancel" class="cancel-outline full-width" @click="showForgotOtp = false" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Step 3: New Password -->
    <q-dialog v-model="showForgotReset" persistent>
      <q-card class="status-dialog">
        <q-form ref="forgotResetForm" @submit.prevent="submitNewPassword">
          <q-card-section class="status-content">
            <div class="status-icon-wrap status-icon-brand">
              <q-icon name="o_lock_reset" size="32px" />
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
                autocomplete="new-password"
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
              <div v-if="newPasswordStrong" class="field-message field-message-success">
                <q-icon name="o_check_circle" size="12px" />
                Strong password.
              </div>
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
                autocomplete="new-password"
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
            <q-btn outline no-caps color="grey-7" label="Cancel" class="cancel-outline full-width" @click="showForgotReset = false" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>

    <!-- SUCCESS NOTIFICATION DIALOG -->
    <q-dialog v-model="showResetSuccess">
      <q-card class="status-dialog">
        <q-card-section class="status-content">
          <div class="status-icon-wrap status-icon-success">
            <q-icon name="o_check" size="32px" />
          </div>
          <div class="status-title">Password Reset Successful</div>
          <p class="status-message">You can now log in with your new password.</p>
        </q-card-section>
        <q-card-actions class="status-actions" vertical>
          <q-btn label="Log in now" no-caps unelevated class="login-button full-width" @click="finishPasswordReset" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- SUSPENDED MODAL -->
    <q-dialog v-model="showSuspended" persistent>
      <q-card class="status-dialog">
        <q-card-section class="status-content">
          <div class="status-icon-wrap status-icon-danger">
            <q-icon name="o_block" size="32px" />
          </div>
          <div class="status-title">Account Suspended</div>
          <p class="status-message">Your account has been temporarily suspended.</p>

          <div class="notice-box notice-box-red">
            <q-icon name="o_info" size="16px" />
            <p><strong>Notice:</strong> {{ suspensionMessage }}</p>
          </div>
        </q-card-section>
        <q-card-actions class="status-actions" vertical>
          <q-btn unelevated no-caps label="Contact Support" class="login-button full-width" @click="showContactSupport = true" />
          <q-btn outline no-caps color="grey-7" label="Back to Login" class="cancel-outline full-width" @click="handleStatusLogout" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- INACTIVE MODAL: an admin set this account inactive, so the only way back is through support. -->
    <q-dialog v-model="showInactive" persistent>
      <q-card class="status-dialog">
        <q-card-section class="status-content">
          <div class="status-icon-wrap status-icon-danger">
            <q-icon name="o_person_off" size="32px" />
          </div>
          <div class="status-title">Account Inactive</div>
          <p class="status-message">Your account is inactive. Please contact support to reactivate it.</p>

          <div v-if="inactiveNotice" class="notice-box notice-box-red">
            <q-icon name="o_info" size="16px" />
            <p><strong>Notice:</strong> {{ inactiveNotice }}</p>
          </div>
        </q-card-section>
        <q-card-actions class="status-actions" vertical>
          <q-btn unelevated no-caps label="Contact Support" class="login-button full-width" @click="showContactSupport = true" />
          <q-btn outline no-caps color="grey-7" label="Back to Login" class="cancel-outline full-width" @click="showInactive = false" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- UNVERIFIED SIGN-UP: shown for a pending account, one that signed up but never verified its mobile number. -->
    <q-dialog v-model="showUnverified" persistent>
      <q-card class="status-dialog">
        <q-form @submit.prevent="sendVerificationCode">
          <q-card-section class="status-content">
            <div class="status-icon-wrap status-icon-brand">
              <q-icon name="o_sms" size="32px" />
            </div>
            <div class="status-title">Verify your mobile number</div>
            <p class="status-message">
              Your account isn't active yet because your mobile number hasn't been verified. We'll text you a new 6-digit code.
            </p>
            <div class="field-group reset-phone-group">
              <q-input
                v-model="unverifiedPhone"
                outlined
                dense
                no-error-icon
                hide-bottom-space
                label="Mobile number"
                type="tel"
                autocomplete="tel"
                class="login-input"
                :rules="[val => !unverifiedTouched || phoneRule(val)]"
                @blur="unverifiedTouched = true"
              />
            </div>
            <div v-if="unverifiedError" class="error-message">{{ unverifiedError }}</div>
          </q-card-section>
          <q-card-actions class="status-actions" vertical>
            <q-btn type="submit" label="Send Code" no-caps unelevated class="login-button full-width" :loading="unverifiedLoading" :disable="phoneRule(unverifiedPhone) !== true" />
            <q-btn outline no-caps color="grey-7" label="Back to Login" class="cancel-outline full-width" @click="showUnverified = false" />
            <button type="button" class="text-button cancel-link cancel-link-quiet" @click="showContactSupport = true">Contact Support</button>
          </q-card-actions>
        </q-form>
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

// The header's guest Sign up button links here with ?register=1 to open the registration-choice dialog straight away.
const passwordInput = ref(null)

onMounted(() => {
  if (route.query.register === '1') {
    showRegistrationOptions.value = true
  }

  // Arriving from a finished sign-up, the number is already known, so only the password is left to type.
  const identifier = history.state?.identifier
  if (identifier) {
    form.identifier = identifier
    nextTick(() => passwordInput.value?.focus())
  }
})

// Gates each field's rules until it has been touched, because Quasar's lazy-rules only re-validates on the next blur and left stale errors on screen after a field became valid.
const identifierTouched = ref(false)
const passwordTouched = ref(false)

// Vendor status modals (Under Review / Rejected now live on their own pages)
const showSuspended = ref(false)
const suspensionMessage = ref('')
const showContactSupport = ref(false)
const showInactive = ref(false)
const inactiveNotice = ref('')

// A pending sign-up never verified its mobile number, so login offers to send a fresh code.
const showUnverified = ref(false)
const unverifiedPhone = ref('')
const unverifiedTouched = ref(false)
const unverifiedError = ref('')
const unverifiedLoading = ref(false)

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

// Shown once the new password passes its rule, the same positive state as the sign-up and profile password fields.
const newPasswordStrong = computed(() => !!forgotPassword1.value && passwordRule(forgotPassword1.value) === true)

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
        const status = error.response.data.account_status

        // Only a pending sign-up is offered a new code, since an inactive account was deactivated by an admin.
        if (status === 'pending') {
          unverifiedPhone.value = toLocalMobile(form.identifier)
          unverifiedTouched.value = false
          unverifiedError.value = ''
          showUnverified.value = true
        } else if (status === 'inactive') {
          inactiveNotice.value = error.response.data.notice || ''
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
}

// Numbers are stored in the 09 form, so a +63 identifier is converted and an email leaves the field blank.
const toLocalMobile = (val) => {
  if (/^09\d{9}$/.test(val)) return val
  if (/^\+639\d{9}$/.test(val)) return '0' + val.slice(3)
  return ''
}

const sendVerificationCode = async () => {
  if (phoneRule(unverifiedPhone.value) !== true) return

  unverifiedError.value = ''
  unverifiedLoading.value = true
  try {
    await api.post('/otp/resend', { phone_number: unverifiedPhone.value, type: 'registration' })
    showUnverified.value = false
    router.push({ path: '/verification', state: { phone_number: unverifiedPhone.value, type: 'registration', role: 'Consumer' } })
  } catch (error) {
    unverifiedError.value = error.response?.data?.message || 'We could not send a code to that number.'
  } finally {
    unverifiedLoading.value = false
  }
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
  const digits = forgotOtp.value[index].replace(/\D/g, '')

  // An autofilled SMS code lands in the first box as one string, so it is spread across all six.
  if (digits.length >= 4) {
    for (let i = 0; i < 6; i++) forgotOtp.value[i] = digits[i] || ''
    forgotOtpRefs.value[Math.min(digits.length, 5)]?.focus()
    forgotError.value = ''
    return
  }

  // Typing into a filled box keeps only the newest digit.
  forgotOtp.value[index] = digits.slice(-1)
  if (!digits) return

  forgotError.value = ''

  if (index < 5) {
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

const finishPasswordReset = () => {
  showResetSuccess.value = false
  form.identifier = forgotPhone.value
  nextTick(() => passwordInput.value?.focus())
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
  margin: 0 0 6px;

  font-size: 27px;
  line-height: 1.2;
  font-weight: 700;

  color: var(--c-text);
}

.subtitle {
  margin: 0 0 30px;

  font-size: var(--fs-sm);

  color: var(--c-muted);
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
  height: 48px;

  border-radius: var(--r-md);
}

.login-input :deep(.q-field__native),
.login-input :deep(.q-field__input) {
  font-family: 'Roboto', Arial, sans-serif;

  font-size: 14px;

  color: var(--c-text-2);

  padding-left: 6px;
}

/* Touch screens keep 16px, because iOS zooms the whole page into any field whose text is smaller than that. */
@media (pointer: coarse) {
  .login-input :deep(.q-field__native),
  .login-input :deep(.q-field__input) {
    font-size: 16px;
  }
}

.login-input :deep(.q-field__label) {
  font-size: var(--fs-sm);

  color: var(--c-muted);
}

.login-input :deep(.q-field__append) {
  height: 48px;
}

.password-icon {
  font-size: 18px;

  color: var(--c-subtle);
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

/* Padding cancelled by an equal negative margin grows the tap area to 44px without moving anything. */
.forgot-password {
  padding: 14px 0;
  margin: -14px 0;

  font-size: var(--fs-xs);
  font-weight: 500;

  color: var(--c-brand);
}

.forgot-password:hover {
  text-decoration: underline;
}

/* LOGIN BUTTON */

.login-button {
  height: 48px;

  /* field-group's own 16px margin-bottom is the only spacing above this button. */
  margin-top: 0;

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

/* REGISTER */

.register-section {
  margin-top: 16px;

  display: flex;
  align-items: center;
  justify-content: center;

  gap: 4px;

  font-size: var(--fs-xs);
}

.register-section span {
  color: var(--c-muted);
}

.create-account {
  padding: 14px 0;
  margin: -14px 0;

  font-size: var(--fs-xs);
  font-weight: 600;

  color: var(--c-brand);
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

/* REGISTRATION DIALOG */

.registration-dialog {
  width: 400px;
  max-width: 90vw;

  border-radius: var(--r-xl);

  font-family: 'Roboto', Arial, sans-serif;
}

/* Same padding rhythm as .status-content across the other LoginPage.vue dialogs. */
.registration-header {
  padding: 28px 28px 4px;
}

.registration-title {
  font-size: 19px;
  font-weight: 700;

  color: var(--c-text);
}

.registration-subtitle {
  margin-top: 6px;

  font-size: var(--fs-sm);

  color: var(--c-text-3);
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

  border-radius: var(--r-sm);

  background: #ffffff;
  color: var(--c-brand);

  font-family: 'Roboto', Arial, sans-serif;

  font-size: var(--fs-sm);
  font-weight: 600;

  transition: background-color 0.15s, border-color 0.15s;
}

.vendor-registration-button:hover {
  background: var(--c-brand-tint);
}

.vendor-registration-button:active {
  background: var(--c-brand-tint-2);
}

.vendor-registration-button:focus-visible {
  outline: none;
  box-shadow: 0 0 0 3px rgba(189, 36, 39, 0.3);
}

/* VENDOR STATUS DIALOGS */

.status-dialog {
  width: 400px;
  max-width: 90vw;

  border-radius: var(--r-xl);

  font-family: 'Roboto', Arial, sans-serif;
}

.status-content {
  text-align: center;

  padding: 30px 28px 10px;
}

/* Tinted tiles, the same icon language as the notification and status icons on the consumer pages. */
.status-icon-wrap {
  display: inline-flex;
  align-items: center;
  justify-content: center;

  width: 64px;
  height: 64px;

  border-radius: var(--r-2xl);

  margin-bottom: 18px;
}

.status-icon-brand {
  background: var(--c-brand-tint);
  color: var(--c-brand);
}

.status-icon-success {
  background: var(--c-success-tint);
  color: var(--c-success);
}

.status-icon-danger {
  background: var(--c-danger-tint);
  color: var(--c-danger);
}

.status-title {
  font-size: 19px;
  font-weight: 700;

  color: var(--c-text);

  margin-bottom: 10px;
}

.status-message {
  font-size: var(--fs-sm);
  line-height: 1.6;

  color: var(--c-text-3);

  margin: 0 0 8px;
}

.status-actions {
  padding: 0 28px 24px;
}

/* Account status notice — used by the Suspended/Inactive dialogs' "Notice: ..." box. */
.notice-box {
  display: flex;
  align-items: flex-start;
  gap: 8px;

  margin: 16px 0 6px;
  padding: 12px 14px;

  border-radius: var(--r-md);

  text-align: left;
}

.notice-box .q-icon {
  flex-shrink: 0;
  margin-top: 2px;
}

.notice-box p {
  margin: 0;

  font-size: 12.5px;
  line-height: 1.6;

  /* The backend sends the admin attribution and the reason as two lines, so the line break is kept instead of collapsing them into one run-on sentence. */
  white-space: pre-line;
}

.notice-box-red {
  border: 1px solid var(--c-danger-line);
  background: var(--c-danger-tint);
  color: var(--c-danger);
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

/* RESEND — same pattern as ConsumerVerify.vue */

.resend-section {
  margin-top: 16px;

  display: flex;
  align-items: center;
  justify-content: center;

  gap: 4px;

  font-size: var(--fs-xs);
}

.resend-section span {
  color: var(--c-muted);
}

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

/* Confirm Password's live match/mismatch message — same look as ConsumerRegister.vue's. */
.field-message {
  margin-top: 6px;

  font-size: var(--fs-xs);
  line-height: 1.4;

  color: var(--c-danger);
}

.field-message-success {
  display: flex;
  align-items: center;

  gap: 3px;

  color: var(--c-success);
  font-weight: 600;
}

/* Matches .forgot-password / .create-account's plain-text link treatment. */
.cancel-link {
  display: block;

  width: 100%;
  min-height: 44px;
  margin-top: 4px;
  padding: 0;

  font-size: var(--fs-sm);

  text-align: center;

  color: var(--c-text-3);
}

.cancel-link-quiet {
  margin-top: 0;

  color: var(--c-muted);
}

.cancel-link:hover {
  text-decoration: underline;
}

/* Cancel and Back to Login, outlined in grey like the profile dialogs' Cancel so they read as buttons under the red one. */
.status-actions .cancel-outline {
  height: 48px;
  min-height: 48px;
  margin-top: 10px;

  border-radius: var(--r-sm);

  font-family: 'Roboto', Arial, sans-serif;
  font-size: var(--fs-sm);

  transition: background-color 0.15s;
}

.status-actions .cancel-outline:hover {
  background: var(--c-surface);
}

.status-actions .cancel-outline:focus-visible {
  box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.12);
}

/* Contact Support stays a quiet link right under Back to Login rather than a third stacked button. */
.status-actions .cancel-outline + .cancel-link-quiet {
  margin-top: 6px;
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
