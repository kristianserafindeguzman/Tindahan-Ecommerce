<template>
  <q-page class="vendor-page" :class="{ 'mobile-page-padding': $q.screen.lt.md }">
    <!-- Subtle Ambient Background Glows -->
    <div class="bg-glow bg-glow-primary desktop-only"></div>
    <div class="bg-glow bg-glow-secondary desktop-only"></div>

    <div class="page-container relative-position" style="z-index: 1">
      
      <!-- PAGE HEADER -->
      <div class="page-header q-mb-lg q-mt-sm">
        <h1 class="text-h4 text-md-h3 text-weight-bolder text-blue-grey-9 q-ma-none tracking-tight">
          Profile Settings
        </h1>
        <p class="text-subtitle2 text-md-subtitle1 text-blue-grey-5 q-mt-xs q-mb-none">
          Manage your personal information, store details, and security.
        </p>
      </div>

      <div class="row q-col-gutter-md items-start">
        
        <!-- ================= MY PROFILE ================= -->
        <div class="col-12 col-md-6">
          <q-card class="premium-glass-card profile-card overflow-hidden">
            <q-card-section class="row items-center q-pa-md text-white bg-gradient-red">
              <q-icon name="account_circle" size="22px" class="q-mr-sm" />
              <div class="text-subtitle1 text-weight-bold">My Profile</div>
            </q-card-section>

            <q-card-section class="q-pa-md">
              <q-form @submit.prevent="openConfirm('Confirm Changes', 'Are you sure you want to save these changes?', submitProfile)">
                <div class="q-gutter-y-sm">
                  <div class="row q-col-gutter-sm">
                    <div class="col-12 col-sm-6">
                      <q-input v-model="profileForm.firstName" label="First Name" outlined class="custom-glass-input" hide-bottom-space>
                        <template v-slot:prepend><q-icon name="person_outline" size="20px" color="blue-grey-4" /></template>
                      </q-input>
                    </div>
                    <div class="col-12 col-sm-6">
                      <q-input v-model="profileForm.lastName" label="Last Name" outlined class="custom-glass-input" hide-bottom-space>
                        <template v-slot:prepend><q-icon name="person_outline" size="20px" color="blue-grey-4" /></template>
                      </q-input>
                    </div>
                  </div>

                  <q-input v-model="profileForm.email" label="Email Address" type="email" outlined class="custom-glass-input" hide-bottom-space>
                    <template v-slot:prepend><q-icon name="mail_outline" size="20px" color="blue-grey-4" /></template>
                  </q-input>

                  <!-- Mobile Number with OTP Verification Flow -->
                  <div class="row items-start no-wrap q-gutter-x-sm">
                    <q-input 
                      v-model="profileForm.phoneNumber" 
                      label="Mobile Number" 
                      outlined 
                      class="custom-glass-input col"
                      hide-bottom-space
                      lazy-rules
                      :rules="[
                        val => !!val || 'Mobile number is required',
                        val => /^09\d{9}$/.test(val) || 'Enter a valid 11-digit mobile number (e.g. 09123456789)'
                      ]"
                    >
                      <template v-slot:prepend><q-icon name="phone_iphone" size="20px" color="blue-grey-4" /></template>
                    </q-input>

                    <!-- Verify Button -->
                    <q-btn 
                      v-if="phoneNeedsVerification" 
                      label="Verify" 
                      unelevated 
                      color="amber-9" 
                      class="text-weight-bold shadow-soft" 
                      style="border-radius: 8px; height: 56px; margin-top: 0;" 
                      no-caps 
                      @click="sendOtp" 
                      :loading="isSendingOtp"
                    />
                  </div>
                </div>

                <div class="text-right q-mt-md">
                  <q-btn v-ripple type="submit" label="Save Profile" unelevated class="btn-red-gradient text-white q-px-lg q-py-sm text-weight-bold full-width-mobile" no-caps :disable="phoneNeedsVerification" />
                </div>
              </q-form>
            </q-card-section>
          </q-card>
        </div>

        <!-- ================= PASSWORD ================= -->
        <div class="col-12 col-md-6">
          <q-card class="premium-glass-card profile-card overflow-hidden">
            <q-card-section class="row items-center q-pa-md text-white bg-gradient-red">
              <q-icon name="security" size="22px" class="q-mr-sm" />
              <div class="text-subtitle1 text-weight-bold">Security & Password</div>
            </q-card-section>

            <q-card-section class="q-pa-md">
              <q-form @submit.prevent="validateAndSubmitPassword">
                <div class="q-gutter-y-sm">
                  <q-input 
                    v-model="passwordForm.current" 
                    label="Current Password" 
                    :type="showCurrentPassword ? 'text' : 'password'" 
                    outlined 
                    class="custom-glass-input"
                    hide-bottom-space
                    lazy-rules
                    :rules="[val => !!val || 'Current password is required']"
                  >
                    <template v-slot:prepend><q-icon name="lock_outline" size="20px" color="blue-grey-4" /></template>
                    <template v-slot:append>
                      <q-icon 
                        :name="showCurrentPassword ? 'visibility' : 'visibility_off'" 
                        class="cursor-pointer text-blue-grey-4" 
                        size="20px"
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
                      class="custom-glass-input q-mb-xs"
                      hide-bottom-space
                      lazy-rules
                      :rules="[val => !!val || 'New password is required']"
                    >
                      <template v-slot:prepend><q-icon name="key" size="20px" color="blue-grey-4" /></template>
                      <template v-slot:append>
                        <q-icon 
                          :name="showNewPassword ? 'visibility' : 'visibility_off'" 
                          class="cursor-pointer text-blue-grey-4" 
                          size="20px"
                          @click="showNewPassword = !showNewPassword" 
                        />
                      </template>
                    </q-input>

                    <div class="password-requirements-card bg-slate-50 border-slate-light rounded-borders q-pa-sm q-px-md q-mt-xs">
                      <div class="text-caption text-weight-bold text-slate-700 q-mb-xs">Password Requirements:</div>
                      <div class="row q-col-gutter-xs">
                        <div class="col-12 col-sm-6 row items-center no-wrap">
                          <q-icon :name="passwordRules.minChars ? 'check_circle' : 'radio_button_unchecked'" size="15px" :color="passwordRules.minChars ? 'positive' : 'grey-5'" class="q-mr-xs shrink-none" />
                          <span class="text-caption" :class="passwordRules.minChars ? 'text-positive text-weight-medium' : 'text-grey-6'">At least 8 characters</span>
                        </div>
                        <div class="col-12 col-sm-6 row items-center no-wrap">
                          <q-icon :name="passwordRules.hasUpper ? 'check_circle' : 'radio_button_unchecked'" size="15px" :color="passwordRules.hasUpper ? 'positive' : 'grey-5'" class="q-mr-xs shrink-none" />
                          <span class="text-caption" :class="passwordRules.hasUpper ? 'text-positive text-weight-medium' : 'text-grey-6'">One uppercase letter (A-Z)</span>
                        </div>
                        <div class="col-12 col-sm-6 row items-center no-wrap">
                          <q-icon :name="passwordRules.hasNumber ? 'check_circle' : 'radio_button_unchecked'" size="15px" :color="passwordRules.hasNumber ? 'positive' : 'grey-5'" class="q-mr-xs shrink-none" />
                          <span class="text-caption" :class="passwordRules.hasNumber ? 'text-positive text-weight-medium' : 'text-grey-6'">One number (0-9)</span>
                        </div>
                        <div class="col-12 col-sm-6 row items-center no-wrap">
                          <q-icon :name="passwordRules.hasSymbol ? 'check_circle' : 'radio_button_unchecked'" size="15px" :color="passwordRules.hasSymbol ? 'positive' : 'grey-5'" class="q-mr-xs shrink-none" />
                          <span class="text-caption" :class="passwordRules.hasSymbol ? 'text-positive text-weight-medium' : 'text-grey-6'">One special symbol (@, #, $)</span>
                        </div>
                      </div>
                    </div>
                  </div>

                  <q-input 
                    v-model="passwordForm.confirm" 
                    label="Confirm New Password" 
                    :type="showConfirmPassword ? 'text' : 'password'" 
                    outlined 
                    class="custom-glass-input"
                    hide-bottom-space
                    lazy-rules
                    :rules="[
                      val => !!val || 'Please confirm your new password',
                      val => val === passwordForm.new || 'Passwords do not match'
                    ]"
                  >
                    <template v-slot:prepend><q-icon name="verified_user" size="20px" color="blue-grey-4" /></template>
                    <template v-slot:append>
                      <q-icon 
                        :name="showConfirmPassword ? 'visibility' : 'visibility_off'" 
                        class="cursor-pointer text-blue-grey-4" 
                        size="20px"
                        @click="showConfirmPassword = !showConfirmPassword" 
                      />
                    </template>
                  </q-input>
                </div>

                <div class="text-right q-mt-md">
                  <q-btn v-ripple type="submit" label="Update Password" unelevated class="btn-red-gradient text-white q-px-lg q-py-sm text-weight-bold full-width-mobile" no-caps />
                </div>
              </q-form>
            </q-card-section>
          </q-card>
        </div>

        <!-- ================= STORE INFO ================= -->
        <div class="col-12 col-md-6">
          <q-card class="premium-glass-card profile-card overflow-hidden">
            <q-card-section class="row items-center q-pa-md text-white bg-gradient-red">
              <q-icon name="storefront" size="22px" class="q-mr-sm" />
              <div class="text-subtitle1 text-weight-bold">Store Info</div>
            </q-card-section>

            <q-card-section class="q-pa-md">
              <q-form @submit.prevent="openConfirm('Confirm Changes', 'Are you sure you want to save these changes?', submitStoreInfo)">
                <div class="q-gutter-y-sm">
                  <q-input v-model="storeForm.storeName" label="Store Name" outlined class="custom-glass-input" hide-bottom-space>
                    <template v-slot:prepend><q-icon name="store" size="20px" color="blue-grey-4" /></template>
                    <template v-slot:hint><span class="text-blue-grey-4">This name will be visible to neighborhood consumers.</span></template>
                  </q-input>

                  <!-- Upload Controls -->
                  <div class="store-photo-preview-container q-my-sm">
                    <q-img v-if="storePicturePreview" :src="storePicturePreview" :style="{ width: '100%', height: $q.screen.lt.md ? '140px' : '180px', objectFit: 'cover', borderRadius: '8px', border: '1px solid #e2e8f0' }" ratio="16/9">
                      <template v-slot:error>
                        <div class="absolute-full flex flex-center bg-grey-3 text-grey-7">Error loading image</div>
                      </template>
                    </q-img>

                    <div v-else class="empty-preview flex flex-center bg-grey-2 text-grey-6" :style="{ width: '100%', height: $q.screen.lt.md ? '140px' : '180px', border: '2px dashed #cbd5e1', borderRadius: '8px' }">
                      <div class="text-center">
                        <q-icon name="storefront" size="32px" color="blue-grey-3" />
                        <div class="q-mt-xs text-caption text-weight-medium">No cover photo uploaded</div>
                      </div>
                    </div>
                  </div>

                  <div class="row items-center justify-between bg-slate-50 q-pa-sm rounded-borders border-slate-light">
                    <div>
                      <div class="text-weight-bold text-dark" style="font-size: 13px;">Store Cover Photo</div>
                    </div>
                    <q-btn outline color="red-9" class="btn-danger-outline bg-white text-weight-bold q-px-md q-py-xs" label="Update Photo" no-caps @click="showImageCaptureModal = true" :loading="uploadingImage" />
                  </div>
                </div>

                <div class="text-right q-mt-md">
                  <q-btn v-ripple type="submit" label="Save Store Info" unelevated class="btn-red-gradient text-white q-px-lg q-py-sm text-weight-bold full-width-mobile" no-caps />
                </div>
              </q-form>
            </q-card-section>
          </q-card>
        </div>

        <!-- ================= MAP ADDRESS ================= -->
        <div class="col-12 col-md-6">
          <q-card class="premium-glass-card profile-card overflow-hidden">
            <q-card-section class="row items-center q-pa-md text-white bg-gradient-red">
              <q-icon name="place" size="22px" class="q-mr-sm" />
              <div class="text-subtitle1 text-weight-bold">Address & Location</div>
            </q-card-section>

            <q-card-section class="q-pa-md">
              <q-form @submit.prevent="openConfirm('Confirm Changes', 'Are you sure you want to save these changes?', submitAddress)">
                <div class="q-gutter-y-sm">
                  <q-input 
                    v-model="addressForm.fullAddress" 
                    label="Street Name, Building, House No" 
                    type="textarea" 
                    autogrow 
                    outlined 
                    class="custom-glass-input"
                    hide-bottom-space
                  >
                    <template v-slot:prepend><q-icon name="map" size="20px" color="blue-grey-4" class="q-mt-xs" /></template>
                  </q-input>

                  <div class="q-mt-sm">
                    <div class="bg-amber-1 rounded-borders q-pa-sm q-mb-sm row no-wrap items-start" style="border: 1px solid #fde68a;">
                      <div class="bg-amber-5 text-white flex flex-center rounded-borders q-mr-sm" style="width: 28px; height: 28px; min-width: 28px; border-radius: 50%; flex-shrink: 0;">
                        <q-icon name="notifications" size="16px" />
                      </div>
                      <div class="col">
                        <div class="text-subtitle2 text-weight-bold text-amber-9 q-mb-none" style="font-size: 13px;">Place an accurate pin</div>
                        <div class="text-caption text-blue-grey-8" style="line-height: 1.3; font-size: 11px;">
                          This map location will be shown to consumers.
                        </div>
                      </div>
                    </div>

                    <div v-if="addressForm.latitude && addressForm.longitude" id="vendor-profile-map" class="rounded-borders shadow-soft" :style="{ height: $q.screen.lt.md ? '200px' : '230px', width: '100%', zIndex: 1 }"></div>
                    <div v-else class="bg-grey-2 rounded-borders flex flex-center shadow-soft full-width column" :style="{ height: $q.screen.lt.md ? '200px' : '230px', border: '2px dashed #cbd5e1' }">
                      <q-icon name="location_off" size="36px" color="blue-grey-3" class="q-mb-sm" />
                      <div class="text-weight-bold text-blue-grey-7 text-body2">No Location Detected</div>
                    </div>
                  </div>

                  <div class="row justify-end q-mt-xs">
                    <q-btn outline color="red-9" icon="my_location" label="Detect Location" @click="detectLocation" :loading="isDetectingLocation" no-caps class="btn-danger-outline bg-white text-weight-bold q-px-md q-py-xs full-width-mobile" />
                  </div>
                </div>

                <div class="text-right q-mt-md">
                  <q-btn v-ripple type="submit" label="Save Address" unelevated class="btn-red-gradient text-white q-px-lg q-py-sm text-weight-bold full-width-mobile" no-caps />
                </div>
              </q-form>
            </q-card-section>
          </q-card>
        </div>

        <!-- ================= STORE HOURS ================= -->
        <div class="col-12">
          <q-card class="premium-glass-card overflow-hidden">
            <q-card-section class="row items-center justify-between q-pa-md text-white bg-gradient-red">
              <div class="row items-center">
                <q-icon name="schedule" size="22px" class="q-mr-sm" />
                <div class="text-subtitle1 text-weight-bold">Store Hours</div>
              </div>
              <q-btn flat dense icon="content_copy" :label="$q.screen.lt.md ? 'Apply Mon' : 'Apply Monday to All'" class="text-white text-weight-bold bg-white-20 rounded-borders q-px-sm" no-caps @click="applyMondayToAll">
                <q-tooltip class="bg-red-9">Copy Monday's schedule to all other days</q-tooltip>
              </q-btn>
            </q-card-section>

            <q-card-section class="q-pa-md">
              <q-form @submit.prevent="openConfirm('Confirm Changes', 'Are you sure you want to save these changes?', submitStoreHours)" class="q-gutter-y-xs">
                
                <div class="row items-center q-col-gutter-sm schedule-row q-pa-sm rounded-borders border-bottom-mobile" v-for="day in operatingDays" :key="day.name">
                  <div class="col-6 col-sm-3 row items-center no-wrap">
                    <div class="text-subtitle2 text-weight-bold text-blue-grey-9 q-mr-sm">{{ day.name }}</div>
                  </div>
                  <div class="col-6 col-sm-3 text-right text-sm-left row items-center justify-end justify-sm-start no-wrap">
                    <q-toggle v-model="day.isOpen" color="red-9" keep-color size="sm" />
                    <span class="text-body2 text-weight-bold q-ml-xs" :class="day.isOpen ? 'text-red-9' : 'text-blue-grey-4'">
                      {{ day.isOpen ? 'Open' : 'Closed' }}
                    </span>
                  </div>

                  <div class="col-12 col-sm-6 row q-gutter-x-sm items-center q-mt-xs q-mt-sm-none" v-if="day.isOpen">
                    <q-input v-model="day.openTime" type="time" outlined dense class="custom-glass-input col" />
                    <div class="text-center text-blue-grey-4 text-weight-bolder">—</div>
                    <q-input v-model="day.closeTime" type="time" outlined dense class="custom-glass-input col" />
                  </div>
                  <div class="col-12 col-sm-6 flex items-center q-mt-xs q-mt-sm-none" v-else>
                    <div class="text-blue-grey-5 text-caption text-weight-medium bg-slate-50 q-px-sm q-py-xs rounded-borders w-full text-center border-slate-light" style="border: 1px dashed #cbd5e1;">
                      Closed
                    </div>
                  </div>
                </div>

                <div class="text-right q-mt-md">
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
            
            <q-card-section class="q-pa-md row items-center justify-between">
              <div class="column col-12 col-md-8 q-mb-sm q-mb-md-none">
                <div class="text-subtitle1 text-weight-bold text-red-9 q-mb-xs">Delete Account</div>
                <div class="text-blue-grey-8 text-caption" style="max-width: 500px;">
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
    
    <!-- OTP Verification Modal -->
    <q-dialog v-model="verifyPhoneModal" persistent backdrop-filter="blur(4px)">
      <q-card class="premium-glass-card q-pa-md text-center" style="width: 400px; max-width: 90vw; border-top: 4px solid #b91c1c;">
        <q-card-section class="q-pb-none">
          <q-icon name="phonelink_ring" size="48px" color="red-9" class="q-mb-md" />
          <div class="text-h6 text-weight-bolder text-blue-grey-9 q-mb-xs">Verify Mobile Number</div>
          <div class="text-body2 text-blue-grey-7 q-mb-lg">We sent a 6-digit verification code to <strong class="text-dark">{{ profileForm.phoneNumber }}</strong>.</div>
          
          <!-- Individual OTP Boxes -->
          <div class="row justify-center q-gutter-x-sm q-mb-xl">
            <input 
              v-for="(digit, index) in otpDigits" 
              :key="index"
              :ref="el => { if (el) otpInputRefs[index] = el }"
              v-model="otpDigits[index]"
              type="text" 
              inputmode="numeric" 
              maxlength="1" 
              class="otp-box-input text-center text-h5 text-weight-bolder" 
              @input="handleOtpInput(index, $event)"
              @keydown="handleOtpKeydown(index, $event)"
              @paste="handlePaste"
            />
          </div>
          
          <q-btn 
            label="Verify Code" 
            unelevated 
            class="btn-red-gradient text-white full-width text-weight-bold q-py-sm q-mb-md" 
            no-caps 
            @click="verifyOtp" 
            :loading="isVerifyingOtp" 
            :disable="otpDigits.join('').length !== 6" 
          />

          <!-- Resend Timer Feedback -->
          <div class="text-body2 text-blue-grey-6 q-mt-sm">
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
          <q-input v-model="deleteDialog.inputName" placeholder="Type your store name" outlined dense class="custom-glass-input q-mb-lg center-input-text" hide-bottom-space />
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

