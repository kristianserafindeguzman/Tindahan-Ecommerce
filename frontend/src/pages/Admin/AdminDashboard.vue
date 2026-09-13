<template>
  <q-page class="vp-page">
    <div class="vp-container">
      <!-- ================= GREETINGS HEADER ================= -->
      <!-- The vendor dashboard's banner, its colour following the time of day, with the admin's greeting, alert, sync, bell and clock. -->
      <AdminHero
        icon="o_dashboard"
        eyebrow="Tindahan Admin Panel"
        :eyebrow-icon="dayPhase.icon"
        :phase="dayPhase.key"
        :title="`${greeting}, ${userName}!`"
        subtitle="Here is the executive overview of your marketplace today."
        :status="attention"
        status-to="/admin/approvals"
        :loading="loading"
        :actions="heroActions"
        bell
        @action="onHeroAction"
      >
        <template #side>
          <div class="db-clock">
            <span class="db-clock-date">{{ clockDate }}</span>
            <span class="db-clock-time">{{ clockTime }}</span>
          </div>
        </template>
      </AdminHero>

      <!-- ================= KEY NUMBERS ================= -->
      <!-- The vendor dashboard's KPI cards: the label and its icon, the number with the week's change, and a note; the ones that count a page open it. -->
      <div class="db-kpis">
        <component
          :is="card.to ? 'button' : 'div'"
          v-for="card in kpis"
          :key="card.key"
          :type="card.to ? 'button' : undefined"
          class="vp-card db-kpi"
          :class="{
            'db-kpi--link': card.to,
            'db-kpi--alert': card.key === 'attention'
          }"
          @click="card.to && router.push(card.to)"
        >
          <div class="db-kpi-top">
            <span class="db-kpi-label">{{ card.label }}</span>
            <span class="db-kpi-icon" :class="`vp-tone--${card.tone}`"
              ><q-icon :name="card.icon" size="20px"
            /></span>
          </div>
          <q-skeleton v-if="loading" type="text" width="45%" height="40px" />
          <div v-else class="db-kpi-figures">
            <span class="db-kpi-value">{{ card.value }}</span>
            <span v-if="card.delta > 0" class="db-kpi-delta"
              ><q-icon name="o_arrow_upward" size="13px" />+{{
                card.delta
              }}
              this week</span
            >
          </div>
          <div class="db-kpi-foot">
            <span class="db-kpi-note">{{ card.note }}</span>
            <q-icon
              v-if="card.to"
              name="o_arrow_forward"
              size="16px"
              class="db-kpi-go"
            />
          </div>
        </component>
      </div>

      <div class="db-grid">
        <!-- ================= NEEDS ATTENTION ================= -->
        <section class="vp-card db-panel db-area-attention">
          <div class="db-panel-head">
            <h2 class="db-panel-title">
              <span class="db-panel-icon vp-tone--brand"
                ><q-icon name="o_pending_actions" size="18px"
              /></span>
              Needs Attention
            </h2>
            <router-link to="/admin/approvals" class="db-link"
              >View all<q-icon name="o_arrow_forward" size="16px"
            /></router-link>
          </div>

          <div class="db-cols db-att-grid">
            <span /><span>Application</span
            ><span class="db-att-date">Applied</span><span />
          </div>

          <div v-if="loading" class="db-rows-loading">
            <div v-for="n in 3" :key="n" class="db-att-grid db-skel-row">
              <q-skeleton
                type="rect"
                width="36px"
                height="36px"
                class="db-skel-tile"
              />
              <div class="db-two-lines">
                <q-skeleton type="text" width="55%" />
                <q-skeleton type="text" width="78%" height="12px" />
              </div>
              <div class="db-two-lines db-skel-end">
                <q-skeleton type="text" width="76px" />
                <q-skeleton type="text" width="48px" height="12px" />
              </div>
            </div>
          </div>
          <div v-else-if="!pending.length" class="db-empty">
            <q-icon name="o_task_alt" size="22px" />
            No applications waiting for review.
          </div>
          <template v-else>
            <button
              v-for="app in pending"
              :key="app.approval_id"
              type="button"
              class="db-row db-att-grid db-att-row"
              @click="actions.openReview(app)"
            >
              <span class="db-row-icon vp-tone--brand"
                ><q-icon name="o_storefront" size="18px"
              /></span>
              <span class="db-two-lines">
                <span class="db-strong">{{
                  app.store_name || 'Unnamed store'
                }}</span>
                <span class="db-soft db-ellipsis"
                  >Vendor application · Pending review</span
                >
              </span>
              <span class="db-two-lines db-att-date">
                <span>{{ formatShortDate(app.applied_at) }}</span>
                <span class="db-soft">{{ formatTime(app.applied_at) }}</span>
              </span>
              <q-icon name="o_chevron_right" size="20px" class="db-row-arrow" />
            </button>
          </template>
        </section>

        <!-- ================= PLATFORM OVERVIEW ================= -->
        <!-- Vendors and consumers on each of the last seven days, counted back from today's totals using approval and sign-up dates. -->
        <section class="vp-card db-panel db-area-overview db-overview">
          <div class="db-panel-head">
            <h2 class="db-panel-title">
              <span class="db-panel-icon vp-tone--brand"
                ><q-icon name="o_insights" size="18px"
              /></span>
              Platform Overview
            </h2>
            <div class="db-legend-inline">
              <span
                ><span
                  class="db-dot"
                  :style="{ background: VENDOR_COLOR }"
                />Vendors</span
              >
              <span
                ><span
                  class="db-dot"
                  :style="{ background: CONSUMER_COLOR }"
                />Consumers</span
              >
            </div>
          </div>
          <div class="db-chart">
            <q-skeleton v-if="loading" type="rect" height="220px" />
            <VueApexCharts
              v-else
              type="area"
              height="240"
              width="100%"
              :options="overviewOptions"
              :series="overviewSeries"
            />
          </div>
        </section>

        <!-- ================= SIDE ================= -->
        <div class="db-area-side">
          <section class="vp-card db-panel db-ratio">
            <div class="db-panel-head">
              <h2 class="db-panel-title">Ecosystem Ratio</h2>
            </div>
            <div class="db-ratio-body">
              <div class="db-ratio-chart">
                <div v-if="loading" class="db-skel-ring">
                  <q-skeleton type="circle" size="150px" />
                  <span class="db-skel-hole" />
                </div>
                <div v-else-if="!mixTotal" class="db-soft">No accounts yet</div>
                <template v-else>
                  <VueApexCharts
                    type="donut"
                    height="170"
                    width="170"
                    :options="donutOptions"
                    :series="donutSeries"
                  />
                  <div class="db-ratio-center">
                    <span :style="{ color: VENDOR_COLOR }">{{
                      mixRows[0].share
                    }}</span>
                    <span :style="{ color: CONSUMER_COLOR }">{{
                      mixRows[1].share
                    }}</span>
                  </div>
                </template>
              </div>
              <ul class="db-ratio-legend">
                <li v-for="row in mixRows" :key="row.label">
                  <span class="db-dot" :style="{ background: row.color }" />
                  <span class="db-ratio-label">{{ row.label }}</span>
                  <span class="db-ratio-value"
                    >{{ loading ? '—' : row.value }}
                    <span class="db-soft">{{
                      loading ? '' : `(${row.share})`
                    }}</span></span
                  >
                </li>
              </ul>
            </div>
          </section>
        </div>

        <!-- ================= RECENT ACTIVITY ================= -->
        <!-- The latest applications, decisions and sign-ups, newest first. -->
        <section class="vp-card db-panel db-area-activity">
          <div class="db-panel-head">
            <h2 class="db-panel-title">
              <span class="db-panel-icon vp-tone--info"
                ><q-icon name="o_history" size="18px"
              /></span>
              Recent Activity
            </h2>
          </div>

          <div class="db-cols db-act-grid">
            <span>Time</span><span>Activity</span><span>User</span><span />
          </div>

          <div v-if="loading" class="db-rows-loading">
            <div v-for="n in 3" :key="n" class="db-act-grid db-skel-row">
              <div class="db-act-time">
                <q-skeleton type="text" width="84px" />
                <q-skeleton type="text" width="52px" height="12px" />
              </div>
              <q-skeleton type="text" width="70%" class="db-act-text" />
              <div class="db-act-user">
                <q-skeleton
                  type="rect"
                  width="28px"
                  height="28px"
                  class="db-skel-tile"
                />
                <q-skeleton type="text" width="60%" />
              </div>
            </div>
          </div>
          <div v-else-if="!activity.length" class="db-empty">
            <q-icon name="o_history" size="22px" />
            Nothing has happened yet.
          </div>
          <template v-else>
            <button
              v-for="item in activity"
              :key="item.key"
              type="button"
              class="db-row db-act-grid db-activity-row"
              @click="router.push(item.to)"
            >
              <span class="db-act-time"
                >{{ formatShortDate(item.at) }}
                <span class="db-soft">{{ formatTime(item.at) }}</span></span
              >
              <span class="db-act-text">{{ item.text }}</span>
              <span class="db-act-user">
                <q-icon
                  :name="item.icon"
                  size="16px"
                  :class="`db-act-icon--${item.kind}`"
                />
                <span class="db-act-name">{{ item.who }}</span>
              </span>
              <q-icon name="o_chevron_right" size="20px" class="db-row-arrow" />
            </button>
          </template>
        </section>
      </div>
    </div>

    <ApplicationActions ref="actions" @decided="onDecided" />
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import VueApexCharts from 'vue3-apexcharts'
import { api } from '@/boot/axios'
import AdminHero from '@/components/admin/AdminHero.vue'
import ApplicationActions from '@/components/admin/ApplicationActions.vue'
import { useAdminNotifications } from '@/composables/useAdminNotifications'
import { formatShortDate } from '@/utils/accountStatus'
import '@/css/admin-pages.scss'

