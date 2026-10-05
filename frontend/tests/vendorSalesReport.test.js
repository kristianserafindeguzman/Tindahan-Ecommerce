import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import test from 'node:test'
import { compileScript, parse } from '@vue/compiler-sfc'
import * as Vue from 'vue'

test('vendor report fetches, polls, handles errors, switches stores and stops when closed', async () => {
  const source = readFileSync(
    new URL('../src/components/admin/VendorSalesReport.vue', import.meta.url),
    'utf8'
  )
  const { descriptor } = parse(source)
  let code = compileScript(descriptor, {
    id: 'sales-test',
    inlineTemplate: true
  }).content
  code = code
    .replace(
      /import\s*\{([^}]+)\}\s*from\s*['"]vue['"]/g,
      (_, names) => `const {${names.replace(/\s+as\s+/g, ':')}} = Vue`
    )
    .replace(/import[^\n]+from ['"]@\/[^'"\n]+['"]\r?\n/g, '')
    .replace(/import[^\n]+from ['"](?:quasar|vue3-apexcharts)['"]\r?\n/g, '')
    .replace('export default', 'return')
  const pending = []
  let poll
  let cleared = 0
  const dark = Vue.reactive({ isActive: false })
  const api = {
    get: (url, options) =>
      new Promise((resolve, reject) =>
        pending.push({ url, options, resolve, reject })
      )
  }
  const component = new Function(
    'Vue',
    'api',
    'useLanguage',
    'useQuasar',
    'VueApexCharts',
    'setInterval',
    'clearInterval',
    code
  )(
    Vue,
    api,
    () => ({ lang: Vue.ref('en') }),
    () => ({ dark }),
    {
      props: ['series', 'options'],
      render() {
        return Vue.h('test-chart', {
          series: this.series,
          options: this.options
        })
      }
    },
    (fn, delay) => {
      assert.equal(delay, 15000)
      poll = fn
      return 1
    },
    () => {
      cleared++
    }
  )
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
  const root = element('root')
  const props = Vue.reactive({
    active: true,
    storeId: 1,
    initialStart: '2026-10-01',
    initialEnd: '2026-10-05'
  })
  const app = renderer.createApp({ render: () => Vue.h(component, props) })
  app.config.warnHandler = () => {}
  app.mount(root)
  const flush = async () => {
    await Promise.resolve()
    await Vue.nextTick()
  }
  const text = node => [node.text, ...(node.children || []).map(text)].join(' ')
  const nodes = node => [node, ...(node.children || []).flatMap(nodes)]
  const chart = () => nodes(root).find(node => node.type === 'test-chart')
  const report = (revenue, store = 1) => ({
    store_id: store,
    start_date: '2026-10-01',
    end_date: '2026-10-05',
    generated_at: '2026-10-05T00:00:00Z',
    metrics: {
      revenue,
      completed_orders: 1,
      units_sold: 2,
      average_order_value: revenue,
      cancelled_orders: 0,
      cancellation_rate: 0
    },
    daily: [{ date: '2026-10-05', revenue, orders: 1 }],
    recent_sales: [
      { order_id: 1, sold_at: '2026-10-05T00:00:00Z', units: 2, total: revenue }
    ]
  })
  assert.equal(pending[0].url, '/admin/vendors/1/sales')
  assert.deepEqual(pending[0].options.params, {
    start_date: '2026-10-01',
    end_date: '2026-10-05'
  })
  pending[0].resolve({ data: report(123) })
  await flush()
  assert.match(text(root), /123\.00/)
  assert.deepEqual(chart().props.series[0].data, [
    { x: Date.parse('2026-10-05T00:00:00Z'), y: 123 }
  ])
  const ordersToggle = nodes(root).find(
    node => node.type === 'button' && text(node).trim() === 'Orders'
  )
  ordersToggle.props.onClick()
  await flush()
  assert.equal(chart().props.series[0].data[0].y, 1)
  assert.equal(chart().props.series[0].name, 'Orders')
  dark.isActive = true
  await flush()
  assert.equal(chart().props.options.tooltip.theme, 'dark')
  nodes(root)
    .find(node => node.type === 'button' && text(node).trim() === 'Revenue')
    .props.onClick()
  await flush()
  poll()
  pending[1].resolve({ data: report(456) })
  await flush()
  assert.match(text(root), /456\.00/)
  assert.equal(chart().props.series[0].data[0].y, 456)
  poll()
  pending[2].reject(new Error('Offline'))
  await flush()
  assert.match(text(root), /Unable to refresh/)
  assert.match(text(root), /456\.00/)
  poll()
  props.storeId = 2
  await flush()
  assert.equal(pending[3].options.signal.aborted, true)
  assert.equal(pending[4].url, '/admin/vendors/2/sales')
  pending[3].resolve({ data: report(999) })
  pending[4].resolve({ data: report(222, 2) })
  await flush()
  assert.match(text(root), /222\.00/)
  assert.doesNotMatch(text(root), /999\.00/)
  const from = nodes(root).find(node => node.props?.label === 'From')
  const to = nodes(root).find(node => node.props?.label === 'To')
  const apply = nodes(root).find(node => node.props?.label === 'Apply')
  from.props['onUpdate:modelValue']('2026-10-05')
  to.props['onUpdate:modelValue']('2026-10-01')
  await flush()
  apply.props.onClick()
  await flush()
  assert.match(text(root), /Choose a valid date range/)
  assert.equal(pending.length, 5)
  from.props['onUpdate:modelValue']('2026-10-01')
  to.props['onUpdate:modelValue']('2026-10-05')
  await flush()
  apply.props.onClick()
  assert.deepEqual(pending[5].options.params, {
    start_date: '2026-10-01',
    end_date: '2026-10-05'
  })
  pending[5].resolve({ data: report(333, 2) })
  await flush()
  assert.match(text(root), /333\.00/)
  nodes(root)
    .find(node => node.type === 'button' && text(node).trim() === 'Last 7 days')
    .props.onClick()
  const range = pending[6].options.params
  assert.equal(
    (Date.parse(range.end_date) - Date.parse(range.start_date)) / 86400000,
    6
  )
  pending[6].resolve({ data: report(444, 2) })
  await flush()
  assert.equal(chart().props.series[0].data[0].y, 444)
  poll()
  props.active = false
  await flush()
  assert.equal(pending[7].options.signal.aborted, true)
  const count = pending.length
  poll()
  assert.equal(pending.length, count)
  assert.ok(cleared >= 2)
  app.unmount()
})
