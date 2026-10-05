import assert from 'node:assert/strict'
import { readFile } from 'node:fs/promises'
import { test } from 'node:test'
import * as Vue from 'vue'
import { parse, compileScript } from '@vue/compiler-sfc'
import { parse as parseJavaScript } from '@babel/parser'
import { useConsumerLanguage } from '../src/composables/useConsumerLanguage.js'

const vueExports = { ...Vue }

// Run the actual component script with Leaflet and timers supplied by the harness.
async function loadModule(path, bindings, exportedName) {
  const source = await readFile(new URL(path, import.meta.url), 'utf8')
  const script = path.endsWith('.vue')
    ? compileScript(parse(source).descriptor, { id: 'consumer-map' }).content
    : source
  const ast = parseJavaScript(script, { sourceType: 'module' })
  const dependencies = { ...bindings }
  const edits = []
  for (const node of ast.program.body) {
    if (node.type === 'ImportDeclaration') {
      for (const specifier of node.specifiers) {
        dependencies[specifier.local.name] =
          node.source.value === 'vue'
            ? vueExports[specifier.imported.name]
            : bindings[specifier.local.name]
      }
      edits.push([node.start, node.end, ''])
    } else if (node.type === 'ExportDefaultDeclaration') {
      edits.push([node.start, node.declaration.start, 'return '])
    } else if (node.type === 'ExportNamedDeclaration') {
      edits.push([node.start, node.declaration.start, ''])
    }
  }
  let code = script
  for (const [start, end, replacement] of edits.sort((a, b) => b[0] - a[0])) {
    code = code.slice(0, start) + replacement + code.slice(end)
  }
  if (exportedName) code += `\nreturn ${exportedName}`
  return new Function(...Object.keys(dependencies), code)(
    ...Object.values(dependencies)
  )
}

const renderer = Vue.createRenderer({
  createElement: () => ({}),
  createText: () => ({}),
  createComment: () => ({}),
  setText() {},
  setElementText() {},
  patchProp() {},
  insert() {},
  remove() {},
  parentNode: () => null,
  nextSibling: () => null
})

async function mapHarness() {
  const stores = Vue.ref([
    {
      id: 1,
      name: 'Closed Store',
      isOpen: false,
      scheduleStatusText: 'Closed now',
      latitude: 14.6,
      longitude: 121
    },
    {
      id: 2,
      name: 'Open Store',
      isOpen: true,
      scheduleStatusText: 'Open until 9:00 PM',
      latitude: 14.61,
      longitude: 121
    },
    {
      id: 3,
      name: 'No Location',
      isOpen: true,
      latitude: null,
      longitude: null
    }
  ])
  const shown = Vue.ref(true)
  const timers = new Map()
  const maps = []
  const requests = []
  const location = {}
  const locationVersion = Vue.ref(0)
  const address = Vue.ref('')
  const createdMarkers = []
  let timerId = 0
  let state
  let fetch = async options => {
    requests.push(options)
  }
  const language = useConsumerLanguage()
  language.setLanguage('en')
  const component = await loadModule('../src/pages/Consumer/ConsumerMap.vue', {
    useConsumerLanguage,
    useRouter: () => ({ push() {} }),
    useStores: () => ({
      stores,
      loading: Vue.ref(false),
      fetchStores: options => fetch(options)
    }),
    useAddress: () => ({ address, locationVersion }),
    formatDistance: String,
    formatTravelTime: String,
    localStorage: { getItem: key => location[key] ?? null },
    setInterval: (fn, ms) => {
      timers.set(++timerId, { fn, ms })
      return timerId
    },
    clearInterval: id => timers.delete(id),
    L: {
      divIcon: options => options,
      tileLayer: () => ({ addTo() {} }),
      map: () => {
        const map = {
          events: {},
          setView(coords) {
            this.center = coords
            return this
          },
          getZoom() {
            return 15
          },
          on(event, fn) {
            this.events[event] = fn
          },
          invalidateSize() {},
          remove() {
            this.removed = true
          },
          removeLayer(marker) {
            marker.removed = true
            if (marker.popupOpen) this.events.popupclose?.()
          }
        }
        maps.push(map)
        return map
      },
      marker: (coords, options) => {
        const marker = {
          coords,
          options,
          addTo(map) {
            this.map = map
            return this
          },
          bindPopup(html) {
            this.html = html
            return this
          },
          setPopupContent(html) {
            this.html = html
          },
          setLatLng(coords) {
            this.coords = coords
          },
          setIcon(icon) {
            this.options.icon = icon
          },
          getLatLng() {
            return this.coords
          },
          getElement() {
            return {
              setAttribute: (key, value) => {
                this.options[key] = value
              }
            }
          },
          openPopup() {
            this.popupOpen = true
            this.map.events.popupopen?.()
          },
          isPopupOpen() {
            return !!this.popupOpen
          },
          getPopup() {
            return {
              update: () => {
                this.popupUpdates = (this.popupUpdates || 0) + 1
              }
            }
          }
        }
        createdMarkers.push(marker)
        return marker
      }
    }
  })
  const originalSetup = component.setup
  component.setup = (props, context) => {
    state = originalSetup(props, context)
    state.mapEl.value = {}
    return () => null
  }
  const app = renderer.createApp({
    setup: () => () => Vue.h(component, { modelValue: shown.value })
  })
  app.mount({})
  return {
    state,
    stores,
    shown,
    timers,
    maps,
    requests,
    language,
    app,
    location,
    locationVersion,
    address,
    createdMarkers,
    setFetch: fn => {
      fetch = fn
    }
  }
}