const router = useRouter()
const $q = useQuasar()
const { fetchNotifications } = useAdminNotifications()

// Vendors in blue and consumers in green, the colours of their cards.
const VENDOR_COLOR = '#2563eb'
const CONSUMER_COLOR = '#16a34a'
const DAY = 86400000

const loading = ref(true)
const syncing = ref(false)
const stats = ref({
  total_vendors: 0,
  pending_approvals: 0,
  total_consumers: 0,
  total_users: 0
})
// Every application (pending, approved and rejected) and the active consumers, for the lists, the week's changes and the chart.
const applications = ref([])
const consumers = ref([])
// The day the numbers were loaded, so the chart only redraws when they are.
const loadedAt = ref(new Date())
const actions = ref(null)

// The signed-in admin's name from the sign-in record, as the old dashboard greeted them.
const userName = (() => {
  try {
    return (
      JSON.parse(localStorage.getItem('auth_user') || '{}').full_name || 'Admin'
    )
  } catch {
    return 'Admin'
  }
})()

// CLOCK — the banner's live date and time.
const now = ref(new Date())
let clockTimer = null

const greeting = computed(() => {
  const hour = now.value.getHours()
  if (hour < 12) return 'Good morning'
  if (hour < 18) return 'Good afternoon'
  return 'Good evening'
})

