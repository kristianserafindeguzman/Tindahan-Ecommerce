<template>
  <q-page class="vendor-page" :class="{ 'mobile-page-padding': $q.screen.lt.md }">
    <!-- Subtle Ambient Background Glows -->
    <div class="bg-glow bg-glow-primary desktop-only"></div>
    <div class="bg-glow bg-glow-secondary desktop-only"></div>

    <div class="page-container relative-position" style="z-index: 1">
      
      <!-- PAGE HEADER -->
      <div class="page-header q-mb-lg q-mb-xl-md q-mt-sm">
        <h1 class="text-h5 text-md-h4 text-weight-bolder text-blue-grey-9 q-ma-none tracking-tight">
          Profile Settings
        </h1>
        <p class="text-caption text-md-body1 text-blue-grey-5 q-mt-xs q-mb-none">
          Manage your personal information, store details, and security.
        </p>
      </div>

      <div class="row q-col-gutter-lg items-stretch">
        
        <!-- ================= MY PROFILE ================= -->
        <div class="col-12 col-md-6">
          <q-card class="premium-glass-card profile-card flex column overflow-hidden">
            <q-card-section class="row items-center q-pa-md text-white bg-gradient-red">
              <q-icon name="account_circle" size="22px" class="q-mr-sm" />
              <div class="text-subtitle1 text-weight-bold">My Profile</div>
            </q-card-section>

            <q-card-section class="q-pa-md q-pa-lg-lg col flex column full-height">
              <q-form
                @submit.prevent="openConfirm('Confirm Changes', 'Are you sure you want to save these changes?', submitProfile)"
                class="col flex column full-height"
              >
                <div class="q-gutter-y-md">
                  <div class="row q-col-gutter-sm q-col-gutter-md-md">
                    <div class="col-12 col-sm-6">
                      <q-input v-model="profileForm.firstName" label="First Name" outlined class="custom-glass-input">
                        <template v-slot:prepend><q-icon name="person_outline" size="20px" color="blue-grey-4" /></template>
                      </q-input>
                    </div>
                    <div class="col-12 col-sm-6">
                      <q-input v-model="profileForm.lastName" label="Last Name" outlined class="custom-glass-input">
                        <template v-slot:prepend><q-icon name="person_outline" size="20px" color="blue-grey-4" /></template>
                      </q-input>
                    </div>
                  </div>

                  <q-input v-model="profileForm.email" label="Email Address" type="email" outlined class="custom-glass-input">
                    <template v-slot:prepend><q-icon name="mail_outline" size="20px" color="blue-grey-4" /></template>
                  </q-input>

                  <div class="row items-center q-gutter-sm no-wrap">
                    <q-input v-model="profileForm.phoneNumber" label="Mobile Number" outlined class="custom-glass-input col">
                      <template v-slot:prepend><q-icon name="phone_iphone" size="20px" color="blue-grey-4" /></template>
                    </q-input>
                    <q-btn v-if="phoneChanged" v-ripple label="Verify" outline color="red-9" no-caps class="btn-danger-outline q-px-md" style="height: 56px;" @click="verifyPhoneModal = true" />
                  </div>
                </div>

                <div class="text-right q-mt-auto pt-lg">
                  <q-btn v-ripple type="submit" label="Save Profile" unelevated class="btn-red-gradient text-white q-px-lg q-py-sm text-weight-bold full-width-mobile" no-caps />
                </div>
              </q-form>
            </q-card-section>
          </q-card>
        </div>

        <!-- ================= STORE INFO ================= -->
        <div class="col-12 col-md-6">
          <q-card class="premium-glass-card profile-card flex column overflow-hidden">
            <q-card-section class="row items-center q-pa-md text-white bg-gradient-red">
              <q-icon name="storefront" size="22px" class="q-mr-sm" />
              <div class="text-subtitle1 text-weight-bold">Store Info</div>
            </q-card-section>

            <q-card-section class="q-pa-md q-pa-lg-lg col flex column full-height">
              <q-form
                @submit.prevent="openConfirm('Confirm Changes', 'Are you sure you want to save these changes?', submitStoreInfo)"
                class="col flex column full-height"
              >
                <div class="q-gutter-y-md">
                  <q-input v-model="storeForm.storeName" label="Store Name" outlined class="custom-glass-input">
                    <template v-slot:prepend><q-icon name="store" size="20px" color="blue-grey-4" /></template>
                    <template v-slot:hint><span class="text-blue-grey-4">This name will be visible to neighborhood consumers.</span></template>
                  </q-input>

                  <!-- Upload Controls -->
                  <div class="store-photo-preview-container q-mt-md q-mb-md">
                    <q-img
                      v-if="storePicturePreview"
                      :src="storePicturePreview"
                      :style="{ width: '100%', height: $q.screen.lt.md ? '160px' : '200px', objectFit: 'cover', borderRadius: '8px', border: '1px solid #e2e8f0' }"
                      ratio="16/9"
                    >
                      <template v-slot:error>
                        <div class="absolute-full flex flex-center bg-grey-3 text-grey-7">Error loading image</div>
                      </template>
                    </q-img>

                    <div
                      v-else
                      class="empty-preview flex flex-center bg-grey-2 text-grey-6"
                      :style="{ width: '100%', height: $q.screen.lt.md ? '160px' : '200px', border: '2px dashed #cbd5e1', borderRadius: '8px' }"
                    >
                      <div class="text-center">
                        <q-icon name="storefront" size="40px" color="blue-grey-3" />
                        <div class="q-mt-sm text-caption text-weight-medium">No cover photo uploaded</div>
                      </div>
                    </div>
                  </div>

                  <div class="row items-center justify-between bg-slate-50 q-pa-md rounded-borders border-slate-light">
                    <div>
                      <div class="text-weight-bold text-dark" style="font-size: 14px;">Store Cover Photo</div>
                      <div class="text-caption text-grey-6">Upload a crisp 16:9 photo</div>
                    </div>
                    <q-btn outline color="red-9" class="btn-danger-outline bg-white" label="Update Photo" no-caps @click="showImageCaptureModal = true" :loading="uploadingImage" />
                  </div>
                </div>

                <div class="text-right q-mt-auto pt-lg">
                  <q-btn v-ripple type="submit" label="Save Store Info" unelevated class="btn-red-gradient text-white q-px-lg q-py-sm text-weight-bold full-width-mobile" no-caps />
                </div>
              </q-form>
            </q-card-section>
          </q-card>
        </div>

        <!-- ================= MAP ADDRESS ================= -->
        <div class="col-12 col-md-6">
          <q-card class="premium-glass-card profile-card flex column overflow-hidden">
            <q-card-section class="row items-center q-pa-md text-white bg-gradient-red">
              <q-icon name="place" size="22px" class="q-mr-sm" />
              <div class="text-subtitle1 text-weight-bold">Address & Location</div>
            </q-card-section>

            <q-card-section class="q-pa-md q-pa-lg-lg col flex column full-height">
              <q-form
                @submit.prevent="openConfirm('Confirm Changes', 'Are you sure you want to save these changes?', submitAddress)"
                class="col flex column full-height"
              >
                <div class="q-gutter-y-md">
                  <q-input v-model="addressForm.fullAddress" label="Street Name, Building, House No" type="textarea" :rows="$q.screen.lt.md ? '2' : '3'" outlined class="custom-glass-input">
                    <template v-slot:prepend><q-icon name="map" size="20px" color="blue-grey-4" class="q-mt-sm" /></template>
                  </q-input>

                  <div class="q-mt-md">
                    <!-- Accurate Pin Warning Banner Fixed Layout -->
                    <div class="bg-amber-1 rounded-borders q-pa-md q-mb-md row no-wrap items-start" style="border: 1px solid #fde68a;">
                      <div class="bg-amber-5 text-white flex flex-center rounded-borders q-mr-md" style="width: 36px; height: 36px; min-width: 36px; border-radius: 50%; flex-shrink: 0;">
                        <q-icon name="notifications" size="20px" />
                      </div>
                      <div class="col">
                        <div class="text-subtitle2 text-weight-bold text-amber-9 q-mb-xs" style="font-size: 14px;">Place an accurate pin</div>
                        <div class="text-caption text-blue-grey-8" style="line-height: 1.4; font-size: 12px;">
                          This will serve as the map location for consumers. Please check if it is correct; otherwise, click on the map to adjust it.
                        </div>
                      </div>
                    </div>

                    <div v-if="addressForm.latitude && addressForm.longitude" id="vendor-profile-map" class="rounded-borders shadow-soft" :style="{ height: $q.screen.lt.md ? '250px' : '300px', width: '100%', zIndex: 1 }"></div>
                    <div v-else class="bg-grey-2 rounded-borders flex flex-center shadow-soft full-width column" :style="{ height: $q.screen.lt.md ? '250px' : '300px', border: '2px dashed #cbd5e1' }">
                      <q-icon name="location_off" size="48px" color="blue-grey-3" class="q-mb-md" />
                      <div class="text-weight-bold text-blue-grey-7 text-body1">No Location Detected</div>
                      <div class="text-caption text-blue-grey-5 q-mt-xs">Please detect your current location first.</div>
                    </div>
                  </div>

                  <div class="row justify-end q-mt-sm">
                    <q-btn outline color="red-9" icon="my_location" label="Detect Current Location" @click="detectLocation" :loading="isDetectingLocation" no-caps class="btn-danger-outline bg-white text-weight-bold q-px-md q-py-xs full-width-mobile" />
                  </div>
                </div>

                <div class="text-right q-mt-auto pt-lg">
                  <q-btn v-ripple type="submit" label="Save Address" unelevated class="btn-red-gradient text-white q-px-lg q-py-sm text-weight-bold full-width-mobile" no-caps />
                </div>
              </q-form>
            </q-card-section>
          </q-card>
        </div>

        <!-- ================= PASSWORD ================= -->
        <div class="col-12 col-md-6">
          <q-card class="premium-glass-card profile-card flex column overflow-hidden">
            <q-card-section class="row items-center q-pa-md text-white bg-gradient-red">
              <q-icon name="security" size="22px" class="q-mr-sm" />
              <div class="text-subtitle1 text-weight-bold">Security & Password</div>
            </q-card-section>

            <q-card-section class="q-pa-md q-pa-lg-lg col flex column full-height">
              <q-form
                @submit.prevent="openConfirm('Confirm Changes', 'Are you sure you want to update your password? A confirmation will be sent to your email.', submitPassword)"
                class="col flex column full-height"
              >
                <div class="q-gutter-y-md">
                  <q-input v-model="passwordForm.current" label="Current Password" type="password" outlined class="custom-glass-input">
                    <template v-slot:prepend><q-icon name="lock_outline" size="20px" color="blue-grey-4" /></template>
                  </q-input>

                  <q-input v-model="passwordForm.new" label="New Password" type="password" outlined class="custom-glass-input">
                    <template v-slot:prepend><q-icon name="key" size="20px" color="blue-grey-4" /></template>
                  </q-input>

                  <q-input v-model="passwordForm.confirm" label="Confirm New Password" type="password" outlined class="custom-glass-input">
                    <template v-slot:prepend><q-icon name="verified_user" size="20px" color="blue-grey-4" /></template>
                  </q-input>
                </div>

                <div class="text-right q-mt-auto pt-lg">
                  <q-btn v-ripple type="submit" label="Update Password" unelevated class="btn-red-gradient text-white q-px-lg q-py-sm text-weight-bold full-width-mobile" no-caps />
                </div>
              </q-form>
            </q-card-section>
          </q-card>
        </div>

        <!-- ================= STORE HOURS (Full Width) ================= -->
        <div class="col-12">
          <q-card class="premium-glass-card flex column overflow-hidden">
            <q-card-section class="row items-center justify-between q-pa-md text-white bg-gradient-red">
              <div class="row items-center">
                <q-icon name="schedule" size="22px" class="q-mr-sm" />
                <div class="text-subtitle1 text-weight-bold">Store Hours</div>
              </div>
              <q-btn flat dense icon="content_copy" :label="$q.screen.lt.md ? 'Apply Mon' : 'Apply Monday to All'" class="text-white text-weight-bold bg-white-20 rounded-borders q-px-sm" no-caps @click="applyMondayToAll">
                <q-tooltip class="bg-red-9">Copy Monday's schedule to all other days</q-tooltip>
              </q-btn>
            </q-card-section>

            <q-card-section class="q-pa-md q-pa-lg-lg col flex column full-height">
              <q-form @submit.prevent="openConfirm('Confirm Changes', 'Are you sure you want to save these changes?', submitStoreHours)" class="col flex column full-height q-gutter-y-sm">
                
                <div class="row items-center q-col-gutter-sm schedule-row q-pa-sm q-pa-md-md rounded-borders border-bottom-mobile" v-for="day in operatingDays" :key="day.name">
                  <!-- Day Name -->
                  <div class="col-6 col-sm-3 row items-center no-wrap">
                    <div class="text-subtitle2 text-weight-bold text-blue-grey-9 q-mr-sm">{{ day.name }}</div>
                  </div>
                  
                  <!-- Toggle -->
                  <div class="col-6 col-sm-3 text-right text-sm-left row items-center justify-end justify-sm-start no-wrap">
                    <q-toggle v-model="day.isOpen" color="red-9" keep-color />
                    <span class="text-body2 text-weight-bold q-ml-xs" :class="day.isOpen ? 'text-red-9' : 'text-blue-grey-4'">
                      {{ day.isOpen ? 'Open' : 'Closed' }}
                    </span>
                  </div>

                  <!-- Time Inputs -->
                  <div class="col-12 col-sm-6 row q-gutter-x-sm items-center q-mt-sm q-mt-sm-none" v-if="day.isOpen">
                    <q-input v-model="day.openTime" type="time" outlined dense class="custom-glass-input col" />
                    <div class="text-center text-blue-grey-4 text-weight-bolder">—</div>
                    <q-input v-model="day.closeTime" type="time" outlined dense class="custom-glass-input col" />
                  </div>

                  <!-- Closed State Placeholder -->
                  <div class="col-12 col-sm-6 flex items-center q-mt-sm q-mt-sm-none" v-else>
                    <div class="text-blue-grey-5 text-caption text-weight-medium bg-slate-50 q-px-sm q-py-xs rounded-borders w-full text-center border-slate-light" style="border: 1px dashed #cbd5e1;">
                      Closed
                    </div>
                  </div>
                </div>

                <div class="text-right q-mt-lg pt-md">
                  <q-btn v-ripple type="submit" label="Save Store Hours" unelevated class="btn-red-gradient text-white q-px-lg q-py-sm text-weight-bold full-width-mobile" no-caps />
                </div>
              </q-form>
            </q-card-section>
          </q-card>
        </div>

        <!-- ================= DELETE ACCOUNT ================= -->
        <div class="col-12">
          <q-card class="delete-zone-card overflow-hidden shadow-soft">
            <q-card-section class="row items-center q-pa-md text-white bg-gradient-red">
              <q-icon name="warning_amber" size="22px" class="q-mr-sm" />
              <div class="text-subtitle1 text-weight-bold">Danger Zone</div>
            </q-card-section>
            
            <q-card-section class="q-pa-md q-pa-lg-lg row items-center justify-between">
              <div class="column col-12 col-md-8 q-mb-md q-mb-md-none">
                <div class="text-h6 text-weight-bold text-red-9 q-mb-xs">Delete Account</div>
                <div class="text-blue-grey-8 text-body2" style="max-width: 500px;">
                  Once you delete your account, there is no going back. All inventory, data, and sales records will be permanently lost.
                </div>
              </div>
              <div class="col-12 col-md-auto text-left text-md-right">
                <q-btn v-ripple label="Delete Account" color="red-9" outline no-caps class="btn-danger-outline q-px-xl q-py-sm text-weight-bold full-width-mobile" @click="initiateDelete" />
              </div>
            </q-card-section>
          </q-card>
        </div>

      </div>
    </div>

    <!-- ================= MODALS ================= -->
    <q-dialog v-model="confirmDialog.isOpen" persistent backdrop-filter="blur(4px)">
      <q-card class="premium-glass-card q-pa-md" style="width: 420px; max-width: 90vw; border-top: 4px solid #b91c1c; height: auto !important;">
        <q-card-section class="column items-center text-center q-pb-none">
          <div class="text-h6 text-weight-bolder text-blue-grey-9 q-mb-sm">{{ confirmDialog.title }}</div>
          <div class="text-body2 text-blue-grey-8 q-mb-md">{{ confirmDialog.message }}</div>
        </q-card-section>
        <q-card-actions align="center" class="q-mt-sm q-mb-sm q-gutter-md">
          <q-btn label="CANCEL" outline color="blue-grey-4" class="text-weight-bold q-px-lg text-blue-grey-7" no-caps v-close-popup />
          <q-btn label="CONFIRM" unelevated class="btn-red-gradient text-white q-px-xl text-weight-bold" no-caps @click="executeConfirmAction" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <ImageCaptureModal v-model="showImageCaptureModal" @captured="handleCapturedImage" :aspectRatio="16 / 9" title="Update Store Cover Photo" />

    <q-dialog v-model="deleteDialog.isOpen" persistent backdrop-filter="blur(8px)">
      <q-card class="premium-glass-card q-pa-lg" style="width: 520px; max-width: 95vw; border-top: 4px solid #b91c1c; height: auto !important;">
        <q-card-section class="text-center q-pb-none">
          <div class="text-h5 text-weight-bolder text-red-9 q-mb-md">Delete Merchant Account</div>
          <div class="text-body1 text-weight-bold text-red-8 q-mb-xs">Do you really want to delete your Merchant Account?</div>
          <div class="text-body2 text-blue-grey-7 q-mb-lg">If yes, please type your store name to confirm.</div>
          <q-input v-model="deleteDialog.inputName" placeholder="Type your store name" outlined class="custom-glass-input q-mb-lg center-input-text" />
        </q-card-section>
        <q-card-actions align="center" class="q-mt-xs q-gutter-md">
          <q-btn label="Back" outline color="blue-grey-4" class="btn-glass-outline text-blue-grey-8 q-px-xl text-weight-bold" no-caps v-close-popup />
          <q-btn label="Yes, Delete" unelevated class="btn-red-gradient text-white q-px-xl text-weight-bold" no-caps :disable="deleteDialog.inputName !== storeForm.storeName || !storeForm.storeName" @click="executeDeleteAccount" />
        </q-card-actions>
      </q-card>
    </q-dialog>

  </q-page>
