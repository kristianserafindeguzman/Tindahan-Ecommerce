import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import test from 'node:test'
import { compileScript, parse } from '@vue/compiler-sfc'
import { date } from 'quasar'
import * as Vue from 'vue'
import { manilaParts } from '../src/utils/pickupSlots.js'

const element = type => ({ type, children: [], props: {}, text: '' })
const renderer = Vue.createRenderer({
  createElement: element,
  createText: text => ({ text }),
  createComment: () => ({ text: '' }),
  setText: (node, text) => {
    node.text = text
  },
  setElementText: (node, text) => {
    node.text = text
    node.children = []
  },
  patchProp: (node, key, prev, next) => {
    node.props[key] = next
  },
  insert: (node, parent, anchor) => {
    if (node.parent)
      node.parent.children.splice(node.parent.children.indexOf(node), 1)
    const at = anchor ? parent.children.indexOf(anchor) : -1
    parent.children.splice(at < 0 ? parent.children.length : at, 0, node)
    node.parent = parent
  },
  remove: node => {
    node.parent.children.splice(node.parent.children.indexOf(node), 1)
  },
  parentNode: node => node.parent,
  nextSibling: node =>
    node.parent?.children[node.parent.children.indexOf(node) + 1]
})
const nodes = node => [node, ...(node.children || []).flatMap(nodes)]
const text = node => [node.text, ...(node.children || []).map(text)].join(' ')
const hasClass = (node, name) =>
  String(node.props?.class || '')
    .split(' ')
    .includes(name)
const flush = async () => {
  await new Promise(resolve => setImmediate(resolve))
  await Vue.nextTick()
}

async function mountReport(t, api, now = '2026-10-07T12:00:00+08:00') {
  t.mock.method(Date, 'now', () =>
    Date.parse(typeof now === 'function' ? now() : now)
  )
  const source = readFileSync(
    new URL('../src/pages/Vendor/VendorSales.vue', import.meta.url),
    'utf8'
  )
  const { descriptor } = parse(source)
  const code = compileScript(descriptor, {
    id: 'sales-pagination-test',
    inlineTemplate: true
  })
    .content.replace(
      /import\s*\{([^}]+)\}\s*from\s*['"]vue['"]/g,
      (_, names) => `const {${names.replace(/\s+as\s+/g, ':')}} = Vue`
    )
    .replace(/import[^\n]+from ['"](?:@\/[^'"\n]+|quasar)['"]\r?\n/g, '')
    .replace('export default', 'return')
  const screen = Vue.reactive({ lt: { md: false, lg: false } })
  const notifications = []
  const component = new Function(
    'Vue',
    'api',
    'useQuasar',
    'date',
    'useLanguage',
    'useCategoryLabels',
    'OrderStatusBadge',
    'SkeletonTable',
    'manilaParts',
    code
  )(
    Vue,
    api,
    () => ({
      screen,
      notify: notification => notifications.push(notification)
    }),
    date,
    dict => ({ t: key => dict.en[key] }),
    () => ({ categoryLabel: name => name }),
    'order-status-badge',
    'skeleton-table',
    manilaParts
  )
  const root = element('root')
  const app = renderer.createApp(component)
  app.config.globalProperties.$q = { screen }
  app.config.warnHandler = () => {}
  for (const name of [
    'QPage',
    'QBtn',
    'QPopupProxy',
    'QDate',
    'QIcon',
    'QSkeleton',
    'QSelect',
    'QForm',
    'QInput',
    'QDialog',
    'QCard'
  ]) {
    app.component(name, {
      inheritAttrs: false,
      setup(props, { attrs, slots, expose }) {
        if (name === 'QPopupProxy') expose({ hide: () => {} })
        if (name === 'QForm') expose({ resetValidation: () => {} })
        return () =>
          name === 'QDialog' && !attrs.modelValue
            ? null
            : Vue.h(name === 'QBtn' ? 'button' : name, attrs, [
                attrs.label || '',
                ...(slots.default?.() || [])
              ])
      }
    })
  }
  app.mount(root)
  let unmounted = false
  const unmount = () => {
    if (!unmounted) app.unmount()
    unmounted = true
  }
  t.after(unmount)
  await flush()
  const findClass = name => nodes(root).find(node => hasClass(node, name))
  const selectDate = value => {
    const handlers = nodes(root).find(node => node.type === 'QDate').props[
      'onUpdate:modelValue'
    ]
    for (const handler of [handlers].flat()) handler(value)
  }
  return { root, screen, findClass, selectDate, notifications, unmount }
}

