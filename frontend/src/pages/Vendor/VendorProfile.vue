<template>
  <q-page class="profile-page">
    <div class="profile-container">
      <div class="page-header-block">
        <h1 class="page-title">Profile Settings</h1>
        <p class="page-subtitle">Manage your personal information, store details and security.</p>
      </div>

      <div class="row q-col-gutter-md items-stretch">

        <!-- ================= PERSONAL INFO ================= -->
        <div class="col-12 col-md-8">
          <q-card flat bordered class="profile-card profile-card-fill">
            <q-card-section>
              <div class="card-header">
                <div>
                  <div class="section-title">Personal Information</div>
                  <div class="section-subtitle">View and update the store owner's details.</div>
                </div>
                <q-btn
                  outline
                  no-caps
                  color="primary"
                  icon="o_edit"
                  label="Edit"
                  class="card-action-btn"
                  :disable="!profileLoaded"
                  @click="startEditPersonal"
                />
              </div>

              <div class="info-row">
                <div class="info-icon"><q-icon name="o_person" size="18px" /></div>
                <div class="info-body">
                  <div class="info-label">Name</div>
                  <div class="info-value">{{ user.full_name || 'Not set' }}</div>
                </div>
              </div>

              <div class="info-row">
                <div class="info-icon"><q-icon name="o_phone" size="18px" /></div>
                <div class="info-body">
                  <div class="info-label">Phone Number</div>
                  <div class="info-value">{{ user.phone_number || 'Not set' }}</div>
                </div>
              </div>

              <div class="info-row">
                <div class="info-icon"><q-icon name="o_mail" size="18px" /></div>
                <div class="info-body">
                  <div class="info-label">Email Address</div>
                  <div class="info-value">{{ user.email || 'Not set' }}</div>
                </div>
              </div>
            </q-card-section>
          </q-card>
        </div>

        <!-- ================= STORE PHOTO ================= -->
        <div class="col-12 col-md-4 col-photo">
          <q-card flat bordered class="profile-card profile-card-fill">
            <q-card-section class="photo-card-section">
              <div>
                <div class="section-title">Store Photo</div>
                <div class="section-subtitle">Customers see this on your store page.</div>
              </div>

              <div class="photo-card-body">
                <div class="q-mb-md">
                  <!-- A 16:9 frame in place of the consumer's round avatar, since it shows the storefront. -->
                  <div class="store-photo-wrap">
                    <img v-if="shownStorePhoto" :src="shownStorePhoto" alt="Storefront" class="store-photo" />
                    <div v-else class="store-photo store-photo--empty">
                      <q-icon name="o_storefront" size="40px" />
                    </div>

                    <q-btn round unelevated color="primary" class="photo-camera-btn" aria-label="Change store photo" @click="triggerUpload">
                      <q-icon name="o_photo_camera" size="16px" />
                    </q-btn>
                  </div>

                  <input type="file" id="storePhotoUpload" accept="image/png, image/jpeg, image/gif" style="display: none;" @change="onFileSelected" />
                </div>

                <div class="text-center">
                  <template v-if="!photoFile">
                    <q-btn outline no-caps color="primary" label="Change Photo" class="full-width" @click="triggerUpload" />
                  </template>
                  <template v-else>
                    <q-btn unelevated no-caps color="primary" label="Save Photo" :loading="savingPhoto" class="full-width q-mb-sm btn-gradient" @click="savePhoto" />
                    <q-btn outline no-caps color="primary" label="Cancel" class="full-width" :disable="savingPhoto" @click="cancelPhoto" />
                  </template>
                </div>
                <div class="text-center photo-hint">JPG, PNG or GIF. Max size of 2MB.</div>
              </div>
            </q-card-section>
          </q-card>
        </div>

        <div class="col-12">

          <!-- ================= STORE DETAILS ================= -->
          <q-card flat bordered class="profile-card q-mb-md">
            <q-card-section>
              <div class="card-header">
                <div>
                  <div class="section-title">Store Details</div>
                  <div class="section-subtitle">What customers see about your store.</div>
                </div>
              </div>

              <div class="info-row">
                <div class="info-icon"><q-icon name="o_storefront" size="18px" /></div>
                <div class="info-body">
                  <div class="info-label">Store Name</div>
                  <div class="info-value">{{ store.store_name || 'Not set' }}</div>
                </div>
                <q-btn outline no-caps color="primary" icon="o_edit" label="Edit" class="card-action-btn" aria-label="Edit store name" :disable="!profileLoaded" @click="startEditStoreName" />
              </div>

              <div class="info-row">
                <div class="info-icon"><q-icon name="o_place" size="18px" /></div>
                <div class="info-body">
                  <div class="info-label">Address</div>
                  <div class="info-value">{{ store.address || 'Not set' }}</div>
                  <div class="info-meta" :class="{ 'info-meta--ok': hasPin }">
                    <q-icon :name="hasPin ? 'o_check_circle' : 'o_location_off'" size="14px" />
                    {{ hasPin ? 'Pinned on the map for nearby customers.' : 'No map pin yet, so nearby customers can\'t find you.' }}
                  </div>
                </div>
                <q-btn outline no-caps color="primary" icon="o_edit" label="Edit" class="card-action-btn" aria-label="Edit address" :disable="!profileLoaded" @click="startEditAddress" />
              </div>

              <div class="info-row info-row-last">
                <div class="info-icon"><q-icon name="o_schedule" size="18px" /></div>
                <div class="info-body">
                  <div class="info-label">Store Hours</div>
                  <div class="hours-lines">
                    <div v-for="line in hoursLines" :key="line" class="hours-line">{{ line }}</div>
                  </div>
                </div>
                <q-btn outline no-caps color="primary" icon="o_edit" label="Edit" class="card-action-btn" aria-label="Edit store hours" :disable="!profileLoaded" @click="startEditHours" />
              </div>
            </q-card-section>
          </q-card>

          <!-- ================= SECURITY ================= -->
          <q-card flat bordered class="profile-card q-mb-md">
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

              <div class="danger-row" role="button" tabindex="0" @click="confirmDeleteAccount" @keydown.enter="confirmDeleteAccount">
                <div class="info-icon danger-icon"><q-icon name="o_delete" size="18px" /></div>
                <div class="info-body">
                  <div class="danger-title">Delete My Store Account</div>
                  <div class="danger-desc">Permanently delete your store, products and sales records.</div>
                </div>
                <q-icon name="o_chevron_right" size="20px" color="red-4" />
              </div>
            </q-card-section>
          </q-card>

        </div>
      </div>
    </div>

    <!-- EDIT PERSONAL INFORMATION DIALOG -->
    <q-dialog v-model="showEditPersonalModal" persistent transition-show="scale" transition-hide="scale">
      <q-card class="profile-dialog-card" style="width: 560px; max-width: 90vw;">
        <q-card-section class="dialog-header">
          <div class="dialog-icon"><q-icon name="o_person" size="22px" /></div>
          <div class="dialog-header-text">
            <div class="text-h6">Edit Personal Information</div>
            <div class="section-subtitle">Update your personal details below.</div>
          </div>
          <q-btn flat round dense icon="o_close" class="dialog-close-btn" aria-label="Close edit profile" :disable="savingPersonal" @click="attemptCloseEditPersonal" />
        </q-card-section>

        <q-form ref="editPersonalFormRef" greedy>
          <q-card-section class="dialog-body">
            <div class="edit-field-row">
              <div class="edit-field">
                <div class="edit-field-label">First Name</div>
                <q-input v-model="editForm.firstName" outlined dense no-error-icon hide-bottom-space :rules="[val => !!val?.trim() || 'Required']" />
              </div>

              <div class="edit-field">
                <div class="edit-field-label">Last Name</div>
                <q-input v-model="editForm.lastName" outlined dense no-error-icon hide-bottom-space :rules="[val => !!val?.trim() || 'Required']" />
              </div>
            </div>

            <div class="edit-field edit-field-tight">
              <div class="edit-field-label">Phone Number</div>
              <q-input
                v-model="editForm.phone_number"
                outlined
                dense
                no-error-icon
                hide-bottom-space
                maxlength="11"
                inputmode="numeric"
                :error="phoneMessage?.type === 'error'"
                @keypress="blockNonDigitKey"
                @paste="onPhoneNumberPaste"
              />
              <div v-if="phoneMessage" class="edit-field-hint" :class="`edit-field-hint-${phoneMessage.type}`">{{ phoneMessage.text }}</div>
            </div>

            <div class="edit-field edit-field-tight">
              <div class="edit-field-label">Email Address</div>
              <q-input
                v-model="editForm.email"
                outlined
                dense
                type="email"
                no-error-icon
                hide-bottom-space
                :error="emailMessage?.type === 'error'"
              />
              <div v-if="emailMessage" class="edit-field-hint" :class="`edit-field-hint-${emailMessage.type}`">{{ emailMessage.text }}</div>
            </div>
          </q-card-section>
        </q-form>

        <q-card-actions align="right">
          <q-btn outline no-caps label="Cancel" color="primary" :disable="savingPersonal" @click="attemptCloseEditPersonal" />
          <q-btn unelevated no-caps color="primary" label="Save Changes" :loading="savingPersonal" :disable="!canSavePersonal" class="btn-gradient" @click="savePersonal" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- EDIT STORE NAME DIALOG -->
    <q-dialog v-model="showStoreNameModal" persistent transition-show="scale" transition-hide="scale">
      <q-card class="profile-dialog-card" style="width: 460px; max-width: 90vw;">
        <q-card-section class="dialog-header">
          <div class="dialog-icon"><q-icon name="o_storefront" size="22px" /></div>
          <div class="dialog-header-text">
            <div class="text-h6">Edit Store Name</div>
            <div class="section-subtitle">This is the name customers see.</div>
          </div>
          <q-btn flat round dense icon="o_close" class="dialog-close-btn" aria-label="Close edit store name" :disable="savingStoreName" @click="attemptCloseStoreName" />
        </q-card-section>

        <q-form ref="storeNameFormRef" greedy>
          <q-card-section class="dialog-body">
            <div class="edit-field">
              <div class="edit-field-label">Store Name</div>
              <q-input v-model="storeNameInput" outlined dense no-error-icon hide-bottom-space maxlength="150" :rules="[val => !!val?.trim() || 'Store name is required.']" />
            </div>
          </q-card-section>
        </q-form>

        <q-card-actions align="right">
          <q-btn outline no-caps label="Cancel" color="primary" :disable="savingStoreName" @click="attemptCloseStoreName" />
          <q-btn unelevated no-caps color="primary" label="Save Changes" :loading="savingStoreName" :disable="!canSaveStoreName" class="btn-gradient" @click="saveStoreName" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- EDIT ADDRESS DIALOG -->
    <!-- The map mounts once the dialog has finished opening, since Leaflet can't measure a panel that is still scaling in. -->
    <q-dialog v-model="showAddressModal" persistent transition-show="scale" transition-hide="scale" @show="addressMapReady = true" @hide="addressMapReady = false">
      <q-card class="profile-dialog-card" style="width: 560px; max-width: 90vw;">
        <q-card-section class="dialog-header">
          <div class="dialog-icon"><q-icon name="o_place" size="22px" /></div>
          <div class="dialog-header-text">
            <div class="text-h6">Edit Address</div>
            <div class="section-subtitle">Tap the map to move your pin, then check the address below.</div>
          </div>
          <q-btn flat round dense icon="o_close" class="dialog-close-btn" aria-label="Close edit address" :disable="savingAddress" @click="attemptCloseAddress" />
        </q-card-section>

        <q-form ref="addressFormRef" greedy>
          <q-card-section class="dialog-body">
            <div class="address-map-frame">
              <VendorLocationMap v-if="addressMapReady" :initial="addressInitial" @location-selected="onLocationSelected" />
            </div>
            <div class="edit-field-hint" :class="{ 'edit-field-hint-success': editAddress.latitude !== null }">
              <q-icon v-if="editAddress.latitude !== null" name="o_check_circle" size="12px" />
              {{ editAddress.latitude !== null ? 'Pin placed.' : 'Tap the map to drop a pin on your store.' }}
            </div>

            <div class="edit-field">
              <div class="edit-field-label">Street, building, house no.</div>
              <q-input
                v-model="editAddress.address"
                type="textarea"
                autogrow
                outlined
                dense
                no-error-icon
                hide-bottom-space
                maxlength="255"
                class="address-input"
                :rules="[val => !!val?.trim() || 'Address is required.']"
              />
            </div>
          </q-card-section>
        </q-form>

        <q-card-actions align="right">
          <q-btn outline no-caps label="Cancel" color="primary" :disable="savingAddress" @click="attemptCloseAddress" />
          <q-btn unelevated no-caps color="primary" label="Save Address" :loading="savingAddress" :disable="!canSaveAddress" class="btn-gradient" @click="saveAddress" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- EDIT STORE HOURS DIALOG -->
    <q-dialog v-model="showHoursModal" persistent transition-show="scale" transition-hide="scale">
      <q-card class="profile-dialog-card hours-dialog" style="width: 480px; max-width: 90vw;">
        <q-card-section class="dialog-header">
          <div class="dialog-icon"><q-icon name="o_schedule" size="22px" /></div>
          <div class="dialog-header-text">
            <div class="text-h6">Edit Store Hours</div>
            <div class="section-subtitle">Set when customers can pick up orders.</div>
          </div>
          <q-btn flat round dense icon="o_close" class="dialog-close-btn" aria-label="Close store hours" :disable="savingHours" @click="attemptCloseHours" />
        </q-card-section>

        <q-card-section class="dialog-body">
          <div class="hours-toolbar">
            <span class="hours-toolbar-hint">Switch a day off to mark it closed.</span>
            <q-btn outline no-caps color="primary" icon="o_content_copy" label="Copy Monday to all" class="hours-copy-btn" @click="applyMondayToAll" />
          </div>

          <!-- Each day is one compact row, with its times picked from half-hour dropdowns rather than typed. -->
          <div v-for="day in editHours" :key="day.name" class="hours-row" :class="{ 'hours-row--closed': !day.isOpen }">
            <div class="hours-day">
              <q-toggle v-model="day.isOpen" color="primary" dense :aria-label="`${day.name} open`" />
              <span class="hours-day-name">{{ day.name }}</span>
            </div>

            <div v-if="day.isOpen" class="hours-times">
              <q-select
                v-model="day.openTime"
                :options="TIME_OPTIONS"
                emit-value
                map-options
                outlined
                dense
                options-dense
                hide-bottom-space
                behavior="menu"
                dropdown-icon="keyboard_arrow_down"
                popup-content-class="hours-menu"
                popup-content-style="max-height: 260px"
                class="hours-select"
                no-error-icon
                :error="invalidDay(day)"
                :aria-label="`${day.name} opening time`"
              />
              <span class="hours-sep">to</span>
              <q-select
                v-model="day.closeTime"
                :options="TIME_OPTIONS"
                emit-value
                map-options
                outlined
                dense
                options-dense
                hide-bottom-space
                behavior="menu"
                dropdown-icon="keyboard_arrow_down"
                popup-content-class="hours-menu"
                popup-content-style="max-height: 260px"
                class="hours-select"
                no-error-icon
                :error="invalidDay(day)"
                :aria-label="`${day.name} closing time`"
              />
            </div>
            <div v-else class="hours-closed">Closed</div>

            <div v-if="invalidDay(day)" class="edit-field-hint edit-field-hint-error hours-error">Closing time must be later than opening time.</div>
          </div>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn outline no-caps label="Cancel" color="primary" :disable="savingHours" @click="attemptCloseHours" />
          <q-btn unelevated no-caps color="primary" label="Save Hours" :loading="savingHours" :disable="!canSaveHours" class="btn-gradient" @click="saveHours" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- DISCARD CHANGES CONFIRMATION (shared by every edit dialog) -->
    <q-dialog v-model="showDiscardConfirm" :persistent="discardingChanges" transition-show="scale" transition-hide="scale">
      <q-card class="profile-dialog-card discard-confirm-card" style="width: 400px; max-width: 90vw;">
        <q-card-section class="dialog-header discard-confirm-header">
          <div class="dialog-header-text">
            <div class="text-h6">Discard Changes?</div>
            <div class="section-subtitle">You have unsaved changes. If you leave now, your changes will not be saved.</div>
          </div>
        </q-card-section>
        <q-card-actions class="discard-confirm-actions">
          <q-btn outline no-caps label="Keep Editing" color="primary" autofocus :disable="discardingChanges" v-close-popup />
          <q-btn unelevated no-caps label="Discard" class="btn-danger-gradient" :loading="discardingChanges" :disable="discardingChanges" @click="confirmDiscardChanges" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- SUCCESS DIALOG (shared by every save) -->
    <q-dialog v-model="showSuccessModal" transition-show="scale" transition-hide="scale">
      <q-card class="profile-dialog-card success-card" style="width: 440px; max-width: 90vw;">
        <q-card-section class="text-center">
          <div class="success-icon">
            <q-icon name="o_check" size="32px" />
          </div>
          <div class="text-h6">{{ successModal.title }}</div>
          <p class="section-subtitle">{{ successModal.message }}</p>
          <q-btn unelevated no-caps color="primary" label="Done" class="full-width btn-gradient" autofocus v-close-popup />
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- CROP DIALOG -->
    <q-dialog v-model="showCropModal" persistent transition-show="scale" transition-hide="scale">
      <q-card class="profile-dialog-card" style="width: 560px; max-width: 90vw;">
        <q-card-section class="dialog-header">
          <div class="dialog-icon"><q-icon name="o_crop" size="22px" /></div>
          <div class="dialog-header-text">
            <div class="text-h6">Crop Store Photo</div>
            <div class="section-subtitle">Drag the photo to move it, and zoom until the frame shows your storefront.</div>
          </div>
          <q-btn flat round dense icon="o_close" class="dialog-close-btn" aria-label="Close photo cropper" @click="showCropModal = false" />
        </q-card-section>
        <q-card-section class="dialog-body">
          <PhotoCropper ref="cropperRef" :src="originalPhotoUrl || ''" :aspect="16 / 9" :output-width="1280" @ready="cropReady = true" />
        </q-card-section>
        <q-card-actions align="right">
          <q-btn outline no-caps label="Cancel" color="primary" @click="showCropModal = false" />
          <q-btn unelevated no-caps color="primary" label="Apply Crop" :disable="!cropReady" class="btn-gradient" @click="applyCrop" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- CHANGE PASSWORD DIALOG -->
    <q-dialog v-model="showPasswordModal" persistent transition-show="scale" transition-hide="scale">
      <q-card class="profile-dialog-card" style="width: 460px; max-width: 90vw;">
        <q-card-section class="dialog-header">
          <div class="dialog-icon"><q-icon name="o_lock" size="22px" /></div>
          <div class="dialog-header-text">
            <div class="text-h6">Change Password</div>
            <div class="section-subtitle">Keep your account secure with a strong password.</div>
          </div>
          <q-btn flat round dense icon="o_close" class="dialog-close-btn" aria-label="Close change password" :disable="savingPassword" @click="attemptClosePasswordModal" />
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
                autocomplete="current-password"
                :type="showCurrentPassword ? 'text' : 'password'"
                :rules="[val => !!val || 'Current password is required']"
              >
                <template #append>
                  <q-icon :name="showCurrentPassword ? 'o_visibility' : 'o_visibility_off'" class="password-icon cursor-pointer" @click="showCurrentPassword = !showCurrentPassword" />
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
                autocomplete="new-password"
                :type="showNewPassword ? 'text' : 'password'"
                :error="newPasswordMessage?.type === 'error'"
              >
                <template #append>
                  <q-icon :name="showNewPassword ? 'o_visibility' : 'o_visibility_off'" class="password-icon cursor-pointer" @click="showNewPassword = !showNewPassword" />
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
                autocomplete="new-password"
                :type="showConfirmPassword ? 'text' : 'password'"
                :error="confirmPasswordMessage?.type === 'error'"
              >
                <template #append>
                  <q-icon :name="showConfirmPassword ? 'o_visibility' : 'o_visibility_off'" class="password-icon cursor-pointer" @click="showConfirmPassword = !showConfirmPassword" />
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
          <q-btn outline no-caps label="Cancel" color="primary" :disable="savingPassword" @click="attemptClosePasswordModal" />
          <q-btn unelevated no-caps color="primary" label="Update Password" :loading="savingPassword" :disable="!canSavePassword" class="btn-gradient" @click="savePassword" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- OTP VERIFICATION DIALOG -->
    <q-dialog v-model="showOtpModal" persistent transition-show="scale" transition-hide="scale">
      <q-card class="profile-dialog-card" style="width: 440px; max-width: 90vw;">
        <q-card-section class="dialog-header">
          <div class="dialog-icon"><q-icon name="o_sms" size="22px" /></div>
          <div class="dialog-header-text">
            <div class="text-h6">Verify New Phone</div>
            <div class="section-subtitle">Enter the 6-digit verification code sent to {{ maskedPhone }}. Sent via SMS.</div>
          </div>
          <q-btn flat round dense icon="o_close" class="dialog-close-btn" aria-label="Close verification" :disable="verifyingOtp" @click="cancelOtp" />
        </q-card-section>
        <q-card-section class="dialog-body text-center">
          <div class="otp-row">
            <input
              v-for="(digit, i) in otpInput"
              :key="i"
              :ref="el => { otpRefs[i] = el }"
              v-model="otpInput[i]"
              type="text"
              inputmode="numeric"
              :autocomplete="i === 0 ? 'one-time-code' : 'off'"
              :aria-label="`Digit ${i + 1} of 6`"
              class="otp-box"
              :class="{ 'otp-box-error': otpError, 'otp-box-success': otpVerifiedFlash }"
              :disabled="otpVerifiedFlash"
              @focus="$event.target.select()"
              @input="onOtpInput(i)"
              @keydown.backspace="onOtpBackspace(i)"
              @paste="onOtpPaste"
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
          <q-btn outline no-caps label="Cancel" color="primary" :disable="verifyingOtp" @click="cancelOtp" />
          <q-btn unelevated no-caps color="primary" label="Verify & Save" :loading="verifyingOtp" :disable="!canVerifyOtp || otpVerifiedFlash" class="btn-gradient" @click="verifyOtp" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- DELETE ACCOUNT DIALOG -->
    <q-dialog v-model="showDeleteModal" persistent transition-show="scale" transition-hide="scale">
      <q-card class="profile-dialog-card" style="width: 480px; max-width: 90vw;">
        <q-card-section class="dialog-header">
          <div class="dialog-icon dialog-icon--danger"><q-icon name="o_delete" size="22px" /></div>
          <div class="dialog-header-text">
            <div class="text-h6">Delete Store Account</div>
            <div class="section-subtitle">This action cannot be undone.</div>
          </div>
          <q-btn flat round dense icon="o_close" class="dialog-close-btn" aria-label="Close delete account" :disable="deletingAccount" @click="cancelDeleteModal" />
        </q-card-section>

        <q-card-section class="dialog-body">
          <p class="delete-warning">
            Are you absolutely sure you want to delete your store account? Your store, products, orders and sales records will be permanently removed. This cannot be undone.
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
          <q-btn outline no-caps label="Cancel" color="primary" :disable="deletingAccount" @click="cancelDeleteModal" />
          <q-btn unelevated no-caps label="Delete Account" :loading="deletingAccount" :disable="!deleteConfirmMatches" class="btn-danger-gradient" @click="deleteAccount" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- ACCOUNT DELETED CONFIRMATION -->
    <!-- No close button, since the account and its session are already gone and the only way out is Go to Login. -->
    <q-dialog v-model="showAccountDeletedModal" persistent transition-show="scale" transition-hide="scale">
      <q-card class="profile-dialog-card success-card" style="width: 440px; max-width: 90vw;">
        <q-card-section class="text-center">
          <div class="success-icon">
            <q-icon name="o_check" size="32px" />
          </div>
          <div class="text-h6">Account Deleted!</div>
          <p class="section-subtitle">Your store account has been permanently deleted. Thank you for being part of Tindahan.</p>
          <q-btn unelevated no-caps color="primary" label="Go to Login" class="full-width btn-gradient" autofocus @click="router.push('/login')" />
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
import { clearAuthStorage } from '@/utils/authStorage'
import PhotoCropper from '@/components/shared/PhotoCropper.vue'
import VendorLocationMap from '@/components/leaflet/VendorLocationMap.vue'

