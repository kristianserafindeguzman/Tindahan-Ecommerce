<template>
  <!-- The vendor dashboard's welcome banner: a date pill, a status pill, the title, and white buttons; brand red unless a page gives it a time of day. -->
  <section class="ah" :class="phase ? `ah--${phase}` : ''">
    <div class="ah-content">
      <div class="ah-eyebrow-row">
        <span class="ah-eyebrow">
          <q-icon :name="eyebrowIcon" size="14px" />
          {{ eyebrow || today }}
        </span>
        <!-- Shown once the count has loaded, so it never flashes a zero first; with a link it opens the page that needs attention. -->
        <component
          :is="statusTo ? 'router-link' : 'span'"
          v-if="status && !loading"
          v-bind="statusTo ? { to: statusTo } : {}"
          class="ah-status"
          :class="{ 'ah-status--link': statusTo }"
        >
          <span class="ah-status-dot" />
          {{ status }}
        </component>
      </div>

      <h1 class="ah-title">{{ title }}</h1>
      <p class="ah-sub">{{ subtitle }}</p>

      <div v-if="actions.length || bell" class="ah-actions">
        <q-btn
          v-for="action in actions"
          :key="action.key"
          unelevated
          no-caps
          :label="action.label"
          class="ah-cta"
          :class="{ 'ah-cta--ghost': action.ghost }"
          :loading="action.loading"
          @click="emit('action', action.key)"
        >
          <q-icon
            v-if="action.icon"
            :name="action.icon"
            size="16px"
            class="q-ml-xs"
          />
        </q-btn>
        <!-- The vendor banner's bell: a square outline button at the end of the row, with the unread count in amber; phones reach notifications from the top bar instead. -->
        <q-btn
          v-if="bell"
          unelevated
          class="ah-cta ah-cta--ghost ah-bell"
          :aria-label="
            unreadCount
              ? `Notifications, ${unreadCount} unread`
              : 'Notifications'
          "
        >
          <q-icon name="o_notifications" />
          <q-badge v-if="unreadCount" floating rounded class="ah-bell-badge">{{
            unreadCount > 99 ? '99+' : unreadCount
          }}</q-badge>
          <q-menu
            class="notif-menu adm-notif-menu"
            anchor="bottom left"
            self="top left"
            :offset="[0, 8]"
          >
            <NotificationsPanel
              :feed="feed"
              :kind-of="adminNotificationKind"
              :link-for="adminNotificationLink"
              view-all-path="/admin/notifications"
              empty-text="New store applications will show up here."
            />
          </q-menu>
        </q-btn>
      </div>
    </div>

    <!-- The page's count in frosted glass on the right; without one, a large faint icon as the vendor banner shows its sun or moon. -->
    <!-- A page's own box on the right, such as the dashboard's clock. -->
    <div v-if="$slots.side" class="ah-side"><slot name="side" /></div>
    <div v-else-if="statLabel" class="ah-stat">
      <span class="ah-stat-label">{{ statLabel }}</span>
      <q-skeleton
        v-if="loading"
        type="rect"
        width="44px"
        height="36px"
        dark
        class="ah-stat-skeleton"
      />
      <span v-else class="ah-stat-value">{{ statValue }}</span>
      <span class="ah-stat-unit">{{ statUnit }}</span>
    </div>
    <q-icon v-else :name="icon" class="ah-art" aria-hidden="true" />
  </section>
</template>

<script setup>
import NotificationsPanel from '@/components/vendor/NotificationsPanel.vue'
import {
  useAdminNotifications,
  adminNotificationKind,
  adminNotificationLink
} from '@/composables/useAdminNotifications'

// The admin's shared notifications, which the layout keeps up to date.
const feed = useAdminNotifications()
const { unreadCount } = feed

defineProps({
  icon: { type: String, required: true },
  // The frosted pill's words; today's date when left out.
  eyebrow: { type: String, default: '' },
  eyebrowIcon: { type: String, default: 'o_admin_panel_settings' },
  // The part of the day — dawn, morning, afternoon, evening or night — for a banner whose colour follows the clock; left out, it stays brand red.
  phase: { type: String, default: '' },
  title: { type: String, required: true },
  // Where the white status pill leads, when it points at something to act on.
  statusTo: { type: String, default: '' },
  subtitle: { type: String, default: '' },
  // A short line in the white pill, such as "3 waiting for review".
  status: { type: String, default: '' },
  // The count on the right, such as "Pending review · 3 · applications".
  statLabel: { type: String, default: '' },
  statValue: { type: [Number, String], default: 0 },
  statUnit: { type: String, default: '' },
  loading: { type: Boolean, default: false },
  // Buttons as { key, label, icon, ghost, loading }; a click emits "action" with the key.
  actions: { type: Array, default: () => [] },
  // Shows the notifications bell at the end of the buttons; the dashboard's banner is the one that has it.
  bell: { type: Boolean, default: false }
})

