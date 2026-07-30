<template>
  <q-page class="vendor-page">
    <div class="page-container">
      
      <div class="page-header q-mb-xl">
        <h1 class="text-h4 text-weight-bold">Vendor Profile Settings</h1>
        <p class="text-grey-7">Manage your personal information, store details, and security.</p>
      </div>

      <div class="row q-col-gutter-lg">
        
        <!-- ================= MY PROFILE ================= -->
        <div class="col-12 col-md-6">
          <q-card class="premium-glass-card profile-card q-pa-md">
            <q-card-section>
              <div class="text-h6 text-weight-bold q-mb-md">My Profile</div>
              <q-form @submit.prevent="saveProfile" class="q-gutter-md">
                <div class="row q-col-gutter-sm">
                  <div class="col-12 col-sm-6">
                    <q-input v-model="profileForm.firstName" label="First Name" outlined dense class="custom-glass-input" />
                  </div>
                  <div class="col-12 col-sm-6">
                    <q-input v-model="profileForm.lastName" label="Last Name" outlined dense class="custom-glass-input" />
                  </div>
                </div>
                
                <q-input v-model="profileForm.email" label="Email Address" type="email" outlined dense class="custom-glass-input" />
                
                <div class="row items-center q-gutter-x-sm">
                  <q-input v-model="profileForm.phoneNumber" label="Mobile Number" outlined dense class="custom-glass-input col" />
                  <q-btn label="Verify Change" outline color="primary" no-caps class="btn-3d-outline" v-if="phoneChanged" @click="verifyPhoneModal = true" />
                </div>
                
                <div class="text-right">
                  <q-btn type="submit" label="Save Profile" unelevated class="btn-approve-3d text-white q-px-md" no-caps />
                </div>
              </q-form>
            </q-card-section>
          </q-card>
        </div>

        <!-- ================= STORE INFO ================= -->
        <div class="col-12 col-md-6">
          <q-card class="premium-glass-card profile-card q-pa-md">
            <q-card-section>
              <div class="text-h6 text-weight-bold q-mb-md">Store Info</div>
              <q-form @submit.prevent="saveStoreInfo" class="q-gutter-md">
                <q-input v-model="storeForm.storeName" label="Store Name" outlined dense class="custom-glass-input" />
                
                <div class="text-right">
                  <q-btn type="submit" label="Save Store Info" unelevated class="btn-approve-3d text-white q-px-md" no-caps />
                </div>
              </q-form>
            </q-card-section>
          </q-card>
        </div>

        <!-- ================= STORE HOURS ================= -->
        <div class="col-12">
          <q-card class="premium-glass-card profile-card q-pa-md">
            <q-card-section>
              <div class="row items-center justify-between q-mb-md">
                <div class="text-h6 text-weight-bold">Store Hours</div>
                <q-btn label="Apply Monday to All" outline color="primary" no-caps class="btn-3d-outline" @click="applyMondayToAll" />
              </div>
              
              <q-form @submit.prevent="saveStoreHours" class="q-gutter-md">
                <div class="row items-center q-col-gutter-md" v-for="day in operatingDays" :key="day.name">
                  <div class="col-3 col-sm-2 text-weight-medium">{{ day.name }}</div>
                  <div class="col-3 col-sm-2">
                    <q-toggle v-model="day.isOpen" color="green-6" label="Open" left-label />
                  </div>
                  <div class="col-6 col-sm-8 row q-gutter-sm" v-if="day.isOpen">
                    <q-input v-model="day.openTime" type="time" outlined dense class="custom-glass-input col" />
                    <div class="text-center self-center">-</div>
                    <q-input v-model="day.closeTime" type="time" outlined dense class="custom-glass-input col" />
                  </div>
                  <div class="col-6 col-sm-8 text-grey-6 text-italic flex items-center" v-else>
                    Closed
                  </div>
                </div>

                <div class="text-right q-mt-lg">
                  <q-btn type="submit" label="Save Store Hours" unelevated class="btn-approve-3d text-white q-px-md" no-caps />
                </div>
              </q-form>
            </q-card-section>
          </q-card>
        </div>

        <!-- ================= MAP ADDRESS ================= -->
        <div class="col-12 col-md-6">
          <q-card class="premium-glass-card profile-card q-pa-md">
            <q-card-section>
              <div class="text-h6 text-weight-bold q-mb-md">Address & Location</div>
              <q-form @submit.prevent="saveAddress" class="q-gutter-md">
                <q-input v-model="addressForm.fullAddress" label="Complete Address" type="textarea" rows="3" outlined dense class="custom-glass-input" />
                
                <div class="map-placeholder empty-state-glass flex flex-center rounded-borders q-mt-md" style="height: 200px;">
                  <div class="text-center">
                    <q-icon name="pin_drop" size="48px" color="red-5" />
                    <div class="text-grey-7 q-mt-sm">Google Maps Pin UI Placeholder</div>
                  </div>
                </div>

                <div class="text-right q-mt-md">
                  <q-btn type="submit" label="Save Address" unelevated class="btn-approve-3d text-white q-px-md" no-caps />
                </div>
              </q-form>
            </q-card-section>
          </q-card>
        </div>

        <!-- ================= PASSWORD ================= -->
        <div class="col-12 col-md-6">
          <q-card class="premium-glass-card profile-card q-pa-md">
            <q-card-section>
              <div class="text-h6 text-weight-bold q-mb-md">Security</div>
              <q-form @submit.prevent="savePassword" class="q-gutter-md">
                <q-input v-model="passwordForm.current" label="Current Password" type="password" outlined dense class="custom-glass-input" />
                <q-input v-model="passwordForm.new" label="New Password" type="password" outlined dense class="custom-glass-input" />
                <q-input v-model="passwordForm.confirm" label="Confirm New Password" type="password" outlined dense class="custom-glass-input" />
                
                <div class="text-right">
                  <q-btn type="submit" label="Update Password" unelevated class="btn-approve-3d text-white q-px-md" no-caps />
                </div>
              </q-form>
            </q-card-section>
          </q-card>
        </div>

        <!-- ================= DANGER ZONE ================= -->
        <div class="col-12">
          <q-card class="premium-glass-card profile-card border-red-light q-pa-md bg-red-50">
            <q-card-section class="row items-center justify-between">
              <div>
                <div class="text-h6 text-weight-bold text-red-9">Danger Zone</div>
                <div class="text-red-7">Once you delete your account, there is no going back. Please be certain.</div>
              </div>
              <q-btn label="Delete my vendor account" color="red-9" unelevated no-caps class="btn-premium q-px-md" @click="confirmDeleteAccount" />
            </q-card-section>
          </q-card>
        </div>

      </div>
    </div>
  </q-page>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { api } from '@/boot/axios'