const $q = useQuasar()
const router = useRouter()

const DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']

const user = ref({})
const store = ref({})
const savedHours = ref(DAYS.map(name => ({ name, isOpen: false, openTime: '08:00', closeTime: '17:00' })))
// The Edit buttons wait for the saved profile, so a quick tap never opens a dialog filled with placeholders that a save would write back.
const profileLoaded = ref(false)

// Reads the first message from a Laravel error response, falling back to the given text.
const errorMessage = (err, fallback) => {
  const data = err.response?.data
  const first = data?.errors ? Object.values(data.errors)[0]?.[0] : null
  return first || data?.message || fallback
}

// Shared success dialog, opened after every save.
const showSuccessModal = ref(false)
const successModal = reactive({ title: '', message: '' })
const openSuccessModal = (title, message) => {
  successModal.title = title
  successModal.message = message
  showSuccessModal.value = true
}

// Shared "unsaved changes" guard for every edit dialog.
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

// --- Loading ---

// Store hours are saved per day, while older stores only kept a list of day names with one shared time.
const parseHours = (s) => {
  let raw = s.operating_days
  if (typeof raw === 'string') {
    try { raw = JSON.parse(raw) } catch { raw = null }
  }
  const defaultOpen = s.opening_time ? s.opening_time.substring(0, 5) : '08:00'
  const defaultClose = s.closing_time ? s.closing_time.substring(0, 5) : '17:00'

  return DAYS.map(name => {
    if (Array.isArray(raw)) {
      return { name, isOpen: raw.some(d => name.toLowerCase().startsWith(String(d).toLowerCase())), openTime: defaultOpen, closeTime: defaultClose }
    }
    const day = raw && typeof raw === 'object' ? raw[name] : null
    return {
      name,
      isOpen: !!day?.is_open,
      openTime: day?.opening_time?.substring(0, 5) || defaultOpen,
      closeTime: day?.closing_time?.substring(0, 5) || defaultClose
    }
  })
}

