<template>
  <q-page class="vp-page">
    <div class="vp-container">
      <!-- ================= GREETINGS HEADER ================= -->
      <AdminHero
        icon="o_dashboard"
        eyebrow="Tindahan Admin Panel"
        :eyebrow-icon="dayPhase.icon"
        :phase="dayPhase.key"
        :title="`${greeting}, ${userName}!`"
        :subtitle="t('heroSubtitle')"
        :status="attention"
        status-to="/admin/approvals"
        :loading="loading"
        :actions="heroActions"
        bell
        class="animate-slide-up"
        style="animation-delay: 0s;"
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
      <div class="db-kpis">
        <component
          :is="card.to ? 'button' : 'div'"
          v-for="(card, index) in kpis"
          :key="card.key"
          :type="card.to ? 'button' : undefined"
          class="vp-card db-kpi animate-slide-up"
          :class="{
            'db-kpi--link': card.to,
            'db-kpi--alert': card.key === 'attention'
          }"
          :style="{ animationDelay: `${0.1 + (index * 0.08)}s` }"
          @click="card.to && router.push(card.to)"
        >
          <div class="db-kpi-top">
            <span class="db-kpi-label">{{ card.label }}</span>
            <span class="db-kpi-icon" :class="`vp-tone--${card.tone}`">
              <q-icon :name="card.icon" size="20px" />
            </span>
          </div>
          <q-skeleton v-if="loading" type="text" width="45%" height="40px" />
          <div v-else class="db-kpi-figures">
            <span class="db-kpi-value">{{ card.value }}</span>
            <span v-if="card.delta > 0" class="db-kpi-delta">
              <q-icon name="o_arrow_upward" size="13px" />+{{ card.delta }} {{ t('thisWeek') }}
            </span>
          </div>
          <div v-if="card.to" class="db-kpi-foot">
            <q-icon name="o_arrow_forward" size="16px" class="db-kpi-go q-ml-auto" />
          </div>
        </component>
      </div>

      <div class="db-grid">
        <!-- ================= NEEDS ATTENTION ================= -->
        <section class="vp-card db-panel db-area-attention column animate-slide-up" style="animation-delay: 0.3s;">
          <div class="db-panel-head">
            <h2 class="db-panel-title dialog-title-text">
              <span class="db-panel-icon vp-tone--brand">
                <q-icon name="o_pending_actions" size="18px" />
              </span>
              {{ t('needsAttention') }}
            </h2>
            <router-link to="/admin/approvals" class="db-link">
              {{ t('viewAll') }}<q-icon name="o_arrow_forward" size="16px" />
            </router-link>
          </div>

          <div class="db-cols db-att-grid">
            <span />
            <span>{{ t('applicationCol') }}</span>
            <span class="db-att-date">{{ t('appliedCol') }}</span>
            <span />
          </div>

          <div v-if="loading" class="db-rows-loading col flex-1">
            <div v-for="n in 3" :key="n" class="db-att-grid db-skel-row">
              <q-skeleton type="rect" width="36px" height="36px" class="db-skel-tile" />
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
          <div v-else-if="!pending.length" class="db-empty col flex flex-center text-center">
            <q-icon name="o_task_alt" size="28px" class="q-mb-xs text-muted-themed" />
            <span class="text-muted-themed">{{ t('noPendingApps') }}</span>
          </div>
          <template v-else>
            <button
              v-for="app in pending"
              :key="app.approval_id"
              type="button"
              class="db-row db-att-grid db-att-row"
              @click="actions.openReview(app)"
            >
              <span class="db-row-icon vp-tone--brand">
                <q-icon name="o_storefront" size="18px" />
              </span>
              <span class="db-two-lines">
                <span class="db-strong">{{ app.store_name || t('unnamedStore') }}</span>
                <span class="db-soft db-ellipsis text-muted-themed">{{ t('vendorAppPending') }}</span>
              </span>
              <span class="db-two-lines db-att-date">
                <span class="dialog-title-text">{{ formatFullDayDate(app.applied_at) }}</span>
                <span class="db-soft text-muted-themed">{{ formatTime(app.applied_at) }}</span>
              </span>
              <q-icon name="o_chevron_right" size="20px" class="db-row-arrow" />
            </button>
          </template>
        </section>

        <!-- ================= PLATFORM OVERVIEW ================= -->
        <section class="vp-card db-panel db-area-overview db-overview animate-slide-up" style="animation-delay: 0.4s;">
          <div class="db-panel-head">
            <h2 class="db-panel-title dialog-title-text">
              <span class="db-panel-icon vp-tone--brand">
                <q-icon name="o_insights" size="18px" />
              </span>
              {{ t('platformOverview') }}
            </h2>
            <div class="db-legend-inline">
              <span class="text-muted-themed">
                <span class="db-dot" :style="{ background: VENDOR_COLOR }" />{{ t('vendorsLegend') }}
              </span>
              <span class="text-muted-themed">
                <span class="db-dot" :style="{ background: CONSUMER_COLOR }" />{{ t('consumersLegend') }}
              </span>
            </div>
          </div>
          <div class="db-chart">
            <q-skeleton v-if="loading" type="rect" height="240px" />
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

        <!-- ================= SIDE (ECOSYSTEM RATIO) ================= -->
        <div class="db-area-side">
          <section class="vp-card db-panel db-ratio animate-slide-up" style="animation-delay: 0.5s;">
            <div class="db-panel-head">
              <h2 class="db-panel-title dialog-title-text">{{ t('ecosystemRatio') }}</h2>
            </div>
            <div class="db-ratio-body">
              <div class="db-ratio-chart">
                <div v-if="loading" class="db-skel-ring">
                  <q-skeleton type="circle" size="150px" />
                  <span class="db-skel-hole" />
                </div>
                <div v-else-if="!mixTotal" class="db-soft text-muted-themed">{{ t('noAccountsYet') }}</div>
                <template v-else>
                  <VueApexCharts
                    type="donut"
                    height="170"
                    width="170"
                    :options="donutOptions"
                    :series="donutSeries"
                  />
                  <div class="db-ratio-center">
                    <span :style="{ color: VENDOR_COLOR }">{{ mixRows[0].share }}</span>
                    <span :style="{ color: CONSUMER_COLOR }">{{ mixRows[1].share }}</span>
                  </div>
                </template>
              </div>
              <ul class="db-ratio-legend">
                <li v-for="row in mixRows" :key="row.label">
                  <span class="db-dot" :style="{ background: row.color }" />
                  <span class="db-ratio-label text-muted-themed">{{ row.label }}</span>
                  <span class="db-ratio-value dialog-title-text">
                    {{ loading ? '—' : row.value }}
                    <span class="db-soft text-muted-themed">{{ loading ? '' : `(${row.share})` }}</span>
                  </span>
                </li>
              </ul>
            </div>
          </section>
        </div>

        <!-- ================= RECENT ACTIVITY ================= -->
        <section class="vp-card db-panel db-area-activity animate-slide-up" style="animation-delay: 0.6s;">
          <div class="db-panel-head">
            <h2 class="db-panel-title dialog-title-text">
              <span class="db-panel-icon vp-tone--info">
                <q-icon name="o_history" size="18px" />
              </span>
              {{ t('recentActivity') }}
            </h2>
          </div>

          <div class="db-cols db-act-grid">
            <span>{{ t('dateTimeCol') }}</span>
            <span>{{ t('activityCol') }}</span>
            <span>{{ t('userCol') }}</span>
            <span />
          </div>

          <div v-if="loading" class="db-rows-loading">
            <div v-for="n in 4" :key="n" class="db-act-grid db-skel-row">
              <div class="db-act-time">
                <q-skeleton type="text" width="120px" />
                <q-skeleton type="text" width="52px" height="12px" />
              </div>
              <q-skeleton type="text" width="70%" class="db-act-text" />
              <div class="db-act-user">
                <q-skeleton type="rect" width="28px" height="28px" class="db-skel-tile" />
                <q-skeleton type="text" width="60%" />
              </div>
            </div>
          </div>
          <div v-else-if="!activity.length" class="db-empty flex flex-center">
            <q-icon name="o_history" size="22px" class="text-muted-themed" />
            <span class="q-ml-sm text-muted-themed">{{ t('nothingHappened') }}</span>
          </div>
          <template v-else>
            <button
              v-for="item in activity"
              :key="item.key"
              type="button"
              class="db-row db-act-grid db-activity-row"
              @click="router.push(item.to)"
            >
              <span class="db-act-time">
                <span class="dialog-title-text">{{ formatFullDayDate(item.at) }}</span>
                <span class="db-soft text-muted-themed">{{ formatTime(item.at) }}</span>
              </span>
              <span class="db-act-text text-muted-themed">{{ item.text }}</span>
              <span class="db-act-user">
                <q-icon
                  :name="item.icon"
                  size="16px"
                  :class="`db-act-icon--${item.kind}`"
                />
                <span class="db-act-name dialog-title-text">{{ item.who }}</span>
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
import { useLanguage } from '@/composables/useLanguage'
import '@/css/admin-pages.scss'