const $q = useQuasar()

const profileForm = reactive({
  firstName: '',
  lastName: '',
  email: '',
  phoneNumber: ''
})

const originalPhone = ref('')
const phoneChanged = computed(() => profileForm.phoneNumber !== originalPhone.value)
const verifyPhoneModal = ref(false)

const storeForm = reactive({
  storeName: ''
})

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
  $q.notify({ type: 'positive', message: 'Monday hours applied to all days.' })
}

const addressForm = reactive({
  fullAddress: '',
  latitude: null,
  longitude: null
})

const passwordForm = reactive({
  current: '',
  new: '',
  confirm: ''
})

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
        addressForm.fullAddress = data.store.address || ''
        
        if (data.store.operating_days) {
          try {
            const daysArr = typeof data.store.operating_days === 'string' 
              ? JSON.parse(data.store.operating_days) 
              : data.store.operating_days
            
            if (Array.isArray(daysArr)) {
              operatingDays.forEach(day => {
                day.isOpen = daysArr.includes(day.name.substring(0, 3)) || daysArr.includes(day.name)
              })
            }
          } catch (e) {
            // Ignore parsing error if format varies
          }
        }
      }
    }
  } catch (error) {
    console.error('Failed to load profile data', error)
  }
}

const saveProfile = () => {
  $q.dialog({
    title: 'Confirm Changes',
    message: 'Are you sure you want to save these changes?',
    cancel: true,
    persistent: true
  }).onOk(async () => {
    try {
      await api.put('/vendor/profile', {
        first_name: profileForm.firstName,
        last_name: profileForm.lastName,
        email: profileForm.email,
        phone_number: profileForm.phoneNumber
      })
      $q.notify({ type: 'positive', message: 'Profile saved successfully.' })
    } catch (error) {
      $q.notify({ type: 'negative', message: 'Failed to update profile.' })
    }
  })
}