onMounted(async () => {
  try {
    const { data } = await api.get('/vendor/profile')
    user.value = data || {}
    store.value = data?.store || {}
    savedHours.value = parseHours(store.value)
    profileLoaded.value = true
  } catch {
    $q.notify({ type: 'negative', message: 'Failed to load your profile.' })
  }
})

// --- Store details shown on the page ---

const hasPin = computed(() => store.value.latitude != null && store.value.longitude != null && store.value.latitude !== '' && store.value.longitude !== '')

const SHORT_DAY = { Monday: 'Mon', Tuesday: 'Tue', Wednesday: 'Wed', Thursday: 'Thu', Friday: 'Fri', Saturday: 'Sat', Sunday: 'Sun' }

const formatTime = (value) => {
  if (!value) return ''
  const [h, m] = value.split(':').map(Number)
  return `${h % 12 || 12}:${String(m).padStart(2, '0')} ${h >= 12 ? 'PM' : 'AM'}`
}

// Every half hour of the day as a dropdown choice, stored as HH:MM and shown as, for example, 7:30 AM.
const TIME_OPTIONS = Array.from({ length: 48 }, (_, i) => {
  const value = `${String(Math.floor(i / 2)).padStart(2, '0')}:${i % 2 ? '30' : '00'}`
  return { value, label: formatTime(value) }
})