// The banner's colour and icon for each part of the day, the same hours as the vendor dashboard; the clock ticks, so it changes on its own.
const dayPhase = computed(() => {
  const hour = now.value.getHours()
  if (hour >= 5 && hour < 8) return { key: 'dawn', icon: 'o_wb_twilight' }
  if (hour >= 8 && hour < 12) return { key: 'morning', icon: 'o_light_mode' }
  if (hour >= 12 && hour < 17) return { key: 'afternoon', icon: 'o_wb_sunny' }
  if (hour >= 17 && hour < 19) return { key: 'evening', icon: 'o_wb_twilight' }
  return { key: 'night', icon: 'o_dark_mode' }
})

const clockDate = computed(() =>
  now.value.toLocaleDateString('en-US', {
    weekday: 'short',
    month: 'short',
    day: 'numeric',
    year: 'numeric'
  })
)
const clockTime = computed(() =>
  now.value.toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  })
)

const formatTime = value => {
  const date = new Date(value)
  return Number.isNaN(date.getTime())
    ? ''
    : date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })
}

// The old banner's "Action required" alert, shown while any store is waiting.
const attention = computed(() => {
  const count = Number(stats.value.pending_approvals) || 0
  return count > 0
    ? `Action required: ${count} ${count === 1 ? 'task' : 'tasks'}`
    : ''
})

const heroActions = computed(() => [
  { key: 'review', label: 'Review Applications', icon: 'o_arrow_forward' },
  {
    key: 'sync',
    label: 'Sync Data',
    icon: 'o_sync',
    ghost: true,
    loading: syncing.value
  }
])

