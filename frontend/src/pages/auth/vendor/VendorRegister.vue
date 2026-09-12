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

      <!-- Shows how far through the six steps the vendor is. -->
      <div class="wizard-progress">
        <div class="wizard-step-label">Step {{ step }} of {{ STEPS.length }} · {{ STEPS[step - 1] }}</div>
        <div
          class="wizard-bar"
          role="progressbar"
          aria-label="Registration progress"
          aria-valuemin="1"
          :aria-valuemax="STEPS.length"
          :aria-valuenow="step"
        >
          <span
            v-for="n in STEPS.length"
            :key="n"
            class="wizard-bar-segment"
            :class="{ 'wizard-bar-segment-done': n <= step }"
          />
        </div>
      </div>

      <!-- STEP 1: OWNER ACCOUNT -->
      <q-form v-show="step === 1" ref="accountForm" greedy class="vendor-form" @submit.prevent="submitAccount">
        <div class="section-title">Owner Account</div>
        <p class="step-hint">We'll text a code to your phone number to make sure it's yours.</p>

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
                :name="showPassword ? 'visibility' : 'visibility_off'"
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
                :name="showConfirmPassword ? 'visibility' : 'visibility_off'"
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

        <div v-if="stepError && step === 1" class="error-message">{{ stepError }}</div>

        <q-btn type="submit" label="Continue" no-caps unelevated class="login-button full-width" :loading="sendingCode" />
      </q-form>

      <!-- STEP 2: VERIFY PHONE -->
      <div v-show="step === 2" class="vendor-form">
        <div class="section-title">Verify Your Phone</div>
        <p class="step-hint">Enter the 6-digit code we sent to <strong>{{ maskedPhone }}</strong>.</p>

        <div class="otp-row">
          <input
            v-for="(digit, index) in otp"
            :key="index"
            :ref="el => { otpRefs[index] = el }"
            v-model="otp[index]"
            type="text"
            inputmode="numeric"
            :autocomplete="index === 0 ? 'one-time-code' : 'off'"
            :aria-label="`Digit ${index + 1} of 6`"
            class="otp-box"
            :class="{ 'otp-error': otpError, 'otp-success': otpVerified }"
            :disabled="otpVerified"
            @focus="$event.target.select()"
            @input="handleOtpInput(index)"
            @keydown="handleOtpKeydown(index, $event)"
            @paste="handleOtpPaste"
          />
        </div>

        <div v-if="otpError" class="error-message">{{ otpError }}</div>

        <div class="resend-section">
          <span>Didn't receive a code?</span>
          <button
            type="button"
            class="resend-btn"
            :class="{ 'resend-disabled': resendTimer > 0 }"
            :disabled="resendTimer > 0 || sendingCode || otpVerified"
            @click="resendCode"
          >
            {{ resendTimer > 0 ? `Resend in ${formattedResendTimer}` : 'Resend Code' }}
          </button>
        </div>

        <div class="wizard-actions">
          <q-btn outline no-caps label="Change number" class="wizard-back" @click="previousStep" />
          <q-btn unelevated no-caps label="Verify" class="login-button" :loading="verifyingCode" :disable="!otpComplete || otpVerified" @click="verifyCode" />
        </div>
      </div>

      <!-- STEP 3: STORE -->
      <q-form v-show="step === 3" ref="storeForm" greedy class="vendor-form" @submit.prevent="nextFromStore">
        <div class="section-title">Your Store</div>
        <p class="step-hint">Both are required, and customers will see this name and photo.</p>

        <div class="field-group">
          <q-input
            v-model="form.storeName"
            outlined
            dense
            no-error-icon
            hide-bottom-space
            label="Store name"
            autocomplete="organization"
            maxlength="150"
            class="login-input"
            :rules="[
              val => !storeNameTouched || !!val?.trim() || 'Store name is required.'
            ]"
            @blur="storeNameTouched = true"
          />
        </div>

        <label class="upload-dropzone" :class="{ 'upload-dropzone--error': photoMissing }" for="storePhoto">
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
          <button type="button" class="remove-photo" aria-label="Remove photo" @click="removePhoto">
            <q-icon name="close" size="14px" />
          </button>
        </div>

        <div v-if="photoMissing" class="photo-error" role="alert">Add a photo of your storefront.</div>

        <div v-if="stepError && step === 3" class="error-message step-error">{{ stepError }}</div>

        <div class="wizard-actions">
          <q-btn outline no-caps label="Back" class="wizard-back" @click="previousStep" />
          <q-btn type="submit" unelevated no-caps label="Continue" class="login-button" />
        </div>
      </q-form>

      <!-- STEP 4: HOURS -->
      <div v-show="step === 4" class="vendor-form">
        <div class="section-title">Business Hours</div>
        <p class="step-hint">Let customers know when your store is open.</p>

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
              v-for="day in DAY_ORDER"
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

        <div v-if="stepError && step === 4" class="error-message step-error">{{ stepError }}</div>

        <div class="wizard-actions">
          <q-btn outline no-caps label="Back" class="wizard-back" @click="previousStep" />
          <q-btn unelevated no-caps label="Continue" class="login-button" @click="nextFromHours" />
        </div>
      </div>

      <!-- STEP 5: LOCATION, mounted on first visit because Leaflet can't size a map inside a hidden panel. -->
      <div v-if="locationVisited" v-show="step === 5" class="vendor-form">
        <div class="section-title">Store Location</div>
        <p class="step-hint">Move the pin to where your store is, or type the address below.</p>

        <div class="map-placeholder">
          <VendorLocationMap @location-selected="handleLocationSelected" />
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

        <div v-if="stepError && step === 5" class="error-message">{{ stepError }}</div>

        <div class="wizard-actions">
          <q-btn outline no-caps label="Back" class="wizard-back" @click="previousStep" />
          <q-btn unelevated no-caps label="Continue" class="login-button" @click="nextFromLocation" />
        </div>
      </div>

      <!-- STEP 6: REVIEW -->
      <div v-show="step === 6" class="vendor-form">
        <div class="section-title">Review &amp; Submit</div>
        <p class="step-hint">Check your details before sending your application.</p>

        <div class="review-list">
          <div class="review-row">
            <div class="review-body">
              <div class="review-label">Owner</div>
              <div class="review-value">{{ form.ownerName }}</div>
              <div class="review-sub">{{ form.email }}</div>
            </div>
            <button type="button" class="review-edit" @click="editStep(1)">Edit</button>
          </div>

          <div class="review-row">
            <div class="review-body">
              <div class="review-label">Phone</div>
              <div class="review-value">
                {{ form.phoneNumber }}
                <span v-if="phoneVerified" class="review-verified">
                  <q-icon name="o_verified" size="14px" />
                  Verified
                </span>
              </div>
            </div>
            <button type="button" class="review-edit" @click="editStep(1)">Edit</button>
          </div>

          <div class="review-row">
            <img v-if="photoPreview" :src="photoPreview" alt="Store photo" class="review-thumb" />
            <div class="review-body">
              <div class="review-label">Store</div>
              <div class="review-value">{{ form.storeName }}</div>
            </div>
            <button type="button" class="review-edit" @click="editStep(3)">Edit</button>
          </div>

          <div class="review-row">
            <div class="review-body">
              <div class="review-label">Hours</div>
              <div class="review-value">{{ hoursSummary }}</div>
              <div class="review-sub">{{ daysSummary }}</div>
            </div>
            <button type="button" class="review-edit" @click="editStep(4)">Edit</button>
          </div>

          <div class="review-row">
            <div class="review-body">
              <div class="review-label">Location</div>
              <div class="review-value">{{ finalAddress || 'Pinned on the map' }}</div>
            </div>
            <button type="button" class="review-edit" @click="editStep(5)">Edit</button>
          </div>
        </div>

        <div v-if="registerError" class="error-message step-error">{{ registerError }}</div>

        <div class="wizard-actions">
          <q-btn outline no-caps label="Back" class="wizard-back" @click="previousStep" />
          <q-btn unelevated no-caps label="Register Store" class="login-button" :loading="loading" :disable="!canRegister" @click="handleVendorRegister" />
        </div>
      </div>

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
import { computed, nextTick, onUnmounted, reactive, ref } from 'vue'
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