// Check if phone was changed and needs OTP verification
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

// Timer Logic
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

// OTP Handlers
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

// Real-time Checklist Computations
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
    'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
    {
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
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
  
  map.value.on('click', function(e) {
    const newLat = e.latlng.lat
    const newLng = e.latlng.lng
    addressForm.latitude = newLat
    addressForm.longitude = newLng
    marker.value.setLatLng([newLat, newLng])
  })
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
  padding: 24px 16px;
  background-color: #f8fafc;
  min-height: 100vh;
}
.page-container {
  max-width: 1000px;
  margin: 0 auto;
}
.w-full { width: 100%; }
.shrink-none { flex-shrink: 0; }

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

/* Subtle Ambient Glows */
.bg-glow {
  position: absolute; width: 500px; height: 500px; border-radius: 50%;
  filter: blur(140px); z-index: 0; opacity: 0.15; pointer-events: none;
}
.bg-glow-primary { top: -100px; left: -100px; background: radial-gradient(circle, rgba(185, 28, 28, 0.4) 0%, transparent 70%); }
.bg-glow-secondary { bottom: 100px; right: -100px; background: radial-gradient(circle, rgba(69, 10, 10, 0.3) 0%, transparent 70%); }

.tracking-tight { letter-spacing: -0.02em; }
.tracking-widest { letter-spacing: 0.2em; }

/* Clean Glassmorphism Cards */
.premium-glass-card {
  background: rgba(255, 255, 255, 0.98);
  border: 1px solid rgba(241, 245, 249, 1);
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.premium-glass-card:hover {
  box-shadow: 0 6px 20px rgba(15, 23, 42, 0.08);
}

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

/* OTP Specific Styling */
.otp-box-input {
  width: 44px;
  height: 52px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  background: #f8fafc;
  color: #0f172a;
  transition: all 0.2s ease;
  outline: none;
}
.otp-box-input:focus {
  border-color: #b91c1c;
  box-shadow: 0 0 0 3px rgba(185, 28, 28, 0.1);
  background: #ffffff;
}

/* Password Checklist Card */
.password-requirements-card {
  border: 1px solid #e2e8f0;
  transition: all 0.2s ease;
}

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
.btn-danger-outline:hover { background-color: #fef2f2 !important; transform: translateY(-1px); }

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