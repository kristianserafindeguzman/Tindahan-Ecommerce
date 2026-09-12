<template>
  <q-page class="dash-page">
    <div class="dash-container">

      <!-- ================= WELCOME BANNER ================= -->
      <!-- The consumer home's red banner, turned into the store owner's daily welcome. -->
      <!-- Its colour and icon follow the time of day, from a sunrise orange to a night indigo. -->
      <section class="dash-hero" :class="`dash-hero--${dayPhase.key}`">
        <div class="dash-hero-content">
          <div class="hero-eyebrow-row">
            <span class="hero-eyebrow">
              <q-icon :name="dayPhase.icon" size="14px" />
              {{ currentDate }}
            </span>
            <!-- Shown once the store has loaded, so it never flashes "Closed now" first. -->
            <span v-if="!loading" class="hero-status" :class="isStoreOpen ? 'hero-status--open' : 'hero-status--closed'">
              <span class="store-status-dot" />
              {{ isStoreOpen ? 'Open now' : 'Closed now' }}
            </span>
          </div>

          <h1 class="hero-title">{{ timeGreeting }}, {{ userName }}!</h1>
          <p class="hero-sub">Here's how {{ vendorStore?.store_name || 'your store' }} is doing today.</p>

          <div class="hero-actions">
            <q-btn unelevated no-caps label="View Store" class="hero-cta" @click="liveStoreModal = true">
              <q-icon name="o_storefront" size="16px" class="q-ml-xs" />
            </q-btn>
            <q-btn unelevated no-caps label="Manage Products" class="hero-cta hero-cta--ghost" @click="router.push('/vendor/products/list')">
              <q-icon name="o_arrow_forward" size="16px" class="q-ml-xs" />
            </q-btn>
            <!-- Notifications ride in the banner's own row of buttons on desktop, while phones reach them from the top bar. -->
            <q-btn unelevated class="hero-cta hero-cta--ghost hero-bell" aria-label="Notifications">
              <q-icon name="o_notifications" />
              <q-badge v-if="unreadCount > 0" floating rounded class="hero-bell-badge">{{ unreadCount > 99 ? '99+' : unreadCount }}</q-badge>
              <q-menu class="notif-menu" anchor="bottom left" self="top left" :offset="[0, 8]">
                <NotificationsPanel />
              </q-menu>
            </q-btn>
          </div>
        </div>

        <!-- A large, faint sun or moon for the time of day, where the store photo used to be. -->
        <q-icon :name="dayPhase.icon" class="hero-art" aria-hidden="true" />
      </section>

      <!-- ================= ORDER COUNTS ================= -->
      <div class="row q-col-gutter-md q-mb-md">
        <div v-for="kpi in kpis" :key="kpi.key" class="col-6 col-md-3">
          <div class="dash-card kpi-card">
            <div class="kpi-top">
              <span class="kpi-label">{{ kpi.label }}</span>
              <span class="kpi-icon" :class="`vp-tone--${kpi.tone}`">
                <q-icon :name="kpi.icon" size="20px" />
              </span>
            </div>
            <q-skeleton v-if="loading" type="text" width="45%" class="kpi-skeleton" />
            <div v-else class="kpi-value">{{ kpi.value }}</div>
          </div>
        </div>
      </div>

      <div class="row q-col-gutter-md q-mb-md items-stretch">

        <!-- ================= REVENUE ================= -->
        <div class="col-12 col-md-8">
          <div class="dash-card dash-card--fill">
            <div class="card-header">
              <div>
                <div class="section-title">Revenue</div>
                <div class="section-subtitle">Income from completed pickups.</div>
              </div>

              <div class="segmented" role="tablist" aria-label="Revenue period">
                <button
                  v-for="filter in FILTERS"
                  :key="filter"
                  type="button"
                  role="tab"
                  class="segmented-btn"
                  :class="{ 'segmented-btn--active': activeRevenueFilter === filter }"
                  :aria-selected="activeRevenueFilter === filter"
                  @click="activeRevenueFilter = filter"
                >
                  {{ filter }}
                </button>
              </div>
            </div>

            <div class="revenue-total">
              <div class="revenue-total-label">Total for this period</div>
              <div class="revenue-total-value">₱{{ formatNumber(totalRevenue) }}</div>
            </div>

            <div class="chart-box">
              <div v-if="chartLoading" class="chart-loading">
                <q-spinner-dots size="36px" color="primary" />
              </div>
              <VueApexCharts
                v-if="chartSeries[0]?.data"
                type="area"
                width="100%"
                :height="chartHeight"
                :options="chartOptions"
                :series="chartSeries"
              />
            </div>
          </div>
        </div>

        <!-- ================= DEMAND FORECAST ================= -->
        <div class="col-12 col-md-4">
          <!-- The forecast is the system's own prediction, so it gets a deep red card of its own. -->
          <div class="dash-card dash-card--fill forecast-card">
            <div class="card-header">
              <div>
                <div class="section-title forecast-title">
                  <span class="forecast-badge"><q-icon name="o_auto_awesome" size="16px" /></span>
                  Demand Forecast
                </div>
                <div class="section-subtitle">Items likely to sell today.</div>
              </div>
            </div>

            <div v-if="mlForecast.loading" class="empty-state">
              <q-spinner-dots size="32px" color="amber-3" />
              <div class="empty-state-text">Reading your recent sales…</div>
            </div>

            <div v-else-if="mlForecast.error" class="empty-state">
              <div class="state-icon tone-danger"><q-icon name="o_sync_problem" size="24px" /></div>
              <div class="empty-state-title">Forecast unavailable</div>
              <div class="empty-state-text">We couldn't load predictions right now.</div>
            </div>

            <div v-else-if="!mlForecast.has_forecast" class="empty-state">
              <div class="state-icon tone-brand"><q-icon name="o_insights" size="24px" /></div>
              <div class="empty-state-title">Collecting trends</div>
              <div class="empty-state-text">A few more completed orders are needed to predict your fast-moving items.</div>
            </div>

            <template v-else>
              <div class="forecast-list">
                <div v-for="(item, idx) in mlForecast.top_products" :key="idx" class="forecast-item">
                  <span class="forecast-rank" :class="{ 'forecast-rank--top': idx === 0 }">{{ idx + 1 }}</span>
                  <div class="forecast-thumb">
                    <img v-if="resolveProductImage(item)" :src="resolveProductImage(item)" :alt="item.product_name" />
                    <q-icon v-else name="o_image" size="18px" />
                  </div>
                  <div class="forecast-body">
                    <div class="forecast-name">{{ item.product_name }}</div>
                    <div class="forecast-meta">{{ getDemandCategory(item.predicted_quantity) }}</div>
                  </div>
                  <span class="forecast-qty">{{ formatPieces(item.predicted_quantity) }} pcs</span>
                </div>
              </div>

              <div v-if="mlForecast.low_data_warning" class="forecast-warning">
                <q-icon name="o_info" size="14px" />
                <span>{{ mlForecast.low_data_warning }}</span>
              </div>

              <div class="forecast-footer">
                <q-icon name="o_sync" size="13px" />
                {{ formatLastSync(mlForecast.generated_at) }}
              </div>
            </template>
          </div>
        </div>
      </div>

      <!-- ================= RECENT ORDERS ================= -->
      <div class="dash-card">
        <div class="card-header">
          <div>
            <div class="section-title">Recent Orders</div>
            <div class="section-subtitle">The latest orders from your customers.</div>
          </div>
          <q-btn outline no-caps color="primary" label="View All" icon-right="o_chevron_right" class="card-action-btn" @click="router.push('/vendor/orders/list')" />
        </div>

        <!-- Placeholder rows shaped like the table on wide screens and the list on phones. -->
        <div v-if="loading" :class="$q.screen.lt.md ? 'recent-skeleton-list' : 'orders-table-wrap'">
          <SkeletonTable :columns="RECENT_SKELETON" :rows="5" :list="$q.screen.lt.md" />
        </div>

        <div v-else-if="!recentOrders.length" class="empty-state">
          <div class="state-icon tone-brand"><q-icon name="o_receipt_long" size="24px" /></div>
          <div class="empty-state-title">No orders yet</div>
          <div class="empty-state-text">New orders from customers will show up here.</div>
        </div>

        <!-- A table on wide screens, where every column has room. -->
        <div v-else-if="!$q.screen.lt.md" class="orders-table-wrap">
          <table class="orders-table">
            <thead>
              <tr>
                <th class="col-order">Order</th>
                <th>Customer</th>
                <th class="col-date">Date</th>
                <th class="text-right col-total">Total</th>
                <th class="col-status">Status</th>
                <th class="col-open"><span class="sr-only">Open</span></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="order in recentOrders.slice(0, 5)" :key="order.id" class="orders-row" @click="openOrder(order)">
                <td><span class="order-id">#{{ order.id }}</span></td>
                <td>
                  <div class="order-customer">
                    <q-avatar size="32px" class="order-avatar">
                      <img v-if="order.avatar" :src="order.avatar" alt="" />
                      <q-icon v-else name="o_person" size="18px" />
                    </q-avatar>
                    <span class="order-customer-name">{{ order.customer }}</span>
                  </div>
                </td>
                <td class="order-date">{{ order.date }}</td>
                <td class="text-right order-total">₱{{ formatNumber(order.price) }}</td>
                <td><OrderStatusBadge :status="order.status" /></td>
                <td class="text-right">
                  <q-btn flat round dense icon="o_chevron_right" class="order-open-btn" :aria-label="`Open order #${order.id}`" @click.stop="openOrder(order)" />
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- A tappable list on phones, with the total and status on the right. -->
        <div v-else class="orders-list">
          <button v-for="order in recentOrders.slice(0, 5)" :key="order.id" type="button" class="orders-list-item" @click="openOrder(order)">
            <q-avatar size="38px" class="order-avatar">
              <img v-if="order.avatar" :src="order.avatar" alt="" />
              <q-icon v-else name="o_person" size="20px" />
            </q-avatar>
            <div class="orders-list-body">
              <div class="order-customer-name">{{ order.customer }}</div>
              <div class="orders-list-meta">#{{ order.id }} · {{ order.date }}</div>
            </div>
            <div class="orders-list-side">
              <div class="order-total">₱{{ formatNumber(order.price) }}</div>
              <OrderStatusBadge :status="order.status" />
            </div>
          </button>
        </div>
      </div>

    </div>

    <!-- ================= STORE PREVIEW ================= -->
    <!-- The map is drawn once the dialog has opened, and removed when it closes. -->
    <q-dialog v-model="liveStoreModal" transition-show="scale" transition-hide="scale" @show="initMap" @hide="cleanupMap">
      <q-card class="dash-dialog">
        <div class="dash-dialog-header">
          <div class="dialog-icon"><q-icon name="o_storefront" size="22px" /></div>
          <div class="dialog-header-text">
            <div class="dialog-title">{{ vendorStore?.store_name || 'My Store' }}</div>
            <div class="section-subtitle">How customers see your store.</div>
          </div>
          <q-btn flat round dense icon="o_close" class="dialog-close-btn" aria-label="Close store preview" v-close-popup />
        </div>

        <div class="dash-dialog-body">
          <div class="preview-banner">
            <img v-if="vendorStore?.store_picture_url" :src="vendorStore.store_picture_url" alt="Storefront" />
            <div v-else class="preview-banner-empty"><q-icon name="o_storefront" size="48px" /></div>
            <span class="store-status preview-status" :class="isStoreOpen ? 'store-status--open' : 'store-status--closed'">
              <span class="store-status-dot" />
              {{ isStoreOpen ? 'Open now' : 'Closed now' }}
            </span>
          </div>

          <div class="info-row">
            <div class="info-icon"><q-icon name="o_person" size="18px" /></div>
            <div class="info-body">
              <div class="info-label">Store Owner</div>
              <div class="info-value">{{ ownerFullName || 'Not provided' }}</div>
            </div>
          </div>

          <div class="info-row">
            <div class="info-icon"><q-icon name="o_phone" size="18px" /></div>
            <div class="info-body">
              <div class="info-label">Contact</div>
              <div class="info-value">{{ vendorPhone || 'Not provided' }}</div>
            </div>
          </div>

          <div class="info-row">
            <div class="info-icon"><q-icon name="o_place" size="18px" /></div>
            <div class="info-body">
              <div class="info-label">Address</div>
              <div class="info-value">{{ vendorStore?.address || 'Not provided' }}</div>
            </div>
          </div>

          <div class="info-row info-row-last info-row--top">
            <div class="info-icon"><q-icon name="o_schedule" size="18px" /></div>
            <div class="info-body">
              <div class="info-label">Store Hours</div>
              <div class="preview-hours">
                <div v-for="day in weekDays" :key="day.name" class="preview-hours-row" :class="{ 'preview-hours-row--closed': !day.isOpen }">
                  <span>{{ day.name }}</span>
                  <span>{{ day.isOpen ? `${formatTime(day.openTime)} – ${formatTime(day.closeTime)}` : 'Closed' }}</span>
                </div>
              </div>
            </div>
          </div>

          <div id="store-preview-map" class="preview-map"></div>
        </div>

        <div class="dash-dialog-actions">
          <q-btn outline no-caps color="primary" label="Close" v-close-popup />
          <q-btn unelevated no-caps color="primary" label="Edit Store Details" class="btn-gradient" @click="goToProfile" />
        </div>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { api } from '@/boot/axios'
