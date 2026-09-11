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
                  autocomplete="given-name"
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
                  autocomplete="family-name"
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
                autocomplete="email"
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
                type="tel"
                autocomplete="tel"
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
                autocomplete="new-password"
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
              <div v-if="passwordStrong" class="field-message field-message-success">
                <q-icon name="o_check_circle" size="12px" />
                Strong password.
              </div>
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
                autocomplete="new-password"
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
              label="Create account"
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
              Log in
            </button>
          </div>

          <q-separator class="separator" />

          <!-- TERMS -->
          <p class="terms">
            By signing up, you agree to our
            <a href="#" @click.prevent="showTerms = true">Terms and Conditions</a>
            and
            <br />
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

// Shown once the password passes its rule, the same positive state as Change Password on the profile page.
const passwordStrong = computed(() => !!form.password && passwordRule(form.password) === true)

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

.login-input :deep(.q-field__messages) {
  line-height: 1.4;
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

  color: var(--c-subtle);
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

/* REGISTER BUTTON */

.login-button {
  height: 48px;

  margin-top: 6px;

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

/* LOG IN LINK */

.register-section {
  margin-top: 17px;

  display: flex;
  align-items: center;
  justify-content: center;

  gap: 4px;

  font-size: var(--fs-xs);
}

.register-section span {
  color: var(--c-muted);
}

/* Padding cancelled by an equal negative margin grows the tap area to 44px without moving anything. */
.create-account {
  padding: 14px 0;
  margin: -14px 0;

  border: none;
  background: transparent;

  font-family: 'Roboto', Arial, sans-serif;
  font-size: var(--fs-xs);
  font-weight: 600;

  color: var(--c-brand);

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
