<template>
  <q-page class="profile-page">

    <SiteHeader :address="address" />

    <div class="profile-container">
      <q-btn flat no-caps dense icon="arrow_back" label="Back to Home" class="back-btn" @click="router.push('/consumer/home')" />

      <div class="page-header-block">
        <h1 class="page-title">Profile Settings</h1>
        <p class="page-subtitle">Manage your personal information and security preferences.</p>
      </div>

      <div class="row q-col-gutter-lg">
        
        <!-- ================= PROFILE PHOTO ================= -->
        <div class="col-12 col-md-4">
          <q-card flat bordered class="profile-card">
            <q-card-section>
              <div class="section-title">Profile Photo</div>
              <div class="text-center q-mb-md relative-position">
                <q-avatar size="120px" class="bg-grey-3">
                  <img v-if="photoPreview" :src="photoPreview" />
                  <img v-else-if="user.profile_picture_url" :src="user.profile_picture_url" />
                  <q-icon v-else name="person" size="64px" color="grey-6" />
                </q-avatar>
                
                <input type="file" id="photoUpload" accept="image/*" class="hidden" @change="onFileSelected" style="display: none;" />
                <q-btn round color="primary" icon="edit" class="absolute" style="bottom: 0; right: 25%;" @click="triggerUpload" />
              </div>
              <div class="text-center">
                <q-btn unelevated color="primary" label="Save Photo" :loading="savingPhoto" @click="savePhoto" :disable="!photoFile" class="full-width" />
              </div>
            </q-card-section>
          </q-card>
        </div>

        <div class="col-12 col-md-8">
          
          <!-- ================= PERSONAL INFO ================= -->
          <q-card flat bordered class="profile-card q-mb-lg">
            <q-card-section>
              <div class="section-title">Personal Information</div>
              <div class="row q-col-gutter-md">
                <div class="col-12">
                  <q-input v-model="form.full_name" outlined dense label="Full Name" />
                </div>
                <!-- Address can be added here in the future when the DB supports it -->
              </div>
              <div class="text-right q-mt-md">
                <q-btn unelevated color="primary" label="Save Info" :loading="savingInfo" @click="saveInfo" />
              </div>
            </q-card-section>
          </q-card>

          <!-- ================= PHONE NUMBER (OTP) ================= -->
          <q-card flat bordered class="profile-card q-mb-lg">
            <q-card-section>
              <div class="section-title">Phone Number</div>
              <p class="text-caption text-grey-7">Changing your phone number requires OTP verification.</p>
              <div class="row q-col-gutter-md items-center">
                <div class="col-12 col-sm-8">
                  <q-input v-model="form.phone_number" outlined dense label="Phone Number" hint="Format: 09XXXXXXXXX" />
                </div>
                <div class="col-12 col-sm-4 text-right">
                  <q-btn unelevated color="primary" label="Update Phone" :loading="requestingOtp" @click="requestPhoneOtp" :disable="form.phone_number === user.phone_number" />
                </div>
              </div>
            </q-card-section>
          </q-card>

          <!-- ================= EMAIL ================= -->
          <q-card flat bordered class="profile-card q-mb-lg">
            <q-card-section>
              <div class="section-title">Email Address</div>
              <div class="row q-col-gutter-md items-center">
                <div class="col-12 col-sm-8">
                  <q-input v-model="form.email" outlined dense label="Email" type="email" />
                </div>
                <div class="col-12 col-sm-4 text-right">
                  <q-btn unelevated color="primary" label="Save Email" :loading="savingEmail" @click="saveEmail" :disable="form.email === user.email" />
                </div>
              </div>
            </q-card-section>
          </q-card>

          <!-- ================= SECURITY ================= -->
          <q-card flat bordered class="profile-card q-mb-lg">
            <q-card-section>
              <div class="section-title">Security</div>
              <div class="row q-col-gutter-md">
                <div class="col-12">
                  <q-input v-model="passwords.current" outlined dense label="Current Password" type="password" />
                </div>
                <div class="col-12 col-sm-6">
                  <q-input v-model="passwords.new" outlined dense label="New Password" type="password" />
                </div>
                <div class="col-12 col-sm-6">
                  <q-input v-model="passwords.confirm" outlined dense label="Confirm New Password" type="password" />
                </div>
              </div>
              <div class="text-right q-mt-md">
                <q-btn unelevated color="primary" label="Update Password" :loading="savingPassword" @click="savePassword" />
              </div>
            </q-card-section>
          </q-card>

          <!-- ================= DANGER ZONE ================= -->
          <q-card flat bordered class="profile-card bg-red-1 border-red">
            <q-card-section>
              <div class="section-title text-red-9">Danger Zone</div>
              <p class="text-body2 text-red-9">Once you delete your account, there is no going back. Please be certain.</p>
              <div class="text-right">
                <q-btn outline color="red-9" label="Delete my account" @click="confirmDeleteAccount" />
              </div>
            </q-card-section>
          </q-card>

        </div>
      </div>
    </div>

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
          <q-btn flat label="Cancel" color="negative" @click="showCropModal = false" />
          <q-btn flat label="Apply Crop" color="primary" @click="applyCrop" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- OTP VERIFICATION DIALOG -->
    <q-dialog v-model="showOtpModal" persistent>
      <q-card style="width: 400px; max-width: 90vw;">
        <q-card-section class="text-center q-pt-lg">
          <div class="text-h5 text-weight-bold q-mb-md">Verify New Phone</div>
          <p class="text-body2 text-grey-7">Enter the 6-digit code sent to {{ form.phone_number }}</p>
          
          <div class="row justify-center q-gutter-sm q-mt-lg">
            <q-input
              v-for="(digit, i) in otpInput"
              :key="i"
              v-model="otpInput[i]"
              outlined
              dense
              class="otp-box"
              maxlength="1"
              @update:model-value="val => onOtpInput(val, i)"
              :ref="el => { otpRefs[i] = el }"
            />
          </div>
          <div v-if="otpError" class="text-red q-mt-sm">{{ otpError }}</div>
        </q-card-section>
        <q-card-actions align="center" class="q-pb-lg q-pt-md">
          <q-btn flat label="Cancel" color="grey-7" v-close-popup @click="cancelOtp" />
          <q-btn unelevated color="primary" label="Verify & Save" :loading="verifyingOtp" @click="verifyOtp" class="q-px-lg" />
        </q-card-actions>
      </q-card>
    </q-dialog>

  </q-page>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { useRouter } from 'vue-router'
