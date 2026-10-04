<template>
  <AuthShell class="status-page">
    <q-card flat class="status-dialog">
      <q-card-section class="status-content">
        <div class="status-icon-wrap status-icon-danger">
          <q-icon name="o_block" size="32px" />
        </div>

        <div class="status-title">{{ t('Application Not Approved') }}</div>

        <p class="status-message">
          {{ t('We reviewed your merchant application for Tindahan. Unfortunately, we cannot approve your request at this time due to specific compliance requirements.') }}
        </p>

        <div v-if="rejectionReason" class="reason-box">
          <div class="reason-label">
            <q-icon name="o_error_outline" size="16px" />
            {{ t('Reason for Rejection') }}
          </div>
          <p class="reason-text">{{ rejectionReason }}</p>
          <div v-if="rejectedBy" class="rejected-by-label">
            <q-icon name="o_person" size="14px" />
            {{ t('Reviewed by') }} {{ rejectedBy }}
          </div>
        </div>
      </q-card-section>

      <q-card-actions class="status-actions">
        <q-btn
          :label="t('Contact Support')"
          icon="o_support_agent"
          no-caps
          unelevated
          class="status-btn primary-btn"
          @click="showContactSupport = true"
        />
        <q-btn
          :label="t('Log out')"
          icon="logout"
          no-caps
          outline
          class="status-btn outline-btn"
          @click="logout"
        />
      </q-card-actions>
    </q-card>

    <ContactSupportModal v-model="showContactSupport" />
  </AuthShell>
</template>

<script setup>
import AuthShell from '@/components/auth/AuthShell.vue'
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { api } from '@/boot/axios'
import { useConsumerLanguage } from '@/composables/useConsumerLanguage'
import { useAuth } from '@/composables/useAuth'
import ContactSupportModal from '@/components/modals/ContactSupportModal.vue'

const { t } = useConsumerLanguage()

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

.status-content {
  text-align: center;

  padding: 8px 0;
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
  padding: 24px 0 8px;

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

@media (max-width: 600px) {

  .status-content {
    padding: 0 0 8px;
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
/* Slightly larger text on the auth screens: the shared size tokens go up about 1px here and in this page's own pop-ups. */
.status-page {
  --fs-2xs: 12.5px;
  --fs-xs: 13.5px;
  --fs-sm: 15px;
  --fs-md: 16px;
}

@media (max-width: 600px) {
  .status-page {
    --fs-2xs: 11.5px;
    --fs-xs: 12.5px;
    --fs-sm: 14px;
    --fs-md: 15px;
  }
}</style>
