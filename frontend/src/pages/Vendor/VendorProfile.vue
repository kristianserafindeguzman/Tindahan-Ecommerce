<template>
  <q-page class="vendor-page relative-position">
    <!-- Subtle Ambient Background Glows -->
    <div class="bg-glow bg-glow-primary"></div>
    <div class="bg-glow bg-glow-secondary"></div>

<div class="page-container relative-position" style="z-index: 1;">
      
      <div class="page-header q-mb-xl q-mt-sm">
        <h1 class="text-h4 text-weight-bolder text-blue-grey-9 q-ma-none tracking-tight">Profile Settings</h1>
        <p class="text-body1 text-blue-grey-5 q-mt-xs">Manage your personal information, store details, and security.</p>
      </div>

      <div class="row q-col-gutter-lg items-stretch">
        
        <!-- ================= MY PROFILE ================= -->
        <div class="col-12 col-md-6">
          <q-card class="premium-glass-card profile-card q-pa-lg flex column">
            <q-card-section class="q-pa-none col flex column full-height">
              <div class="row items-center q-mb-xl">
                <div class="glass-icon-box q-mr-md">
                  <q-icon name="account_circle" size="26px" color="red-6" />
                </div>
                <div class="text-h5 text-weight-bold text-blue-grey-9">My Profile</div>
              </div>
              
              <q-form @submit.prevent="openConfirm('Confirm Changes', 'Are you sure you want to save these changes?', submitProfile)" class="col flex column full-height">
                <div class="q-gutter-y-md">
                  <div class="row q-col-gutter-md">
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
                  
                  <div class="row items-center q-gutter-x-sm">
                    <q-input v-model="profileForm.phoneNumber" label="Mobile Number" outlined class="custom-glass-input col">
                      <template v-slot:prepend><q-icon name="phone_iphone" size="20px" color="blue-grey-4" /></template>
                    </q-input>
                    <q-btn v-ripple label="Verify Change" outline color="red-6" no-caps class="btn-danger-outline q-px-md q-py-sm" v-if="phoneChanged" @click="verifyPhoneModal = true" />
                  </div>
                </div>
                
                <div class="text-right q-mt-auto pt-lg">
                  <q-btn v-ripple type="submit" label="Save Profile" unelevated class="btn-red-gradient text-white q-px-xl q-py-sm text-weight-bold" no-caps />
                </div>
              </q-form>
            </q-card-section>
          </q-card>
        </div>

        <!-- ================= STORE INFO ================= -->
        <div class="col-12 col-md-6">
          <q-card class="premium-glass-card profile-card q-pa-lg flex column">
            <q-card-section class="q-pa-none col flex column full-height">
              <div class="row items-center q-mb-xl">
                <div class="glass-icon-box q-mr-md">
                  <q-icon name="storefront" size="26px" color="red-6" />
                </div>
                <div class="text-h5 text-weight-bold text-blue-grey-9">Store Info</div>
              </div>
              
              <q-form @submit.prevent="openConfirm('Confirm Changes', 'Are you sure you want to save these changes?', submitStoreInfo)" class="col flex column full-height">
                <div class="q-gutter-y-md">
                  <q-input v-model="storeForm.storeName" label="Store Name" outlined class="custom-glass-input">
                    <template v-slot:prepend><q-icon name="store" size="20px" color="blue-grey-4" /></template>
                    <template v-slot:hint><span class="text-blue-grey-4">This name will be visible to neighborhood consumers.</span></template>
                  </q-input>

                  <!-- RESTORED: Dynamic Image Render or Fallback Container -->
                  <div class="q-mb-md full-width">
                    <q-img 
                      v-if="storePicture && String(storePicture).trim().length > 10" 
                      :src="storePicture" 
                      ratio="16/9" 
                      class="rounded-borders shadow-2 q-mb-sm" 
                    />
                    <div 
                      v-else 
                      class="bg-grey-3 rounded-borders flex flex-center shadow-1 q-mb-sm full-width" 
                      style="height: 200px; min-height: 200px;"
                    >
                      <q-icon name="storefront" size="64px" color="grey-6" />
                    </div>
                  </div>
                  
                  <!-- Upload Controls -->
                  <div class="row items-center justify-between">
                    <div>
                      <div class="text-weight-bold">Store Cover Photo</div>
                      <div class="text-caption text-grey">Upload a crisp 16:9 rectangular photo</div>
                    </div>
                    <q-btn outline color="primary" label="Update Photo" @click="showImageCaptureModal = true" :loading="uploadingImage" />
                  </div>
                </div>
                
                <div class="text-right q-mt-auto pt-lg">
                  <q-btn v-ripple type="submit" label="Save Store Info" unelevated class="btn-red-gradient text-white q-px-xl q-py-sm text-weight-bold" no-caps />
                </div>
              </q-form>
            </q-card-section>
          </q-card>
        </div>

        <!-- ================= STORE HOURS ================= -->
        <div class="col-12">
          <q-card class="premium-glass-card q-pa-lg">
            <q-card-section class="q-pa-none">
              <div class="row items-center justify-between q-mb-xl">
                <div class="row items-center">
                  <div class="glass-icon-box q-mr-md">
                    <q-icon name="schedule" size="26px" color="red-6" />
                  </div>
                  <div class="text-h5 text-weight-bold text-blue-grey-9">Store Hours</div>
                </div>
                <q-btn v-ripple label="Apply Monday to All" outline color="red-6" no-caps class="btn-danger-outline q-px-md q-py-sm" @click="applyMondayToAll">
                  <q-tooltip class="bg-red-7">Copy Monday's schedule to all other days</q-tooltip>
                </q-btn>
              </div>
              
              <q-form @submit.prevent="openConfirm('Confirm Changes', 'Are you sure you want to save these changes?', submitStoreHours)" class="q-gutter-y-sm">
                <div class="row items-center q-col-gutter-md schedule-row q-pa-md rounded-borders" v-for="day in operatingDays" :key="day.name">
                  <div class="col-4 col-sm-3 text-subtitle2 text-weight-bold text-blue-grey-8">{{ day.name }}</div>
                  
                  <div class="col-4 col-sm-2">
                    <q-toggle v-model="day.isOpen" color="red-6" keep-color checked-icon="check" unchecked-icon="clear" />
                    <span class="text-body2 text-weight-medium q-ml-sm" :class="day.isOpen ? 'text-red-6' : 'text-blue-grey-4'">
                      {{ day.isOpen ? 'Open' : 'Closed' }}
                    </span>
                  </div>
                  
                  <div class="col-12 col-sm-7 row q-gutter-x-sm items-center" v-if="day.isOpen">
                    <q-input v-model="day.openTime" type="time" outlined dense class="custom-glass-input col" />
                    <div class="text-center text-blue-grey-3 text-weight-bold">—</div>
                    <q-input v-model="day.closeTime" type="time" outlined dense class="custom-glass-input col" />
                  </div>
                  <div class="col-12 col-sm-7 flex items-center" v-else>
                    <div class="text-blue-grey-4 text-italic bg-slate-50 q-px-md q-py-sm rounded-borders w-full text-center border-slate-light">
                      Not operating on this day
                    </div>
                  </div>
                </div>

                <div class="text-right q-mt-lg pt-md">
                  <q-btn v-ripple type="submit" label="Save Store Hours" unelevated class="btn-red-gradient text-white q-px-xl q-py-sm text-weight-bold" no-caps />
                </div>
              </q-form>
            </q-card-section>
          </q-card>
        </div>

        <!-- ================= MAP ADDRESS ================= -->
        <div class="col-12 col-md-6">
          <q-card class="premium-glass-card profile-card q-pa-lg flex column">
            <q-card-section class="q-pa-none col flex column full-height">
              <div class="row items-center q-mb-xl">
                <div class="glass-icon-box q-mr-md">
                  <q-icon name="place" size="26px" color="red-6" />
                </div>
                <div class="text-h5 text-weight-bold text-blue-grey-9">Address & Location</div>
              </div>
              
              <q-form @submit.prevent="openConfirm('Confirm Changes', 'Are you sure you want to save these changes?', submitAddress)" class="col flex column full-height">
                <div class="q-gutter-y-md">
                  <q-input v-model="addressForm.fullAddress" label="Complete Address" type="textarea" rows="2" outlined class="custom-glass-input">
                    <template v-slot:prepend><q-icon name="map" size="20px" color="blue-grey-4" class="q-mt-sm" /></template>
                  </q-input>
                  
                  <div class="q-mb-md">
                    <!-- Show Map if coordinates exist -->
                    <div 
                      v-if="addressForm.latitude && addressForm.longitude"
                      id="vendor-profile-map" 
                      class="rounded-borders shadow-1" 
                      style="height: 250px; width: 100%; z-index: 1;"
                    ></div>
                    
                    <!-- Show Empty State if coordinates are null -->
                    <div 
                      v-else
                      class="bg-grey-3 rounded-borders flex flex-center shadow-1 full-width column" 
                      style="height: 250px; border: 1px dashed #94a3b8;"
                    >
                      <q-icon name="location_off" size="48px" color="blue-grey-4" class="q-mb-sm" />
                      <div class="text-weight-bold text-blue-grey-7">No Location Detected</div>
                      <div class="text-caption text-blue-grey-5">Click the button below to detect and set your store coordinates.</div>
                    </div>
                  </div>
                  <div class="row justify-end q-mt-sm">
                    <q-btn outline color="secondary" icon="my_location" label="Detect Current Location" @click="detectLocation" :loading="isDetectingLocation" />
                  </div>
                </div>

                <div class="text-right q-mt-auto pt-lg">
                  <q-btn v-ripple type="submit" label="Save Address" unelevated class="btn-red-gradient text-white q-px-xl q-py-sm text-weight-bold" no-caps />
                </div>
              </q-form>
            </q-card-section>
          </q-card>
        </div>

        <!-- ================= PASSWORD ================= -->
        <div class="col-12 col-md-6">
          <q-card class="premium-glass-card profile-card q-pa-lg flex column">
            <q-card-section class="q-pa-none col flex column full-height">
              <div class="row items-center q-mb-xl">
                <div class="glass-icon-box q-mr-md">
                  <q-icon name="security" size="26px" color="red-6" />
                </div>
                <div class="text-h5 text-weight-bold text-blue-grey-9">Security & Password</div>
              </div>
              
              <q-form @submit.prevent="openConfirm('Confirm Changes', 'Are you sure you want to update your password? A change password email confirmation will be sent to your email.', submitPassword)" class="col flex column full-height">
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
                  <q-btn v-ripple type="submit" label="Update Password" unelevated class="btn-red-gradient text-white q-px-xl q-py-sm text-weight-bold" no-caps />
                </div>
              </q-form>
            </q-card-section>
          </q-card>
        </div>

        <!-- ================= DELETE ACCOUNT ================= -->
        <div class="col-12">
          <q-card class="delete-zone-card q-pa-lg">
            <q-card-section class="q-pa-none">
              <div class="row no-wrap">
                <q-icon name="warning_amber" color="red-6" size="32px" class="q-mr-md q-mt-xs" />
                <div class="column col">
                  <div class="text-h6 text-weight-bold text-red-7">Delete Account</div>
                  <div class="text-blue-grey-6 text-body2 q-mt-xs q-mb-md">Once you delete your account, there is no going back. All inventory and sales records will be permanently lost.</div>
                  <div>
                    <q-btn 
                      v-ripple 
                      label="Delete Account" 
                      color="red-6" 
                      outline
                      no-caps 
                      class="btn-danger-outline q-px-lg q-py-xs text-weight-bold" 
                      @click="initiateDelete" 
                    />
                  </div>
                </div>
              </div>
            </q-card-section>
          </q-card>
        </div>

      </div>
    </div>

    <!-- ================= MODALS (CUSTOM GLASSMORPHISM) ================= -->
    
    <!-- Generic Confirmation Modal (Save/Update) -->
    <q-dialog v-model="confirmDialog.isOpen" persistent backdrop-filter="blur(4px)">
      <!-- Forced height auto to prevent stretching -->
      <q-card class="premium-glass-card q-pa-md" style="width: 420px; max-width: 90vw; border-top: 4px solid #e11d48; height: auto !important;">
        <q-card-section class="column items-center text-center q-pb-none">
          <div class="text-h5 text-weight-bolder text-blue-grey-9 q-mb-md">{{ confirmDialog.title }}</div>
          <div class="text-body1 text-blue-grey-6 q-mb-md">{{ confirmDialog.message }}</div>
        </q-card-section>
        <q-card-actions align="center" class="q-mt-sm q-mb-sm">
          <q-btn label="CANCEL" flat color="blue-grey-5" class="text-weight-bold q-px-md q-mr-sm" no-caps v-close-popup />
          <q-btn label="OK" unelevated class="btn-red-gradient text-white q-px-xl text-weight-bold" no-caps @click="executeConfirmAction" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Camera/Cropper Modal -->
    <ImageCaptureModal v-model="showImageCaptureModal" @captured="handleCapturedImage" :aspectRatio="16/9" />

    <!-- Secure Delete Account Modal -->
    <q-dialog v-model="deleteDialog.isOpen" persistent backdrop-filter="blur(8px)">
      <!-- Forced height auto to prevent stretching -->
      <q-card class="premium-glass-card q-pa-lg" style="width: 520px; max-width: 95vw; border-top: 4px solid #dc2626; height: auto !important;">
        <q-card-section class="text-center q-pb-none">
          <div class="text-h5 text-weight-bolder text-red-7 q-mb-lg">Delete Merchant Account</div>
          
          <div class="text-subtitle1 text-weight-bold text-red-6 q-mb-sm">Do you really want to delete your Merchant Account?</div>
          <div class="text-body2 text-blue-grey-6 q-mb-lg">If yes, please type your store name to confirm.</div>
          
          <q-input 
            v-model="deleteDialog.inputName" 
            placeholder="Type your store name" 
            outlined 
            class="custom-glass-input q-mb-md center-input-text"
          />
        </q-card-section>

        <q-card-actions align="center" class="q-mt-md">
          <q-btn label="Back" outline color="blue-grey-5" class="btn-glass-outline q-px-xl q-mr-md" no-caps v-close-popup />
          <q-btn 
            label="Yes, Delete" 
            unelevated 
            class="btn-red-gradient text-white q-px-xl text-weight-bold" 
            no-caps 
            :disable="deleteDialog.inputName !== storeForm.storeName || !storeForm.storeName"
            @click="executeDeleteAccount"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

  </q-page>
