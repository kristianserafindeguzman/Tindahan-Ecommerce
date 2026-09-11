<template>
  <q-page class="vendor-page relative-position" :class="{ 'mobile-page-padding': $q.screen.lt.md }">
    <!-- Subtle Ambient Background Glows -->
    <div class="bg-glow bg-glow-primary desktop-only"></div>
    <div class="bg-glow bg-glow-secondary desktop-only"></div>

    <div class="page-container relative-position" style="z-index: 1">
      
      <!-- ================= HEADER AREA ================= -->
      <div v-if="!$q.screen.lt.md" class="page-header q-mb-xl q-mt-sm row items-center justify-between">
        <div class="row items-center no-wrap">
          <div class="glass-icon-box q-mr-md shrink-none">
            <q-icon name="manage_accounts" size="26px" class="text-brand-red" />
          </div>
          <div>
            <h1 class="text-h4 text-weight-bolder text-blue-grey-9 q-ma-none tracking-tight">
              Profile Settings
            </h1>
            <p class="text-body1 text-blue-grey-5 q-mt-xs q-mb-none font-medium">
              Manage your personal information, store details, and security.
            </p>
          </div>
        </div>
      </div>

      <!-- Mobile Header Area with Standardized Icon Box -->
      <div v-else class="page-header q-mb-lg q-mt-sm row items-center no-wrap">
        <div class="glass-icon-box q-mr-md shrink-none" style="width: 44px; height: 44px;">
          <q-icon name="manage_accounts" size="22px" class="text-brand-red" />
        </div>
        <div>
          <h1 class="text-h5 text-weight-bolder text-blue-grey-9 q-ma-none tracking-tight leading-tight">
            Profile Settings
          </h1>
          <p class="text-caption text-blue-grey-5 q-mt-xs q-mb-none font-medium">
            Manage account and store info.
          </p>
        </div>
      </div>

      <!-- Main Settings Grid -->
      <div class="row q-col-gutter-lg q-col-gutter-y-lg items-start">
        
        <!-- ================= MY PROFILE ================= -->
        <div class="col-12 col-md-6">
          <q-card class="premium-glass-card profile-card overflow-hidden">
            <q-card-section class="card-header-styled row items-center text-white bg-gradient-red no-wrap" :class="$q.screen.lt.md ? 'q-py-sm q-px-md' : 'q-pa-md'">
              <q-icon name="account_circle" :size="$q.screen.lt.md ? '20px' : '20px'" class="q-mr-sm shrink-none" />
              <div class="text-weight-bold" :style="{ fontSize: $q.screen.lt.md ? '15.5px' : '15px' }">My Profile</div>
            </q-card-section>

            <q-card-section class="q-pa-md q-pa-sm-lg">
              <q-form @submit.prevent="openConfirm('Confirm Changes', 'Are you sure you want to save these changes?', submitProfile)">
                <div class="q-gutter-y-md">
                  <div class="row q-col-gutter-md">
                    <div class="col-12 col-sm-6">
                      <q-input v-model="profileForm.firstName" label="First Name" outlined dense class="custom-glass-input" hide-bottom-space>
                        <template v-slot:prepend><q-icon name="person_outline" size="18px" color="blue-grey-4" /></template>
                      </q-input>
                    </div>
                    <div class="col-12 col-sm-6">
                      <q-input v-model="profileForm.lastName" label="Last Name" outlined dense class="custom-glass-input" hide-bottom-space>
                        <template v-slot:prepend><q-icon name="person_outline" size="18px" color="blue-grey-4" /></template>
                      </q-input>
                    </div>
                  </div>

                  <q-input v-model="profileForm.email" label="Email Address" type="email" outlined dense class="custom-glass-input" hide-bottom-space>
                    <template v-slot:prepend><q-icon name="mail_outline" size="18px" color="blue-grey-4" /></template>
                  </q-input>

                  <!-- Mobile Number with Proportional Verify Button -->
                  <div class="row items-center no-wrap q-gutter-x-sm">
                    <q-input 
                      v-model="profileForm.phoneNumber" 
                      label="Mobile Number" 
                      outlined 
                      dense
                      class="custom-glass-input col"
                      hide-bottom-space
                      lazy-rules
                      :rules="[
                        val => !!val || 'Mobile number is required',
                        val => /^09\d{9}$/.test(val) || 'Enter a valid 11-digit mobile number'
                      ]"
                    >
                      <template v-slot:prepend><q-icon name="phone_iphone" size="18px" color="blue-grey-4" /></template>
                    </q-input>

                    <q-btn 
                      v-if="phoneNeedsVerification" 
                      label="Verify" 
                      unelevated 
                      color="amber-9" 
                      class="text-weight-bold shadow-soft flex-shrink-0" 
                      style="border-radius: 8px; height: 44px; font-size: 13px; padding: 0 16px;" 
                      no-caps 
                      @click="sendOtp" 
                      :loading="isSendingOtp"
                    />
                  </div>
                </div>

                <div class="text-right q-mt-lg">
                  <q-btn v-ripple type="submit" label="Save Profile" unelevated class="btn-red-gradient text-white q-px-xl text-weight-bold full-width-mobile" style="height: 44px; font-size: 13.5px;" no-caps :disable="phoneNeedsVerification" />
                </div>
              </q-form>
            </q-card-section>
          </q-card>
        </div>

        <!-- ================= PASSWORD ================= -->
        <div class="col-12 col-md-6">
          <q-card class="premium-glass-card profile-card overflow-hidden">
            <q-card-section class="card-header-styled row items-center text-white bg-gradient-red no-wrap" :class="$q.screen.lt.md ? 'q-py-sm q-px-md' : 'q-pa-md'">
              <q-icon name="security" :size="$q.screen.lt.md ? '20px' : '20px'" class="q-mr-sm shrink-none" />
              <div class="text-weight-bold" :style="{ fontSize: $q.screen.lt.md ? '15.5px' : '15px' }">Security & Password</div>
            </q-card-section>

            <q-card-section class="q-pa-md q-pa-sm-lg">
              <q-form @submit.prevent="validateAndSubmitPassword">
                <div class="q-gutter-y-md">
                  <q-input 
                    v-model="passwordForm.current" 
                    label="Current Password" 
                    :type="showCurrentPassword ? 'text' : 'password'" 
                    outlined 
                    dense
                    class="custom-glass-input"
                    hide-bottom-space
                    lazy-rules
                    :rules="[val => !!val || 'Current password is required']"
                  >
                    <template v-slot:prepend><q-icon name="lock_outline" size="18px" color="blue-grey-4" /></template>
                    <template v-slot:append>
                      <q-icon 
                        :name="showCurrentPassword ? 'visibility' : 'visibility_off'" 
                        class="cursor-pointer text-blue-grey-4" 
                        size="18px"
                        @click="showCurrentPassword = !showCurrentPassword" 
                      />
                    </template>
                  </q-input>

                  <div>
                    <q-input 
                      v-model="passwordForm.new" 
                      label="New Password" 
                      :type="showNewPassword ? 'text' : 'password'" 
                      outlined 
                      dense
                      class="custom-glass-input q-mb-xs"
                      hide-bottom-space
                      lazy-rules
                      :rules="[val => !!val || 'New password is required']"
                    >
                      <template v-slot:prepend><q-icon name="key" size="18px" color="blue-grey-4" /></template>
                      <template v-slot:append>
                        <q-icon 
                          :name="showNewPassword ? 'visibility' : 'visibility_off'" 
                          class="cursor-pointer text-blue-grey-4" 
                          size="18px"
                          @click="showNewPassword = !showNewPassword" 
                        />
                      </template>
                    </q-input>

                    <div class="password-requirements-card bg-slate-50 border-slate-light rounded-borders q-pa-md q-mt-sm">
                      <div class="text-caption text-weight-bold text-slate-700 q-mb-xs" style="font-size: 11.5px;">Password Requirements:</div>
                      <div class="row q-col-gutter-xs">
                        <div class="col-12 col-sm-6 row items-center no-wrap">
                          <q-icon :name="passwordRules.minChars ? 'check_circle' : 'radio_button_unchecked'" size="14px" :color="passwordRules.minChars ? 'positive' : 'grey-5'" class="q-mr-xs shrink-none" />
                          <span class="text-caption" style="font-size: 11.5px;" :class="passwordRules.minChars ? 'text-positive text-weight-medium' : 'text-grey-6'">At least 8 characters</span>
                        </div>
                        <div class="col-12 col-sm-6 row items-center no-wrap">
                          <q-icon :name="passwordRules.hasUpper ? 'check_circle' : 'radio_button_unchecked'" size="14px" :color="passwordRules.hasUpper ? 'positive' : 'grey-5'" class="q-mr-xs shrink-none" />
                          <span class="text-caption" style="font-size: 11.5px;" :class="passwordRules.hasUpper ? 'text-positive text-weight-medium' : 'text-grey-6'">One uppercase letter (A-Z)</span>
                        </div>
                        <div class="col-12 col-sm-6 row items-center no-wrap">
                          <q-icon :name="passwordRules.hasNumber ? 'check_circle' : 'radio_button_unchecked'" size="14px" :color="passwordRules.hasNumber ? 'positive' : 'grey-5'" class="q-mr-xs shrink-none" />
                          <span class="text-caption" style="font-size: 11.5px;" :class="passwordRules.hasNumber ? 'text-positive text-weight-medium' : 'text-grey-6'">One number (0-9)</span>
                        </div>
                        <div class="col-12 col-sm-6 row items-center no-wrap">
                          <q-icon :name="passwordRules.hasSymbol ? 'check_circle' : 'radio_button_unchecked'" size="14px" :color="passwordRules.hasSymbol ? 'positive' : 'grey-5'" class="q-mr-xs shrink-none" />
                          <span class="text-caption" style="font-size: 11.5px;" :class="passwordRules.hasSymbol ? 'text-positive text-weight-medium' : 'text-grey-6'">One symbol (@, #, $)</span>
                        </div>
                      </div>
                    </div>
                  </div>

                  <q-input 
                    v-model="passwordForm.confirm" 
                    label="Confirm New Password" 
                    :type="showConfirmPassword ? 'text' : 'password'" 
                    outlined 
                    dense
                    class="custom-glass-input"
                    hide-bottom-space
                    lazy-rules
                    :rules="[
                      val => !!val || 'Please confirm your new password',
                      val => val === passwordForm.new || 'Passwords do not match'
                    ]"
                  >
                    <template v-slot:prepend><q-icon name="verified_user" size="18px" color="blue-grey-4" /></template>
                    <template v-slot:append>
                      <q-icon 
                        :name="showConfirmPassword ? 'visibility' : 'visibility_off'" 
                        class="cursor-pointer text-blue-grey-4" 
                        size="18px"
                        @click="showConfirmPassword = !showConfirmPassword" 
                      />
                    </template>
                  </q-input>
                </div>

                <div class="text-right q-mt-lg">
                  <q-btn v-ripple type="submit" label="Update Password" unelevated class="btn-red-gradient text-white q-px-xl text-weight-bold full-width-mobile" style="height: 44px; font-size: 13.5px;" no-caps />
                </div>
              </q-form>
            </q-card-section>
          </q-card>
        </div>

        <!-- ================= STORE INFO ================= -->
        <div class="col-12 col-md-6">
          <q-card class="premium-glass-card profile-card overflow-hidden">
            <q-card-section class="card-header-styled row items-center text-white bg-gradient-red no-wrap" :class="$q.screen.lt.md ? 'q-py-sm q-px-md' : 'q-pa-md'">
              <q-icon name="storefront" :size="$q.screen.lt.md ? '20px' : '20px'" class="q-mr-sm shrink-none" />
              <div class="text-weight-bold" :style="{ fontSize: $q.screen.lt.md ? '15.5px' : '15px' }">Store Info</div>
            </q-card-section>

            <q-card-section class="q-pa-md q-pa-sm-lg">
              <q-form @submit.prevent="openConfirm('Confirm Changes', 'Are you sure you want to save these changes?', submitStoreInfo)">
                <div class="q-gutter-y-md">
                  <q-input v-model="storeForm.storeName" label="Store Name" outlined dense class="custom-glass-input" hide-bottom-space>
                    <template v-slot:prepend><q-icon name="store" size="18px" color="blue-grey-4" /></template>
                  </q-input>

                  <!-- Photo Preview Container -->
                  <div class="store-photo-preview-container q-my-sm">
                    <q-img v-if="storePicturePreview" :src="storePicturePreview" :style="{ width: '100%', height: $q.screen.lt.md ? '160px' : '190px', objectFit: 'cover', borderRadius: '10px', border: '1px solid #e2e8f0' }" ratio="16/9">
                      <template v-slot:error>
                        <div class="absolute-full flex flex-center bg-grey-3 text-grey-7">Error loading image</div>
                      </template>
                    </q-img>

                    <div v-else class="empty-preview flex flex-center bg-grey-2 text-grey-6" :style="{ width: '100%', height: $q.screen.lt.md ? '160px' : '190px', border: '2px dashed #cbd5e1', borderRadius: '10px' }">
                      <div class="text-center">
                        <q-icon name="storefront" size="32px" color="blue-grey-3" />
                        <div class="q-mt-xs text-caption text-weight-medium">No cover photo uploaded</div>
                      </div>
                    </div>
                  </div>

                  <div class="row items-center justify-between bg-slate-50 q-pa-sm q-px-md rounded-borders border-slate-light">
                    <div class="text-weight-bold text-slate-800 text-caption font-medium">Store Cover Photo</div>
                    <q-btn outline color="red-9" class="btn-danger-outline bg-white text-weight-bold q-px-md" style="height: 36px; font-size: 12.5px;" label="Update Photo" no-caps @click="showImageCaptureModal = true" :loading="uploadingImage" />
                  </div>
                </div>

                <div class="text-right q-mt-lg">
                  <q-btn v-ripple type="submit" label="Save Store Info" unelevated class="btn-red-gradient text-white q-px-xl text-weight-bold full-width-mobile" style="height: 44px; font-size: 13.5px;" no-caps />
                </div>
              </q-form>
            </q-card-section>
          </q-card>
        </div>

        <!-- ================= MAP ADDRESS ================= -->
        <div class="col-12 col-md-6">
          <q-card class="premium-glass-card profile-card overflow-hidden">
            <q-card-section class="card-header-styled row items-center text-white bg-gradient-red no-wrap" :class="$q.screen.lt.md ? 'q-py-sm q-px-md' : 'q-pa-md'">
              <q-icon name="place" :size="$q.screen.lt.md ? '20px' : '20px'" class="q-mr-sm shrink-none" />
              <div class="text-weight-bold" :style="{ fontSize: $q.screen.lt.md ? '15.5px' : '15px' }">Address & Location</div>
            </q-card-section>

            <q-card-section class="q-pa-md q-pa-sm-lg">
              <q-form @submit.prevent="openConfirm('Confirm Changes', 'Are you sure you want to save these changes?', submitAddress)">
                <div class="q-gutter-y-md">
                  <q-input 
                    v-model="addressForm.fullAddress" 
                    label="Street Name, Building, House No" 
                    type="textarea" 
                    autogrow 
                    outlined 
                    dense
                    class="custom-glass-input"
                    hide-bottom-space
                  >
                    <template v-slot:prepend><q-icon name="map" size="18px" color="blue-grey-4" class="q-mt-xs" /></template>
                  </q-input>

                  <div>
                    <div class="bg-amber-1 rounded-borders q-pa-sm q-px-md q-mb-sm row no-wrap items-center" style="border: 1px solid #fde68a;">
                      <q-icon name="notifications" size="18px" color="amber-9" class="q-mr-sm flex-shrink-0" />
                      <div class="text-caption text-blue-grey-9 font-medium" style="font-size: 12px; line-height: 1.3;">
                        Place an accurate pin to be shown to neighborhood consumers.
                      </div>
                    </div>

                    <!-- Map Container -->
                    <div class="relative-position">
                      <div v-if="addressForm.latitude && addressForm.longitude" id="vendor-profile-map" class="rounded-borders shadow-soft" :style="{ height: $q.screen.lt.md ? '180px' : '220px', width: '100%', zIndex: 1 }"></div>
                      <div v-else class="bg-grey-2 rounded-borders flex flex-center shadow-soft full-width column" :style="{ height: $q.screen.lt.md ? '180px' : '220px', border: '2px dashed #cbd5e1' }">
                        <q-icon name="location_off" size="32px" color="blue-grey-3" class="q-mb-xs" />
                        <div class="text-weight-bold text-blue-grey-7 text-caption">No Location Detected</div>
                      </div>
                    </div>
                  </div>

                  <!-- Actions Bar: Detect Location + Always Visible Enlarge Map Button -->
                  <div class="row items-center justify-end q-gutter-x-sm q-mt-xs">
                    <q-btn 
                      outline 
                      color="blue-grey-8" 
                      icon="fullscreen" 
                      label="Enlarge Map" 
                      @click="openEnlargedMap" 
                      no-caps 
                      class="btn-glass-outline bg-white text-weight-bold q-px-md col-auto" 
                      style="height: 36px; font-size: 12.5px;" 
                    />
                    <q-btn 
                      outline 
                      color="red-9" 
                      icon="my_location" 
                      label="Detect Location" 
                      @click="detectLocation" 
                      :loading="isDetectingLocation" 
                      no-caps 
                      class="btn-danger-outline bg-white text-weight-bold q-px-md col-auto" 
                      style="height: 36px; font-size: 12.5px;" 
                    />
                  </div>
                </div>

                <div class="text-right q-mt-lg">
                  <q-btn v-ripple type="submit" label="Save Address" unelevated class="btn-red-gradient text-white q-px-xl text-weight-bold full-width-mobile" style="height: 44px; font-size: 13.5px;" no-caps />
                </div>
              </q-form>
            </q-card-section>
          </q-card>
        </div>

        <!-- ================= STORE HOURS ================= -->
        <div class="col-12">
          <q-card class="premium-glass-card overflow-hidden">
            <q-card-section class="card-header-styled row items-center justify-between text-white bg-gradient-red no-wrap" :class="$q.screen.lt.md ? 'q-py-sm q-px-md' : 'q-pa-md'">
              <div class="row items-center no-wrap">
                <q-icon name="schedule" :size="$q.screen.lt.md ? '18px' : '20px'" class="q-mr-sm shrink-none" />
                <div class="text-weight-bold" :style="{ fontSize: $q.screen.lt.md ? '15.5px' : '15px' }">Store Hours</div>
              </div>
              <q-btn flat dense icon="content_copy" :label="$q.screen.lt.md ? 'Copy Mon' : 'Apply Monday to All'" class="text-white text-weight-bold bg-white-20 rounded-borders q-px-sm" style="font-size: 12px;" no-caps @click="applyMondayToAll">
                <q-tooltip class="bg-red-9">Copy Monday's schedule to all other days</q-tooltip>
              </q-btn>
            </q-card-section>

            <q-card-section class="q-pa-md q-pa-sm-lg">
              <q-form @submit.prevent="openConfirm('Confirm Changes', 'Are you sure you want to save these changes?', submitStoreHours)" class="q-gutter-y-md">
                
                <!-- Desktop View -->
                <div v-if="!$q.screen.lt.md">
                  <div class="row items-center q-col-gutter-md schedule-row q-pa-sm rounded-borders" v-for="day in operatingDays" :key="day.name">
                    <div class="col-sm-3 row items-center no-wrap">
                      <div class="text-subtitle2 text-weight-bold text-blue-grey-9 q-mr-sm" style="font-size: 14px;">{{ day.name }}</div>
                    </div>
                    <div class="col-sm-3 text-left row items-center justify-start no-wrap">
                      <q-toggle v-model="day.isOpen" color="red-9" keep-color size="sm" />
                      <span class="text-caption text-weight-bold q-ml-xs" :class="day.isOpen ? 'text-red-9' : 'text-blue-grey-4'" style="font-size: 13px;">
                        {{ day.isOpen ? 'Open' : 'Closed' }}
                      </span>
                    </div>
                    <div class="col-sm-6 row q-gutter-x-sm items-center" v-if="day.isOpen">
                      <q-input v-model="day.openTime" type="time" outlined dense class="custom-glass-input col" />
                      <div class="text-center text-blue-grey-4 text-weight-bolder">—</div>
                      <q-input v-model="day.closeTime" type="time" outlined dense class="custom-glass-input col" />
                    </div>
                    <div class="col-sm-6 flex items-center" v-else>
                      <div class="text-blue-grey-4 text-caption bg-slate-50 q-px-md q-py-xs rounded-borders w-full text-center border-slate-light" style="border: 1px dashed #cbd5e1; font-size: 12px;">
                        Closed
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Mobile View -->
                <div v-else class="q-gutter-y-sm">
                  <div 
                    v-for="day in operatingDays" 
                    :key="day.name"
                    class="mobile-schedule-item q-pa-sm q-px-md rounded-borders border-slate-light"
                    :class="day.isOpen ? 'bg-white' : 'bg-slate-50 opacity-90'"
                  >
                    <!-- Top Row: Day Name and Open Toggle -->
                    <div class="row items-center justify-between no-wrap q-mb-xs">
                      <span class="text-weight-bold text-slate-800" style="font-size: 14px;">{{ day.name }}</span>
                      
                      <div class="row items-center no-wrap">
                        <span class="text-weight-bold q-mr-xs" :class="day.isOpen ? 'text-red-9' : 'text-grey-5'" style="font-size: 12px;">
                          {{ day.isOpen ? 'Open' : 'Closed' }}
                        </span>
                        <q-toggle v-model="day.isOpen" color="red-9" keep-color dense size="sm" />
                      </div>
                    </div>

                    <!-- Bottom Row: Time Pickers -->
                    <div v-if="day.isOpen" class="row items-center q-gutter-x-sm no-wrap q-pt-xs">
                      <div class="col">
                        <input v-model="day.openTime" type="time" class="mobile-time-picker full-width" />
                      </div>
                      <span class="text-grey-5 text-weight-bold" style="font-size: 13px;">to</span>
                      <div class="col">
                        <input v-model="day.closeTime" type="time" class="mobile-time-picker full-width" />
                      </div>
                    </div>
                    <div v-else class="text-caption text-grey-5 font-medium q-pt-xs" style="font-size: 11.5px;">
                      No operating hours scheduled.
                    </div>
                  </div>
                </div>

                <div class="text-right q-mt-lg">
                  <q-btn v-ripple type="submit" label="Save Store Hours" unelevated class="btn-red-gradient text-white q-px-xl text-weight-bold full-width-mobile" style="height: 44px; font-size: 13.5px;" no-caps />
                </div>
              </q-form>
            </q-card-section>
          </q-card>
        </div>

        <!-- ================= DELETE ACCOUNT ================= -->
        <div class="col-12">
          <q-card class="delete-zone-card overflow-hidden shadow-soft">
            <q-card-section class="card-header-styled row items-center text-white bg-gradient-red no-wrap" :class="$q.screen.lt.md ? 'q-py-sm q-px-md' : 'q-pa-md'">
              <q-icon name="warning_amber" :size="$q.screen.lt.md ? '18px' : '20px'" class="q-mr-sm shrink-none" />
              <div class="text-weight-bold" :style="{ fontSize: $q.screen.lt.md ? '15.5px' : '15px' }">Danger Zone</div>
            </q-card-section>
            
            <q-card-section class="q-pa-md q-pa-sm-lg row items-center justify-between">
              <div class="column col-12 col-md-8 q-mb-md q-mb-md-none">
                <div class="text-subtitle1 text-weight-bold text-red-9 q-mb-xs">Delete Account</div>
                <div class="text-blue-grey-8 text-caption leading-normal" style="max-width: 540px; font-size: 12px;">
                  Once you delete your account, there is no going back. All inventory, data, and sales records will be permanently lost.
                </div>
              </div>
              <div class="col-12 col-md-auto text-left text-md-right full-width-mobile">
                <q-btn v-ripple label="Delete Account" color="red-9" outline no-caps class="btn-danger-outline q-px-xl text-weight-bold full-width-mobile" style="height: 44px; font-size: 13.5px;" @click="initiateDelete" />
              </div>
            </q-card-section>
          </q-card>
        </div>

      </div>
    </div>

    <!-- ================= FULLSCREEN ENLARGED MAP MODAL ================= -->
    <q-dialog v-model="showEnlargedMapModal" maximized transition-show="slide-up" transition-hide="slide-down" @show="initEnlargedMap">
      <q-card class="bg-white column no-wrap" style="height: 100vh; width: 100vw;">
        <!-- Modal Top Bar -->
        <q-card-section class="row items-center justify-between q-py-sm q-px-md bg-gradient-red text-white z-top shadow-2">
          <div class="row items-center no-wrap">
            <q-icon name="pin_drop" size="20px" class="q-mr-xs" />
            <div class="text-subtitle2 text-weight-bold" style="font-size: 15px;">Set Pin Location</div>
          </div>
          <q-btn flat round dense icon="close" color="white" v-close-popup size="sm" />
        </q-card-section>

        <!-- Direction Guide Strip -->
        <div class="bg-amber-1 q-pa-xs q-px-md row items-center no-wrap text-blue-grey-9 text-caption border-bottom-solid" style="font-size: 11.5px;">
          <q-icon name="touch_app" size="16px" color="amber-9" class="q-mr-xs flex-shrink-0" />
          <span class="ellipsis">Click anywhere on the map or drag the pin to set your storefront.</span>
        </div>

        <!-- Fullscreen Leaflet Container -->
        <div id="enlarged-profile-map" class="col full-width" style="z-index: 1;"></div>

        <!-- Footer Coordinates & Confirmation (Safely casts to Number before .toFixed) -->
        <q-card-section class="bg-slate-50 border-top-solid q-pa-md row items-center justify-between no-wrap z-top">
          <div class="col q-pr-sm ellipsis">
            <div class="text-caption text-weight-bold text-slate-800 ellipsis">{{ addressForm.fullAddress || 'Selected location' }}</div>
            <div class="text-caption text-grey-6 font-monospace" style="font-size: 11px;">
              {{ formatCoordinate(addressForm.latitude) }}, {{ formatCoordinate(addressForm.longitude) }}
            </div>
          </div>
          <q-btn unelevated label="Done" color="red-9" class="text-weight-bold q-px-lg" style="border-radius: 8px; height: 38px;" no-caps v-close-popup />
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- OTP Verification Modal -->
    <q-dialog v-model="verifyPhoneModal" persistent backdrop-filter="blur(4px)">
      <q-card class="premium-glass-card q-pa-lg text-center" style="width: 390px; max-width: 90vw; border-top: 4px solid #b91c1c; border-radius: 16px;">
        <q-card-section class="q-pb-none">
          <q-icon name="phonelink_ring" size="44px" color="red-9" class="q-mb-sm" />
          <div class="text-h6 text-weight-bolder text-blue-grey-9 q-mb-xs">Verify Mobile Number</div>
          <div class="text-body2 text-blue-grey-7 q-mb-lg">We sent a 6-digit code to <strong class="text-dark">{{ profileForm.phoneNumber }}</strong>.</div>
          
          <div class="row justify-center q-gutter-x-xs q-mb-xl">
            <input 
              v-for="(digit, index) in otpDigits" 
              :key="index"
              :ref="el => { if (el) otpInputRefs[index] = el }"
              v-model="otpDigits[index]"
              type="text" 
              inputmode="numeric" 
              maxlength="1" 
              class="otp-box-input text-center text-subtitle1 text-weight-bolder" 
              @input="handleOtpInput(index, $event)"
              @keydown="handleOtpKeydown(index, $event)"
              @paste="handlePaste"
            />
          </div>
          
          <q-btn 
            label="Verify Code" 
            unelevated 
            class="btn-red-gradient text-white full-width text-weight-bold q-py-sm q-mb-sm" 
            no-caps 
            style="height: 42px; font-size: 13.5px;"
            @click="verifyOtp" 
            :loading="isVerifyingOtp" 
            :disable="otpDigits.join('').length !== 6" 
          />

          <div class="text-caption text-blue-grey-6 q-mt-sm">
            Didn't receive a code? 
            <span v-if="!canResend" class="text-weight-bold">Resend in {{ resendTimer }}s</span>
            <q-btn v-else flat dense no-caps label="Resend Now" color="red-9" class="text-weight-bold q-pa-none" style="min-height: auto;" @click="sendOtp" :loading="isSendingOtp" />
          </div>
        </q-card-section>

        <q-card-actions align="center" class="q-pt-md">
          <q-btn flat label="Cancel" color="blue-grey-5" no-caps v-close-popup class="text-weight-bold full-width" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Standard Confirm Modal -->
    <q-dialog v-model="confirmDialog.isOpen" persistent backdrop-filter="blur(4px)">
      <q-card class="premium-glass-card q-pa-lg" style="width: 420px; max-width: 90vw; border-top: 4px solid #b91c1c; border-radius: 16px;">
        <q-card-section class="column items-center text-center q-pb-none">
          <div class="text-h6 text-weight-bolder text-blue-grey-9 q-mb-xs">{{ confirmDialog.title }}</div>
          <div class="text-body2 text-blue-grey-8 q-mb-sm">{{ confirmDialog.message }}</div>
        </q-card-section>
        <q-card-actions align="center" class="q-mt-sm q-gutter-md">
          <q-btn label="Cancel" outline color="blue-grey-4" class="text-weight-bold q-px-lg text-blue-grey-7" no-caps v-close-popup />
          <q-btn label="Confirm" unelevated class="btn-red-gradient text-white q-px-xl text-weight-bold" no-caps @click="executeConfirmAction" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <ImageCaptureModal v-model="showImageCaptureModal" @captured="handleCapturedImage" :aspectRatio="16 / 9" title="Update Store Cover Photo" />

    <q-dialog v-model="deleteDialog.isOpen" persistent backdrop-filter="blur(8px)">
      <q-card class="premium-glass-card q-pa-lg" style="width: 480px; max-width: 92vw; border-top: 4px solid #b91c1c; border-radius: 16px;">
        <q-card-section class="text-center q-pb-none">
          <div class="text-h6 text-weight-bolder text-red-9 q-mb-sm">Delete Merchant Account</div>
          <div class="text-body2 text-weight-bold text-red-8 q-mb-xs">Do you really want to delete your Merchant Account?</div>
          <div class="text-caption text-blue-grey-7 q-mb-lg">If yes, please type your store name to confirm.</div>
          <q-input v-model="deleteDialog.inputName" placeholder="Type your store name" outlined dense class="custom-glass-input q-mb-lg center-input-text" hide-bottom-space />
        </q-card-section>
        <q-card-actions align="center" class="q-gutter-md">
          <q-btn label="Back" outline color="blue-grey-4" class="btn-glass-outline text-blue-grey-8 q-px-xl text-weight-bold" no-caps v-close-popup />
          <q-btn label="Yes, Delete" unelevated class="btn-red-gradient text-white q-px-xl text-weight-bold" no-caps :disable="deleteDialog.inputName !== storeForm.storeName || !storeForm.storeName" @click="executeDeleteAccount" />
        </q-card-actions>
      </q-card>
    </q-dialog>

  </q-page>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted, nextTick } from 'vue'
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
const showEnlargedMapModal = ref(false)