import { api } from '@/boot/axios'
import { useAuth } from '@/composables/useAuth'
import SiteHeader from '@/components/consumer/SiteHeader.vue'

const $q = useQuasar()
const router = useRouter()
const { logout } = useAuth()

const address = ref('123 Shaw Boulevard, Barangay Pleasant Hills, Mandaluyong City')

const user = ref({})
const form = reactive({
  full_name: '',
  phone_number: '',
  email: ''
})

const passwords = reactive({
  current: '',
  new: '',
  confirm: ''
})

// Loading states
const savingPhoto = ref(false)
const savingInfo = ref(false)
const requestingOtp = ref(false)
const verifyingOtp = ref(false)
const savingEmail = ref(false)
const savingPassword = ref(false)

// Photo Crop State
const photoFile = ref(null)
const photoPreview = ref(null)
const originalPhotoUrl = ref(null)
const showCropModal = ref(false)
const cropCanvas = ref(null)

let imageObj = null
let isDragging = false
const cropRect = reactive({ x: 0, y: 0, w: 0, h: 0 })
const startPos = reactive({ x: 0, y: 0 })

// OTP State
const showOtpModal = ref(false)
const otpInput = ref(['', '', '', '', '', ''])
const otpRefs = ref([])
const otpError = ref('')

onMounted(async () => {
  try {
    const res = await api.get('/user')
    if (res.data && res.data.user) {
      user.value = res.data.user
      form.full_name = user.value.full_name
      form.phone_number = user.value.phone_number
      form.email = user.value.email
    }
  } catch (error) {
    console.error('Failed to load user', error)
  }
})

