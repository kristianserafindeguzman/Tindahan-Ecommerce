<template>
  <q-page class="vp-page">
    <div class="vp-container">

      <div class="vp-header">
        <div>
          <h1 class="vp-title">{{ t('title') }}</h1>
          <p class="vp-subtitle">{{ t('subtitle') }}</p>
        </div>
        <q-btn outline no-caps color="primary" icon="o_done_all" :label="t('markAllAsRead')" class="vp-pill-btn" :disable="!unreadCount" @click="markAllAsRead" />
      </div>

      <div class="vp-card">
        <div class="vp-toolbar">
          <div class="vp-chips" role="tablist" :aria-label="t('filterAria')">
            <button
              v-for="filter in localizedFilters"
              :key="filter.key"
              type="button"
              role="tab"
              class="vp-chip"
              :class="{ 'vp-chip--active': active === filter.key }"
              :aria-selected="active === filter.key"
              @click="active = filter.key"
            >
              {{ filter.label }}
              <span class="vp-chip-count">{{ countFor(filter.key) }}</span>
            </button>
          </div>
        </div>

        <!-- Placeholder rows shaped like the real ones until the first answer arrives. -->
        <div v-if="loading && !notifications.length" class="nt-skeletons">
          <div v-for="n in 6" :key="n" class="nt-skeleton">
            <q-skeleton type="rect" width="40px" height="40px" class="nt-skeleton-icon" />
            <div class="nt-skeleton-lines">
              <q-skeleton type="text" width="35%" />
              <q-skeleton type="text" width="70%" />
            </div>
            <q-skeleton type="text" width="60px" />
          </div>
        </div>

        <div v-else-if="!filtered.length" class="vp-empty">
          <div class="vp-empty-icon"><q-icon name="o_notifications_none" size="24px" /></div>
          <div class="vp-empty-title">{{ active === 'unread' ? t('emptyUnreadTitle') : t('emptyAllTitle') }}</div>
          <div class="vp-empty-text">
            {{ active === 'unread' ? t('emptyUnreadDesc') : t('emptyAllDesc') }}
          </div>
        </div>

        <template v-else>
          <!-- Grouped by day, so today's news sits apart from older ones. -->
          <section v-for="group in groups" :key="group.label" class="nt-group">
            <div class="nt-group-label">{{ group.label }}</div>
            <button
              v-for="notif in group.items"
              :key="notif.notification_id"
              type="button"
              class="nt-item"
              :class="{ 'nt-item--unread': !notif.is_read }"
              @click="openNotification(notif)"
            >
              <span class="nt-icon" :class="`vp-tone--${notificationKind(notif).tone}`">
                <q-icon :name="notificationKind(notif).icon" size="20px" />
              </span>
              <span class="nt-body">
                <span class="nt-title">
                  {{ notif.title }}
                  <span v-if="!notif.is_read" class="nt-new">{{ t('newBadge') }}</span>
                </span>
                <span class="nt-message">{{ notif.message }}</span>
              </span>
              <span class="nt-side">
                <span class="nt-time">{{ formatClock(notif.created_at) }}</span>
                <span class="nt-ago">{{ timeAgo(notif.created_at) }}</span>
              </span>
              <q-icon v-if="notif.order_id" name="o_chevron_right" size="18px" class="nt-arrow" />
            </button>
          </section>

          <div v-if="pageCount > 1" class="vp-pager">
            <span>{{ t('showingWord') }} {{ rangeStart }}–{{ rangeEnd }} {{ t('ofWord') }} {{ filtered.length }}</span>
            <div class="vp-pager-btns">
              <q-btn outline no-caps color="primary" icon="o_chevron_left" class="vp-pill-btn" :aria-label="t('prevPage')" :disable="page === 1" @click="page--" />
              <q-btn outline no-caps color="primary" icon="o_chevron_right" class="vp-pill-btn" :aria-label="t('nextPage')" :disable="page === pageCount" @click="page++" />
            </div>
          </div>
        </template>
      </div>

    </div>
  </q-page>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useVendorNotifications, notificationKind } from '@/composables/useVendorNotifications'
import { useLanguage } from '@/composables/useLanguage'

const router = useRouter()
const { notifications, loading, unreadCount, fetchNotifications, markAsRead, markAllAsRead, timeAgo } = useVendorNotifications()

// Language Dictionary for Notifications
const vendorNotificationsDict = {
  en: {
    title: 'Notifications',
    subtitle: 'New orders and cancellations from your store, newest first.',
    markAllAsRead: 'Mark all as read',
    filterAria: 'Filter notifications',
    filterAll: 'All',
    filterUnread: 'Unread',
    filterOrders: 'Orders',
    filterCancelled: 'Cancellations',
    emptyUnreadTitle: "You're all caught up",
    emptyUnreadDesc: 'Every notification has been read.',
    emptyAllTitle: 'Nothing here yet',
    emptyAllDesc: 'New orders and cancellations will show up here.',
    newBadge: 'New',
    showingWord: 'Showing',
    ofWord: 'of',
    prevPage: 'Previous page',
    nextPage: 'Next page',
    dayEarlier: 'Earlier',
    dayToday: 'Today',
    dayYesterday: 'Yesterday'
  },
  ph: {
    title: 'Mga Notification',
    subtitle: 'Mga bagong order at cancellation mula sa tindahan mo, pinakabago una.',
    markAllAsRead: 'I-mark lahat as read',
    filterAria: 'I-filter ang notifications',
    filterAll: 'Lahat',
    filterUnread: 'Unread',
    filterOrders: 'Mga Order',
    filterCancelled: 'Mga Kinansela',
    emptyUnreadTitle: 'Wala nang bagong notification',
    emptyUnreadDesc: 'Nabasang lahat ang bawat notification.',
    emptyAllTitle: 'Wala pang notification',
    emptyAllDesc: 'Dito lalabas ang mga bagong order at cancellations.',
    newBadge: 'Bago',
    showingWord: 'Pinapakita ang',
    ofWord: 'mula sa',
    prevPage: 'Nakaraang pahina',
    nextPage: 'Susunod na pahina',
    dayEarlier: 'Nakaraan',
    dayToday: 'Ngayon',
    dayYesterday: 'Kahapon'
  }
}

