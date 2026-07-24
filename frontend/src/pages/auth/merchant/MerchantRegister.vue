<template>
  <q-page class="merchant-page">
    <div class="merchant-card">

      <!-- HEADER -->
      <div class="merchant-header">
        <img
          src="@/assets/tindahan-mobile.png"
          alt="Tindahan Logo"
          class="tindahan-logo"
        />

        <h1>Merchant Registration</h1>

        <p class="subtitle">
          Join our ecosystem of successful micro-entrepreneurs today.
        </p>
      </div>

      <q-form
        ref="merchantForm"
        class="merchant-form"
        @submit.prevent="handleMerchantRegister"
      >

        <div class="register-body">

          <!-- LEFT COLUMN: FORM FIELDS -->
          <div class="form-column">

            <!-- STORE IDENTITY -->
            <div class="section">
              <div class="section-title">Store Identity</div>

              <div class="field-group">
                <q-input
                  v-model="form.storeName"
                  outlined
                  dense
                  hide-bottom-space
                  label="Store name"
                  class="login-input"
                  :rules="[
                    val => !!val || 'Store name is required'
                  ]"
                />
              </div>

              <div class="field-group">
                <q-input
                  v-model="form.ownerName"
                  outlined
                  dense
                  hide-bottom-space
                  label="Store owner name"
                  class="login-input"
                  :rules="[
                    val => !!val || 'Store owner name is required',
                    nameRule
                  ]"
                />
              </div>
            </div>

            <!-- STORE APPEARANCE -->
            <div class="section">
              <div class="section-title">Store Appearance</div>

              <label class="upload-dropzone" for="storePhoto">
                <input
                  id="storePhoto"
                  type="file"
                  accept="image/png, image/jpeg, image/gif"
                  class="upload-input"
                  @change="handlePhotoChange"
                />

                <template v-if="!photoPreview">
                  <q-icon name="add_a_photo" class="upload-icon" />
                  <div class="upload-label">Upload Store Exterior Photo</div>
                  <div class="upload-hint">PNG, JPG, GIF up to 10MB</div>
                </template>

                <img
                  v-else
                  :src="photoPreview"
                  alt="Store exterior preview"
                  class="upload-preview"
                />
              </label>
            </div>

            <!-- CONTACT & SECURITY -->
            <div class="section">
              <div class="section-title">Contact &amp; Security</div>

              <div class="field-group">
                <q-input
                  v-model="form.email"
                  outlined
                  dense
                  hide-bottom-space
                  type="email"
                  label="Email address"
                  class="login-input"
                  :rules="[
                    val => !!val || 'Email is required',
                    emailRule
                  ]"
                />
              </div>

              <div class="field-group">
                <q-input
                  v-model="form.phoneNumber"
                  outlined
                  dense
                  hide-bottom-space
                  label="Phone number"
                  class="login-input phone-input"
                  :rules="[
                    val => !!val || 'Phone number is required',
                    phoneRule
                  ]"
                >
                  <template #prepend>
                    <span class="phone-prefix">+63</span>
                  </template>
                </q-input>
              </div>

              <div class="field-group">
                <q-input
                  v-model="form.password"
                  outlined
                  dense
                  hide-bottom-space
                  :type="showPassword ? 'text' : 'password'"
                  label="Create a password"
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

              <div class="field-group">
                <q-input
                  v-model="form.confirmPassword"
                  outlined
                  dense
                  hide-bottom-space
                  :type="showConfirmPassword ? 'text' : 'password'"
                  label="Retype password"
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
            </div>

            <!-- BUSINESS HOURS -->
            <div class="section">
              <div class="section-title">Business Hours</div>

              <div class="hours-row">
                <div class="field-group">
                  <q-select
                    v-model="form.openingTime"
                    outlined
                    dense
                    hide-bottom-space
                    emit-value
                    map-options
                    label="Opening time"
                    class="login-input"
                    :options="timeOptions"
                    :disable="form.hoursOption === 'always'"
                    :rules="[
                      val => form.hoursOption === 'always' || !!val || 'Opening time is required'
                    ]"
                  >
                    <template #append>
                      <q-icon name="schedule" class="password-icon" />
                    </template>
                  </q-select>
                </div>

                <div class="field-group">
                  <q-select
                    v-model="form.closingTime"
                    outlined
                    dense
                    hide-bottom-space
                    emit-value
                    map-options
                    label="Closing time"
                    class="login-input"
                    :options="timeOptions"
                    :disable="form.hoursOption === 'always'"
                    :rules="[
                      val => form.hoursOption === 'always' || !!val || 'Closing time is required'
                    ]"
                  >
                    <template #append>
                      <q-icon name="schedule" class="password-icon" />
                    </template>
                  </q-select>
                </div>
              </div>

              <q-option-group
                v-model="form.hoursOption"
                type="radio"
                class="hours-options"
                :options="[
                  { label: 'Always Open (24/7)', value: 'always' },
                  { label: 'Open Every Weekend', value: 'weekend' },
                  { label: 'Open Every Weekday', value: 'weekday' }
                ]"
              />
            </div>

          </div>

          <!-- RIGHT COLUMN: STORE LOCATION -->
          <div class="map-column">
            <div class="section-title">Store Location</div>

            <!--
              NOTE: this is a static placeholder. Wire up your real map
              provider (Google Maps / Mapbox / Leaflet) here — it needs
              an API key this file doesn't have access to. Keep
              `form.latitude` / `form.longitude` / `form.detectedAddress`
              updated from the map's pin-drag or geolocation events.
            -->
            <div class="map-placeholder">
              <q-icon name="location_on" class="map-pin-icon" />
              <span class="map-placeholder-text">Map goes here</span>
            </div>

            <div class="detected-address">
              <div class="detected-address-label">Detected address</div>
              <div class="detected-address-value">
                {{ form.detectedAddress || 'Waiting for location…' }}
              </div>
            </div>

            <div class="field-group manual-address">
              <q-input
                v-model="form.manualAddress"
                outlined
                dense
                hide-bottom-space
                label="Manual address entry"
                class="login-input"
              />
            </div>
          </div>

        </div>

        <!-- SUBMIT BUTTON -->
        <q-btn
          type="submit"
          no-caps
          unelevated
          class="login-button full-width"
          :loading="loading"
        >
          Register Store
          <q-icon name="arrow_forward" class="q-ml-xs" />
        </q-btn>

      </q-form>

      <!-- LOGIN LINK -->
      <div class="register-section">
        <span>Already a partner?</span>

        <button
          type="button"
          class="text-button create-account"
          @click="goToLogin"
        >
          Login
        </button>
      </div>

      <!-- TERMS -->
      <p class="terms">
        By signing up, you agree to our
        <a href="#" @click.prevent>
          Terms and Conditions
        </a>
        and
        <a href="#" @click.prevent>
          Privacy Policy
        </a>
      </p>

    </div>
  </q-page>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