</template>

<script setup>
import { ref, reactive, computed, onMounted, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { api } from '@/boot/axios'
import ImageCaptureModal from '@/components/modals/ImageCaptureModal.vue'
import { useAuth } from '@/composables/useAuth'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

const $q = useQuasar()
const authStore = useAuth()
const router = useRouter()

// Form States
const profileForm = reactive({
  firstName: '',
  lastName: '',
  email: '',
  phoneNumber: ''
})
const storeForm = reactive({
  storeName: ''
})
const storePicturePreview = ref(null)
const newStorePictureFile = ref(null)

const uploadingImage = ref(false)
const showImageCaptureModal = ref(false)
const addressForm = reactive({
  fullAddress: '',
  latitude: null,
  longitude: null
})
const passwordForm = reactive({ current: '', new: '', confirm: '' })

const originalPhone = ref('')
const phoneChanged = computed(
  () => profileForm.phoneNumber !== originalPhone.value
)
const verifyPhoneModal = ref(false)

const operatingDays = reactive([
  { name: 'Monday', isOpen: true, openTime: '08:00', closeTime: '17:00' },
  { name: 'Tuesday', isOpen: true, openTime: '08:00', closeTime: '17:00' },
  { name: 'Wednesday', isOpen: true, openTime: '08:00', closeTime: '17:00' },
  { name: 'Thursday', isOpen: true, openTime: '08:00', closeTime: '17:00' },
  { name: 'Friday', isOpen: true, openTime: '08:00', closeTime: '17:00' },
  { name: 'Saturday', isOpen: false, openTime: '08:00', closeTime: '17:00' },
  { name: 'Sunday', isOpen: false, openTime: '08:00', closeTime: '17:00' }
])

const applyMondayToAll = () => {
  const monday = operatingDays[0]
  for (let i = 1; i < operatingDays.length; i++) {
    operatingDays[i].isOpen = monday.isOpen
    operatingDays[i].openTime = monday.openTime
    operatingDays[i].closeTime = monday.closeTime
  }
  $q.notify({
    type: 'positive',
    message: 'Monday hours applied to all days.',
    color: 'red-9'
  })
}

// ================= MODAL LOGIC =================
const confirmDialog = reactive({
  isOpen: false,
  title: '',
  message: '',
  action: null
})

const deleteDialog = reactive({
  isOpen: false,
  inputName: ''
})

const openConfirm = (title, message, actionCallback) => {
  confirmDialog.title = title
  confirmDialog.message = message
  confirmDialog.action = actionCallback
  confirmDialog.isOpen = true
}

const executeConfirmAction = async () => {
  if (confirmDialog.action) {
    await confirmDialog.action()
  }
  confirmDialog.isOpen = false
}

const initiateDelete = () => {
  deleteDialog.inputName = ''
  deleteDialog.isOpen = true
}

// ================= API SUBMISSION LOGIC =================
const submitProfile = async () => {
  try {
    await api.put('/vendor/profile', {
      first_name: profileForm.firstName,
      last_name: profileForm.lastName,
      email: profileForm.email,
      phone_number: profileForm.phoneNumber
    })
    $q.notify({
      type: 'positive',
      message: 'Profile saved successfully.',
      color: 'green-7'
    })
  } catch (error) {
    $q.notify({ type: 'negative', message: 'Failed to update profile.' })
  }
}

const submitStoreInfo = async () => {
  try {
    const formData = new FormData()
    formData.append('store_name', storeForm.storeName)
    if (newStorePictureFile.value) {
      formData.append(
        'store_picture',
        newStorePictureFile.value,
        'store_cover.jpg'
      )
    }
    formData.append('_method', 'PUT')

    const response = await api.post('/vendor/store/info', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })

    if (response.data && response.data.store_picture_url) {
      const newUrl = response.data.store_picture_url
      storePicturePreview.value = newUrl
      if (authStore.user && authStore.user.store) {
        authStore.user.store.store_picture_url = newUrl
      }
      newStorePictureFile.value = null
    }

    $q.notify({
      type: 'positive',
      message: 'Store Info saved successfully.',
      color: 'green-7'
    })
  } catch (error) {
    const errorMsg =
      error.response?.data?.message || 'Failed to update store info.'
    $q.notify({ type: 'negative', message: errorMsg })
  }
}