// Neighbouring days with the same hours share one line, such as "Mon–Sat · 7:00 AM – 9:00 PM".
const hoursLines = computed(() => {
  const groups = []
  for (const day of savedHours.value) {
    const label = day.isOpen ? `${formatTime(day.openTime)} – ${formatTime(day.closeTime)}` : 'Closed'
    const last = groups[groups.length - 1]
    if (last && last.label === label) last.days.push(day.name)
    else groups.push({ label, days: [day.name] })
  }
  if (groups.length === 1 && groups[0].label === 'Closed') return ['Closed every day']
  return groups.map(g => {
    const first = SHORT_DAY[g.days[0]]
    const span = g.days.length > 1 ? `${first}–${SHORT_DAY[g.days[g.days.length - 1]]}` : first
    return `${span} · ${g.label}`
  })
})

// --- Personal information ---

const showEditPersonalModal = ref(false)
const savingPersonal = ref(false)
const editPersonalFormRef = ref(null)
const editForm = reactive({ firstName: '', lastName: '', phone_number: '', email: '' })

const fullNameInput = computed(() => `${editForm.firstName} ${editForm.lastName}`.trim())
const nameChanged = computed(() => fullNameInput.value !== (user.value.full_name || ''))
const phoneChanged = computed(() => editForm.phone_number !== user.value.phone_number)
const emailChanged = computed(() => editForm.email !== user.value.email)
const isPhoneValid = computed(() => /^09\d{9}$/.test(editForm.phone_number || ''))
const isEmailValid = computed(() => /.+@.+\..+/.test(editForm.email || ''))

// One message per field: error, then a note about what a change will do, then nothing.
const phoneMessage = computed(() => {
  if (!isPhoneValid.value) return { type: 'error', text: 'Phone number must start with 09 and contain 11 digits.' }
  if (phoneChanged.value) return { type: 'helper', text: "We'll send an OTP to verify your new phone number." }
  return null
})

const emailMessage = computed(() => {
  if (!isEmailValid.value) return { type: 'error', text: 'Enter a valid email address.' }
  if (emailChanged.value) return { type: 'helper', text: 'You will sign in with this email from now on.' }
  return null
})

const hasPersonalChanges = computed(() => nameChanged.value || phoneChanged.value || emailChanged.value)

const canSavePersonal = computed(() =>
  hasPersonalChanges.value &&
  !!editForm.firstName.trim() &&
  !!editForm.lastName.trim() &&
  isPhoneValid.value &&
  isEmailValid.value
)

// Blocks at the keystroke rather than through a v-model transform, which lags under fast typing.
const blockNonDigitKey = (event) => {
  if (event.key.length === 1 && !/\d/.test(event.key)) event.preventDefault()
}

const onPhoneNumberPaste = (event) => {
  const pasted = event.clipboardData?.getData('text') || ''
  event.preventDefault()
  editForm.phone_number = `${editForm.phone_number}${pasted.replace(/\D/g, '')}`.slice(0, 11)
}

const startEditPersonal = () => {
  const parts = (user.value.full_name || '').trim().split(' ')
  editForm.firstName = parts[0] || ''
  editForm.lastName = parts.slice(1).join(' ')
  editForm.phone_number = user.value.phone_number || ''
  editForm.email = user.value.email || ''
  editPersonalFormRef.value?.resetValidation()
  showEditPersonalModal.value = true
}

const attemptCloseEditPersonal = () => {
  requestClose(hasPersonalChanges.value, () => { showEditPersonalModal.value = false })
}

// Each save reports whether it went through, so a failure keeps the dialog open instead of claiming success.
const saveName = async (fullName) => {
  try {
    await api.put('/vendor/profile', { full_name: fullName })
    user.value.full_name = fullName
    const lsUser = JSON.parse(localStorage.getItem('auth_user') || '{}')
    lsUser.full_name = fullName
    localStorage.setItem('auth_user', JSON.stringify(lsUser))
    return true
  } catch (err) {
    $q.notify({ type: 'negative', message: errorMessage(err, 'Failed to update your name.') })
    return false
  }
}

const saveEmail = async (email) => {
  try {
    await api.post('/vendor/profile/email', { email })
    user.value.email = email
    return true
  } catch (err) {
    $q.notify({ type: 'negative', message: errorMessage(err, 'Failed to update your email.') })
    return false
  }
}

const savePersonal = async () => {
  if (!canSavePersonal.value) return
  if (!(await editPersonalFormRef.value.validate())) return

  const nameIsChanged = nameChanged.value
  const phoneIsChanged = phoneChanged.value
  const emailIsChanged = emailChanged.value

  savingPersonal.value = true
  try {
    if (nameIsChanged && !(await saveName(fullNameInput.value))) return
    if (emailIsChanged && !(await saveEmail(editForm.email))) return

    // A new phone is only saved once its code is verified, so the dialog hands over to the code step.
    if (phoneIsChanged) {
      if (!(await requestPhoneOtp(editForm.phone_number))) return
      showEditPersonalModal.value = false
      return
    }

    showEditPersonalModal.value = false
    openSuccessModal('Information Updated!', 'Your personal information has been updated successfully.')
  } finally {
    savingPersonal.value = false
  }
}

// --- Phone verification ---

const showOtpModal = ref(false)
const pendingPhone = ref('')
const otpInput = ref(['', '', '', '', '', ''])
const otpRefs = ref([])
const otpError = ref('')
const otpVerifiedFlash = ref(false)
const verifyingOtp = ref(false)
const canVerifyOtp = computed(() => otpInput.value.every(digit => digit !== ''))

// e.g. "09981234567" -> "0998•••4567"
const maskedPhone = computed(() => {
  const digits = pendingPhone.value || ''
  return digits.length < 7 ? digits : `${digits.slice(0, 4)}•••${digits.slice(-4)}`
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
      if (!otpVerifiedFlash.value) otpError.value = 'Code expired. Please resend a new code.'
    }
  }, 1000)
}

