<template>
  <!-- REVIEW — the store's full profile, with the decision buttons at the foot. -->
  <StoreProfileDialog
    v-if="current"
    v-model="reviewOpen"
    :name="current.store_name"
    :owner="ownerOf(current)"
    :photo="current.store?.store_picture_url"
    :operating-days="current.store?.operating_days"
    :opening-time="current.store?.opening_time"
    :closing-time="current.store?.closing_time"
    :email="current.email"
    :phone="current.phone"
    :latitude="current.store?.latitude"
    :longitude="current.store?.longitude"
    :info="personalInfo"
  >
    <template #badge>
      <span
        class="vp-status"
        :class="`vp-status--${accountStatusTone(current.status)}`"
        >{{ accountStatusLabel(current.status) }}</span
      >
    </template>

    <!-- The outcome so far, so a decided application says when and why. -->
    <template #notice>
      <div
        v-if="current.status === 'rejected'"
        class="adm-note adm-note--danger"
      >
        <q-icon name="o_info" size="18px" />
        <div>
          <div class="adm-note-title"
            >Rejected{{
              current.reviewed_at
                ? ` on ${formatShortDate(current.reviewed_at)}`
                : ''
            }}</div
          >
          <div>{{ current.rejection_reason || 'No reason was recorded.' }}</div>
        </div>
      </div>
      <div
        v-else-if="current.status === 'approved'"
        class="adm-note adm-note--success"
      >
        <q-icon name="o_verified" size="18px" />
        <div>
          <div class="adm-note-title"
            >Approved{{
              current.reviewed_at
                ? ` on ${formatShortDate(current.reviewed_at)}`
                : ''
            }}</div
          >
          <div>This store can sell on Tindahan.</div>
        </div>
      </div>
    </template>

    <template #actions>
      <template v-if="current.status === 'pending'">
        <q-btn
          outline
          no-caps
          color="primary"
          label="Reject"
          class="vp-dialog-btn"
          @click="openReject(current)"
        />
        <q-btn
          unelevated
          no-caps
          color="primary"
          label="Approve"
          class="vp-dialog-btn"
          @click="openApprove(current)"
        />
      </template>
      <template v-else-if="current.status === 'rejected'">
        <q-btn
          v-close-popup
          outline
          no-caps
          color="primary"
          label="Close"
          class="vp-dialog-btn"
        />
        <q-btn
          unelevated
          no-caps
          color="primary"
          label="Approve Instead"
          class="vp-dialog-btn"
          @click="openApprove(current)"
        />
      </template>
      <q-btn
        v-else
        v-close-popup
        outline
        no-caps
        color="primary"
        label="Close"
        class="vp-dialog-btn"
      />
    </template>
  </StoreProfileDialog>

  <!-- APPROVE — the Log out dialog's layout: centred icon and text, a line, then two equal buttons. -->
  <q-dialog v-model="approveOpen" persistent>
    <q-card class="vp-dialog adm-confirm">
      <div class="adm-confirm-body">
        <span class="vp-dialog-icon adm-confirm-icon"
          ><q-icon name="o_check_circle" size="24px"
        /></span>
        <div class="adm-confirm-title">Approve this store?</div>
        <p class="adm-confirm-text"
          ><strong>{{ target?.store_name }}</strong> will be able to sign in and
          start selling right away.</p
        >
      </div>
      <q-separator class="adm-sep" />
      <div class="adm-confirm-actions">
        <q-btn
          v-close-popup
          outline
          no-caps
          color="primary"
          label="Cancel"
          class="vp-dialog-btn"
          :disable="busy"
        />
        <q-btn
          unelevated
          no-caps
          color="primary"
          label="Approve"
          class="vp-dialog-btn"
          :loading="busy"
          @click="approve"
        />
      </div>
    </q-card>
  </q-dialog>

  <!-- REJECT — a written reason is required, since the applicant sees it. -->
  <q-dialog v-model="rejectOpen" persistent>
    <q-card class="vp-dialog adm-form-dialog">
      <div class="vp-dialog-head">
        <span class="vp-dialog-icon vp-dialog-icon--danger"
          ><q-icon name="o_block" size="22px"
        /></span>
        <div>
          <div class="vp-dialog-title">Reject this application?</div>
          <div class="vp-dialog-text"
            >Tell {{ target?.owner_name || 'the applicant' }} why
            <strong>{{ target?.store_name }}</strong> wasn't approved.</div
          >
        </div>
      </div>
      <q-separator class="adm-sep adm-sep--head" />
      <div class="vp-dialog-body">
        <div>
          <label class="vp-field-label" for="adm-reject-reason">Reason</label>
          <!-- The server keeps up to 500 characters. -->
          <q-input
            v-model="reason"
            for="adm-reject-reason"
            type="textarea"
            outlined
            autogrow
            autofocus
            counter
            maxlength="500"
            placeholder="e.g. The store photo doesn't show a storefront."
            class="vp-input adm-reason"
          />
        </div>
      </div>
      <div class="vp-dialog-actions">
        <q-btn
          v-close-popup
          outline
          no-caps
          color="primary"
          label="Cancel"
          class="vp-dialog-btn"
          :disable="busy"
        />
        <q-btn
          unelevated
          no-caps
          color="primary"
          label="Reject Application"
          class="vp-dialog-btn"
          :disable="!reason.trim()"
          :loading="busy"
          @click="reject"
        />
      </div>
    </q-card>
  </q-dialog>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useQuasar } from 'quasar'