import VueApexCharts from 'vue3-apexcharts'
import NotificationsPanel from '@/components/vendor/NotificationsPanel.vue'
import OrderStatusBadge from '@/components/vendor/OrderStatusBadge.vue'
import SkeletonTable from '@/components/vendor/SkeletonTable.vue'
import { statusIcon } from '@/utils/orderStatus'
import { useVendorNotifications } from '@/composables/useVendorNotifications'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

const router = useRouter()
const $q = useQuasar()
// The layout loads the notifications, so the banner bell only reads the shared unread count.
const { unreadCount } = useVendorNotifications()

const FILTERS = ['Daily', 'Weekly', 'Monthly']

// Shows placeholders in the cards until the first answers arrive.
const loading = ref(true)

const userName = ref('Vendor')
const ownerFullName = ref('Vendor')
const vendorStore = ref(null)
const vendorPhone = ref(null)
const liveStoreModal = ref(false)
const activeRevenueFilter = ref('Daily')
const catalogProducts = ref([])

const mlForecast = ref({
  loading: true,
  has_forecast: false,
  low_data_warning: false,
  error: false,
  summary: null,
  top_products: [],
  generated_at: null
})

const recentOrders = ref([])

// The recent orders placeholder takes the table's columns: order, customer, date, total, status and the open arrow.
const RECENT_SKELETON = [
  { width: '11%', type: 'pill', size: 56 },
  { type: 'avatar' },
  { width: '22%', type: 'text' },
  { width: '13%', type: 'text', align: 'right' },
  { width: '17%', type: 'pill', size: 96 },
  { width: '6%', type: 'icon', align: 'right' }
]
const stats = ref({
  placed_orders: 0,
  preparing_orders: 0,
  picked_up_orders: 0,
  cancelled_orders: 0
})