const router = useRouter()
const $q = useQuasar()
const { fetchNotifications } = useAdminNotifications()

// DICTIONARY: English and Natural Taglish with translated cards
const dashboardDict = {
  en: {
    heroSubtitle: 'Here is the executive overview of your marketplace today.',
    goodMorning: 'Good morning',
    goodAfternoon: 'Good afternoon',
    goodEvening: 'Good evening',
    actionRequiredSingular: 'Action required: 1 task',
    actionRequiredPlural: 'Action required: {x} tasks',
    reviewApplications: 'Review Applications',
    syncData: 'Sync Data',
    syncedToast: 'Dashboard synced.',
    failedLoad: 'Couldn’t load the dashboard. Please refresh.',
    needsAttention: 'Needs Attention',
    approvedVendors: 'Approved vendors',
    activeConsumers: 'Active consumers',
    totalUsers: 'Total platform users',
    thisWeek: 'this week',
    viewAll: 'View all',
    applicationCol: 'Application',
    appliedCol: 'Applied',
    noPendingApps: 'No applications waiting for review.',
    unnamedStore: 'Unnamed store',
    vendorAppPending: 'Vendor application · Pending review',
    platformOverview: 'Platform Overview',
    vendorsLegend: 'Vendors',
    consumersLegend: 'Consumers',
    ecosystemRatio: 'Ecosystem Ratio',
    noAccountsYet: 'No accounts yet',
    recentActivity: 'Recent Activity',
    dateTimeCol: 'Date & Time',
    activityCol: 'Activity',
    userCol: 'User',
    nothingHappened: 'Nothing has happened yet.',
    actAppSubmitted: 'New vendor application submitted',
    actAppApproved: 'Vendor application approved',
    actAppRejected: 'Vendor application rejected',
    actConsumerRegistered: 'Consumer registered',
    unnamedConsumer: 'Unnamed consumer'
  },
  ph: {
    heroSubtitle: 'Narito ang quick overview ng marketplace mo ngayon.',
    goodMorning: 'Magandang umaga',
    goodAfternoon: 'Magandang hapon',
    goodEvening: 'Magandang gabi',
    actionRequiredSingular: 'Action required: 1 task',
    actionRequiredPlural: 'Action required: {x} tasks',
    reviewApplications: 'Review Applications',
    syncData: 'Sync Data',
    syncedToast: 'Na-sync na ang dashboard.',
    failedLoad: 'Hindi ma-load ang dashboard. Paki-refresh.',
    needsAttention: 'Kailangang Asikasuhin',
    approvedVendors: 'Approved Vendors',
    activeConsumers: 'Active Consumers',
    totalUsers: 'Total Users',
    thisWeek: 'ngayong linggo',
    viewAll: 'View all',
    applicationCol: 'Application',
    appliedCol: 'Applied',
    noPendingApps: 'Walang pending applications na kailangang i-review.',
    unnamedStore: 'Unnamed store',
    vendorAppPending: 'Vendor application · Pending review',
    platformOverview: 'Platform Overview',
    vendorsLegend: 'Vendors',
    consumersLegend: 'Consumers',
    ecosystemRatio: 'Ecosystem Ratio',
    noAccountsYet: 'Wala pang accounts',
    recentActivity: 'Recent Activity',
    dateTimeCol: 'Date & Time',
    activityCol: 'Activity',
    userCol: 'User',
    nothingHappened: 'Wala pang activity.',
    actAppSubmitted: 'Nag-submit ng bagong vendor application',
    actAppApproved: 'Na-approve ang vendor application',
    actAppRejected: 'Na-reject ang vendor application',
    actConsumerRegistered: 'May bagong registered na consumer',
    unnamedConsumer: 'Unnamed consumer'
  }
}

