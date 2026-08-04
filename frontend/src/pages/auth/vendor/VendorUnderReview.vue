<template>
  <q-page class="status-page">
    <q-card class="status-dialog">
      <q-card-section class="status-content">
        <img
          src="@/assets/tindahan-mobile.png"
          alt="Tindahan Logo"
          class="status-logo"
        />

        <div class="status-badge">
          <q-icon name="info" size="15px" />
          Status: Pending Approval
        </div>

        <div class="status-title">Application Under Review</div>

        <p class="status-message">
          Our administrators are currently reviewing your store details
          and business credentials to ensure everything meets our
          community standards.
        </p>

        <div class="next-steps-box">
          <div class="next-steps-title">
            <q-icon name="info" size="17px" />
            Next Steps
          </div>
          <p class="next-steps-text">
            The verification process typically takes 1-2 business days.
            You will receive an email notification once your account has
            been approved and your store is ready for setup.
          </p>
        </div>
      </q-card-section>

      <q-card-actions class="status-actions" vertical>
        <q-btn
          label="Check Application Status / Refresh"
          icon="refresh"
          no-caps
          unelevated
          class="status-btn primary-btn"
          :loading="loading"
          @click="checkStatus"
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

      <div class="support-link">
        Need help?
        <a href="#" @click.prevent="showContactSupport = true">Contact Support</a>
      </div>
    </q-card>

    <ContactSupportModal v-model="showContactSupport" />
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '@/boot/axios'
import { useAuth } from '@/composables/useAuth'
import ContactSupportModal from '@/components/modals/ContactSupportModal.vue'

const router = useRouter()
const { logout } = useAuth()
const loading = ref(false)
const showContactSupport = ref(false)

const checkStatus = async () => {
  loading.value = true
  try {
    const { data } = await api.get('/user')

    if (data.vendor_status === 'approved') {
      router.push('/vendor/dashboard')
    } else if (data.vendor_status === 'rejected') {
      router.push({
        path: '/auth/vendor/rejected',
        query: { reason: data.rejection_reason || '' }
      })
    }
    // still pending — stay on this page
  } catch (error) {
    if (error.response?.status === 401) {
      logout()
    }
  } finally {
    loading.value = false
  }
}

onMounted(checkStatus)
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

.status-badge {
  display: inline-flex;
  align-items: center;

  gap: 6px;

  padding: 5px 14px;

  border-radius: 20px;

  background: #eef0f2;

  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.02em;
  text-transform: uppercase;

  color: #555555;

  margin-bottom: 16px;
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

.next-steps-box {
  margin-top: 22px;
  padding: 16px 18px;

  border-radius: 10px;

  background: #f6f6f7;
  border: 1px solid #ececec;

  text-align: left;
}

.next-steps-title {
  display: flex;
  align-items: center;

  gap: 7px;

  font-size: 13px;
  font-weight: 700;

  color: #333333;

  margin-bottom: 7px;
}

.next-steps-text {
  font-size: 13px;
  line-height: 1.65;

  color: #666666;

  margin: 0;
}

.status-actions {
  padding: 16px 34px 8px;

  gap: 11px;
}

.status-btn {
  width: 100%;

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

.support-link {
  padding: 6px 34px 26px;

  text-align: center;

  font-size: 12px;

  color: #9a9aa2;
}

.support-link a {
  color: #333333;
  font-weight: 600;

  text-decoration: none;
}

.support-link a:hover {
  text-decoration: underline;
}
</style>
