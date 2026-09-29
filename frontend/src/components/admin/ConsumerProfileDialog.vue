<template>
  <!-- A shopper's account at a glance: who they are, three quick facts, and how to reach them. -->
  <q-dialog v-model="open">
    <q-card v-if="consumer" class="vp-dialog cpd">
      <div class="cpd-scroll">
        <!-- WHO — the photo or initials, and the name with the account's status. -->
        <div class="cpd-head">
          <q-avatar size="72px" class="cpd-avatar">
            <img
              v-if="photo"
              :src="photo"
              :alt="`${consumer.full_name || 'Consumer'} photo`"
            />
            <span v-else class="cpd-initials">{{ initials }}</span>
          </q-avatar>
          <div class="cpd-names">
            <div class="cpd-name-row">
              <h2 class="cpd-name">{{
                consumer.full_name || 'Unnamed consumer'
              }}</h2>
              <span
                class="vp-status cpd-status"
                :class="`vp-status--${accountStatusTone(status)}`"
                >{{ accountStatusLabel(status) }}</span
              >
            </div>
            <div class="cpd-meta">
              <q-icon name="o_shopping_bag" size="16px" />Consumer account
            </div>
          </div>
        </div>

        <!-- FACTS — three tiles, as the store profile shows its counts. -->
        <div class="cpd-facts">
          <div v-for="fact in facts" :key="fact.label" class="cpd-fact">
            <span class="cpd-fact-icon"
              ><q-icon :name="fact.icon" size="18px"
            /></span>
            <span class="cpd-fact-text">
              <span class="cpd-fact-label">{{ fact.label }}</span>
              <span class="cpd-fact-value">{{ fact.value || '—' }}</span>
            </span>
          </div>
        </div>

        <!-- CONTACT -->
        <section class="cpd-card">
          <div class="cpd-label"
            ><q-icon name="o_contact_mail" size="16px" />Contact</div
          >
          <div class="cpd-contact">
            <div v-for="row in contact" :key="row.label" class="cpd-row">
              <span class="cpd-row-label"
                ><q-icon :name="row.icon" size="15px" />{{ row.label }}</span
              >
              <span class="cpd-row-value">{{ row.value || '—' }}</span>
            </div>
          </div>
        </section>
      </div>

      <q-btn
        v-close-popup
        flat
        round
        dense
        icon="o_close"
        class="cpd-close"
        aria-label="Close"
      />

      <div v-if="$slots.actions" class="vp-dialog-actions cpd-actions">
        <slot name="actions" />
      </div>
    </q-card>
  </q-dialog>
</template>

<script setup>
import { computed } from 'vue'
import {
  accountStatusTone,
  accountStatusLabel,
  formatActivity,
  formatShortDate
} from '@/utils/accountStatus'

const props = defineProps({
  consumer: { type: Object, default: null },
  // A deleted account reads as Deleted whatever its last status was.
  deleted: { type: Boolean, default: false }
})

const open = defineModel({ type: Boolean, default: false })

const status = computed(() =>
  props.deleted ? 'deleted' : props.consumer?.account_status
)

const photo = computed(() => {
  const url = props.consumer?.profile_picture_url
  return url && url !== 'null' && String(url).trim() ? url : null
})

// Up to two letters from the name, for people without a photo.
const initials = computed(() =>
  String(props.consumer?.full_name || '?')
    .trim()
    .split(/\s+/)
    .slice(0, 2)
    .map(part => part[0]?.toUpperCase() || '')
    .join('')
)

const facts = computed(() => [
  {
    label: 'Member since',
    value: formatShortDate(props.consumer?.created_at),
    icon: 'o_event'
  },
  {
    label: 'Last active',
    value: formatActivity(props.consumer?.last_activity_at),
    icon: 'o_schedule'
  },
  {
    label: 'User ID',
    value: props.consumer?.user_id ? `#${props.consumer.user_id}` : '',
    icon: 'o_tag'
  }
])

const contact = computed(() => [
  {
    label: 'Email address',
    value: props.consumer?.email,
    icon: 'o_alternate_email'
  },
  { label: 'Phone number', value: props.consumer?.phone_number, icon: 'o_call' }
])
</script>

<style scoped>
.cpd {
  position: relative;

  display: flex;
  flex-direction: column;

  width: 640px;
  max-height: calc(100vh - 48px);
}

/* Quasar caps a dialog at 560px wide, so this one restates its own limit. */
.q-dialog__inner--minimized > .cpd {
  max-width: calc(100vw - 32px);
}

.cpd-scroll {
  display: flex;
  flex-direction: column;
  flex: 1 1 auto;

  gap: 14px;
  overflow-y: auto;

  min-height: 0;
  padding: 24px 24px 20px;
}

.cpd-scroll > * {
  flex-shrink: 0;
}

.cpd-close {
  position: absolute;
  top: 34px;
  right: 34px;
  z-index: 2;

  color: var(--c-muted);
}