const { t, lang } = useLanguage(dashboardDict)

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

const applications = ref([])
const consumers = ref([])
const loadedAt = ref(new Date())
const actions = ref(null)

const userName = (() => {
  try {
    return JSON.parse(localStorage.getItem('auth_user') || '{}').full_name || 'Admin'
  } catch {
    return 'Admin'
  }
})()

// ================= LIVE CLOCK & GREETING ICON SYNC =================
const now = ref(new Date())
let clockTimer = null

const greeting = computed(() => {
  const hour = now.value.getHours()
  if (hour < 12) return t('goodMorning')
  if (hour < 18) return t('goodAfternoon')
  return t('goodEvening')
})

const dayPhase = computed(() => {
  const hour = now.value.getHours()
  if (hour >= 5 && hour < 8) return { key: 'dawn', icon: 'o_wb_twilight' }
  if (hour >= 8 && hour < 12) return { key: 'morning', icon: 'o_light_mode' }
  if (hour >= 12 && hour < 17) return { key: 'afternoon', icon: 'o_wb_sunny' }
  if (hour >= 17 && hour < 19) return { key: 'evening', icon: 'o_wb_twilight' }
  return { key: 'night', icon: 'o_dark_mode' }
})

const currentLocale = computed(() => (lang.value === 'ph' ? 'fil-PH' : 'en-US'))

