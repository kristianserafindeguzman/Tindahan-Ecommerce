<template>
  <q-page class="vendor-page">
    <div class="vendor-card">

      <!-- Leaves the form without submitting, going back when there is history and otherwise to the storefront, since this page can also be opened straight from a link. -->
      <button type="button" class="vendor-back" @click="goBack">
        <q-icon name="o_arrow_back" size="18px" />
        <span>Back</span>
      </button>

      <!-- HEADER -->
      <div class="vendor-header">
        <img
          src="@/assets/tindahan-mobile.png"
          alt="Tindahan Logo"
          class="tindahan-logo"
        />

        <h1>Vendor Registration</h1>

        <p class="subtitle">
          Join our ecosystem of successful micro-entrepreneurs today.
        </p>
      </div>

      <q-form
        ref="vendorForm"
        class="vendor-form"
        @submit.prevent="handleVendorRegister"
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
                  no-error-icon
                  hide-bottom-space
                  label="Store name"
                  autocomplete="organization"
                  class="login-input"
                  :rules="[
                    val => !storeNameTouched || !!val || 'Store name is required.'
                  ]"
                  @blur="storeNameTouched = true"
                />
              </div>

              <div class="field-group">
                <q-input
                  v-model="form.ownerName"
                  outlined
                  dense
                  no-error-icon
                  hide-bottom-space
                  label="Store owner name"
                  autocomplete="name"
                  class="login-input"
                  :rules="[
                    val => !ownerNameTouched || !!val || 'Store owner name is required.',
                    val => !ownerNameTouched || nameRule(val)
                  ]"
                  @blur="ownerNameTouched = true"
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

                <div v-else class="preview-container">
                  <img
                    :src="photoPreview"
                    alt="Store exterior preview"
                    class="upload-preview"
                  />
                  <div class="preview-overlay" @click.prevent="openCropModal">
                    <q-icon name="crop" size="18px" />
                    <span>Edit / Crop</span>
                  </div>
                </div>
              </label>

              <div v-if="photoFile" class="photo-info">
                <q-icon name="image" size="14px" />
                <span>{{ photoFile.name }}</span>
                <button type="button" class="remove-photo" @click="removePhoto">
                  <q-icon name="close" size="14px" />
                </button>
              </div>
            </div>

            <!-- CONTACT & SECURITY -->
            <div class="section">
              <div class="section-title">Contact &amp; Security</div>

              <div class="field-group">
                <q-input
                  v-model="form.email"
                  outlined
                  dense
                  no-error-icon
                  hide-bottom-space
                  type="email"
                  label="Email address"
                  autocomplete="email"
                  class="login-input"
                  :rules="[
                    val => !emailTouched || !!val || 'Email is required.',
                    val => !emailTouched || emailRule(val)
                  ]"
                  @blur="emailTouched = true"
                />
              </div>

              <div class="field-group">
                <q-input
                  v-model="form.phoneNumber"
                  outlined
                  dense
                  no-error-icon
                  hide-bottom-space
                  label="Phone number"
                  type="tel"
                  autocomplete="tel"
                  class="login-input phone-input"
                  :rules="[
                    val => !phoneTouched || !!val || 'Phone number is required.',
                    val => !phoneTouched || phoneRule(val)
                  ]"
                  @blur="phoneTouched = true"
                >
                  <template #prepend>
                    <q-icon name="phone" class="phone-prefix" />
                  </template>
                </q-input>
              </div>

              <div class="field-group">
                <q-input
                  v-model="form.password"
                  outlined
                  dense
                  no-error-icon
                  hide-bottom-space
                  :type="showPassword ? 'text' : 'password'"
                  label="Create a password"
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
                        ? 'visibility'
                        : 'visibility_off'"
                      class="password-icon cursor-pointer"
                      @click="showPassword = !showPassword"
                    />
                  </template>
                </q-input>
                <div v-if="passwordStrong" class="field-message field-message-success">
                  <q-icon name="check_circle" size="12px" />
                  Strong password.
                </div>
              </div>

              <div class="field-group">
                <q-input
                  v-model="form.confirmPassword"
                  outlined
                  dense
                  no-error-icon
                  hide-bottom-space
                  :type="showConfirmPassword ? 'text' : 'password'"
                  label="Retype password"
                  autocomplete="new-password"
                  class="login-input"
                  :error="confirmPasswordMessage?.type === 'error'"
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
                <div v-if="confirmPasswordMessage" class="field-message" :class="`field-message-${confirmPasswordMessage.type}`">
                  <q-icon v-if="confirmPasswordMessage.type === 'success'" name="check_circle" size="12px" />
                  {{ confirmPasswordMessage.text }}
                </div>
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
                    :disable="alwaysOpen"
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
                    :disable="alwaysOpen"
                  >
                    <template #append>
                      <q-icon name="schedule" class="password-icon" />
                    </template>
                  </q-select>
                </div>
              </div>

              <div class="operating-days-block">
                <div class="detected-address-label">Operating Days</div>

                <q-toggle
                  v-model="alwaysOpen"
                  label="Always Open (24/7)"
                  color="red-9"
                  class="always-open-toggle"
                  @update:model-value="handleAlwaysOpenToggle"
                />

                <div class="days-toggle">
                  <button
                    v-for="day in ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']"
                    :key="day"
                    type="button"
                    class="day-btn"
                    :class="{ 'day-btn-active': form.operatingDays.includes(day) }"
                    @click="toggleDay(day)"
                  >
                    {{ day }}
                  </button>
                </div>
              </div>
            </div>

          </div>

          <!-- RIGHT COLUMN: STORE LOCATION -->
          <div class="map-column">
            <div class="section-title">Store Location</div>

            <div class="map-placeholder">


              <VendorLocationMap
                @location-selected="handleLocationSelected"
              />
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
                no-error-icon
                hide-bottom-space
                label="Manual address entry"
                class="login-input"
              />
            </div>
          </div>

        </div>

        <!-- ERROR MESSAGE -->
        <div v-if="registerError" class="error-message">
          {{ registerError }}
        </div>

        <!-- SUBMIT BUTTON -->
        <q-btn
          type="submit"
          label="Register Store"
          no-caps
          unelevated
          class="login-button full-width"
          :loading="loading"
          :disable="!canRegister"
        />

      </q-form>

      <!-- LOGIN LINK -->
      <div class="register-section">
        <span>Already a partner?</span>

        <button
          type="button"
          class="text-button create-account"
          @click="goToLogin"
        >
          Log in
        </button>
      </div>

      <!-- TERMS -->
      <p class="terms">
        By signing up, you agree to our
        <a href="#" @click.prevent="showTerms = true">Terms and Conditions</a>
        and
        <a href="#" @click.prevent="showPrivacy = true">Privacy Policy</a>.
      </p>

    </div>

    <!-- CROP DIALOG -->
    <q-dialog v-model="showCropModal" persistent>
      <q-card class="crop-dialog">
        <q-card-section class="crop-header">
          <div class="crop-icon"><q-icon name="o_crop" size="22px" /></div>
          <div class="crop-header-text">
            <div class="crop-title">Crop Store Photo</div>
            <div class="crop-subtitle">Drag the photo to move it, and zoom until the frame shows your storefront.</div>
          </div>
          <q-btn flat round dense icon="o_close" class="crop-close" aria-label="Close photo cropper" @click="showCropModal = false" />
        </q-card-section>
        <q-card-section class="crop-body">
          <PhotoCropper ref="cropperRef" :src="originalPhotoUrl || ''" :aspect="16 / 9" :output-width="1280" @ready="cropReady = true" />
        </q-card-section>
        <q-card-actions class="crop-actions">
          <q-btn outline no-caps label="Cancel" class="crop-cancel" @click="showCropModal = false" />
          <q-btn unelevated no-caps label="Apply Crop" class="crop-apply" :disable="!cropReady" @click="applyCrop" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- SUCCESS DIALOG -->
    <q-dialog v-model="showSuccess" persistent>
      <q-card class="success-dialog">

        <q-card-section class="success-content">
          <div class="success-icon-wrap">
            <q-icon name="o_check" size="32px" />
          </div>

          <div class="success-title">Application Submitted!</div>

          <p class="success-message">
            Your vendor application has been submitted and is currently
            under review. Our team will process your application within
            1–3 business days.
          </p>
        </q-card-section>

        <q-card-actions class="success-actions" vertical>
          <q-btn
            label="Close"
            no-caps
            unelevated
            class="success-btn primary-btn"
            @click="handleSuccessClose"
          />
          <q-btn
            label="Contact Support"
            no-caps
            flat
            class="success-btn flat-btn"
            @click="showContactSupport = true"
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
import { computed, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '@/boot/axios'
import TermsModal from '@/components/modals/TermsModal.vue'
import PrivacyModal from '@/components/modals/PrivacyModal.vue'
import ContactSupportModal from '@/components/modals/ContactSupportModal.vue'
import VendorLocationMap from '@/components/leaflet/VendorLocationMap.vue'
import PhotoCropper from '@/components/shared/PhotoCropper.vue'

const router = useRouter()

const goBack = () => {
  if (window.history.length > 1) router.back()
  else router.push('/consumer/home')
}

const vendorForm = ref(null)
const showPassword = ref(false)
const showConfirmPassword = ref(false)
const loading = ref(false)
const photoPreview = ref(null)
const photoFile = ref(null)
const originalPhotoUrl = ref(null)
const registerError = ref('')

// Gates each field's rules until touched, so rules stay silent on page load — same pattern as ConsumerRegister.vue.
const storeNameTouched = ref(false)
const ownerNameTouched = ref(false)
const emailTouched = ref(false)
const phoneTouched = ref(false)
const passwordTouched = ref(false)

// Dialogs
const showSuccess = ref(false)
const showContactSupport = ref(false)
const showTerms = ref(false)
const showPrivacy = ref(false)
const alwaysOpen = ref(false)

// Crop State
const showCropModal = ref(false)
const cropperRef = ref(null)
// Set once the cropper has loaded the photo, so Apply Crop can't run on an empty frame.
const cropReady = ref(false)

const form = reactive({
  storeName: '',
  ownerName: '',
  email: '',
  phoneNumber: '',
  password: '',
  confirmPassword: '',
  openingTime: '',
  closingTime: '',
  operatingDays: [],
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

const emailRule = val => /.+@.+\..+/.test(val) || 'Enter a valid email'
const phoneRule = val => /^09\d{9}$/.test(val) || 'Phone must be exactly 11 digits starting with 09'
const passwordRule = val => val.length >= 8 || 'Minimum 8 characters'

// Shown once the password passes its rule, the same positive state as the consumer sign-up and profile password fields.
const passwordStrong = computed(() => !!form.password && passwordRule(form.password) === true)

// Confirm Password uses its own message (not Quasar's :rules) to show a positive "Passwords match" state — same pattern as ConsumerRegister.vue.
const confirmPasswordMessage = computed(() => {
  if (!form.confirmPassword) return null
  if (form.confirmPassword !== form.password) return { type: 'error', text: 'Passwords do not match.' }
  return { type: 'success', text: 'Passwords match.' }
})

const canRegister = computed(() =>
  !!form.storeName &&
  !!form.ownerName && nameRule(form.ownerName) === true &&
  !!form.email && emailRule(form.email) === true &&
  !!form.phoneNumber && phoneRule(form.phoneNumber) === true &&
  !!form.password && passwordRule(form.password) === true &&
  !!form.confirmPassword && form.confirmPassword === form.password &&
  !!photoFile.value &&
  !!form.latitude && !!form.longitude
)

const toggleDay = (day) => {
  const index = form.operatingDays.indexOf(day)
  if (index > -1) {
    form.operatingDays.splice(index, 1)
  } else {
    form.operatingDays.push(day)
  }
}

const handleAlwaysOpenToggle = (val) => {
  if (val) {
    form.operatingDays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
    form.openingTime = '00:00'
    form.closingTime = '23:30'
  } else {
    form.operatingDays = []
    form.openingTime = ''
    form.closingTime = ''
  }
}

const handlePhotoChange = event => {
  const file = event.target.files?.[0]

  if (!file) {
    photoFile.value = null
    photoPreview.value = null
    return
  }

  photoFile.value = file
  const url = URL.createObjectURL(file)
  photoPreview.value = url
  originalPhotoUrl.value = url
}

const openCropModal = () => {
  cropReady.value = false
  showCropModal.value = true
}

// Saves the framed 16:9 area from the original photo, which stays available for cropping again.
const applyCrop = async () => {
  const blob = await cropperRef.value?.toBlob()
  if (!blob) return
  photoFile.value = new File([blob], 'cropped_' + (photoFile.value?.name || 'store.jpg'), { type: 'image/jpeg' })
  photoPreview.value = URL.createObjectURL(blob)
  showCropModal.value = false
}

const removePhoto = () => {
  photoFile.value = null
  photoPreview.value = null
  const input = document.getElementById('storePhoto')
  if (input) input.value = ''
}

const handleVendorRegister = async () => {
  storeNameTouched.value = true
  ownerNameTouched.value = true
  emailTouched.value = true
  phoneTouched.value = true
  passwordTouched.value = true

  const isValid = await vendorForm.value.validate()

  // Confirm Password isn't part of the form's own :rules, so it needs its own guard here.
  if (!isValid || !form.confirmPassword || form.confirmPassword !== form.password || !photoFile.value) return

  loading.value = true
  registerError.value = ''

  try {
    const dayMap = { 'Mon': 'Monday', 'Tue': 'Tuesday', 'Wed': 'Wednesday', 'Thu': 'Thursday', 'Fri': 'Friday', 'Sat': 'Saturday', 'Sun': 'Sunday' }
    const schedule = {}
    
    Object.values(dayMap).forEach(fullDay => {
      const isDaySelected = form.operatingDays.some(shortDay => dayMap[shortDay] === fullDay)
      schedule[fullDay] = {
        is_open: isDaySelected,
        opening_time: isDaySelected ? form.openingTime : null,
        closing_time: isDaySelected ? form.closingTime : null
      }
    })

    const formData = new FormData()
    formData.append('store_name', form.storeName)
    formData.append('full_name', form.ownerName)
    formData.append('email', form.email)
    formData.append('phone_number', form.phoneNumber)
    formData.append('password', form.password)
    formData.append('password_confirmation', form.confirmPassword)
    formData.append('opening_time', form.openingTime)
    formData.append('closing_time', form.closingTime)
    formData.append('operating_days', JSON.stringify(schedule))


    if (!form.latitude || !form.longitude) {
        registerError.value =
          'Please select your store location on the map.'
        loading.value = false
        return
      }

    const finalAddress =
      form.manualAddress.trim() ||
      form.detectedAddress


    formData.append(
      'address',
      finalAddress
    )
      
    formData.append(
      'latitude',
      form.latitude
    )

    formData.append(
      'longitude',
      form.longitude
    )


    if (photoFile.value) {
      formData.append('store_picture', photoFile.value)
    }

    await api.post('/register/vendor', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })

    // Show success popup
    showSuccess.value = true

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

const handleSuccessClose = () => {
  showSuccess.value = false
  router.push('/login')
}

const goToLogin = () => {
  router.push('/login')
}


function handleLocationSelected(location) {

  console.log('Store location:', location)

  // Save coordinates
  form.latitude = location.latitude
  form.longitude = location.longitude

  // Save detected address
  form.detectedAddress = location.address

}


</script>

<style scoped>
/* PAGE */

/* Same red gradient as the login and sign-up pages. */
.vendor-page {
  min-height: 100vh;

  display: flex;
  justify-content: center;

  padding: 48px 24px;

  background:
    linear-gradient(
      145deg,
      #c02226 0%,
      #9c171b 55%,
      #651012 100%
    );

  font-family: 'Roboto', Arial, sans-serif;
}

/* CARD */

.vendor-card {
  width: 100%;
  max-width: 900px;
  align-self: flex-start;

  padding: 45px 55px;

  background: #ffffff;
  border-radius: var(--r-2xl);

  box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
}

/* HEADER */

/* Sits above the centred header so it doesn't pull the logo off-centre, with an outline so it reads as a control rather than a stray label. */
.vendor-back {
  display: inline-flex;
  align-items: center;
  gap: 8px;

  height: 44px;
  margin-bottom: 10px;
  padding: 0 18px 0 14px;

  border: 1px solid var(--c-border);
  border-radius: var(--r-pill);

  background: #ffffff;
  color: var(--c-text-2);

  font-family: inherit;
  font-size: var(--fs-sm);
  font-weight: 600;

  cursor: pointer;

  transition: background-color 0.15s, border-color 0.15s, color 0.15s;
}

/* The red arrow ties the control to the section markers and submit button, while the label stays neutral so it doesn't compete with them. */
.vendor-back .q-icon {
  color: var(--c-brand);

  transition: transform 0.2s ease;
}

.vendor-back:hover {
  border-color: var(--c-brand-tint-3);
  background: var(--c-brand-tint);
  color: var(--c-brand);
}

.vendor-back:hover .q-icon {
  transform: translateX(-2px);
}

.vendor-back:active {
  background: var(--c-brand-tint-2);
}

.vendor-back:focus-visible {
  outline: 2px solid var(--c-brand);
  outline-offset: 2px;
}

.vendor-header {
  text-align: center;

  margin-bottom: 26px;
}

.tindahan-logo {
  display: block;

  width: 150px;

  margin: 0 auto 12px;

  object-fit: contain;
}

.vendor-header h1 {
  margin: 0 0 6px;

  font-size: 27px;
  line-height: 1.2;
  font-weight: 700;

  color: var(--c-text);
}

.subtitle {
  margin: 0;

  font-size: var(--fs-sm);
  line-height: 1.5;

  color: var(--c-muted);
}

/* FORM LAYOUT */

.vendor-form {
  width: 100%;
}

.register-body {
  display: grid;
  grid-template-columns: 1fr 1fr;

  gap: 40px;

  margin-bottom: 20px;
}

/* SECTIONS */

.section {
  margin-bottom: 26px;
}

.section-title {
  position: relative;

  margin-bottom: 14px;
  padding-left: 10px;

  font-size: var(--fs-2xs);
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;

  color: var(--c-text-2);
}

.section-title::before {
  content: '';

  position: absolute;
  left: 0;
  top: 1px;

  width: 3px;
  height: 13px;

  border-radius: 2px;

  background: var(--c-brand);
}

/* FIELDS (shared with login/register) */

.field-group {
  margin-bottom: 16px;
}

.hours-row {
  display: flex;

  gap: 12px;
}

.hours-row .field-group {
  flex: 1;

  min-width: 0;
}

/* hide-bottom-space removes this area entirely when there's no message, so padding only applies once one shows — same as ConsumerRegister.vue. */
.login-input :deep(.q-field__bottom) {
  padding-top: 6px;
  padding-bottom: 0;
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

.login-input :deep(.q-field__prepend) {
  height: 48px;
}

.password-icon {
  font-size: 18px;

  color: var(--c-subtle);
}

.phone-prefix {
  padding: 0 6px 0 4px;

  font-size: 13px;
  font-weight: 500;

  color: var(--c-text-2);

  border-right: 1px solid var(--c-border);
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

/* PHOTO UPLOAD */

.upload-dropzone {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;

  gap: 4px;

  height: 130px;

  border: 1.5px dashed var(--c-border-strong);
  border-radius: var(--r-lg);

  background: var(--c-surface-2);

  cursor: pointer;

  overflow: hidden;
  position: relative;

  transition: background-color 0.15s, border-color 0.15s;
}

.upload-dropzone:hover {
  border-color: var(--c-brand);
  background: var(--c-brand-tint);
}

.upload-input {
  display: none;
}

.upload-icon {
  font-size: 24px;

  color: var(--c-brand);
}

.upload-label {
  font-size: var(--fs-xs);
  font-weight: 600;

  color: var(--c-text-2);
}

.upload-hint {
  font-size: var(--fs-2xs);

  color: var(--c-muted);
}

.preview-container {
  position: relative;

  width: 100%;
  height: 100%;
}

.upload-preview {
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

  gap: 6px;

  padding: 6px;

  background: rgba(0, 0, 0, 0.55);

  font-size: var(--fs-2xs);
  font-weight: 500;

  color: #ffffff;
}

.photo-info {
  display: flex;
  align-items: center;

  gap: 6px;

  margin-top: 8px;

  font-size: var(--fs-2xs);

  color: var(--c-text-3);
}

.remove-photo {
  display: flex;
  align-items: center;
  justify-content: center;

  width: 28px;
  height: 28px;
  padding: 0;

  margin-left: auto;

  border: none;
  border-radius: var(--r-pill);
  background: transparent;

  color: var(--c-subtle);

  cursor: pointer;
}

.remove-photo:hover {
  background: var(--c-brand-tint);
  color: var(--c-brand);
}

/* BUSINESS HOURS OPTIONS */

.operating-days-block {
  margin-top: 4px;
}

.operating-days-block .detected-address-label {
  margin-bottom: 10px;
}

.always-open-toggle {
  margin: -6px 0 10px -8px;
}

.always-open-toggle :deep(.q-toggle__label) {
  font-size: var(--fs-xs);

  color: var(--c-text-2);
}

.days-toggle {
  display: flex;

  border: 1px solid var(--c-border);
  border-radius: var(--r-md);
  overflow: hidden;
}

.day-btn {
  flex: 1;

  min-height: 44px;
  padding: 0;

  border: none;
  border-right: 1px solid var(--c-border);

  background: #ffffff;
  color: var(--c-text-3);

  font-family: 'Roboto', Arial, sans-serif;
  font-size: var(--fs-xs);
  font-weight: 600;

  cursor: pointer;

  transition: background-color 0.15s, color 0.15s;
}

.day-btn:last-child {
  border-right: none;
}

.day-btn:hover {
  background: var(--c-brand-tint);
  color: var(--c-brand);
}

.day-btn-active,
.day-btn-active:hover {
  background: var(--c-brand);
  color: #ffffff;
}

/* MAP COLUMN */

.map-column {
  display: flex;
  flex-direction: column;
}

/* The map sets its own 280px minimum height, so this box matches it and clips the corners round. */
.map-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;

  gap: 6px;

  height: 280px;

  border-radius: var(--r-lg);
  overflow: hidden;

  background: var(--c-surface);

  color: var(--c-muted);
}

.detected-address {
  margin-top: 14px;
  padding: 10px 12px;

  border-radius: var(--r-md);

  background: var(--c-surface);
}

.detected-address-label {
  font-size: var(--fs-2xs);
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.03em;

  color: var(--c-muted);
}

.detected-address-value {
  margin-top: 3px;

  font-size: var(--fs-xs);

  color: var(--c-text-2);
}

.manual-address {
  margin-top: 14px;
}

/* SUBMIT BUTTON */

.login-button {
  height: 48px;

  margin-top: 4px;

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

/* LOGIN LINK */

.register-section {
  margin-top: 20px;

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

/* TERMS */

.terms {
  margin: 14px 0 0;

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

/* SUCCESS DIALOG */

.success-dialog {
  width: 400px;
  max-width: 90vw;

  border-radius: var(--r-xl);

  font-family: 'Roboto', Arial, sans-serif;
}

.success-content {
  text-align: center;

  padding: 30px 28px 10px;
}

/* A tinted tile, the same success treatment as the dialogs on the login page. */
.success-icon-wrap {
  display: inline-flex;
  align-items: center;
  justify-content: center;

  width: 64px;
  height: 64px;

  border-radius: var(--r-2xl);

  background: var(--c-success-tint);
  color: var(--c-success);

  margin-bottom: 18px;
}

.success-title {
  font-size: 19px;
  font-weight: 700;

  color: var(--c-text);

  margin-bottom: 10px;
}

.success-message {
  font-size: var(--fs-sm);
  line-height: 1.6;

  color: var(--c-text-3);

  margin: 0;
}

.success-actions {
  padding: 14px 28px 24px;
}

.success-btn {
  width: 100%;

  height: 48px;

  border-radius: var(--r-sm);

  font-size: var(--fs-sm);
  font-weight: 600;
}

.primary-btn {
  background: var(--c-brand);
  color: #ffffff;

  box-shadow: var(--sh-brand);
}

.primary-btn:hover {
  background: var(--c-brand-hover);
}

.flat-btn {
  color: var(--c-text-3);
}

/* CROP DIALOG */

/* Same layout as the Crop Profile Photo dialog on the consumer profile page. */
.crop-dialog {
  width: 560px;
  max-width: 90vw;

  border: 1px solid var(--c-border);
  border-radius: var(--r-xl);

  box-shadow: 0 18px 48px rgba(17, 17, 17, 0.18) !important;

  font-family: 'Roboto', Arial, sans-serif;
}

.crop-header {
  display: flex;
  align-items: center;

  gap: 12px;

  padding: 32px 32px 20px;

  border-bottom: 1px solid var(--c-hairline);
}

.crop-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;

  width: 44px;
  height: 44px;

  border-radius: var(--r-xl);

  background: linear-gradient(145deg, var(--c-brand-tint) 0%, var(--c-brand-tint-2) 100%);
  color: var(--c-brand);
}

.crop-header-text {
  flex: 1;
  min-width: 0;
}

.crop-title {
  font-size: 20px;
  line-height: 1.3;
  font-weight: 700;

  color: var(--c-text);
}

.crop-subtitle {
  margin-top: 2px;

  font-size: var(--fs-sm);
  line-height: 1.4;

  color: var(--c-subtle);
}

.crop-close {
  color: var(--c-muted);
}

.crop-body {
  padding: 24px 32px;
}

.crop-actions {
  justify-content: flex-end;

  gap: 10px;

  padding: 20px 32px;

  border-top: 1px solid var(--c-border);
}

/* !important beats Quasar's own dialog-actions rule, the same as the profile page's paired buttons. */
.crop-actions .q-btn {
  min-width: 160px !important;
  height: 48px;

  border-radius: var(--r-sm);

  font-size: var(--fs-sm);
  font-weight: 600;
}

/* Quasar spaces neighbouring card buttons with its own margin, which would double up with the gap. */
.crop-actions .q-btn + .q-btn {
  margin-left: 0;
}

.crop-cancel {
  color: var(--c-text-2);
}

/* Quasar draws the outline on ::before in the text colour, so the softer border has to be set there. */
.crop-cancel::before {
  border-color: var(--c-border-strong);
}

.crop-cancel:hover {
  background: var(--c-surface);
}

.crop-apply {
  background: var(--c-brand);
  color: #ffffff;

  box-shadow: var(--sh-brand);
}

.crop-apply:hover {
  background: var(--c-brand-hover);
}

@media (max-width: 600px) {
  /* Quasar pads a small dialog by 24px, which is trimmed to 16px so the photo gets more room on phones. */
  :global(.q-dialog__inner--minimized:has(.crop-dialog)) {
    padding: 16px;
  }

  .crop-dialog {
    max-width: 100%;
  }

  .crop-header {
    align-items: flex-start;

    padding: 24px 24px 20px;
  }

  .crop-body {
    padding: 20px 24px;
  }

  .crop-actions {
    padding: 16px 24px;
  }

  /* The two buttons split the row equally, so the desktop minimum width is dropped. */
  .crop-actions .q-btn {
    flex: 1 1 0;
    min-width: 0 !important;
  }
}

/* TABLET */

@media (max-width: 900px) {
  .vendor-card {
    padding: 35px 30px;
  }

  .register-body {
    grid-template-columns: 1fr;

    gap: 10px;
  }
}

/* MOBILE */

/* Full-bleed white on phones, the same as the login page. */
@media (max-width: 600px) {
  .vendor-page {
    padding: 0;

    background: #ffffff;
  }

  .vendor-card {
    border-radius: 0;
    box-shadow: none;

    padding: 24px 20px 40px;
  }

  .tindahan-logo {
    width: 110px;
  }

  .vendor-header h1 {
    font-size: 22px;
  }

  .hours-row {
    flex-direction: column;

    gap: 0;
  }
}
</style>