test('map status filters and search narrow both the list and pins', async t => {
  const h = await mapHarness()
  t.after(() => h.app.unmount())
  await h.state.onDialogShow()
  assert.deepEqual(
    h.state.statusFilters.value.map(filter => filter.count),
    [3, 2, 1]
  )
  assert.deepEqual(Object.keys(h.state.markersById), ['1', '2'])
  assert.match(h.state.markersById[1].options.icon.className, /marker-closed/)
  assert.match(h.state.markersById[2].html, /Open until 9:00 PM/)

  h.state.statusFilter.value = 'open'
  await Vue.nextTick()
  assert.deepEqual(
    h.state.filteredStores.value.map(store => store.id),
    [2, 3]
  )
  assert.deepEqual(Object.keys(h.state.markersById), ['2'])

  h.state.statusFilter.value = 'closed'
  h.state.searchQuery.value = ' CLOSED '
  await Vue.nextTick()
  assert.deepEqual(
    h.state.filteredStores.value.map(store => store.id),
    [1]
  )
  assert.deepEqual(Object.keys(h.state.markersById), ['1'])

  h.state.searchQuery.value = 'missing'
  await Vue.nextTick()
  assert.equal(h.state.filteredStores.value.length, 0)
  assert.deepEqual(Object.keys(h.state.markersById), [])
  h.state.searchQuery.value = null
  await Vue.nextTick()
  assert.deepEqual(Object.keys(h.state.markersById), ['1'])
})

test('a refreshed status updates the existing pin and open popup, including translations', async t => {
  const h = await mapHarness()
  t.after(() => {
    h.app.unmount()
    h.language.setLanguage('en')
  })
  await h.state.onDialogShow()
  const marker = h.state.markersById[2]
  h.state.focusStore(h.stores.value[1])
  h.stores.value[1] = {
    ...h.stores.value[1],
    isOpen: false,
    scheduleStatusText: 'Closed now'
  }
  await Vue.nextTick()
  assert.equal(h.state.markersById[2], marker)
  assert.equal(h.state.storePopupOpen.value, true)
  assert.match(marker.options.icon.className, /marker-closed/)
  assert.match(marker.html, /Closed now/)
  h.language.setLanguage('fil')
  await Vue.nextTick()
  assert.match(marker.html, /Sarado ngayon/)
  assert.match(marker.options.title, /Sarado/)
})