/* WHO — a plain white card like the rest, holding the photo and name. */
.cpd-head {
  display: flex;
  align-items: center;

  gap: 16px;
  padding: 20px 56px 20px 20px;

  border: 1px solid var(--c-border);
  border-radius: var(--r-surface);

  background: #ffffff;
}

.cpd-avatar {
  flex-shrink: 0;

  border: 3px solid #ffffff;

  background: var(--c-brand-tint);
  box-shadow: 0 2px 10px rgba(17, 17, 17, 0.12);
}

.cpd-avatar img {
  object-fit: cover;
}

.cpd-initials {
  font-family: 'Poppins', 'Roboto', Arial, sans-serif;
  font-size: 24px;
  font-weight: 700;

  color: var(--c-brand);
}

.cpd-names {
  min-width: 0;
}

.cpd-name-row {
  display: flex;
  align-items: center;
  flex-wrap: wrap;

  gap: 6px 10px;
}

.cpd-name {
  margin: 0;

  overflow-wrap: anywhere;

  font-family: 'Poppins', 'Roboto', Arial, sans-serif;
  font-size: var(--fs-3xl);
  font-weight: 700;
  line-height: 1.2;
  letter-spacing: -0.01em;

  color: var(--c-text);
}

.cpd-meta {
  display: flex;
  align-items: center;

  gap: 6px;
  margin-top: 6px;

  font-size: var(--fs-sm);
  font-weight: 500;

  color: var(--c-text-3);
}

.cpd-meta .q-icon {
  color: var(--c-muted);
}

/* FACTS */
.cpd-facts {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));

  gap: 10px;
}

.cpd-fact {
  display: flex;
  align-items: center;

  gap: 10px;
  min-width: 0;
  padding: 12px 14px;

  border: 1px solid var(--c-border);
  border-radius: var(--r-control);

  background: #ffffff;
}

.cpd-fact-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;

  width: 36px;
  height: 36px;

  border-radius: var(--r-control);

  background: var(--c-brand-tint);

  color: var(--c-brand);
}

.cpd-fact-text {
  display: flex;
  flex-direction: column;

  gap: 2px;
  min-width: 0;
}

.cpd-fact-label {
  font-size: var(--fs-2xs);
  font-weight: 600;
  letter-spacing: 0.05em;
  text-transform: uppercase;

  color: var(--c-muted);
}

.cpd-fact-value {
  overflow: hidden;

  font-size: var(--fs-sm);
  font-weight: 700;
  text-overflow: ellipsis;
  white-space: nowrap;

  color: var(--c-text);
}

/* CONTACT — the store profile's card: a red heading, then the email and phone side by side. */
.cpd-card {
  padding: 16px;

  border: 1px solid var(--c-border);
  border-radius: var(--r-control);

  background: #ffffff;
}

.cpd-label {
  display: flex;
  align-items: center;

  gap: 6px;
  margin-bottom: 12px;

  font-size: var(--fs-2xs);
  font-weight: 700;
  letter-spacing: 0.05em;
  text-transform: uppercase;

  color: var(--c-brand);
}

.cpd-contact {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));

  gap: 12px;
}

.cpd-row {
  display: flex;
  flex-direction: column;

  gap: 3px;
  min-width: 0;
}

.cpd-row + .cpd-row {
  padding-left: 12px;

  border-left: 1px solid var(--c-hairline);
}

.cpd-row-label {
  display: flex;
  align-items: center;

  gap: 5px;

  font-size: var(--fs-2xs);
  font-weight: 600;
  letter-spacing: 0.05em;
  text-transform: uppercase;

  color: var(--c-muted);
}

.cpd-row-value {
  overflow-wrap: anywhere;

  font-size: var(--fs-sm);
  font-weight: 500;
  line-height: 1.5;

  color: var(--c-text);
}

.cpd-actions {
  flex-shrink: 0;

  border-top: 1px solid var(--c-hairline);
}

/* Phones centre the photo and name, and take the facts and contact one at a time. */
@media (max-width: 600px) {
  :global(.q-dialog__inner--minimized:has(.cpd)) {
    padding: 16px;
  }

  .cpd {
    max-height: calc(100vh - 32px);
  }

  .cpd-scroll {
    padding: 16px;
  }

  .cpd-close {
    top: 24px;
    right: 24px;
  }

  .cpd-head {
    flex-direction: column;

    gap: 10px;
    padding: 20px 16px;

    text-align: center;
  }

  .cpd-name-row,
  .cpd-meta {
    justify-content: center;
  }

  .cpd-name {
    font-size: var(--fs-2xl);
  }

  .cpd-facts,
  .cpd-contact {
    grid-template-columns: minmax(0, 1fr);
  }

  .cpd-row + .cpd-row {
    padding-top: 12px;
    padding-left: 0;

    border-top: 1px solid var(--c-hairline);
    border-left: none;
  }

  .cpd-actions {
    padding: 14px 16px 16px;
  }

  .cpd-actions :slotted(.q-btn) {
    flex: 1;

    min-width: 0;
  }
}
</style>
