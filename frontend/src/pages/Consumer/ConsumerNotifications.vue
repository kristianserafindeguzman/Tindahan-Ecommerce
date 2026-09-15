<template>
  <q-page class="storefront-page">
    <SiteHeader />

    <div class="page-content">
      <div class="page-header-row">
        <div>
          <h1 class="page-title">{{ t('Notifications') }}</h1>
          <p class="page-subtitle">{{ t('Order updates and store activity.') }}</p>
        </div>

        <q-btn
          v-if="unreadCount"
          unelevated
          no-caps
          :label="t('Mark all as read ({count})', { count: unreadCount })"
          class="mark-all-btn"
          :loading="markingAll"
          @click="markAllAsRead"
        />
      </div>

      <!-- Same skeleton shape as the rows below, so nothing shifts when they arrive. -->
      <div v-if="loading" class="notif-list">
        <div v-for="n in 5" :key="n" class="notif-row">
          <q-skeleton type="QAvatar" size="56px" class="notif-skeleton-icon" />
          <div class="notif-row-body">
            <q-skeleton type="text" class="notif-skeleton-title" />
            <q-skeleton type="text" class="notif-skeleton-text" />
          </div>
        </div>
      </div>

      <div v-else-if="!notifications.length" class="notif-empty">
        <q-icon name="o_notifications_none" size="40px" class="notif-empty-icon" />
        <p class="notif-empty-text">{{ t('You have no notifications yet.') }}</p>
        <q-btn unelevated no-caps :label="t('Browse Products')" class="browse-btn" @click="router.push('/consumer/home')" />
      </div>

      <div v-else class="notif-list">
        <!-- A row is only a button when it has an order to open, so informational notifications render as a plain div. -->
        <component
          :is="notif.order_id ? 'button' : 'div'"
          v-for="notif in notifications"
          :key="notif.notification_id"
          :type="notif.order_id ? 'button' : undefined"
          class="notif-row"
          :class="{ 'notif-row--unread': !notif.is_read, 'notif-row--link': !!notif.order_id }"
          @click="notif.order_id ? openOrder(notif) : markRead(notif)"
        >
          <span class="notif-icon" :class="`notif-icon--${toneOf(notif)}`">
            <q-icon :name="iconOf(notif)" size="26px" />
          </span>

          <span class="notif-row-body">
            <span class="notif-row-head">
              <span class="notif-row-title">{{ notif.title }}</span>
              <span v-if="!notif.is_read" class="notif-dot" :aria-label="t('Unread')"></span>
            </span>
            <span class="notif-row-text">{{ notif.message }}</span>
            <span class="notif-row-time">{{ relativeTime(notif.created_at) }}</span>
          </span>

          <q-icon v-if="notif.order_id" name="o_chevron_right" size="20px" class="notif-row-chevron" />
        </component>
      </div>
    </div>

    <SiteFooter />
  </q-page>
</template>

<script setup>
import { useConsumerLanguage } from '@/composables/useConsumerLanguage'

import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import SiteHeader from '@/components/consumer/SiteHeader.vue'
import SiteFooter from '@/components/consumer/SiteFooter.vue'
import { api } from '@/boot/axios'
import { notificationPresentation, notificationTime } from '@/utils/notificationPresentation'
import {
  NOTIFICATION_READ_STATE_EVENT,
  applyNotificationReadState,
  publishNotificationReadState
} from '@/utils/notificationSync'

const { t, locale } = useConsumerLanguage()

const router = useRouter()
const $q = useQuasar()

const notifications = ref([])
const loading = ref(true)
const markingAll = ref(false)

const syncNotificationReadState = event => {
  applyNotificationReadState(notifications.value, event.detail)
}

const unreadCount = computed(() => notifications.value.filter((n) => !n.is_read).length)

const toneOf = notif => notificationPresentation(notif).tone
const iconOf = notif => notificationPresentation(notif).icon
const relativeTime = value => notificationTime(value, t, locale.value)

const fetchNotifications = async () => {
  loading.value = true
  try {
    const { data } = await api.get('/consumer/notifications')
    notifications.value = data || []
  } catch (error) {
    console.error('Failed to load notifications', error)
    notifications.value = []
  } finally {
    loading.value = false
  }
}

// Mark locally for an immediate row update, then restore it if persistence fails.
const markRead = async (notif) => {
  if (notif.is_read) return
  notif.is_read = true
  try {
    await api.patch(`/consumer/notifications/${notif.notification_id}/read`)
    publishNotificationReadState({
      notificationId: notif.notification_id,
      isRead: true
    })
  } catch (error) {
    notif.is_read = false
    console.error('Failed to mark notification as read', error)
    $q.notify({ type: 'negative', message: t('Could not mark notifications as read') })
  }
}

const openOrder = async (notif) => {
  await markRead(notif)
  localStorage.setItem('consumer_selected_order_id', notif.order_id)
  router.push('/consumer/orders/details')
}

const markAllAsRead = async () => {
  markingAll.value = true
  const previous = notifications.value.map((n) => n.is_read)
  notifications.value.forEach((n) => { n.is_read = true })
  try {
    await api.post('/consumer/notifications/read-all')
    publishNotificationReadState({ all: true, isRead: true })
  } catch {
    notifications.value.forEach((n, i) => { n.is_read = previous[i] })
    $q.notify({ type: 'negative', message: t('Could not mark notifications as read') })
  } finally {
    markingAll.value = false
  }
}

onMounted(() => {
  window.addEventListener(NOTIFICATION_READ_STATE_EVENT, syncNotificationReadState)
  fetchNotifications()
})