</template>

<script setup>
import { ref, reactive, computed, onMounted, nextTick } from 'vue'
import { useQuasar } from 'quasar'
import { api } from '@/boot/axios'
import ImageCaptureModal from '@/components/modals/ImageCaptureModal.vue'
import { useAuth } from '@/composables/useAuth'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

const $q = useQuasar()
const authStore = useAuth()

// Form States
const profileForm = reactive({ firstName: '', lastName: '', email: '', phoneNumber: '' })
const storeForm = reactive({
  storeName: '',
})
const storePicture = ref(null)

const uploadingImage = ref(false)
const showImageCaptureModal = ref(false)
const addressForm = reactive({ fullAddress: '', latitude: null, longitude: null })
const passwordForm = reactive({ current: '', new: '', confirm: '' })

const originalPhone = ref('')
const phoneChanged = computed(() => profileForm.phoneNumber !== originalPhone.value)
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
  $q.notify({ type: 'positive', message: 'Monday hours applied to all days.', color: 'red-6' })
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
    $q.notify({ type: 'positive', message: 'Profile saved successfully.', color: 'green' })
  } catch (error) {
    $q.notify({ type: 'negative', message: 'Failed to update profile.' })
  }
}

const submitStoreInfo = async () => {
  try {
    await api.put('/vendor/store/info', { store_name: storeForm.storeName })
    $q.notify({ type: 'positive', message: 'Store Info saved successfully.', color: 'green' })
  } catch (error) {
    $q.notify({ type: 'negative', message: 'Failed to update store info.' })
  }
}