// Each order count uses its status's own colour and icon, the same as the status badges.
const kpis = computed(() => [
  { key: 'placed', label: 'Placed Orders', icon: statusIcon('placed'), tone: 'placed', value: stats.value.placed_orders },
  { key: 'preparing', label: 'Preparing', icon: statusIcon('preparing'), tone: 'preparing', value: stats.value.preparing_orders },
  { key: 'picked', label: 'Picked Up', icon: statusIcon('picked_up'), tone: 'done', value: stats.value.picked_up_orders },
  { key: 'cancelled', label: 'Cancelled', icon: statusIcon('cancelled'), tone: 'cancelled', value: stats.value.cancelled_orders }
])

// --- Revenue chart ---

const chartLoading = ref(false)
const chartSeries = ref([{ name: 'Revenue', data: [] }])
const chartHeight = computed(() => ($q.screen.lt.md ? 200 : 280))

const totalRevenue = computed(() => (chartSeries.value[0]?.data || []).reduce((a, b) => a + b, 0))

const peso = value => '₱' + Number(value).toLocaleString('en-PH', { minimumFractionDigits: 0, maximumFractionDigits: 0 })

// ApexCharts draws into SVG attributes, which can't read CSS variables, so the brand and text colours are written out.
const chartOptions = ref({
  chart: { type: 'area', toolbar: { show: false }, zoom: { enabled: false }, fontFamily: 'Roboto, Arial, sans-serif' },
  colors: ['#bd2427'],
  dataLabels: { enabled: false },
  stroke: { curve: 'smooth', width: 2.5 },
  xaxis: { categories: [], labels: { style: { colors: '#8992a2', fontSize: '11px' } }, axisBorder: { show: false }, axisTicks: { show: false } },
  yaxis: { labels: { style: { colors: '#8992a2', fontSize: '11px' }, formatter: peso } },
  grid: { borderColor: '#f0f0f0', strokeDashArray: 4 },
  tooltip: { y: { formatter: value => '₱' + Number(value).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) } },
  fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.28, opacityTo: 0.02, stops: [0, 100] } }
})

const fetchChartData = async () => {
  chartLoading.value = true
  try {
    const res = await api.get('/vendor/stats/chart', { params: { filter: activeRevenueFilter.value } })
    if (res.data) {
      chartSeries.value = [{ name: 'Revenue', data: res.data.map(item => item.total) }]
      chartOptions.value = {
        ...chartOptions.value,
        xaxis: { ...chartOptions.value.xaxis, categories: res.data.map(item => item.period) }
      }
    }
  } catch (error) {
    console.error('Failed to load chart data:', error)
  } finally {
    chartLoading.value = false
  }
}

watch(activeRevenueFilter, fetchChartData)

// --- Greeting and store status ---

// Filipino honorifics such as Aling and Mang belong with the name after them, so "Aling Nena" is greeted in full.
const HONORIFICS = ['aling', 'mang', 'ate', 'kuya', 'manang', 'manong', 'tita', 'tito', 'lola', 'lolo']
const greetingName = (fullName) => {
  const parts = (fullName || '').trim().split(/\s+/)
  if (!parts[0]) return 'Vendor'
  return HONORIFICS.includes(parts[0].toLowerCase()) && parts[1] ? `${parts[0]} ${parts[1]}` : parts[0]
}

// A clock that ticks every minute, so the greeting, the banner's colour and the open status keep up while the page stays open.
const clock = ref(new Date())
const clockTimer = setInterval(() => { clock.value = new Date() }, 60000)
onBeforeUnmount(() => clearInterval(clockTimer))

const timeGreeting = computed(() => {
  const hour = clock.value.getHours()
  if (hour < 12) return 'Good morning'
  if (hour < 18) return 'Good afternoon'
  return 'Good evening'
})

// The banner's colour and icon for each part of the day.
const dayPhase = computed(() => {
  const hour = clock.value.getHours()
  if (hour >= 5 && hour < 8) return { key: 'dawn', icon: 'o_wb_twilight' }
  if (hour >= 8 && hour < 12) return { key: 'morning', icon: 'o_light_mode' }
  if (hour >= 12 && hour < 17) return { key: 'afternoon', icon: 'o_wb_sunny' }
  if (hour >= 17 && hour < 19) return { key: 'evening', icon: 'o_wb_twilight' }
  return { key: 'night', icon: 'o_dark_mode' }
})

const currentDate = computed(() =>
  new Intl.DateTimeFormat('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' }).format(clock.value)
)