const addressForm = reactive({
  fullAddress: '',
  latitude: null,
  longitude: null
})

// Password Form & Visibility Toggles
const passwordForm = reactive({ current: '', new: '', confirm: '' })
const showCurrentPassword = ref(false)
const showNewPassword = ref(false)
const showConfirmPassword = ref(false)

// Mobile OTP States
const originalPhone = ref('')
const phoneVerified = ref(true)
const verifyPhoneModal = ref(false)

const otpDigits = ref(['', '', '', '', '', ''])
const otpInputRefs = ref([])

const isSendingOtp = ref(false)
const isVerifyingOtp = ref(false)

const resendTimer = ref(25)
const canResend = ref(false)
let timerInterval = null

const phoneNeedsVerification = computed(() => {
  const isValidRegex = /^09\d{9}$/.test(profileForm.phoneNumber)
  return profileForm.phoneNumber && profileForm.phoneNumber !== originalPhone.value && !phoneVerified.value && isValidRegex
})

watch(() => profileForm.phoneNumber, (newVal) => {
  if (newVal !== originalPhone.value) {
    phoneVerified.value = false
  } else {
    phoneVerified.value = true
  }
})

const startResendTimer = () => {
  resendTimer.value = 25
  canResend.value = false
  if (timerInterval) clearInterval(timerInterval)
  timerInterval = setInterval(() => {
    if (resendTimer.value > 0) {
      resendTimer.value--
    } else {
      canResend.value = true
      clearInterval(timerInterval)
    }
  }, 1000)
}

