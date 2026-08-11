<template>
  <q-page class="profile-page">

    <SiteHeader />

    <div class="profile-container">
      <div class="page-header-block">
        <h1 class="page-title">Profile Settings</h1>
        <p class="page-subtitle">Manage your personal information and security preferences.</p>
      </div>

      <div class="row q-col-gutter-lg items-stretch">

        <!-- ================= PERSONAL INFO ================= -->
        <div class="col-12 col-md-8">
          <q-card flat bordered class="profile-card profile-card-fill">
            <q-card-section>
              <div class="card-header">
                <div>
                  <div class="section-title">Personal Information</div>
                  <div class="section-subtitle">View and update your personal details.</div>
                </div>
                <q-btn
                  outline
                  no-caps
                  color="primary"
                  icon="o_edit"
                  label="Edit"
                  class="card-action-btn"
                  @click="startEditPersonal"
                />
              </div>

              <div class="info-row">
                <div class="info-icon"><q-icon name="o_person" size="18px" /></div>
                <div class="info-body">
                  <div class="info-label">Name</div>
                  <div class="info-value">{{ user.full_name }}</div>
                </div>
              </div>

              <div class="info-row">
                <div class="info-icon"><q-icon name="o_phone" size="18px" /></div>
                <div class="info-body">
                  <div class="info-label">Phone Number</div>
                  <div class="info-value-row">
                    <div class="info-value">{{ user.phone_number }}</div>
                  </div>
                </div>
              </div>

              <div class="info-row">
                <div class="info-icon"><q-icon name="o_mail" size="18px" /></div>
                <div class="info-body">
                  <div class="info-label">Email Address</div>
                  <div class="info-value-row">
                    <div class="info-value">{{ user.email }}</div>
                  </div>
                </div>
              </div>
            </q-card-section>
          </q-card>
        </div>

        <!-- ================= PROFILE PHOTO ================= -->
        <div class="col-12 col-md-4 col-photo">
          <q-card flat bordered class="profile-card profile-card-fill">
            <q-card-section class="photo-card-section">
              <div>
                <div class="section-title">Profile Photo</div>
                <div class="section-subtitle">This will be displayed on your account.</div>
              </div>

              <div class="photo-card-body">
                <div class="text-center q-mb-md relative-position">
                  <q-avatar :size="avatarSize" class="bg-grey-3 photo-avatar">
                    <img v-if="photoPreview" :src="photoPreview" />
                    <img v-else-if="user.profile_picture_url" :src="user.profile_picture_url" />
                    <q-icon v-else name="person" size="64px" color="grey-6" />
                  </q-avatar>

                  <input type="file" id="photoUpload" accept="image/*" class="hidden" @change="onFileSelected" style="display: none;" />
                  <q-btn round unelevated color="primary" class="photo-camera-btn" @click="triggerUpload">
                    <q-icon name="o_photo_camera" size="14px" />
                  </q-btn>
                </div>

                <div class="text-center">
                  <template v-if="!photoFile">
                    <q-btn outline no-caps color="primary" label="Change Photo" class="full-width" @click="triggerUpload" />
                  </template>
                  <template v-else>
                    <q-btn unelevated no-caps color="primary" label="Save Photo" :loading="savingPhoto" @click="savePhoto" class="full-width q-mb-sm btn-gradient" />
                    <q-btn flat no-caps color="grey-7" label="Cancel" class="full-width" :disable="savingPhoto" @click="cancelPhoto" />
                  </template>
                </div>
                <div class="text-center photo-hint">JPG, PNG or GIF. Max size of 2MB.</div>
              </div>
            </q-card-section>
          </q-card>
        </div>

        <div class="col-12">

          <!-- ================= SECURITY ================= -->
          <q-card flat bordered class="profile-card q-mb-lg">
            <q-card-section>
              <div class="card-header">
                <div>
                  <div class="section-title">Security</div>
                  <div class="section-subtitle">Keep your account secure.</div>
                </div>
              </div>

              <div class="info-row info-row-last">
                <div class="info-icon"><q-icon name="o_lock" size="18px" /></div>
                <div class="info-body">
                  <div class="info-label">Password</div>
                  <div class="info-value">••••••••••••</div>
                </div>
                <q-btn
                  outline
                  no-caps
                  color="primary"
                  icon="o_lock"
                  label="Change Password"
                  class="card-action-btn"
                  @click="showPasswordModal = true"
                />
              </div>
            </q-card-section>
          </q-card>

          <!-- ================= DANGER ZONE ================= -->
          <q-card flat bordered class="profile-card danger-card">
            <q-card-section>
              <div class="section-title text-red-9">Danger Zone</div>
              <div class="section-subtitle q-mb-md">Actions here are permanent and cannot be undone.</div>

              <div class="danger-row" @click="confirmDeleteAccount">
                <div class="info-icon danger-icon"><q-icon name="o_delete" size="18px" /></div>
                <div class="info-body">
                  <div class="danger-title">Delete My Account</div>
                  <div class="danger-desc">Permanently delete your account and all data.</div>
                </div>
                <q-icon name="o_chevron_right" size="20px" color="red-4" />
              </div>
            </q-card-section>
          </q-card>

        </div>
      </div>
    </div>

    <SiteFooter />

    <!-- EDIT PERSONAL INFORMATION DIALOG -->
    <q-dialog v-model="showEditPersonalModal" persistent transition-show="scale" transition-hide="scale">
      <q-card class="profile-dialog-card edit-personal-card" style="width: 620px; max-width: 90vw;">
        <q-card-section class="dialog-header">
          <div class="dialog-header-text">
            <div class="text-h6">Edit Personal Information</div>
            <div class="section-subtitle">Update your personal details below.</div>
          </div>
          <q-btn flat round dense icon="o_close" class="dialog-close-btn" :disable="savingPersonal" @click="attemptCloseEditPersonal" />
        </q-card-section>

        <q-form ref="editPersonalFormRef">
          <q-card-section class="dialog-body">
            <div class="edit-field-row">
              <div class="edit-field">
                <div class="edit-field-label">First Name</div>
                <q-input v-model="editForm.firstName" outlined dense no-error-icon hide-bottom-space :rules="[val => !!val || 'Required']" />
              </div>

              <div class="edit-field">
                <div class="edit-field-label">Last Name</div>
                <q-input v-model="editForm.lastName" outlined dense no-error-icon hide-bottom-space :rules="[val => !!val || 'Required']" />
              </div>
            </div>

            <div class="edit-field edit-field-tight">
              <div class="edit-field-label-row">
                <div class="edit-field-label">Phone Number</div>
              </div>
              <q-input
                v-model="editForm.phone_number"
                outlined
                dense
                no-error-icon
                hide-bottom-space
                maxlength="11"
                :error="phoneMessage?.type === 'error'"
                @keypress="blockNonDigitKey"
                @paste="onPhoneNumberPaste"
              />
              <div v-if="phoneMessage" class="edit-field-hint" :class="`edit-field-hint-${phoneMessage.type}`">
                <q-icon v-if="phoneMessage.type === 'success'" name="o_check_circle" size="12px" />
                {{ phoneMessage.text }}
              </div>
            </div>

            <div class="edit-field edit-field-tight">
              <div class="edit-field-label-row">
                <div class="edit-field-label">Email Address</div>
              </div>
              <q-input
                v-model="editForm.email"
                outlined
                dense
                type="email"
                no-error-icon
                hide-bottom-space
                :error="emailMessage?.type === 'error'"
              />
              <div v-if="emailMessage" class="edit-field-hint" :class="`edit-field-hint-${emailMessage.type}`">
                <q-icon v-if="emailMessage.type === 'success'" name="o_check_circle" size="12px" />
                {{ emailMessage.text }}
              </div>
            </div>
          </q-card-section>
        </q-form>

        <q-card-actions align="right">
          <q-btn outline no-caps label="Cancel" color="grey-7" :disable="savingPersonal" @click="attemptCloseEditPersonal" />
          <q-btn
            unelevated
            no-caps
            color="primary"
            label="Save Changes"
            :loading="savingPersonal"
            :disable="!canSavePersonal"
            class="btn-gradient"
            @click="savePersonal"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- DISCARD CHANGES CONFIRMATION (Edit Personal Information / Change Password) -->
    <q-dialog v-model="showDiscardConfirm" :persistent="discardingChanges" transition-show="scale" transition-hide="scale">
      <q-card class="profile-dialog-card discard-confirm-card" style="width: 420px; max-width: 90vw;">
        <q-card-section class="dialog-header discard-confirm-header">
          <div class="dialog-header-text">
            <div class="text-h6">Discard Changes?</div>
            <div class="section-subtitle">You have unsaved changes. If you leave now, your changes will not be saved.</div>
          </div>
        </q-card-section>
        <q-card-actions class="discard-confirm-actions">
          <q-btn outline no-caps label="Keep Editing" color="grey-7" autofocus :disable="discardingChanges" v-close-popup />
          <q-btn unelevated no-caps label="Discard" class="btn-danger-gradient" :loading="discardingChanges" :disable="discardingChanges" @click="confirmDiscardChanges" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- SUCCESS DIALOG (shared: personal info, password, photo updates) -->
    <q-dialog v-model="showSuccessModal" transition-show="scale" transition-hide="scale">
      <q-card class="profile-dialog-card success-card" style="width: 500px; max-width: 90vw;">
        <q-card-section class="text-center">
          <div class="success-icon">
            <q-icon name="o_check" size="32px" />
          </div>
          <div class="text-h6 q-mt-sm">{{ successModal.title }}</div>
          <p class="section-subtitle q-mt-sm">{{ successModal.message }}</p>
          <q-btn unelevated no-caps color="primary" label="Done" class="full-width btn-gradient q-mt-md" autofocus v-close-popup />
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- CROP DIALOG -->
    <q-dialog v-model="showCropModal" persistent transition-show="scale" transition-hide="scale">
      <q-card class="profile-dialog-card" style="width: 560px; max-width: 90vw;">
        <q-card-section class="dialog-header">
          <div class="dialog-header-text">
            <div class="text-h6">Crop Profile Photo</div>
            <div class="section-subtitle">Drag to select a square crop area.</div>
          </div>
          <q-btn flat round dense icon="o_close" class="dialog-close-btn" @click="showCropModal = false" />
        </q-card-section>
        <q-card-section class="dialog-body text-center">
          <canvas
            ref="cropCanvas"
            style="border: 1px dashed #ccc; cursor: crosshair; max-width: 100%;"
            @mousedown="onCropMouseDown"
            @mousemove="onCropMouseMove"
            @mouseup="onCropMouseUp"
            @mouseleave="onCropMouseUp"
          ></canvas>
        </q-card-section>
        <q-card-actions align="right">
          <q-btn outline no-caps label="Cancel" color="grey-7" @click="showCropModal = false" />
          <q-btn unelevated no-caps color="primary" label="Apply Crop" :disable="!canApplyCrop" class="btn-gradient" @click="applyCrop" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- CHANGE PASSWORD DIALOG -->
    <q-dialog v-model="showPasswordModal" persistent transition-show="scale" transition-hide="scale">
      <q-card class="profile-dialog-card" style="width: 480px; max-width: 90vw;">
        <q-card-section class="dialog-header">
          <div class="dialog-header-text">
            <div class="text-h6">Change Password</div>
            <div class="section-subtitle">Keep your account secure with a strong password.</div>
          </div>
          <q-btn flat round dense icon="o_close" class="dialog-close-btn" :disable="savingPassword" @click="attemptClosePasswordModal" />
        </q-card-section>

        <q-form ref="passwordFormRef">
          <q-card-section class="dialog-body">
            <div class="edit-field">
              <div class="edit-field-label">Current Password</div>
              <q-input
                v-model="passwords.current"
                outlined
                dense
                no-error-icon
                hide-bottom-space
                :type="showCurrentPassword ? 'text' : 'password'"
                :rules="[val => !!val || 'Current password is required']"
              >
                <template #append>
                  <q-icon
                    :name="showCurrentPassword ? 'o_visibility' : 'o_visibility_off'"
                    class="password-icon cursor-pointer"
                    @click="showCurrentPassword = !showCurrentPassword"
                  />
                </template>
              </q-input>
            </div>

            <div class="edit-field edit-field-tight">
              <div class="edit-field-label">New Password</div>
              <q-input
                v-model="passwords.new"
                outlined
                dense
                no-error-icon
                hide-bottom-space
                :type="showNewPassword ? 'text' : 'password'"
                :error="newPasswordMessage?.type === 'error'"
              >
                <template #append>
                  <q-icon
                    :name="showNewPassword ? 'o_visibility' : 'o_visibility_off'"
                    class="password-icon cursor-pointer"
                    @click="showNewPassword = !showNewPassword"
                  />
                </template>
              </q-input>
              <div v-if="newPasswordMessage" class="edit-field-hint" :class="`edit-field-hint-${newPasswordMessage.type}`">
                <q-icon v-if="newPasswordMessage.type === 'success'" name="o_check_circle" size="12px" />
                {{ newPasswordMessage.text }}
              </div>
            </div>

            <div class="edit-field edit-field-tight">
              <div class="edit-field-label">Confirm New Password</div>
              <q-input
                v-model="passwords.confirm"
                outlined
                dense
                no-error-icon
                hide-bottom-space
                :type="showConfirmPassword ? 'text' : 'password'"
                :error="confirmPasswordMessage?.type === 'error'"
              >
                <template #append>
                  <q-icon
                    :name="showConfirmPassword ? 'o_visibility' : 'o_visibility_off'"
                    class="password-icon cursor-pointer"
                    @click="showConfirmPassword = !showConfirmPassword"
                  />
                </template>
              </q-input>
              <div v-if="confirmPasswordMessage" class="edit-field-hint" :class="`edit-field-hint-${confirmPasswordMessage.type}`">
                <q-icon v-if="confirmPasswordMessage.type === 'success'" name="o_check_circle" size="12px" />
                {{ confirmPasswordMessage.text }}
              </div>
            </div>
          </q-card-section>
        </q-form>

        <q-card-actions align="right">
          <q-btn outline no-caps label="Cancel" color="grey-7" :disable="savingPassword" @click="attemptClosePasswordModal" />
          <q-btn
            unelevated
            no-caps
            color="primary"
            label="Update Password"
            :loading="savingPassword"
            :disable="!canSavePassword"
            class="btn-gradient"
            @click="savePassword"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- OTP VERIFICATION DIALOG -->
    <q-dialog v-model="showOtpModal" persistent transition-show="scale" transition-hide="scale">
      <q-card class="profile-dialog-card" style="width: 500px; max-width: 90vw;">
        <q-card-section class="dialog-header">
          <div class="dialog-header-text">
            <div class="text-h6">Verify New Phone</div>
            <div class="section-subtitle">Enter the 6-digit verification code sent to {{ maskedPhone }}. Sent via SMS.</div>
          </div>
          <q-btn flat round dense icon="o_close" class="dialog-close-btn" :disable="verifyingOtp" @click="cancelOtp" />
        </q-card-section>
        <q-card-section class="dialog-body text-center">
          <!-- Same boxed OTP pattern as ConsumerVerify.vue / LoginPage.vue's forgot-password flow -->
          <div class="otp-row">
            <input
              v-for="(digit, i) in otpInput"
              :key="i"
              v-model="otpInput[i]"
              type="text"
              inputmode="numeric"
              maxlength="1"
              class="otp-box"
              :class="{ 'otp-box-error': otpError, 'otp-box-success': otpVerifiedFlash }"
              :disabled="otpVerifiedFlash"
              @input="onOtpInput(i)"
              @keydown.backspace="onOtpBackspace(i)"
              @paste="onOtpPaste"
              :ref="el => { otpRefs[i] = el }"
            />
          </div>
          <div v-if="otpError" class="text-red otp-error q-mt-sm">{{ otpError }}</div>

          <div class="otp-meta">
            <span class="otp-resend">
              <template v-if="canResendOtp">
                Didn't receive the code?
                <a href="#" class="otp-resend-link" @click.prevent="resendOtpCode">Resend Code</a>
              </template>
              <template v-else>
                Didn't receive the code? Resend in {{ resendSecondsLeft }}s
              </template>
            </span>
          </div>
        </q-card-section>
        <q-card-actions align="right">
          <q-btn outline no-caps label="Cancel" color="grey-7" :disable="verifyingOtp" @click="cancelOtp" />
          <q-btn unelevated no-caps color="primary" label="Verify & Save" :loading="verifyingOtp" :disable="!canVerifyOtp" class="btn-gradient" @click="verifyOtp" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- DELETE ACCOUNT DIALOG -->
    <q-dialog v-model="showDeleteModal" persistent transition-show="scale" transition-hide="scale">
      <q-card class="profile-dialog-card delete-dialog-card" style="width: 540px; max-width: 90vw;">
        <q-card-section class="dialog-header">
          <div class="dialog-header-text">
            <div class="text-h6">Delete Account</div>
            <div class="section-subtitle">This action cannot be undone.</div>
          </div>
          <q-btn flat round dense icon="o_close" class="dialog-close-btn" :disable="deletingAccount" @click="cancelDeleteModal" />
        </q-card-section>

        <q-card-section class="dialog-body">
          <p class="delete-warning">
            Are you absolutely sure you want to delete your account? All of your orders, saved details, and account data will be permanently removed. This cannot be undone.
          </p>

          <div class="edit-field edit-field-tight">
            <div class="edit-field-label">
              Type "{{ deleteConfirmName }}" below to confirm account deletion.
            </div>
            <q-input v-model="deleteConfirmInput" outlined dense no-error-icon :placeholder="deleteConfirmName" />
            <div v-if="deleteConfirmInput && !deleteConfirmMatches" class="edit-field-hint edit-field-hint-error">
              Name doesn't match. Please type "{{ deleteConfirmName }}" exactly.
            </div>
          </div>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn outline no-caps label="Cancel" color="grey-7" :disable="deletingAccount" @click="cancelDeleteModal" />
          <q-btn
            unelevated
            no-caps
            label="Delete Account"
            :loading="deletingAccount"
            :disable="!deleteConfirmMatches"
            class="btn-danger-gradient"
            @click="deleteAccount"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- ACCOUNT DELETED CONFIRMATION -->
    <!-- No close button — the account (and its session) is already gone,
         so the only way out is the explicit "Go to Home" navigation. -->
    <q-dialog v-model="showAccountDeletedModal" persistent transition-show="scale" transition-hide="scale">
      <q-card class="profile-dialog-card success-card" style="width: 500px; max-width: 90vw;">
        <q-card-section class="text-center">
          <div class="success-icon">
            <q-icon name="o_check" size="32px" />
          </div>
          <div class="text-h6 q-mt-sm">Account Deleted!</div>
          <p class="section-subtitle q-mt-sm">Your account has been permanently deleted. Thank you for being part of Tindahan.</p>
          <q-btn unelevated no-caps color="primary" label="Go to Home" class="full-width btn-gradient q-mt-md" autofocus @click="goHomeAfterDelete" />
        </q-card-section>
      </q-card>
    </q-dialog>

  </q-page>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue'