const handleCapturedImage = ({ file }) => {
  if (!file) return

  // Instant local preview from the blob
  storePicturePreview.value = URL.createObjectURL(file)

  // Store the file to be uploaded later when saving Store Info
  newStorePictureFile.value = file

  $q.notify({
    type: 'info',
    message:
      'Photo uploaded successfully! Please click "Save Store Info" to apply changes.',
    color: 'blue-8',
    icon: 'info'
  })
}

const submitStoreHours = async () => {
  try {
    for (const day of operatingDays) {
      if (day.isOpen && day.openTime && day.closeTime) {
        if (day.closeTime <= day.openTime) {
          $q.notify({ type: 'warning', message: `Invalid times for ${day.name}. Closing time must be later than opening time.` })
          return
        }
      }
    }

    await api.put('/vendor/profile/hours', { operatingDays })
    $q.notify({
      type: 'positive',
      message: 'Store Hours saved successfully.',
      color: 'green-7'
    })
  } catch (error) {
    $q.notify({ type: 'negative', message: 'Failed to save store hours.' })
  }
}

const submitAddress = async () => {
  try {
    await api.put('/vendor/store/address', {
      address: addressForm.fullAddress,
      latitude: addressForm.latitude,
      longitude: addressForm.longitude
    })
    $q.notify({
      type: 'positive',
      message: 'Address saved successfully.',
      color: 'green-7'
    })
  } catch (error) {
    $q.notify({ type: 'negative', message: 'Failed to save address.' })
  }
}

