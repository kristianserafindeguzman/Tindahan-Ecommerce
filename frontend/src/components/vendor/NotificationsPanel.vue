<template>
  <div class="notif-panel">
    <div class="notif-head">
      <div class="notif-title">Notifications</div>
      <q-btn flat dense no-caps label="Mark all as read" color="primary" class="notif-mark-all" :disable="unreadCount === 0" @click="markAllAsRead" />
    </div>

    <div class="notif-list">
      <div v-if="!notifications.length" class="notif-empty">
        <div class="notif-empty-icon"><q-icon name="o_notifications_none" size="24px" /></div>
        <div class="notif-empty-title">You're all caught up</div>
        <div class="notif-empty-text">New orders and cancellations will show up here.</div>
      </div>

      <!-- Only the latest five; tapping one marks it read and, when it belongs to an order, opens that order. -->
      <button
        v-for="notif in latest"
        :key="notif.notification_id"
        v-close-popup
        type="button"
        class="notif-item"
        :class="{ 'notif-item--unread': !notif.is_read }"
        @click="openNotification(notif)"
      >
        <span class="notif-icon" :class="`vp-tone--${notificationKind(notif).tone}`">
          <q-icon :name="notificationKind(notif).icon" size="18px" />
        </span>
        <span class="notif-body">
          <span class="notif-item-title">{{ notif.title }}</span>
          <span class="notif-item-message">{{ notif.message }}</span>
          <span class="notif-item-time">{{ timeAgo(notif.created_at) }}</span>
        </span>
        <span v-if="!notif.is_read" class="notif-dot" aria-label="Unread" />
      </button>
    </div>

    <!-- Always there, so the full notifications page is one tap away even when the list is empty. -->
    <div class="notif-foot">
      <q-btn
        v-close-popup
        flat
        no-caps
        color="primary"
        icon-right="o_chevron_right"
        :label="notifications.length > LIMIT ? `View all ${notifications.length} notifications` : 'View all notifications'"
        class="notif-view-all"
        @click="router.push('/vendor/notifications')"
      />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useVendorNotifications, notificationKind } from '@/composables/useVendorNotifications'

const LIMIT = 5

const router = useRouter()
const { notifications, unreadCount, markAsRead, markAllAsRead, timeAgo } = useVendorNotifications()

const latest = computed(() => [...notifications.value].sort((a, b) => new Date(b.created_at) - new Date(a.created_at)).slice(0, LIMIT))

const openNotification = (notif) => {
  markAsRead(notif)
  if (notif.order_id) router.push(`/vendor/orders/${notif.order_id}`)
}
</script>

<style scoped>
/* Shrinks to the menu's width on phones, where the bell sits near the edge and leaves less than 340px beside it. */
.notif-panel {
  width: 340px;
  max-width: 100%;

  font-family: 'Roboto', Arial, sans-serif;
}

.notif-head {
  display: flex;
  align-items: center;
  justify-content: space-between;

  padding: 12px 12px 12px 16px;

  border-bottom: 1px solid var(--c-border);
}

.notif-title {
  font-size: var(--fs-md);
  font-weight: 700;

  color: var(--c-text);
}

.notif-mark-all {
  font-size: var(--fs-xs);
  font-weight: 600;
}

.notif-list {
  max-height: min(60vh, 440px);
  overflow-y: auto;
}

.notif-empty {
  display: flex;
  flex-direction: column;
  align-items: center;

  gap: 4px;
  padding: 32px 20px;

  text-align: center;
}

.notif-empty-icon {
  display: flex;
  align-items: center;
  justify-content: center;

  width: 48px;
  height: 48px;
  margin-bottom: 6px;

  border-radius: var(--r-surface);

  background: linear-gradient(145deg, var(--c-brand-tint) 0%, var(--c-brand-tint-2) 100%);
  color: var(--c-brand);
}

.notif-empty-title {
  font-size: var(--fs-sm);
  font-weight: 700;

  color: var(--c-text);
}

.notif-empty-text {
  font-size: var(--fs-xs);

  color: var(--c-muted);
}

.notif-item {
  display: flex;
  align-items: flex-start;

  gap: 12px;
  width: 100%;
  padding: 12px 16px;

  border: none;
  border-bottom: 1px solid var(--c-hairline);

  background: #ffffff;

  font-family: inherit;
  text-align: left;

  cursor: pointer;

  transition: background-color 0.15s;
}

.notif-item:last-child {
  border-bottom: none;
}

.notif-item:hover {
  background: var(--c-surface-2);
}

.notif-item:focus-visible {
  outline: 2px solid var(--c-brand);
  outline-offset: -2px;
}

.notif-item--unread {
  background: var(--c-brand-tint);
}

.notif-item--unread:hover {
  background: var(--c-brand-tint-2);
}

.notif-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;

  width: 36px;
  height: 36px;

  border-radius: var(--r-control);
}

.notif-body {
  display: flex;
  flex-direction: column;

  flex: 1;
  min-width: 0;
  gap: 2px;
}

.notif-item-title {
  font-size: var(--fs-sm);
  font-weight: 700;

  color: var(--c-text);
}

.notif-item-message {
  font-size: var(--fs-xs);
  line-height: 1.4;

  color: var(--c-text-3);
}

.notif-item-time {
  margin-top: 2px;

  font-size: var(--fs-2xs);
  font-weight: 600;

  color: var(--c-muted);
}

.notif-item--unread .notif-item-time {
  color: var(--c-brand);
}

.notif-dot {
  flex-shrink: 0;

  width: 8px;
  height: 8px;
  margin-top: 6px;

  border-radius: 50%;

  background: var(--c-brand);
}

.notif-foot {
  padding: 6px;

  border-top: 1px solid var(--c-border);
}

.notif-view-all {
  width: 100%;
  height: 38px;

  border-radius: var(--r-control);

  font-size: var(--fs-sm);
  font-weight: 600;
}
</style>
