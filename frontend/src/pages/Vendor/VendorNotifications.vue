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
          <!-- Grouped by day, so today's news sits apart from older ones; each notification is its own card. -->
          <div class="nt-groups">
            <section v-for="group in groups" :key="group.label" class="nt-group">
              <h2 class="nt-group-label">{{ group.label }}</h2>
              <div class="nt-list">
                <button
                  v-for="item in group.items"
                  :key="item.notif.notification_id"
                  type="button"
                  class="nt-item"
                  :class="{ 'nt-item--unread': !item.notif.is_read }"
                  @click="openNotification(item.notif)"
                >
                  <span class="nt-icon" :class="`vp-tone--${item.kind.tone}`">
                    <q-icon :name="item.kind.icon" size="20px" />
                  </span>
                  <span class="nt-body">
                    <span class="nt-head">
                      <span class="nt-title">{{ item.text.title }}</span>
                      <span v-if="!item.notif.is_read" class="nt-new">{{ t('newBadge') }}</span>
                      <span class="nt-time">{{ formatClock(item.notif.created_at) }} · {{ timeAgo(item.notif.created_at) }}</span>
                    </span>
                    <span class="nt-message">{{ item.text.message }}</span>
                    <span v-if="item.text.pickup || item.text.reason" class="nt-meta">
                      <span v-if="item.text.pickup" class="nt-chip">
                        <q-icon name="o_schedule" size="14px" />
                        {{ item.text.pickupLabel }}: {{ item.text.pickup }}
                      </span>
                      <span v-if="item.text.reason" class="nt-chip nt-chip--reason">
                        <q-icon name="o_info" size="14px" />
                        {{ item.text.reasonLabel }}: {{ item.text.reason }}
                      </span>
                    </span>
                  </span>
                  <q-icon v-if="item.notif.order_id" name="o_chevron_right" size="20px" class="nt-arrow" />
                </button>
              </div>
            </section>
          </div>

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
import { useVendorNotifications, notificationKind, notificationText } from '@/composables/useVendorNotifications'
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

// Each row carries its kind and its text in the current language, worked out once per render.
const groups = computed(() => paged.value.reduce((list, notif) => {
  const label = dayLabel(notif.created_at)
  const item = { notif, kind: notificationKind(notif), text: notificationText(notif) }
  const last = list[list.length - 1]
  if (last && last.label === label) last.items.push(item)
  else list.push({ label, items: [item] })
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

  transition: background-color 0.15s, border-color 0.15s, box-shadow 0.15s;
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

  box-shadow: inset 4px 0 0 var(--c-brand), 0 4px 14px rgba(15, 23, 42, 0.06);
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
</style>
