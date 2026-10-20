// Dependency injection keeps connection/retry/session races testable without a browser.
export function createLiveUpdates({
  api,
  createEcho,
  getSession,
  onSession = () => {},
  onReset = () => {},
  browser = globalThis,
  timers = globalThis
}) {
  const resources = new Set()
  let token = null
  let epoch = 0
  let echo = null
  let signature = null
  let timer = null
  let configRequest = null
  let configDirty = false
  let healthy = false
  let started = false
  let failures = 0
  let refreshTimer = null
  const pendingTopics = new Set()
  const seen = new Set()
  const visible = () =>
    !browser.document?.hidden && browser.navigator?.onLine !== false

  const run = async resource => {
    if (!started || !token || !visible() || !resources.has(resource)) return
    if (resource.running) {
      resource.dirty = true
      return
    }
    const generation = epoch
    const ticket = {}
    resource.running = ticket
    try {
      await resource.refresh({ background: true })
    } catch (error) {
      if (error?.code !== 'ERR_CANCELED')
        console.error('Background refresh failed', error)
    } finally {
      if (resource.running === ticket) {
        resource.running = null
        if (generation === epoch && resource.dirty) {
          resource.dirty = false
          void run(resource)
        }
      }
    }
  }

  const refresh = topics => {
    for (const resource of resources) {
      if (!topics || resource.topics.some(topic => topics.has(topic)))
        void run(resource)
    }
  }

  const invalidate = topic => {
    pendingTopics.add(topic)
    if (refreshTimer !== null) return
    // Batch signals arriving in the same turn without adding a visible debounce delay.
    refreshTimer = timers.setTimeout(() => {
      refreshTimer = null
      const topics = new Set(pendingTopics)
      pendingTopics.clear()
      refresh(topics)
    }, 0)
  }

  const disconnect = () => {
    healthy = false
    if (echo) echo.disconnect()
    echo = null
    signature = null
  }

  const reset = nextToken => {
    epoch++
    token = nextToken
    disconnect()
    configRequest = null
    configDirty = false
    for (const resource of resources) {
      resource.running = null
      resource.dirty = false
    }
    seen.clear()
    pendingTopics.clear()
    if (refreshTimer !== null) timers.clearTimeout(refreshTimer)
    refreshTimer = null
    onReset()
  }

  const configure = async (mustRefresh = false) => {
    if (configRequest) {
      if (mustRefresh) configDirty = true
      return configRequest
    }
    const generation = epoch
    const currentToken = token
    const request = (async () => {
      try {
        const { data } = await api.get('/realtime/config', {
          timeout: 10000,
          headers: { 'X-Background-Sync': '1' }
        })
        if (generation !== epoch || currentToken !== getSession()?.token) return
        failures = 0
        onSession(data)
        const nextSignature = JSON.stringify([
          data.enabled,
          data.host,
          data.port,
          data.scheme,
          data.path,
          data.key,
          data.channels
        ])
        if (!data.enabled) {
          disconnect()
          return
        }
        if (nextSignature === signature && echo) return
        disconnect()
        signature = nextSignature
        echo = createEcho(data)
        const activeEcho = echo
        const subscribed = new Set()
        const channels = data.channels || []
        for (const name of channels) {
          echo
            .private(name)
            .listen('.realtime.changed', event => {
              if (generation !== epoch || echo !== activeEcho) return
              if (event.event_id && seen.has(event.event_id)) return
              if (event.event_id) seen.add(event.event_id)
              if (seen.size > 500) seen.delete(seen.values().next().value)
              if (event.topic === 'profile' || event.topic === 'session')
                void configure(true)
              if (typeof event.topic === 'string') invalidate(event.topic)
            })
            .subscribed(() => {
              if (generation !== epoch || echo !== activeEcho) return
              subscribed.add(name)
              healthy = subscribed.size === channels.length
              // Refetch after subscribing; anything changed during setup is included.
              refresh()
            })
            .error(() => {
              if (echo !== activeEcho) return
              // Pusher does not retry failed channel authorization on a healthy
              // connection. Rebuild it on the next synchronization attempt.
              disconnect()
              schedule()
            })
        }
        echo.connector.pusher.connection.bind('state_change', ({ current }) => {
          if (echo !== activeEcho) return
          if (current !== 'connected') {
            healthy = false
            subscribed.clear()
          }
        })
      } catch (error) {
        if (generation === epoch) {
          // A temporary HTTP failure does not change existing socket subscriptions.
          failures++
        }
        if (
          generation === epoch &&
          (error?.response?.status === 401 || error?.response?.status === 403)
        )
          disconnect()
      }
    })()
    configRequest = request
    try {
      await request
    } finally {
      if (configRequest === request) {
        configRequest = null
        if (configDirty && generation === epoch && started && token) {
          configDirty = false
          void configure()
        }
      }
    }
  }

  const schedule = () => {
    if (timer !== null) timers.clearTimeout(timer)
    timer = null
    if (!started || !token) return
    const delay = Math.min(
      120000,
      (healthy ? 60000 : 30000) * 2 ** Math.min(failures, 2)
    )
    timer = timers.setTimeout(async () => {
      timer = null
      await syncSession(true)
      if (started) schedule()
    }, delay)
  }

  const syncSession = async (force = false) => {
    if (!started) return
    const nextToken = getSession()?.token || null
    const changed = token !== nextToken
    if (changed) reset(nextToken)
    // Routing/storage activity must not keep moving the reconciliation deadline.
    if (!changed && !force) return
    if (!token || !visible()) {
      schedule()
      return
    }
    if (changed || force) {
      await configure()
      if (token === getSession()?.token) refresh()
    }
    schedule()
  }

  const resume = () => {
    if (visible()) void syncSession(true)
  }
  const storageChanged = () => {
    void syncSession()
  }

  return {
    register(topics, callback) {
      const resource = {
        topics: Array.isArray(topics) ? topics : [topics],
        refresh: callback,
        running: false,
        dirty: false
      }
      resources.add(resource)
      return () => resources.delete(resource)
    },
    invalidate,
    syncSession,
    start() {
      if (started) return
      started = true
      browser.window?.addEventListener('storage', storageChanged)
      browser.window?.addEventListener('auth-session-changed', storageChanged)
      browser.window?.addEventListener('online', resume)
      browser.document?.addEventListener('visibilitychange', resume)
      void syncSession(true)
    },
    stop() {
      started = false
      reset(null)
      if (timer !== null) timers.clearTimeout(timer)
      timer = null
      browser.window?.removeEventListener('storage', storageChanged)
      browser.window?.removeEventListener(
        'auth-session-changed',
        storageChanged
      )
      browser.window?.removeEventListener('online', resume)
      browser.document?.removeEventListener('visibilitychange', resume)
    },
    get connected() {
      return healthy
    }
  }
}
