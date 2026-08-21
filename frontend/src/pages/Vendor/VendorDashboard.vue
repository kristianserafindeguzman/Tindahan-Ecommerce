<template>
  <q-page class="vendor-page">
    <!-- Decorative Ambient Background Glows -->
    <div class="bg-glow bg-glow-primary"></div>
    <div class="bg-glow bg-glow-secondary"></div>

    <div v-if="checkingAccess" class="checking-access flex flex-center h-full">
      <q-spinner-puff color="red-9" size="60px" thickness="3" />
    </div>

    <div v-else class="page-container">
      <!-- ================= DYNAMIC TIME-SYNCED HEADER ================= -->
      <div
        class="welcome-banner q-mb-md q-pa-lg row items-center justify-between transition-theme card-rounded"
        :class="headerThemeClass"
      >
        <div>
          <div class="row items-center q-mb-sm">
            <q-icon
              name="today"
              size="18px"
              :class="subTextClass"
              class="q-mr-sm opacity-80"
            />
            <span
              class="text-caption text-weight-bold tracking-wide text-uppercase"
              :class="subTextClass"
            >
              {{ currentDate }}
            </span>
          </div>
          <h1
            class="text-h3 text-weight-bolder q-ma-none header-title"
            :class="headerTextClass"
          >
            {{ timeGreeting }},
            <span class="text-weight-black">{{ userName }}</span>
          </h1>
          <p
            class="text-subtitle1 q-mt-sm q-mb-none opacity-80"
            :class="subTextClass"
          >
            Here's what's happening with your neighborhood store today.
          </p>
        </div>

        <div class="row items-center q-gutter-md">
          <div
            class="store-status-badge row items-center q-px-md q-py-sm border-radius-8 shadow-soft"
            :class="isStoreOpen ? 'bg-green-1' : 'bg-red-1'"
          >
            <q-icon
              name="fiber_manual_record"
              size="10px"
              :color="isStoreOpen ? 'green-8' : 'red-8'"
              class="q-mr-sm"
            />
            <span
              class="text-weight-bold text-caption"
              :class="isStoreOpen ? 'text-green-8' : 'text-red-8'"
              >{{ isStoreOpen ? 'Store is Open' : 'Store is Closed' }}</span
            >
          </div>
          <q-btn
            label="View Live Store"
            unelevated
            color="white"
            text-color="dark"
            icon="storefront"
            class="btn-premium q-px-lg shadow-1"
            no-caps
            @click="liveStoreModal = true"
          />
        </div>
      </div>

      <!-- ================= TOP METRICS (Solid Clean Design) ================= -->
      <div class="row q-col-gutter-lg q-mb-xl">
        <div class="col-12 col-sm-6 col-md-3">
          <q-card class="clean-solid-card card-hover">
            <q-card-section>
              <div class="row items-center justify-between q-mb-md">
                <div
                  class="text-subtitle2 text-weight-bold text-blue-9 text-uppercase tracking-wide"
                  >Placed Orders</div
                >
                <div class="icon-premium-box bg-blue-1 text-blue-7 shadow-soft">
                  <q-icon name="shopping_cart_checkout" size="22px" />
                </div>
              </div>
              <div class="text-h3 text-weight-bolder text-dark">{{
                stats.placed_orders
              }}</div>
            </q-card-section>
          </q-card>
        </div>

        <div class="col-12 col-sm-6 col-md-3">
          <q-card class="clean-solid-card card-hover">
            <q-card-section>
              <div class="row items-center justify-between q-mb-md">
                <div
                  class="text-subtitle2 text-weight-bold text-amber-9 text-uppercase tracking-wide"
                  >Preparing</div
                >
                <div
                  class="icon-premium-box bg-amber-1 text-amber-7 shadow-soft"
                >
                  <q-icon name="inventory_2" size="22px" />
                </div>
              </div>
              <div class="text-h3 text-weight-bolder text-dark">{{
                stats.preparing_orders
              }}</div>
            </q-card-section>
          </q-card>
        </div>

        <div class="col-12 col-sm-6 col-md-3">
          <q-card class="clean-solid-card card-hover">
            <q-card-section>
              <div class="row items-center justify-between q-mb-md">
                <div
                  class="text-subtitle2 text-weight-bold text-green-9 text-uppercase tracking-wide"
                  >Picked Up</div
                >
                <div
                  class="icon-premium-box bg-green-1 text-green-7 shadow-soft"
                >
                  <q-icon name="task_alt" size="22px" />
                </div>
              </div>
              <div class="text-h3 text-weight-bolder text-dark">{{
                stats.picked_up_orders
              }}</div>
            </q-card-section>
          </q-card>
        </div>

        <div class="col-12 col-sm-6 col-md-3">
          <q-card class="clean-solid-card card-hover">
            <q-card-section>
              <div class="row items-center justify-between q-mb-md">
                <div
                  class="text-subtitle2 text-weight-bold text-red-9 text-uppercase tracking-wide"
                  >Cancelled</div
                >
                <div class="icon-premium-box bg-red-1 text-red-7 shadow-soft">
                  <q-icon name="block" size="22px" />
                </div>
              </div>
              <div class="text-h3 text-weight-bolder text-dark">{{
                stats.cancelled_orders
              }}</div>
            </q-card-section>
          </q-card>
        </div>
      </div>

      <!-- ================= RECENT ORDERS TABLE ================= -->
      <q-card class="premium-glass-card card-rounded q-mb-xl card-hover">
        <q-card-section
          class="panel-header row items-center justify-between q-pa-md"
        >
          <div class="text-h6 text-weight-bold text-dark row items-center">
            <div class="header-accent-red q-mr-md"></div>
            Recent Customer Order Requests
          </div>
          <q-btn
            label="View All Orders"
            flat
            color="red-9"
            class="text-weight-bold border-radius-8"
            no-caps
            @click="router.push('/vendor/orders/list')"
          />
        </q-card-section>

        <q-table
          flat
          class="custom-premium-table bg-transparent"
          :rows="recentOrders"
          :columns="orderColumns"
          row-key="id"
          hide-bottom
          :pagination="{ rowsPerPage: 5 }"
        >
          <!-- Order ID Column -->
          <template #body-cell-id="props">
            <q-td :props="props">
              <span
                class="text-weight-bold text-red-8 font-monospace q-px-sm q-py-xs bg-red-1"
                style="border: 1px solid rgba(220, 38, 38, 0.3); border-radius: 6px;"
              >
                #{{ props.row.id }}
              </span>
            </q-td>
          </template>

          <template #body-cell-customer="props">
            <q-td :props="props">
              <div class="row items-center no-wrap">
                <q-avatar
                  size="34px"
                  class="q-mr-md shadow-soft border-white bg-blue-grey-1"
                >
                  <img v-if="props.row.avatar" :src="props.row.avatar" />
                  <q-icon
                    v-else
                    name="person"
                    color="blue-grey-6"
                    size="22px"
                  />
                </q-avatar>
                <div class="text-weight-bold text-blue-grey-9 text-body2">{{
                  props.row.customer
                }}</div>
              </div>
            </q-td>
          </template>

          <template #body-cell-status="props">
            <q-td :props="props">
              <q-chip
                dense
                :color="getStatusColor(props.row.status)"
                text-color="white"
                class="text-weight-bold shadow-soft q-px-sm q-py-xs"
              >
                {{ props.row.status }}
              </q-chip>
            </q-td>
          </template>

          <template #body-cell-action="props">
            <q-td :props="props" class="text-right">
              <q-btn
                flat
                round
                dense
                icon="chevron_right"
                color="blue-grey-4"
                class="hover-icon-btn"
                @click="router.push('/vendor/orders/' + props.row.id)"
              />
            </q-td>
          </template>
        </q-table>
      </q-card>

      <!-- ================= REVENUE & ML BLUEPRINT ================= -->
      <div class="row q-col-gutter-lg">
        <!-- Revenue Chart -->
        <div class="col-12 col-md-7">
          <q-card class="premium-glass-card card-rounded h-full card-hover">
            <q-card-section
              class="panel-header row items-center justify-between q-pa-md"
            >
              <div class="text-h6 text-weight-bold text-dark row items-center">
                <div class="header-accent-red q-mr-md"></div>
                Revenue Overview
              </div>
              <!-- Fixed Filter Options -->
              <q-btn-group
                flat
                class="bg-slate-50 border-slate-light rounded-borders p-1"
              >
                <q-btn
                  v-for="filter in ['Daily', 'Weekly', 'Monthly']"
                  :key="filter"
                  :label="filter"
                  :unelevated="activeRevenueFilter === filter"
                  :flat="activeRevenueFilter !== filter"
                  :class="
                    activeRevenueFilter === filter
                      ? 'bg-white text-dark shadow-1 text-weight-bold'
                      : 'text-blue-grey-6 hover-text-dark'
                  "
                  no-caps
                  size="sm"
                  class="border-radius-8 transition-ease"
                  @click="activeRevenueFilter = filter"
                />
              </q-btn-group>
            </q-card-section>

            <q-card-section class="chart-container relative-position q-pa-none">
              <div
                v-if="chartLoading"
                class="absolute-full flex flex-center z-top"
                style="background: rgba(255, 255, 255, 0.6); backdrop-filter: blur(2px); border-radius: 0 0 12px 12px;"
              >
                <q-spinner-dots size="40px" color="red-8" />
              </div>
              <VueApexCharts
                class="full-width"
                style="width: 100%; display: block;"
                type="area"
                height="250"
                width="100%"
                :options="chartOptions"
                :series="chartSeries"
              />
            </q-card-section>
          </q-card>
        </div>

        <!-- ML Blueprint Container (Reverted to Indigo/Amber) -->
        <div class="col-12 col-md-5">
          <q-card class="ml-blueprint-card card-rounded h-full card-hover">
            <q-card-section class="q-pa-md relative-position h-full column">
              <div class="ml-glow-overlay"></div>

              <div
                class="row items-center justify-between q-mb-md relative-position z-top"
              >
                <div class="row items-center">
                  <q-icon
                    name="model_training"
                    size="32px"
                    color="amber-3"
                    class="q-mr-sm drop-shadow-icon"
                  />
                  <div class="text-h6 text-weight-bold text-white"
                    >Demand Forecast</div
                  >
                </div>
                <q-chip
                  color="white"
                  text-color="indigo-10"
                  size="sm"
                  class="text-weight-bolder shadow-1"
                >
                  <q-icon name="memory" size="14px" class="q-mr-xs" /> AI Engine
                </q-chip>
              </div>

              <template v-if="mlForecast.loading">
                <p class="text-indigo-1 text-body2 q-mb-lg opacity-80 relative-position z-top leading-relaxed flex-grow-1">
                  Fetching latest demand forecast data...
                </p>
                <div class="ml-container-glass flex flex-center relative-position overflow-hidden shadow-soft q-mt-auto">
                  <div class="ml-animated-bg"></div>
                  <div class="text-center z-top">
                    <q-spinner-orbit size="45px" color="amber-3" />
                    <div class="text-caption text-amber-2 q-mt-md font-monospace text-weight-bold tracking-wide">
                      LOADING PREDICTIONS...
                    </div>
                  </div>
                </div>
              </template>
              
              <template v-else-if="!mlForecast.has_forecast">
                <p class="text-indigo-1 text-body2 q-mb-lg opacity-80 relative-position z-top leading-relaxed flex-grow-1">
                  Insufficient historical data to generate a reliable demand forecast. The AI engine requires more completed orders to establish baseline sales patterns.
                </p>
                <div class="ml-container-glass flex flex-center relative-position overflow-hidden shadow-soft q-mt-auto p-4 bg-indigo-9">
                  <div class="text-center z-top q-pa-md">
                    <q-icon name="analytics" size="40px" color="blue-grey-4" class="q-mb-sm" />
                    <div class="text-caption text-blue-grey-2 font-monospace text-weight-bold tracking-wide">
                      AWAITING MORE DATA
                    </div>
                  </div>
                </div>
              </template>
              
              <template v-else>
                <p class="text-indigo-1 text-body2 q-mb-md opacity-80 relative-position z-top leading-relaxed">
                  Based on recent trends, here are your top predicted demand items for today.
                </p>
                
                <div class="text-white relative-position z-top flex-grow-1">
                  <div v-for="(item, idx) in mlForecast.top_products" :key="idx" class="row justify-between items-center q-mb-sm bg-indigo-9 q-pa-sm rounded-borders">
                    <div class="text-weight-bold ellipsis" style="max-width: 70%;">
                      {{ idx + 1 }}. {{ item.product_name }}
                    </div>
                    <q-chip color="amber-3" text-color="indigo-10" size="sm" class="text-weight-bold shadow-1 q-ma-none">
                      {{ item.predicted_quantity }} units
                    </q-chip>
                  </div>
                </div>
                
                <div class="text-caption text-indigo-3 text-right q-mt-md relative-position z-top font-monospace">
                  <q-icon name="update" class="q-mr-xs" />
                  Last updated: {{ new Date(mlForecast.generated_at).toLocaleString() }}
                </div>
              </template>
            </q-card-section>
          </q-card>
        </div>
      </div>
    </div>

    <!-- ================= LIVE STORE MODAL ================= -->
    <q-dialog
      v-model="liveStoreModal"
      transition-show="scale"
      transition-hide="scale"
      @show="initMap"
    >
      <!-- max-height and column no-wrap enforce scrolling -->
      <q-card
        class="premium-glass-card column no-wrap live-store-modal card-rounded"
      >
        <!-- 1. Header: Store Name -->
        <q-card-section
          class="row items-center justify-center q-pb-sm bg-white col-auto z-top-10"
        >
          <div class="text-h6 text-weight-bold">{{
            vendorStore?.store_name || 'My Store'
          }}</div>
        </q-card-section>

        <q-separator />

        <!-- 2. Scrollable Body Container -->
        <q-card-section class="q-pa-none col scroll">
          <!-- Store Picture (or 16:9 Placeholder) -->
          <div class="full-width">
            <q-img
              v-if="vendorStore?.store_picture_url"
              :src="vendorStore.store_picture_url"
              class="store-banner"
              fit="cover"
            />
            <div
              v-else
              class="bg-grey-3 flex flex-center full-width store-placeholder"
            >
              <q-icon name="storefront" size="80px" color="grey-6" />
            </div>
          </div>

          <!-- Remaining Sequence -->
          <div class="q-pa-md">
            <q-list>
              <!-- Store Owner Full Name -->
              <q-item class="q-px-none">
                <q-item-section avatar>
                  <q-icon color="red-8" name="person" />
                </q-item-section>
                <q-item-section>
                  <q-item-label class="text-weight-bold text-dark"
                    >Store Owner</q-item-label
                  >
                  <q-item-label caption>{{
                    ownerFullName || 'Not provided'
                  }}</q-item-label>
                </q-item-section>
              </q-item>

              <!-- Phone Number -->
              <q-item class="q-px-none">
                <q-item-section avatar>
                  <q-icon color="red-8" name="call" />
                </q-item-section>
                <q-item-section>
                  <q-item-label class="text-weight-bold text-dark"
                    >Contact</q-item-label
                  >
                  <q-item-label caption>{{
                    vendorPhone || 'Not provided'
                  }}</q-item-label>
                </q-item-section>
              </q-item>

              <!-- Store Schedule -->
              <q-item class="q-px-none items-start">
                <q-item-section avatar class="q-pt-xs">
                  <q-icon color="red-8" name="schedule" />
                </q-item-section>
                <q-item-section>
                  <q-item-label class="text-weight-bold text-dark q-mb-sm"
                    >Store Schedule</q-item-label
                  >
                  <div
                    class="bg-grey-1 rounded-borders q-pa-sm custom-glass-input"
                  >
                    <div
                      v-for="day in weekDays"
                      :key="day.name"
                      class="row justify-between items-center q-py-xs"
                      :class="{
                        'text-dark': day.isOpen,
                        'text-blue-grey-4': !day.isOpen
                      }"
                    >
                      <span class="text-weight-medium text-body2">{{
                        day.name
                      }}</span>
                      <span
                        v-if="day.isOpen"
                        class="text-caption text-weight-bold"
                      >
                        {{ formatTime(day.openTime) }} -
                        {{ formatTime(day.closeTime) }}
                      </span>
                      <span v-else class="text-caption text-italic"
                        >Closed</span
                      >
                    </div>
                  </div>
                </q-item-section>
              </q-item>

              <!-- Address -->
              <q-item class="q-px-none">
                <q-item-section avatar>
                  <q-icon color="red-8" name="location_on" />
                </q-item-section>
                <q-item-section>
                  <q-item-label class="text-weight-bold text-dark"
                    >Address</q-item-label
                  >
                  <q-item-label caption>{{
                    vendorStore?.address || 'Not provided'
                  }}</q-item-label>
                </q-item-section>
              </q-item>
            </q-list>

            <!-- Leaflet Map -->
            <div
              id="store-preview-map"
              class="border-radius-8 overflow-hidden shadow-soft q-mt-sm store-preview-map-container"
            ></div>
          </div>
        </q-card-section>

        <q-separator />

        <!-- 3. Bottom Close Button (Sticky Footer) -->
        <q-card-actions
          align="right"
          class="bg-white col-auto q-pa-md z-top-10"
        >
          <q-btn
            flat
            label="Close"
            color="blue-grey-8"
            class="text-weight-bold q-px-md"
            v-close-popup
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '@/boot/axios'
import { useAuth } from '@/composables/useAuth'
import VueApexCharts from 'vue3-apexcharts'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

