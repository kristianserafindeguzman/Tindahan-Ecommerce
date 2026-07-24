<template>
  <q-page class="login-page">
    <div class="login-card">

      <!-- LEFT BRANDING PANEL -->
      <div class="branding-panel">
        <img
          src="@/assets/tindahan-logo.png"
          alt="Tindahan Logo"
          class="tindahan-logo"
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
                hide-bottom-space
                label="Email or mobile number"
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
                hide-bottom-space
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
            <a href="#" @click.prevent>
              Terms and Conditions
            </a>
            and
            <br>
            <a href="#" @click.prevent>
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
            label="Register as Merchant"
            no-caps
            outline
            class="merchant-registration-button"
            @click="goToMerchantRegister"
          />

        </q-card-section>

      </q-card>
    </q-dialog>

  </q-page>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

const loginForm = ref(null)
const showPassword = ref(false)
const loading = ref(false)
const showRegistrationOptions = ref(false)

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

  try {
    // Laravel backend integration will be added later.
    // The backend will determine whether the user
    // is an Admin, Merchant, or Consumer.

    console.log('Login:', {
      identifier: form.identifier,
      password: form.password
    })

  } catch (error) {
    console.error('Login failed:', error)

  } finally {
    loading.value = false
  }
}

const handleForgotPassword = () => {
  console.log('Forgot password')
}

const goToConsumerRegister = () => {
  showRegistrationOptions.value = false
  router.push('/consumer/register')
}

const goToMerchantRegister = () => {
  showRegistrationOptions.value = false
  router.push('/merchant/register')
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

/* =========================
   RIGHT LOGIN PANEL
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
  margin-bottom: 17px;
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

.merchant-registration-button {
  height: 45px;

  color: #bd2427;
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
    height: 180px;

    padding: 25px;
  }

  .tindahan-logo {
    width: 200px;
  }

  .login-panel {
    width: 100%;

    padding: 40px 25px;
  }

  .login-content {
    max-width: 100%;
  }

  .login-content h1 {
    font-size: 25px;
  }

  .login-button {
    height: 50px;
  }
}
</style>
