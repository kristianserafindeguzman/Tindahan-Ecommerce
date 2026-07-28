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
                label="Email or Mobile Number"
                class="login-input"
                :rules="[
                  val => !!val || 'Email or mobile number is required',
                  identifierRule
                ]"
              />
            </div>

            <!-- PASSWORD -->
            <div class="field-group">
              <q-input
                v-model="form.password"
                outlined
                dense
                :type="showPassword ? 'text' : 'password'"
                label="Password"
                class="login-input"
                :rules="[
                  val => !!val || 'Password is required'
                ]"
              >
                <template #append>
                  <q-icon
                    :name="showPassword
                      ? 'visibility'
                      : 'visibility_off'"
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

        <q-card-section>
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
            class="registration-button"
            @click="goToConsumerRegister"
          />

          <q-btn
            label="Register as Vendor"
            no-caps
            outline
            class="vendor-registration-button"
            @click="goToVendorRegister"
          />

        </q-card-section>

      </q-card>
    </q-dialog>

    <!-- FORGOT PASSWORD FLOW MODALS -->

    <!-- Step 1: Request OTP -->
    <q-dialog v-model="showForgotWarning">
      <q-card class="status-dialog">
        <q-card-section class="status-content">
          <div class="status-icon-wrap status-pending" style="background: #3b82f6;">
            <q-icon name="lock_reset" size="36px" color="white" />
          </div>
          <div class="status-title">Reset Password</div>
          <p class="status-message q-mb-md">
            Enter your registered mobile number. We will send an SMS with a 6-digit verification code.
          </p>
          <q-input
            v-model="forgotPhone"
            outlined
            dense
            placeholder="09..."
            label="Mobile Number"
          />
          <div v-if="forgotError" class="error-message q-mt-sm">{{ forgotError }}</div>
        </q-card-section>
        <q-card-actions class="status-actions" vertical>
          <q-btn label="Send Code" no-caps unelevated class="status-btn primary-btn" :loading="forgotLoading" @click="requestResetOTP" />
          <q-btn label="Cancel" no-caps flat class="status-btn flat-btn" @click="showForgotWarning = false" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Step 2: Verify OTP -->
    <q-dialog v-model="showForgotOtp" persistent>
      <q-card class="status-dialog">
        <q-card-section class="status-content">
          <div class="status-title">Verify Phone Number</div>
          <p class="status-message q-mb-md">
            Enter the 6-digit code sent to {{ forgotPhone }}
          </p>
          <q-input
            v-model="forgotOtpCode"
            outlined
            dense
            placeholder="123456"
            label="Verification Code"
            mask="######"
          />
          <div v-if="forgotError" class="error-message q-mt-sm">{{ forgotError }}</div>
        </q-card-section>
        <q-card-actions class="status-actions" vertical>
          <q-btn label="Verify Code" no-caps unelevated class="status-btn primary-btn" :loading="forgotLoading" @click="verifyResetOTP" />
          <q-btn label="Cancel" no-caps flat class="status-btn flat-btn" @click="showForgotOtp = false" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Step 3: New Password -->
    <q-dialog v-model="showForgotReset" persistent>
      <q-card class="status-dialog">
        <q-card-section class="status-content">
          <div class="status-title">Create New Password</div>
          <p class="status-message q-mb-md">
            Your new password must be at least 8 characters long.
          </p>
          <q-input
            v-model="forgotPassword1"
            outlined
            dense
            type="password"
            label="New Password"
            class="q-mb-sm"
          />
          <q-input
            v-model="forgotPassword2"
            outlined
            dense
            type="password"
            label="Confirm New Password"
          />
          <div v-if="forgotError" class="error-message q-mt-sm">{{ forgotError }}</div>
        </q-card-section>
        <q-card-actions class="status-actions" vertical>
          <q-btn label="Reset Password" no-caps unelevated class="status-btn primary-btn" :loading="forgotLoading" @click="submitNewPassword" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- SUCCESS NOTIFICATION DIALOG -->
    <q-dialog v-model="showResetSuccess">
      <q-card class="status-dialog">
        <q-card-section class="status-content">
          <div class="status-icon-wrap" style="background: #22c55e;">
            <q-icon name="check" size="36px" color="white" />
          </div>
          <div class="status-title">Password Reset Successful</div>
          <p class="status-message">You can now log in with your new password.</p>
        </q-card-section>
        <q-card-actions class="status-actions" vertical>
          <q-btn label="Login Now" no-caps unelevated class="status-btn primary-btn" @click="showResetSuccess = false" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- SUSPENDED MODAL -->
    <q-dialog v-model="showSuspended" persistent>
      <q-card class="status-dialog">
        <q-card-section class="status-content">
          <div class="status-icon-wrap bg-red-1 text-red-6">
            <q-icon name="block" size="36px" />
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
            <q-icon name="warning" size="36px" />
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
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '@/boot/axios'
import TermsModal from '@/components/modals/TermsModal.vue'
import PrivacyModal from '@/components/modals/PrivacyModal.vue'
import ContactSupportModal from '@/components/modals/ContactSupportModal.vue'