// ==========================================
// 1. STATE & REFS
// ==========================================
const { logout } = useAuth()
const router = useRouter()

const checkingAccess = ref(true)
const userName = ref('Vendor')
const ownerFullName = ref('Vendor')
const vendorStore = ref(null)
const vendorPhone = ref(null)
const liveStoreModal = ref(false)
const activeRevenueFilter = ref('Daily')

const mlForecast = ref({
  loading: true,
  has_forecast: false,
  summary: null,
  top_products: [],
  generated_at: null
})

const recentOrders = ref([])
const stats = ref({
  placed_orders: 0,
  preparing_orders: 0,
  picked_up_orders: 0,
  cancelled_orders: 0
})

const orderColumns = [
  { name: 'id', label: 'Order ID', field: 'id', align: 'left', sortable: true },
  { name: 'date', label: 'Date', field: 'date', align: 'left', sortable: true },
  { name: 'customer', label: 'Customer', field: 'customer', align: 'left' },
  {
    name: 'price',
    label: 'Price',
    field: 'price',
    align: 'left',
    sortable: true
  },
  { name: 'status', label: 'Status', field: 'status', align: 'left' },
  { name: 'action', label: '', field: 'action', align: 'right' }
]

const chartLoading = ref(false)
const chartSeries = ref([{ name: 'Revenue', data: [] }])
const chartOptions = ref({
  chart: {
    type: 'area',
    toolbar: { show: false },
    zoom: { enabled: false }
  },
  colors: ['#c62828'], // red-8
  dataLabels: { enabled: false },
  stroke: { curve: 'smooth', width: 3 },
  xaxis: {
    categories: [],
    labels: { style: { colors: '#78909c' } },
    axisBorder: { show: false },
    axisTicks: { show: false }
  },
  yaxis: {
    labels: {
      style: { colors: '#78909c' },
      formatter: value => {
        return '₱' + Number(value).toLocaleString('en-PH', { 
          minimumFractionDigits: 2, 
          maximumFractionDigits: 2 
        })
      }
    }
  },
  grid: {
    borderColor: '#eceff1',
    strokeDashArray: 4
  },
  fill: {
    type: 'gradient',
    gradient: {
      shadeIntensity: 1,
      opacityFrom: 0.4,
      opacityTo: 0.05,
      stops: [0, 100]
    }
  }
})

