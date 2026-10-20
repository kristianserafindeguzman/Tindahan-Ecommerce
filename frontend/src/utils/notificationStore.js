import { ref } from 'vue'

export function createNotificationStore({ api, role, getSession }) {
  const notifications = ref([])
  const loading = ref(false)
  const unreadCount = ref(0)
  const total = ref(0)
  const prefix = `/${role.toLowerCase()}/notifications`
  let context = null

  const reset = () => {
    context = null
    notifications.value = []
    unreadCount.value = 0
    total.value = 0
    loading.value = false
  }
  const sessionToken = () => {
    const session = getSession()
    if (
      !session?.token ||
      session.role !== role ||
      (role === 'Vendor' && session.vendorStatus !== 'approved')
    )
      return null
    return session.token
  }
  const active = ctx => context === ctx && sessionToken() === ctx.token
  const ensureContext = () => {
    const token = sessionToken()
    if (!token) return null
    if (context?.token !== token) {
      reset()
      context = {
        token,
        revision: 0,
        pending: null,
        dirty: false,
        mutations: 0,
        queue: Promise.resolve()
      }
    }
    return context
  }
  const fetchNotifications = async () => {
    const ctx = ensureContext()
    if (!ctx) return
    if (ctx.mutations) {
      ctx.dirty = true
      return
    }
    if (ctx.pending) {
      ctx.dirty = true
      return ctx.pending
    }
    const generation = ctx.revision
    ctx.dirty = false
    loading.value = notifications.value.length === 0
    let request
    request = (async () => {
      try {
        const { data } = await api.get(prefix, {
          timeout: 15000,
          params: { sync: 1 },
          headers: { 'X-Background-Sync': '1' }
        })
        if (!active(ctx) || generation !== ctx.revision) return
        const rows = Array.isArray(data)
          ? data
          : data.notifications || data.data || []
        notifications.value = rows.map(row => ({
          ...row,
          is_read:
            row.is_read === true || row.is_read === 1 || row.is_read === '1'
        }))
        unreadCount.value = Number(
          data.unread_count ??
            notifications.value.filter(row => !row.is_read).length
        )
        total.value = Number(data.total ?? rows.length)
      } catch (error) {
        if (error?.code !== 'ERR_CANCELED')
          console.error('Failed to fetch notifications', error)
      } finally {
        if (ctx.pending === request) ctx.pending = null
        if (active(ctx)) loading.value = false
      }
    })()
    ctx.pending = request
    await request
    if (active(ctx) && ctx.dirty && !ctx.mutations) {
      ctx.dirty = false
      return fetchNotifications()
    }
  }
  const mutate = action => {
    const ctx = ensureContext()
    if (!ctx) return Promise.resolve()
    const work = ctx.queue
      .catch(() => {})
      .then(async () => {
        if (!active(ctx)) return
        ctx.revision++
        ctx.mutations++
        const previousCount = unreadCount.value
        const affected = notifications.value.filter(
          row =>
            !row.is_read &&
            (action === 'all' || String(row.notification_id) === String(action))
        )
        affected.forEach(row => {
          row.is_read = true
        })
        unreadCount.value =
          action === 'all'
            ? 0
            : Math.max(0, unreadCount.value - affected.length)
        try {
          const options = { timeout: 15000 }
          if (action === 'all')
            await api.post(`${prefix}/read-all`, {}, options)
          else await api.patch(`${prefix}/${action}/read`, {}, options)
        } catch (error) {
          if (active(ctx)) {
            affected.forEach(row => {
              row.is_read = false
            })
            unreadCount.value = previousCount
          }
          throw error
        } finally {
          ctx.mutations--
          ctx.revision++
          if (active(ctx)) {
            // Persistence is complete. Reconciliation must not block navigation
            // or the next read action behind a slow background request.
            void fetchNotifications()
          }
        }
      })
    ctx.queue = work
    return work
  }
  return {
    notifications,
    loading,
    unreadCount,
    total,
    reset,
    fetchNotifications,
    markAsRead: notification =>
      notification?.is_read
        ? Promise.resolve()
        : mutate(notification?.notification_id ?? notification),
    markAllAsRead: () => mutate('all')
  }
}