const merchantForm = ref(null)
const showPassword = ref(false)
const showConfirmPassword = ref(false)
const loading = ref(false)
const photoPreview = ref(null)
const photoFile = ref(null)

const form = reactive({
  storeName: '',
  ownerName: '',
  email: '',
  phoneNumber: '',
  password: '',
  confirmPassword: '',
  openingTime: '',
  closingTime: '',
  hoursOption: 'weekday',
  detectedAddress: '',
  manualAddress: '',
  latitude: null,
  longitude: null
})

const timeOptions = []
for (let minutes = 0; minutes < 24 * 60; minutes += 30) {
  const hour24 = Math.floor(minutes / 60)
  const minute = minutes % 60
  const period = hour24 < 12 ? 'AM' : 'PM'
  const hour12 = hour24 % 12 === 0 ? 12 : hour24 % 12

  const value = `${String(hour24).padStart(2, '0')}:${String(minute).padStart(2, '0')}`
  const label = `${hour12}:${String(minute).padStart(2, '0')} ${period}`

  timeOptions.push({ label, value })
}

const nameRule = val =>
  /^[A-Za-zÀ-ÖØ-öø-ÿ' -]+$/.test(val) || 'Only letters are allowed'

const emailRule = val =>
  /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val) || 'Enter a valid email address'

const phoneRule = val =>
  /^\d{9,10}$/.test(val) || 'Enter a valid phone number (e.g. 9171234567)'

const passwordRule = val =>
  /^(?=.*[A-Za-z])(?=.*\d).{8,}$/.test(val) ||
  'Password must be at least 8 characters and include a letter and a number'

const handlePhotoChange = event => {
  const file = event.target.files?.[0]

  if (!file) {
    photoFile.value = null
    photoPreview.value = null
    return
  }

  photoFile.value = file
  photoPreview.value = URL.createObjectURL(file)
}