import { useQuasar } from 'quasar'
import { useRouter } from 'vue-router'
import { api } from '@/boot/axios'
import { useAuth } from '@/composables/useAuth'
import SiteHeader from '@/components/consumer/SiteHeader.vue'
import SiteFooter from '@/components/consumer/SiteFooter.vue'

const $q = useQuasar()
const router = useRouter()
const { logout } = useAuth()

// QAvatar's size is an inline style, so it needs $q.screen instead of a media query.
const avatarSize = computed(() => ($q.screen.lt.sm ? '96px' : '120px'))

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

// Personal info edit modal
const showEditPersonalModal = ref(false)
const savingPersonal = ref(false)
const editPersonalFormRef = ref(null)

// Shared success dialog (info/password/photo updates)
const showSuccessModal = ref(false)
const successModal = reactive({ title: '', message: '' })
const openSuccessModal = (title, message) => {
  successModal.title = title
  successModal.message = message
  showSuccessModal.value = true
}
const editForm = reactive({
  firstName: '',
  lastName: '',
  phone_number: '',
  email: ''
})

const phoneChanged = computed(() => editForm.phone_number !== user.value.phone_number)
const emailChanged = computed(() => editForm.email !== user.value.email)
const isPhoneValid = computed(() => /^09\d{9}$/.test(editForm.phone_number || ''))
const isEmailValid = computed(() => /.+@.+\..+/.test(editForm.email || ''))