const stopOtpTimers = () => {
  clearInterval(otpTimerHandle)
  otpTimerHandle = null
}

onUnmounted(stopOtpTimers)

const requestPhoneOtp = async (phone) => {
  try {
    await api.post('/vendor/profile/phone-request-otp', { phone_number: phone })
    pendingPhone.value = phone
    otpInput.value = ['', '', '', '', '', '']
    otpError.value = ''
    otpVerifiedFlash.value = false
    showOtpModal.value = true
    startOtpTimers()
    return true
  } catch (err) {
    $q.notify({ type: 'negative', message: errorMessage(err, 'Failed to send a verification code.') })
    return false
  }
}

const resendOtpCode = async () => {
  if (!canResendOtp.value || otpVerifiedFlash.value) return
  try {
    await api.post('/vendor/profile/phone-request-otp', { phone_number: pendingPhone.value })
    otpInput.value = ['', '', '', '', '', '']
    otpError.value = ''
    startOtpTimers()
    otpRefs.value[0]?.focus()
  } catch (err) {
    $q.notify({ type: 'negative', message: errorMessage(err, 'Failed to resend the code.') })
  }
}

const onOtpInput = (index) => {
  const digits = otpInput.value[index].replace(/\D/g, '')

  // An autofilled SMS code lands in the first box as one string, so it is spread across all six.
  if (digits.length >= 4) {
    for (let i = 0; i < 6; i++) otpInput.value[i] = digits[i] || ''
    otpRefs.value[Math.min(digits.length, 5)]?.focus()
    otpError.value = ''
    if (canVerifyOtp.value) verifyOtp()
    return
  }

  // Typing into a filled box keeps only the newest digit.
  otpInput.value[index] = digits.slice(-1)
  if (!digits) return
  otpError.value = ''
  if (index < 5) otpRefs.value[index + 1]?.focus()
  if (canVerifyOtp.value) verifyOtp()
}

const onOtpBackspace = (index) => {
  if (!otpInput.value[index] && index > 0) otpRefs.value[index - 1]?.focus()
}

const onOtpPaste = (event) => {
  const pasted = (event.clipboardData?.getData('text') || '').replace(/\D/g, '').slice(0, 6)
  if (!pasted) return
  event.preventDefault()
  pasted.split('').forEach((digit, i) => { otpInput.value[i] = digit })
  otpRefs.value[Math.min(pasted.length, 6) - 1]?.focus()
  if (canVerifyOtp.value) verifyOtp()
}

const cancelOtp = () => {
  stopOtpTimers()
  showOtpModal.value = false
}

const verifyOtp = async () => {
  const code = otpInput.value.join('')
  if (code.length !== 6 || verifyingOtp.value || otpVerifiedFlash.value) return

  verifyingOtp.value = true
  otpError.value = ''
  try {
    await api.post('/vendor/profile/phone-verify-otp', { phone_number: pendingPhone.value, code })
    user.value.phone_number = pendingPhone.value
    stopOtpTimers()
    otpVerifiedFlash.value = true
    // Flashes green briefly, then hands over to the shared success dialog.
    setTimeout(() => {
      showOtpModal.value = false
      openSuccessModal('Information Updated!', 'Your personal information has been updated successfully.')
    }, 450)
  } catch (err) {
    otpError.value = errorMessage(err, 'Invalid verification code. Please try again.')
  } finally {
    verifyingOtp.value = false
  }
}

// --- Store photo ---

const photoFile = ref(null)
const photoPreview = ref(null)
const originalPhotoUrl = ref(null)
const showCropModal = ref(false)
const cropperRef = ref(null)
// Set once the cropper has loaded the photo, so Apply Crop can't run on an empty frame.
const cropReady = ref(false)
const savingPhoto = ref(false)

const shownStorePhoto = computed(() => photoPreview.value || store.value.store_picture_url || null)

const triggerUpload = () => {
  document.getElementById('storePhotoUpload').click()
}

const onFileSelected = (event) => {
  const file = event.target.files?.[0]
  // Cleared so choosing the same photo again after cancelling still opens the cropper.
  event.target.value = ''
  if (!file) return
  originalPhotoUrl.value = URL.createObjectURL(file)
  cropReady.value = false
  showCropModal.value = true
}

// Saves the framed 16:9 area from the original photo, the same frame as vendor sign-up.
const applyCrop = async () => {
  const blob = await cropperRef.value?.toBlob()
  if (!blob) return
  photoFile.value = new File([blob], 'store_cover.jpg', { type: 'image/jpeg' })
  photoPreview.value = URL.createObjectURL(blob)
  showCropModal.value = false
}

const cancelPhoto = () => {
  photoFile.value = null
  photoPreview.value = null
}

// The store endpoint always takes the name alongside the photo, so the current name is sent with it.
const saveStoreInfo = (fields) => {
  const fd = new FormData()
  fd.append('store_name', fields.store_name)
  if (fields.store_picture) fd.append('store_picture', fields.store_picture)
  fd.append('_method', 'PUT')
  return api.post('/vendor/store/info', fd, { headers: { 'Content-Type': 'multipart/form-data' } })
}

const savePhoto = async () => {
  if (!photoFile.value) return
  savingPhoto.value = true
  try {
    const { data } = await saveStoreInfo({ store_name: store.value.store_name || '', store_picture: photoFile.value })
    store.value.store_picture_url = data?.store_picture_url || photoPreview.value
    photoFile.value = null
    photoPreview.value = null
    openSuccessModal('Photo Updated!', 'Your store photo has been updated successfully.')
  } catch (err) {
    $q.notify({ type: 'negative', message: errorMessage(err, 'Failed to update the store photo.') })
  } finally {
    savingPhoto.value = false
  }
}

// --- Store name ---

const showStoreNameModal = ref(false)
const savingStoreName = ref(false)
const storeNameFormRef = ref(null)
const storeNameInput = ref('')
const storeNameChanged = computed(() => storeNameInput.value.trim() !== (store.value.store_name || ''))
const canSaveStoreName = computed(() => storeNameChanged.value && !!storeNameInput.value.trim())

const startEditStoreName = () => {
  storeNameInput.value = store.value.store_name || ''
  storeNameFormRef.value?.resetValidation()
  showStoreNameModal.value = true
}

const attemptCloseStoreName = () => {
  requestClose(storeNameChanged.value, () => { showStoreNameModal.value = false })
}

const saveStoreName = async () => {
  if (!canSaveStoreName.value) return
  if (!(await storeNameFormRef.value.validate())) return
  savingStoreName.value = true
  try {
    const name = storeNameInput.value.trim()
    await saveStoreInfo({ store_name: name })
    store.value.store_name = name
    showStoreNameModal.value = false
    openSuccessModal('Store Updated!', 'Your store name has been updated successfully.')
  } catch (err) {
    $q.notify({ type: 'negative', message: errorMessage(err, 'Failed to update the store name.') })
  } finally {
    savingStoreName.value = false
  }
}

// --- Address ---

const showAddressModal = ref(false)
const savingAddress = ref(false)
const addressFormRef = ref(null)
const addressMapReady = ref(false)
const addressInitial = ref(null)
const editAddress = reactive({ address: '', latitude: null, longitude: null })

const addressChanged = computed(() =>
  editAddress.address.trim() !== (store.value.address || '') ||
  Number(editAddress.latitude) !== Number(store.value.latitude) ||
  Number(editAddress.longitude) !== Number(store.value.longitude)
)

// A pin is required, since customers find stores on the map.
const canSaveAddress = computed(() =>
  addressChanged.value && !!editAddress.address.trim() && editAddress.latitude !== null && editAddress.longitude !== null
)

const startEditAddress = () => {
  editAddress.address = store.value.address || ''
  editAddress.latitude = hasPin.value ? Number(store.value.latitude) : null
  editAddress.longitude = hasPin.value ? Number(store.value.longitude) : null
  addressInitial.value = hasPin.value ? { latitude: editAddress.latitude, longitude: editAddress.longitude } : null
  addressFormRef.value?.resetValidation()
  showAddressModal.value = true
}

const onLocationSelected = ({ latitude, longitude, address }) => {
  editAddress.latitude = latitude
  editAddress.longitude = longitude
  if (address) editAddress.address = address
}

const attemptCloseAddress = () => {
  requestClose(addressChanged.value, () => { showAddressModal.value = false })
}

