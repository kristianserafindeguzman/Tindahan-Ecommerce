<template>
  <q-page class="storefront-page">
    <SiteHeader />
    <div class="page-content">
      <div class="page-header-row"
        ><div
          ><h1 class="page-title">{{ t('Notifications') }}</h1
          ><p class="page-subtitle">{{
            t('Order updates and store activity.')
          }}</p></div
        ><q-btn
          outline
          no-caps
          icon="o_done_all"
          :label="t('Mark all as read')"
          class="mark-all-btn"
          :disable="!unreadCount"
          :loading="markingAll"
          @click="markAllAsRead"
      /></div>
      <div class="vp-card">
        <div class="vp-toolbar"
          ><div
            class="vp-chips"
            role="tablist"
            :aria-label="t('Filter notifications')"
            ><button
              v-for="filter in filters"
              :key="filter.key"
              type="button"
              role="tab"
              class="vp-chip"
              :class="{ 'vp-chip--active': active === filter.key }"
              :aria-selected="active === filter.key"
              @click="active = filter.key"
              >{{ filter.label
              }}<span class="vp-chip-count">{{
                countFor(filter.key)
              }}</span></button
            ></div
          ></div
        >
        <div v-if="loading" class="nt-skeletons"
          ><div v-for="n in 6" :key="n" class="nt-skeleton"
            ><q-skeleton
              type="rect"
              width="40px"
              height="40px"
              class="nt-skeleton-icon" /><div class="nt-skeleton-lines"
              ><q-skeleton type="text" width="35%" /><q-skeleton
                type="text"
                width="70%" /></div
            ><q-skeleton type="text" width="60px" /></div
        ></div>
        <div v-else-if="!filtered.length" class="vp-empty"
          ><div class="vp-empty-icon"
            ><q-icon name="o_notifications_none" size="24px" /></div
          ><div class="vp-empty-title">{{
            active === 'unread'
              ? t('No unread notifications')
              : notifications.length
                ? t('No notifications in this filter')
                : t('You have no notifications yet.')
          }}</div
          ><q-btn
            v-if="!notifications.length"
            unelevated
            no-caps
            :label="t('Browse Products')"
            class="browse-btn q-mt-md"
            @click="router.push('/consumer/home')"
        /></div>
        <template v-else>
          <div class="nt-groups"
            ><section v-for="group in groups" :key="group.key" class="nt-group"
              ><h2 class="nt-group-label">{{ group.label }}</h2
              ><div class="nt-list"
                ><button
                  v-for="item in group.items"
                  :key="item.notif.notification_id"
                  type="button"
                  class="nt-item"
                  :class="{ 'nt-item--unread': !item.notif.is_read }"
                  @click="
                    item.notif.order_id
                      ? openOrder(item.notif)
                      : markRead(item.notif)
                  "
                  ><span
                    class="nt-icon"
                    :class="'notif-tone--' + item.kind.tone"
                    ><q-icon :name="item.kind.icon" size="20px" /></span
                  ><span class="nt-body"
                    ><span class="nt-head"
                      ><span class="nt-title">{{ item.text.title }}</span
                      ><span v-if="!item.notif.is_read" class="nt-new">{{
                        t('New')
                      }}</span
                      ><span class="nt-time"
                        >{{ formatClock(item.notif.created_at) }} &middot;
                        {{ relativeTime(item.notif.created_at) }}</span
                      ></span
                    ><span class="nt-message">{{
                      item.text.message
                    }}</span></span
                  ><q-icon
                    v-if="item.notif.order_id"
                    name="o_chevron_right"
                    size="20px"
                    class="nt-arrow" /></button></div></section
          ></div>
          <div v-if="pageCount > 1" class="vp-pager"
            ><span>{{
              t('Showing {start}–{end} of {total}', {
                start: rangeStart,
                end: rangeEnd,
                total: filtered.length
              })
            }}</span
            ><div class="vp-pager-btns"
              ><q-btn
                outline
                no-caps
                color="primary"
                icon="o_chevron_left"
                class="vp-pill-btn"
                :aria-label="t('Previous page')"
                :disable="page === 1"
                @click="page--" /><q-btn
                outline
                no-caps
                color="primary"
                icon="o_chevron_right"
                class="vp-pill-btn"
                :aria-label="t('Next page')"
                :disable="page === pageCount"
                @click="page++" /></div
          ></div>
        </template>
      </div>
    </div>
    <SiteFooter />
  </q-page>
</template>

<script setup>
import { useConsumerLanguage } from '@/composables/useConsumerLanguage'

import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import SiteHeader from '@/components/consumer/SiteHeader.vue'
import SiteFooter from '@/components/consumer/SiteFooter.vue'
import '@/css/vendor-pages.scss'
import { api } from '@/boot/axios'
import {
  notificationPresentation,
  notificationTime,
  consumerNotificationText
} from '@/utils/notificationPresentation'
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

const unreadCount = computed(
  () => notifications.value.filter(n => !n.is_read).length
)

const relativeTime = value => notificationTime(value, t, locale.value)