const submitPassword = async () => {
  try {
    await api.put('/vendor/profile/password', {
      current_password: passwordForm.current,
      new_password: passwordForm.new,
      new_password_confirmation: passwordForm.confirm
    })
    $q.notify({
      type: 'positive',
      message: 'Password updated successfully.',
      color: 'green-7'
    })
    passwordForm.current = ''
    passwordForm.new = ''
    passwordForm.confirm = ''
  } catch (error) {
    $q.notify({ type: 'negative', message: 'Failed to update password.' })
  }
}

const executeDeleteAccount = async () => {
  try {
    await api.delete('/vendor/account')
    $q.notify({ type: 'positive', message: 'Account deleted.' })
    localStorage.clear()
    window.location.href = '/login'
  } catch (error) {
    $q.notify({ type: 'negative', message: 'Failed to delete account.' })
  }
}

// Fetch Initial Data
const fetchProfile = async () => {
  try {
    const res = await api.get('/vendor/profile')
    const data = res.data
    if (data) {
      if (data.full_name) {
        const parts = data.full_name.split(' ')
        profileForm.firstName = parts[0]
        profileForm.lastName = parts.slice(1).join(' ')
      }
      profileForm.email = data.email || ''
      profileForm.phoneNumber = data.phone_number || ''
      originalPhone.value = profileForm.phoneNumber
      if (data.store) {
        storeForm.storeName = data.store.store_name || ''

        if (data.store && data.store.store_picture_url) {
          storePicturePreview.value = data.store.store_picture_url
        } else if (data.store && data.store.store_picture) {
          storePicturePreview.value = `/storage/${data.store.store_picture}`
        } else {
          storePicturePreview.value = null
        }
        addressForm.fullAddress = data.store.address || ''
        addressForm.latitude = data.store.latitude || null
        addressForm.longitude = data.store.longitude || null

        if (data.store.operating_days) {
          let raw = data.store.operating_days
          if (typeof raw === 'string') {
            try {
              raw = JSON.parse(raw)
            } catch (e) {}
          }
          const defaultOpen = data.store.opening_time
            ? data.store.opening_time.substring(0, 5)
            : '08:00'
          const defaultClose = data.store.closing_time
            ? data.store.closing_time.substring(0, 5)
            : '17:00'

          if (raw !== null && typeof raw === 'object' && !Array.isArray(raw)) {
            operatingDays.forEach(day => {
              if (raw[day.name]) {
                day.isOpen = !!raw[day.name].is_open
                if (raw[day.name].opening_time)
                  day.openTime = raw[day.name].opening_time.substring(0, 5)
                if (raw[day.name].closing_time)
                  day.closeTime = raw[day.name].closing_time.substring(0, 5)
              }
            })
          } else if (Array.isArray(raw)) {
            operatingDays.forEach(day => {
              day.isOpen = raw.some(d =>
                day.name.toLowerCase().startsWith(String(d).toLowerCase())
              )
              day.openTime = defaultOpen
              day.closeTime = defaultClose
            })
          }
        }
      }
    }
  } catch (error) {
    console.error('Failed to load profile data', error)
  }
}