const saveAddress = async () => {
  if (!canSaveAddress.value) return
  if (!(await addressFormRef.value.validate())) return
  savingAddress.value = true
  try {
    const payload = { address: editAddress.address.trim(), latitude: editAddress.latitude, longitude: editAddress.longitude }
    await api.put('/vendor/store/address', payload)
    Object.assign(store.value, payload)
    showAddressModal.value = false
    openSuccessModal('Address Updated!', 'Your store address has been updated successfully.')
  } catch (err) {
    $q.notify({ type: 'negative', message: errorMessage(err, 'Failed to update the address.') })
  } finally {
    savingAddress.value = false
  }
}

// --- Store hours ---

const showHoursModal = ref(false)
const savingHours = ref(false)
const editHours = ref([])

const invalidDay = (day) => day.isOpen && (!day.openTime || !day.closeTime || day.closeTime <= day.openTime)
const hoursChanged = computed(() => JSON.stringify(editHours.value) !== JSON.stringify(savedHours.value))
const canSaveHours = computed(() => hoursChanged.value && !editHours.value.some(invalidDay))

const startEditHours = () => {
  editHours.value = savedHours.value.map(day => ({ ...day }))
  showHoursModal.value = true
}

const applyMondayToAll = () => {
  const [monday, ...rest] = editHours.value
  rest.forEach(day => Object.assign(day, { isOpen: monday.isOpen, openTime: monday.openTime, closeTime: monday.closeTime }))
}

const attemptCloseHours = () => {
  requestClose(hoursChanged.value, () => { showHoursModal.value = false })
}

const saveHours = async () => {
  if (!canSaveHours.value) return
  savingHours.value = true
  try {
    await api.put('/vendor/profile/hours', { operatingDays: editHours.value })
    savedHours.value = editHours.value.map(day => ({ ...day }))
    showHoursModal.value = false
    openSuccessModal('Store Hours Updated!', 'Your store hours have been updated successfully.')
  } catch (err) {
    $q.notify({ type: 'negative', message: errorMessage(err, 'Failed to update store hours.') })
  } finally {
    savingHours.value = false
  }
}

// --- Password ---

const showPasswordModal = ref(false)
const passwordFormRef = ref(null)
const savingPassword = ref(false)
const showCurrentPassword = ref(false)
const showNewPassword = ref(false)
const showConfirmPassword = ref(false)
const passwords = reactive({ current: '', new: '', confirm: '' })

// Same rule as sign-up's password check.
const isNewPasswordValid = computed(() => passwords.new.length >= 8)

const newPasswordMessage = computed(() => {
  if (!passwords.new) return null
  if (!isNewPasswordValid.value) return { type: 'error', text: 'Minimum 8 characters' }
  return { type: 'success', text: 'Strong password.' }
})

const confirmPasswordMessage = computed(() => {
  if (!passwords.confirm) return null
  if (passwords.confirm !== passwords.new) return { type: 'error', text: 'Passwords do not match.' }
  return { type: 'success', text: 'Passwords match.' }
})

const canSavePassword = computed(() => !!passwords.current && isNewPasswordValid.value && passwords.confirm === passwords.new)
const hasPasswordChanges = computed(() => !!passwords.current || !!passwords.new || !!passwords.confirm)

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

const savePassword = async () => {
  if (!(await passwordFormRef.value.validate())) return
  savingPassword.value = true
  try {
    await api.put('/vendor/profile/password', {
      current_password: passwords.current,
      new_password: passwords.new,
      new_password_confirmation: passwords.confirm
    })
    cancelPasswordModal()
    openSuccessModal('Password Updated!', 'Your password has been changed successfully.')
  } catch (err) {
    $q.notify({ type: 'negative', message: errorMessage(err, 'Failed to update password.') })
  } finally {
    savingPassword.value = false
  }
}

// --- Delete account ---

const showDeleteModal = ref(false)
const showAccountDeletedModal = ref(false)
const deletingAccount = ref(false)
const deleteConfirmInput = ref('')
// Typing the store name, rather than the owner's first name, makes sure it's the right store being deleted.
const deleteConfirmName = computed(() => (store.value.store_name || user.value.full_name || '').trim())
const deleteConfirmMatches = computed(() =>
  deleteConfirmName.value !== '' && deleteConfirmInput.value.trim().toLowerCase() === deleteConfirmName.value.toLowerCase()
)

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
    await api.delete('/vendor/account')
    clearAuthStorage()
    showDeleteModal.value = false
    showAccountDeletedModal.value = true
  } catch (err) {
    $q.notify({ type: 'negative', message: errorMessage(err, 'Failed to delete account.') })
  } finally {
    deletingAccount.value = false
  }
}
</script>

<style scoped>
/* The same page as the consumer profile, on the vendor layout's white ground. */
.profile-page {
  background: #ffffff;

  font-family: 'Roboto', Arial, sans-serif;
}

.profile-container {
  width: 100%;
  max-width: 1000px;
  box-sizing: border-box;

  margin: 0 auto;

  padding: 24px;
}

.page-header-block {
  margin-bottom: 20px;
}

.page-title {
  margin: 0 0 4px;

  font-size: var(--fs-3xl);
  font-weight: 700;
  line-height: 1.3;

  color: var(--c-text);
}

.page-subtitle {
  margin: 0;

  font-size: var(--fs-sm);

  color: var(--c-subtle);
}

.profile-card {
  border: 1px solid var(--c-border);
  border-radius: var(--r-surface);

  background: #ffffff;

  /* !important beats Quasar's `flat` prop, which adds its own no-shadow !important utility class. */
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05) !important;

  transition: box-shadow 0.2s, border-color 0.2s;

  animation: profile-fade-up 0.5s ease both;
  animation-delay: 0.06s;
}

/* Page entrance on load only, using opacity and transform so it never shifts layout. */
@keyframes profile-fade-up {
  from { opacity: 0; transform: translateY(14px); }
  to { opacity: 1; transform: translateY(0); }
}

.page-header-block {
  animation: profile-fade-up 0.5s ease both;
}

@media (prefers-reduced-motion: reduce) {
  .profile-card,
  .page-header-block {
    animation: none;
  }
}

.profile-card:hover {
  border-color: var(--c-border-strong);

  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.06) !important;
}

.profile-card :deep(.q-card__section) {
  padding: 20px;
}

/* Stretches Personal Information and Store Photo to the same height. */
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

/* Centred in the height left over when the card stretches, so no empty band sits at the bottom. */
.photo-card-body {
  display: flex;
  flex-direction: column;
  justify-content: center;

  flex: 1;
  margin-top: 20px;
}

/* CARD HEADER (title and subtitle, with an optional action button) */

.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;

  gap: 12px;
  margin-bottom: 12px;
}

.section-title {
  font-size: var(--fs-xl);
  font-weight: 700;
  line-height: 1.3;

  color: var(--c-text);
}

.section-subtitle {
  margin-top: 2px;

  font-size: var(--fs-sm);
  line-height: 1.4;

  color: var(--c-subtle);
}

/* Sized explicitly, not through q-btn's dense prop, so every pill renders the same height. */
.card-action-btn {
  flex-shrink: 0;

  height: 32px;
  min-height: 32px;
  padding: 0 14px;

  border-radius: var(--r-control);

  font-size: var(--fs-sm);
  font-weight: 600;

  transition: background-color 0.15s, border-color 0.15s;
}

.card-action-btn:hover {
  background: var(--c-brand-tint);
}

.card-action-btn :deep(.q-icon) {
  font-size: 18px;
}

.card-action-btn :deep(.on-left) {
  margin-right: 8px;
}

/* Leaves round buttons alone so the camera badge keeps its circle. */
.profile-container :deep(.q-btn:not(.q-btn--round)) {
  border-radius: var(--r-control);
}

.dialog-close-btn {
  color: var(--c-muted);
}

.profile-container :deep(.q-btn) {
  font-size: var(--fs-sm);
}

/* Primary buttons, the same flat red and shadow lift as the consumer profile. */
.btn-gradient {
  background: var(--c-brand) !important;

  box-shadow: 0 2px 8px rgba(189, 36, 39, 0.25);

  transition: background-color 0.15s, box-shadow 0.2s, transform 0.2s;
}

.btn-gradient:hover {
  background: var(--c-brand-hover) !important;

  box-shadow: 0 6px 16px rgba(189, 36, 39, 0.32);

  transform: translateY(-1px);
}