const clockDate = computed(() =>
  now.value.toLocaleDateString(currentLocale.value, {
    weekday: 'long',
    month: 'long',
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

const formatFullDayDate = value => {
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return ''
  
  let formatted = date.toLocaleDateString(currentLocale.value, {
    weekday: 'long',
    month: 'short',
    day: 'numeric'
  })

  // Fix: Force 'Set' to 'Sept' for proper formatting in Taglish mode
  if (lang.value === 'ph') {
    formatted = formatted.replace(/\bSet\b/i, 'Sept').replace('Set ', 'Sept ')
  }
  
  return formatted
}

const formatTime = value => {
  const date = new Date(value)
  return Number.isNaN(date.getTime())
    ? ''
    : date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })
}

const attention = computed(() => {
  const count = Number(stats.value.pending_approvals) || 0
  if (count <= 0) return ''
  return count === 1
    ? t('actionRequiredSingular')
    : t('actionRequiredPlural').replace('{x}', count)
})

const heroActions = computed(() => [
  { key: 'review', label: t('reviewApplications'), icon: 'o_arrow_forward' },
  {
    key: 'sync',
    label: t('syncData'),
    icon: 'o_sync',
    ghost: true,
    loading: syncing.value
  }
])

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
const newVendors = computed(() => approvedTimes.value.filter(t => t > weekStart.value).length)
const newConsumers = computed(() => signupTimes.value.filter(t => t > weekStart.value).length)

const kpis = computed(() => [
  {
    key: 'attention',
    label: t('needsAttention'),
    value: stats.value.pending_approvals ?? 0,
    icon: 'o_pending_actions',
    tone: 'brand',
    delta: 0,
    to: '/admin/approvals'
  },
  {
    key: 'vendors',
    label: t('approvedVendors'),
    value: stats.value.total_vendors ?? 0,
    icon: 'o_storefront',
    tone: 'info',
    delta: newVendors.value,
    to: '/admin/vendors'
  },
  {
    key: 'consumers',
    label: t('activeConsumers'),
    value: stats.value.total_consumers ?? 0,
    icon: 'o_groups',
    tone: 'success',
    delta: newConsumers.value,
    to: '/admin/consumers'
  },
  {
    key: 'users',
    label: t('totalUsers'),
    value: stats.value.total_users ?? 0,
    icon: 'o_people_alt',
    tone: 'neutral',
    delta: newVendors.value + newConsumers.value,
    to: null
  }
])

const pending = computed(() =>
  applications.value
    .filter(app => app.status === 'pending')
    .sort((a, b) => new Date(b.applied_at) - new Date(a.applied_at))
    .slice(0, 4)
)

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
    name: t('vendorsLegend'),
    data: overviewDays.value.map(day =>
      countOn(approvedTimes.value, Number(stats.value.total_vendors) || 0, day)
    )
  },
  {
    name: t('consumersLegend'),
    data: overviewDays.value.map(day =>
      countOn(signupTimes.value, Number(stats.value.total_consumers) || 0, day)
    )
  }
])

