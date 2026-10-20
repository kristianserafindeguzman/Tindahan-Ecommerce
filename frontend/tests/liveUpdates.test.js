import test from 'node:test'
import assert from 'node:assert/strict'
import { createLiveUpdates } from '../src/utils/liveUpdatesCore.js'
import { createNotificationStore } from '../src/utils/notificationStore.js'

const settle = () => new Promise(resolve => setImmediate(resolve))
const deferred = () => {
  let resolve, reject
  const promise = new Promise((yes, no) => {
    resolve = yes
    reject = no
  })
  return { promise, resolve, reject }
}

function harness({ configGet } = {}) {
  let session = { token: 'first', role: 'Consumer' }
  const jobs = new Map()
  let timerId = 0
  const timers = {
    setTimeout(fn, delay) {
      const id = ++timerId
      jobs.set(id, { fn, delay })
      return id
    },
    clearTimeout(id) {
      jobs.delete(id)
    }
  }
  const browser = {
    window: new EventTarget(),
    document: new EventTarget(),
    navigator: { onLine: true }
  }
  browser.document.hidden = false
  const sockets = []
  const calls = []
  let resets = 0
  const engine = createLiveUpdates({
    api: {
      get: async () => {
        calls.push(session.token)
        return configGet
          ? configGet(session)
          : {
              data: {
                enabled: true,
                key: 'public',
                host: 'localhost',
                port: 8080,
                channels: ['users.1']
              }
            }
      }
    },
    getSession: () => session,
    onReset: () => {
      resets++
    },
    browser,
    timers,
    createEcho: () => {
      const socket = {
        channels: new Map(),
        disconnected: false,
        stateChanged: null,
        disconnect() {
          this.disconnected = true
        },
        connector: {
          pusher: {
            connection: {
              bind: (_, fn) => {
                socket.stateChanged = fn
              }
            }
          }
        },
        private(name) {
          const channel = {
            listen(_, fn) {
              this.receive = fn
              return this
            },
            subscribed(fn) {
              this.ready = fn
              return this
            },
            error(fn) {
              this.failed = fn
              return this
            }
          }
          this.channels.set(name, channel)
          return channel
        }
      }
      sockets.push(socket)
      return socket
    }
  })
  return {
    engine,
    sockets,
    calls,
    browser,
    jobs,
    get resets() {
      return resets
    },
    session: value => {
      session = value
    },
    async fire(delay) {
      const entry = [...jobs].find(([, job]) => job.delay === delay)
      assert.ok(entry, `Expected a ${delay}ms timer`)
      jobs.delete(entry[0])
      await entry[1].fn()
      await settle()
    }
  }
}

test('private subscriptions reconcile and deduplicate burst signals without overlapping resource requests', async () => {
  const h = harness()
  let refreshes = 0
  let pending = null
  h.engine.register('orders', async () => {
    refreshes++
    if (pending) await pending.promise
  })
  h.engine.start()
  await settle()
  assert.equal(refreshes, 1)
  const channel = h.sockets[0].channels.get('users.1')
  channel.ready()
  await settle()
  assert.equal(h.engine.connected, true)
  assert.equal(refreshes, 2)
  pending = deferred()
  channel.receive({ event_id: 'new-order', topic: 'orders' })
  channel.receive({ event_id: 'new-order', topic: 'orders' })
  channel.receive({ event_id: 'status', topic: 'orders' })
  await h.fire(0)
  assert.equal(refreshes, 3)
  channel.receive({ event_id: 'another-status', topic: 'orders' })
  await h.fire(0)
  assert.equal(refreshes, 3)
  pending.resolve()
  pending = null
  await settle()
  assert.equal(refreshes, 4)
  h.engine.stop()
  assert.equal(h.sockets[0].disconnected, true)
  assert.equal(h.jobs.size, 0)
})

test('late configuration from a previous account cannot subscribe after account switching', async () => {
  const first = deferred()
  const h = harness({
    configGet: session =>
      session.token === 'first'
        ? first.promise
        : Promise.resolve({ data: { enabled: true, channels: ['users.2'] } })
  })
  h.engine.start()
  await settle()
  h.session({ token: 'second', role: 'Consumer' })
  await h.engine.syncSession()
  first.resolve({ data: { enabled: true, channels: ['users.1'] } })
  await settle()
  assert.equal(h.sockets.length, 1)
  assert.deepEqual([...h.sockets[0].channels.keys()], ['users.2'])
  h.session({ token: null })
  await h.engine.syncSession()
  assert.equal(h.sockets[0].disconnected, true)
  assert.equal(h.jobs.size, 0)
  h.engine.stop()
})