// THE WEEK — approvals and sign-ups in the last seven days, from their own dates.
const toTime = value => {
  const time = new Date(value).getTime()
  return Number.isNaN(time) ? null : time
}
const approvedTimes = computed(() =>
  applications.value
    .filter(app => app.status === 'approved')
    .map(app => toTime(app.reviewed_at))
    .filter(Boolean)
)
const signupTimes = computed(() =>
  consumers.value.map(c => toTime(c.created_at)).filter(Boolean)
)
const weekStart = computed(() => loadedAt.value.getTime() - 7 * DAY)
const newVendors = computed(
  () => approvedTimes.value.filter(t => t > weekStart.value).length
)
const newConsumers = computed(
  () => signupTimes.value.filter(t => t > weekStart.value).length
)

const kpis = computed(() => [
  {
    key: 'attention',
    label: 'Needs attention',
    note: 'Pending vendor applications awaiting review and authorization.',
    value: stats.value.pending_approvals ?? 0,
    icon: 'o_pending_actions',
    tone: 'brand',
    delta: 0,
    to: '/admin/approvals'
  },
  {
    key: 'vendors',
    label: 'Approved vendors',
    note: 'Stores approved to sell on Tindahan.',
    value: stats.value.total_vendors ?? 0,
    icon: 'o_storefront',
    tone: 'info',
    delta: newVendors.value,
    to: '/admin/vendors'
  },
  {
    key: 'consumers',
    label: 'Active consumers',
    note: 'Registered consumers on the platform.',
    value: stats.value.total_consumers ?? 0,
    icon: 'o_groups',
    tone: 'success',
    delta: newConsumers.value,
    to: '/admin/consumers'
  },
  {
    key: 'users',
    label: 'Total platform users',
    note: 'Vendors + consumers',
    value: stats.value.total_users ?? 0,
    icon: 'o_people_alt',
    tone: 'neutral',
    delta: newVendors.value + newConsumers.value,
    to: null
  }
])

// NEEDS ATTENTION — the newest applications still waiting.
const pending = computed(() =>
  applications.value
    .filter(app => app.status === 'pending')
    .sort((a, b) => new Date(b.applied_at) - new Date(a.applied_at))
    .slice(0, 4)
)

// PLATFORM OVERVIEW — each of the last seven days, counting back from today's totals: anyone approved or signed up after that day is taken off.
const overviewDays = computed(() =>
  Array.from({ length: 7 }, (_, i) => {
    const day = new Date(loadedAt.value)
    day.setHours(23, 59, 59, 999)
    day.setDate(day.getDate() - (6 - i))
    return day
  })
)
const countOn = (times, total, day) =>
  Math.max(0, total - times.filter(t => t > day.getTime()).length)
const overviewSeries = computed(() => [
  {
    name: 'Vendors',
    data: overviewDays.value.map(day =>
      countOn(approvedTimes.value, Number(stats.value.total_vendors) || 0, day)
    )
  },
  {
    name: 'Consumers',
    data: overviewDays.value.map(day =>
      countOn(signupTimes.value, Number(stats.value.total_consumers) || 0, day)
    )
  }
])
const overviewOptions = computed(() => ({
  chart: {
    type: 'area',
    background: 'transparent',
    toolbar: { show: false },
    zoom: { enabled: false },
    fontFamily: 'Roboto, Arial, sans-serif'
  },
  colors: [VENDOR_COLOR, CONSUMER_COLOR],
  dataLabels: { enabled: false },
  legend: { show: false },
  stroke: { curve: 'straight', width: 2.5 },
  fill: {
    type: 'gradient',
    gradient: {
      shadeIntensity: 1,
      opacityFrom: 0.22,
      opacityTo: 0.02,
      stops: [0, 95, 100]
    }
  },
  markers: { size: 4, strokeWidth: 0, hover: { size: 6 } },
  grid: {
    borderColor: '#f0ebe7',
    strokeDashArray: 0,
    padding: { left: 6, right: 10 }
  },
  xaxis: {
    categories: overviewDays.value.map(day =>
      day.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
    ),
    axisBorder: { show: false },
    axisTicks: { show: false },
    labels: { style: { colors: '#77716d', fontSize: '11.5px' } }
  },
  yaxis: {
    min: 0,
    forceNiceScale: true,
    labels: {
      formatter: value => Math.round(value),
      style: { colors: '#77716d', fontSize: '11.5px' }
    }
  },
  tooltip: { y: { formatter: value => `${value} accounts` } }
}))