const router = useRouter()

const loginForm = ref(null)
const showPassword = ref(false)
const loading = ref(false)
const loginError = ref('')
const showRegistrationOptions = ref(false)

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
const forgotPhone = ref('')
const forgotOtpCode = ref('')
const forgotPassword1 = ref('')
const forgotPassword2 = ref('')
const forgotResetToken = ref('')
const forgotLoading = ref(false)
const forgotError = ref('')

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

const handleLogin = async () => {
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

    const { token, role, user, vendor_status, rejection_reason } = response.data

    // Store auth data
    localStorage.setItem('auth_token', token)
    localStorage.setItem('auth_user', JSON.stringify(user))
    localStorage.setItem('auth_role', role)

    // Route based on role
    if (role === 'Admin') {
      router.push('/admin/dashboard')

    } else if (role === 'Consumer') {
      router.push('/consumer/home')

    } else if (role === 'Vendor') {
      // Check vendor approval status
      if (vendor_status === 'approved') {
        router.push('/vendor/dashboard')

      } else if (vendor_status === 'rejected') {
        router.push({
          path: '/auth/vendor/rejected',
          query: { reason: rejection_reason || '' }
        })

      } else {
        // pending or any other status
        router.push('/auth/vendor/under-review')
      }
    }

  } catch (error) {
    if (error.response && error.response.status === 422) {
      const errors = error.response.data.errors
      loginError.value = errors?.email?.[0] || 'Invalid credentials. Please try again.'
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
  showForgotWarning.value = true
}

const requestResetOTP = async () => {
  if (!forgotPhone.value) {
    forgotError.value = 'Please enter your mobile number.'
    return
  }
  forgotError.value = ''
  forgotLoading.value = true
  try {
    await api.post('/forgot-password', { phone_number: forgotPhone.value })
    showForgotWarning.value = false
    forgotOtpCode.value = ''
    showForgotOtp.value = true
  } catch (error) {
    forgotError.value = error.response?.data?.message || 'Failed to send OTP.'
  } finally {
    forgotLoading.value = false
  }
}

const verifyResetOTP = async () => {
  if (!forgotOtpCode.value || forgotOtpCode.value.length < 6) {
    forgotError.value = 'Please enter the 6-digit code.'
    return
  }
  forgotError.value = ''
  forgotLoading.value = true
  try {
    const res = await api.post('/otp/verify', {
      phone_number: forgotPhone.value,
      code: forgotOtpCode.value,
      type: 'password_reset'
    })
    forgotResetToken.value = res.data.reset_token
    showForgotOtp.value = false
    forgotPassword1.value = ''
    forgotPassword2.value = ''
    showForgotReset.value = true
  } catch (error) {
    forgotError.value = error.response?.data?.message || 'Invalid OTP code.'
  } finally {
    forgotLoading.value = false
  }
}

const submitNewPassword = async () => {
  if (!forgotPassword1.value || forgotPassword1.value.length < 8) {
    forgotError.value = 'Password must be at least 8 characters.'
    return
  }
  if (forgotPassword1.value !== forgotPassword2.value) {
    forgotError.value = 'Passwords do not match.'
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

/* =========================
   LOGIN CARD (layout row, no visual chrome of its own)
========================= */

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
   RIGHT LOGIN PANEL
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
  margin: 0 0 30px;

  font-size: 13px;

  color: #8992a2;
}

/* =========================
   FORM
========================= */

.login-form {
  width: 100%;
}

.field-group {
  margin-bottom: 2px;
}

.login-input :deep(.q-field__bottom) {
  padding-bottom: 8px;
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
}

/* =========================
   FORGOT PASSWORD
========================= */

.forgot-container {
  display: flex;
  justify-content: flex-end;

  margin-top: 7px;
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

/* =========================
   LOGIN BUTTON
========================= */

.login-button {
  height: 48px;

  margin-top: 2px;

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
   REGISTER
========================= */

.register-section {
  margin-top: 17px;

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
   REGISTRATION DIALOG
========================= */

.registration-dialog {
  width: 380px;
  max-width: 90vw;

  padding: 12px;

  border-radius: 10px;

  font-family: 'Roboto', Arial, sans-serif;
}

.registration-title {
  font-size: 20px;
  font-weight: 700;

  color: #222222;
}

.registration-subtitle {
  margin-top: 5px;

  font-size: 13px;

  color: #777777;
}

.registration-buttons {
  display: flex;
  flex-direction: column;

  gap: 12px;
}

.registration-button {
  height: 45px;

  background: #bd2427;
  color: #ffffff;
}

.vendor-registration-button {
  height: 45px;

  color: #bd2427;
}

/* =========================
   VENDOR STATUS DIALOGS
========================= */

.status-dialog {
  width: 400px;
  max-width: 90vw;

  border-radius: 10px;

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

.status-pending {
  background: #f59e0b;
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

  margin: 0;
}

.status-actions {
  padding: 14px 28px 24px;
}

.status-btn {
  width: 100%;

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

.flat-btn {
  color: #666666;
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
    width: 360px;

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