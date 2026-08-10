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

      <!-- RIGHT REGISTER PANEL -->
      <div class="login-panel">
        <div class="login-content">

          <h1>Sign up</h1>

          <p class="subtitle">
            Create an account to get started.
          </p>

          <q-form
            ref="registerForm"
            class="login-form"
            @submit.prevent="handleRegister"
          >

            <!-- FIRST / LAST NAME -->
            <div class="name-row">
              <div class="field-group">
                <q-input
                  v-model="form.firstName"
                  outlined
                  dense
                  no-error-icon
                  hide-bottom-space
                  label="First name"
                  class="login-input"
                  :rules="[
                    val => !firstNameTouched || !!val || 'First name is required.',
                    val => !firstNameTouched || nameRule(val)
                  ]"
                  @blur="firstNameTouched = true"
                />
              </div>

              <div class="field-group">
                <q-input
                  v-model="form.lastName"
                  outlined
                  dense
                  no-error-icon
                  hide-bottom-space
                  label="Last name"
                  class="login-input"
                  :rules="[
                    val => !lastNameTouched || !!val || 'Last name is required.',
                    val => !lastNameTouched || nameRule(val)
                  ]"
                  @blur="lastNameTouched = true"
                />
              </div>
            </div>

            <!-- EMAIL -->
            <div class="field-group">
              <q-input
                v-model="form.email"
                outlined
                dense
                no-error-icon
                hide-bottom-space
                type="email"
                label="Email"
                class="login-input"
                :rules="[
                  val => !emailTouched || !!val || 'Email is required.',
                  val => !emailTouched || emailRule(val)
                ]"
                @blur="emailTouched = true"
              />
            </div>

            <!-- MOBILE NUMBER -->
            <div class="field-group">
              <q-input
                v-model="form.phoneNumber"
                outlined
                dense
                no-error-icon
                hide-bottom-space
                label="Mobile number"
                class="login-input"
                :rules="[
                  val => !phoneTouched || !!val || 'Mobile number is required.',
                  val => !phoneTouched || phoneRule(val)
                ]"
                @blur="phoneTouched = true"
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
                label="Create Password"
                class="login-input"
                :rules="[
                  val => !passwordTouched || !!val || 'Password is required.',
                  val => !passwordTouched || passwordRule(val)
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
            </div>

            <!-- CONFIRM PASSWORD -->
            <div class="field-group">
              <q-input
                v-model="form.confirmPassword"
                outlined
                dense
                no-error-icon
                hide-bottom-space
                :type="showConfirmPassword ? 'text' : 'password'"
                label="Confirm Password"
                class="login-input"
                :error="confirmPasswordMessage?.type === 'error'"
              >
                <template #append>
                  <q-icon
                    :name="showConfirmPassword
                      ? 'o_visibility'
                      : 'o_visibility_off'"
                    class="password-icon cursor-pointer"
                    @click="showConfirmPassword = !showConfirmPassword"
                  />
                </template>
              </q-input>
              <div v-if="confirmPasswordMessage" class="field-message" :class="`field-message-${confirmPasswordMessage.type}`">
                <q-icon v-if="confirmPasswordMessage.type === 'success'" name="o_check_circle" size="12px" />
                {{ confirmPasswordMessage.text }}
              </div>
            </div>

            <!-- ERROR MESSAGE -->
            <div v-if="registerError" class="error-message">
              {{ registerError }}
            </div>

            <!-- SUBMIT BUTTON -->
            <q-btn
              type="submit"
              label="Submit"
              no-caps
              unelevated
              class="login-button full-width"
              :loading="loading"
              :disable="!canRegister"
            />

          </q-form>

          <!-- LOGIN LINK -->
          <div class="register-section">
            <span>Already have an account?</span>

            <button
              type="button"
              class="text-button create-account"
              @click="goToLogin"
            >
              Log in here.
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

    <!-- LEGAL MODALS -->
    <TermsModal v-model="showTerms" />
    <PrivacyModal v-model="showPrivacy" />

  </q-page>
</template>