// ECOSYSTEM RATIO
const mixTotal = computed(
  () =>
    (Number(stats.value.total_vendors) || 0) +
    (Number(stats.value.total_consumers) || 0)
)
const share = value =>
  mixTotal.value ? `${Math.round((value / mixTotal.value) * 100)}%` : '0%'
const mixRows = computed(() => {
  const vendors = Number(stats.value.total_vendors) || 0
  const shoppers = Number(stats.value.total_consumers) || 0
  return [
    {
      label: 'Vendors',
      value: vendors,
      color: VENDOR_COLOR,
      share: share(vendors)
    },
    {
      label: 'Consumers',
      value: shoppers,
      color: CONSUMER_COLOR,
      share: share(shoppers)
    }
  ]
})
const donutSeries = computed(() => mixRows.value.map(row => row.value))
const donutOptions = computed(() => ({
  chart: {
    type: 'donut',
    background: 'transparent',
    fontFamily: 'Roboto, Arial, sans-serif'
  },
  labels: ['Vendors', 'Consumers'],
  colors: [VENDOR_COLOR, CONSUMER_COLOR],
  legend: { show: false },
  dataLabels: { enabled: false },
  stroke: { width: 3, colors: ['#ffffff'] },
  tooltip: { y: { formatter: value => `${value} accounts` } },
  plotOptions: { pie: { donut: { size: '66%', labels: { show: false } } } }
}))

// RECENT ACTIVITY — applications, decisions and sign-ups, each from its own date.
const activity = computed(() => {
  const items = []
  for (const app of applications.value) {
    const who = app.store_name || 'Unnamed store'
    if (app.applied_at)
      items.push({
        key: `applied-${app.approval_id}`,
        at: app.applied_at,
        text: 'New vendor application submitted',
        who,
        icon: 'o_storefront',
        kind: 'store',
        to: '/admin/approvals'
      })
    if (app.reviewed_at && app.status === 'approved')
      items.push({
        key: `approved-${app.approval_id}`,
        at: app.reviewed_at,
        text: 'Vendor application approved',
        who,
        icon: 'o_storefront',
        kind: 'store',
        to: '/admin/vendors'
      })
    if (app.reviewed_at && app.status === 'rejected')
      items.push({
        key: `rejected-${app.approval_id}`,
        at: app.reviewed_at,
        text: 'Vendor application rejected',
        who,
        icon: 'o_storefront',
        kind: 'store',
        to: '/admin/approvals'
      })
  }
  for (const consumer of consumers.value) {
    if (consumer.created_at)
      items.push({
        key: `joined-${consumer.user_id}`,
        at: consumer.created_at,
        text: 'Consumer registered',
        who: consumer.full_name || 'Unnamed consumer',
        icon: 'o_person',
        kind: 'person',
        to: '/admin/consumers'
      })
  }
  return items.sort((a, b) => new Date(b.at) - new Date(a.at)).slice(0, 5)
})

const listOf = res =>
  Array.isArray(res.data) ? res.data : res.data?.data || []

const loadDashboard = async () => {
  try {
    const [statsRes, appsRes, consumersRes] = await Promise.all([
      api.get('/admin/stats'),
      api.get('/admin/vendors/pending'),
      api.get('/admin/consumers', { params: { tab: 'active' } })
    ])
    stats.value = { ...stats.value, ...statsRes.data }
    applications.value = listOf(appsRes)
    consumers.value = listOf(consumersRes)
    loadedAt.value = new Date()
  } catch (error) {
    console.error('Failed to load the admin dashboard', error)
    $q.notify({
      type: 'negative',
      message: 'Couldn’t load the dashboard. Please refresh.'
    })
  } finally {
    loading.value = false
  }
}

// Sync reloads the numbers, the lists and the bell together, as the old sync button did.
const syncDashboard = async () => {
  syncing.value = true
  await Promise.all([loadDashboard(), fetchNotifications()])
  syncing.value = false
  $q.notify({ type: 'positive', message: 'Dashboard synced.' })
}

const onHeroAction = key => {
  if (key === 'review') router.push('/admin/approvals')
  if (key === 'sync') syncDashboard()
}

// A decided application leaves Needs Attention, shows up in Recent Activity, and the counts follow.
const onDecided = ({ storeId, status, reason }) => {
  applications.value = applications.value.map(app =>
    app.store_id === storeId
      ? {
          ...app,
          status,
          reviewed_at: new Date().toISOString(),
          rejection_reason: reason || app.rejection_reason
        }
      : app
  )
  stats.value.pending_approvals = Math.max(
    0,
    (Number(stats.value.pending_approvals) || 0) - 1
  )
  if (status === 'approved') {
    stats.value.total_vendors = (Number(stats.value.total_vendors) || 0) + 1
    stats.value.total_users = (Number(stats.value.total_users) || 0) + 1
  }
}