// ==========================================
// 2. COMPUTED PROPERTIES
// ==========================================
const currentHour = new Date().getHours()

const timeGreeting = computed(() => {
  if (currentHour < 12) return 'Good morning'
  if (currentHour < 18) return 'Good afternoon'
  return 'Good evening'
})

const headerThemeClass = computed(() => {
  if (currentHour < 12) return 'header-morning'
  if (currentHour < 18) return 'header-afternoon'
  return 'header-evening'
})

const headerTextClass = computed(() => {
  if (currentHour >= 18) return 'text-white'
  return 'text-blue-grey-9'
})

const subTextClass = computed(() => {
  if (currentHour >= 18) return 'text-indigo-1'
  return 'text-blue-grey-7'
})

const currentDate = computed(() => {
  const options = {
    weekday: 'long',
    month: 'long',
    day: 'numeric',
    year: 'numeric'
  }
  return new Intl.DateTimeFormat('en-US', options).format(new Date())
})

const weekDays = computed(() => {
  const fullDays = [
    'Monday',
    'Tuesday',
    'Wednesday',
    'Thursday',
    'Friday',
    'Saturday',
    'Sunday'
  ]

  if (!vendorStore.value?.operating_days) {
    return fullDays.map(name => ({
      name,
      isOpen: false,
      openTime: null,
      closeTime: null
    }))
  }

  let raw = vendorStore.value.operating_days
  if (typeof raw === 'string') {
    try {
      raw = JSON.parse(raw)
    } catch (e) {}
  }

  const defaultOpen = vendorStore.value.opening_time
    ? vendorStore.value.opening_time.substring(0, 5)
    : null
  const defaultClose = vendorStore.value.closing_time
    ? vendorStore.value.closing_time.substring(0, 5)
    : null

  return fullDays.map(dayName => {
    let isOpen = false
    let openTime = defaultOpen
    let closeTime = defaultClose

    if (raw !== null && typeof raw === 'object' && !Array.isArray(raw)) {
      if (raw[dayName]) {
        isOpen = !!raw[dayName].is_open
        if (raw[dayName].opening_time)
          openTime = raw[dayName].opening_time.substring(0, 5)
        if (raw[dayName].closing_time)
          closeTime = raw[dayName].closing_time.substring(0, 5)
      }
    } else if (Array.isArray(raw)) {
      isOpen = raw.some(d =>
        dayName.toLowerCase().startsWith(String(d).toLowerCase())
      )
    }

    return { name: dayName, isOpen, openTime, closeTime }
  })
})

