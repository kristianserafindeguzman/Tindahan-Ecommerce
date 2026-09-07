<template>
  <!--
    Bell + dropdown, self-contained so the header can place one instance in the desktop
    action cluster and another in the compact mobile cluster without duplicating the
    panel markup. Only one is ever rendered at a time (the header gates on its own
    breakpoint), so this never double-fetches.
  -->
  <div class="notif">
    <q-btn flat dense :ripple="false" class="notif__btn" :aria-label="ariaLabel" @click="toggle">
      <q-icon name="o_notifications" size="24px" />
      <span v-if="unreadCount" class="notif__count">{{ unreadCount }}</span>

      <!-- no-parent-event: the button's @click already toggles and triggers the fetch,
           so QMenu must not also open itself. Outside-click, Escape, focus return and
           placement come from QMenu instead of the hand-rolled document listener. -->
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
          label="Mark all read"
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
      </q-menu>
    </q-btn>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '@/boot/axios'

const router = useRouter()

const props = defineProps({
  /**
   * Element the panel aligns to. The bell is not the rightmost control in the header,
   * so aligning to the button itself leaves the panel short of the cluster's right
   * edge — which is where it sat before this became a QMenu. Falls back to the button.
   */
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

const toggle = () => {
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

  width: 40px;
  height: 40px;

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

/* QMenu positions and teleports this itself, so the absolute placement the old div
   needed is gone. It stays in the scoped block via :deep-free plain class because the
   class is passed to QMenu, which renders it on the teleported root. */
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

/* QItem inside a QMenu is the canonical list row: it brings the ripple, focus ring
   and keyboard activation the bare <button> only had by accident of being a button.
   Its default 48px min-height and 16px padding are trimmed back to this panel's
   tighter rhythm. */
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

/* Unread carries the weight; read rows fade back. */
.notif__item:not(.notif__item--unread) {
  opacity: 0.7;
}

.notif__item--unread .notif__title {
  font-weight: 700;
}
</style>



