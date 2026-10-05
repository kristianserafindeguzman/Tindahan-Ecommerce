import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import test from 'node:test'
import { compileScript, parse } from '@vue/compiler-sfc'
import * as Vue from 'vue'

test('rankings use selected periods, refresh, open matching reports and cancel stale requests', async () => {
  const { descriptor } = parse(
    readFileSync(
      new URL('../src/components/admin/VendorPerformance.vue', import.meta.url),
      'utf8'
    )
  )
  const code = compileScript(descriptor, {
    id: 'rankings-test',
    inlineTemplate: true
  })
    .content.replace(
      /import\s*\{([^}]+)\}\s*from\s*['"]vue['"]/g,
      (_, names) => `const {${names.replace(/\s+as\s+/g, ':')}} = Vue`
    )
    .replace(/import[^\n]+from ['"]@\/[^'"\n]+['"]\r?\n/g, '')
    .replace('export default', 'return')
  const pending = []
  let poll
  let cleared = false
  const document = { hidden: false }
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
    'setInterval',
    'clearInterval',
    'document',
    code
  )(
    Vue,
    api,
    () => ({ lang: Vue.ref('en') }),
    (fn, delay) => {
      assert.equal(delay, 15000)
      poll = fn
      return 1
    },
    () => {
      cleared = true
    },
    document
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
  let opened
  const app = renderer.createApp({
    render: () =>
      Vue.h(component, {
        onView: value => {
          opened = value
        }
      })
  })
  app.config.warnHandler = () => {}
  app.mount(root)
  const flush = async () => {
    await Promise.resolve()
    await Vue.nextTick()
  }
  const nodes = node => [node, ...(node.children || []).flatMap(nodes)]
  const text = node => [node.text, ...(node.children || []).map(text)].join(' ')
  const row = {
    store_id: 1,
    store_name: 'Nena Store',
    owner_name: 'Nena',
    revenue: 500,
    completed_orders: 4,
    account_status: 'active',
    rank: 1
  }
  const data = {
    start_date: '2026-10-01',
    end_date: '2026-10-05',
    generated_at: '2026-10-05T00:00:00Z',
    eligible_stores: 2,
    stores_without_sales: 1,
    top: [row],
    least: [
      {
        ...row,
        store_id: 2,
        store_name: 'Zero Store',
        revenue: 0,
        completed_orders: 0,
        rank: 2
      }
    ]
  }
  assert.equal(pending[0].url, '/admin/vendors/performance')
  assert.equal(pending[0].options.params.metric, 'revenue')
  assert.equal(
    (Date.parse(pending[0].options.params.end_date) -
      Date.parse(pending[0].options.params.start_date)) /
      86400000,
    29
  )
  pending[0].resolve({ data })
  await flush()
  assert.match(text(root), /Nena Store/)
  assert.match(text(root), /Zero Store/)
  assert.match(text(root), /No recorded sales/)
  nodes(root)
    .find(
      node =>
        node.type === 'button' &&
        node.props['aria-label']?.includes('Nena Store')
    )
    .props.onClick()
  assert.deepEqual(opened, {
    storeId: 1,
    startDate: data.start_date,
    endDate: data.end_date
  })
  const rankBy = nodes(root).find(node => node.props?.label === 'Rank by')
  rankBy.props['onUpdate:modelValue']('completed_orders')
  await flush()
  assert.equal(pending[1].options.params.metric, 'completed_orders')
  const period = nodes(root).find(node => node.props?.label === 'Period')
  period.props['onUpdate:modelValue'](7)
  await flush()
  assert.equal(pending[1].options.signal.aborted, true)
  assert.equal(
    (Date.parse(pending[2].options.params.end_date) -
      Date.parse(pending[2].options.params.start_date)) /
      86400000,
    6
  )
  pending[1].resolve({
    data: { ...data, top: [{ ...row, store_name: 'Old result' }] }
  })
  pending[2].resolve({ data })
  await flush()
  assert.doesNotMatch(text(root), /Old result/)
  poll()
  pending[3].resolve({
    data: { ...data, top: [{ ...row, completed_orders: 12 }] }
  })
  await flush()
  assert.match(text(root), /12/)
  poll()
  pending[4].reject(new Error('Offline'))
  await flush()
  assert.match(text(root), /Refresh failed/)
  assert.match(text(root), /Nena Store/)
  document.hidden = true
  poll()
  assert.equal(pending.length, 5)
  document.hidden = false
  poll()
  app.unmount()
  assert.equal(pending[5].options.signal.aborted, true)
  assert.equal(cleared, true)
})