const emit = defineEmits(['action'])

const today = new Intl.DateTimeFormat('en-US', {
  weekday: 'long',
  month: 'long',
  day: 'numeric',
  year: 'numeric'
}).format(new Date())
</script>

<style scoped>
.ah {
  position: relative;
  overflow: hidden;

  display: flex;
  align-items: center;
  justify-content: space-between;

  gap: 32px;
  padding: 32px 36px 36px;
  margin-bottom: var(--sp-gap);

  border-radius: var(--r-surface);
  box-shadow: 0 4px 16px rgba(101, 16, 18, 0.2);

  background:
    radial-gradient(circle, rgba(255, 255, 255, 0.1) 1.5px, transparent 1.5px) 0
      0 / 26px 26px,
    linear-gradient(
      145deg,
      var(--c-brand) 0%,
      var(--c-brand-deep) 55%,
      var(--c-brand-active) 100%
    );

  color: #ffffff;

  animation: ah-fade-up 0.5s ease both;
}

/* The vendor banner's colours through the day, from a sunrise orange to a night indigo; afternoon keeps the brand red above. */
.ah--dawn {
  background:
    radial-gradient(circle, rgba(255, 255, 255, 0.1) 1.5px, transparent 1.5px) 0
      0 / 26px 26px,
    linear-gradient(145deg, #d2612e 0%, #c0392f 50%, #942133 100%);
}

.ah--morning {
  background:
    radial-gradient(circle, rgba(255, 255, 255, 0.1) 1.5px, transparent 1.5px) 0
      0 / 26px 26px,
    linear-gradient(145deg, #d24d2a 0%, #bd2427 55%, #9c171b 100%);
}

.ah--evening {
  box-shadow: 0 4px 16px rgba(74, 25, 66, 0.28);

  background:
    radial-gradient(circle, rgba(255, 255, 255, 0.1) 1.5px, transparent 1.5px) 0
      0 / 26px 26px,
    linear-gradient(145deg, #b3304a 0%, #82204f 55%, #4a1942 100%);
}

.ah--night {
  box-shadow: 0 4px 16px rgba(20, 15, 43, 0.35);

  background:
    radial-gradient(circle, rgba(255, 255, 255, 0.12) 1.5px, transparent 1.5px)
      0 0 / 26px 26px,
    linear-gradient(145deg, #3b2a6b 0%, #26194d 55%, #140f2b 100%);
}

@keyframes ah-fade-up {
  from {
    opacity: 0;
    transform: translateY(14px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.ah-art {
  position: absolute;
  top: 50%;
  right: 48px;
  z-index: 0;

  font-size: 170px;

  color: rgba(255, 255, 255, 0.14);

  transform: translateY(-50%);
  pointer-events: none;
}

.ah-content {
  position: relative;
  z-index: 1;

  min-width: 0;
}

.ah-eyebrow-row {
  display: flex;
  align-items: center;
  flex-wrap: wrap;

  gap: 8px;
  margin-bottom: 14px;
}

.ah-eyebrow {
  display: inline-flex;
  align-items: center;

  gap: 6px;
  padding: 5px 12px;

  border: 1px solid rgba(255, 255, 255, 0.28);
  border-radius: var(--r-pill);

  background: rgba(255, 255, 255, 0.12);
  color: #ffffff;

  font-size: var(--fs-2xs);
  font-weight: 600;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  white-space: nowrap;
}

.ah-status {
  display: inline-flex;
  align-items: center;

  gap: 8px;
  height: 28px;
  padding: 0 12px;

  border-radius: var(--r-pill);

  background: #ffffff;
  color: var(--c-text-2);

  font-size: var(--fs-xs);
  font-weight: 700;
  white-space: nowrap;

  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.18);
}

.ah-status-dot {
  width: 8px;
  height: 8px;

  border-radius: 50%;

  background: var(--c-brand);
}

.ah-title {
  margin: 0 0 8px;

  font-family: 'Poppins', 'Roboto', Arial, sans-serif;
  font-size: var(--fs-hero);
  font-weight: 800;
  line-height: 1.2;
  letter-spacing: -0.01em;

  color: #ffffff;
}

.ah-sub {
  max-width: 46ch;
  margin: 0;

  font-size: var(--fs-md);
  line-height: 1.5;

  color: rgba(255, 255, 255, 0.86);
}

.ah-actions {
  display: flex;
  flex-wrap: wrap;

  gap: 12px;
  margin-top: 22px;
}

.ah-cta {
  height: 42px;
  padding: 0 20px;

  border-radius: var(--r-control);

  background: #ffffff;
  color: var(--c-brand);

  font-size: var(--fs-sm);
  font-weight: 700;

  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.18);

  transition:
    background-color 0.15s,
    box-shadow 0.2s,
    transform 0.2s;
}

.ah-cta:hover {
  background: var(--c-hairline);

  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.25);
  transform: translateY(-1px);
}

.ah-cta:focus-visible {
  outline: none;
  box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.5);
}

/* The secondary path, so it stays an outline and doesn't compete with the white button. */
.ah-cta--ghost {
  border: 1px solid rgba(255, 255, 255, 0.55);

  background: transparent;
  color: #ffffff;

  box-shadow: none;
}

.ah-cta--ghost:hover {
  border-color: #ffffff;
  background: rgba(255, 255, 255, 0.14);
  box-shadow: none;
}

/* The count, in the same frosted glass as the date pill. */
.ah-stat {
  position: relative;
  z-index: 1;

  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;

  gap: 8px;
  min-width: 170px;
  padding: 18px 28px;

  border: 1px solid rgba(255, 255, 255, 0.28);
  border-radius: var(--r-surface);

  background: rgba(255, 255, 255, 0.12);

  text-align: center;
}

.ah-stat-label,
.ah-stat-unit {
  font-size: var(--fs-2xs);
  font-weight: 600;
  line-height: 1;
  letter-spacing: 0.04em;
  text-transform: uppercase;

  color: rgba(255, 255, 255, 0.8);
}

.ah-stat-value {
  font-family: 'Poppins', 'Roboto', Arial, sans-serif;
  font-size: 40px;
  font-weight: 800;
  line-height: 1;
}

.ah-stat-skeleton {
  border-radius: 6px;
}

.ah-status--link {
  text-decoration: none;

  cursor: pointer;

  transition:
    box-shadow 0.2s,
    transform 0.2s;
}

.ah-status--link:hover {
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.25);
  transform: translateY(-1px);
}

.ah-status--link:focus-visible {
  outline: none;
  box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.5);
}

.ah-side {
  position: relative;
  z-index: 1;

  flex-shrink: 0;
}

/* BELL — the vendor banner's square outline button, with the unread count in amber. */
.ah-bell {
  width: 42px;
  min-width: 42px;
  padding: 0;
}

.ah-bell :deep(.q-icon) {
  font-size: 20px;
}

.ah-bell-badge {
  background: #ffb300 !important;
  color: #3b1d00 !important;

  font-weight: 800;
}

/* Below the md breakpoint the phone top bar already has a bell, so the banner doesn't repeat it. */
@media (max-width: 1023px) {
  .ah-bell {
    display: none;
  }
}

@media (prefers-reduced-motion: reduce) {
  .ah {
    animation: none;
  }
}

@media (max-width: 1023px) {
  .ah {
    gap: 24px;
    padding: 26px 28px 30px;
  }

  .ah-art {
    right: 28px;

    font-size: 130px;
  }
}

@media (max-width: 600px) {
  .ah {
    flex-direction: column;
    align-items: stretch;

    gap: 16px;
    padding: 20px 18px;
  }

  /* Phones put the count under the title as one slim row. */
  .ah-stat {
    flex-direction: row;
    justify-content: flex-start;

    gap: 8px;
    min-width: 0;
    padding: 12px 16px;
  }

  .ah-stat-label {
    margin-right: auto;
  }

  .ah-stat-value {
    font-size: 24px;
  }

  .ah-art {
    display: none;
  }

  .ah-status {
    box-shadow: none;
  }

  .ah-eyebrow-row {
    margin-bottom: 10px;
  }

  .ah-title {
    font-size: var(--fs-4xl);
  }

  .ah-sub {
    font-size: var(--fs-sm);
  }

  .ah-actions {
    gap: 10px;
    margin-top: 16px;
  }

  .ah-cta {
    flex: 1;

    padding: 0 12px;
  }

  .ah-cta :deep(.q-btn__content) {
    flex-wrap: nowrap;
    white-space: nowrap;
  }
}
</style>
