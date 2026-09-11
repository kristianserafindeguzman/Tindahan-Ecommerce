<template>
  <q-page class="status-page">
    <q-card class="status-dialog">
      <q-card-section class="status-content">
        <img
          src="@/assets/tindahan-mobile.png"
          alt="Tindahan Logo"
          class="status-logo"
        />

        <div class="status-icon-wrap status-icon-warning">
          <q-icon name="o_hourglass_top" size="32px" />
        </div>

        <div class="status-title">Application Under Review</div>

        <p class="status-message">
          Our administrators are currently reviewing your store details and
          business credentials to ensure everything meets our community
          standards.
        </p>

        <div class="next-steps-box">
          <div class="next-steps-title">
            <q-icon name="o_info" size="16px" />
            Next Steps
          </div>
          <p class="next-steps-text">
            The verification process typically takes 1–2 business days. You will
            receive an email notification once your account has been approved
            and your store is ready for setup.
          </p>
        </div>
      </q-card-section>

      <q-card-actions class="status-actions" vertical>
        <q-btn
          label="Refresh Status"
          icon="refresh"
          no-caps
          unelevated
          class="status-btn primary-btn"
          :loading="loading"
          @click="checkStatus"
        />
        <q-btn
          label="Log out"
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

    if (data.vendor_status) {
      localStorage.setItem('vendor_status', data.vendor_status)
    }

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
      logout(true) // Force logout without confirmation dialog
    }
  } finally {
    loading.value = false
  }
}

onMounted(checkStatus)
</script>

<style scoped>
/* PAGE */

/* Same red gradient as the login and sign-up pages. */
.status-page {
  min-height: 100vh;
  width: 100%;

  display: flex;
  align-items: center;
  justify-content: center;

  padding: 40px 20px;

  background:
    linear-gradient(
      145deg,
      #c02226 0%,
      #9c171b 55%,
      #651012 100%
    );

  font-family: 'Roboto', Arial, sans-serif;
}

/* CARD */

.status-dialog {
  width: 100%;
  max-width: 440px;

  border-radius: var(--r-2xl);

  box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
}

.status-content {
  text-align: center;

  padding: 40px 36px 8px;
}

.status-logo {
  display: block;

  width: 130px;

  margin: 0 auto 22px;

  object-fit: contain;
}

/* Tinted tiles, the same icon language as the dialogs on the login page. */
.status-icon-wrap {
  display: inline-flex;
  align-items: center;
  justify-content: center;

  width: 64px;
  height: 64px;

  border-radius: var(--r-2xl);

  margin-bottom: 18px;
}

.status-icon-warning {
  background: var(--c-warning-tint);
  color: var(--c-warning);
}

.status-icon-danger {
  background: var(--c-danger-tint);
  color: var(--c-danger);
}

.status-title {
  font-size: 22px;
  line-height: 1.25;
  font-weight: 700;

  color: var(--c-text);

  margin-bottom: 10px;
}

.status-message {
  font-size: var(--fs-sm);
  line-height: 1.6;

  color: var(--c-text-3);

  margin: 0;
}

/* ACTIONS */

.status-actions {
  padding: 24px 36px 8px;

  gap: 12px;
}

/* Quasar spaces neighbouring card buttons with its own margin, which would double up with the gap. */
.status-actions .status-btn {
  margin: 0;
}

.status-btn {
  height: 48px;

  border-radius: var(--r-sm);

  font-family: 'Roboto', Arial, sans-serif;
  font-size: var(--fs-sm);
  font-weight: 600;
}

.primary-btn {
  background: var(--c-brand);
  color: #ffffff;

  box-shadow: var(--sh-brand);

  transition: background-color 0.15s, box-shadow 0.2s, transform 0.2s;
}

.primary-btn:hover {
  background: var(--c-brand-hover);

  box-shadow: var(--sh-brand-hover);

  transform: translateY(-1px);
}

.primary-btn:active {
  background: var(--c-brand-active);

  transform: translateY(0);
}

.outline-btn {
  color: var(--c-text-2);
}

/* Quasar draws the outline on ::before in the text colour, so the softer border has to be set there. */
.outline-btn::before {
  border-color: var(--c-border-strong);
}

.outline-btn:hover {
  background: var(--c-surface);
}

.status-btn:focus-visible {
  outline: none;
  box-shadow: 0 0 0 3px rgba(189, 36, 39, 0.3);
}
/* NEXT STEPS */

.next-steps-box {
  margin-top: 20px;
  padding: 14px 16px;

  border: 1px solid var(--c-hairline);
  border-radius: var(--r-md);

  background: var(--c-surface);

  text-align: left;
}

.next-steps-title {
  display: flex;
  align-items: center;

  gap: 6px;

  font-size: var(--fs-xs);
  font-weight: 700;

  color: var(--c-text-2);

  margin-bottom: 6px;
}

.next-steps-text {
  font-size: var(--fs-xs);
  line-height: 1.6;

  color: var(--c-text-3);

  margin: 0;
}

.status-btn {
  width: 100%;
}

/* SUPPORT LINK */

.support-link {
  padding: 16px 36px 32px;

  text-align: center;

  font-size: var(--fs-xs);

  color: var(--c-muted);
}

/* Padding cancelled by an equal negative margin grows the tap area to 44px without moving anything. */
.support-link a {
  padding: 16px 0;
  margin: -16px 0;

  font-weight: 600;

  color: var(--c-brand);

  text-decoration: none;
}

.support-link a:hover {
  text-decoration: underline;
}

/* MOBILE */

/* Full-bleed white on phones, the same as the login page. */
@media (max-width: 600px) {
  .status-page {
    align-items: stretch;

    padding: 0;

    background: #ffffff;
  }

  .status-dialog {
    max-width: 100%;

    border-radius: 0;

    box-shadow: none;
  }

  .status-content {
    padding: 32px 24px 8px;
  }

  .status-logo {
    width: 110px;
  }

  .status-title {
    font-size: 20px;
  }

  .status-actions {
    padding: 24px 24px 8px;
  }

  .support-link {
    padding: 16px 24px 32px;
  }
}
</style>