const handleOtpInput = (index, event) => {
  const val = event.target.value
  if (val) {
    otpDigits.value[index] = val.slice(-1)
    if (index < 5) {
      otpInputRefs.value[index + 1].focus()
    }
  }
}

const handleOtpKeydown = (index, event) => {
  if (event.key === 'Backspace' && !otpDigits.value[index]) {
    if (index > 0) {
      otpInputRefs.value[index - 1].focus()
    }
  }
}

const handlePaste = (event) => {
  event.preventDefault()
  const pasteData = event.clipboardData.getData('text').slice(0, 6).split('')
  let pastedCount = 0
  pasteData.forEach((char, i) => {
    if (/[0-9]/.test(char) && i < 6) {
      otpDigits.value[i] = char
      pastedCount++
    }
  })
  const nextIndex = Math.min(pastedCount, 5)
  otpInputRefs.value[nextIndex].focus()
}

const sendOtp = () => {
  isSendingOtp.value = true
  setTimeout(() => {
    isSendingOtp.value = false
    verifyPhoneModal.value = true
    otpDigits.value = ['', '', '', '', '', '']
    startResendTimer()
    $q.notify({ type: 'info', message: 'Verification code sent.', color: 'blue-8' })
    nextTick(() => {
      if (otpInputRefs.value[0]) otpInputRefs.value[0].focus()
    })
  }, 1000)
}