onBeforeUnmount(() => {
  window.removeEventListener(NOTIFICATION_READ_STATE_EVENT, syncNotificationReadState)
})
</script>

<style scoped>
.storefront-page {
  min-height: 100vh;

  display: flex;
  flex-direction: column;

  background: #ffffff;
  font-family: 'Roboto', Arial, sans-serif;
}

.page-content {
  flex: 1;
  width: 100%;
  max-width: 1000px;
  box-sizing: border-box;

  margin: 0 auto;
  padding: 24px;
}

/* Title block and mark-all action share a row, with the action dropping below the text on phones. */
.page-header-row {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;

  gap: 16px;
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

.mark-all-btn {
  flex-shrink: 0;
  height: 40px;
  padding: 0 16px;

  border: 1px solid var(--c-border);
  border-radius: var(--r-sm);

  background: #ffffff;
  color: var(--c-brand);

  font-size: var(--fs-xs);
  font-weight: 600;
}

.mark-all-btn:hover {
  border-color: var(--c-brand-tint-3);
  background: var(--c-brand-tint);
}

/* ROWS */
.notif-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.notif-row {
  display: flex;
  align-items: flex-start;
  gap: 14px;

  width: 100%;
  padding: 16px 18px;

  border: 1px solid var(--c-border);
  border-radius: var(--r-lg);

  background: #ffffff;
  box-shadow: var(--sh-card);

  font-family: inherit;
  text-align: left;

  transition: border-color 0.15s, box-shadow 0.2s, transform 0.2s;
}

.notif-row--link {
  cursor: pointer;
}

.notif-row--link:hover {
  border-color: var(--c-brand-tint-3);
  box-shadow: var(--sh-card-hover);
  transform: translateY(-1px);
}

.notif-row--link:focus-visible {
  outline: 2px solid var(--c-brand);
  outline-offset: 2px;
}

.notif-row--unread {
  border-color: var(--c-brand-tint-3);
  background: var(--c-brand-tint);
}

.notif-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;

  width: 56px;
  height: 56px;

  border-radius: var(--r-xl);

  background: var(--tone-bg);
  color: var(--tone);
}

/* Each stage wears its status pill's colours: blue placed, amber preparing, purple ready, green picked up, red cancelled; anything else stays brand red. */
.notif-icon--placed {
  --tone: var(--st-placed);
  --tone-bg: var(--st-placed-bg);
}

.notif-icon--preparing {
  --tone: var(--st-preparing);
  --tone-bg: var(--st-preparing-bg);
}

.notif-icon--ready {
  --tone: var(--st-ready);
  --tone-bg: var(--st-ready-bg);
}

.notif-icon--done {
  --tone: var(--st-done);
  --tone-bg: var(--st-done-bg);
}

.notif-icon--cancelled {
  --tone: var(--st-cancelled);
  --tone-bg: var(--st-cancelled-bg);
}

.notif-icon--brand {
  --tone: var(--c-brand);
  --tone-bg: var(--c-brand-tint);
}

.notif-row-body {
  display: flex;
  flex-direction: column;
  gap: 3px;

  min-width: 0;
  flex: 1;
}

.notif-row-head {
  display: flex;
  align-items: center;
  gap: 8px;
}

.notif-row-title {
  font-size: var(--fs-md);
  font-weight: 600;
  line-height: 1.3;

  color: var(--c-text);
}

.notif-row--unread .notif-row-title {
  font-weight: 700;
}

.notif-dot {
  flex-shrink: 0;

  width: 7px;
  height: 7px;

  border-radius: var(--r-pill);
  background: var(--c-brand);
}

.notif-row-text {
  font-size: var(--fs-sm);
  line-height: 1.4;

  color: var(--c-text-3);
}

.notif-row-time {
  margin-top: 2px;

  font-size: var(--fs-xs);
  color: var(--c-muted);
}

.notif-row-chevron {
  flex-shrink: 0;
  align-self: center;

  color: var(--c-border-strong);
}

/* SKELETON — mirrors the row above so the list does not jump on load. */
.notif-skeleton-icon {
  flex-shrink: 0;
  border-radius: var(--r-xl);
}

.notif-skeleton-title {
  width: 34%;
  height: calc(var(--fs-md) * 1.3);
}

.notif-skeleton-text {
  width: 68%;
  height: calc(var(--fs-sm) * 1.4);
}

/* EMPTY */
.notif-empty {
  display: flex;
  flex-direction: column;
  align-items: center;

  padding: 60px 24px;
  text-align: center;
}

.notif-empty-icon {
  margin-bottom: 10px;
  color: var(--c-border);
}

.notif-empty-text {
  margin: 0 0 20px;

  font-size: var(--fs-md);
  color: var(--c-muted);
}

.browse-btn {
  height: 48px;
  padding: 0 24px;

  border-radius: var(--r-sm);

  background: var(--c-brand);
  color: #ffffff;

  font-size: var(--fs-sm);
  font-weight: 500;

  box-shadow: var(--sh-brand);

  transition: background-color 0.15s, box-shadow 0.2s, transform 0.2s;
}

.browse-btn:hover {
  background: var(--c-brand-hover);
  box-shadow: var(--sh-brand-hover);
  transform: translateY(-1px);
}

@media (max-width: 600px) {
  .page-content {
    padding: 16px;
  }

  .page-header-row {
    flex-direction: column;
    align-items: stretch;
  }

  .notif-row {
    gap: 12px;
    padding: 14px;
  }

  .notif-icon {
    width: 48px;
    height: 48px;
  }

  .notif-skeleton-icon {
    width: 48px !important;
    height: 48px !important;
  }
}
</style>