test('order signals refresh on the next browser turn without an artificial debounce delay', async () => {
  const h = harness()
  let refreshes = 0
  h.engine.register('orders', async () => {
    refreshes++
  })
  h.engine.start()
  await settle()
  const before = refreshes

  h.sockets[0].channels
    .get('users.1')
    .receive({ event_id: 'status-now', topic: 'orders' })
  await h.fire(0)

  assert.equal(refreshes, before + 1)
  h.engine.stop()
})

test('a previous account refresh does not block the next session from loading', async () => {
  const h = harness()
  const old = deferred()
  let calls = 0
  h.engine.register('notifications', async () => {
    calls++
    if (calls === 1) await old.promise
  })
  h.engine.start()
  await settle()
  h.session({ token: 'second', role: 'Consumer' })
  await h.engine.syncSession()
  assert.equal(calls, 2)
  old.resolve()
  await settle()
  assert.equal(calls, 2)
  h.engine.stop()
})

test('fallback polling pauses in hidden tabs, reconciles on return and unregisters resources', async () => {
  const h = harness()
  let refreshes = 0
  const unregister = h.engine.register('notifications', async () => {
    refreshes++
  })
  h.engine.start()
  await settle()
  assert.equal(refreshes, 1)
  h.browser.document.hidden = true
  await h.fire(30000)
  assert.equal(refreshes, 1)
  h.browser.document.hidden = false
  h.browser.document.dispatchEvent(new Event('visibilitychange'))
  await settle()
  assert.equal(refreshes, 2)
  unregister()
  await h.fire(30000)
  assert.equal(refreshes, 2)
  h.engine.stop()
})

test('all subscribed channels must recover after a connection interruption', async () => {
  const h = harness({
    configGet: async () => ({
      data: { enabled: true, channels: ['users.1', 'vendor-applications.1'] }
    })
  })
  h.engine.start()
  await settle()
  const socket = h.sockets[0]
  const channels = [...socket.channels.values()]
  channels[0].ready()
  assert.equal(h.engine.connected, false)
  channels[1].ready()
  assert.equal(h.engine.connected, true)
  socket.stateChanged({ current: 'unavailable' })
  assert.equal(h.engine.connected, false)
  channels[0].ready()
  assert.equal(h.engine.connected, false)
  channels[1].ready()
  assert.equal(h.engine.connected, true)
  h.engine.stop()
})

test('route navigation does not keep postponing the fallback refresh timer', async () => {
  const h = harness()
  h.engine.start()
  await settle()
  const deadline = [...h.jobs.keys()][0]
  await h.engine.syncSession()
  await h.engine.syncSession()
  assert.ok(
    h.jobs.has(deadline),
    'unchanged sessions must retain their refresh deadline'
  )
  h.engine.stop()
})

test('a transient channel authorization failure is retried without a transport outage', async () => {
  const h = harness()
  h.engine.start()
  await settle()
  const first = h.sockets[0]
  first.channels.get('users.1').failed({ status: 503 })
  await h.engine.syncSession(true)
  assert.equal(h.sockets.length, 2)
  assert.equal(first.disconnected, true)
  h.sockets[1].channels.get('users.1').ready()
  assert.equal(h.engine.connected, true)
  h.engine.stop()
})

test('temporary config API errors do not permanently mark a subscribed socket unhealthy', async () => {
  let unavailable = false
  const h = harness({
    configGet: async () => {
      if (unavailable) throw new Error('Temporary API outage')
      return { data: { enabled: true, channels: ['users.1'] } }
    }
  })
  h.engine.start()
  await settle()
  h.sockets[0].channels.get('users.1').ready()
  unavailable = true
  await h.engine.syncSession(true)
  unavailable = false
  await h.engine.syncSession(true)
  assert.equal(h.engine.connected, true)
  assert.equal(h.sockets.length, 1)
  h.engine.stop()
})

test('an approval signal during a config request triggers a fresh subscription check afterward', async () => {
  const pending = deferred()
  let requests = 0
  const h = harness({
    configGet: async () => {
      requests++
      if (requests === 2) return pending.promise
      return {
        data: {
          enabled: true,
          channels:
            requests === 1
              ? ['vendor-applications.1']
              : ['users.1', 'vendor-applications.1']
        }
      }
    }
  })
  h.engine.start()
  await settle()
  const refresh = h.engine.syncSession(true)
  await settle()
  h.sockets[0].channels
    .get('vendor-applications.1')
    .receive({ event_id: 'approved', topic: 'profile' })
  pending.resolve({
    data: { enabled: true, channels: ['vendor-applications.1'] }
  })
  await refresh
  await settle()
  assert.equal(requests, 3)
  assert.deepEqual(
    [...h.sockets.at(-1).channels.keys()],
    ['users.1', 'vendor-applications.1']
  )
  h.engine.stop()
})