const isStoreOpen = computed(() => {
  if (!vendorStore.value?.operating_days) return false

  const dayAbbreviations = [
    'Sunday',
    'Monday',
    'Tuesday',
    'Wednesday',
    'Thursday',
    'Friday',
    'Saturday'
  ]
  const now = new Date()
  const todayName = dayAbbreviations[now.getDay()]

  const todaySchedule = weekDays.value.find(d => d.name === todayName)
  if (!todaySchedule || !todaySchedule.isOpen) return false

  if (!todaySchedule.openTime || !todaySchedule.closeTime) return true

  const currentMinutes = now.getHours() * 60 + now.getMinutes()
  const [openH, openM] = todaySchedule.openTime.split(':').map(Number)
  const [closeH, closeM] = todaySchedule.closeTime.split(':').map(Number)

  const openMinutes = openH * 60 + openM
  const closeMinutes = closeH * 60 + closeM

  if (openMinutes <= closeMinutes) {
    return currentMinutes >= openMinutes && currentMinutes <= closeMinutes
  }
  // Overnight
  return currentMinutes >= openMinutes || currentMinutes <= closeMinutes
})

// ==========================================
// 3. HELPER FUNCTIONS
// ==========================================
const getStatusColor = status => {
  const normalizedStatus = status.toLowerCase().replace(/\s+/g, '_')

  switch (normalizedStatus) {
    case 'placed':
      return 'blue-5'
    case 'preparing':
      return 'amber-6'
    case 'picked_up':
      return 'green-6'
    case 'cancelled':
      return 'red-5'
    default:
      return 'blue-grey-4'
  }
}