// Store hours are saved per day, while older stores only kept a list of day names with one shared time.
const weekDays = computed(() => {
  const fullDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']
  if (!vendorStore.value?.operating_days) return fullDays.map(name => ({ name, isOpen: false, openTime: null, closeTime: null }))

  let raw = vendorStore.value.operating_days
  if (typeof raw === 'string') {
    try { raw = JSON.parse(raw) } catch { raw = null }
  }

  const defaultOpen = vendorStore.value.opening_time ? vendorStore.value.opening_time.substring(0, 5) : null
  const defaultClose = vendorStore.value.closing_time ? vendorStore.value.closing_time.substring(0, 5) : null

  return fullDays.map(dayName => {
    let isOpen = false
    let openTime = defaultOpen
    let closeTime = defaultClose
    if (raw !== null && typeof raw === 'object' && !Array.isArray(raw)) {
      if (raw[dayName]) {
        isOpen = !!raw[dayName].is_open
        if (raw[dayName].opening_time) openTime = raw[dayName].opening_time.substring(0, 5)
        if (raw[dayName].closing_time) closeTime = raw[dayName].closing_time.substring(0, 5)
      }
    } else if (Array.isArray(raw)) {
      isOpen = raw.some(d => dayName.toLowerCase().startsWith(String(d).toLowerCase()))
    }
    return { name: dayName, isOpen, openTime, closeTime }
  })
})

// Open when today is an open day and the time falls inside its hours, including hours that run past midnight.
const isStoreOpen = computed(() => {
  if (!vendorStore.value?.operating_days) return false
  const now = clock.value
  const todayName = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'][now.getDay()]
  const today = weekDays.value.find(d => d.name === todayName)

  if (!today || !today.isOpen) return false
  if (!today.openTime || !today.closeTime) return true

  const minutes = now.getHours() * 60 + now.getMinutes()
  const [openH, openM] = today.openTime.split(':').map(Number)
  const [closeH, closeM] = today.closeTime.split(':').map(Number)
  const openMinutes = openH * 60 + openM
  const closeMinutes = closeH * 60 + closeM

  if (openMinutes <= closeMinutes) return minutes >= openMinutes && minutes <= closeMinutes
  return minutes >= openMinutes || minutes <= closeMinutes
})

// --- Helpers ---

const formatTime = timeString => {
  if (!timeString) return 'Not set'
  const [h, m] = timeString.split(':')
  const hours = parseInt(h, 10)
  if (Number.isNaN(hours) || m === undefined) return timeString
  return `${hours % 12 || 12}:${m.substring(0, 2)} ${hours >= 12 ? 'PM' : 'AM'}`
}

const formatNumber = num => {
  const clean = Number(String(num).replace(/[^0-9.-]+/g, ''))
  return Number(clean || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const formatPieces = val => {
  const parsed = parseFloat(val)
  return Number.isNaN(parsed) ? '0' : Math.round(parsed).toString()
}

const getDemandCategory = qty => {
  const parsed = parseFloat(qty)
  if (parsed >= 10) return 'High demand'
  if (parsed >= 5) return 'Steady sales'
  return 'Regular demand'
}

const formatLastSync = timestamp => {
  const date = timestamp ? new Date(timestamp) : null
  if (!date || Number.isNaN(date.getTime())) return 'Updated today'
  const day = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
  const time = date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })
  return `Updated ${day} · ${time}`
}

const normalize = str => (str ? String(str).toLowerCase().trim() : '')

// A forecast item may carry its own image, and otherwise borrows it from the matching catalogue product.
const resolveProductImage = item => {
  if (!item) return null
  if (typeof item.image_url === 'string' && item.image_url.trim() !== '') return item.image_url.trim()

  const matched = catalogProducts.value.find(p => {
    if (item.product_id && p.product_id && Number(item.product_id) === Number(p.product_id)) return true
    if (item.inventory_id && p.inventory_id && Number(item.inventory_id) === Number(p.inventory_id)) return true
    return !!(item.product_name && p.product_name && normalize(item.product_name) === normalize(p.product_name))
  })
  return matched?.image_url || null
}

const openOrder = order => router.push('/vendor/orders/' + order.id)

const goToProfile = () => {
  liveStoreModal.value = false
  router.push('/vendor/profile')
}

// --- Store preview map ---

let map = null

const cleanupMap = () => {
  if (map) {
    map.remove()
    map = null
  }
}

const initMap = async () => {
  await nextTick()
  // Waits for the dialog's opening animation, since Leaflet can't measure a panel that is still scaling in.
  setTimeout(() => {
    const container = document.getElementById('store-preview-map')
    if (!container) return

    cleanupMap()
    if (container._leaflet_id) delete container._leaflet_id

    const rawLat = vendorStore.value?.latitude
    const rawLng = vendorStore.value?.longitude
    const lat = rawLat && !Number.isNaN(Number(rawLat)) ? Number(rawLat) : 14.5995
    const lng = rawLng && !Number.isNaN(Number(rawLng)) ? Number(rawLng) : 120.9842

    try {
      map = L.map(container).setView([lat, lng], 15)
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
      }).addTo(map)

      const icon = L.icon({
        iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
        iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
        shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
      })

      L.marker([lat, lng], { icon }).addTo(map)
      setTimeout(() => { if (map) map.invalidateSize() }, 200)
    } catch (err) {
      console.warn('Map preview could not start:', err)
    }
  }, 180)
}

// --- Loading ---

onMounted(async () => {
  fetchChartData()

  try {
    const [profileRes, statsRes, productsRes] = await Promise.allSettled([
      api.get('/vendor/profile'),
      api.get('/vendor/stats'),
      api.get('/vendor/products')
    ])

    if (profileRes.status === 'fulfilled' && profileRes.value.data) {
      const profile = profileRes.value.data
      userName.value = greetingName(profile.full_name)
      ownerFullName.value = profile.full_name || 'Vendor'
      vendorPhone.value = profile.phone_number || null
      vendorStore.value = profile.store ? { ...profile.store } : null
    }

    if (statsRes.status === 'fulfilled' && statsRes.value.data) {
      const data = statsRes.value.data
      stats.value = {
        placed_orders: data.placed_orders || 0,
        preparing_orders: data.preparing_orders || 0,
        picked_up_orders: data.picked_up_orders || 0,
        cancelled_orders: data.cancelled_orders || 0
      }
      recentOrders.value = data.recent_orders || []
    }

    if (productsRes.status === 'fulfilled' && productsRes.value.data) {
      catalogProducts.value = productsRes.value.data || []
    }
  } catch (error) {
    console.error('Dashboard init error:', error)
  } finally {
    loading.value = false
  }

  try {
    const { data } = await api.get('/vendor/demand-forecast')
    if (data) {
      mlForecast.value.has_forecast = data.has_forecast
      mlForecast.value.low_data_warning = data.low_data_warning || false
      mlForecast.value.summary = data.summary
      mlForecast.value.top_products = data.top_products || []
      mlForecast.value.generated_at = data.generated_at
    }
  } catch (err) {
    console.error('Failed to load demand forecast:', err)
    mlForecast.value.error = true
    mlForecast.value.has_forecast = false
  } finally {
    mlForecast.value.loading = false
  }
})
</script>