test('resizing an open map repositions the selected store popup', async t => {
  const h = await mapHarness()
  t.after(() => h.app.unmount())
  await h.state.onDialogShow()
  const marker = h.state.markersById[2]
  h.state.focusStore(h.stores.value[1])
  h.maps[0].events.resize?.()
  assert.equal(marker.popupUpdates, 1)
  assert.equal(h.state.markersById[1].popupUpdates, undefined)
})

test('a location detected after the map opens adds and updates the consumer pin', async t => {
  const h = await mapHarness()
  t.after(() => h.app.unmount())
  await h.state.onDialogShow()
  h.location.consumer_lat = '14.6'
  h.location.consumer_lng = '121'
  h.address.value = 'Detected location'
  h.locationVersion.value++
  await Vue.nextTick()
  const consumerPin = h.createdMarkers.find(
    marker => marker.options.icon.className === 'me-map-marker'
  )
  assert.ok(
    consumerPin,
    'late location detection shows the consumer on the map'
  )
  assert.deepEqual(consumerPin.coords, [14.6, 121])
  assert.deepEqual(h.maps[0].center, [14.6, 121])
  assert.deepEqual(h.requests.at(-1), { silent: true })
  h.location.consumer_lat = '14.7'
  h.locationVersion.value++
  await Vue.nextTick()
  assert.deepEqual(consumerPin.coords, [14.7, 121])
  assert.equal(
    h.createdMarkers.filter(
      marker => marker.options.icon.className === 'me-map-marker'
    ).length,
    1
  )
  h.state.focusStore(h.stores.value[1])
  const selectedStoreCenter = h.maps[0].center
  h.location.consumer_lat = '14.8'
  h.locationVersion.value++
  await Vue.nextTick()
  assert.deepEqual(consumerPin.coords, [14.8, 121])
  assert.equal(
    h.maps[0].center,
    selectedStoreCenter,
    'location updates preserve the selected store view'
  )
  delete h.location.consumer_lat
  delete h.location.consumer_lng
  h.locationVersion.value++
  await Vue.nextTick()
  assert.equal(consumerPin.removed, true)
})

test('a location saved while the dialog waits for rendering is used when the map initializes', async t => {
  const h = await mapHarness()
  t.after(() => h.app.unmount())
  h.state.searchQuery.value = 'Open'
  const opening = h.state.onDialogShow()
  h.location.consumer_lat = '14.6'
  h.location.consumer_lng = '121'
  h.locationVersion.value++
  await opening
  await Vue.nextTick()
  const consumerPin = h.createdMarkers.find(
    marker => marker.options.icon.className === 'me-map-marker'
  )
  assert.ok(consumerPin)
  assert.deepEqual(consumerPin.coords, [14.6, 121])
  assert.deepEqual(h.maps[0].center, [14.6, 121])
})

test('minute refresh stops on close and unmount, and a stale open cannot restart it', async t => {
  const h = await mapHarness()
  t.after(() => h.app.unmount())
  await h.state.onDialogShow()
  const interval = [...h.timers.values()][0]
  assert.equal(interval.ms, 60000)
  await interval.fn()
  assert.deepEqual(h.requests.at(-1), { silent: true })
  h.shown.value = false
  await Vue.nextTick()
  assert.equal(h.timers.size, 0)
  assert.equal(h.maps[0].removed, true)
  const count = h.requests.length
  interval.fn()
  assert.equal(h.requests.length, count)

  h.shown.value = true
  await Vue.nextTick()
  let finishFetch
  h.setFetch(
    () =>
      new Promise(resolve => {
        finishFetch = resolve
      })
  )
  const opening = h.state.onDialogShow()
  await Vue.nextTick()
  h.shown.value = false
  await Vue.nextTick()
  finishFetch()
  await opening
  assert.equal(h.timers.size, 0)
  assert.deepEqual(Object.keys(h.state.markersById), [])

  h.shown.value = true
  await Vue.nextTick()
  h.setFetch(async () => {})
  await h.state.onDialogShow()
  assert.equal(h.timers.size, 1)
  h.app.unmount()
  assert.equal(h.timers.size, 0)
})

