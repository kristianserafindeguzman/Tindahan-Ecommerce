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
                v-model="form.phoneNumber"
                outlined
                dense
                label="Mobile number"
                class="login-input"
                :rules="[
                  val => !!val || 'Mobile number is required',
                  phoneRule
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

            <!-- PROFILE PHOTO -->
            <div class="field-group q-mt-sm">
              <div class="text-caption q-mb-xs">Profile Photo (Optional)</div>
              
              <input
                type="file"
                id="consumerPhoto"
                accept="image/*"
                @change="handlePhotoChange"
                style="display: none;"
              />
              
              <label for="consumerPhoto" class="upload-area" :class="{ 'has-preview': photoPreview }">
                <div v-if="!photoPreview" class="upload-placeholder">
                  <q-icon name="add_a_photo" size="24px" color="grey-6" />
                  <div class="text-caption text-grey-6 q-mt-xs">Upload Photo</div>
                </div>
                <div v-else class="preview-container">
                  <img :src="photoPreview" alt="Profile Preview" class="photo-preview" />
                  <div class="preview-overlay" @click.prevent="openCropModal">
                    <q-icon name="crop" size="18px" />
                    <span>Crop</span>
                  </div>
                </div>
              </label>

              <div v-if="photoFile" class="photo-info text-center q-mt-xs">
                <span class="text-caption">{{ photoFile.name }}</span>
                <q-btn flat dense icon="close" size="sm" color="negative" @click="removePhoto" class="q-ml-sm" />
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

    <!-- CROP DIALOG -->
    <q-dialog v-model="showCropModal" persistent>
      <q-card style="width: 500px; max-width: 90vw;">
        <q-card-section>
          <div class="text-h6">Crop Profile Photo</div>
        </q-card-section>
        <q-card-section style="text-align: center;">
          <canvas
            ref="cropCanvas"
            style="border: 1px dashed #ccc; cursor: crosshair; max-width: 100%;"
            @mousedown="onCropMouseDown"
            @mousemove="onCropMouseMove"
            @mouseup="onCropMouseUp"
            @mouseleave="onCropMouseUp"
          ></canvas>
          <div class="text-caption q-mt-sm">Drag to select a square crop area.</div>
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat label="Cancel" color="primary" @click="showCropModal = false" />
          <q-btn flat label="Apply Crop" color="primary" @click="applyCrop" />
        </q-card-actions>
      </q-card>
    </q-dialog>

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
const photoPreview = ref(null)
const originalPhotoUrl = ref(null)

// Legal modals
const showTerms = ref(false)
const showPrivacy = ref(false)

// Crop state
const showCropModal = ref(false)
const cropCanvas = ref(null)
let imageObj = null
let isDragging = false
const cropRect = reactive({ x: 0, y: 0, w: 0, h: 0 })
const startPos = reactive({ x: 0, y: 0 })

const form = reactive({
  firstName: '',
  lastName: '',
  email: '',
  phoneNumber: '',
  password: '',
  confirmPassword: ''
})

const photoFile = ref(null)

const nameRule = val =>
  /^[A-Za-zÀ-ÖØ-öø-ÿ' -]+$/.test(val) || 'Only letters are allowed'

const emailRule = val => /.+@.+\..+/.test(val) || 'Enter a valid email'
const phoneRule = val => /^09\d{9}$/.test(val) || 'Phone must be exactly 11 digits starting with 09'
const passwordRule = val => val.length >= 8 || 'Minimum 8 characters'

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
  showCropModal.value = true
  setTimeout(() => {
    const canvas = cropCanvas.value
    if (!canvas) return
    const ctx = canvas.getContext('2d')
    imageObj = new Image()
    imageObj.onload = () => {
      const maxW = 400
      let w = imageObj.width
      let h = imageObj.height
      if (w > maxW) {
        h = (h * maxW) / w
        w = maxW
      }
      canvas.width = w
      canvas.height = h
      ctx.drawImage(imageObj, 0, 0, w, h)
      cropRect.x = 0; cropRect.y = 0; cropRect.w = w; cropRect.h = h
      drawCropCanvas()
    }
    imageObj.src = originalPhotoUrl.value
  }, 100)
}