<style scoped>
/* PAGE — the consumer pages' white ground, centred 1200px column and 24px gutter. */
.dash-page {
  background: #ffffff;

  font-family: 'Roboto', Arial, sans-serif;
}

.dash-container {
  width: 100%;
  max-width: 1200px;
  box-sizing: border-box;

  margin: 0 auto;
  padding: 24px;
}

/* WELCOME BANNER — the consumer home's red banner, with its dot grid, Poppins headline and white buttons. */

.dash-hero {
  position: relative;
  overflow: hidden;

  display: flex;
  align-items: center;
  justify-content: space-between;

  gap: 32px;
  padding: 32px 36px 36px;
  margin-bottom: var(--sp-gap);

  border-radius: var(--r-surface);
  box-shadow: 0 4px 16px rgba(101, 16, 18, 0.2);

  background:
    radial-gradient(circle, rgba(255, 255, 255, 0.1) 1.5px, transparent 1.5px) 0 0 / 26px 26px,
    linear-gradient(145deg, var(--c-brand) 0%, var(--c-brand-deep) 55%, var(--c-brand-active) 100%);

  color: #ffffff;

  animation: dash-fade-up 0.5s ease both;
}

/* The banner's colour follows the time of day; midday keeps the brand red above. */
.dash-hero--dawn {
  background:
    radial-gradient(circle, rgba(255, 255, 255, 0.1) 1.5px, transparent 1.5px) 0 0 / 26px 26px,
    linear-gradient(145deg, #d2612e 0%, #c0392f 50%, #942133 100%);
}

.dash-hero--morning {
  background:
    radial-gradient(circle, rgba(255, 255, 255, 0.1) 1.5px, transparent 1.5px) 0 0 / 26px 26px,
    linear-gradient(145deg, #d24d2a 0%, #bd2427 55%, #9c171b 100%);
}

.dash-hero--evening {
  background:
    radial-gradient(circle, rgba(255, 255, 255, 0.1) 1.5px, transparent 1.5px) 0 0 / 26px 26px,
    linear-gradient(145deg, #b3304a 0%, #82204f 55%, #4a1942 100%);

  box-shadow: 0 4px 16px rgba(74, 25, 66, 0.28);
}

.dash-hero--night {
  background:
    radial-gradient(circle, rgba(255, 255, 255, 0.12) 1.5px, transparent 1.5px) 0 0 / 26px 26px,
    linear-gradient(145deg, #3b2a6b 0%, #26194d 55%, #140f2b 100%);

  box-shadow: 0 4px 16px rgba(20, 15, 43, 0.35);
}

/* A large, faint sun or moon on the right, in place of the store photo. */
.hero-art {
  position: absolute;
  top: 50%;
  right: 48px;
  z-index: 0;

  font-size: 170px;

  color: rgba(255, 255, 255, 0.14);

  transform: translateY(-50%);
  pointer-events: none;
}

@keyframes dash-fade-up {
  from { opacity: 0; transform: translateY(14px); }
  to { opacity: 1; transform: translateY(0); }
}

@media (prefers-reduced-motion: reduce) {
  .dash-hero {
    animation: none;
  }
}

.dash-hero-content {
  position: relative;
  z-index: 1;

  min-width: 0;
}

.hero-eyebrow-row {
  display: flex;
  align-items: center;
  flex-wrap: wrap;

  gap: 8px;
  margin-bottom: 14px;
}

.hero-eyebrow {
  display: inline-flex;
  align-items: center;

  gap: 6px;
  padding: 5px 12px;

  border: 1px solid rgba(255, 255, 255, 0.28);
  border-radius: var(--r-pill);

  background: rgba(255, 255, 255, 0.12);
  color: #ffffff;

  font-size: var(--fs-2xs);
  font-weight: 600;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  white-space: nowrap;
}

.hero-title {
  margin: 0 0 8px;

  font-family: 'Poppins', 'Roboto', Arial, sans-serif;
  font-size: var(--fs-hero);
  font-weight: 800;
  line-height: 1.2;
  letter-spacing: -0.01em;

  color: #ffffff;
}

.hero-sub {
  max-width: 46ch;
  margin: 0 0 22px;

  font-size: var(--fs-md);
  line-height: 1.5;

  color: rgba(255, 255, 255, 0.86);
}

.hero-actions {
  display: flex;
  flex-wrap: wrap;

  gap: 12px;
}

.hero-cta {
  height: 42px;
  padding: 0 20px;

  border-radius: var(--r-control);

  background: #ffffff;
  color: var(--c-brand);

  font-size: var(--fs-sm);
  font-weight: 700;

  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.18);

  transition: background-color 0.15s, box-shadow 0.2s, transform 0.2s;
}

.hero-cta:hover {
  background: var(--c-hairline);

  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.25);
  transform: translateY(-1px);
}

.hero-cta:focus-visible {
  outline: none;
  box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.5);
}

/* The secondary path, so it stays an outline and doesn't compete with the white button. */
.hero-cta--ghost {
  border: 1px solid rgba(255, 255, 255, 0.55);

  background: transparent;
  color: #ffffff;

  box-shadow: none;
}

.hero-cta--ghost:hover {
  border-color: #ffffff;
  background: rgba(255, 255, 255, 0.14);
  box-shadow: none;
}

/* Open or closed, as a white pill beside the date. */
.hero-status {
  display: inline-flex;
  align-items: center;

  gap: 8px;
  height: 28px;
  padding: 0 12px;

  border-radius: var(--r-pill);

  background: #ffffff;

  font-size: var(--fs-xs);
  font-weight: 700;
  white-space: nowrap;

  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.18);
}

.hero-status--open {
  color: var(--c-success);
}

.hero-status--closed {
  color: var(--c-text-3);
}

/* The bell is a square outline button beside Manage Products, part of the banner's own row of actions. */
.hero-bell {
  width: 42px;
  min-width: 42px;
  padding: 0;
}

.hero-bell :deep(.q-icon) {
  font-size: 20px;
}

.hero-bell-badge {
  background: #ffb300 !important;
  color: #3b1d00 !important;

  font-weight: 800;
}

.notif-menu {
  overflow: hidden;

  border: 1px solid var(--c-border);
  border-radius: var(--r-surface);

  box-shadow: var(--sh-pop) !important;
}

/* Below the md breakpoint the phone top bar already has a bell, so the banner doesn't repeat it. */
@media (max-width: 1023px) {
  .hero-bell {
    display: none;
  }
}

