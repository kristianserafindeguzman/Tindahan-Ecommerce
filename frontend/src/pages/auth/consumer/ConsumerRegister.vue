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
                  label="First name"
                  class="login-input"
                  :rules="[
                    val => !!val || 'First name is required',
                    nameRule
                  ]"
                />
              </div>

              <div class="field-group">
                <q-input
                  v-model="form.lastName"
                  outlined
                  dense
                  label="Last name"
                  class="login-input"
                  :rules="[
                    val => !!val || 'Last name is required',
                    nameRule
                  ]"
                />
              </div>
            </div>

            <!-- EMAIL -->
            <div class="field-group">
              <q-input
                v-model="form.email"
                outlined
                dense
                type="email"
                label="Email"
                class="login-input"
                :rules="[
                  val => !!val || 'Email is required',
                  emailRule
                ]"
              />
            </div>

            <!-- MOBILE NUMBER -->
            <div class="field-group">
              <q-input
                v-model="form.mobileNumber"
                outlined
                dense
                label="Mobile number"
                class="login-input"
                :rules="[
                  val => !!val || 'Mobile number is required',
                  mobileRule
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
                label="Create Password"
                class="login-input"
                :rules="[
                  val => !!val || 'Password is required',
                  passwordRule
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
            </div>

            <!-- CONFIRM PASSWORD -->
            <div class="field-group">
              <q-input
                v-model="form.confirmPassword"
                outlined
                dense
                :type="showConfirmPassword ? 'text' : 'password'"
                label="Confirm Password"
                class="login-input"
                :rules="[
                  val => !!val || 'Please confirm your password',
                  val => val === form.password || 'Passwords do not match'
                ]"
              >
                <template #append>
                  <q-icon
                    :name="showConfirmPassword
                      ? 'visibility'
                      : 'visibility_off'"
                    class="password-icon cursor-pointer"
                    @click="showConfirmPassword = !showConfirmPassword"
                  />
                </template>
              </q-input>
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
import { reactive, ref } from 'vue'
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
  mobileNumber: '',
  password: '',
  confirmPassword: ''
})

const nameRule = val =>
  /^[A-Za-zÀ-ÖØ-öø-ÿ' -]+$/.test(val) || 'Only letters are allowed'

const emailRule = val =>
  /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val) || 'Enter a valid email address'

const mobileRule = val =>
  /^(09\d{9}|\+639\d{9})$/.test(val) ||
  'Enter a valid mobile number (e.g. 09171234567)'

const passwordRule = val =>
  /^(?=.*[A-Za-z])(?=.*\d).{8,}$/.test(val) ||
  'Min 8 characters, with a letter and a number'

const handleRegister = async () => {
  const isValid = await registerForm.value.validate()

  if (!isValid) {
    return
  }

  loading.value = true
  registerError.value = ''

  try {
    await api.post('/register/consumer', {
      full_name: `${form.firstName} ${form.lastName}`,
      email: form.email,
      phone_number: form.mobileNumber,
      password: form.password,
      password_confirmation: form.confirmPassword
    })

    // On success, route to OTP verification page with the mobile number
    router.push({
      path: '/consumer/verify',
      query: { phone_number: form.mobileNumber }
    })

  } catch (error) {
    if (error.response && error.response.status === 422) {
      const errors = error.response.data.errors
      const firstError = Object.values(errors || {})[0]
      registerError.value = firstError?.[0] || 'Validation failed. Please check your inputs.'
    } else {
      registerError.value = 'Something went wrong. Please try again later.'
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
   RIGHT REGISTER PANEL
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

.name-row {
  display: flex;

  gap: 12px;
}

.name-row .field-group {
  flex: 1;

  min-width: 0;
}

.field-group {
  margin-bottom: 4px;
}

.login-input :deep(.q-field__bottom) {
  padding-top: 4px;
  padding-bottom: 10px;
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
   REGISTER BUTTON
========================= */

.login-button {
  height: 48px;

  margin-top: 6px;

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
   LOG IN LINK
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

  border: none;
  background: transparent;

  padding: 0;

  font-family: 'Roboto', Arial, sans-serif;

  cursor: pointer;
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

  .login-button {
    height: 48px;
  }

  .name-row {
    gap: 10px;
  }
}
</style>