const formatTime = timeString => {
  if (!timeString) return 'Not set'
  return new Date(`1970-01-01T${timeString}`).toLocaleTimeString([], {
    hour: '2-digit',
    minute: '2-digit'
  })
}

// ==========================================
// 4. API FUNCTIONS
// ==========================================
const fetchChartData = async () => {
  chartLoading.value = true
  try {
    const res = await api.get('/vendor/stats/chart', {
      params: { filter: activeRevenueFilter.value }
    })

    if (res.data) {
      chartSeries.value = [
        {
          name: 'Revenue',
          data: res.data.map(item => item.total)
        }
      ]
      chartOptions.value = {
        ...chartOptions.value,
        xaxis: {
          ...chartOptions.value.xaxis,
          categories: [...res.data.map(item => item.period)]
        }
      }
    }
  } catch (error) {
    console.error('Failed to load chart data:', error)
  } finally {
    chartLoading.value = false
  }
}

// ==========================================
// 5. LEAFLET FUNCTIONS
// ==========================================
let map = null
const initMap = async () => {
  await nextTick()
  if (map) {
    map.remove()
  }

  const lat = vendorStore.value?.latitude || 14.5995
  const lng = vendorStore.value?.longitude || 120.9842

  map = L.map('store-preview-map').setView([lat, lng], 15)
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map)

  // Fix custom icon path issues in Leaflet when bundled
  const icon = L.icon({
    iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
    iconRetinaUrl:
      'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
    shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34],
    shadowSize: [41, 41]
  })

  L.marker([lat, lng], { icon })
    .addTo(map)
    .bindPopup(
      '<b>' + (vendorStore.value?.store_name || 'My Store') + '</b><br>Location'
    )
    .openPopup()

  // Prevent grey tiles in modal
  setTimeout(() => {
    map.invalidateSize()
  }, 100)
}