// One message per field: error > changed-helper > nothing.
const phoneMessage = computed(() => {
  if (!isPhoneValid.value) return { type: 'error', text: 'Phone number must start with 09 and contain 11 digits.' }
  if (phoneChanged.value) return { type: 'helper', text: "We'll send an OTP to verify your new phone number." }
  return null
})

const emailMessage = computed(() => {
  if (!isEmailValid.value) return { type: 'error', text: 'Enter a valid email address.' }
  if (emailChanged.value) return { type: 'helper', text: "We'll send a verification link to your new email." }
  return null
})

// Phone flips to pending during its OTP flow; email flips to pending after any save (no real email-verification step exists yet).
const phoneVerificationRequired = ref(false)
const emailVerificationRequired = ref(false)

// Shared "unsaved changes" guard for both edit modals.
const showDiscardConfirm = ref(false)
const discardingChanges = ref(false)
let pendingDiscardAction = null
const requestClose = (hasChanges, closeFn) => {
  if (hasChanges) {
    pendingDiscardAction = closeFn
    showDiscardConfirm.value = true
  } else {
    closeFn()
  }
}
const confirmDiscardChanges = () => {
  if (discardingChanges.value) return
  discardingChanges.value = true
  if (pendingDiscardAction) pendingDiscardAction()
  pendingDiscardAction = null
  showDiscardConfirm.value = false
  discardingChanges.value = false
}