const map = ref(null)
const marker = ref(null)
const isDetectingLocation = ref(false)

const initMap = () => {
  if (!addressForm.latitude || !addressForm.longitude) {
    if (map.value) {
      map.value.remove()
      map.value = null
    }
    return
  }

  const lat = addressForm.latitude
  const lng = addressForm.longitude

  if (map.value) {
    map.value.remove()
  }

  map.value = L.map('vendor-profile-map').setView([lat, lng], 15)

  L.tileLayer(
    'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png',
    {
      attribution: '&copy; OpenStreetMap contributors &copy; CARTO'
    }
  ).addTo(map.value)

  const icon = L.icon({
    iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
    iconRetinaUrl:
      'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
    shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34],
    shadowSize: [41, 41]
  })

  marker.value = L.marker([lat, lng], { icon }).addTo(map.value)
  
  // Update pin location smoothly when map is clicked
  map.value.on('click', function(e) {
    const newLat = e.latlng.lat;
    const newLng = e.latlng.lng;
    addressForm.latitude = newLat;
    addressForm.longitude = newLng;
    marker.value.setLatLng([newLat, newLng]);
  });
}

const detectLocation = () => {
  if (!navigator.geolocation) {
    $q.notify({
      type: 'negative',
      message: 'Geolocation is not supported by your browser.'
    })
    return
  }

  isDetectingLocation.value = true
  navigator.geolocation.getCurrentPosition(
    position => {
      addressForm.latitude = position.coords.latitude
      addressForm.longitude = position.coords.longitude

      nextTick(() => {
        if (map.value && marker.value) {
          map.value.setView([addressForm.latitude, addressForm.longitude], 15)
          marker.value.setLatLng([addressForm.latitude, addressForm.longitude])
        } else {
          initMap()
        }
      })

      $q.notify({
        type: 'positive',
        message: 'Location detected successfully.',
        color: 'green'
      })
      isDetectingLocation.value = false
    },
    error => {
      console.error(error)
      $q.notify({ type: 'negative', message: 'Failed to detect location.' })
      isDetectingLocation.value = false
    }
  )
}