const showPassword = ref(false)
const showConfirmPassword = ref(false)
const loading = ref(false)
const photoPreview = ref(null)
const photoFile = ref(null)
const originalPhotoUrl = ref(null)
const registerError = ref('')

// Gates each field's rules until touched, so rules stay silent on page load — same pattern as ConsumerRegister.vue.
const storeNameTouched = ref(false)
// Set when Continue is pressed without a photo, which turns the upload box red until one is added.
const photoMissing = ref(false)
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

// The six steps in order, named in the progress label.
const STEPS = ['Account', 'Verify phone', 'Store', 'Hours', 'Location', 'Review']
// The weekdays in display order, used by the day toggles and the review summary.
const DAY_ORDER = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']

const step = ref(1)
const stepError = ref('')
const accountForm = ref(null)
const storeForm = ref(null)
// Set when a review Edit link opened a step, so its Continue goes straight back to the review.
const editingFromReview = ref(false)
// The map mounts the first time its step opens, since Leaflet can't size itself inside a hidden panel.
const locationVisited = ref(false)

// Phone verification, where verifiedPhone remembers which number the code proved and the token lets registration use it once.
const phoneVerified = ref(false)
const verifiedPhone = ref('')
const verificationToken = ref('')
const sendingCode = ref(false)
const verifyingCode = ref(false)
const otp = ref(['', '', '', '', '', ''])
const otpRefs = ref([])
const otpError = ref('')
// Turns the boxes green for a moment once the code is accepted, the same flash as the consumer profile's phone check.
const otpVerified = ref(false)
let verifiedTimer = null
const otpComplete = computed(() => otp.value.every(digit => digit !== ''))
const resendTimer = ref(0)
let resendInterval = null