const hasPersonalChanges = computed(() => {
  const fullName = `${editForm.firstName} ${editForm.lastName}`.trim()
  return (
    fullName !== (user.value.full_name || '') ||
    phoneChanged.value ||
    emailChanged.value
  )
})

const canSavePersonal = computed(() =>
  hasPersonalChanges.value &&
  !!editForm.firstName &&
  !!editForm.lastName &&
  isPhoneValid.value &&
  isEmailValid.value
)

// Blocks at the keystroke rather than via v-model transform, which has a sync-timing gap under fast typing.
const blockNonDigitKey = (event) => {
  if (event.key.length === 1 && !/\d/.test(event.key)) {
    event.preventDefault()
  }
}

const onPhoneNumberPaste = (event) => {
  const pasted = event.clipboardData?.getData('text') || ''
  event.preventDefault()
  editForm.phone_number = `${editForm.phone_number}${pasted.replace(/\D/g, '')}`.slice(0, 11)
}

// Password dialog
const showPasswordModal = ref(false)
const passwordFormRef = ref(null)
const showCurrentPassword = ref(false)
const showNewPassword = ref(false)
const showConfirmPassword = ref(false)

// Same rule as ConsumerRegister.vue's passwordRule.
const isNewPasswordValid = computed(() => passwords.new.length >= 8)

const newPasswordMessage = computed(() => {
  if (!passwords.new) return null
  if (!isNewPasswordValid.value) {
    return { type: 'error', text: 'Minimum 8 characters' }
  }
  return { type: 'success', text: 'Strong password.' }
})

const confirmPasswordMessage = computed(() => {
  if (!passwords.confirm) return null
  if (passwords.confirm !== passwords.new) return { type: 'error', text: 'Passwords do not match.' }
  return { type: 'success', text: 'Passwords match.' }
})

const canSavePassword = computed(() =>
  !!passwords.current &&
  isNewPasswordValid.value &&
  passwords.confirm === passwords.new
)

const hasPasswordChanges = computed(() => !!passwords.current || !!passwords.new || !!passwords.confirm)

// Delete account dialog
const showDeleteModal = ref(false)
const showAccountDeletedModal = ref(false)
const deletingAccount = ref(false)
const deleteConfirmInput = ref('')
const deleteConfirmName = computed(() => (user.value.full_name || '').trim().split(' ')[0] || '')
const deleteConfirmMatches = computed(() =>
  deleteConfirmInput.value.trim().toLowerCase() === deleteConfirmName.value.toLowerCase() && deleteConfirmName.value !== ''
)

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
const canApplyCrop = computed(() => Math.abs(cropRect.w) > 10 && Math.abs(cropRect.h) > 10)

// OTP State
const showOtpModal = ref(false)
const otpInput = ref(['', '', '', '', '', ''])
const otpRefs = ref([])
const otpError = ref('')
const otpVerifiedFlash = ref(false)
const canVerifyOtp = computed(() => otpInput.value.every((digit) => digit !== ''))

// e.g. "09981234567" -> "0998•••4567"
const maskedPhone = computed(() => {
  const digits = form.phone_number || ''
  if (digits.length < 7) return digits
  return `${digits.slice(0, 4)}•••${digits.slice(-4)}`
})

const OTP_EXPIRY_SECONDS = 300
const OTP_RESEND_COOLDOWN = 30
const otpSecondsLeft = ref(OTP_EXPIRY_SECONDS)
const resendSecondsLeft = ref(OTP_RESEND_COOLDOWN)
let otpTimerHandle = null

const canResendOtp = computed(() => resendSecondsLeft.value <= 0)

const startOtpTimers = () => {
  otpSecondsLeft.value = OTP_EXPIRY_SECONDS
  resendSecondsLeft.value = OTP_RESEND_COOLDOWN
  clearInterval(otpTimerHandle)
  otpTimerHandle = setInterval(() => {
    if (otpSecondsLeft.value > 0) otpSecondsLeft.value -= 1
    if (resendSecondsLeft.value > 0) resendSecondsLeft.value -= 1
    if (otpSecondsLeft.value <= 0) {
      clearInterval(otpTimerHandle)
      // The countdown UI was removed, but the user still needs to know why
      // their code stopped working instead of getting a generic "invalid code".
      if (!otpVerifiedFlash.value) otpError.value = 'Code expired. Please resend a new code.'
    }
  }, 1000)
}

const stopOtpTimers = () => {
  clearInterval(otpTimerHandle)
  otpTimerHandle = null
}

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