// ==========================================
// 6. LIFECYCLE HOOKS & WATCHERS
// ==========================================
onMounted(async () => {
  try {
    fetchChartData()

    // Use the profile endpoint to guarantee store_picture data is included
    const res = await api.get('/vendor/profile')

    if (res.data) {
      const profile = res.data
      userName.value = profile.full_name
        ? profile.full_name.split(' ')[0]
        : 'Vendor'
      ownerFullName.value = profile.full_name || 'Vendor'
      vendorPhone.value = profile.phone_number || null

      if (profile.store) {
        vendorStore.value = { ...profile.store }
      } else {
        vendorStore.value = null
      }
    }

    const statsRes = await api.get('/vendor/stats')
    if (statsRes.data) {
      stats.value.placed_orders = statsRes.data.placed_orders || 0
      stats.value.preparing_orders = statsRes.data.preparing_orders || 0
      stats.value.picked_up_orders = statsRes.data.picked_up_orders || 0
      stats.value.cancelled_orders = statsRes.data.cancelled_orders || 0
      recentOrders.value = statsRes.data.recent_orders || []
    }
    
    // Fetch ML Forecast
    try {
      const mlRes = await api.get('/vendor/demand-forecast')
      if (mlRes.data) {
        mlForecast.value.has_forecast = mlRes.data.has_forecast
        mlForecast.value.summary = mlRes.data.summary
        mlForecast.value.top_products = mlRes.data.top_products || []
        mlForecast.value.generated_at = mlRes.data.generated_at
      }
    } catch (err) {
      console.error('Failed to load demand forecast:', err)
      mlForecast.value.has_forecast = false
    } finally {
      mlForecast.value.loading = false
    }
  } catch (error) {
    console.error('Dashboard init error:', error)
    userName.value = 'Vendor'
  } finally {
    checkingAccess.value = false
  }
})