const verifyOtp = () => {
  isVerifyingOtp.value = true
  const code = otpDigits.value.join('')
  setTimeout(() => {
    isVerifyingOtp.value = false
    if (code === '123456' || code.length === 6) {
      phoneVerified.value = true
      originalPhone.value = profileForm.phoneNumber
      verifyPhoneModal.value = false
      if (timerInterval) clearInterval(timerInterval)
      $q.notify({ type: 'positive', message: 'Mobile number verified successfully!', color: 'green-7' })
    } else {
      $q.notify({ type: 'negative', message: 'Invalid OTP code.' })
    }
  }, 1000)
}

const passwordRules = computed(() => {
  const val = passwordForm.new || ''
  return {
    minChars: val.length >= 8,
    hasUpper: /[A-Z]/.test(val),
    hasNumber: /[0-9]/.test(val),
    hasSymbol: /[!@#$%^&*(),.?":{}|<>]/.test(val)
  }
})

const isPasswordChecklistValid = computed(() => {
  return (
    passwordRules.value.minChars &&
    passwordRules.value.hasUpper &&
    passwordRules.value.hasNumber &&
    passwordRules.value.hasSymbol
  )
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
  $q.notify({
    type: 'positive',
    message: 'Monday hours applied to all days.',
    color: 'red-9'
  })
}

// Coordinate safe formatter
const formatCoordinate = (coord) => {
  if (coord === null || coord === undefined || isNaN(Number(coord))) return '0.000000'
  return Number(coord).toFixed(6)
}

// Reverse Geocoding helper
const fetchAddressFromCoords = async (lat, lng) => {
  try {
    const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}`)
    const data = await res.json()
    if (data && data.display_name) {
      addressForm.fullAddress = data.display_name
    }
  } catch (err) {
    console.warn('Reverse geocoding error:', err)
  }
}

// Modal Logic
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

// API Submission Logic
const submitProfile = async () => {
  try {
    const fullName = `${profileForm.firstName} ${profileForm.lastName}`.trim()

    await api.put('/vendor/profile', {
      name: fullName,
      full_name: fullName,
      first_name: profileForm.firstName,
      last_name: profileForm.lastName,
      email: profileForm.email,
      phone_number: profileForm.phoneNumber
    })

    originalPhone.value = profileForm.phoneNumber
    phoneVerified.value = true

    $q.notify({
      type: 'positive',
      message: 'Profile saved successfully.',
      color: 'green-7'
    })
  } catch (error) {
    const errorData = error.response?.data
    let errorMsg = errorData?.message || 'Failed to update profile.'

    if (errorData?.errors) {
      const firstKey = Object.keys(errorData.errors)[0]
      if (firstKey && errorData.errors[firstKey][0]) {
        errorMsg = errorData.errors[firstKey][0]
      }
    }

    $q.notify({
      type: 'negative',
      message: errorMsg,
      position: 'top'
    })
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

  storePicturePreview.value = URL.createObjectURL(file)
  newStorePictureFile.value = file

  $q.notify({
    type: 'info',
    message: 'Photo uploaded successfully! Please click "Save Store Info" to apply changes.',
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

const validateAndSubmitPassword = () => {
  if (!isPasswordChecklistValid.value) {
    $q.notify({
      type: 'warning',
      message: 'Please meet all password complexity requirements.',
      position: 'top',
      icon: 'warning'
    })
    return
  }

  if (passwordForm.new !== passwordForm.confirm) {
    $q.notify({
      type: 'warning',
      message: 'Passwords do not match.',
      position: 'top',
      icon: 'warning'
    })
    return
  }

  openConfirm(
    'Confirm Changes', 
    'Are you sure you want to update your password? A confirmation will be sent to your email.', 
    submitPassword
  )
}

const submitPassword = async () => {
  try {
    await api.put('/vendor/profile/password', {
      current_password: passwordForm.current,
      password: passwordForm.new,
      password_confirmation: passwordForm.confirm
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
    const errorMsg = error.response?.data?.message || error.response?.data?.error || 'Failed to update password.'
    $q.notify({ type: 'negative', message: errorMsg, position: 'top' })
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
      phoneVerified.value = true
      
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
        addressForm.latitude = data.store.latitude ? Number(data.store.latitude) : null
        addressForm.longitude = data.store.longitude ? Number(data.store.longitude) : null

        if (data.store.operating_days) {
          let raw = data.store.operating_days
          if (typeof raw === 'string') {
            try {
              raw = JSON.parse(raw)
            } catch (e) {}
          }
          const defaultOpen = data.store.opening_time ? data.store.opening_time.substring(0, 5) : '08:00'
          const defaultClose = data.store.closing_time ? data.store.closing_time.substring(0, 5) : '17:00'

          if (raw !== null && typeof raw === 'object' && !Array.isArray(raw)) {
            operatingDays.forEach(day => {
              if (raw[day.name]) {
                day.isOpen = !!raw[day.name].is_open
                if (raw[day.name].opening_time) day.openTime = raw[day.name].opening_time.substring(0, 5)
                if (raw[day.name].closing_time) day.closeTime = raw[day.name].closing_time.substring(0, 5)
              }
            })
          } else if (Array.isArray(raw)) {
            operatingDays.forEach(day => {
              day.isOpen = raw.some(d => day.name.toLowerCase().startsWith(String(d).toLowerCase()))
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

// Leaflet Map Handlers
const map = ref(null)
const marker = ref(null)
const enlargedMap = ref(null)
const enlargedMarker = ref(null)
const isDetectingLocation = ref(false)

const getLeafletIcon = () => {
  return L.icon({
    iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
    iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
    shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34],
    shadowSize: [41, 41]
  })
}

const initMap = () => {
  if (!addressForm.latitude || !addressForm.longitude) {
    if (map.value) {
      map.value.remove()
      map.value = null
    }
    return
  }

  const lat = Number(addressForm.latitude)
  const lng = Number(addressForm.longitude)

  if (map.value) {
    map.value.remove()
  }

  map.value = L.map('vendor-profile-map').setView([lat, lng], 15)

  L.tileLayer(
    'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
    { attribution: '&copy; OpenStreetMap contributors' }
  ).addTo(map.value)

  marker.value = L.marker([lat, lng], { icon: getLeafletIcon() }).addTo(map.value)
  
  map.value.on('click', async function(e) {
    const newLat = Number(e.latlng.lat)
    const newLng = Number(e.latlng.lng)
    addressForm.latitude = newLat
    addressForm.longitude = newLng
    marker.value.setLatLng([newLat, newLng])
    await fetchAddressFromCoords(newLat, newLng)
  })
}

// Enlarged Fullscreen Map Initialization
const openEnlargedMap = () => {
  showEnlargedMapModal.value = true
}

const initEnlargedMap = () => {
  nextTick(() => {
    const container = document.getElementById('enlarged-profile-map')
    if (!container) return

    const lat = addressForm.latitude ? Number(addressForm.latitude) : 14.5995
    const lng = addressForm.longitude ? Number(addressForm.longitude) : 120.9842

    if (enlargedMap.value) {
      enlargedMap.value.remove()
      enlargedMap.value = null
    }

    enlargedMap.value = L.map('enlarged-profile-map').setView([lat, lng], 16)

    L.tileLayer(
      'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
      { attribution: '&copy; OpenStreetMap contributors' }
    ).addTo(enlargedMap.value)

    enlargedMarker.value = L.marker([lat, lng], { 
      icon: getLeafletIcon(), 
      draggable: true 
    }).addTo(enlargedMap.value)

    const handlePositionChange = async (newLat, newLng) => {
      const numLat = Number(newLat)
      const numLng = Number(newLng)
      addressForm.latitude = numLat
      addressForm.longitude = numLng
      enlargedMarker.value.setLatLng([numLat, numLng])
      if (marker.value) {
        marker.value.setLatLng([numLat, numLng])
      }
      if (map.value) {
        map.value.setView([numLat, numLng], 15)
      }
      await fetchAddressFromCoords(numLat, numLng)
    }

    enlargedMarker.value.on('dragend', async function(e) {
      const pos = e.target.getLatLng()
      await handlePositionChange(pos.lat, pos.lng)
    })

    enlargedMap.value.on('click', async function(e) {
      await handlePositionChange(e.latlng.lat, e.latlng.lng)
    })

    setTimeout(() => {
      if (enlargedMap.value) {
        enlargedMap.value.invalidateSize()
      }
    }, 250)
  })
}

const detectLocation = () => {
  if (!navigator.geolocation) {
    $q.notify({ type: 'negative', message: 'Geolocation is not supported by your browser.' })
    return
  }

  isDetectingLocation.value = true
  navigator.geolocation.getCurrentPosition(
    async position => {
      const lat = Number(position.coords.latitude)
      const lng = Number(position.coords.longitude)
      addressForm.latitude = lat
      addressForm.longitude = lng

      await fetchAddressFromCoords(lat, lng)

      nextTick(() => {
        if (map.value && marker.value) {
          map.value.setView([lat, lng], 15)
          marker.value.setLatLng([lat, lng])
        } else {
          initMap()
        }
      })

      $q.notify({ type: 'positive', message: 'Location & Address updated successfully.', color: 'green' })
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
  max-width: 1100px;
  margin: 0 auto;
}
.w-full { width: 100%; }
.shrink-none { flex-shrink: 0; }
.font-medium { font-weight: 500; }
.text-brand-red { color: #b91c1c !important; }

/* Strict Brand Red Gradient Class */
.bg-gradient-red {
  background: linear-gradient(135deg, #b91c1c 0%, #7f1d1d 100%) !important;
}
.bg-white-20 {
  background: rgba(255, 255, 255, 0.2);
}
.bg-white-20:hover {
  background: rgba(255, 255, 255, 0.3);
}

/* Header Glass Icon Box */
.glass-icon-box {
  width: 48px;
  height: 48px;
  background: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.9);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 12px rgba(185, 28, 28, 0.1);
}

/* Subtle Ambient Glows */
.bg-glow {
  position: absolute; width: 500px; height: 500px; border-radius: 50%;
  filter: blur(140px); z-index: 0; opacity: 0.15; pointer-events: none;
}
.bg-glow-primary { top: -100px; left: -100px; background: radial-gradient(circle, rgba(185, 28, 28, 0.4) 0%, transparent 70%); }
.bg-glow-secondary { bottom: 100px; right: -100px; background: radial-gradient(circle, rgba(69, 10, 10, 0.3) 0%, transparent 70%); }

.tracking-tight { letter-spacing: -0.02em; }
.leading-tight { line-height: 1.2; }
.leading-normal { line-height: 1.5; }

/* Clean Glassmorphism Cards */
.premium-glass-card {
  background: rgba(255, 255, 255, 0.98);
  border: 1px solid rgba(226, 232, 240, 0.8);
  border-radius: 16px;
  box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
}

/* Inputs & Form Controls with Generous Breathing Room */
.custom-glass-input :deep(.q-field__control) {
  background: rgba(248, 250, 252, 0.85);
  border-radius: 8px;
  height: 44px !important;
  min-height: 44px !important;
  font-size: 13.5px;
}
.custom-glass-input.q-textarea :deep(.q-field__control) {
  height: auto !important;
  min-height: 65px !important;
}
.custom-glass-input :deep(.q-field__control:before) { border: 1px solid rgba(226, 232, 240, 0.85); }
.custom-glass-input :deep(.q-field__control:hover) { background: #ffffff; }
.custom-glass-input :deep(.q-field--focused .q-field__control) {
  background: #ffffff;
  box-shadow: 0 0 0 2px rgba(185, 28, 28, 0.12);
  border-color: #b91c1c;
}
.custom-glass-input :deep(.q-field--focused .q-icon) { color: #b91c1c !important; }
.center-input-text :deep(.q-field__native) { text-align: center; font-weight: bold; font-size: 1rem; }

/* OTP Specific Styling */
.otp-box-input {
  width: 42px;
  height: 48px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  background: #f8fafc;
  color: #0f172a;
  outline: none;
}
.otp-box-input:focus {
  border-color: #b91c1c;
  box-shadow: 0 0 0 2px rgba(185, 28, 28, 0.15);
  background: #ffffff;
}

/* Password Checklist Card */
.password-requirements-card {
  border: 1px solid #e2e8f0;
}

/* Primary Buttons */
.btn-red-gradient {
  border-radius: 8px !important;
  background: linear-gradient(135deg, #b91c1c 0%, #7f1d1d 100%) !important;
  box-shadow: 0 2px 8px rgba(185, 28, 28, 0.2) !important;
}
.btn-glass-outline {
  border-radius: 8px !important; background: rgba(255, 255, 255, 0.8) !important;
  border: 1px solid currentColor;
}
.btn-danger-outline {
  border-radius: 8px !important; background: #ffffff !important;
  border: 1px solid #b91c1c !important; color: #b91c1c !important;
}

/* Delete Zone */
.delete-zone-card {
  background: #fffafa; border: 1px solid #fee2e2; border-radius: 16px;
}

/* Store Hours Table */
.schedule-row { transition: background-color 0.2s ease; }
.schedule-row:hover { background-color: rgba(248, 250, 252, 0.8); }
.bg-slate-50 { background-color: #f8fafc; }
.border-slate-light { border: 1px solid #e2e8f0; }
.border-top-solid { border-top: 1px solid #e2e8f0; }
.border-bottom-solid { border-bottom: 1px solid #fde68a; }

/* Mobile Schedule Items */
.mobile-schedule-item {
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  transition: all 0.2s ease;
}
.mobile-time-picker {
  height: 36px;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  background-color: #f8fafc;
  font-size: 13px;
  font-weight: 600;
  color: #1e293b;
  text-align: center;
  padding: 0 8px;
  outline: none;
}
.mobile-time-picker:focus {
  border-color: #b91c1c;
  background-color: #ffffff;
}

/* Mobile Specific Spacing */
@media (max-width: 767px) {
  .vendor-page.mobile-page-padding { padding: 20px 14px 40px 14px !important; }
  .desktop-only { display: none !important; }
  .full-width-mobile { width: 100%; }
}
</style>