function storeHarness() {
  let session = { token: 'first', role: 'Consumer' }
  let rows = [{ notification_id: 1, is_read: false }]
  let unread = 61
  const requests = []
  const api = {
    get: async (url, options) => {
      requests.push({ url, options })
      return {
        data: {
          notifications: rows.map(row => ({ ...row })),
          unread_count: unread,
          total: 61
        }
      }
    },
    patch: async () => {
      rows[0].is_read = true
      unread--
    },
    post: async () => {
      rows.forEach(row => {
        row.is_read = true
      })
      unread = 0
    }
  }
  const store = createNotificationStore({
    api,
    role: 'Consumer',
    getSession: () => session
  })
  return {
    store,
    api,
    requests,
    session: value => {
      session = value
    },
    rows: value => {
      rows = value
    },
    unread: value => {
      unread = value
    }
  }
}

test('unread badges use server totals beyond the preview and read mutations reconcile them', async () => {
  const h = storeHarness()
  await h.store.fetchNotifications()
  assert.equal(h.store.notifications.value.length, 1)
  assert.equal(h.store.unreadCount.value, 61)
  await h.store.markAsRead(1)
  assert.equal(h.store.unreadCount.value, 60)
  assert.equal(h.store.notifications.value[0].is_read, true)
  await h.store.markAllAsRead()
  assert.equal(h.store.unreadCount.value, 0)
  assert.equal(h.requests[0].options.headers['X-Background-Sync'], '1')
})

test('an old snapshot cannot undo a completed read and a concurrent new notice remains unread', async () => {
  const h = storeHarness()
  await h.store.fetchNotifications()
  const old = deferred()
  const normalGet = h.api.get
  h.api.get = () => old.promise
  const refresh = h.store.fetchNotifications()
  const read = h.store.markAllAsRead()
  await settle()
  h.api.get = normalGet
  h.rows([
    { notification_id: 1, is_read: true },
    { notification_id: 2, is_read: false }
  ])
  h.unread(1)
  old.resolve({
    data: {
      notifications: [{ notification_id: 1, is_read: false }],
      unread_count: 61
    }
  })
  await Promise.all([read, refresh])
  assert.equal(h.store.unreadCount.value, 1)
  assert.equal(h.store.notifications.value[0].is_read, true)
  assert.equal(h.store.notifications.value[1].is_read, false)
})

test('logout discards late snapshots and an old mutation cannot block a new account', async () => {
  const h = storeHarness()
  await h.store.fetchNotifications()
  const old = deferred()
  h.api.patch = () => old.promise
  const mutation = h.store.markAsRead(1)
  await settle()
  h.session({ token: 'second', role: 'Consumer' })
  h.store.reset()
  h.rows([{ notification_id: 99, is_read: false }])
  h.unread(1)
  await h.store.fetchNotifications()
  old.resolve()
  await mutation
  assert.equal(h.store.notifications.value[0].notification_id, 99)
  assert.equal(h.store.unreadCount.value, 1)
  const late = deferred()
  h.api.get = () => late.promise
  const refresh = h.store.fetchNotifications()
  h.session({ token: null })
  h.store.reset()
  late.resolve({
    data: { notifications: [{ notification_id: 99 }], unread_count: 1 }
  })
  await refresh
  assert.deepEqual(h.store.notifications.value, [])
})

test('failed read actions roll back and resynchronize without clearing the feed', async () => {
  const h = storeHarness()
  await h.store.fetchNotifications()
  h.api.patch = async () => {
    throw new Error('Network failure')
  }
  await assert.rejects(h.store.markAsRead(1), /Network failure/)
  assert.equal(h.store.unreadCount.value, 61)
  assert.equal(h.store.notifications.value[0].is_read, false)
})

test('a persisted read completes without waiting for a slow reconciliation request', async () => {
  const h = storeHarness()
  await h.store.fetchNotifications()
  const slow = deferred()
  h.api.get = () => slow.promise
  let complete = false
  const action = h.store.markAsRead(1).then(() => {
    complete = true
  })
  await settle()
  const didComplete = complete
  slow.resolve({
    data: {
      notifications: [{ notification_id: 1, is_read: true }],
      unread_count: 60
    }
  })
  await action
  assert.equal(
    didComplete,
    true,
    'notification navigation must only wait for persistence'
  )
})

test('read mutations have bounded request timeouts so an outage cannot freeze synchronization', async () => {
  const h = storeHarness()
  await h.store.fetchNotifications()
  const options = []
  h.api.patch = async (_url, _body, config) => {
    options.push(config)
  }
  h.api.post = async (_url, _body, config) => {
    options.push(config)
  }
  await h.store.markAsRead(1)
  await h.store.markAllAsRead()
  assert.equal(options.length, 2)
  assert.ok(options.every(config => config?.timeout > 0))
})