const active = ref('all')
const page = ref(1)
const PAGE_SIZE = 20
const filters = computed(() => [
  { key: 'all', label: t('All') },
  { key: 'unread', label: t('Unread') },
  { key: 'order', label: t('Orders') },
  { key: 'cancelled', label: t('Cancellations') }
])
const matches = (notif, key) =>
  key === 'all' ||
  (key === 'unread'
    ? !notif.is_read
    : key === 'cancelled'
      ? notificationPresentation(notif).tone === 'cancelled'
      : notificationPresentation(notif).tone !== 'cancelled' &&
        !!notif.order_id)
const countFor = key =>
  notifications.value.filter(notif => matches(notif, key)).length
const filtered = computed(() =>
  [...notifications.value]
    .sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
    .filter(notif => matches(notif, active.value))
)
const pageCount = computed(() =>
  Math.max(1, Math.ceil(filtered.value.length / PAGE_SIZE))
)
const paged = computed(() =>
  filtered.value.slice((page.value - 1) * PAGE_SIZE, page.value * PAGE_SIZE)
)
const rangeStart = computed(() => (page.value - 1) * PAGE_SIZE + 1)
const rangeEnd = computed(() =>
  Math.min(page.value * PAGE_SIZE, filtered.value.length)
)
watch(active, () => {
  page.value = 1
})
watch(pageCount, count => {
  if (page.value > count) page.value = count
})
const dayKey = value => {
  const date = new Date(value)
  return Number.isNaN(date.getTime())
    ? 'earlier'
    : new Intl.DateTimeFormat('en-CA', {
        timeZone: 'Asia/Manila',
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
      }).format(date)
}
const dayLabel = key => {
  if (key === 'earlier') return t('Earlier')
  const today = dayKey(new Date())
  const yesterday = new Date(`${today}T12:00:00+08:00`)
  yesterday.setUTCDate(yesterday.getUTCDate() - 1)
  if (key === today) return t('Today')
  if (key === dayKey(yesterday)) return t('Yesterday')
  return new Date(`${key}T00:00:00+08:00`).toLocaleDateString(locale.value, {
    timeZone: 'Asia/Manila',
    weekday: 'short',
    month: 'short',
    day: 'numeric',
    year: 'numeric'
  })
}
const groups = computed(() =>
  paged.value.reduce((list, notif) => {
    const key = dayKey(notif.created_at)
    const item = {
      notif,
      kind: notificationPresentation(notif),
      text: consumerNotificationText(notif, t)
    }
    const last = list[list.length - 1]
    if (last?.key === key) last.items.push(item)
    else list.push({ key, label: dayLabel(key), items: [item] })
    return list
  }, [])
)
const formatClock = value => {
  const date = new Date(value)
  return Number.isNaN(date.getTime())
    ? ''
    : date.toLocaleTimeString(locale.value, {
        timeZone: 'Asia/Manila',
        hour: 'numeric',
        minute: '2-digit'
      })
}

const fetchNotifications = async () => {
  loading.value = true
  try {
    const { data } = await api.get('/consumer/notifications')
    notifications.value = Array.isArray(data) ? data : data?.data || []
  } catch (error) {
    console.error('Failed to load notifications', error)
    notifications.value = []
  } finally {
    loading.value = false
  }
}

// Mark locally for an immediate row update, then restore it if persistence fails.
const markRead = async notif => {
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
    $q.notify({
      type: 'negative',
      message: t('Could not mark notifications as read')
    })
  }
}

const openOrder = async notif => {
  await markRead(notif)
  localStorage.setItem('consumer_selected_order_id', notif.order_id)
  router.push('/consumer/orders/details')
}

const markAllAsRead = async () => {
  markingAll.value = true
  const previous = notifications.value.map(n => n.is_read)
  notifications.value.forEach(n => {
    n.is_read = true
  })
  try {
    await api.post('/consumer/notifications/read-all')
    publishNotificationReadState({ all: true, isRead: true })
  } catch {
    notifications.value.forEach((n, i) => {
      n.is_read = previous[i]
    })
    $q.notify({
      type: 'negative',
      message: t('Could not mark notifications as read')
    })
  } finally {
    markingAll.value = false
  }
}

onMounted(() => {
  window.addEventListener(
    NOTIFICATION_READ_STATE_EVENT,
    syncNotificationReadState
  )
  fetchNotifications()
})