const drawCropCanvas = () => {
  const canvas = cropCanvas.value
  if (!canvas || !imageObj) return
  const ctx = canvas.getContext('2d')
  ctx.clearRect(0, 0, canvas.width, canvas.height)
  ctx.drawImage(imageObj, 0, 0, canvas.width, canvas.height)
  
  ctx.fillStyle = 'rgba(0, 0, 0, 0.5)'
  ctx.fillRect(0, 0, canvas.width, canvas.height)
  
  if (cropRect.w > 0 && cropRect.h > 0) {
    ctx.clearRect(cropRect.x, cropRect.y, cropRect.w, cropRect.h)
    ctx.drawImage(imageObj, 
      (cropRect.x / canvas.width) * imageObj.width, 
      (cropRect.y / canvas.height) * imageObj.height, 
      (cropRect.w / canvas.width) * imageObj.width, 
      (cropRect.h / canvas.height) * imageObj.height, 
      cropRect.x, cropRect.y, cropRect.w, cropRect.h)
    
    ctx.strokeStyle = '#fff'
    ctx.lineWidth = 2
    ctx.strokeRect(cropRect.x, cropRect.y, cropRect.w, cropRect.h)
  }
}

const onCropMouseDown = (e) => {
  isDragging = true
  const rect = cropCanvas.value.getBoundingClientRect()
  startPos.x = e.clientX - rect.left
  startPos.y = e.clientY - rect.top
  cropRect.x = startPos.x
  cropRect.y = startPos.y
  cropRect.w = 0
  cropRect.h = 0
}

const onCropMouseMove = (e) => {
  if (!isDragging) return
  const rect = cropCanvas.value.getBoundingClientRect()
  const mouseX = e.clientX - rect.left
  const mouseY = e.clientY - rect.top
  cropRect.w = mouseX - startPos.x
  
  // Force square aspect ratio
  cropRect.h = cropRect.w
  
  drawCropCanvas()
}

const onCropMouseUp = () => {
  if (isDragging) {
    if (cropRect.w < 0) {
      cropRect.x += cropRect.w
      cropRect.w = Math.abs(cropRect.w)
      cropRect.h = Math.abs(cropRect.h)
    }
    isDragging = false
  }
}

const applyCrop = () => {
  if (cropRect.w <= 0 || cropRect.h <= 0) {
    showCropModal.value = false
    return
  }
  
  const canvas = cropCanvas.value
  const scaleX = imageObj.width / canvas.width
  const scaleY = imageObj.height / canvas.height
  
  const tempCanvas = document.createElement('canvas')
  tempCanvas.width = cropRect.w * scaleX
  tempCanvas.height = cropRect.h * scaleY
  const ctx = tempCanvas.getContext('2d')
  ctx.drawImage(imageObj, 
    cropRect.x * scaleX, cropRect.y * scaleY, cropRect.w * scaleX, cropRect.h * scaleY, 
    0, 0, tempCanvas.width, tempCanvas.height)
    
  tempCanvas.toBlob((blob) => {
    if (blob) {
      const croppedFile = new File([blob], 'cropped_' + photoFile.value.name, { type: 'image/jpeg' })
      photoFile.value = croppedFile
      photoPreview.value = URL.createObjectURL(croppedFile)
      showCropModal.value = false
    }
  }, 'image/jpeg', 0.9)
}

const removePhoto = () => {
  photoFile.value = null
  photoPreview.value = null
  const input = document.getElementById('consumerPhoto')
  if (input) input.value = ''
}

const handleRegister = async () => {
  const isValid = await registerForm.value.validate()

  if (!isValid) {
    return
  }

  loading.value = true
  registerError.value = ''

  try {
    const formData = new FormData()
    formData.append('full_name', `${form.firstName} ${form.lastName}`)
    formData.append('email', form.email)
    formData.append('phone_number', form.phoneNumber)
    formData.append('password', form.password)
    formData.append('password_confirmation', form.confirmPassword)
    
    if (photoFile.value) {
      const fileName = photoFile.value.name || 'profile.jpg'
      formData.append('profile_picture', photoFile.value, fileName)
    }

    await api.post('/register/consumer', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })

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
   UPLOAD AREA (from VendorRegister)
========================= */
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