watch(activeRevenueFilter, () => {
  fetchChartData()
})
</script>

<style scoped>
/* Core Page Styling */
.vendor-page {
  padding: 32px 24px;
  background-color: #f8fafc;
  min-height: 100vh;
  position: relative;
  overflow: hidden;
}

/* Ambient Background Glows */
.bg-glow {
  position: absolute;
  width: 600px;
  height: 600px;
  border-radius: 50%;
  filter: blur(140px);
  z-index: 0;
  opacity: 0.15;
  pointer-events: none;
}
.bg-glow-primary {
  top: -150px;
  left: -150px;
  background: radial-gradient(
    circle,
    rgba(185, 28, 28, 0.4) 0%,
    transparent 70%
  );
}
.bg-glow-secondary {
  bottom: -150px;
  right: -150px;
  background: radial-gradient(
    circle,
    rgba(15, 23, 42, 0.3) 0%,
    transparent 70%
  );
}

.page-container {
  max-width: 1400px;
  margin: 0 auto;
  position: relative;
  z-index: 1;
}

/* Utility Classes */
.card-rounded {
  border-radius: 16px;
}
.z-top-10 {
  z-index: 10;
}
.h-full {
  height: 100%;
}
.border-radius-8 {
  border-radius: 8px;
}
.p-1 {
  padding: 4px;
}
.opacity-50 {
  opacity: 0.5;
}
.opacity-80 {
  opacity: 0.8;
}
.flex-grow-1 {
  flex-grow: 1;
}
.tracking-wide {
  letter-spacing: 0.08em;
}
.leading-relaxed {
  line-height: 1.6;
}

/* Clean Solid Metrics Cards */
.clean-solid-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05);
  transition:
    transform 0.2s ease,
    box-shadow 0.2s ease;
}

/* Glassmorphism Panels */
.premium-glass-card {
  background: rgba(255, 255, 255, 0.98);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border: 1px solid rgba(241, 245, 249, 1);
  box-shadow: 0 4px 24px rgba(15, 23, 42, 0.04);
  transition:
    transform 0.3s cubic-bezier(0.4, 0, 0.2, 1),
    box-shadow 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.card-hover:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
}

/* ================= TIME-SYNCED HEADER THEMES ================= */
.transition-theme {
  transition:
    background 1s ease,
    color 0.5s ease;
}
.header-morning {
  background: linear-gradient(
    135deg,
    rgba(254, 243, 199, 0.85) 0%,
    rgba(224, 242, 254, 0.85) 100%
  );
  border: 1px solid rgba(255, 255, 255, 0.9);
}
.header-afternoon {
  background: linear-gradient(
    135deg,
    rgba(224, 242, 254, 0.9) 0%,
    rgba(219, 234, 254, 0.7) 100%
  );
  border: 1px solid rgba(255, 255, 255, 0.9);
}
.header-evening {
  background: linear-gradient(135deg, #1e3a8a 0%, #312e81 100%);
  border: 1px solid rgba(255, 255, 255, 0.1);
  box-shadow:
    0 15px 40px rgba(30, 58, 138, 0.3),
    inset 0 1px 0 rgba(255, 255, 255, 0.1);
}

.bg-glass {
  background: rgba(255, 255, 255, 0.2);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.3);
}

/* Status Dot Indicator */
.status-dot {
  width: 10px;
  height: 10px;
  background-color: #10b981;
  border-radius: 50%;
  box-shadow: 0 0 10px rgba(16, 185, 129, 0.6);
  animation: pulse-dot 2s infinite;
}
@keyframes pulse-dot {
  0% {
    transform: scale(0.95);
    box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
  }
  70% {
    transform: scale(1);
    box-shadow: 0 0 0 6px rgba(16, 185, 129, 0);
  }
  100% {
    transform: scale(0.95);
    box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
  }
}