const overviewOptions = computed(() => {
  const isDark = $q.dark.isActive
  return {
    chart: {
      type: 'area',
      background: 'transparent',
      toolbar: { show: false },
      zoom: { enabled: false },
      fontFamily: 'inherit'
    },
    colors: [VENDOR_COLOR, CONSUMER_COLOR],
    dataLabels: { enabled: false },
    legend: { show: false },
    stroke: { curve: 'smooth', width: 2.5 },
    fill: {
      type: 'gradient',
      gradient: {
        shadeIntensity: 1,
        opacityFrom: isDark ? 0.45 : 0.32,
        opacityTo: 0.04,
        stops: [0, 90, 100]
      }
    },
    markers: { size: 3, strokeWidth: 0, hover: { size: 5 } },
    grid: {
      borderColor: isDark ? '#262a32' : '#f0ebe7',
      strokeDashArray: 3,
      padding: { left: 8, right: 8, top: 0, bottom: 0 }
    },
    xaxis: {
      categories: overviewDays.value.map(day =>
        day.toLocaleDateString(currentLocale.value, { weekday: 'long' })
      ),
      axisBorder: { show: false },
      axisTicks: { show: false },
      labels: {
        rotate: -20,
        rotateAlways: false,
        hideOverlappingLabels: true,
        style: { colors: isDark ? '#94a3b8' : '#77716d', fontSize: '10.5px' }
      }
    },
    yaxis: {
      min: 0,
      forceNiceScale: true,
      labels: {
        formatter: value => Math.round(value),
        style: { colors: isDark ? '#94a3b8' : '#77716d', fontSize: '11px' }
      }
    },
    tooltip: {
      theme: isDark ? 'dark' : 'light',
      y: { formatter: value => `${value}` }
    }
  }
})

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
      label: t('vendorsLegend'),
      value: vendors,
      color: VENDOR_COLOR,
      share: share(vendors)
    },
    {
      label: t('consumersLegend'),
      value: shoppers,
      color: CONSUMER_COLOR,
      share: share(shoppers)
    }
  ]
})

const donutSeries = computed(() => mixRows.value.map(row => row.value))

const donutOptions = computed(() => {
  const isDark = $q.dark.isActive
  return {
    chart: {
      type: 'donut',
      background: 'transparent',
      fontFamily: 'inherit'
    },
    labels: [t('vendorsLegend'), t('consumersLegend')],
    colors: [VENDOR_COLOR, CONSUMER_COLOR],
    legend: { show: false },
    dataLabels: { enabled: false },
    stroke: { width: 3, colors: [isDark ? '#181b20' : '#ffffff'] },
    tooltip: {
      theme: isDark ? 'dark' : 'light',
      y: { formatter: value => `${value}` }
    },
    plotOptions: { pie: { donut: { size: '68%', labels: { show: false } } } }
  }
})

const activity = computed(() => {
  const items = []
  for (const app of applications.value) {
    const who = app.store_name || t('unnamedStore')
    if (app.applied_at) {
      items.push({
        key: `applied-${app.approval_id}`,
        at: app.applied_at,
        text: t('actAppSubmitted'),
        who,
        icon: 'o_storefront',
        kind: 'store',
        to: '/admin/approvals'
      })
    }
    if (app.reviewed_at && app.status === 'approved') {
      items.push({
        key: `approved-${app.approval_id}`,
        at: app.reviewed_at,
        text: t('actAppApproved'),
        who,
        icon: 'o_storefront',
        kind: 'store',
        to: '/admin/vendors'
      })
    }
    if (app.reviewed_at && app.status === 'rejected') {
      items.push({
        key: `rejected-${app.approval_id}`,
        at: app.reviewed_at,
        text: t('actAppRejected'),
        who,
        icon: 'o_storefront',
        kind: 'store',
        to: '/admin/approvals'
      })
    }
  }
  for (const consumer of consumers.value) {
    if (consumer.created_at) {
      items.push({
        key: `joined-${consumer.user_id}`,
        at: consumer.created_at,
        text: t('actConsumerRegistered'),
        who: consumer.full_name || t('unnamedConsumer'),
        icon: 'o_person',
        kind: 'person',
        to: '/admin/consumers'
      })
    }
  }
  return items.sort((a, b) => new Date(b.at) - new Date(a.at)).slice(0, 5)
})

const listOf = res => (Array.isArray(res.data) ? res.data : res.data?.data || [])

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
      message: t('failedLoad')
    })
  } finally {
    loading.value = false
  }
}

const syncDashboard = async () => {
  syncing.value = true
  await Promise.all([loadDashboard(), fetchNotifications()])
  syncing.value = false
  $q.notify({ type: 'positive', message: t('syncedToast') })
}

const onHeroAction = key => {
  if (key === 'review') router.push('/admin/approvals')
  if (key === 'sync') syncDashboard()
}

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
/* =========================================================
   ANIMATIONS 
========================================================= */
@keyframes slideFadeUp {
  0% { opacity: 0; transform: translateY(20px); }
  100% { opacity: 1; transform: translateY(0); }
}