const saveStoreInfo = () => {
  $q.dialog({
    title: 'Confirm Changes',
    message: 'Are you sure you want to save these changes?',
    cancel: true,
    persistent: true
  }).onOk(async () => {
    try {
      await api.put('/vendor/store/info', {
        store_name: storeForm.storeName
      })
      $q.notify({ type: 'positive', message: 'Store Info saved successfully.' })
    } catch (error) {
      $q.notify({ type: 'negative', message: 'Failed to update store info.' })
    }
  })
}

const saveStoreHours = () => {
  $q.dialog({
    title: 'Confirm Changes',
    message: 'Are you sure you want to save these changes?',
    cancel: true,
    persistent: true
  }).onOk(async () => {
    try {
      await api.put('/vendor/profile/hours', { operatingDays })
      $q.notify({ type: 'positive', message: 'Store Hours saved successfully.' })
    } catch (error) {
      $q.notify({ type: 'negative', message: 'Failed to save store hours.' })
    }
  })
}

const saveAddress = () => {
  $q.dialog({
    title: 'Confirm Changes',
    message: 'Are you sure you want to save these changes?',
    cancel: true,
    persistent: true
  }).onOk(async () => {
    try {
      await api.put('/vendor/store/address', {
        address: addressForm.fullAddress
      })
      $q.notify({ type: 'positive', message: 'Address saved successfully.' })
    } catch (error) {
      $q.notify({ type: 'negative', message: 'Failed to save address.' })
    }
  })
}

const savePassword = () => {
  $q.dialog({
    title: 'Confirm Changes',
    message: 'Are you sure you want to update your password?',
    cancel: true,
    persistent: true
  }).onOk(async () => {
    try {
      await api.put('/vendor/profile/password', {
        current_password: passwordForm.current,
        new_password: passwordForm.new,
        new_password_confirmation: passwordForm.confirm
      })
      $q.notify({ type: 'positive', message: 'Password updated successfully.' })
      passwordForm.current = ''
      passwordForm.new = ''
      passwordForm.confirm = ''
    } catch (error) {
      $q.notify({ type: 'negative', message: 'Failed to update password.' })
    }
  })
}

const confirmDeleteAccount = () => {
  $q.dialog({
    title: 'Delete Account',
    message: 'Are you absolutely sure you want to delete your vendor account? This action cannot be undone.',
    color: 'red',
    ok: { label: 'Yes, Delete', color: 'red-9', unelevated: true, noCaps: true },
    cancel: { label: 'Cancel', flat: true, color: 'grey-7', noCaps: true }
  }).onOk(async () => {
    try {
      await api.delete('/vendor/account')
      $q.notify({ type: 'positive', message: 'Account deleted.' })
      localStorage.clear()
      window.location.href = '/login'
    } catch (error) {
      $q.notify({ type: 'negative', message: 'Failed to delete account.' })
    }
  })
}

onMounted(() => {
  fetchProfile()
})
</script>

<style scoped>
.vendor-page {
  padding: 24px;
  background: #f8fafc;
  min-height: 100vh;
}
.page-container {
  max-width: 1200px;
  margin: 0 auto;
}
.premium-glass-card {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.8);
  border-radius: 16px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
  height: 100%;
}
.custom-glass-input :deep(.q-field__control) {
  background: rgba(241, 245, 249, 0.6);
  border-radius: 8px;
}
.btn-approve-3d {
  border-radius: 8px !important;
  background: linear-gradient(180deg, #10b981 0%, #059669 100%) !important;
  box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3), inset 0 1px 1px rgba(255, 255, 255, 0.3);
  transition: all 0.2s ease;
}
.btn-approve-3d:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 15px rgba(16, 185, 129, 0.4), inset 0 1px 1px rgba(255, 255, 255, 0.3);
}
.btn-3d-outline {
  border-radius: 8px !important;
  background: #ffffff !important;
  border: 1px solid #E2E8F0;
  box-shadow: 0 2px 5px rgba(0,0,0,0.05);
  transition: all 0.2s ease;
}
.btn-3d-outline:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 10px rgba(0,0,0,0.08);
  background: #F8FAFC !important;
}
.btn-premium {
  border-radius: 8px !important;
  font-weight: 600;
  box-shadow: 0 4px 12px rgba(185, 28, 28, 0.2);
  transition: all 0.2s ease;
}
.border-red-light {
  border: 1px solid #FEE2E2;
}
.empty-state-glass {
  background: rgba(248, 250, 252, 0.8);
  border: 2px dashed #E2E8F0;
}
</style>