@media (max-width: 1023px) {
  .dash-hero {
    gap: 24px;
    padding: 26px 28px 30px;
  }

  .hero-art {
    right: 28px;

    font-size: 130px;
  }
}

/* Open or closed, as a tinted pill with a dot, readable without relying on colour alone. */
.store-status {
  display: inline-flex;
  align-items: center;

  gap: 8px;
  height: 32px;
  padding: 0 14px;

  border-radius: var(--r-pill);

  font-size: var(--fs-sm);
  font-weight: 600;
}

.store-status-dot {
  width: 8px;
  height: 8px;

  border-radius: 50%;

  background: currentColor;
}

.store-status--open {
  background: var(--c-success-tint);
  color: var(--c-success);
}

.store-status--closed {
  background: var(--c-surface);
  color: var(--c-text-3);
}

/* CARDS — the consumer profile's white card with a hairline border and soft shadow. */

.dash-card {
  padding: 20px;

  border: 1px solid var(--c-border);
  border-radius: var(--r-surface);

  background: #ffffff;

  box-shadow: var(--sh-card);

  transition: box-shadow 0.2s, border-color 0.2s;
}

.dash-card:hover {
  border-color: var(--c-border-strong);

  box-shadow: var(--sh-card-hover);
}

.dash-card--fill {
  display: flex;
  flex-direction: column;

  height: 100%;
}

.card-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  flex-wrap: wrap;

  gap: 12px;
  margin-bottom: 16px;
}

.section-title {
  font-size: var(--fs-xl);
  font-weight: 700;
  line-height: 1.3;

  color: var(--c-text);
}

.section-subtitle {
  margin-top: 2px;

  font-size: var(--fs-sm);
  line-height: 1.4;

  color: var(--c-subtle);
}

/* The consumer profile's small outline pill. */
.card-action-btn {
  flex-shrink: 0;

  height: 32px;
  min-height: 32px;
  padding: 0 14px;

  border-radius: var(--r-control);

  font-size: var(--fs-sm);
  font-weight: 600;

  transition: background-color 0.15s;
}

.card-action-btn:hover {
  background: var(--c-brand-tint);
}

.card-action-btn :deep(.q-icon) {
  font-size: 18px;
}

/* TONES — one tinted background and text colour per status, shared by the count tiles and the order chips. */

.tone-info { background: var(--c-info-tint); color: var(--c-info); }
.tone-warning { background: var(--c-warning-tint); color: var(--c-warning); }
.tone-success { background: var(--c-success-tint); color: var(--c-success); }
.tone-danger { background: var(--c-danger-tint); color: var(--c-danger); }
.tone-wait { background: var(--c-status-wait-tint); color: var(--c-status-wait); }
.tone-active { background: var(--c-status-active-tint); color: var(--c-status-active); }
.tone-neutral { background: var(--c-surface); color: var(--c-text-3); }
.tone-brand { background: linear-gradient(145deg, var(--c-brand-tint) 0%, var(--c-brand-tint-2) 100%); color: var(--c-brand); }

/* ORDER COUNTS */

.kpi-card {
  height: 100%;
}

.kpi-top {
  display: flex;
  align-items: center;
  justify-content: space-between;

  gap: 8px;
}

.kpi-label {
  font-size: var(--fs-sm);
  font-weight: 600;

  color: var(--c-text-3);
}

.kpi-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;

  width: 40px;
  height: 40px;

  border-radius: var(--r-surface);
}

.kpi-value {
  margin-top: 10px;

  font-size: var(--fs-4xl);
  font-weight: 700;
  line-height: 1.1;

  color: var(--c-text);
}

.kpi-skeleton {
  margin-top: 10px;
  height: 30px;
}

/* REVENUE */

/* A segmented control like the consumer filter chips, with the chosen period in the brand tint. */
.segmented {
  display: inline-flex;

  gap: 2px;
  padding: 3px;

  border: 1px solid var(--c-border);
  border-radius: var(--r-control);

  background: var(--c-surface-2);
}

.segmented-btn {
  height: 30px;
  padding: 0 14px;

  border: none;
  border-radius: var(--r-control);

  background: transparent;

  font-family: inherit;
  font-size: var(--fs-xs);
  font-weight: 600;

  color: var(--c-text-3);

  cursor: pointer;

  transition: background-color 0.15s, color 0.15s;
}

.segmented-btn:hover:not(.segmented-btn--active) {
  color: var(--c-text);
}

.segmented-btn--active {
  background: #ffffff;
  color: var(--c-brand);

  box-shadow: 0 1px 3px rgba(17, 17, 17, 0.08);
}

.segmented-btn:focus-visible {
  outline: 2px solid var(--c-brand);
  outline-offset: 1px;
}

.revenue-total {
  margin-bottom: 4px;
}

.revenue-total-label {
  font-size: var(--fs-xs);

  color: var(--c-muted);
}

.revenue-total-value {
  margin-top: 2px;

  font-size: var(--fs-3xl);
  font-weight: 700;

  color: var(--c-text);
}

.chart-box {
  position: relative;

  flex: 1;
  min-height: 200px;
  margin: 0 -10px -10px;
}

.chart-loading {
  position: absolute;
  inset: 0;
  z-index: 2;

  display: flex;
  align-items: center;
  justify-content: center;

  background: rgba(255, 255, 255, 0.7);
}

/* DEMAND FORECAST */

.forecast-title {
  display: flex;
  align-items: center;

  gap: 8px;
}