// --- Photo Crop Logic ---
const triggerUpload = () => {
  document.getElementById('photoUpload').click()
}

const onFileSelected = (event) => {
  const file = event.target.files?.[0]
  if (!file) return
  
  const url = URL.createObjectURL(file)
  originalPhotoUrl.value = url
  
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
  cropRect.h = cropRect.w // Force square
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
  const canvas = cropCanvas.value
  const tempCanvas = document.createElement('canvas')
  tempCanvas.width = cropRect.w
  tempCanvas.height = cropRect.h
  const tCtx = tempCanvas.getContext('2d')
  
  tCtx.drawImage(
    canvas,
    cropRect.x, cropRect.y, cropRect.w, cropRect.h,
    0, 0, cropRect.w, cropRect.h
  )
  
  photoPreview.value = tempCanvas.toDataURL('image/jpeg', 0.9)
  
  tempCanvas.toBlob((blob) => {
    if (blob) {
      photoFile.value = new File([blob], 'profile_cropped.jpg', { type: 'image/jpeg' })
    }
  }, 'image/jpeg', 0.9)
  
  showCropModal.value = false
}

// --- API Calls ---

const savePhoto = async () => {
  if (!photoFile.value) return
  savingPhoto.value = true
  try {
    const fd = new FormData()
    fd.append('profile_picture', photoFile.value)
    const res = await api.post('/profile/photo', fd, { headers: { 'Content-Type': 'multipart/form-data' } })
    user.value.profile_picture_url = res.data.profile_picture_url
    
    // Update local storage so Header avatar updates on refresh
    const lsUser = JSON.parse(localStorage.getItem('auth_user') || '{}')
    lsUser.profile_picture_url = res.data.profile_picture_url
    localStorage.setItem('auth_user', JSON.stringify(lsUser))
    
    $q.notify({ type: 'positive', message: 'Profile photo updated successfully.' })
  } catch (err) {
    $q.notify({ type: 'negative', message: 'Failed to update photo.' })
  } finally {
    savingPhoto.value = false
  }
}

const saveInfo = async () => {
  if (!form.full_name) return
  savingInfo.value = true
  try {
    await api.post('/profile/personal-info', { full_name: form.full_name })
    user.value.full_name = form.full_name
    
    const lsUser = JSON.parse(localStorage.getItem('auth_user') || '{}')
    lsUser.full_name = form.full_name
    localStorage.setItem('auth_user', JSON.stringify(lsUser))
    
    $q.notify({ type: 'positive', message: 'Personal info updated.' })
  } catch (err) {
    $q.notify({ type: 'negative', message: 'Failed to update info.' })
  } finally {
    savingInfo.value = false
  }
}

const requestPhoneOtp = async () => {
  if (form.phone_number === user.value.phone_number) return
  requestingOtp.value = true
  try {
    await api.post('/profile/phone-request-otp', { phone_number: form.phone_number })
    showOtpModal.value = true
    otpInput.value = ['', '', '', '', '', '']
  } catch (err) {
    $q.notify({ type: 'negative', message: err.response?.data?.message || 'Failed to request OTP.' })
  } finally {
    requestingOtp.value = false
  }
}

const onOtpInput = (val, index) => {
  if (val && index < 5) {
    otpRefs.value[index + 1]?.focus()
  }
}

const cancelOtp = () => {
  form.phone_number = user.value.phone_number
  showOtpModal.value = false
}