const handleCapturedImage = async ({ file }) => {
  if (!file) return

  // Instant local preview from the blob
  storePicture.value = URL.createObjectURL(file)

  // Immediately upload
  uploadingImage.value = true
  const formData = new FormData()
  formData.append('store_picture', file, 'store_cover.jpg')
  formData.append('_method', 'POST')

  try {
    const response = await api.post('/vendor/profile/store-image', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    console.log('UPLOAD SUCCESS - RESPONSE PAYLOAD:', response.data);
    console.log('UPLOAD SUCCESS - NEW URL:', response.data.store_picture_url);
    $q.notify({ type: 'positive', message: 'Store image uploaded successfully.', color: 'green' })
    
    // CRITICAL: Update Pinia auth store so other components (like Dashboard) update instantly
    if (response.data && response.data.store_picture_url) {
        const newUrl = response.data.store_picture_url;
        storePicture.value = newUrl; // Immediate local update
        
        // Immediate Pinia update for dashboard consistency
        if (authStore.user && authStore.user.store) {
            authStore.user.store.store_picture_url = newUrl;
        }
    }
  } catch (error) {
    const errorMsg = error.response?.data?.message || 'Failed to upload store image.'
    $q.notify({ type: 'negative', message: errorMsg })
    console.error('Upload error:', error.response?.data || error)
    storePicture.value = null
  } finally {
    uploadingImage.value = false
  }
}

const submitStoreHours = async () => {
  try {
    await api.put('/vendor/profile/hours', { operatingDays })
    $q.notify({ type: 'positive', message: 'Store Hours saved successfully.', color: 'green' })
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
    $q.notify({ type: 'positive', message: 'Address saved successfully.', color: 'green' })
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
    $q.notify({ type: 'positive', message: 'Password updated successfully.', color: 'green' })
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
        console.log('PROFILE LOAD - RAW STORE DATA:', data.store);
        console.log('PROFILE LOAD - PICTURE URL:', data.store?.store_picture_url);
        
        storeForm.storeName = data.store.store_name || ''
        
        if (data.store && data.store.store_picture_url) {
            storePicture.value = data.store.store_picture_url;
        } else {
            storePicture.value = null; // Ensure fallback shows if data is missing
        }
        addressForm.fullAddress = data.store.address || ''
        addressForm.latitude = data.store.latitude || null
        addressForm.longitude = data.store.longitude || null
      }
    }
  } catch (error) { console.error('Failed to load profile data', error) }
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

  L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
    attribution: '&copy; OpenStreetMap contributors &copy; CARTO'
  }).addTo(map.value)

  const icon = L.icon({
    iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
    iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
    shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34],
    shadowSize: [41, 41]
  })

  marker.value = L.marker([lat, lng], { icon }).addTo(map.value)
}