/* The forecast card: deep red with gold accents, so the system's own prediction stands apart from the plain cards. */
.forecast-card {
  border-color: transparent;

  background:
    radial-gradient(circle, rgba(255, 255, 255, 0.07) 1.5px, transparent 1.5px) 0 0 / 22px 22px,
    linear-gradient(160deg, #4a0f13 0%, #7a181c 55%, #a51d22 100%);
  color: #ffffff;

  box-shadow: 0 8px 24px rgba(101, 16, 18, 0.28);
}

.forecast-card:hover {
  border-color: transparent;

  box-shadow: 0 10px 28px rgba(101, 16, 18, 0.34);
}

.forecast-card .section-title {
  color: #ffffff;
}

.forecast-card .section-subtitle {
  color: rgba(255, 255, 255, 0.72);
}

.forecast-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;

  width: 30px;
  height: 30px;

  border-radius: var(--r-control);

  background: linear-gradient(145deg, #fde68a 0%, #f59e0b 100%);
  color: #4a2400;

  box-shadow: 0 2px 8px rgba(245, 158, 11, 0.35);
}

.forecast-card .forecast-item:hover {
  background: rgba(255, 255, 255, 0.08);
}

.forecast-card .forecast-rank {
  background: rgba(255, 255, 255, 0.14);
  color: #ffffff;
}

.forecast-card .forecast-rank--top {
  background: #fbbf24;
  color: #4a2400;
}

.forecast-card .forecast-thumb {
  border-color: transparent;

  background: #ffffff;
}

.forecast-card .forecast-name {
  color: #ffffff;
}

.forecast-card .forecast-meta {
  color: rgba(255, 255, 255, 0.7);
}

.forecast-card .forecast-qty {
  background: rgba(251, 191, 36, 0.18);
  color: #fcd34d;
}

.forecast-card .forecast-warning {
  border-color: rgba(251, 191, 36, 0.4);

  background: rgba(251, 191, 36, 0.12);
  color: #fde68a;
}

.forecast-card .forecast-footer {
  border-top-color: rgba(255, 255, 255, 0.14);

  color: rgba(255, 255, 255, 0.65);
}

.forecast-card .empty-state-title {
  color: #ffffff;
}

.forecast-card .empty-state-text {
  color: rgba(255, 255, 255, 0.72);
}

.forecast-card .state-icon {
  background: rgba(255, 255, 255, 0.12);
  color: #fcd34d;
}

.forecast-list {
  display: flex;
  flex-direction: column;

  gap: 4px;
}

.forecast-item {
  display: flex;
  align-items: center;

  gap: 10px;
  padding: 8px;
  margin: 0 -8px;

  border-radius: var(--r-control);

  transition: background-color 0.15s;
}

.forecast-item:hover {
  background: var(--c-surface-2);
}

.forecast-rank {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;

  width: 24px;
  height: 24px;

  border-radius: 50%;

  background: var(--c-surface);

  font-size: var(--fs-xs);
  font-weight: 700;

  color: var(--c-text-3);
}

.forecast-rank--top {
  background: var(--c-brand);
  color: #ffffff;
}

.forecast-thumb {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;

  width: 40px;
  height: 40px;
  overflow: hidden;

  border: 1px solid var(--c-hairline);
  border-radius: var(--r-control);

  background: var(--c-surface-2);
  color: var(--c-muted);
}

.forecast-thumb img {
  width: 100%;
  height: 100%;

  object-fit: contain;
}

.forecast-body {
  flex: 1;
  min-width: 0;
}

.forecast-name {
  overflow: hidden;

  font-size: var(--fs-sm);
  font-weight: 600;
  white-space: nowrap;
  text-overflow: ellipsis;

  color: var(--c-text);
}

.forecast-meta {
  font-size: var(--fs-xs);

  color: var(--c-muted);
}

.forecast-qty {
  flex-shrink: 0;

  padding: 4px 10px;

  border-radius: var(--r-pill);

  background: var(--c-brand-tint);

  font-size: var(--fs-xs);
  font-weight: 700;

  color: var(--c-brand);
}

.forecast-warning {
  display: flex;
  align-items: flex-start;

  gap: 6px;
  margin-top: 12px;
  padding: 8px 10px;

  border: 1px solid var(--c-warning-line);
  border-radius: var(--r-control);

  background: var(--c-warning-wash);

  font-size: var(--fs-xs);
  line-height: 1.4;

  color: var(--c-warning);
}

.forecast-footer {
  display: flex;
  align-items: center;

  gap: 4px;
  margin-top: auto;
  padding-top: 12px;

  border-top: 1px solid var(--c-hairline);

  font-size: var(--fs-2xs);

  color: var(--c-muted);
}

/* EMPTY, LOADING AND ERROR STATES */

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;

  flex: 1;
  gap: 6px;
  padding: 28px 12px;

  text-align: center;
}

.state-icon {
  display: flex;
  align-items: center;
  justify-content: center;

  width: 52px;
  height: 52px;
  margin-bottom: 6px;

  border-radius: var(--r-surface);
}

.empty-state-title {
  font-size: var(--fs-md);
  font-weight: 700;

  color: var(--c-text);
}

.empty-state-text {
  max-width: 260px;

  font-size: var(--fs-xs);
  line-height: 1.5;

  color: var(--c-muted);
}

/* RECENT ORDERS */

/* The phone placeholder lines up with the real list, which has no extra side padding. */
.recent-skeleton-list :deep(.sk-list) {
  padding: 0;
}

.orders-table-wrap {
  overflow-x: auto;

  margin: 0 -20px -20px;
}

/* Proportional column widths, so the columns spread evenly at any width instead of leaving one wide empty column. */
.orders-table {
  width: 100%;
  min-width: 640px;

  table-layout: fixed;
  border-collapse: collapse;

  font-size: var(--fs-sm);
}

.orders-table .col-order { width: 11%; }
.orders-table .col-date { width: 22%; }
.orders-table .col-total { width: 13%; }
.orders-table .col-status { width: 17%; }
.orders-table .col-open { width: 6%; }

.orders-table th {
  padding: 10px 20px;

  border-top: 1px solid var(--c-hairline);
  border-bottom: 1px solid var(--c-hairline);

  background: var(--c-surface-2);

  font-size: var(--fs-2xs);
  font-weight: 700;
  letter-spacing: 0.06em;
  text-align: left;
  text-transform: uppercase;

  color: var(--c-muted);
}

/* The header rule above sets every heading left, so the money column's heading is set back to the right here. */
.orders-table .text-right {
  text-align: right;
}

.orders-table td {
  padding: 12px 20px;

  border-bottom: 1px solid var(--c-hairline);

  color: var(--c-text-2);
}

.orders-table tbody tr:last-child td {
  border-bottom: none;
}

.orders-row {
  cursor: pointer;

  transition: background-color 0.15s;
}

.orders-row:hover {
  background: var(--c-surface-2);
}

.order-id {
  padding: 3px 8px;

  border-radius: var(--r-control);

  background: var(--c-brand-tint);

  font-size: var(--fs-xs);
  font-weight: 700;

  color: var(--c-brand);
}

.order-customer {
  display: flex;
  align-items: center;

  gap: 10px;
}

.order-avatar {
  flex-shrink: 0;

  background: var(--c-surface);
  color: var(--c-muted);
}

.order-customer-name {
  min-width: 0;
  overflow: hidden;

  font-weight: 600;
  white-space: nowrap;
  text-overflow: ellipsis;

  color: var(--c-text);
}

.order-date {
  white-space: nowrap;

  color: var(--c-text-3);
}

.order-total {
  font-weight: 700;

  color: var(--c-text);
}

.order-open-btn {
  color: var(--c-muted);
}

.orders-list {
  display: flex;
  flex-direction: column;

  margin: 0 -8px;
}