onMounted(() => {
  loadDashboard()
  clockTimer = setInterval(() => {
    now.value = new Date()
  }, 1000)
})

onBeforeUnmount(() => clearInterval(clockTimer))
</script>

<style scoped>
/* CLOCK — the date and time in the banner's frosted glass. */
.db-clock {
  display: flex;
  flex-direction: column;
  align-items: flex-end;

  gap: 6px;
  min-width: 170px;
  padding: 16px 22px;

  border: 1px solid rgba(255, 255, 255, 0.28);
  border-radius: var(--r-surface);

  background: rgba(255, 255, 255, 0.12);

  color: #ffffff;
}

.db-clock-date {
  font-size: var(--fs-2xs);
  font-weight: 600;
  letter-spacing: 0.06em;
  text-transform: uppercase;

  color: rgba(255, 255, 255, 0.8);
}

.db-clock-time {
  font-family: 'Poppins', 'Roboto', Arial, sans-serif;
  font-size: 30px;
  font-weight: 800;
  line-height: 1;
  font-variant-numeric: tabular-nums;
}

/* KEY NUMBERS — the vendor dashboard's KPI cards: white, a soft shadow, the label beside a tone tile, and a firmer edge on hover. */
.db-kpis {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));

  gap: var(--sp-gap);
  margin-bottom: var(--sp-gap);
}

.db-kpi {
  display: flex;
  flex-direction: column;

  gap: 10px;
  min-width: 0;
  padding: 20px;

  font-family: inherit;
  text-align: left;
}

.db-kpi--link {
  cursor: pointer;

  transition:
    border-color 0.2s,
    box-shadow 0.2s;
}

.db-kpi--link:hover {
  border-color: var(--c-border-strong);
  box-shadow: var(--sh-card-hover);
}

.db-kpi--link:focus-visible {
  outline: 2px solid var(--c-brand);
  outline-offset: 2px;
}

.db-kpi-top {
  display: flex;
  align-items: center;
  justify-content: space-between;

  gap: 8px;
}

.db-kpi-label {
  font-size: var(--fs-sm);
  font-weight: 600;

  color: var(--c-text-3);
}

.db-kpi-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;

  width: 40px;
  height: 40px;

  border-radius: var(--r-surface);
}

.db-kpi-figures {
  display: flex;
  align-items: baseline;
  flex-wrap: wrap;

  gap: 4px 10px;
}

.db-kpi-value {
  font-size: var(--fs-4xl);
  font-weight: 700;
  line-height: 1.1;

  color: var(--c-text);
}

/* The pending count reads in brand red, since it is the one that asks for action. */
.db-kpi--alert .db-kpi-value {
  color: var(--c-brand);
}

.db-kpi-delta {
  display: inline-flex;
  align-items: center;

  gap: 2px;
  padding: 2px 8px;

  border-radius: var(--r-pill);

  background: var(--c-success-wash);

  font-size: var(--fs-xs);
  font-weight: 700;

  color: var(--c-success);
}

.db-kpi-foot {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;

  gap: 8px;
  margin-top: auto;
}

.db-kpi-note {
  font-size: var(--fs-xs);
  line-height: 1.45;

  color: var(--c-subtle);
}

.db-kpi-go {
  flex-shrink: 0;

  color: var(--c-border-strong);

  transition:
    color 0.15s,
    transform 0.2s;
}

.db-kpi--link:hover .db-kpi-go {
  color: var(--c-brand);

  transform: translateX(3px);
}

/* PANELS — Needs Attention and the chart side by side, the red card and the ratio down the right, Recent Activity along the bottom. */
.db-grid {
  display: grid;
  grid-template-columns: minmax(0, 1.35fr) minmax(0, 1.2fr) minmax(0, 0.9fr);
  grid-template-areas:
    'attention overview side'
    'activity activity side';

  gap: var(--sp-gap);
  align-items: start;
}

.db-area-attention {
  grid-area: attention;
}

.db-area-overview {
  grid-area: overview;
}