const detectLocation = () => {
  if (!navigator.geolocation) {
    $q.notify({ type: 'negative', message: 'Geolocation is not supported by your browser.' })
    return
  }

  isDetectingLocation.value = true
  navigator.geolocation.getCurrentPosition(
    (position) => {
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
      
      $q.notify({ type: 'positive', message: 'Location detected successfully.', color: 'green' })
      isDetectingLocation.value = false
    },
    (error) => {
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
.pt-xl { padding-top: 32px; }

/* Subtle Ambient Glows */
.bg-glow {
  position: absolute;
  width: 500px;
  height: 500px;
  border-radius: 50%;
  filter: blur(140px);
  z-index: 0;
  opacity: 0.15; 
  pointer-events: none;
}
.bg-glow-primary {
  top: -100px;
  left: -100px;
  background: radial-gradient(circle, rgba(225, 29, 72, 0.4) 0%, transparent 70%); 
}
.bg-glow-secondary {
  bottom: 100px;
  right: -100px;
  background: radial-gradient(circle, rgba(244, 63, 94, 0.3) 0%, transparent 70%); 
}

.tracking-tight { letter-spacing: -0.02em; }
.tracking-wide { letter-spacing: 0.05em; }

/* Clean Glassmorphism Cards - Visuals Only */
.premium-glass-card {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  border: 1px solid rgba(241, 245, 249, 1);
  border-radius: 12px;
  box-shadow: 0 2px 16px rgba(15, 23, 42, 0.04); 
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.premium-glass-card:hover {
  box-shadow: 0 6px 24px rgba(15, 23, 42, 0.06);
}

/* Specific rule for the grid cards to stretch perfectly */
.profile-card {
  height: 100%;
}

/* Beautiful Header Glass Icon Box */
.glass-icon-box {
  width: 48px;
  height: 48px;
  background: rgba(255, 255, 255, 0.7);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.9);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 12px rgba(225, 29, 72, 0.08); /* Soft red shadow */
}

/* Custom Glass Inputs */
.custom-glass-input :deep(.q-field__control) {
  background: rgba(248, 250, 252, 0.8); 
  border-radius: 8px;
  transition: all 0.3s ease;
}
.custom-glass-input :deep(.q-field__control:before) {
  border: 1px solid rgba(226, 232, 240, 0.8);
}
.custom-glass-input :deep(.q-field__control:hover) {
  background: rgba(241, 245, 249, 1);
}
.custom-glass-input :deep(.q-field--focused .q-field__control) {
  background: #ffffff;
  box-shadow: 0 2px 10px rgba(225, 29, 72, 0.06); 
}
.custom-glass-input :deep(.q-field--focused .q-icon) {
  color: #e11d48 !important; 
}
.center-input-text :deep(.q-field__native) {
  text-align: center;
  font-weight: bold;
  font-size: 1.1rem;
}

/* Primary Accent Buttons (Red Gradient) */
.btn-red-gradient {
  border-radius: 8px !important;
  background: linear-gradient(135deg, #e11d48 0%, #be123c 100%) !important; 
  box-shadow: 0 2px 8px rgba(225, 29, 72, 0.2);
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}
.btn-red-gradient:hover:not(.disabled) {
  box-shadow: 0 4px 12px rgba(225, 29, 72, 0.3);
  transform: translateY(-1px);
}
.btn-glass-outline {
  border-radius: 8px !important;
  background: rgba(255, 255, 255, 0.8) !important;
  border: 1px solid currentColor;
  transition: all 0.2s ease;
}
.btn-glass-outline:hover {
  background: rgba(241, 245, 249, 0.9) !important;
  transform: translateY(-1px);
}

/* Delete Zone */
.delete-zone-card {
  background: #fffafa; 
  border: 1px solid #fee2e2; 
  border-radius: 12px;
}
.btn-danger-outline {
  border-radius: 8px !important;
  background: #ffffff !important;
  border: 1px solid #dc2626 !important;
  color: #dc2626 !important;
  transition: all 0.2s ease;
}
.btn-danger-outline:hover {
  background: #fef2f2 !important; 
  transform: translateY(-1px);
}

/* Store Hours Table */
.schedule-row { transition: background-color 0.2s ease; }
.schedule-row:hover { background-color: rgba(248, 250, 252, 0.8); }
.bg-slate-50 { background-color: #f8fafc; }
.border-slate-light { border: 1px solid #f1f5f9; }
.w-full { width: 100%; }

/* Map Placeholder UI */
.empty-state-glass {
  background: rgba(248, 250, 252, 0.8);
  border: 1px solid rgba(226, 232, 240, 0.9);
}
.map-grid-bg {
  position: absolute;
  top: 0; left: 0; right: 0; bottom: 0;
  background-image: linear-gradient(rgba(203, 213, 225, 0.4) 1px, transparent 1px),
  linear-gradient(90deg, rgba(203, 213, 225, 0.4) 1px, transparent 1px);
  background-size: 24px 24px;
  z-index: 0;
}
.drop-shadow-icon {
  filter: drop-shadow(0 2px 4px rgba(225, 29, 72, 0.3));
}

/* Pulsing Map Pin */
.pin-container {
  position: relative;
  display: inline-flex;
  justify-content: center;
  align-items: center;
}
.pin-pulse {
  position: absolute;
  width: 50px;
  height: 50px;
  background: rgba(225, 29, 72, 0.2);
  border-radius: 50%;
  z-index: -1;
  animation: pulse-ring 2s cubic-bezier(0.215, 0.61, 0.355, 1) infinite;
}
@keyframes pulse-ring {
  0% { transform: scale(0.5); opacity: 0; }
  50% { opacity: 1; }
  100% { transform: scale(2); opacity: 0; }
}
</style>