.btn-gradient:active {
  background: var(--c-brand-active) !important;

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

/* A deeper red than the primary button, so it reads as higher stakes. */
.btn-danger-gradient {
  background: var(--c-danger) !important;
  color: #ffffff !important;

  box-shadow: 0 2px 8px rgba(185, 28, 28, 0.3);

  transition: background-color 0.15s, box-shadow 0.2s, transform 0.2s;
}

.btn-danger-gradient:hover {
  background: var(--c-brand-deep) !important;

  box-shadow: 0 6px 16px rgba(185, 28, 28, 0.4);

  transform: translateY(-1px);
}

.btn-danger-gradient:focus-visible {
  outline: none;
  box-shadow: 0 0 0 3px rgba(185, 28, 28, 0.3);
}

.btn-danger-gradient:disabled,
.btn-danger-gradient.disabled {
  opacity: 0.45;
}

/* INFO ROWS (icon, label and value) */

.info-row {
  display: flex;
  align-items: center;

  gap: 14px;
  margin: 0 -10px;
  padding: 14px 10px;

  border-bottom: 1px solid var(--c-hairline);
  border-radius: var(--r-surface);

  transition: background-color 0.15s;
}

.info-row:hover {
  background: var(--c-surface-2);
}

.info-row:last-of-type,
.info-row-last {
  border-bottom: none;
}

.info-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;

  width: 44px;
  height: 44px;

  border-radius: var(--r-surface);

  background: linear-gradient(145deg, var(--c-brand-tint) 0%, var(--c-brand-tint-2) 100%);
  color: var(--c-brand);
}

.info-body {
  flex: 1;
  min-width: 0;
}

.info-label {
  font-size: var(--fs-xs);

  color: var(--c-muted);
}

.info-value {
  margin-top: 5px;

  font-size: var(--fs-lg);
  font-weight: 500;
  line-height: 1.4;

  color: var(--c-text);

  overflow-wrap: anywhere;
}

/* Whether the store has a map pin, under its address. */
.info-meta {
  display: flex;
  align-items: center;

  gap: 4px;
  margin-top: 4px;

  font-size: var(--fs-xs);

  color: var(--c-muted);
}

.info-meta--ok {
  color: var(--c-success);
}

.hours-lines {
  margin-top: 5px;
}

.hours-line {
  font-size: var(--fs-md);
  font-weight: 500;
  line-height: 1.5;

  color: var(--c-text);
}

/* STORE PHOTO — a 16:9 frame, since it shows the storefront rather than a face. */

.store-photo-wrap {
  position: relative;

  width: 100%;
  aspect-ratio: 16 / 9;
}

.store-photo {
  display: block;

  width: 100%;
  height: 100%;

  border-radius: var(--r-surface);

  object-fit: cover;

  box-shadow:
    0 0 0 1px var(--c-border),
    0 6px 16px rgba(0, 0, 0, 0.1);
}

.store-photo--empty {
  display: flex;
  align-items: center;
  justify-content: center;

  background: var(--c-surface);
  color: var(--c-muted);
}

/* Sits on the photo's lower-right corner so it never covers the storefront itself. */
.photo-camera-btn {
  position: absolute;
  right: -8px;
  bottom: -8px;

  display: flex;
  align-items: center;
  justify-content: center;

  width: 36px;
  height: 36px;
  min-width: 36px;
  min-height: 36px;
  padding: 0;

  box-shadow:
    0 0 0 3px #ffffff,
    0 2px 6px rgba(0, 0, 0, 0.25);
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

  font-size: var(--fs-xs);

  color: var(--c-muted);
}

.profile-container :deep(.q-btn--outline.text-primary) {
  transition: background-color 0.15s, border-color 0.15s;
}

.profile-container :deep(.q-btn--outline.text-primary:hover) {
  border-color: var(--c-brand);
  background: var(--c-brand-tint);
}

/* Red outline buttons (Cancel, Keep Editing), the same design as the Edit pill, shared by every dialog and the photo card. */
.profile-container :deep(.q-btn--outline.text-primary),
.profile-dialog-card :deep(.q-btn--outline.text-primary) {
  transition: background-color 0.15s, border-color 0.15s, box-shadow 0.15s;
}

.profile-dialog-card :deep(.q-btn--outline.text-primary:hover),
.profile-dialog-card :deep(.q-btn--outline.text-primary:active) {
  background: var(--c-brand-tint);
}

.profile-container :deep(.q-btn--outline.text-primary:focus-visible),
.profile-dialog-card :deep(.q-btn--outline.text-primary:focus-visible) {
  outline: none;
  box-shadow: 0 0 0 3px rgba(189, 36, 39, 0.3);
}

/* DANGER ZONE */

.danger-card {
  border-color: var(--c-danger-tint);
  background: var(--c-brand-tint);
}

.danger-card .section-subtitle {
  margin-bottom: 20px;
}

.danger-row {
  display: flex;
  align-items: center;

  gap: 14px;
  padding: 10px;

  border-radius: var(--r-surface);
  border: 1px solid var(--c-danger-tint);

  background: #ffffff;

  cursor: pointer;

  transition: background-color 0.15s, border-color 0.15s, box-shadow 0.2s, transform 0.15s;
}

.danger-row:hover {
  background: var(--c-brand-tint);

  box-shadow: 0 4px 14px rgba(220, 38, 38, 0.1);
  transform: translateY(-1px);
}

.danger-row:focus-visible {
  outline: 2px solid var(--c-danger);
  outline-offset: 2px;
}

.danger-icon {
  background: linear-gradient(145deg, var(--c-danger-tint) 0%, var(--c-danger-tint) 100%);
  color: var(--c-danger);
}

.danger-title {
  font-size: var(--fs-md);
  font-weight: 700;

  color: var(--c-danger);
}

.danger-desc {
  margin-top: 1px;

  font-size: var(--fs-xs);

  color: var(--c-danger);
}

/* DIALOGS — the consumer profile's dialog shell, header, fields and buttons. */

.dialog-header {
  display: flex;
  align-items: center;

  gap: 12px;
}

/* Overrides Quasar's 2rem line-height on the title, which adds empty space below it. */
.profile-dialog-card :deep(.text-h6) {
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

.edit-field,
.edit-field-tight {
  margin-top: 16px;
}

.edit-field:first-child {
  margin-top: 0;
}

.edit-field-label {
  margin-bottom: 6px;

  font-size: var(--fs-sm);
  font-weight: 500;

  color: var(--c-text);
}

.edit-field-hint {
  margin-top: 6px;

  font-size: var(--fs-xs);

  color: var(--c-muted);
}

.edit-field-hint-error {
  color: var(--c-danger);
}

.edit-field-hint-success {
  display: flex;
  align-items: center;

  gap: 3px;

  font-weight: 600;

  color: var(--c-success);
}

.password-icon {
  font-size: 16px;

  color: var(--c-muted);
}

.delete-warning {
  margin: 0;

  font-size: var(--fs-md);
  line-height: 1.6;

  color: var(--c-text-2);
}

.success-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;

  width: 64px;
  height: 64px;

  border-radius: 50%;

  background: var(--c-success-tint);
  color: var(--c-success);

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

@media (prefers-reduced-motion: reduce) {
  .success-icon {
    animation: none;
  }
}

.success-card .text-h6 {
  margin-top: 16px;
}

.success-card .section-subtitle {
  margin: 6px 0 0;
}

.success-card .q-btn {
  margin-top: 20px;
}

/* OTP boxes, the same as the consumer profile's phone check. */

.otp-row {
  display: flex;
  justify-content: center;

  gap: 10px;
}

.otp-box {
  width: 48px;
  height: 48px;
  padding: 0;

  border: 1px solid var(--c-border-strong);
  border-radius: var(--r-control);

  background: #ffffff;

  font-family: 'Roboto', Arial, sans-serif;
  font-size: var(--fs-2xl);
  font-weight: 600;
  line-height: 1;

  text-align: center;

  color: var(--c-text);

  outline: none;

  transition: border-color 0.15s, box-shadow 0.15s, background-color 0.2s;
}

.otp-box:focus {
  border-color: var(--c-brand);

  box-shadow: 0 0 0 1px rgba(189, 36, 39, 0.1);
}

.otp-box-error {
  border-color: var(--c-danger);
}

.otp-box-success {
  border-color: var(--c-success);

  background: var(--c-success-tint);
  color: var(--c-success);
}

.otp-error {
  font-size: var(--fs-sm);
}

.otp-meta {
  display: flex;
  flex-direction: column;
  align-items: center;

  gap: 6px;
  margin-top: 16px;

  font-size: var(--fs-sm);
}

.otp-resend {
  color: var(--c-muted);
}

.otp-resend-link {
  color: var(--c-brand);
  font-weight: 600;

  text-decoration: none;
}

.otp-resend-link:hover {
  text-decoration: underline;
}

/* ADDRESS DIALOG */

.address-map-frame {
  overflow: hidden;

  border: 1px solid var(--c-border);
  border-radius: var(--r-surface);
}

.address-map-frame :deep(.location-wrapper .map) {
  height: 260px;
}

/* HOURS DIALOG */

/* A short hint on the left and the copy action as a small outline pill on the right. */
.hours-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;

  gap: 8px 12px;
  margin-bottom: 6px;
}