<script setup>
import { computed, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '@/boot/axios'
import TermsModal from '@/components/modals/TermsModal.vue'
import PrivacyModal from '@/components/modals/PrivacyModal.vue'

const router = useRouter()

const registerForm = ref(null)
const showPassword = ref(false)
const showConfirmPassword = ref(false)
const loading = ref(false)
const registerError = ref('')

// Legal modals
const showTerms = ref(false)
const showPrivacy = ref(false)

const form = reactive({
  firstName: '',
  lastName: '',
  email: '',
  phoneNumber: '',
  password: '',
  confirmPassword: ''
})

const nameRule = val =>
  /^[A-Za-zÀ-ÖØ-öø-ÿ' -]+$/.test(val) || 'Only letters are allowed.'

const emailRule = val => /.+@.+\..+/.test(val) || 'Enter a valid email address.'
const phoneRule = val => /^09\d{9}$/.test(val) || 'Mobile number must start with 09 and contain 11 digits.'
const passwordRule = val => val.length >= 8 || 'Minimum 8 characters'

// Gates each field's rules until touched, so rules stay silent on page load — same fix as the Login page's lazy-rules bug.
const firstNameTouched = ref(false)
const lastNameTouched = ref(false)
const emailTouched = ref(false)
const phoneTouched = ref(false)
const passwordTouched = ref(false)

// Confirm Password uses its own message (not Quasar's :rules) to show a positive "Passwords match" state, not just errors.
const confirmPasswordMessage = computed(() => {
  if (!form.confirmPassword) return null
  if (form.confirmPassword !== form.password) return { type: 'error', text: 'Passwords do not match.' }
  return { type: 'success', text: 'Passwords match.' }
})

const canRegister = computed(() =>
  !!form.firstName && nameRule(form.firstName) === true &&
  !!form.lastName && nameRule(form.lastName) === true &&
  !!form.email && emailRule(form.email) === true &&
  !!form.phoneNumber && phoneRule(form.phoneNumber) === true &&
  !!form.password && passwordRule(form.password) === true &&
  !!form.confirmPassword && form.confirmPassword === form.password
)

const handleRegister = async () => {
  firstNameTouched.value = true
  lastNameTouched.value = true
  emailTouched.value = true
  phoneTouched.value = true
  passwordTouched.value = true

  const isValid = await registerForm.value.validate()

  // Confirm Password isn't part of the form's own :rules, so it needs its own guard here.
  if (!isValid || !form.confirmPassword || form.confirmPassword !== form.password) {
    return
  }

  loading.value = true
  registerError.value = ''

  try {
    const payload = {
      full_name: `${form.firstName} ${form.lastName}`,
      email: form.email,
      phone_number: form.phoneNumber,
      password: form.password,
      password_confirmation: form.confirmPassword
    }

    await api.post('/register/consumer', payload)

    router.push({
      path: '/verification',
      state: {
        phone_number: form.phoneNumber,
        type: 'registration',
        role: 'Consumer'
      }
    })

  } catch (error) {
    loading.value = false
    if (error.response && error.response.status === 422) {
      const errors = error.response.data.errors
      const firstError = Object.values(errors || {})[0]
      registerError.value = firstError?.[0] || 'Validation failed. Please check your inputs.'
    } else {
      registerError.value = error.response?.data?.message || error.message || 'Something went wrong.'
    }
  } finally {
    loading.value = false
  }
}

const goToLogin = () => {
  router.push('/login')
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

/* RIGHT REGISTER PANEL */

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

.name-row {
  display: flex;

  gap: 12px;
}

.name-row .field-group {
  flex: 1;

  min-width: 0;
}

.field-group {
  margin-bottom: 16px;
}

/* hide-bottom-space removes this area entirely when there's no message, so padding only applies once one shows. */
.login-input :deep(.q-field__bottom) {
  padding-top: 6px;
  padding-bottom: 0;
}

/* Confirm Password's message (error or success) — same look as Quasar's own :rules-driven messages. */
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

.login-input :deep(.q-field__messages) {
  line-height: 1.4;
}

.login-input :deep(.q-field__control) {
  height: 40px;

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
  height: 40px;

  color: #777777;
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

/* REGISTER BUTTON */

.login-button {
  height: 48px;

  margin-top: 6px;

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

/* LOG IN LINK */

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

  border: none;
  background: transparent;

  padding: 0;

  font-family: 'Roboto', Arial, sans-serif;

  cursor: pointer;
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

    padding: 40px 35px;
  }

  .tindahan-logo {
    width: 260px;
  }
}

/* UPLOAD AREA (from VendorRegister) */
.upload-area {
  display: flex;
  align-items: center;
  justify-content: center;

  width: 120px;
  height: 120px;

  margin: 0 auto;

  background: #f8f8fa;
  border: 1px dashed #cfcfd6;
  border-radius: 8px;

  cursor: pointer;
  overflow: hidden;
  transition: all 0.2s ease;
}
.upload-area:hover {
  background: #f0f0f4;
  border-color: #a0a0ab;
}
.upload-area.has-preview {
  border-style: solid;
  border-color: transparent;
}

.upload-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

.preview-container {
  position: relative;
  width: 100%;
  height: 100%;
}

.photo-preview {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.preview-overlay {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;

  display: flex;
  align-items: center;
  justify-content: center;
  gap: 4px;

  padding: 4px;
  background: rgba(0, 0, 0, 0.55);

  font-size: 11px;
  font-weight: 500;
  color: #ffffff;
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

  .name-row {
    gap: 10px;
  }
}
</style>