const canRegister = computed(() =>
  phoneVerified.value && verifiedPhone.value === form.phoneNumber &&
  !!form.storeName.trim() &&
  !!form.ownerName && nameRule(form.ownerName) === true &&
  !!form.email && emailRule(form.email) === true &&
  !!form.phoneNumber && phoneRule(form.phoneNumber) === true &&
  !!form.password && passwordRule(form.password) === true &&
  !!form.confirmPassword && form.confirmPassword === form.password &&
  !!form.openingTime && !!form.closingTime && form.operatingDays.length > 0 &&
  !!photoFile.value &&
  !!form.latitude && !!form.longitude
)

const maskedPhone = computed(() => {
  const phone = form.phoneNumber || ''
  return phone.length >= 10 ? `${phone.slice(0, 4)}***${phone.slice(-4)}` : phone
})

const formattedResendTimer = computed(() => `${Math.floor(resendTimer.value / 60)}:${String(resendTimer.value % 60).padStart(2, '0')}`)

const finalAddress = computed(() => form.manualAddress.trim() || form.detectedAddress)
const timeLabel = value => timeOptions.find(option => option.value === value)?.label || value
const hoursSummary = computed(() => (alwaysOpen.value ? 'Always open (24/7)' : `${timeLabel(form.openingTime)} – ${timeLabel(form.closingTime)}`))
const daysSummary = computed(() => DAY_ORDER.filter(day => form.operatingDays.includes(day)).join(', '))

const startResendTimer = () => {
  clearInterval(resendInterval)
  resendTimer.value = 60
  resendInterval = setInterval(() => {
    if (resendTimer.value > 0) resendTimer.value--
    else clearInterval(resendInterval)
  }, 1000)
}

onUnmounted(() => {
  clearInterval(resendInterval)
  clearTimeout(verifiedTimer)
})

