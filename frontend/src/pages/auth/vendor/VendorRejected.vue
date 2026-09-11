<template>
  <q-page class="status-page">
    <q-card class="status-dialog">
      <q-card-section class="status-content">
        <img
          src="@/assets/tindahan-mobile.png"
          alt="Tindahan Logo"
          class="status-logo"
        />

        <div class="status-icon-wrap status-icon-danger">
          <q-icon name="o_block" size="32px" />
        </div>

        <div class="status-title">Application Not Approved</div>

        <p class="status-message">
          We reviewed your merchant application for Tindahan. Unfortunately, we
          cannot approve your request at this time due to specific compliance
          requirements.
        </p>

        <div v-if="rejectionReason" class="reason-box">
          <div class="reason-label">
            <q-icon name="o_error_outline" size="16px" />
            Reason for Rejection
          </div>
          <p class="reason-text">{{ rejectionReason }}</p>
          <div v-if="rejectedBy" class="rejected-by-label">
            <q-icon name="o_person" size="14px" />
            Reviewed by {{ rejectedBy }}
          </div>
        </div>
      </q-card-section>

      <q-card-actions class="status-actions">
        <q-btn
          label="Contact Support"
          icon="o_support_agent"
          no-caps
          unelevated
          class="status-btn primary-btn"
          @click="showContactSupport = true"
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
    </q-card>

    <ContactSupportModal v-model="showContactSupport" />
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { api } from '@/boot/axios'
import { useAuth } from '@/composables/useAuth'
import ContactSupportModal from '@/components/modals/ContactSupportModal.vue'

const route = useRoute()
const { logout } = useAuth()

const showContactSupport = ref(false)
const rejectionReason = ref('')
const rejectedBy = ref('')
const loading = ref(true)

const fetchReason = async () => {
  try {
    const { data } = await api.get('/user')
    rejectionReason.value = data.rejection_reason || route.query.reason || ''
    rejectedBy.value = data.rejected_by || route.query.rejected_by || ''
  } catch (error) {
    if (error.response?.status === 401) {
      logout(true)
    }
  } finally {
    loading.value = false
  }
}

onMounted(fetchReason)
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
/* REASON */

.reason-box {
  margin-top: 20px;
  padding: 14px 16px;

  border: 1px solid var(--c-danger-line);
  border-radius: var(--r-md);

  background: var(--c-danger-tint);

  text-align: left;
}

.reason-label {
  display: flex;
  align-items: center;

  gap: 6px;

  margin-bottom: 6px;

  font-size: var(--fs-xs);
  font-weight: 700;

  color: var(--c-danger);
}

/* Its own line under the reason, since a full admin name is too long to share the heading row. */
.rejected-by-label {
  display: flex;
  align-items: center;

  gap: 4px;

  margin-top: 10px;
  padding-top: 10px;

  border-top: 1px solid var(--c-danger-line);

  font-size: var(--fs-2xs);
  font-weight: 500;

  color: var(--c-danger-muted);
}

.reason-text {
  font-size: var(--fs-xs);
  line-height: 1.6;

  color: var(--c-text-2);

  margin: 0;
}

.status-actions {
  padding-bottom: 36px;
}

.status-btn {
  flex: 1;
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

  /* Stacked buttons stretch to the card's width instead of shrinking to their labels. */
  .status-actions {
    flex-direction: column;
    align-items: stretch;

    padding: 24px 24px 32px;
  }

  .status-btn {
    flex: none;
  }
}
</style>