onBeforeUnmount(() => {
  window.removeEventListener(
    NOTIFICATION_READ_STATE_EVENT,
    syncNotificationReadState
  )
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

.nt-skeletons {
  display: flex;
  flex-direction: column;

  padding: 8px 20px;
}

.nt-skeleton {
  display: flex;
  align-items: center;

  gap: 14px;
  padding: 12px 0;

  border-bottom: 1px solid var(--c-hairline);
}

.nt-skeleton:last-child {
  border-bottom: none;
}

.nt-skeleton-icon {
  flex-shrink: 0;

  border-radius: var(--r-control);
}

.nt-skeleton-lines {
  flex: 1;
}

/* Each day is a quiet heading over its own stack of cards, with room between days and between cards. */
.nt-groups {
  display: flex;
  flex-direction: column;

  gap: 24px;
  padding: 20px;
}

.nt-group-label {
  margin: 0 0 10px;
  padding: 0 2px;

  font-size: var(--fs-2xs);
  font-weight: 700;
  line-height: 1.4;
  letter-spacing: 0.06em;
  text-transform: uppercase;

  color: var(--c-muted);
}

.nt-list {
  display: flex;
  flex-direction: column;

  gap: 10px;
}

.nt-item {
  display: flex;
  align-items: flex-start;

  gap: 16px;
  width: 100%;
  padding: 16px 18px;

  border: 1px solid var(--c-border);
  border-radius: var(--r-control);

  background: #ffffff;

  font-family: inherit;
  text-align: left;

  cursor: pointer;

  transition:
    background-color 0.15s,
    border-color 0.15s,
    box-shadow 0.15s;
}

.nt-item:hover {
  border-color: var(--c-border-strong);

  box-shadow: 0 4px 14px rgba(15, 23, 42, 0.06);
}

.nt-item:focus-visible {
  outline: 2px solid var(--c-brand);
  outline-offset: 2px;
}

/* Unread ones carry a brand bar on the left, a soft tint and a "New" tag. */
.nt-item--unread {
  border-color: var(--c-brand-tint-2);

  background: var(--c-brand-tint);

  box-shadow: inset 4px 0 0 var(--c-brand);
}

.nt-item--unread:hover {
  border-color: var(--c-brand-tint-2);

  box-shadow:
    inset 4px 0 0 var(--c-brand),
    0 4px 14px rgba(15, 23, 42, 0.06);
}

.nt-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;

  width: 42px;
  height: 42px;

  border-radius: var(--r-control);
}

.nt-body {
  display: flex;
  flex-direction: column;

  flex: 1;
  min-width: 0;
  gap: 6px;
}

.nt-head {
  display: flex;
  align-items: center;
  flex-wrap: wrap;

  gap: 6px 10px;
}

.nt-title {
  min-width: 0;

  font-size: var(--fs-md);
  font-weight: 700;
  line-height: 1.3;

  color: var(--c-text);
}

.nt-new {
  padding: 2px 9px;

  border-radius: var(--r-pill);

  background: var(--c-brand);

  font-size: var(--fs-2xs);
  font-weight: 700;
  line-height: 1.4;

  color: #ffffff;
}

.nt-time {
  margin-left: auto;

  font-size: var(--fs-xs);
  font-weight: 600;
  white-space: nowrap;

  color: var(--c-muted);
}

.nt-message {
  font-size: var(--fs-sm);
  line-height: 1.5;

  color: var(--c-text-3);
}

.nt-meta {
  display: flex;
  flex-wrap: wrap;

  gap: 8px;
  margin-top: 2px;
}

.nt-chip {
  display: inline-flex;
  align-items: center;

  gap: 6px;
  max-width: 100%;
  padding: 5px 10px;

  border: 1px solid var(--c-border);
  border-radius: var(--r-pill);

  background: var(--c-surface);

  font-size: var(--fs-xs);
  font-weight: 600;
  line-height: 1.35;

  color: var(--c-text-2);
}

.nt-chip--reason {
  border-color: transparent;

  background: var(--c-danger-tint);

  color: var(--c-danger);
}

.nt-arrow {
  flex-shrink: 0;
  align-self: center;

  color: var(--c-border-strong);
}

@media (max-width: 600px) {
  .nt-groups {
    gap: 20px;
    padding: 16px 12px;
  }

  .nt-item {
    gap: 12px;
    padding: 14px;
  }

  .nt-icon {
    width: 38px;
    height: 38px;
  }

  .nt-title {
    font-size: var(--fs-sm);
  }

  /* The time drops under the title, so the title keeps the full width. */
  .nt-time {
    flex-basis: 100%;
    margin-left: 0;
  }

  .nt-chip {
    border-radius: var(--r-control);
  }

  .nt-arrow {
    display: none;
  }
}

.notif-tone--placed {
  background: var(--st-placed-bg);
  color: var(--st-placed);
}
.notif-tone--preparing {
  background: var(--st-preparing-bg);
  color: var(--st-preparing);
}
.notif-tone--ready {
  background: var(--st-ready-bg);
  color: var(--st-ready);
}
.notif-tone--done {
  background: var(--st-done-bg);
  color: var(--st-done);
}
.notif-tone--cancelled {
  background: var(--st-cancelled-bg);
  color: var(--st-cancelled);
}
.notif-tone--brand {
  background: var(--c-brand-tint);
  color: var(--c-brand);
}
.nt-message {
  overflow-wrap: anywhere;
}
.browse-btn {
  border-radius: var(--r-control);
  background: var(--c-brand);
  color: white;
}
@media (max-width: 600px) {
  .page-content {
    padding: 16px;
  }
  .page-header-row {
    flex-direction: column;
    align-items: stretch;
  }
}
</style>