// Opens a step, clears the last step's error and brings the top of the form into view.
const goTo = (target) => {
  stepError.value = ''
  step.value = target
  if (target === 5) {
    // Leaflet only re-measures on a window resize, so one is sent when the map's panel is shown again.
    if (locationVisited.value) nextTick(() => window.dispatchEvent(new Event('resize')))
    locationVisited.value = true
  }
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

// Moves on after a step, returning to the review when the step was opened from one of its Edit links.
const afterStep = (next) => {
  const target = editingFromReview.value ? 6 : next
  if (target === 6) editingFromReview.value = false
  goTo(target)
}

const editStep = (target) => {
  editingFromReview.value = true
  goTo(target)
}

// Back skips the verify step once the number is verified, since there is nothing left to do there.
const previousStep = () => {
  editingFromReview.value = false
  goTo(step.value === 3 ? 1 : step.value - 1)
}

const errorMessage = (error, fallback) => {
  if (error.response?.status === 429) return 'Too many attempts. Please wait a few minutes and try again.'
  const errors = error.response?.data?.errors
  return Object.values(errors || {})[0]?.[0] || error.response?.data?.message || fallback
}

// Texts a code to the entered number, which the backend only does for an email and phone not already registered.
const sendCode = async () => {
  sendingCode.value = true
  stepError.value = ''
  otpError.value = ''
  try {
    await api.post('/register/vendor/otp', { email: form.email, phone_number: form.phoneNumber })
    otp.value = ['', '', '', '', '', '']
    otpVerified.value = false
    startResendTimer()
    return true
  } catch (error) {
    const message = errorMessage(error, 'We could not send a code right now. Please try again.')
    if (step.value === 2) otpError.value = message
    else stepError.value = message
    return false
  } finally {
    sendingCode.value = false
  }
}

const submitAccount = async () => {
  ownerNameTouched.value = true
  emailTouched.value = true
  phoneTouched.value = true
  passwordTouched.value = true

  const isValid = await accountForm.value.validate()
  if (!isValid) return

  // Confirm Password isn't part of the form's own :rules, so it needs its own guard here.
  if (!form.confirmPassword || form.confirmPassword !== form.password) {
    stepError.value = 'Please retype the same password.'
    return
  }

  // A number that was already verified skips the code, while a changed number needs a new one.
  if (phoneVerified.value && verifiedPhone.value === form.phoneNumber) {
    afterStep(3)
    return
  }

  phoneVerified.value = false
  otpVerified.value = false
  verificationToken.value = ''
  if (await sendCode()) {
    goTo(2)
    nextTick(() => otpRefs.value[0]?.focus())
  }
}

const resendCode = async () => {
  if (resendTimer.value > 0 || sendingCode.value || otpVerified.value) return
  if (await sendCode()) nextTick(() => otpRefs.value[0]?.focus())
}

const verifyCode = async () => {
  if (!otpComplete.value || verifyingCode.value || otpVerified.value) return
  verifyingCode.value = true
  otpError.value = ''
  try {
    const { data } = await api.post('/otp/verify', {
      phone_number: form.phoneNumber,
      code: otp.value.join(''),
      type: 'registration'
    })
    phoneVerified.value = true
    verifiedPhone.value = form.phoneNumber
    verificationToken.value = data.verification_token || ''
    clearInterval(resendInterval)
    resendTimer.value = 0
    otpVerified.value = true
    // Holds the green boxes briefly, and only moves on if the vendor is still on this step.
    verifiedTimer = setTimeout(() => {
      if (step.value === 2) afterStep(3)
    }, 450)
  } catch (error) {
    otpError.value = errorMessage(error, 'The code you entered is incorrect.')
    otp.value = ['', '', '', '', '', '']
    otpRefs.value[0]?.focus()
  } finally {
    verifyingCode.value = false
  }
}

// Typing moves to the next box, and an autofilled SMS code that lands in one box as a string is spread across all six.
const handleOtpInput = (index) => {
  const digits = otp.value[index].replace(/\D/g, '')

  if (digits.length >= 4) {
    for (let i = 0; i < 6; i++) otp.value[i] = digits[i] || ''
    otpRefs.value[Math.min(digits.length, 5)]?.focus()
    otpError.value = ''
    return
  }

  // Typing into a filled box keeps only the newest digit.
  otp.value[index] = digits.slice(-1)
  if (!digits) return

  otpError.value = ''
  if (index < 5) otpRefs.value[index + 1]?.focus()
}

const handleOtpKeydown = (index, event) => {
  if (event.key === 'Backspace' && !otp.value[index] && index > 0) {
    otpRefs.value[index - 1]?.focus()
  }
}

const handleOtpPaste = (event) => {
  event.preventDefault()
  const pasted = event.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6)
  for (let i = 0; i < 6; i++) otp.value[i] = pasted[i] || ''
  otpRefs.value[Math.min(pasted.length, 5)]?.focus()
  otpError.value = ''
}