onUnmounted(() => {
  clearInterval(otpTimerHandle)
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
  if (!canApplyCrop.value) return
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

const cancelPhoto = () => {
  photoFile.value = null
  photoPreview.value = null
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
    
    photoFile.value = null
    photoPreview.value = null
    openSuccessModal('Photo Updated!', 'Your profile photo has been updated successfully.')
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
  } catch (err) {
    $q.notify({ type: 'negative', message: 'Failed to update info.' })
  } finally {
    savingInfo.value = false
  }
}

// --- Personal Info edit modal --- (phone changes still route through the OTP dialog)
const startEditPersonal = () => {
  const parts = (user.value.full_name || '').trim().split(' ')
  editForm.firstName = parts[0] || ''
  editForm.lastName = parts.slice(1).join(' ')
  editForm.phone_number = user.value.phone_number
  editForm.email = user.value.email
  editPersonalFormRef.value?.resetValidation()
  showEditPersonalModal.value = true
}

// Only prompt if there's actually something to lose.
const attemptCloseEditPersonal = () => {
  requestClose(hasPersonalChanges.value, () => { showEditPersonalModal.value = false })
}

const savePersonal = async () => {
  if (!canSavePersonal.value) return
  const isValid = await editPersonalFormRef.value.validate()
  if (!isValid) return

  const nameChanged = `${editForm.firstName} ${editForm.lastName}`.trim() !== user.value.full_name
  const phoneIsChanged = phoneChanged.value
  const emailIsChanged = emailChanged.value

  form.full_name = `${editForm.firstName} ${editForm.lastName}`.trim()
  form.phone_number = editForm.phone_number
  form.email = editForm.email

  savingPersonal.value = true
  try {
    if (nameChanged) await saveInfo()
    if (emailIsChanged) {
      await saveEmail()
      // saveEmail() swallows its own errors, so check the value actually landed.
      if (user.value.email === form.email) {
        emailVerificationRequired.value = true
      }
    }

    showEditPersonalModal.value = false

    if (phoneIsChanged) {
      // Opens the OTP dialog; success modal shows once verifyOtp() succeeds.
      await requestPhoneOtp()
      if (showOtpModal.value) {
        phoneVerificationRequired.value = true
      }
    } else if (nameChanged || emailIsChanged) {
      openSuccessModal('Information Updated!', 'Your personal information has been updated successfully.')
    }
  } finally {
    savingPersonal.value = false
  }
}

const requestPhoneOtp = async () => {
  if (form.phone_number === user.value.phone_number) return
  requestingOtp.value = true
  try {
    await api.post('/profile/phone-request-otp', { phone_number: form.phone_number })
    otpInput.value = ['', '', '', '', '', '']
    otpError.value = ''
    otpVerifiedFlash.value = false
    showOtpModal.value = true
    startOtpTimers()
  } catch (err) {
    $q.notify({ type: 'negative', message: err.response?.data?.message || 'Failed to request OTP.' })
  } finally {
    requestingOtp.value = false
  }
}

const resendOtpCode = async () => {
  if (!canResendOtp.value) return
  try {
    await api.post('/profile/phone-request-otp', { phone_number: form.phone_number })
    otpInput.value = ['', '', '', '', '', '']
    otpError.value = ''
    startOtpTimers()
    otpRefs.value[0]?.focus()
  } catch (err) {
    $q.notify({ type: 'negative', message: err.response?.data?.message || 'Failed to resend code.' })
  }
}

const onOtpInput = (index) => {
  const val = otpInput.value[index]

  if (val && !/^\d$/.test(val)) {
    otpInput.value[index] = ''
    return
  }

  otpError.value = ''

  if (val && index < 5) {
    otpRefs.value[index + 1]?.focus()
  }
  // Auto-submit once every box has a digit.
  if (canVerifyOtp.value) {
    verifyOtp()
  }
}

const onOtpBackspace = (index) => {
  if (!otpInput.value[index] && index > 0) {
    otpRefs.value[index - 1]?.focus()
  }
}

const onOtpPaste = (event) => {
  const pasted = (event.clipboardData?.getData('text') || '').replace(/\D/g, '').slice(0, 6)
  if (!pasted) return
  event.preventDefault()
  pasted.split('').forEach((digit, i) => { otpInput.value[i] = digit })
  const lastIndex = Math.min(pasted.length, 6) - 1
  otpRefs.value[lastIndex]?.focus()
  if (canVerifyOtp.value) verifyOtp()
}

const cancelOtp = () => {
  form.phone_number = user.value.phone_number
  phoneVerificationRequired.value = false
  stopOtpTimers()
  showOtpModal.value = false
}

const verifyOtp = async () => {
  const code = otpInput.value.join('')
  if (code.length !== 6 || verifyingOtp.value) return

  verifyingOtp.value = true
  otpError.value = ''
  try {
    await api.post('/profile/phone-verify-otp', {
      phone_number: form.phone_number,
      code
    })
    user.value.phone_number = form.phone_number
    phoneVerificationRequired.value = false
    stopOtpTimers()
    otpVerifiedFlash.value = true
    // Flash green briefly, then hand off to the shared success dialog.
    setTimeout(() => {
      showOtpModal.value = false
      openSuccessModal('Information Updated!', 'Your personal information has been updated successfully.')
    }, 450)
  } catch (err) {
    otpError.value = err.response?.data?.message || 'Invalid verification code. Please try again.'
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
  } catch (err) {
    $q.notify({ type: 'negative', message: err.response?.data?.message || 'Failed to update email.' })
  } finally {
    savingEmail.value = false
  }
}

const savePassword = async () => {
  const isValid = await passwordFormRef.value.validate()
  if (!isValid) return

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
    showPasswordModal.value = false
    openSuccessModal('Password Updated!', 'Your password has been changed successfully.')
  } catch (err) {
    $q.notify({ type: 'negative', message: err.response?.data?.message || 'Failed to update password.' })
  } finally {
    savingPassword.value = false
  }
}

const cancelPasswordModal = () => {
  passwords.current = ''
  passwords.new = ''
  passwords.confirm = ''
  showCurrentPassword.value = false
  showNewPassword.value = false
  showConfirmPassword.value = false
  passwordFormRef.value?.resetValidation()
  showPasswordModal.value = false
}

const attemptClosePasswordModal = () => {
  requestClose(hasPasswordChanges.value, cancelPasswordModal)
}

const confirmDeleteAccount = () => {
  deleteConfirmInput.value = ''
  showDeleteModal.value = true
}

const cancelDeleteModal = () => {
  deleteConfirmInput.value = ''
  showDeleteModal.value = false
}

const deleteAccount = async () => {
  if (!deleteConfirmMatches.value) return
  deletingAccount.value = true
  try {
    await api.delete('/profile/delete')
    // "Go to Home" in the confirmation dialog is the only way out from here.
    localStorage.removeItem('auth_token')
    localStorage.removeItem('auth_user')
    localStorage.removeItem('auth_role')
    showDeleteModal.value = false
    showAccountDeletedModal.value = true
  } catch (err) {
    $q.notify({ type: 'negative', message: 'Failed to delete account.' })
  } finally {
    deletingAccount.value = false
  }
}

const goHomeAfterDelete = () => {
  router.push('/login')
}
</script>

<style scoped>
/* flex column so SiteFooter's margin-top:auto pins it to the viewport bottom on short pages. */
.profile-page {
  min-height: 100vh;

  display: flex;
  flex-direction: column;

  background: #ffffff;

  font-family: 'Roboto', Arial, sans-serif;
}