const { t } = useLanguage(vendorNotificationsDict)

// Reactive filters using translations
const localizedFilters = computed(() => [
  { key: 'all', label: t('filterAll') },
  { key: 'unread', label: t('filterUnread') },
  { key: 'order', label: t('filterOrders') },
  { key: 'cancelled', label: t('filterCancelled') }
])

const PAGE_SIZE = 20

const active = ref('all')
const page = ref(1)

const matches = (notif, key) => {
  if (key === 'all') return true
  if (key === 'unread') return !notif.is_read
  return notificationKind(notif).key === key
}

const countFor = key => notifications.value.filter(n => matches(n, key)).length

const filtered = computed(() =>
  [...notifications.value]
    .sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
    .filter(n => matches(n, active.value))
)

const pageCount = computed(() => Math.max(1, Math.ceil(filtered.value.length / PAGE_SIZE)))
const paged = computed(() => filtered.value.slice((page.value - 1) * PAGE_SIZE, page.value * PAGE_SIZE))
const rangeStart = computed(() => (page.value - 1) * PAGE_SIZE + 1)
const rangeEnd = computed(() => Math.min(page.value * PAGE_SIZE, filtered.value.length))

watch(active, () => { page.value = 1 })

// Reading one under "Unread" takes it off the list, so a later page that runs out steps back rather than showing nothing.
watch(pageCount, count => { if (page.value > count) page.value = count })

// "Today", "Yesterday", or the date, for each day's heading.
const dayLabel = dateString => {
  const date = new Date(dateString)
  if (Number.isNaN(date.getTime())) return t('dayEarlier')
  const today = new Date()
  const yesterday = new Date()
  yesterday.setDate(today.getDate() - 1)
  if (date.toDateString() === today.toDateString()) return t('dayToday')
  if (date.toDateString() === yesterday.toDateString()) return t('dayYesterday')
  return date.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' })
}

const groups = computed(() => paged.value.reduce((list, notif) => {
  const label = dayLabel(notif.created_at)
  const last = list[list.length - 1]
  if (last && last.label === label) last.items.push(notif)
  else list.push({ label, items: [notif] })
  return list
}, []))

const formatClock = dateString => {
  const date = new Date(dateString)
  return Number.isNaN(date.getTime()) ? '' : date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })
}

const openNotification = notif => {
  markAsRead(notif)
  if (notif.order_id) router.push(`/vendor/orders/${notif.order_id}`)
}

onMounted(fetchNotifications)
</script>

<style scoped>
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

.nt-group-label {
  padding: 10px 20px;

  border-bottom: 1px solid var(--c-hairline);

  background: var(--c-surface-2);

  font-size: var(--fs-2xs);
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;

  color: var(--c-muted);
}

.nt-item {
  display: flex;
  align-items: center;

  gap: 14px;
  width: 100%;
  padding: 14px 20px;

  border: none;
  border-bottom: 1px solid var(--c-hairline);

  background: #ffffff;

  font-family: inherit;
  text-align: left;

  cursor: pointer;

  transition: background-color 0.15s;
}

.nt-group:last-of-type .nt-item:last-child {
  border-bottom: none;
}

.nt-item:hover {
  background: var(--c-surface-2);
}

.nt-item:focus-visible {
  outline: 2px solid var(--c-brand);
  outline-offset: -2px;
}

/* Unread ones carry a brand bar on the left and a "New" tag. */
.nt-item--unread {
  box-shadow: inset 3px 0 0 var(--c-brand);

  background: var(--c-brand-tint);
}

.nt-item--unread:hover {
  background: var(--c-brand-tint-2);
}

.nt-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;

  width: 40px;
  height: 40px;

  border-radius: var(--r-control);
}

.nt-body {
  display: flex;
  flex-direction: column;

  flex: 1;
  min-width: 0;
  gap: 2px;
}

.nt-title {
  display: flex;
  align-items: center;

  gap: 8px;

  font-size: var(--fs-sm);
  font-weight: 700;

  color: var(--c-text);
}

.nt-new {
  padding: 1px 8px;

  border-radius: var(--r-pill);

  background: var(--c-brand);

  font-size: var(--fs-2xs);
  font-weight: 700;

  color: #ffffff;
}

.nt-message {
  font-size: var(--fs-sm);
  line-height: 1.45;

  color: var(--c-text-3);
}

.nt-side {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  flex-shrink: 0;

  gap: 2px;
}

.nt-time {
  font-size: var(--fs-xs);
  font-weight: 600;

  color: var(--c-text-2);
}

.nt-ago {
  font-size: var(--fs-2xs);

  color: var(--c-muted);
}

.nt-arrow {
  flex-shrink: 0;

  color: var(--c-border-strong);
}

@media (max-width: 600px) {
  .nt-item {
    align-items: flex-start;

    gap: 12px;
    padding: 12px 16px;
  }

  .nt-group-label {
    padding-inline: 16px;
  }

  .nt-arrow {
    display: none;
  }
}
</style>