/* Buttons & Icons */
.btn-premium {
  border-radius: 10px !important;
  font-weight: 700;
  transition: all 0.2s ease;
}
.btn-premium:hover {
  transform: scale(1.02);
}

.icon-premium-box {
  width: 46px;
  height: 46px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(255, 255, 255, 0.9);
}
.shadow-soft {
  box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04);
}
.border-white {
  border: 2px solid #ffffff;
}

/* Filter Buttons */
.bg-slate-50 {
  background-color: #f8fafc;
}
.border-slate-light {
  border: 1px solid #e2e8f0;
}
.transition-ease {
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}
.hover-text-dark:hover {
  color: #1e293b !important;
}

/* Panels & Tables */
.panel-header {
  background: rgba(248, 250, 252, 0.5);
  border-bottom: 1px solid rgba(226, 232, 240, 0.6);
  border-radius: 16px 16px 0 0;
}

.header-accent-red {
  width: 6px;
  height: 24px;
  background: linear-gradient(180deg, #b91c1c 0%, #450a0a 100%);
  border-radius: 6px;
}

:deep(.custom-premium-table thead tr th) {
  background: rgba(248, 250, 252, 0.5);
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  font-size: 11px;
  letter-spacing: 0.05em;
  padding: 16px 20px;
  border-bottom: 1px solid rgba(226, 232, 240, 0.8);
}
:deep(.custom-premium-table tbody td) {
  padding: 16px 20px;
  border-bottom: 1px solid rgba(241, 245, 249, 1);
  transition: all 0.2s ease;
}
:deep(.custom-premium-table tbody tr:hover) {
  background: #ffffff;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
  transform: scale(1.002);
  z-index: 5;
  position: relative;
}
:deep(.custom-premium-table tbody tr:hover td) {
  border-bottom-color: transparent;
}
.hover-icon-btn {
  transition: all 0.2s ease;
}
:deep(.custom-premium-table tbody tr:hover .hover-icon-btn) {
  color: #b91c1c !important;
  transform: translateX(4px);
  background: rgba(185, 28, 28, 0.05);
}

/* Charts */
.chart-container {
  height: 250px;
  padding: 0;
  width: 100%;       /* Siguraduhing sakop ang buong width */
  display: block;    /* Tanggalin ang flex behavior na nagpapa-shrink */
  overflow: hidden;  /* Iwas lumagpas ang SVG lines */
}
.chart-placeholder-bg {
  background-image:
    linear-gradient(rgba(226, 232, 240, 0.3) 1px, transparent 1px),
    linear-gradient(90deg, rgba(226, 232, 240, 0.3) 1px, transparent 1px);
  background-size: 20px 20px;
  border-radius: 0 0 16px 16px;
}

/* ML Blueprint Card */
.ml-blueprint-card {
  background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
  border: 1px solid rgba(255, 255, 255, 0.08);
  box-shadow:
    0 15px 40px rgba(15, 23, 42, 0.3),
    inset 0 1px 0 rgba(255, 255, 255, 0.05);
}
.ml-glow-overlay {
  position: absolute;
  top: 0;
  right: 0;
  width: 150px;
  height: 150px;
  background: radial-gradient(
    circle,
    rgba(99, 102, 241, 0.2) 0%,
    transparent 70%
  );
  filter: blur(20px);
  z-index: 0;
}
.ml-container-glass {
  background: rgba(0, 0, 0, 0.25);
  border: 1px solid rgba(251, 191, 36, 0.25);
  backdrop-filter: blur(12px);
  height: 140px;
  border-radius: 12px;
}
.ml-animated-bg {
  position: absolute;
  top: -50%;
  left: -50%;
  width: 200%;
  height: 200%;
  background: conic-gradient(
    from 0deg,
    transparent 0%,
    rgba(251, 191, 36, 0.08) 25%,
    transparent 50%
  );
  animation: rotate-bg 10s linear infinite;
}
@keyframes rotate-bg {
  100% {
    transform: rotate(360deg);
  }
}

.drop-shadow-icon {
  filter: drop-shadow(0 0 10px rgba(251, 191, 36, 0.4));
}
.font-monospace {
  font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
  letter-spacing: 0.12em;
}

/* Live Store Modal specific styles */
.live-store-modal {
  width: 500px;
  max-width: 90vw;
  max-height: 90vh;
}
.store-banner {
  height: 220px;
}
.store-placeholder {
  aspect-ratio: 16/9;
}
.custom-glass-input {
  border: 1px solid #e2e8f0;
}
.store-preview-map-container {
  height: 200px;
  border: 1px solid #cfd8dc;
  position: relative;
  z-index: 1;
}
</style>