.hours-toolbar-hint {
  font-size: var(--fs-xs);

  color: var(--c-muted);
}

/* Time boxes are sized to a time like "12:30 PM", so every row lines up and none stretches across the dialog. */
.hours-dialog {
  --hours-select: 124px;
}

.hours-row {
  display: grid;
  grid-template-columns: 132px minmax(0, 1fr);
  align-items: center;

  gap: 4px 12px;
  padding: 6px 0;

  border-bottom: 1px solid var(--c-hairline);
}

.hours-row:last-child {
  border-bottom: none;
}

.hours-day {
  display: flex;
  align-items: center;

  gap: 10px;
}

.hours-day-name {
  font-size: var(--fs-sm);
  font-weight: 600;

  color: var(--c-text);
}

.hours-row--closed .hours-day-name {
  color: var(--c-muted);
}

.hours-times {
  display: grid;
  grid-template-columns: var(--hours-select) minmax(0, 1fr) var(--hours-select);
  align-items: center;

  width: calc(var(--hours-select) * 2 + 36px);
  gap: 8px;
}

.hours-times .q-field {
  min-width: 0;
}

.hours-sep {
  font-size: var(--fs-xs);
  text-align: center;

  color: var(--c-muted);
}

/* As wide as the two time boxes, so a closed day keeps the same shape as an open one. */
.hours-closed {
  display: flex;
  align-items: center;

  width: calc(var(--hours-select) * 2 + 36px);
  height: 40px;
  padding: 0 12px;

  border-radius: var(--r-control);

  background: var(--c-surface-2);

  font-size: var(--fs-xs);
  font-weight: 600;

  color: var(--c-muted);
}

.hours-error {
  grid-column: 2 / -1;
  margin-top: 0;
}

/* DIALOG SHELL */
/* Dialogs are teleported to the body, so these rules anchor on .profile-dialog-card rather than the page. */

.profile-dialog-card {
  border: 1px solid var(--c-border);
  border-radius: var(--r-surface);

  box-shadow: 0 18px 48px rgba(17, 17, 17, 0.18) !important;

  --q-transition-duration: 200ms;
}

.dialog-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;

  width: 44px;
  height: 44px;

  border-radius: var(--r-surface);

  background: linear-gradient(145deg, var(--c-brand-tint) 0%, var(--c-brand-tint-2) 100%);
  color: var(--c-brand);
}

.dialog-icon--danger {
  background: linear-gradient(145deg, var(--c-brand-tint-2) 0%, var(--c-brand-tint-3) 100%);
  color: var(--c-brand-deep);
}

.profile-dialog-card :deep(.q-card__section) {
  padding: 24px;
}

.profile-dialog-card :deep(.dialog-header:not(.discard-confirm-header)) {
  padding-bottom: 16px;

  border-bottom: 1px solid var(--c-hairline);
}

.profile-dialog-card :deep(.dialog-body) {
  padding-top: 20px;
  padding-bottom: 20px;
}

.profile-dialog-card :deep(.q-card__actions) {
  display: flex;
  justify-content: flex-end;

  gap: 10px;
  padding: 16px 24px;

  border-top: 1px solid var(--c-border);
}

.profile-dialog-card :deep(.q-card__actions .q-btn-item + .q-btn-item) {
  margin-left: 0;
}

.profile-dialog-card .discard-confirm-header {
  padding-bottom: 20px;
}

.discard-confirm-actions {
  display: flex;
  justify-content: flex-end;

  gap: 10px;
  padding: 16px 24px;
}

.discard-confirm-actions :deep(.q-btn-item + .q-btn-item) {
  margin-left: 0;
}

/* Paired dialog buttons share one width, so Cancel doesn't shrink beside a longer label. */
.profile-dialog-card :deep(.q-card__actions .q-btn) {
  min-width: 132px !important;
}

.profile-dialog-card :deep(.q-btn:not(.q-btn--round)) {
  height: 44px;
  min-height: 44px;

  border-radius: var(--r-control);
  font-size: var(--fs-sm);
}

/* Copy Monday is a small outline pill, the same size as the page's Edit pills. */
.profile-dialog-card :deep(.hours-copy-btn) {
  height: 32px;
  min-height: 32px;
  padding: 0 12px;

  font-size: var(--fs-xs);
  font-weight: 600;
}

.profile-dialog-card :deep(.hours-copy-btn .q-icon) {
  font-size: 16px;
}

/* The time dropdowns are 40px, lighter than the dialog's 48px text fields, so seven rows fit without scrolling. */
.profile-dialog-card :deep(.hours-select .q-field__control),
.profile-dialog-card :deep(.hours-select .q-field__marginal) {
  height: 40px !important;
  min-height: 40px !important;
}

.profile-dialog-card :deep(.hours-select .q-field__native) {
  min-height: 40px;

  font-size: var(--fs-sm);
}

/* A button focused as its dialog opens keeps Quasar's focus tint for keyboard users only. */
.profile-dialog-card :deep(.q-btn:focus:not(:focus-visible):not(:hover) > .q-focus-helper) {
  opacity: 0;
}

.profile-dialog-card :deep(.dialog-header .q-btn--round .q-icon) {
  font-size: 19px;
}

/* Inputs are 48px tall, except the OTP boxes and the address box, which grows with its text. */
.profile-dialog-card :deep(.q-field--outlined:not(.otp-box):not(.q-textarea) .q-field__control) {
  height: 48px;

  border-radius: var(--r-control);
}

.profile-dialog-card :deep(.q-textarea .q-field__control) {
  min-height: 72px;

  border-radius: var(--r-control);
}

.profile-dialog-card :deep(.q-field--focused:not(.q-field--error)) {
  color: var(--c-brand);
}

/* Below the md breakpoint the cards stack, with Store Photo moved ahead of Personal Information. */
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
    font-size: var(--fs-2xl);
  }

  .profile-card :deep(.q-card__section) {
    padding: 16px;
  }

  .section-title {
    font-size: var(--fs-lg);
  }

  .section-subtitle {
    font-size: var(--fs-xs);
  }

  .card-action-btn {
    padding: 0 12px;

    font-size: var(--fs-xs);
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
    font-size: var(--fs-md);
  }

  .hours-line {
    font-size: var(--fs-sm);
  }

  .photo-camera-btn {
    width: 32px;
    height: 32px;
    min-width: 32px;
    min-height: 32px;
  }

  .danger-row {
    padding: 8px;
  }

  .edit-field-hint {
    font-size: var(--fs-2xs);
  }

  .delete-warning {
    font-size: var(--fs-sm);
  }

  .otp-row {
    gap: 8px;
  }

  .otp-box {
    flex: 0 1 48px;
    min-width: 0;
  }

  .hours-row {
    grid-template-columns: minmax(0, 1fr);
  }

  /* Stacked under the day name on phones, the two boxes share the full width. */
  .hours-times {
    grid-template-columns: minmax(0, 1fr) 20px minmax(0, 1fr);
    width: auto;
  }

  .hours-closed {
    width: auto;
  }

  .hours-error {
    grid-column: 1 / -1;
  }

  :global(.q-dialog__inner--minimized:has(.profile-dialog-card)) {
    padding: 12px;
  }

  .profile-dialog-card {
    max-width: 100% !important;
  }

  .profile-dialog-card :deep(.q-card__section) {
    padding: 18px;
  }

  .profile-dialog-card :deep(.dialog-header) {
    padding-bottom: 16px;

    align-items: flex-start;
  }

  .profile-dialog-card :deep(.dialog-body) {
    padding-top: 16px;
    padding-bottom: 16px;
  }

  .profile-dialog-card :deep(.q-card__actions) {
    gap: 10px;
    padding: 14px 18px;
  }

  .profile-dialog-card :deep(.q-card__actions .q-btn) {
    flex: 1 1 0;
    min-width: 0 !important;
    margin-left: 0 !important;
    margin-right: 0 !important;
  }
}
</style>