.orders-list-item {
  display: flex;
  align-items: center;

  gap: 12px;
  width: 100%;
  padding: 12px 8px;

  border: none;
  border-bottom: 1px solid var(--c-hairline);
  border-radius: var(--r-control);

  background: transparent;

  font-family: inherit;
  text-align: left;

  cursor: pointer;
}

.orders-list-item:last-child {
  border-bottom: none;
}

.orders-list-item:hover {
  background: var(--c-surface-2);
}

.orders-list-item:focus-visible {
  outline: 2px solid var(--c-brand);
  outline-offset: -2px;
}

.orders-list-body {
  flex: 1;
  min-width: 0;
}

.orders-list-body .order-customer-name {
  overflow: hidden;

  font-size: var(--fs-sm);
  white-space: nowrap;
  text-overflow: ellipsis;
}

.orders-list-meta {
  margin-top: 2px;

  font-size: var(--fs-xs);

  color: var(--c-muted);
}

.orders-list-side {
  display: flex;
  flex-direction: column;
  align-items: flex-end;

  gap: 4px;
}

.sr-only {
  position: absolute;

  width: 1px;
  height: 1px;
  overflow: hidden;

  clip: rect(0 0 0 0);
  white-space: nowrap;
}

/* STORE PREVIEW DIALOG — the consumer profile's dialog shell. */

.dash-dialog {
  display: flex;
  flex-direction: column;

  width: 520px;
  max-width: 92vw;
  max-height: 88vh;

  border: 1px solid var(--c-border);
  border-radius: var(--r-surface);

  box-shadow: 0 18px 48px rgba(17, 17, 17, 0.18) !important;

  --q-transition-duration: 200ms;
}

.dash-dialog-header {
  display: flex;
  align-items: center;

  gap: 12px;
  padding: 24px 24px 18px;

  border-bottom: 1px solid var(--c-hairline);
}

.dialog-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;

  width: 44px;
  height: 44px;

  border-radius: var(--r-surface);

  background: linear-gradient(145deg, var(--c-brand-tint) 0%, var(--c-brand-tint-2) 100%);
  color: var(--c-brand);
}

.dialog-header-text {
  flex: 1;
  min-width: 0;
}

.dialog-title {
  overflow: hidden;

  font-size: var(--fs-xl);
  font-weight: 700;
  white-space: nowrap;
  text-overflow: ellipsis;

  color: var(--c-text);
}

.dialog-close-btn {
  color: var(--c-muted);
}

.dash-dialog-body {
  flex: 1;
  overflow-y: auto;

  padding: 20px 24px;
}

.preview-banner {
  position: relative;

  aspect-ratio: 16 / 9;
  max-width: 100%;
  overflow: hidden;
  margin-bottom: 8px;

  border-radius: var(--r-surface);

  background: var(--c-surface);
}

.preview-banner img {
  width: 100%;
  height: 100%;

  object-fit: cover;
}

.preview-banner-empty {
  display: flex;
  align-items: center;
  justify-content: center;

  height: 100%;

  color: var(--c-muted);
}

.preview-status {
  position: absolute;
  left: 12px;
  bottom: 12px;

  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
}

.preview-status.store-status--closed {
  background: #ffffff;
}

.info-row {
  display: flex;
  align-items: center;

  gap: 14px;
  padding: 12px 0;

  border-bottom: 1px solid var(--c-hairline);
}

.info-row-last {
  border-bottom: none;
}

.info-row--top {
  align-items: flex-start;
}

.info-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;

  width: 40px;
  height: 40px;

  border-radius: var(--r-surface);

  background: linear-gradient(145deg, var(--c-brand-tint) 0%, var(--c-brand-tint-2) 100%);
  color: var(--c-brand);
}

.info-body {
  flex: 1;
  min-width: 0;
}

.info-label {
  font-size: var(--fs-xs);

  color: var(--c-muted);
}

.info-value {
  margin-top: 3px;

  font-size: var(--fs-md);
  font-weight: 500;

  color: var(--c-text);

  overflow-wrap: anywhere;
}

.preview-hours {
  margin-top: 6px;
}

.preview-hours-row {
  display: flex;
  justify-content: space-between;

  gap: 12px;
  padding: 3px 0;

  font-size: var(--fs-sm);

  color: var(--c-text-2);
}

.preview-hours-row--closed {
  color: var(--c-muted);
}

.preview-map {
  position: relative;
  z-index: 1;

  height: 190px;
  margin-top: 8px;

  border: 1px solid var(--c-border);
  border-radius: var(--r-surface);
}

.dash-dialog-actions {
  display: flex;
  justify-content: flex-end;

  gap: 10px;
  padding: 16px 24px;

  border-top: 1px solid var(--c-border);
}

.dash-dialog-actions .q-btn {
  height: 44px;
  min-width: 132px;

  border-radius: var(--r-control);
}

/* Close uses the red outline of the Edit pill, tinting on hover. */
.dash-dialog-actions .q-btn--outline:hover {
  background: var(--c-brand-tint);
}

/* The consumer profile's primary button, flat red with a soft lift. */
.btn-gradient {
  background: var(--c-brand) !important;

  box-shadow: var(--sh-brand);

  transition: background-color 0.15s, box-shadow 0.2s;
}

.btn-gradient:hover {
  background: var(--c-brand-hover) !important;

  box-shadow: var(--sh-brand-hover);
}

@media (max-width: 600px) {
  .dash-container {
    padding: 16px;
  }

  .dash-hero {
    padding: 20px 18px;
  }

  .hero-art {
    display: none;
  }

  .hero-status {
    box-shadow: none;
  }

  .hero-eyebrow-row {
    margin-bottom: 10px;
  }

  .hero-title {
    font-size: var(--fs-4xl);
  }

  .hero-sub {
    margin-bottom: 16px;

    font-size: var(--fs-sm);
  }

  .hero-actions {
    gap: 10px;
  }

  .hero-cta {
    flex: 1;

    padding: 0 12px;
  }

  .hero-cta :deep(.q-btn__content) {
    flex-wrap: nowrap;
    white-space: nowrap;
  }

  .dash-card {
    padding: 16px;
  }

  .kpi-icon {
    width: 34px;
    height: 34px;
  }

  .kpi-value {
    font-size: var(--fs-3xl);
  }

  .section-title {
    font-size: var(--fs-lg);
  }

  .section-subtitle {
    font-size: var(--fs-xs);
  }

  .revenue-total-value {
    font-size: var(--fs-2xl);
  }

  .dash-dialog-header,
  .dash-dialog-body {
    padding-left: 18px;
    padding-right: 18px;
  }

  .dash-dialog-actions {
    padding: 14px 18px;
  }

  .dash-dialog-actions .q-btn {
    flex: 1 1 0;
    min-width: 0;
  }
}
</style>