// Both checks run before stopping, so a missing name and a missing photo are flagged together.
const nextFromStore = async () => {
  storeNameTouched.value = true
  const nameValid = await storeForm.value.validate()
  photoMissing.value = !photoFile.value
  if (!nameValid || photoMissing.value) return
  afterStep(4)
}

const nextFromHours = () => {
  if (!form.openingTime || !form.closingTime) {
    stepError.value = 'Choose your opening and closing time.'
    return
  }
  if (!form.operatingDays.length) {
    stepError.value = 'Choose at least one day your store is open.'
    return
  }
  afterStep(5)
}

const nextFromLocation = () => {
  if (!form.latitude || !form.longitude) {
    stepError.value = 'Pick your store location on the map.'
    return
  }
  afterStep(6)
}

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
  photoMissing.value = false
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
  if (!canRegister.value || loading.value) return

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
    formData.append('store_name', form.storeName.trim())
    formData.append('full_name', form.ownerName)
    formData.append('email', form.email)
    formData.append('phone_number', form.phoneNumber)
    formData.append('verification_token', verificationToken.value)
    formData.append('password', form.password)
    formData.append('password_confirmation', form.confirmPassword)
    formData.append('opening_time', form.openingTime)
    formData.append('closing_time', form.closingTime)
    formData.append('operating_days', JSON.stringify(schedule))
    formData.append('address', finalAddress.value)
    formData.append('latitude', form.latitude)
    formData.append('longitude', form.longitude)
    formData.append('store_picture', photoFile.value)

    await api.post('/register/vendor', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })

    showSuccess.value = true
  } catch (error) {
    const errors = error.response?.status === 422 ? (error.response.data?.errors || {}) : null

    if (errors?.phone_number) {
      // The verification expired or the number was taken meanwhile, so the phone is verified again before returning to the review.
      phoneVerified.value = false
      otpVerified.value = false
      verificationToken.value = ''
      editingFromReview.value = true
      goTo(2)
      if (await sendCode()) otpError.value = `${errors.phone_number[0]} We sent you a new code.`
    } else if (errors?.email) {
      editingFromReview.value = true
      goTo(1)
      stepError.value = errors.email[0]
    } else if (errors?.store_name || errors?.store_picture) {
      // The store name or photo was refused, so the store step opens again with the reason.
      editingFromReview.value = true
      goTo(3)
      stepError.value = (errors.store_name || errors.store_picture)[0]
    } else if (errors) {
      registerError.value = Object.values(errors)[0]?.[0] || 'Validation failed. Please check your inputs.'
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
  max-width: 600px;
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

/* SECTIONS */

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

/* Red once Continue is pressed without a photo, the same way a missing field turns red. */
.upload-dropzone--error,
.upload-dropzone--error:hover {
  border-color: var(--c-danger);
}

/* Indented 12px to line up with the store name's error, which sits inside Quasar's field padding. */
.photo-error {
  margin-top: 6px;
  padding-left: 12px;

  font-size: var(--fs-xs);
  line-height: 1.4;

  color: var(--c-danger);
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

  animation: success-icon-pop 240ms ease-out;
}

/* The same pop as the consumer profile's success dialog, scaling the tile in as the dialog opens. */
@keyframes success-icon-pop {
  from {
    opacity: 0;
    transform: scale(0.75);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

@media (prefers-reduced-motion: reduce) {
  .success-icon-wrap {
    animation: none;
  }
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

/* WIZARD */

.wizard-progress {
  margin-bottom: 24px;
}

.wizard-step-label {
  margin-bottom: 8px;

  font-size: var(--fs-xs);
  font-weight: 600;

  color: var(--c-text-3);
}

.wizard-bar {
  display: flex;

  gap: 6px;
}

.wizard-bar-segment {
  flex: 1;

  height: 4px;

  border-radius: var(--r-pill);

  background: var(--c-hairline);

  transition: background-color 0.2s;
}

.wizard-bar-segment-done {
  background: var(--c-brand);
}

.step-hint {
  margin: -6px 0 18px;

  font-size: var(--fs-sm);
  line-height: 1.5;

  color: var(--c-text-3);
}

.step-error {
  margin-top: 16px;
}

/* Back and Continue share the row equally, the same pairing as the crop dialog's buttons. */
.wizard-actions {
  display: flex;

  gap: 12px;

  margin-top: 24px;
}

.wizard-actions .q-btn {
  flex: 1 1 0;

  height: 48px;
  margin: 0;
}

.wizard-back {
  border-radius: var(--r-sm);

  color: var(--c-text-2);

  font-family: 'Roboto', Arial, sans-serif;
  font-size: var(--fs-sm);
  font-weight: 600;
}

/* Quasar draws the outline on ::before in the text colour, so the softer border has to be set there. */
.wizard-back::before {
  border-color: var(--c-border-strong);
}

.wizard-back:hover {
  background: var(--c-surface);
}

/* VERIFY PHONE */

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

/* Brief green flash on the digit boxes before the store step, the same as the consumer profile's phone check. */
.otp-box.otp-success {
  border-color: var(--c-success);

  background: var(--c-success-tint);
  color: var(--c-success);

  transition: border-color 0.15s, background-color 0.2s, color 0.2s;
}

.resend-section {
  display: flex;
  align-items: center;
  justify-content: center;

  gap: 4px;

  font-size: var(--fs-xs);
}

.resend-section span {
  color: var(--c-muted);
}

/* Padding cancelled by an equal negative margin grows the tap area to 44px without moving anything. */
.resend-btn {
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

.resend-btn:hover:not(:disabled) {
  text-decoration: underline;
}

.resend-disabled {
  color: var(--c-muted);

  cursor: default;
}

/* REVIEW */

.review-list {
  overflow: hidden;

  border: 1px solid var(--c-border);
  border-radius: var(--r-lg);
}

.review-row {
  display: flex;
  align-items: center;

  gap: 12px;

  padding: 14px 16px;

  border-bottom: 1px solid var(--c-hairline);
}

.review-row:last-child {
  border-bottom: none;
}

.review-body {
  flex: 1;
  min-width: 0;
}

.review-label {
  margin-bottom: 2px;

  font-size: var(--fs-2xs);
  font-weight: 600;
  letter-spacing: 0.03em;
  text-transform: uppercase;

  color: var(--c-muted);
}

.review-value {
  font-size: var(--fs-sm);
  font-weight: 600;

  color: var(--c-text);

  overflow-wrap: anywhere;
}

.review-sub {
  margin-top: 2px;

  font-size: var(--fs-xs);

  color: var(--c-text-3);

  overflow-wrap: anywhere;
}

.review-thumb {
  flex-shrink: 0;

  width: 72px;
  aspect-ratio: 16 / 9;

  border-radius: var(--r-sm);

  object-fit: cover;
}

.review-verified {
  display: inline-flex;
  align-items: center;

  gap: 3px;

  margin-left: 6px;

  font-size: var(--fs-2xs);
  font-weight: 600;

  color: var(--c-success);
}

/* Padding cancelled by an equal negative margin grows the tap area to 44px without moving anything. */
.review-edit {
  flex-shrink: 0;

  padding: 14px 4px;
  margin: -14px 0;

  border: none;
  background: transparent;

  font-family: 'Roboto', Arial, sans-serif;
  font-size: var(--fs-xs);
  font-weight: 600;

  color: var(--c-brand);

  cursor: pointer;
}

.review-edit:hover {
  text-decoration: underline;
}

@media (max-width: 600px) {
  /* Six 48px boxes overflow a phone screen, so they shrink to share the row. */
  .otp-row {
    gap: 8px;
  }

  .otp-box {
    flex: 0 1 48px;
    min-width: 0;
  }
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

/* The red outline of the profile's Edit pill, with Quasar drawing the border in the text colour. */
.crop-cancel {
  color: var(--c-brand);
}

.crop-cancel:hover {
  background: var(--c-brand-tint);
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