.animate-slide-up {
  opacity: 0;
  animation: slideFadeUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

/* TYPOGRAPHY OVERRIDES */
.text-muted-themed { color: var(--c-muted, #64748b); }
.dialog-title-text { color: var(--c-text, #1e293b); }

/* DEEP DARK MODE OVERRIDES FOR ADMIN TEXTS */
:deep(.body--dark) .text-muted-themed,
:global(.admin-layout--dark) .text-muted-themed { color: #94a3b8 !important; }

:deep(.body--dark) .dialog-title-text,
:deep(.body--dark) .db-strong,
:global(.admin-layout--dark) .dialog-title-text,
:global(.admin-layout--dark) .db-strong { color: #f8fafc !important; }

/* ------------------------------------------------------ */

.db-clock {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 6px;
  min-width: 190px;
  padding: 16px 22px;
  border: 1px solid rgba(255, 255, 255, 0.28);
  border-radius: var(--r-surface);
  background: rgba(255, 255, 255, 0.12);
  color: #ffffff;
}

.db-clock-date {
  font-size: var(--fs-2xs);
  font-weight: 600;
  letter-spacing: 0.04em;
  text-transform: capitalize;
  color: rgba(255, 255, 255, 0.85);
}

.db-clock-time {
  font-family: 'Poppins', 'Roboto', Arial, sans-serif;
  font-size: 28px;
  font-weight: 800;
  line-height: 1;
  font-variant-numeric: tabular-nums;
}

/* KEY NUMBERS */
.db-kpis {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: var(--sp-gap);
  margin-bottom: var(--sp-gap);
}

.db-kpi {
  display: flex;
  flex-direction: column;
  gap: 12px;
  min-width: 0;
  padding: 20px 20px 16px;
  font-family: inherit;
  text-align: left;
}

.db-kpi--link {
  cursor: pointer;
  transition: border-color 0.2s, box-shadow 0.2s, transform 0.2s;
}

.db-kpi--link:hover {
  border-color: var(--c-border-strong);
  box-shadow: var(--sh-card-hover);
  transform: translateY(-2px);
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
  gap: 6px 12px;
}

.db-kpi-value {
  font-size: var(--fs-4xl);
  font-weight: 700;
  line-height: 1.1;
  color: var(--c-text);
}

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
  align-items: center;
  margin-top: auto;
}

.db-kpi-go {
  color: var(--c-border-strong);
  transition: color 0.15s, transform 0.2s;
}

.db-kpi--link:hover .db-kpi-go {
  color: var(--c-brand);
  transform: translateX(3px);
}

/* PANELS */
.db-grid {
  display: grid;
  grid-template-columns: minmax(0, 1.25fr) minmax(0, 1.25fr) minmax(0, 0.95fr);
  grid-template-areas:
    'attention overview side'
    'activity activity activity';
  gap: var(--sp-gap);
  align-items: start;
}

.db-area-attention {
  grid-area: attention;
  min-height: 320px;
}

.db-area-overview {
  grid-area: overview;
  min-height: 320px;
}

.db-area-side {
  grid-area: side;
  display: flex;
  flex-direction: column;
}

.db-area-activity {
  grid-area: activity;
  width: 100%;
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

/* ROWS */
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
  grid-template-columns: 36px minmax(0, 1fr) 140px 20px;
  align-items: center;
  gap: 12px;
}

.db-act-grid {
  display: grid;
  grid-template-columns: 200px minmax(0, 1.8fr) minmax(0, 1.4fr) 24px;
  align-items: center;
  gap: 20px;
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

.db-row:focus-visible {
  outline: none;
  background: var(--c-surface-2);
  box-shadow: inset 3px 0 0 var(--c-brand);
}

.db-row-arrow {
  color: var(--c-border-strong);
  transition: color 0.15s, transform 0.2s;
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
}

.db-soft {
  font-size: var(--fs-xs);
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
  background: var(--c-surface);
}

.db-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 40px 20px;
  font-size: var(--fs-sm);
  color: var(--c-muted);
}

.db-act-time {
  display: flex;
  flex-direction: column;
  font-weight: 600;
  white-space: nowrap;
}

.db-act-text {
  line-height: 1.4;
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
}

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
  padding: 4px 12px;
}

/* ECOSYSTEM RATIO */
.db-ratio {
  height: 100%;
}

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
}

.db-ratio-value {
  font-weight: 700;
}

@media (max-width: 1279px) {
  .db-kpis {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
  .db-grid {
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    grid-template-areas:
      'attention overview'
      'side side'
      'activity activity';
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
  }

  .db-activity-row .db-row-arrow {
    grid-area: arrow;
  }
}
</style>