test('a failed silent refresh keeps stores visible without a loading state', async () => {
  let request
  const useStores = await loadModule(
    '../src/composables/useStores.js',
    {
      localStorage: { getItem: () => null },
      console: { error() {} },
      api: {
        get: () =>
          new Promise((resolve, reject) => {
            request = { resolve, reject }
          })
      }
    },
    'useStores'
  )
  const { stores, loading, fetchStores } = useStores()
  const initial = fetchStores()
  assert.equal(loading.value, true)
  request.resolve({ data: [{ id: 1, name: 'Open Store', isOpen: true }] })
  await initial
  const lastStores = stores.value
  const refresh = fetchStores({ silent: true })
  assert.equal(loading.value, false)
  request.reject(new Error('Temporary connection failure'))
  await refresh
  assert.equal(stores.value, lastStores)
  assert.equal(loading.value, false)
})

test('invalid and missing store coordinates stay unmapped instead of becoming zero or NaN', async () => {
  const useStores = await loadModule(
    '../src/composables/useStores.js',
    {
      localStorage: { getItem: () => null },
      api: {
        get: async () => ({
          data: [
            { id: 1, latitude: '', longitude: '121' },
            { id: 2, latitude: 'invalid', longitude: '121' },
            { id: 3, latitude: '  ', longitude: 'Infinity' },
            { id: 4, latitude: '95', longitude: '200' },
            { id: 5, latitude: '0', longitude: '0' },
            { id: 6, latitude: '14.6', longitude: '121' }
          ]
        })
      }
    },
    'useStores'
  )
  const { stores, fetchStores } = useStores()
  await fetchStores()
  assert.deepEqual(
    stores.value.map(store => [store.latitude, store.longitude]),
    [
      [null, 121],
      [null, 121],
      [null, null],
      [null, null],
      [0, 0],
      [14.6, 121]
    ]
  )
})

test('an older location request cannot overwrite newer store or product results', async () => {
  for (const name of ['useStores', 'useProducts']) {
    const location = { consumer_lat: '14.6', consumer_lng: '121' }
    const pending = []
    const useCatalog = await loadModule(
      `../src/composables/${name}.js`,
      {
        localStorage: { getItem: key => location[key] },
        console: { error() {} },
        api: {
          get: () =>
            new Promise((resolve, reject) => pending.push({ resolve, reject }))
        }
      },
      name
    )
    const state = useCatalog()
    const fetch = state.fetchStores || state.fetchProducts
    const items = state.stores || state.products
    const first = fetch()
    assert.equal(
      fetch(),
      first,
      `${name} shares a pending request for the same location`
    )
    location.consumer_lat = '14.7'
    const second = fetch()
    pending[1].resolve({
      data: [{ id: 2, name: 'New location', distance_meters: 10 }]
    })
    await second
    pending[0].resolve({
      data: [{ id: 1, name: 'Old location', distance_meters: 1000 }]
    })
    await first
    assert.equal(items.value[0].name, 'New location', name)

    location.consumer_lat = '14.8'
    const olderFailure = fetch()
    location.consumer_lat = '14.9'
    const latest = fetch()
    pending[3].resolve({ data: [{ id: 3, name: 'Latest location' }] })
    await latest
    pending[2].reject(new Error('Old request failed'))
    await olderFailure
    assert.equal(
      items.value[0].name,
      'Latest location',
      `${name} ignores outdated failures`
    )
    assert.equal(state.loading.value, false)
  }
})

