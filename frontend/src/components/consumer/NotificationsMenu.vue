<template>
  <!-- Notification bell for the compact header, where tapping it opens the notifications page rather than this panel. -->
  <div class="notif">
    <q-btn flat dense :ripple="false" class="notif__btn" :aria-label="ariaLabel" @click="toggle">
      <q-icon name="o_notifications" size="24px" />
      <span v-if="unreadCount" class="notif__count">{{ unreadCount }}</span>

      <!-- no-parent-event, because the button's click already toggles and fetches, while QMenu handles outside-click, Escape and focus. -->
      <q-menu
        v-model="open"
        no-parent-event
        :target="anchorTarget || undefined"
        anchor="bottom right"
        self="top right"
        :offset="[0, 8]"
        class="notif__panel"
      >
      <div class="notif__head">
        Notifications
        <q-btn
          v-if="unreadCount"
          flat
          dense
          no-caps
          size="sm"
          label="Mark all as read"
          color="primary"
          @click="markAllAsRead"
        />
      </div>

      <p v-if="!notifications.length" class="notif__empty">No notifications yet.</p>

      <q-list v-else class="notif__scroll">
        <q-item
          v-for="notif in notifications.slice(0, MAX_SHOWN)"
          :key="notif.notification_id"
          v-close-popup
          clickable
          class="notif__item"
          :class="{ 'notif__item--unread': !notif.is_read }"
          @click="select(notif)"
        >
          <q-item-section>
            <q-item-label class="notif__title">{{ notif.title }}</q-item-label>
            <q-item-label caption class="notif__body">{{ notif.message }}</q-item-label>
          </q-item-section>
        </q-item>
      </q-list>

      <!-- Always offered, even with an empty list, so the full history is one tap away. -->
      <q-btn
        unelevated
        no-caps
        label="View All Notifications"
        class="notif__view-all"
        @click="goToAll"
      />
      </q-menu>
    </q-btn>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { api } from '@/boot/axios'

const router = useRouter()
const $q = useQuasar()

const props = defineProps({
  /** Element the panel aligns to, falling back to the button, since the bell is not the header's rightmost control. */
  anchorTarget: {
    type: Object,
    default: null
  }
})

const anchorTarget = computed(() => props.anchorTarget)

const MAX_SHOWN = 10

const notifications = ref([])
const open = ref(false)

const unreadCount = computed(() => notifications.value.filter((n) => !n.is_read).length)

const ariaLabel = computed(() =>
  unreadCount.value ? `Notifications, ${unreadCount.value} unread` : 'Notifications'
)

const isLoggedIn = () => !!localStorage.getItem('auth_token')

const fetchNotifications = async () => {
  if (!isLoggedIn()) return
  try {
    const res = await api.get('/consumer/notifications')
    notifications.value = res.data
  } catch (error) {
    console.error('Failed to fetch notifications', error)
  }
}

// This instance only renders in the compact header, so it always opens the notifications page, like the cart icon.
const toggle = () => {
  if ($q.screen.lt.md) {
    open.value = false
    router.push('/consumer/notifications')
    return
  }
  open.value = !open.value
  if (open.value) fetchNotifications()
}

onMounted(fetchNotifications)

// Marked locally first so the badge responds immediately; the request is best-effort.
const markAsRead = async (id) => {
  const notif = notifications.value.find((n) => n.notification_id === id)
  if (!notif || notif.is_read) return
  notif.is_read = true
  try {
    await api.patch(`/consumer/notifications/${id}/read`)
  } catch {
    // Stays read locally; the next fetch reconciles it.
  }
}

const select = async (notif) => {
  if (!notif.is_read) await markAsRead(notif.notification_id)
  if (notif.order_id) {
    localStorage.setItem('consumer_selected_order_id', notif.order_id)
    open.value = false
    router.push('/consumer/orders/details')
  }
}

const goToAll = () => {
  open.value = false
  router.push('/consumer/notifications')
}

const markAllAsRead = async () => {
  notifications.value.forEach((n) => { n.is_read = true })
  try {
    await api.post('/consumer/notifications/read-all')
  } catch {
    // Same best-effort treatment as markAsRead.
  }
}
</script>

<style scoped>
.notif {
  display: flex;
  align-items: center;
}

.notif__btn {
  position: relative;

  /* 44px to match the compact header's .header-mobile-btn siblings, set here because SiteHeader's scoped styles cannot reach it. */
  width: 44px;
  height: 44px;

  border-radius: var(--r-md);

  color: #ffffff;

  transition: background-color 0.15s;
}

.notif__btn:hover {
  background: rgba(255, 255, 255, 0.14);
}

.notif__count {
  position: absolute;
  top: 2px;
  right: 2px;

  display: flex;
  align-items: center;
  justify-content: center;

  min-width: 16px;
  height: 16px;
  padding: 0 3px;

  border: 1.5px solid var(--c-brand-deep);
  border-radius: var(--r-pill);

  background: #ffffff;
  color: var(--c-brand);

  font-size: var(--fs-2xs);
  font-weight: 700;
  line-height: 1;
}

/* QMenu positions and teleports this panel itself, so it needs none of the old absolute placement. */
.notif__panel {
  width: 300px;
  max-width: calc(100vw - 32px);
  box-sizing: border-box;

  overflow: hidden;

  border: 1px solid var(--c-border);
  border-radius: var(--r-lg);

  background: #ffffff;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.18);

  font-family: 'Roboto', Arial, sans-serif;
}

.notif__head {
  display: flex;
  align-items: center;
  justify-content: space-between;

  padding: 14px 14px 10px;

  font-size: var(--fs-md);
  font-weight: 700;

  color: var(--c-text);
}

.notif__empty {
  margin: 0;
  padding: 6px 14px 18px;

  font-size: var(--fs-sm);
  text-align: center;

  color: var(--c-muted);
}

/* Caps at roughly five rows before scrolling. */
.notif__scroll {
  max-height: 335px;
  padding-bottom: 6px;

  overflow-y: auto;
}

/* QItem supplies the ripple, focus ring and keyboard activation, with its default height and padding trimmed to this panel. */
.notif__item {
  padding: 10px 14px;
  min-height: auto;

  border-top: 1px solid var(--c-hairline);
}

.notif__item:first-child {
  border-top: none;
}

.notif__item:hover {
  background: var(--c-brand-tint);
}

.notif__title {
  display: block;

  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;

  font-size: var(--fs-sm);
  font-weight: 600;

  color: var(--c-text);
}

.notif__body {
  display: block;

  margin-top: 1px;

  font-size: var(--fs-xs);
  line-height: 1.3;

  color: var(--c-muted);
}

.notif__view-all {
  width: calc(100% - 28px);
  height: 40px;
  margin: 10px 14px 14px;

  border-radius: var(--r-sm);

  background: var(--c-brand);
  color: #ffffff;

  font-size: var(--fs-xs);
  font-weight: 600;
}

.notif__view-all:hover {
  background: var(--c-brand-hover);
}

/* Unread carries the weight; read rows fade back. */
.notif__item:not(.notif__item--unread) {
  opacity: 0.7;
}

.notif__item--unread .notif__title {
  font-weight: 700;
}
</style>