test('sales totals include all records while desktop and mobile pages reach every sale', async t => {
  let sales = Array.from({ length: 33 }, (_, index) => ({
    order_id: 1033 - index,
    product: `Product ${1033 - index}`,
    quantity: 2,
    total: 20,
    status: 'Picked up'
  }))
  const requests = []
  const api = {
    get: async (url, options) => {
      requests.push({ url, options })
      return {
        data: url.endsWith('/transactions')
          ? sales
          : url.endsWith('/metrics')
            ? { revenue: 660, avg_order_value: 20, cancellation_rate: 0 }
            : []
      }
    }
  }
  const { root, screen, findClass, selectDate } = await mountReport(t, api)
  const button = label =>
    nodes(root).find(node => node.props?.['aria-label'] === label)
  const next = () => button('Next page')
  const previous = () => button('Previous page')
  const rows = () =>
    nodes(root).filter(
      node =>
        node.type === 'tr' && node.children.some(child => child.type === 'td')
    )
  const ids = () => rows().map(row => Number(text(row).match(/#(\d+)/)[1]))
  const expectedIds = sales.map(row => row.order_id)
  const assertTotals = () => {
    assert.equal(text(findClass('sr-count')).trim(), '33')
    assert.match(text(findClass('sr-hero-facts')), /33 orders/)
    assert.match(text(findClass('sr-hero-facts')), /66 items/)
  }

  assertTotals()
  assert.match(text(findClass('vp-pager')), /Showing 1–10 of 33/)
  assert.equal(previous().props.disable, true)
  assert.equal(next().props.disable, false)
  const seen = [...ids()]
  for (const [start, end] of [
    [11, 20],
    [21, 30],
    [31, 33]
  ]) {
    next().props.onClick()
    await flush()
    assert.match(
      text(findClass('vp-pager')),
      new RegExp(`Showing ${start}–${end} of 33`)
    )
    seen.push(...ids())
    assertTotals()
  }
  assert.deepEqual(seen, expectedIds)
  assert.equal(next().props.disable, true)
  previous().props.onClick()
  await flush()
  assert.deepEqual(ids(), expectedIds.slice(20, 30))
  // Moving between pages uses the loaded result and never changes the totals.
  assert.equal(requests.length, 3)

  const pageSize = () =>
    nodes(root).find(
      node =>
        node.type === 'QSelect' &&
        node.props?.['aria-label'] === 'Rows per page'
    )
  pageSize().props['onUpdate:modelValue'](25)
  await flush()
  assert.deepEqual(ids(), expectedIds.slice(0, 25))
  assert.match(text(findClass('vp-pager')), /Showing 1–25 of 33/)
  next().props.onClick()
  await flush()
  assert.deepEqual(ids(), expectedIds.slice(25))
  assert.equal(next().props.disable, true)
  pageSize().props['onUpdate:modelValue'](50)
  await flush()
  assert.equal(rows().length, 33)
  assert.equal(previous().props.disable, true)
  assert.equal(next().props.disable, true)

  pageSize().props['onUpdate:modelValue'](10)
  screen.lt.md = true
  screen.lt.lg = true
  await flush()
  const mobileIds = () =>
    nodes(root)
      .filter(node => hasClass(node, 'sr-list-item'))
      .map(row => Number(text(row).match(/#(\d+)/)[1]))
  const mobileSeen = [...mobileIds()]
  for (let page = 2; page <= 4; page++) {
    next().props.onClick()
    await flush()
    mobileSeen.push(...mobileIds())
    assertTotals()
  }
  assert.deepEqual(mobileSeen, expectedIds)
  assert.equal(next().props.disable, true)

  // A new date with fewer records resets the old fourth page and hides the pager.
  sales = [
    {
      order_id: 2001,
      product: 'Water',
      quantity: 1,
      total: 20,
      status: 'Picked up'
    }
  ]
  selectDate('2026/10/05')
  await flush()
  assert.deepEqual(mobileIds(), [2001])
  assert.equal(findClass('vp-pager'), undefined)
  assert.match(text(findClass('sr-hero-facts')), /1 order/)
  assert.deepEqual(requests[4].options.params, {
    start_date: '2026-10-05',
    end_date: '2026-10-05'
  })

  // All time still paginates daily totals and resets when switching report types.
  sales = Array.from({ length: 12 }, (_, index) => ({
    sale_date: `Sep ${String(12 - index).padStart(2, '0')}, 2026`,
    total_items: 2,
    daily_revenue: 20
  }))
  nodes(root)
    .find(node => node.type === 'button' && node.props?.label === 'All time')
    .props.onClick()
  await flush()
  assert.match(text(findClass('vp-pager')), /Showing 1–10 of 12/)
  assert.match(text(findClass('sr-hero-facts')), /12 days/)
  next().props.onClick()
  await flush()
  assert.match(text(findClass('vp-pager')), /Showing 11–12 of 12/)
  assert.equal(
    nodes(root).filter(node => hasClass(node, 'sr-list-item')).length,
    2
  )
  assert.equal(next().props.disable, true)
  assert.deepEqual(requests[7].options.params, {})

  sales = []
  selectDate('2026/10/04')
  await flush()
  assert.match(text(root), /No sales found/)
  assert.equal(findClass('vp-pager'), undefined)
  assert.equal(text(findClass('sr-count')).trim(), '0')
})

test('chart samples follow sale recency and identify when only the latest orders are shown', async t => {
  // The newest sale can have an older ID; walk-in orders can be backdated.
  let sales = Array.from({ length: 33 }, (_, index) => ({
    order_id: index + 1,
    product: 'Water',
    quantity: 1,
    total: 20,
    status: 'Picked up'
  }))
  const { root, findClass, selectDate } = await mountReport(t, {
    get: async url => ({ data: url.endsWith('/transactions') ? sales : {} })
  })
  const titles = () =>
    nodes(root)
      .filter(node => hasClass(node, 'sr-bar'))
      .map(node => node.props.title)
  assert.equal(titles().length, 16)
  assert.match(titles()[0], /Order #16:/)
  assert.match(titles()[15], /Order #1:/)
  assert.match(text(findClass('sr-bars-label')), /Latest 16 orders/)
  assert.match(findClass('sr-bars').props['aria-label'], /latest 16 orders/)
  sales = sales.slice(0, 2)
  selectDate('2026/10/05')
  await flush()
  assert.match(text(findClass('sr-bars-label')), /Each order on this day/)

  sales = Array.from({ length: 18 }, (_, index) => ({
    sale_date: `Sep ${String(18 - index).padStart(2, '0')}, 2026`,
    total_items: 1,
    daily_revenue: 20
  }))
  nodes(root)
    .find(node => node.type === 'button' && node.props.label === 'All time')
    .props.onClick()
  await flush()
  assert.equal(titles().length, 14)
  assert.match(titles()[0], /Sep 05, 2026:/)
  assert.match(titles()[13], /Sep 18, 2026:/)
  assert.match(text(findClass('sr-bars-label')), /Latest 14 days with sales/)
})

test('loading and failed date changes hide stale sales, offer retry and ignore older failures', async t => {
  t.mock.method(console, 'error', () => {})
  const pending = []
  const api = {
    get: (url, options) =>
      new Promise((resolve, reject) =>
        pending.push({ url, options, resolve, reject })
      )
  }
  const { root, findClass, selectDate } = await mountReport(t, api)
  const resolveReport = (offset, id = 1033, revenue = 660) => {
    pending[offset].resolve({ data: { revenue, avg_order_value: revenue } })
    pending[offset + 1].resolve({
      data: [
        {
          order_id: id,
          product: 'Water',
          quantity: 2,
          total: revenue,
          status: 'Picked up'
        }
      ]
    })
    pending[offset + 2].resolve({ data: [] })
  }
  resolveReport(0)
  await flush()
  assert.match(text(root), /#1033/)
  selectDate('2026/10/05')
  await flush()
  assert.doesNotMatch(text(root), /660\.00/)
  assert.doesNotMatch(text(root), /#1033/)
  pending[3].resolve({ data: { revenue: 20 } })
  pending[4].reject(new Error('Offline'))
  pending[5].resolve({ data: [] })
  await flush()
  assert.match(text(root), /Unable to load sales/)
  assert.doesNotMatch(text(root), /#1033|660\.00|No sales found/)
  assert.equal(findClass('sr-count'), undefined)
  assert.equal(findClass('vp-pager'), undefined)
  assert.ok(nodes(root).some(node => node.props?.role === 'alert'))

  nodes(root)
    .find(node => node.type === 'button' && node.props.label === 'Try again')
    .props.onClick()
  await flush()
  assert.deepEqual(pending[7].options.params, {
    start_date: '2026-10-05',
    end_date: '2026-10-05'
  })
  resolveReport(6, 2001, 20)
  await flush()
  assert.match(text(root), /#2001/)
  assert.doesNotMatch(text(root), /Unable to load sales/)

  selectDate('2026/10/04')
  await flush()
  selectDate('2026/10/03')
  await flush()
  resolveReport(12, 3001, 30)
  await flush()
  pending[9].reject(new Error('Older request failed'))
  pending[10].resolve({ data: [] })
  pending[11].resolve({ data: [] })
  await flush()
  assert.match(text(root), /#3001/)
  assert.doesNotMatch(text(root), /Unable to load sales/)

  selectDate('2026/10/02')
  await flush()
  selectDate('2026/10/01')
  await flush()
  resolveReport(18, 4001, 40)
  await flush()
  resolveReport(15, 5001, 999)
  await flush()
  assert.match(text(root), /#4001/)
  assert.doesNotMatch(text(root), /#5001|999\.00/)
})

test('an initial request failure is distinct from a successfully loaded empty day', async t => {
  t.mock.method(console, 'error', () => {})
  let offline = true
  const { root, findClass } = await mountReport(t, {
    get: async url => {
      if (offline) throw new Error('Offline')
      return {
        data: url.endsWith('/metrics') ? { revenue: 0, revenue_growth: 0 } : []
      }
    }
  })
  assert.match(text(root), /Unable to load sales/)
  assert.doesNotMatch(text(root), /No sales found/)
  assert.equal(findClass('sr-hero'), undefined)
  offline = false
  nodes(root)
    .find(node => node.type === 'button' && node.props.label === 'Try again')
    .props.onClick()
  await flush()
  assert.match(text(root), /No sales found/)
  assert.doesNotMatch(text(root), /Unable to load sales/)
  assert.match(text(findClass('sr-hero-value')), /0\.00/)
  assert.equal(text(findClass('sr-count')).trim(), '0')
})

test('walk-in sale validation accepts cent prices and rejects excess precision and overflowing totals', async t => {
  const product = {
    inventory_id: 1,
    product_name: 'Water',
    price: 10,
    available_quantity: 10
  }
  const { root, findClass } = await mountReport(t, {
    get: async url => ({
      data: url.endsWith('/products')
        ? [product]
        : url.endsWith('/metrics')
          ? {}
          : []
    })
  })
  const update = (node, value) => {
    for (const handler of [node.props['onUpdate:modelValue']].flat())
      handler(value)
  }
  const price = () =>
    nodes(root).find(node => node.type === 'QInput' && node.props.min === '0')
  const quantity = () =>
    nodes(root).find(node => node.type === 'QInput' && node.props.min === '1')
  update(
    nodes(root).find(
      node =>
        node.type === 'QSelect' && node.props['option-value'] === 'inventory_id'
    ),
    product
  )
  update(price(), 0.29)
  update(quantity(), 2)
  await flush()
  assert.ok(price().props.rules.every(rule => rule(0.29) === true))
  assert.match(text(findClass('sr-estimate')), /0\.58/)
  update(price(), 0.005)
  await flush()
  assert.ok(price().props.rules.some(rule => rule(0.005) !== true))
  assert.match(text(findClass('sr-estimate')), /—/)
  update(price(), 600000)
  await flush()
  assert.ok(price().props.rules.some(rule => rule(600000) !== true))
  assert.match(text(findClass('sr-estimate')), /—/)
  update(quantity(), 1)
  update(price(), 999999.99)
  await flush()
  assert.ok(price().props.rules.every(rule => rule(999999.99) === true))
  assert.match(text(findClass('sr-estimate')), /999,999\.99/)
  assert.ok(price().props.rules.some(rule => rule(1000000) !== true))
})

test('future days lock desktop and mobile sale entry and cannot submit an old confirmation', async t => {
  const posts = []
  const { root, screen, findClass, selectDate } = await mountReport(t, {
    get: async url => ({ data: url.endsWith('/metrics') ? {} : [] }),
    post: async (url, payload) => {
      posts.push({ url, payload })
    }
  })
  assert.equal(nodes(root).filter(node => node.type === 'QForm').length, 1)
  const form = nodes(root).find(node => node.type === 'QForm')
  form.props.onSubmit({ preventDefault() {} })
  await flush()
  const confirm = nodes(root).find(
    node =>
      node.type === 'button' &&
      hasClass(node, 'vp-dialog-btn') &&
      node.props.label === 'Record Sale'
  )
  assert.ok(confirm)
  selectDate('2026/10/08')
  await flush()
  assert.equal(nodes(root).filter(node => node.type === 'QForm').length, 0)
  assert.match(text(findClass('sr-locked')), /Future date selected/)
  assert.match(text(findClass('sr-locked')), /today or a past date/)
  assert.equal(findClass('vp-dialog'), undefined)
  await confirm.props.onClick()
  assert.equal(posts.length, 0)

  screen.lt.lg = true
  screen.lt.md = true
  await flush()
  nodes(root)
    .find(node => node.type === 'button' && node.props.label === 'Record Sale')
    .props.onClick()
  await flush()
  assert.match(text(findClass('sr-dialog')), /Future date selected/)
  assert.equal(nodes(root).filter(node => node.type === 'QForm').length, 0)
  selectDate('2026/10/07')
  await flush()
  assert.equal(nodes(root).filter(node => node.type === 'QForm').length, 1)
  selectDate('2026/10/06')
  await flush()
  assert.equal(nodes(root).filter(node => node.type === 'QForm').length, 1)
  nodes(root)
    .find(node => node.type === 'button' && node.props.label === 'All time')
    .props.onClick()
  await flush()
  assert.equal(nodes(root).filter(node => node.type === 'QForm').length, 0)
  assert.match(text(findClass('sr-locked')), /Pick a day first/)
})

test('the sales calendar uses Manila today when the UTC day is different', async t => {
  const requests = []
  const { root, findClass, selectDate } = await mountReport(
    t,
    {
      get: async (url, options) => {
        requests.push({ url, options })
        return { data: url.endsWith('/metrics') ? {} : [] }
      }
    },
    '2026-10-06T16:15:00Z'
  )
  assert.deepEqual(requests[0].options.params, {
    start_date: '2026-10-07',
    end_date: '2026-10-07'
  })
  assert.equal(nodes(root).filter(node => node.type === 'QForm').length, 1)
  selectDate('2026/10/08')
  await flush()
  assert.match(text(findClass('sr-locked')), /Future date selected/)
})

test('a valid sale submits once, refreshes the totals and clears the entry form', async t => {
  const product = {
    inventory_id: 1,
    product_name: 'Water',
    price: 10,
    available_quantity: 10
  }
  let rows = []
  let resolveSale
  const posts = []
  const { root, findClass, notifications } = await mountReport(t, {
    get: async url => ({
      data: url.endsWith('/products')
        ? [product]
        : url.endsWith('/metrics')
          ? { revenue: rows.length ? 0.58 : 0, avg_order_value: 0.58 }
          : rows
    }),
    post: (url, payload) =>
      new Promise(resolve => {
        posts.push({ url, payload })
        resolveSale = resolve
      })
  })
  const update = (node, value) => {
    for (const handler of [node.props['onUpdate:modelValue']].flat())
      handler(value)
  }
  update(
    nodes(root).find(
      node =>
        node.type === 'QSelect' && node.props['option-value'] === 'inventory_id'
    ),
    product
  )
  update(
    nodes(root).find(node => node.type === 'QInput' && node.props.min === '0'),
    0.29
  )
  update(
    nodes(root).find(node => node.type === 'QInput' && node.props.min === '1'),
    2
  )
  await flush()
  nodes(root)
    .find(node => node.type === 'QForm')
    .props.onSubmit({ preventDefault() {} })
  await flush()
  const confirm = nodes(root).find(
    node =>
      node.type === 'button' &&
      hasClass(node, 'vp-dialog-btn') &&
      node.props.label === 'Record Sale'
  )
  const saving = confirm.props.onClick()
  await confirm.props.onClick()
  assert.equal(posts.length, 1)
  assert.deepEqual(posts[0], {
    url: '/vendor/sales/manual',
    payload: {
      inventory_id: 1,
      quantity: 2,
      unit_price: 0.29,
      total_amount: 0.58,
      sale_date: '2026-10-07'
    }
  })
  rows = [
    {
      order_id: 2001,
      product: 'Water',
      quantity: 2,
      total: 0.58,
      status: 'Picked up'
    }
  ]
  resolveSale({ data: { message: 'Manual sale recorded successfully' } })
  await saving
  await flush()
  assert.equal(notifications.length, 1)
  assert.equal(notifications[0].type, 'positive')
  assert.match(text(findClass('sr-hero-facts')), /1 order/)
  assert.match(text(findClass('sr-hero-facts')), /2 items/)
  assert.match(text(findClass('sr-hero-value')), /0\.58/)
  assert.equal(findClass('vp-dialog'), undefined)
  assert.equal(
    nodes(root).find(
      node =>
        node.type === 'QSelect' && node.props['option-value'] === 'inventory_id'
    ).props.modelValue,
    null
  )
  assert.equal(
    nodes(root).find(node => node.type === 'QInput' && node.props.min === '1')
      .props.modelValue,
    1
  )
})

test('future entry unlocks at Manila midnight and the clock stops when leaving the page', async t => {
  let tick
  const cleared = []
  t.mock.method(globalThis, 'setInterval', (callback, delay) => {
    assert.equal(delay, 60000)
    tick = callback
    return 42
  })
  t.mock.method(globalThis, 'clearInterval', timer => cleared.push(timer))
  let now = '2026-10-07T23:59:00+08:00'
  const { root, findClass, selectDate, unmount } = await mountReport(
    t,
    {
      get: async url => ({ data: url.endsWith('/metrics') ? {} : [] })
    },
    () => now
  )
  selectDate('2026/10/08')
  await flush()
  assert.match(text(findClass('sr-locked')), /Future date selected/)
  now = '2026-10-08T00:00:00+08:00'
  tick()
  await flush()
  assert.equal(findClass('sr-locked'), undefined)
  assert.equal(nodes(root).filter(node => node.type === 'QForm').length, 1)
  unmount()
  assert.deepEqual(cleared, [42])
})

test('a report request completing after the page is closed cannot bring its UI back', async t => {
  const pending = []
  const { root, unmount } = await mountReport(t, {
    get: () => new Promise(resolve => pending.push(resolve))
  })
  assert.equal(pending.length, 3)
  unmount()
  pending[0]({ data: { revenue: 100 } })
  pending[1]({
    data: [{ order_id: 2001, product: 'Water', quantity: 1, total: 100 }]
  })
  pending[2]({ data: [] })
  await flush()
  assert.equal(nodes(root).filter(node => node.type === 'QPage').length, 0)
})