const handleMerchantRegister = async () => {
  const isValid = await merchantForm.value.validate()

  if (!isValid) {
    return
  }

  loading.value = true

  try {
    // Laravel backend integration will be added later.
    // This will register the user as a Merchant.
    // photoFile.value holds the raw File for multipart upload.

    console.log('Merchant Register:', {
      storeName: form.storeName,
      ownerName: form.ownerName,
      email: form.email,
      phoneNumber: `+63${form.phoneNumber}`,
      password: form.password,
      confirmPassword: form.confirmPassword,
      openingTime: form.openingTime,
      closingTime: form.closingTime,
      hoursOption: form.hoursOption,
      detectedAddress: form.detectedAddress,
      manualAddress: form.manualAddress,
      latitude: form.latitude,
      longitude: form.longitude,
      photo: photoFile.value
    })

  } catch (error) {
    console.error('Merchant registration failed:', error)

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

.merchant-page {
  min-height: 100vh;

  display: flex;
  justify-content: center;

  padding: 40px 20px;

  background: #f4f4f4;

  font-family: 'Roboto', Arial, sans-serif;
}

/* =========================
   CARD
========================= */

.merchant-card {
  width: 100%;
  max-width: 900px;

  padding: 45px 55px;

  background: #ffffff;

  box-shadow:
    0 12px 35px rgba(0, 0, 0, 0.12);
}

/* =========================
   HEADER
========================= */

.merchant-header {
  text-align: center;

  margin-bottom: 22px;
}

.tindahan-logo {
  display: block;

  width: 160px;

  margin: 0 auto 10px;

  object-fit: contain;
}

.merchant-header h1 {
  margin: 0 0 4px;

  font-size: 22px;
  line-height: 1.25;
  font-weight: 700;

  color: #111111;
}

.subtitle {
  margin: 0;

  font-size: 13px;
  line-height: 1.4;

  color: #8992a2;
}

/* =========================
   FORM LAYOUT
========================= */

.merchant-form {
  width: 100%;
}

.register-body {
  display: grid;
  grid-template-columns: 1fr 1fr;

  gap: 40px;

  margin-bottom: 20px;
}

/* =========================
   SECTIONS
========================= */

.section {
  margin-bottom: 26px;
}

.section-title {
  position: relative;

  margin-bottom: 14px;
  padding-left: 10px;

  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;

  color: #333333;
}

.section-title::before {
  content: '';

  position: absolute;
  left: 0;
  top: 1px;

  width: 3px;
  height: 13px;

  background: #bd2427;
}

/* =========================
   FIELDS (shared with login/register)
========================= */

.field-group {
  margin-bottom: 14px;
}

.hours-row {
  display: flex;

  gap: 12px;
}

.hours-row .field-group {
  flex: 1;

  min-width: 0;
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

.login-input :deep(.q-field__prepend) {
  height: 40px;
}

.password-icon {
  font-size: 18px;

  color: #777777;
}

.phone-prefix {
  padding: 0 6px 0 4px;

  font-size: 13px;
  font-weight: 500;

  color: #333333;

  border-right: 1px solid #d6d6da;
}

/* =========================
   PHOTO UPLOAD
========================= */

.upload-dropzone {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;

  gap: 4px;

  height: 130px;

  border: 1.5px dashed #cfcfd4;
  border-radius: 10px;

  background: #fafafa;

  cursor: pointer;

  overflow: hidden;
}

.upload-input {
  display: none;
}

.upload-icon {
  font-size: 24px;

  color: #555555;
}

.upload-label {
  font-size: 12px;
  font-weight: 600;

  color: #333333;
}

.upload-hint {
  font-size: 10px;

  color: #9a9aa2;
}

.upload-preview {
  width: 100%;
  height: 100%;

  object-fit: cover;
}

/* =========================
   BUSINESS HOURS OPTIONS
========================= */

.hours-options :deep(.q-radio) {
  margin-right: 18px;

  font-size: 12px;

  color: #333333;
}

/* =========================
   MAP COLUMN
========================= */

.map-column {
  display: flex;
  flex-direction: column;
}

.map-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;

  gap: 6px;

  height: 280px;

  border-radius: 10px;

  background: #eceef1;

  color: #9a9aa2;
}

.map-pin-icon {
  font-size: 26px;

  color: #bd2427;
}

.map-placeholder-text {
  font-size: 12px;
}

.detected-address {
  margin-top: 14px;
  padding: 10px 12px;

  border-radius: 8px;

  background: #f6f6f7;
}

.detected-address-label {
  font-size: 10px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.03em;

  color: #9a9aa2;
}

.detected-address-value {
  margin-top: 3px;

  font-size: 12px;

  color: #333333;
}

.manual-address {
  margin-top: 14px;
}

/* =========================
   SUBMIT BUTTON
========================= */

.login-button {
  height: 48px;

  margin-top: 4px;

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
   LOGIN LINK
========================= */

.register-section {
  margin-top: 20px;

  display: flex;
  align-items: center;
  justify-content: center;

  gap: 4px;

  font-size: 12px;
}

.register-section span {
  color: #8e97a6;
}

.create-account {
  font-size: 12px;

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
   TERMS
========================= */

.terms {
  margin: 10px 0 0;

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

@media (max-width: 900px) {
  .merchant-card {
    padding: 35px 30px;
  }

  .register-body {
    grid-template-columns: 1fr;

    gap: 10px;
  }

  .map-placeholder {
    height: 220px;
  }
}

/* =========================
   MOBILE
========================= */

@media (max-width: 600px) {
  .merchant-page {
    padding: 0;

    background: #ffffff;
  }

  .merchant-card {
    box-shadow: none;

    padding: 30px 20px 40px;
  }

  .tindahan-logo {
    width: 120px;
  }

  .merchant-header h1 {
    font-size: 19px;
  }

  .hours-row {
    flex-direction: column;

    gap: 0;
  }
}
</style>