test('clearing price fields emits an unset bound instead of an empty string', async t => {
  const component = await loadModule(
    '../src/components/consumer/ProductFilters.vue',
    { useConsumerLanguage }
  )
  let state
  const prices = Vue.reactive({ priceMin: 10, priceMax: 50 })
  const originalSetup = component.setup
  component.setup = (props, context) => {
    state = originalSetup(props, context)
    return () => null
  }
  const app = renderer.createApp({
    setup: () => () =>
      Vue.h(component, {
        ...prices,
        sortOptions: [],
        'onUpdate:priceMin': value => {
          prices.priceMin = value
        },
        'onUpdate:priceMax': value => {
          prices.priceMax = value
        }
      })
  })
  app.mount({})
  t.after(() => app.unmount())
  state.priceMin.value = ''
  state.priceMax.value = ''
  await Vue.nextTick()
  assert.equal(prices.priceMin, null)
  assert.equal(prices.priceMax, null)
  state.priceMax.value = 0
  await Vue.nextTick()
  assert.equal(
    prices.priceMax,
    0,
    'a deliberately entered zero remains a real bound'
  )
})

async function catalogPageHarness(file) {
  const products = Vue.ref([
    {
      id: 1,
      name: 'Cola',
      store: 'Beverage Store',
      storeId: 1,
      category: 'Beverages',
      price: 25,
      inStock: true
    },
    {
      id: 2,
      name: 'Chips',
      store: 'Snack Store',
      storeId: 2,
      category: 'Snacks',
      price: 15,
      inStock: true
    }
  ])
  const stores = Vue.ref([
    { id: 1, name: 'Beverage Store' },
    { id: 2, name: 'Snack Store' }
  ])
  const route = Vue.reactive({
    query: { category: 'Beverages' },
    params: { id: '1' }
  })
  const noop = () => {}
  const component = await loadModule(`../src/pages/Consumer/${file}`, {
    useConsumerLanguage,
    useRoute: () => route,
    useRouter: () => ({ push: noop }),
    useQuasar: () => ({ screen: { width: 1280 }, notify: noop }),
    useCategories: () => ({
      categories: Vue.ref([
        { id: 1, label: 'Beverages' },
        { id: 2, label: 'Snacks' }
      ]),
      fetchCategories: noop
    }),
    useProducts: () => ({
      products,
      loading: Vue.ref(false),
      fetchProducts: noop
    }),
    useStores: () => ({ stores, loading: Vue.ref(false), fetchStores: noop }),
    useCart: () => ({ addToCart: noop }),
    useGridColumns: () => ({ columns: Vue.ref(4) }),
    useReveal: () => ({ onReveal: noop }),
    localStorage: { getItem: () => null }
  })
  let state
  const originalSetup = component.setup
  component.setup = (props, context) => {
    state = originalSetup(props, context)
    return () => null
  }
  const app = renderer.createApp(component)
  app.mount({})
  return { app, state, route, products }
}

test('category navigation updates an existing Products page and removes the old category', async t => {
  const h = await catalogPageHarness('ConsumerProducts.vue')
  t.after(() => h.app.unmount())
  assert.deepEqual(
    h.state.filteredProducts.value.map(product => product.id),
    [1]
  )
  h.route.query.category = 'Snacks'
  await Vue.nextTick()
  assert.deepEqual(
    h.state.filteredProducts.value.map(product => product.id),
    [2]
  )
  delete h.route.query.category
  await Vue.nextTick()
  assert.equal(h.state.selectedCategory.value, 'All')
  assert.deepEqual(
    h.state.filteredProducts.value.map(product => product.id),
    [1, 2]
  )
})

test('switching stores clears previous filters and the previous store product dialog', async t => {
  const h = await catalogPageHarness('ConsumerStoreDetail.vue')
  t.after(() => h.app.unmount())
  h.state.selectedCategory.value = 'Beverages'
  h.state.filterSearch.value = 'Cola'
  h.state.priceMax.value = 50
  h.state.openProductModal(h.products.value[0])
  assert.deepEqual(
    h.state.filteredProducts.value.map(product => product.id),
    [1]
  )
  h.route.params.id = '2'
  await Vue.nextTick()
  assert.deepEqual(
    h.state.filteredProducts.value.map(product => product.id),
    [2]
  )
  assert.equal(h.state.hasActiveFilters.value, false)
  assert.equal(h.state.showProductModal.value, false)
  assert.equal(h.state.selectedProduct.value, null)
})