/* width:100% needed: margin:0 auto on a flex item shrinks it to content width otherwise. */
.profile-container {
  width: 100%;
  max-width: 1000px;
  box-sizing: border-box;

  margin: 0 auto;

  padding: 24px;
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
  border: 1px solid #e8e8e8;
  border-radius: 10px;

  background: #ffffff;

  /* !important beats Quasar's `flat` prop, which adds its own no-shadow !important utility class. */
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05) !important;

  transition: box-shadow 0.2s, border-color 0.2s;
}

/* No hover lift/red-tint, unlike ProductCard/StoreCard — this card isn't a single clickable unit. */

.profile-card:hover {
  border-color: #cccccc;

  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.06) !important;
}

.profile-card :deep(.q-card__section) {
  padding: 20px;
}

/* Stretch Personal Information and Profile Photo to match heights (items-stretch on parent .row). */
.profile-card-fill {
  display: flex;
  flex-direction: column;

  height: 100%;
}

.profile-card-fill :deep(.q-card__section) {
  display: flex;
  flex-direction: column;

  flex: 1;
}

.photo-card-section {
  justify-content: flex-start;
}

/* Top-anchored, not centered, so extra height (2-button crop state) doesn't crowd the header. */
.photo-card-body {
  display: flex;
  flex-direction: column;

  flex: 1;
  margin-top: 20px;
}

/* CARD HEADER (title/subtitle + optional action button) */

.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;

  gap: 12px;
  margin-bottom: 12px;
}

.section-title {
  font-size: 16px;
  font-weight: 700;
  line-height: 1.3;

  color: #111111;
}

.section-subtitle {
  margin-top: 2px;

  font-size: 12.5px;
  line-height: 1.4;

  color: #767676;
}

/* Sized explicitly (not via q-btn's dense prop) so every pill renders the same height. */
.card-action-btn {
  flex-shrink: 0;

  height: 32px;
  min-height: 32px;
  padding: 0 14px;

  border-radius: 6px;

  font-size: 12.5px;
  font-weight: 600;

  transition: background-color 0.15s, border-color 0.15s;
}

.card-action-btn:hover {
  background: #fdecec;
}

/* Quasar's default icon size (~22px) reads oversized on a 32px pill. */
.card-action-btn :deep(.q-icon) {
  font-size: 18px;
}

/* Quasar's default icon-label gap (12px) reads too loose on a compact pill. */
.card-action-btn :deep(.on-left) {
  margin-right: 8px;
}

.border-red {
  border-color: #fca5a5;
}

/* Excludes round buttons (photo camera badge) so it doesn't flatten Quasar's .q-btn--round. */
.profile-container :deep(.q-btn:not(.q-btn--round)) {
  border-radius: 6px;
}

/* Same close-icon grey used everywhere else a dialog has an X (Terms/Privacy/Support/ProductDetailModal). */
.dialog-close-btn {
  color: #666666;
}

.profile-container :deep(.q-btn) {
  font-size: 13px;
}

.profile-container :deep(.q-field--outlined .q-field__control) {
  border-radius: 12px;
}

/* Primary CTA buttons — same flat red + shadow-lift treatment as Login/Sign Up/Apply Filters. */
.btn-gradient {
  background: #bd2427 !important;

  box-shadow: 0 2px 8px rgba(189, 36, 39, 0.25);

  transition: background-color 0.15s, box-shadow 0.2s, transform 0.2s;
}

.btn-gradient:hover {
  background: #a91e21 !important;

  box-shadow: 0 6px 16px rgba(189, 36, 39, 0.32);

  transform: translateY(-1px);
}

.btn-gradient:active {
  background: #8f1a1c !important;

  box-shadow: 0 2px 6px rgba(189, 36, 39, 0.28);

  transform: translateY(0);
}

.btn-gradient:focus-visible {
  outline: none;
  box-shadow: 0 0 0 3px rgba(189, 36, 39, 0.3);
}

.btn-gradient:disabled,
.btn-gradient.disabled {
  opacity: 0.45;
}

/* Deeper, more saturated red than the primary button to read as higher-stakes. */
.btn-danger-gradient {
  background: #b91c1c !important;
  color: #ffffff !important;

  box-shadow: 0 2px 8px rgba(185, 28, 28, 0.3);

  transition: background-color 0.15s, box-shadow 0.2s, transform 0.2s;
}

.btn-danger-gradient:hover {
  background: #991717 !important;

  box-shadow: 0 6px 16px rgba(185, 28, 28, 0.4);

  transform: translateY(-1px);
}

.btn-danger-gradient:active {
  background: #7a1212 !important;

  box-shadow: 0 2px 6px rgba(185, 28, 28, 0.35);

  transform: translateY(0);
}

.btn-danger-gradient:focus-visible {
  outline: none;
  box-shadow: 0 0 0 3px rgba(185, 28, 28, 0.3);
}

.btn-danger-gradient:disabled,
.btn-danger-gradient.disabled {
  opacity: 0.45;
}

/* INFO ROWS (icon + label/value) */

.info-row {
  display: flex;
  align-items: center;

  gap: 14px;
  margin: 0 -10px;
  padding: 14px 10px;

  /* Softer than the card's #e8e8e8 border so rows read as a subtle rhythm. */
  border-bottom: 1px solid #f6f6f6;
  border-radius: 12px;

  transition: background-color 0.15s;
}

.info-row:hover {
  background: #fafafa;
}

.info-row:last-of-type,
.info-row-last {
  border-bottom: none;
}

/* Same gradient direction StoreCard uses for its image placeholder. */
.info-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;

  width: 44px;
  height: 44px;

  border-radius: 12px;

  background: linear-gradient(145deg, #fdecec 0%, #fbdbdc 100%);
  color: #bd2427;
}

.info-body {
  flex: 1;
  min-width: 0;
}

.info-label {
  font-size: 12px;

  color: #a3a3a3;
}

.info-value-row {
  display: flex;
  align-items: center;

  gap: 5px;
  margin-top: 5px;
  flex-wrap: wrap;
}

.info-value {
  margin-top: 5px;

  font-size: 14.5px;
  font-weight: 500;
  line-height: 1.4;

  color: #111111;

  overflow-wrap: anywhere;
}

.info-value-row .info-value {
  margin-top: 0;
}

/* PROFILE PHOTO */

/* box-shadow instead of border, so the ring doesn't shrink the box and spill the <img>. */
.photo-avatar {
  box-shadow:
    0 0 0 4px #ffffff,
    0 0 0 5px #e0e0e0,
    0 6px 16px rgba(0, 0, 0, 0.12);
}

.photo-camera-btn {
  position: absolute;
  bottom: 2px;
  left: 50%;

  display: flex;
  align-items: center;
  justify-content: center;

  width: 34px;
  height: 34px;
  min-width: 34px;
  min-height: 34px;
  padding: 0;

  transform: translateX(-50%);

  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.25);
}

