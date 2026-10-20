import test from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { compileScript, parse } from '@vue/compiler-sfc'
import * as Vue from 'vue'
import {
  CONSUMER_ORDER_SELECTED,
  selectConsumerOrder
} from '../src/utils/consumerOrderSelection.js'

const settle = () => new Promise(resolve => setImmediate(resolve))

async function mountDetails(t) {
  const { descriptor } = parse(
    readFileSync(
      new URL(
        '../src/pages/Consumer/ConsumerOrderDetails.vue',
        import.meta.url
      ),
      'utf8'
    )
  )
  const code = compileScript(descriptor, { id: 'consumer-realtime-test' })
    .content.replace(
      /import\s*\{([^}]+)\}\s*from\s*['"]vue['"]/g,
      (_, names) => 'const {' + names.replace(/\s+as\s+/g, ':') + '} = Vue'
    )
    .replace(/import[\s\S]*?from\s*['"][^'"]+['"];?/g, '')
    .replace('export default', 'return')
  const requests = []
  const routes = []
  const saved = { consumer_selected_order_id: '1' }
  let backgroundRefresh
  const deps = {
    window: new EventTarget(),
    CONSUMER_ORDER_SELECTED,
    localStorage: {
      getItem: key => saved[key],
      setItem: (key, value) => {
        saved[key] = String(value)
      },
      removeItem: key => {
        delete saved[key]
      }
    },
    api: {
      get: url =>
        new Promise((resolve, reject) => {
          requests.push({ url, resolve, reject })
        })
    },
    useRouter: () => ({
      replace: path => routes.push(path),
      push: path => routes.push(path)
    }),
    useQuasar: () => ({ screen: { lt: { sm: false } }, notify() {} }),
    useConsumerLanguage: () => ({
      t: value => value,
      locale: Vue.ref('en-PH')
    }),
    useLiveResource: (_topics, callback) => {
      backgroundRefresh = callback
    },
    backgroundOptions: () => ({}),
    formatDistance: () => '',
    calculateDistanceMeters: () => 0,
    formatPickupSlot: () => '',
    SiteHeader: {},
    SiteFooter: {}
  }
  const component = new Function(
    'Vue',
    'deps',
    'const { ' + Object.keys(deps).join(', ') + ' } = deps\n' + code
  )(Vue, deps)
  let state
  const setup = component.setup
  component.setup = (props, context) => {
    state = setup(props, context)
    return state
  }
  component.render = () => Vue.h('details')
  const renderer = Vue.createRenderer({
    createElement: type => ({ type, children: [] }),
    createText: text => ({ text }),
    createComment: text => ({ text }),
    setText() {},
    setElementText() {},
    patchProp() {},
    insert: (node, parent) => {
      parent.children.push(node)
      node.parent = parent
    },
    remove: node =>
      node.parent.children.splice(node.parent.children.indexOf(node), 1),
    parentNode: node => node.parent,
    nextSibling: () => null
  })
  const app = renderer.createApp(component)
  app.mount({ children: [] })
  let mounted = true
  const unmount = () => {
    if (mounted) {
      app.unmount()
      mounted = false
    }
  }
  t.after(unmount)
  await settle()
  return {
    state,
    requests,
    routes,
    saved,
    unmount,
    window: deps.window,
    storage: deps.localStorage,
    backgroundRefresh: () => backgroundRefresh({ background: true })
  }
}

test('a delayed manual refresh cannot overwrite a newer WebSocket order update', async t => {
  const h = await mountDetails(t)
  h.requests[0].resolve({ data: { order_id: 1, status: 'placed' } })
  await settle()
  const manual = h.state.refreshOrder()
  const background = h.backgroundRefresh()
  h.requests[2].resolve({ data: { order_id: 1, status: 'preparing' } })
  await background
  h.requests[1].resolve({ data: { order_id: 1, status: 'placed' } })
  await manual
  assert.equal(h.state.order.value.status, 'preparing')
})

test('a failed request for an earlier order cannot clear the current selection or redirect', async t => {
  const h = await mountDetails(t)
  h.saved.consumer_selected_order_id = '2'
  h.window.dispatchEvent(new Event(CONSUMER_ORDER_SELECTED))
  h.requests[1].resolve({ data: { order_id: 2, status: 'preparing' } })
  await settle()
  h.requests[0].reject({ response: { status: 403 } })
  await settle()
  assert.equal(h.saved.consumer_selected_order_id, '2')
  assert.deepEqual(h.routes, [])
  assert.equal(h.state.order.value.order_id, 2)
})

test('leaving the detail page cancels the effects of late authorization errors', async t => {
  const h = await mountDetails(t)
  h.unmount()
  h.requests[0].reject({ response: { status: 404 } })
  await settle()
  assert.deepEqual(h.routes, [])
  assert.equal(h.saved.consumer_selected_order_id, '1')
})

test('selecting another notification order reloads an already mounted detail page', async t => {
  const h = await mountDetails(t)
  h.requests[0].resolve({ data: { order_id: 1, status: 'placed' } })
  await settle()
  const previousWindow = globalThis.window
  const previousStorage = globalThis.localStorage
  try {
    globalThis.window = h.window
    globalThis.localStorage = h.storage
    selectConsumerOrder(2)
  } finally {
    globalThis.window = previousWindow
    globalThis.localStorage = previousStorage
  }
  await settle()
  assert.equal(h.requests.length, 2)
  assert.equal(h.requests[1].url, '/consumer/orders/2')
  h.requests[1].resolve({ data: { order_id: 2, status: 'preparing' } })
  await settle()
  assert.equal(h.state.order.value.order_id, 2)
})

test('another tab selecting an order does not replace this tabs open order during synchronization', async t => {
  const h = await mountDetails(t)
  h.requests[0].resolve({ data: { order_id: 1, status: 'placed' } })
  await settle()
  h.saved.consumer_selected_order_id = '2'
  const refresh = h.backgroundRefresh()
  const requestedUrl = h.requests[1].url
  h.requests[1].resolve({ data: { order_id: 1, status: 'preparing' } })
  await refresh
  assert.equal(requestedUrl, '/consumer/orders/1')
})