onMounted(async () => {
  await fetchProfile()
  nextTick(() => {
    initMap()
  })
})
</script>

<style scoped>
/* Core Page Styling */
.vendor-page {
  padding: 32px 24px;
  background-color: #f8fafc;
  min-height: 100vh;
}
.page-container {
  max-width: 1000px;
  margin: 0 auto;
}
.pt-lg { padding-top: 24px; }
.pt-md { padding-top: 16px; }
.w-full { width: 100%; }
.shrink-none { flex-shrink: 0; }

/* Strict Brand Red Gradient Class (No Pink) */
.bg-gradient-red {
  background: linear-gradient(135deg, #b91c1c 0%, #7f1d1d 100%) !important;
}
.bg-white-20 {
  background: rgba(255, 255, 255, 0.2);
}
.bg-white-20:hover {
  background: rgba(255, 255, 255, 0.3);
}

/* Subtle Ambient Glows */
.bg-glow {
  position: absolute; width: 500px; height: 500px; border-radius: 50%;
  filter: blur(140px); z-index: 0; opacity: 0.15; pointer-events: none;
}
.bg-glow-primary { top: -100px; left: -100px; background: radial-gradient(circle, rgba(185, 28, 28, 0.4) 0%, transparent 70%); }
.bg-glow-secondary { bottom: 100px; right: -100px; background: radial-gradient(circle, rgba(69, 10, 10, 0.3) 0%, transparent 70%); }

.tracking-tight { letter-spacing: -0.02em; }

/* Clean Glassmorphism Cards */
.premium-glass-card {
  background: rgba(255, 255, 255, 0.98);
  border: 1px solid rgba(241, 245, 249, 1);
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(15, 23, 42, 0.05);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.premium-glass-card:hover {
  box-shadow: 0 6px 28px rgba(15, 23, 42, 0.08);
}
.profile-card { height: 100%; }

/* Custom Glass Inputs */
.custom-glass-input :deep(.q-field__control) {
  background: rgba(248, 250, 252, 0.8);
  border-radius: 8px;
  transition: all 0.3s ease;
}
.custom-glass-input :deep(.q-field__control:before) { border: 1px solid rgba(226, 232, 240, 0.8); }
.custom-glass-input :deep(.q-field__control:hover) { background: rgba(241, 245, 249, 1); }
.custom-glass-input :deep(.q-field--focused .q-field__control) {
  background: #ffffff;
  box-shadow: 0 2px 10px rgba(185, 28, 28, 0.06);
}
.custom-glass-input :deep(.q-field--focused .q-icon) { color: #b91c1c !important; }
.center-input-text :deep(.q-field__native) { text-align: center; font-weight: bold; font-size: 1.1rem; }

/* Primary Accent Buttons (Strict Red Gradient) */
.btn-red-gradient {
  border-radius: 8px !important;
  background: linear-gradient(135deg, #b91c1c 0%, #7f1d1d 100%) !important;
  box-shadow: 0 4px 12px rgba(185, 28, 28, 0.2) !important;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}
.btn-red-gradient:hover:not(.disabled) {
  box-shadow: 0 6px 16px rgba(185, 28, 28, 0.3) !important;
  transform: translateY(-1px);
}
.btn-glass-outline {
  border-radius: 8px !important; background: rgba(255, 255, 255, 0.8) !important;
  border: 1px solid currentColor; transition: all 0.2s ease;
}
.btn-glass-outline:hover { background: rgba(241, 245, 249, 0.9) !important; transform: translateY(-1px); }
.btn-danger-outline {
  border-radius: 8px !important; background: #ffffff !important;
  border: 1px solid #b91c1c !important; color: #b91c1c !important; transition: all 0.2s ease;
}
.btn-danger-outline:hover { background: #fef2f2 !important; transform: translateY(-1px); }

/* Delete Zone */
.delete-zone-card {
  background: #fffafa; border: 1px solid #fee2e2; border-radius: 12px;
}

/* Store Hours Table */
.schedule-row { transition: background-color 0.2s ease; }
.schedule-row:hover { background-color: rgba(248, 250, 252, 0.8); }
.bg-slate-50 { background-color: #f8fafc; }
.border-slate-light { border: 1px solid #f1f5f9; }

/* Mobile Only Adjustments */
@media (max-width: 767px) {
  .vendor-page.mobile-page-padding { padding: 20px 12px !important; }
  .desktop-only { display: none !important; }
  .full-width-mobile { width: 100%; }
  .border-bottom-mobile { border-bottom: 1px solid rgba(15,23,42, 0.05); }
}
</style>