.photo-camera-btn :deep(.q-btn__content) {
  display: flex;
  align-items: center;
  justify-content: center;

  width: 100%;
  height: 100%;
}

.photo-hint {
  margin-top: 10px;

  font-size: 11.5px;

  color: #9a9a9a;
}

.profile-container :deep(.q-btn--outline.text-primary) {
  transition: background-color 0.15s, border-color 0.15s;
}

.profile-container :deep(.q-btn--outline.text-primary:hover) {
  border-color: #bd2427;
  background: #fdecec;
}

/* Secondary outline buttons inside dialogs (Cancel, Keep Editing), shared across every modal. */
.profile-dialog-card :deep(.q-btn--outline.text-grey-7) {
  transition: background-color 0.15s, border-color 0.15s, box-shadow 0.15s;
}

.profile-dialog-card :deep(.q-btn--outline.text-grey-7:hover) {
  border-color: #b0b0b0;
  background: #f5f5f5;
}

.profile-dialog-card :deep(.q-btn--outline.text-grey-7:active) {
  background: #ececec;
}

.profile-dialog-card :deep(.q-btn--outline.text-grey-7:focus-visible) {
  outline: none;
  box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.12);
}

/* DANGER ZONE */

.danger-card {
  border-color: #fecdd3;
  background: #fffbfb;
}

.danger-card .section-subtitle {
  margin-bottom: 20px;
}

.danger-row {
  display: flex;
  align-items: center;

  gap: 14px;
  padding: 10px;

  border-radius: 10px;
  border: 1px solid #fecaca;

  background: #ffffff;

  cursor: pointer;

  transition: background-color 0.15s, border-color 0.15s, box-shadow 0.2s, transform 0.15s;
}

.danger-row:hover {
  border-color: #fca5a5;
  background: #fff5f5;

  box-shadow: 0 4px 14px rgba(220, 38, 38, 0.1);
  transform: translateY(-1px);
}

.danger-icon {
  border-radius: 12px;

  background: linear-gradient(145deg, #fee2e2 0%, #fecaca 100%);
  color: #b91c1c;
}

.danger-title {
  font-size: 14px;
  font-weight: 700;

  color: #b91c1c;
}

.danger-desc {
  margin-top: 1px;

  font-size: 12px;

  color: #b45858;
}

/* DIALOG HEADER (title/subtitle + close) — shared by all dialogs. */

.dialog-header {
  display: flex;
  align-items: center;

  gap: 12px;
}

/* Overrides Quasar's flat 2rem line-height on .text-h6/.text-h5, which bakes in phantom space below the title. */
.profile-dialog-card :deep(.text-h6),
.profile-dialog-card :deep(.text-h5) {
  line-height: 1.3;
  font-weight: 700;
}

.dialog-header-text {
  flex: 1;
  min-width: 0;
}

.edit-field-row {
  display: flex;

  gap: 12px;
}

.edit-field-row .edit-field {
  flex: 1;
  margin-top: 0;
}

.edit-field {
  margin-top: 12px;
}

.edit-field:first-child {
  margin-top: 0;
}

/* Phone Number / Email Address sit in a denser cluster, so tighter than the default 12px gap. */
.edit-field-tight {
  margin-top: 8px;
}

.edit-field-label {
  margin-bottom: 6px;

  font-size: 13px;
  font-weight: 500;

  color: #111111;
}

.edit-field-label-row {
  display: flex;
  align-items: center;
  justify-content: space-between;

  margin-bottom: 6px;
}

.edit-field-label-row .edit-field-label {
  margin-bottom: 0;
}

.verified-badge {
  display: inline-flex;
  align-items: center;

  gap: 4px;
  padding: 4px 10px 4px 8px;

  border-radius: 999px;

  background: #dcfce7;

  font-size: 12.5px;
  font-weight: 600;
  line-height: 1;

  color: #16a34a;
}

.pending-badge {
  display: inline-flex;
  align-items: center;

  gap: 4px;
  padding: 4px 10px 4px 8px;

  border-radius: 999px;

  background: #fef3c7;

  font-size: 12.5px;
  font-weight: 600;
  line-height: 1;

  color: #b45309;
}

.edit-field-hint {
  margin-top: 4px;

  font-size: 11.5px;

  color: #9a9a9a;
}

.edit-field-hint-success {
  display: flex;
  align-items: center;

  gap: 3px;

  color: #16a34a;
  font-weight: 600;
}

.edit-field-hint-error {
  color: #dc2626;
}

/* Same show/hide toggle as ConsumerRegister.vue's password fields */
.password-icon {
  font-size: 16px;

  color: #777777;
}

/* DELETE ACCOUNT DIALOG */

.delete-warning {
  margin: 0;

  font-size: 13.5px;
  line-height: 1.6;

  color: #4a4a4a;
}

/* SUCCESS DIALOG */

.success-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;

  width: 64px;
  height: 64px;

  border-radius: 50%;

  background: #dcfce7;
  color: #16a34a;

  animation: success-icon-pop 240ms ease-out;
}

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

/* Same boxed OTP pattern as ConsumerVerify.vue / LoginPage.vue's forgot-password flow */

.otp-row {
  display: flex;
  justify-content: center;

  gap: 10px;
}

.otp-box {
  width: 48px;
  height: 48px;
  padding: 0;

  border: 1px solid #d6d6da;
  border-radius: 8px;

  background: #ffffff;

  font-family: 'Roboto', Arial, sans-serif;
  font-size: 20px;
  font-weight: 600;
  line-height: 1;

  text-align: center;

  color: #222222;

  outline: none;

  transition: border-color 0.15s, box-shadow 0.15s, background-color 0.2s;
}

.otp-box:focus {
  border-color: #bd2427;

  box-shadow: 0 0 0 1px rgba(189, 36, 39, 0.1);
}

.otp-box-error {
  border-color: #ef4444;
}

/* Brief green flash on the digit boxes before handoff to the success screen. */
.otp-box-success {
  border-color: #16a34a;

  background: #f0fdf4;
  color: #16a34a;
}

.otp-error {
  font-size: 12.5px;
}

.otp-meta {
  display: flex;
  flex-direction: column;
  align-items: center;

  gap: 6px;
  margin-top: 16px;
  margin-bottom: 12px;

  font-size: 13px;
}

.otp-resend {
  color: #9a9a9a;
}

.otp-resend-link {
  color: #bd2427;
  font-weight: 600;

  text-decoration: none;
}

.otp-resend-link:hover {
  text-decoration: underline;
}

/* DIALOGS (crop / password / OTP / edit / success) */
/* q-dialog content is teleported to <body>, so :deep() scoping must anchor on .profile-dialog-card itself, not an ancestor like .profile-container. */

