import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import test from 'node:test'
import { compileScript, parse } from '@vue/compiler-sfc'
import * as Vue from 'vue'

const flush = async () => {
  await new Promise(resolve => setImmediate(resolve))
  await Vue.nextTick()
}

async function mountDashboard(t) {
  const { descriptor } = parse(
    readFileSync(
      new URL('../src/pages/Vendor/VendorDashboard.vue', import.meta.url),
      'utf8'
    )
  )
  const code = compileScript(descriptor, { id: 'revenue-chart-test' })
    .content.replace(
      /import\s*\{([^}]+)\}\s*from\s*['"]vue['"]/g,
      (_, names) => 'const {' + names.replace(/\s+as\s+/g, ':') + '} = Vue'
    )
    .replace(/^import[^\n]*\r?\n/gm, '')
    .replace('export default', 'return')
  const pending = []
  const intervals = new Map()
  const cleared = []
  const document = { hidden: false }
  let timerId = 0
  const api = {
    get: (url, options) => {
      if (url === '/vendor/stats/chart') {
        return new Promise((resolve, reject) => {
          pending.push({ options, resolve, reject })
        })
      }
      return Promise.resolve({
        data: url === '/vendor/products' ? [] : {}
      })
    }
  }
  const deps = {
    api,
    useRouter: () => ({ push() {} }),
    useQuasar: () => ({ screen: { lt: { md: false } } }),
    useLanguage: dict => ({
      t: key => dict.en[key],
      lang: Vue.ref('en')
    }),
    useVendorNotifications: () => ({ unreadCount: Vue.ref(0) }),
    statusIcon: () => '',
    VueApexCharts: {},
    NotificationsPanel: {},
    SkeletonTable: {},
    L: {},
    markerIconUrl: '',
    markerIcon2xUrl: '',
    markerShadowUrl: '',
    document,
    setInterval: (fn, delay) => {
      const id = ++timerId
      intervals.set(id, { fn, delay })
      return id
    },
    clearInterval: id => {
      cleared.push(id)
      intervals.delete(id)
    }
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
  component.render = () => Vue.h('dashboard')
  const renderer = Vue.createRenderer({
    createElement: type => ({ type, children: [] }),
    createText: text => ({ text }),
    createComment: text => ({ text }),
    setText: (node, text) => {
      node.text = text
    },
    setElementText: (node, text) => {
      node.text = text
    },
    patchProp() {},
    insert: (node, parent) => {
      parent.children.push(node)
      node.parent = parent
    },
    remove: node => {
      node.parent.children.splice(node.parent.children.indexOf(node), 1)
    },
    parentNode: node => node.parent,
    nextSibling: () => null
  })
  const app = renderer.createApp(component)
  app.config.warnHandler = () => {}
  app.mount({ children: [] })
  let unmounted = false
  const unmount = () => {
    if (unmounted) return
    app.unmount()
    unmounted = true
  }
  t.after(unmount)
  await flush()
  return { state, pending, intervals, cleared, document, unmount }
}

test('revenue filters ignore late responses and keep numeric totals with matching dates', async t => {
  const { state, pending } = await mountDashboard(t)
  const daily = pending.shift()
  assert.equal(daily.options.params.filter, 'Daily')

  state.activeRevenueFilter.value = 'Weekly'
  await flush()
  const weekly = pending.shift()
  assert.equal(daily.options.signal.aborted, true)

  state.activeRevenueFilter.value = 'Monthly'
  await flush()
  const monthly = pending.shift()
  assert.equal(monthly.options.params.filter, 'Monthly')
  assert.equal(weekly.options.signal.aborted, true)

  weekly.resolve({ data: [{ period: 'Oct 05, 2026', total: 200 }] })
  await flush()
  assert.equal(state.chartLoading.value, true)
  assert.deepEqual(state.chartSeries.value[0].data, [])

  monthly.resolve({ data: [{ period: 'Oct 2026', total: '40.50' }] })
  await flush()
  assert.equal(state.chartLoading.value, false)
  assert.deepEqual(state.chartSeries.value[0].data, [40.5])
  assert.equal(state.totalRevenue.value, 40.5)
  assert.deepEqual(state.chartOptions.value.xaxis.categories, ['Oct 2026'])

  daily.resolve({ data: [{ period: 'Oct 07, 2026', total: 100 }] })
  await flush()
  assert.deepEqual(state.chartSeries.value[0].data, [40.5])
  assert.deepEqual(state.chartOptions.value.xaxis.categories, ['Oct 2026'])
})

test('revenue polling updates visible dashboards and stops pending work on unmount', async t => {
  const { state, pending, intervals, cleared, document, unmount } =
    await mountDashboard(t)
  const initial = pending.shift()
  initial.resolve({ data: [{ period: 'Oct 07, 2026', total: 20 }] })
  await flush()

  const [pollId, { fn: poll }] = [...intervals].find(
    ([, timer]) => timer.delay === 15000
  )
  document.hidden = true
  poll()
  assert.equal(pending.length, 0)

  document.hidden = false
  poll()
  const refresh = pending.shift()
  assert.equal(state.chartLoading.value, false)
  poll()
  assert.equal(pending.length, 0)
  refresh.resolve({ data: [{ period: 'Oct 07, 2026', total: 40 }] })
  await flush()
  assert.equal(state.totalRevenue.value, 40)

  poll()
  const unfinished = pending.shift()
  unmount()
  assert.equal(unfinished.options.signal.aborted, true)
  assert.equal(cleared.includes(pollId), true)
  assert.equal(intervals.size, 0)

  unfinished.resolve({ data: [{ period: 'Oct 07, 2026', total: 999 }] })
  await flush()
  assert.equal(state.totalRevenue.value, 40)
})