.db-area-side {
  grid-area: side;

  display: flex;
  flex-direction: column;

  gap: var(--sp-gap);
}

.db-area-activity {
  grid-area: activity;
}

.db-area-attention,
.db-area-overview {
  align-self: stretch;
}

.db-panel {
  min-width: 0;
  padding-bottom: 8px;
}

.db-panel-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;

  gap: 10px;
  padding: 18px 20px 12px;
}

.db-panel-title {
  display: flex;
  align-items: center;

  gap: 10px;
  margin: 0;

  font-size: var(--fs-lg);
  font-weight: 700;
  line-height: 1.3;

  color: var(--c-text);
}

.db-panel-icon {
  display: flex;
  align-items: center;
  justify-content: center;

  width: 32px;
  height: 32px;

  border-radius: var(--r-control);
}

.db-link {
  display: inline-flex;
  align-items: center;

  gap: 4px;

  font-size: var(--fs-sm);
  font-weight: 600;
  text-decoration: none;

  color: var(--c-brand);
}

.db-link .q-icon {
  transition: transform 0.2s;
}

.db-link:hover .q-icon {
  transform: translateX(3px);
}

/* ROWS — a small column heading line, then rows that open what they describe. */
.db-cols {
  padding: 8px 20px;

  border-top: 1px solid var(--c-hairline);
  border-bottom: 1px solid var(--c-hairline);

  background: var(--c-surface-2);

  font-size: var(--fs-2xs);
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;

  color: var(--c-muted);
}

.db-att-grid {
  display: grid;
  grid-template-columns: 36px minmax(0, 1fr) auto 20px;
  align-items: center;

  gap: 12px;
}

.db-act-grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1.5fr) minmax(0, 1.3fr) 20px;
  align-items: center;

  gap: 12px;
}

.db-row {
  width: 100%;
  padding: 12px 20px;

  border: none;
  border-bottom: 1px solid var(--c-hairline);

  background: transparent;

  font-family: inherit;
  font-size: var(--fs-sm);
  text-align: left;

  color: var(--c-text-2);

  cursor: pointer;

  transition: background-color 0.15s;
}

.db-row:last-child {
  border-bottom: none;
}

.db-row:hover {
  background: var(--c-surface-2);
}

/* Focus shows as a soft bar down the left edge rather than a box, since it also lands here when a dialog closes. */
.db-row:focus-visible {
  outline: none;

  background: var(--c-surface-2);
  box-shadow: inset 3px 0 0 var(--c-brand);
}

.db-row-arrow {
  color: var(--c-border-strong);

  transition:
    color 0.15s,
    transform 0.2s;
}

.db-row:hover .db-row-arrow {
  color: var(--c-brand);

  transform: translateX(2px);
}

.db-row-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;

  width: 36px;
  height: 36px;

  border-radius: var(--r-control);
}

.db-att-type {
  display: flex;
  align-items: center;

  gap: 10px;
  min-width: 0;
}

.db-att-type-label {
  font-weight: 600;

  color: var(--c-brand);
}

.db-two-lines {
  display: flex;
  flex-direction: column;

  gap: 2px;
  min-width: 0;
}

.db-strong {
  overflow: hidden;

  font-weight: 700;
  text-overflow: ellipsis;
  white-space: nowrap;

  color: var(--c-text);
}

.db-soft {
  font-size: var(--fs-xs);

  color: var(--c-muted);
}

.db-att-date {
  font-size: var(--fs-xs);
  text-align: right;
  white-space: nowrap;
}

.db-ellipsis {
  overflow: hidden;

  text-overflow: ellipsis;
  white-space: nowrap;
}

.db-rows-loading {
  display: flex;
  flex-direction: column;
}

/* Placeholder rows shaped like the real ones: the same columns, a tile, two lines and the date. */
.db-skel-row {
  padding: 12px 20px;

  border-bottom: 1px solid var(--c-hairline);
}

.db-skel-row:last-child {
  border-bottom: none;
}

.db-skel-tile {
  border-radius: var(--r-control);
}

.db-skel-end {
  align-items: flex-end;
}

/* The donut's placeholder is a ring, not a disc. */
.db-skel-ring {
  position: relative;

  width: 150px;
  height: 150px;
}

.db-skel-hole {
  position: absolute;
  inset: 26px;
  z-index: 2;

  border-radius: 50%;

  background: #ffffff;
}