.profile-dialog-card {
  border-radius: 12px;

  /* Tightens Quasar's default 300ms "scale" transition down to ~200ms. */
  --q-transition-duration: 200ms;
}

/* 32px baseline padding; .dialog-header/.dialog-body trim top/bottom so adjacent sections don't double up into a 64px gap. */
.profile-dialog-card :deep(.q-card__section) {
  padding: 32px;
}

.profile-dialog-card :deep(.dialog-header) {
  padding-bottom: 20px;
}

.profile-dialog-card :deep(.dialog-body) {
  padding-top: 0;
  padding-bottom: 4px;
}

.profile-dialog-card :deep(.q-card__actions) {
  display: flex;
  justify-content: flex-end;

  gap: 10px;
  padding: 4px 32px 32px;
}

/* Zeroes Quasar's own .q-card__actions--horiz margin-left so flex `gap` above is the only spacing in play. */
.profile-dialog-card :deep(.q-card__actions .q-btn-item + .q-btn-item) {
  margin-left: 0;
}

/* Discard-confirm dialog has no .dialog-body, so trim the header's own bottom padding instead of stacking with .q-card__actions'. */
.discard-confirm-header {
  padding-bottom: 6px;
}

.discard-confirm-actions {
  display: flex;
  justify-content: flex-end;

  gap: 10px;
  padding: 6px 32px 28px;
}

/* Zeroes Quasar's own .q-card__actions--horiz margin-left so flex `gap` is the only spacing. */
.discard-confirm-actions :deep(.q-btn-item + .q-btn-item) {
  margin-left: 0;
}

/* !important needed: Quasar's own dialog-actions rule carries higher specificity. */
.discard-confirm-actions :deep(.q-btn) {
  min-width: 130px !important;
}

/* Buttons — 48px tall everywhere except the round close (×) button, matching the canonical .login-button/.btn-gradient recipe. */
.profile-dialog-card :deep(.q-btn:not(.q-btn--round)) {
  height: 48px;
  min-height: 48px;

  border-radius: 6px;
  font-size: 13px;
}

/* Close (×) icon reads oversized next to the dialog's 18px icon language. */
.profile-dialog-card :deep(.dialog-header .q-btn--round .q-icon) {
  font-size: 19px;
}

/* Inputs — 48px tall everywhere except the OTP digit boxes. */
.profile-dialog-card :deep(.q-field--outlined:not(.otp-box) .q-field__control) {
  height: 48px;

  border-radius: 8px;
}

/* Excludes the error state so a failed field still shows Quasar's own negative red. */
.profile-dialog-card :deep(.q-field--focused:not(.q-field--error)) {
  color: #bd2427;
}

/* Below md breakpoint both cards stack in DOM order — pull Profile Photo ahead of Personal Information for phones/tablets. */
@media (max-width: 1023px) {
  .col-photo {
    order: -1;
  }
}

@media (max-width: 600px) {
  .profile-container {
    padding: 16px;
  }

  .page-title {
    font-size: 19px;
  }

  .page-subtitle {
    font-size: 12.5px;
  }

  .profile-card :deep(.q-card__section) {
    padding: 16px;
  }

  .section-title {
    font-size: 15px;
  }

  .section-subtitle {
    font-size: 12px;
  }

  /* Edit / Change Password pills */
  .card-action-btn {
    height: 32px;
    min-height: 32px;
    padding: 0 12px;

    font-size: 12px;
  }

  .info-row {
    gap: 12px;
    padding: 12px 8px;
  }

  .info-icon {
    width: 36px;
    height: 36px;
  }

  .info-value {
    font-size: 14px;
  }

  .photo-camera-btn {
    width: 30px;
    height: 30px;
    min-width: 30px;
    min-height: 30px;
  }

  .danger-row {
    padding: 8px;
  }

  .danger-title {
    font-size: 13.5px;
  }

  .danger-desc {
    font-size: 11.5px;
  }

  /* All buttons — pill actions, primary CTAs, and dialog buttons alike */
  .profile-container :deep(.q-btn),
  .profile-dialog-card :deep(.q-btn) {
    font-size: 12.5px;
  }

  .edit-field-label {
    font-size: 12.5px;
  }

  .edit-field-hint {
    font-size: 11px;
  }

  .delete-warning {
    font-size: 13px;
  }

  /* .success-icon deliberately has no override — stays 64px, same as desktop. */

  /* Quasar's "minimized" dialog positioning adds its own 24px padding; :global()+:has() reaches .q-dialog__inner since it's an ancestor, not a descendant, of .profile-dialog-card. */
  :global(.q-dialog__inner--minimized:has(.profile-dialog-card)) {
    padding: 16px;
  }

  /* Overrides each dialog's inline max-width:90vw, which read as extra uneven margin on top of the 16px padding at phone widths. */
  .profile-dialog-card {
    max-width: 100% !important;
  }

  /* Dialog shell — same rhythm as desktop, scaled down to 24px for small screens. */
  .profile-dialog-card :deep(.q-card__section) {
    padding: 24px;
  }

  /* Header-to-first-field and last-field-to-actions gaps matched (~20px each) for a symmetrical form, instead of desktop's lopsided 20px/8px split. */
  .profile-dialog-card :deep(.dialog-header) {
    padding-bottom: 20px;

    /* Align close (×) button to the top instead of centering against the title+subtitle block. */
    align-items: flex-start;
  }

  /* Reasserts padding-top:0 — the blanket .q-card__section rule above would otherwise double the top gap. */
  .profile-dialog-card :deep(.dialog-body) {
    padding-top: 0;
    padding-bottom: 8px;
  }

  /* Cancel + primary action buttons split the footer evenly instead of sizing to their own label. */
  .profile-dialog-card :deep(.q-card__actions) {
    display: flex;

    gap: 10px;
    padding: 12px 24px 24px;
  }

  /* Zeroes the buttons' leftover q-mr-sm margin so flex `gap` above is the only spacing in play. */
  .profile-dialog-card :deep(.q-card__actions .q-btn) {
    flex: 1 1 0;
    margin-left: 0 !important;
    margin-right: 0 !important;
  }

  /* Slightly shorter than desktop's 48px — still comfortably above the 44px minimum touch-target guidance. */
  .profile-dialog-card :deep(.q-btn:not(.q-btn--round)) {
    height: 44px;
    min-height: 44px;
  }

  /* Same 48px as desktop; inputs shouldn't shrink for a small screen. */
  .profile-dialog-card :deep(.q-field--outlined:not(.otp-box) .q-field__control) {
    height: 48px;
  }

  /* Flattens desktop's two different field rhythms (12px vs 8px) to one consistent 18px gap. */
  .edit-field,
  .edit-field-tight {
    margin-top: 18px;
  }

  .edit-field:first-child {
    margin-top: 0;
  }
}
</style>