const verifyOtp = async () => {
  const code = otpInput.value.join('')
  if (code.length !== 6) return
  
  verifyingOtp.value = true
  otpError.value = ''
  try {
    await api.post('/profile/phone-verify-otp', { 
      phone_number: form.phone_number,
      code 
    })
    user.value.phone_number = form.phone_number
    showOtpModal.value = false
    $q.notify({ type: 'positive', message: 'Phone number updated successfully.' })
  } catch (err) {
    otpError.value = err.response?.data?.message || 'Invalid verification code.'
  } finally {
    verifyingOtp.value = false
  }
}

const saveEmail = async () => {
  if (form.email === user.value.email) return
  savingEmail.value = true
  try {
    await api.post('/profile/email', { email: form.email })
    user.value.email = form.email
    $q.notify({ type: 'positive', message: 'Email updated successfully.' })
  } catch (err) {
    $q.notify({ type: 'negative', message: err.response?.data?.message || 'Failed to update email.' })
  } finally {
    savingEmail.value = false
  }
}

const savePassword = async () => {
  if (!passwords.current || !passwords.new || !passwords.confirm) {
    $q.notify({ type: 'warning', message: 'Please fill out all password fields.' })
    return
  }
  if (passwords.new !== passwords.confirm) {
    $q.notify({ type: 'warning', message: 'New passwords do not match.' })
    return
  }
  
  savingPassword.value = true
  try {
    await api.post('/profile/password', {
      current_password: passwords.current,
      new_password: passwords.new,
      new_password_confirmation: passwords.confirm
    })
    passwords.current = ''
    passwords.new = ''
    passwords.confirm = ''
    $q.notify({ type: 'positive', message: 'Password updated successfully.' })
  } catch (err) {
    $q.notify({ type: 'negative', message: err.response?.data?.message || 'Failed to update password.' })
  } finally {
    savingPassword.value = false
  }
}

const confirmDeleteAccount = () => {
  $q.dialog({
    title: 'Delete Account',
    message: 'Are you absolutely sure you want to delete your account? This action cannot be undone.',
    color: 'negative',
    cancel: true,
    persistent: true
  }).onOk(async () => {
    try {
      await api.delete('/profile/delete')
      // Clean up and logout
      localStorage.removeItem('auth_token')
      localStorage.removeItem('auth_user')
      localStorage.removeItem('auth_role')
      router.push('/login')
      $q.notify({ type: 'info', message: 'Your account has been deleted.' })
    } catch (err) {
      $q.notify({ type: 'negative', message: 'Failed to delete account.' })
    }
  })
}
</script>

<style scoped>
.profile-page {
  min-height: 100vh;

  background: #f4f4f4;

  font-family: 'Roboto', Arial, sans-serif;
}

.profile-container {
  max-width: 1000px;

  margin: 0 auto;

  padding: 24px;
}

.back-btn {
  margin-bottom: 16px;
  padding: 0 8px 0 0;

  color: #666666;

  font-size: 13px;
  font-weight: 500;
}

.page-header-block {
  margin-bottom: 28px;
}

.page-title {
  margin: 0 0 4px;

  font-size: 22px;
  font-weight: 700;
  line-height: 1.3;

  color: #111111;
}

.page-subtitle {
  margin: 0;

  font-size: 13px;

  color: #767676;
}

.profile-card {
  border: 1px solid #f0f0f0;
  border-radius: 8px;

  background: #ffffff;

  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.section-title {
  margin-bottom: 16px;

  font-size: 16px;
  font-weight: 700;

  color: #111111;
}

.border-red {
  border-color: #fca5a5;
}

.profile-container :deep(.q-btn) {
  border-radius: 8px;
}

.profile-container :deep(.q-field--outlined .q-field__control) {
  border-radius: 8px;
}

.otp-box {
  width: 48px;
  text-align: center;
  font-size: 20px;
}
.otp-box :deep(.q-field__native) {
  text-align: center;
}

@media (max-width: 600px) {
  .profile-container {
    padding: 16px;
  }
}
</style>
