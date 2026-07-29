<template>
  <q-page class="status-page">
    <q-card class="status-dialog">
      <q-card-section class="status-content">
        <img
          src="@/assets/tindahan-mobile.png"
          alt="Tindahan Logo"
          class="status-logo"
        />

        <div class="status-icon-wrap">
          <q-icon name="block" size="34px" />
        </div>

        <div class="status-title">Application Not Approved</div>

        <p class="status-message">
          We reviewed your merchant application for Tindahan. Unfortunately,
          we cannot approve your request at this time due to specific
          compliance requirements.
        </p>

        <div v-if="rejectionReason" class="reason-box">
          <div class="reason-label flex items-center justify-between">
            <div>
              <q-icon name="error_outline" size="15px" class="q-mr-xs" />
              Reason for Rejection
            </div>
            <div v-if="rejectedBy" class="rejected-by-label flex items-center">
              <q-icon name="person" size="14px" class="q-mr-xs" />
              Reviewed by {{ rejectedBy }}
            </div>
          </div>
          <p class="reason-text">{{ rejectionReason }}</p>
        </div>
      </q-card-section>

      <q-card-actions class="status-actions">
        <q-btn
          label="Contact Support"
          icon="support_agent"
          no-caps
          unelevated
          class="status-btn primary-btn"
          @click="showContactSupport = true"
        />
        <q-btn
          label="Logout"
          icon="logout"
          no-caps
          outline
          class="status-btn outline-btn"
          @click="logout"
        />
      </q-card-actions>
    </q-card>

    <ContactSupportModal v-model="showContactSupport" />
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useAuth } from '@/composables/useAuth'
import ContactSupportModal from '@/components/modals/ContactSupportModal.vue'

const route = useRoute()
const { logout } = useAuth()

const showContactSupport = ref(false)
const rejectionReason = ref('')
const rejectedBy = ref('')

onMounted(() => {
  rejectionReason.value = route.query.reason || ''
  rejectedBy.value = route.query.rejected_by || ''
})
</script>

<style scoped>
.status-page {
  min-height: 100vh;
  width: 100%;

  display: flex;
  align-items: center;
  justify-content: center;

  padding: 40px 20px;

  background: #f4f4f4;

  font-family: 'Roboto', Arial, sans-serif;
}

.status-dialog {
  width: 100%;
  max-width: 460px;

  padding: 12px;

  border-radius: 14px;

  box-shadow: 0 14px 40px rgba(0, 0, 0, 0.1);
}

.status-content {
  text-align: center;

  padding: 36px 34px 14px;
}

.status-logo {
  display: block;

  width: 150px;

  margin: 0 auto 18px;

  object-fit: contain;
}

.status-icon-wrap {
  display: inline-flex;
  align-items: center;
  justify-content: center;

  width: 66px;
  height: 66px;

  border-radius: 50%;

  background: #fde8e8;
  color: #ef4444;

  margin-bottom: 18px;
}

.status-title {
  font-size: 21px;
  font-weight: 700;

  color: #1a1a1a;

  margin-bottom: 12px;
}

.status-message {
  font-size: 14px;
  line-height: 1.65;

  color: #666666;

  margin: 0;
}

.reason-box {
  margin-top: 22px;
  padding: 16px 18px;

  border-radius: 10px;

  background: #f6f6f7;
  border: 1px solid #ececec;

  text-align: left;
}

.reason-label {
  font-size: 13px;
  font-weight: 700;
  color: #d32f2f;
  margin-bottom: 6px;
}

.rejected-by-label {
  font-size: 12px;
  font-weight: 500;
  color: #666;
}

.reason-text {
  font-size: 13px;
  line-height: 1.65;

  color: #444444;

  margin: 0;
}

.status-actions {
  padding: 22px 34px 26px;

  gap: 11px;
}

.status-btn {
  flex: 1;

  height: 46px;

  border-radius: 7px;

  font-size: 14px;
  font-weight: 500;
}

.primary-btn {
  background: #bd2427;
  color: #ffffff;
}

.primary-btn:hover {
  background: #a91e21;
}

.outline-btn {
  color: #333333;
  border-color: #d6d6da;
}

@media (max-width: 600px) {
  .status-actions {
    flex-direction: column;
  }
}
</style>