.db-empty {
  display: flex;
  align-items: center;

  gap: 8px;
  padding: 24px 20px;

  font-size: var(--fs-sm);

  color: var(--c-muted);
}

.db-act-time {
  display: flex;
  flex-direction: column;

  font-weight: 600;
  white-space: nowrap;

  color: var(--c-text);
}

.db-act-text {
  color: var(--c-text-2);
}

.db-act-user {
  display: flex;
  align-items: center;

  gap: 8px;
  min-width: 0;
}

.db-act-name {
  overflow: hidden;

  font-weight: 600;
  text-overflow: ellipsis;
  white-space: nowrap;

  color: var(--c-text);
}

/* Each store or person sits in a small tile in its colour: red for stores, as in Needs Attention, and green for consumers, as in the charts. */
.db-act-user .q-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;

  width: 28px;
  height: 28px;

  border-radius: var(--r-control);
}

.db-act-icon--store {
  background: var(--c-brand-tint);

  color: var(--c-brand);
}

.db-act-icon--person {
  background: var(--c-success-tint);

  color: var(--c-success);
}

/* PLATFORM OVERVIEW */
.db-legend-inline {
  display: flex;
  flex-wrap: wrap;

  gap: 14px;

  font-size: var(--fs-xs);
  font-weight: 600;

  color: var(--c-text-2);
}

.db-legend-inline > span {
  display: inline-flex;
  align-items: center;

  gap: 6px;
}

.db-dot {
  flex-shrink: 0;

  width: 9px;
  height: 9px;

  border-radius: 50%;
}

.db-chart {
  padding: 0 12px 4px;
}

/* ECOSYSTEM RATIO — the donut with both shares in its middle, and the counts beside it. */
.db-ratio-body {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  justify-content: center;

  gap: 8px 16px;
  padding: 0 16px 12px;
}

.db-ratio-chart {
  position: relative;

  display: flex;
  align-items: center;
  justify-content: center;

  width: 170px;
  height: 170px;
}

.db-ratio-center {
  position: absolute;
  inset: 0;

  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;

  font-size: var(--fs-sm);
  font-weight: 800;
  line-height: 1.35;

  pointer-events: none;
}

.db-ratio-legend {
  display: flex;
  flex-direction: column;

  gap: 10px;
  margin: 0;
  padding: 0;

  list-style: none;
}

.db-ratio-legend li {
  display: flex;
  align-items: center;

  gap: 8px;

  font-size: var(--fs-sm);
}

.db-ratio-label {
  min-width: 76px;

  font-weight: 600;

  color: var(--c-text-2);
}

.db-ratio-value {
  font-weight: 700;

  color: var(--c-text);
}

/* Narrower screens: two cards to a row, and the panels in two columns, then one. */
@media (max-width: 1399px) {
  .db-grid {
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    grid-template-areas:
      'attention overview'
      'activity side';
  }
}

@media (max-width: 1279px) {
  .db-kpis {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 1023px) {
  .db-grid {
    grid-template-columns: minmax(0, 1fr);
    grid-template-areas:
      'attention'
      'overview'
      'side'
      'activity';
  }

  .db-area-side {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  }
}

@media (max-width: 600px) {
  .db-clock {
    flex-direction: row;
    align-items: center;
    justify-content: space-between;

    min-width: 0;
    padding: 12px 16px;
  }

  .db-clock-time {
    font-size: 22px;
  }

  .db-kpis {
    grid-template-columns: minmax(0, 1fr);
  }

  .db-kpi-value {
    font-size: 30px;
  }

  .db-panel-head {
    padding: 16px 16px 10px;
  }

  /* Phones drop the column headings and fold each row into the icon, the details and the arrow. */
  .db-cols {
    display: none;
  }

  .db-row {
    padding: 12px 16px;
  }

  .db-act-grid {
    grid-template-columns: minmax(0, 1fr) 20px;
    grid-template-areas:
      'text arrow'
      'user arrow'
      'time arrow';

    gap: 2px 12px;
  }

  .db-act-text {
    grid-area: text;

    font-weight: 600;

    color: var(--c-text);
  }

  .db-act-user {
    grid-area: user;
  }

  .db-act-time {
    grid-area: time;
    flex-direction: row;

    gap: 6px;

    font-size: var(--fs-xs);
    font-weight: 400;

    color: var(--c-muted);
  }

  .db-activity-row .db-row-arrow {
    grid-area: arrow;
  }
}
</style>