import { api } from '@/boot/axios'
import StoreProfileDialog from '@/components/shared/StoreProfileDialog.vue'
import {
  accountStatusTone,
  accountStatusLabel,
  formatShortDate
} from '@/utils/accountStatus'
import '@/css/admin-pages.scss'

// Tells the page an application was approved or rejected, so it can move the row without reloading.
const emit = defineEmits(['decided'])
const $q = useQuasar()

const reviewOpen = ref(false)
const approveOpen = ref(false)
const rejectOpen = ref(false)
const current = ref(null)
const target = ref(null)
const reason = ref('')
const busy = ref(false)

const ownerOf = app => app?.owner_name || app?.store?.owner?.full_name || ''

const personalInfo = computed(() => [
  { label: 'Vendor', value: ownerOf(current.value) },
  { label: 'Applied on', value: formatShortDate(current.value?.applied_at) }
])

const openReview = app => {
  current.value = app
  reviewOpen.value = true
}

// Approving or rejecting closes the review first, so only one dialog is on screen.
const openApprove = app => {
  target.value = app
  reviewOpen.value = false
  approveOpen.value = true
}

const openReject = app => {
  target.value = app
  reason.value = ''
  reviewOpen.value = false
  rejectOpen.value = true
}

const approve = async () => {
  if (!target.value) return
  busy.value = true
  try {
    await api.post(`/admin/vendors/${target.value.store_id}/approve`)
    approveOpen.value = false
    $q.notify({
      type: 'positive',
      message: `${target.value.store_name} was approved.`
    })
    emit('decided', { storeId: target.value.store_id, status: 'approved' })
  } catch (error) {
    $q.notify({
      type: 'negative',
      message:
        error.response?.data?.message ||
        'Couldn’t approve this store. Please try again.'
    })
  } finally {
    busy.value = false
  }
}

const reject = async () => {
  const text = reason.value.trim()
  if (!target.value || !text) return
  busy.value = true
  try {
    await api.post(`/admin/vendors/${target.value.store_id}/reject`, {
      rejection_reason: text
    })
    rejectOpen.value = false
    $q.notify({
      type: 'positive',
      message: `${target.value.store_name} was rejected.`
    })
    emit('decided', {
      storeId: target.value.store_id,
      status: 'rejected',
      reason: text
    })
  } catch (error) {
    $q.notify({
      type: 'negative',
      message:
        error.response?.data?.message ||
        'Couldn’t reject this application. Please try again.'
    })
  } finally {
    busy.value = false
  }
}

defineExpose({ openReview, openApprove, openReject })
</script>
