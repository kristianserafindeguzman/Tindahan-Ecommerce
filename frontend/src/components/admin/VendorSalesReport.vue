<template>
  <section class="sales-report" aria-label="Store sales report">
    <header class="sr-header">
      <div class="sr-heading">
        <h2
          ><span class="sr-title-icon vp-tone--brand"
            ><q-icon name="o_storefront" size="18px" /></span
          >{{ storeName || text('Sales overview', 'Pangkalahatang benta') }}</h2
        >
        <span v-if="report" class="sr-live" role="status"
          >{{ text('Updated', 'Na-update')
          }}<q-tooltip
            >{{ dateTime(report.generated_at) }}<br />{{
              text(
                'Refreshes every 15 seconds',
                'Nag-a-update bawat 15 segundo'
              )
            }}</q-tooltip
          ></span
        >
        <q-icon
          name="o_info"
          size="16px"
          class="sr-info"
          tabindex="0"
          :aria-label="text('About this report', 'Tungkol sa ulat')"
          ><q-tooltip>{{
            text(
              'Completed orders and recorded walk-in sales. Manila time. Maximum range: 93 days.',
              'Nakumpletong order at naitalang walk-in sales. Oras sa Manila. Hanggang 93 araw.'
            )
          }}</q-tooltip></q-icon
        >
      </div>
      <q-btn
        flat
        round
        dense
        icon="o_refresh"
        :loading="loading"
        :aria-label="text('Refresh', 'I-refresh')"
        class="sr-refresh"
        @click="load"
        ><q-tooltip>{{ text('Refresh', 'I-refresh') }}</q-tooltip></q-btn
      >
    </header>
    <div class="sr-filters">
      <div
        class="sr-presets"
        role="group"
        :aria-label="text('Report period', 'Panahon ng ulat')"
        ><button
          v-for="preset in presets"
          :key="preset.days"
          type="button"
          class="vp-chip"
          :class="{ 'vp-chip--active': selectedPreset === preset.days }"
          :aria-pressed="selectedPreset === preset.days"
          :disabled="loading"
          @click="choosePreset(preset.days)"
          >{{ preset.label }}</button
        ></div
      >
      <div class="sr-dates"
        ><q-input
          v-model="startDate"
          type="date"
          outlined
          dense
          stack-label
          hide-bottom-space
          :label="text('From', 'Mula')"
          class="sr-date-input" /><q-input
          v-model="endDate"
          type="date"
          outlined
          dense
          stack-label
          hide-bottom-space
          :label="text('To', 'Hanggang')"
          class="sr-date-input" /><q-btn
          no-caps
          unelevated
          color="primary"
          :label="text('Apply', 'Ilapat')"
          class="vp-pill-btn sr-apply"
          :disable="loading"
          @click="applyRange"
      /></div>
    </div>
    <q-banner v-if="error" rounded class="sr-error" role="alert">{{
      error
    }}</q-banner>
    <div
      v-if="loading && !report"
      :aria-label="text('Loading sales report', 'Kinukuha ang ulat')"
      ><div class="sr-kpis"
        ><q-skeleton
          v-for="n in 6"
          :key="n"
          type="rect"
          height="104px"
          class="sr-skeleton" /></div
      ><q-skeleton type="rect" height="310px" class="sr-skeleton q-mt-md"
    /></div>
    <template v-if="report">
      <div class="sr-kpis"
        ><div
          v-for="(metric, index) in metrics"
          :key="metric.label"
          class="vp-card sales-metric"
          ><div class="sr-kpi-top"
            ><span>{{ metric.label }}</span
            ><span
              class="sr-kpi-icon"
              :class="index === 0 ? 'vp-tone--brand' : 'vp-tone--neutral'"
              ><q-icon :name="metric.icon" size="18px" /></span></div
          ><div class="sr-kpi-value">{{ metric.value }}</div></div
        ></div
      >
      <section class="vp-card sr-chart-panel">
        <div class="sr-panel-head"
          ><div
            ><h3
              ><span class="sr-panel-icon vp-tone--brand"
                ><q-icon name="o_show_chart" size="18px" /></span
              >{{ text('Sales trend', 'Takbo ng benta') }}</h3
            ><p class="sr-period"
              >{{ report.start_date }} &ndash; {{ report.end_date }}</p
            ></div
          ><div
            class="sr-chart-toggle"
            role="group"
            :aria-label="text('Chart metric', 'Sukatan ng graph')"
            ><button
              v-for="option in chartMetrics"
              :key="option.key"
              type="button"
              :class="{ 'is-active': chartMetric === option.key }"
              :aria-pressed="chartMetric === option.key"
              @click="chartMetric = option.key"
              >{{ option.label }}</button
            ></div
          ></div
        >
        <div
          class="sr-chart"
          role="img"
          :aria-label="
            chartLabel + ': ' + report.start_date + ' to ' + report.end_date
          "
          ><VueApexCharts
            type="line"
            height="280"
            width="100%"
            :options="chartOptions"
            :series="chartSeries"
        /></div>
        <div v-if="!report.metrics.completed_orders" class="sr-chart-empty">{{
          text(
            'No completed sales this period',
            'Walang nakumpletong benta sa panahong ito'
          )
        }}</div>
      </section>
      <section class="vp-card sr-transactions-panel">
        <div class="sr-panel-head"
          ><h3
            ><span class="sr-panel-icon vp-tone--neutral"
              ><q-icon name="o_receipt_long" size="18px" /></span
            >{{ text('Recent sales', 'Mga huling benta') }}</h3
          ><span class="sr-count"
            >{{ report.recent_sales.length
            }}<q-tooltip>{{
              text('Latest 10 completed sales', 'Huling 10 nakumpletong benta')
            }}</q-tooltip></span
          ></div
        >
        <div v-if="!report.recent_sales.length" class="sr-empty"
          ><q-icon name="o_receipt_long" size="24px" /><span>{{
            text('No transactions', 'Walang transaksyon')
          }}</span></div
        >
        <div v-else class="sr-transactions scroll"
          ><table class="vp-table"
            ><thead
              ><tr
                ><th>{{ text('Order', 'Order') }}</th
                ><th>{{ text('Sale time', 'Oras ng benta') }}</th
                ><th class="text-right">{{ text('Items', 'Mga item') }}</th
                ><th class="text-right">{{ text('Total', 'Kabuuan') }}</th></tr
              ></thead
            ><tbody
              ><tr v-for="sale in report.recent_sales" :key="sale.order_id"
                ><td class="sr-order">#{{ sale.order_id }}</td
                ><td class="sr-muted">{{ dateTime(sale.sold_at) }}</td
                ><td class="text-right">{{ sale.units }}</td
                ><td class="text-right sr-amount">{{
                  money(sale.total)
                }}</td></tr
              ></tbody
            ></table
          ></div
        >
      </section>
      <q-expansion-item
        icon="o_calendar_month"
        :label="text('Daily breakdown', 'Araw-araw na detalye')"
        class="vp-card sr-breakdown"
        ><div class="sales-table scroll"
          ><table class="vp-table"
            ><thead
              ><tr
                ><th>{{ text('Date', 'Petsa') }}</th
                ><th class="text-right">{{
                  text('Completed orders', 'Nakumpletong order')
                }}</th
                ><th class="text-right">{{ text('Revenue', 'Benta') }}</th></tr
              ></thead
            ><tbody
              ><tr v-for="day in report.daily" :key="day.date"
                ><td>{{ day.date }}</td
                ><td class="text-right">{{ day.orders }}</td
                ><td class="text-right sr-amount">{{
                  money(day.revenue)
                }}</td></tr
              ></tbody
            ></table
          ></div
        ></q-expansion-item
      >
    </template>
  </section>
</template>

<script setup>
import { computed, ref, watch, onBeforeUnmount } from 'vue'
import VueApexCharts from 'vue3-apexcharts'
import { useQuasar } from 'quasar'
import { api } from '@/boot/axios'
import { useLanguage } from '@/composables/useLanguage'

const props = defineProps({
  storeId: Number,
  storeName: String,
  active: Boolean,
  initialStart: String,
  initialEnd: String
})
const $q = useQuasar()
const { lang } = useLanguage()
const text = (en, fil) => (lang.value === 'ph' ? fil : en)
const manilaDate = date =>
  new Intl.DateTimeFormat('en-CA', {
    timeZone: 'Asia/Manila',
    year: 'numeric',
    month: '2-digit',
    day: '2-digit'
  }).format(date)
const today = new Date()
const first = new Date(today)
first.setDate(first.getDate() - 29)
const startDate = ref(props.initialStart || manilaDate(first))
const endDate = ref(props.initialEnd || manilaDate(today))
const applied = ref({ start_date: startDate.value, end_date: endDate.value })
const report = ref(null)
const error = ref('')
const loading = ref(false)
const selectedPreset = ref(props.initialStart ? null : 30)
const chartMetric = ref('revenue')
const presets = computed(() => [
  { days: 1, label: text('Today', 'Ngayon') },
  { days: 7, label: text('Last 7 days', 'Huling 7 araw') },
  { days: 30, label: text('Last 30 days', 'Huling 30 araw') }
])
const chartMetrics = computed(() => [
  { key: 'revenue', label: text('Revenue', 'Benta') },
  { key: 'orders', label: text('Orders', 'Mga order') }
])
const chartLabel = computed(
  () => chartMetrics.value.find(item => item.key === chartMetric.value).label
)
// UTC midnight preserves each Manila calendar date in the chart.
const chartSeries = computed(() => [
  {
    name: chartLabel.value,
    data: (report.value?.daily || []).map(day => ({
      x: Date.parse(`${day.date}T00:00:00Z`),
      y: Number(day[chartMetric.value])
    }))
  }
])
const chartOptions = computed(() => {
  const dark = $q.dark.isActive
  const color = dark ? '#9fb1d1' : '#77716d'
  return {
    chart: {
      type: 'line',
      background: 'transparent',
      fontFamily: 'inherit',
      foreColor: color,
      toolbar: { show: false },
      zoom: { enabled: false },
      animations: { enabled: false }
    },
    colors: [dark ? '#ff4d4d' : '#bd2427'],
    stroke: { width: 3, curve: 'straight' },
    markers: {
      size: (report.value?.daily.length || 0) <= 7 ? 4 : 0,
      hover: { size: 5 },
      strokeWidth: 2
    },
    dataLabels: { enabled: false },
    legend: { show: false },
    grid: {
      borderColor: dark ? '#24314e' : '#f0ebe7',
      strokeDashArray: 4,
      padding: { left: 12, right: 16 }
    },
    xaxis: {
      type: 'datetime',
      axisBorder: { show: false },
      axisTicks: { show: false },
      labels: {
        datetimeUTC: true,
        format: 'MMM dd',
        hideOverlappingLabels: true,
        style: { colors: color, fontSize: '11px' }
      },
      tooltip: { enabled: false }
    },
    yaxis: {
      min: 0,
      forceNiceScale: true,
      decimalsInFloat: 0,
      labels: {
        style: { colors: color, fontSize: '11px' },
        formatter: value =>
          chartMetric.value === 'revenue'
            ? new Intl.NumberFormat('en-PH', {
                style: 'currency',
                currency: 'PHP',
                notation: 'compact',
                maximumFractionDigits: 1
              }).format(value)
            : Math.round(value).toString()
      }
    },
    tooltip: {
      theme: dark ? 'dark' : 'light',
      x: { format: 'MMM dd, yyyy' },
      y: {
        formatter: value =>
          chartMetric.value === 'revenue'
            ? money(value)
            : `${value} ${text('orders', 'order')}`
      }
    }
  }
})
let timer
let controller
const money = value =>
  new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(
    value
  )
const dateTime = value =>
  new Intl.DateTimeFormat('en-PH', {
    timeZone: 'Asia/Manila',
    dateStyle: 'medium',
    timeStyle: 'medium'
  }).format(new Date(value))
const metrics = computed(() =>
  report.value
    ? [
        {
          label: text('Revenue', 'Benta'),
          icon: 'o_payments',
          value: money(report.value.metrics.revenue)
        },
        {
          label: text('Completed orders', 'Nakumpletong order'),
          icon: 'o_receipt_long',
          value: report.value.metrics.completed_orders
        },
        {
          label: text('Items sold', 'Mga item na nabenta'),
          icon: 'o_inventory_2',
          value: report.value.metrics.units_sold
        },
        {
          label: text('Average sale', 'Karaniwang benta'),
          icon: 'o_shopping_bag',
          value: money(report.value.metrics.average_order_value)
        },
        {
          label: text('Cancelled orders', 'Nakanselang order'),
          icon: 'o_cancel',
          value: report.value.metrics.cancelled_orders
        },
        {
          label: text('Cancellation rate', 'Porsiyento ng kanselasyon'),
          icon: 'o_percent',
          value: `${report.value.metrics.cancellation_rate}%`
        }
      ]
    : []
)

async function load() {
  if (!props.active || !props.storeId || loading.value) return
  const request = new AbortController()
  controller = request
  loading.value = true
  try {
    const { data } = await api.get(`/admin/vendors/${props.storeId}/sales`, {
      params: applied.value,
      signal: request.signal
    })
    if (request.signal.aborted) return
    report.value = data
    error.value = ''
  } catch (err) {
    if (!request.signal.aborted)
      error.value =
        err.response?.data?.message ||
        text(
          'Unable to refresh sales. Displayed data may be out of date. Try Refresh.',
          'Hindi ma-update ang benta. Maaaring luma ang datos. Subukang i-refresh.'
        )
  } finally {
    if (controller === request) loading.value = false
  }
}
function choosePreset(days) {
  const end = manilaDate(new Date())
  const start = new Date(`${end}T00:00:00Z`)
  start.setUTCDate(start.getUTCDate() - days + 1)
  startDate.value = start.toISOString().slice(0, 10)
  endDate.value = end
  applyRange()
  selectedPreset.value = days
}
function applyRange() {
  if (
    !startDate.value ||
    !endDate.value ||
    endDate.value < startDate.value ||
    (new Date(endDate.value) - new Date(startDate.value)) / 86400000 > 92
  ) {
    error.value = text(
      'Choose a valid date range of at most 93 days.',
      'Pumili ng wastong saklaw na hanggang 93 araw.'
    )
    return
  }
  selectedPreset.value = null
  applied.value = { start_date: startDate.value, end_date: endDate.value }
  report.value = null
  load()
}
function stop() {
  clearInterval(timer)
  controller?.abort()
  controller = null
  loading.value = false
}
watch(
  () => [props.active, props.storeId],
  () => {
    stop()
    report.value = null
    error.value = ''
    if (props.active && props.storeId) {
      load()
      timer = setInterval(load, 15000)
    }
  },
  { immediate: true }
)
onBeforeUnmount(stop)
</script>

<style scoped>
.sales-report {
  color: var(--c-text);
}
.sr-header,
.sr-heading,
.sr-filters,
.sr-dates,
.sr-panel-head,
.sr-kpi-top {
  display: flex;
  align-items: center;
  gap: 12px;
}
.sr-header {
  justify-content: space-between;
  margin-bottom: 16px;
}
.sr-heading {
  min-width: 0;
  flex-wrap: wrap;
  gap: 8px;
}
h2,
h3,
p {
  margin: 0;
}
h2,
h3 {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: var(--fs-lg);
  font-weight: 700;
  line-height: 1.3;
}
h2 {
  overflow-wrap: anywhere;
}
.sr-title-icon,
.sr-panel-icon {
  width: 32px;
  height: 32px;
  border-radius: var(--r-control);
  display: grid;
  place-items: center;
  flex-shrink: 0;
}
.sr-live {
  padding: 3px 8px;
  border-radius: var(--r-pill);
  background: var(--c-surface-2);
  color: var(--c-muted);
  font-size: 10px;
  font-weight: 600;
  white-space: nowrap;
}
.sr-info {
  color: var(--c-muted);
  cursor: help;
}
.sr-info:focus-visible {
  outline: 2px solid var(--c-brand);
  outline-offset: 3px;
  border-radius: 50%;
}
.sr-refresh {
  flex-shrink: 0;
  width: 38px;
  height: 38px;
  color: var(--c-text-3);
}
.sr-refresh:hover {
  color: var(--c-brand);
}
.sr-filters {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  gap: 12px;
}
.sr-presets {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}
.sr-presets .vp-chip {
  min-height: 34px;
  padding: 0 12px;
  font-size: var(--fs-2xs);
}
.sr-presets .vp-chip:disabled {
  opacity: 0.6;
  cursor: wait;
}
.sr-dates {
  gap: 8px;
}
.sr-apply {
  height: 40px;
  min-height: 40px;
}
.sr-date-input {
  width: 150px;
}
.sr-date-input :deep(.q-field__control) {
  border-radius: var(--r-control);
  background: var(--c-surface);
}
.sr-error {
  background: var(--c-brand-tint);
  color: var(--c-brand);
  margin-top: 16px;
  font-size: var(--fs-xs);
}
.sr-kpis {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: var(--sp-gap);
  margin-top: var(--sp-gap);
  grid-auto-rows: 1fr;
}
.sales-metric {
  padding: 16px 20px;
  min-width: 0;
}
.sr-kpi-top {
  justify-content: space-between;
  font-size: var(--fs-xs);
  font-weight: 600;
  color: var(--c-text-3);
}
.sr-kpi-icon {
  width: 32px;
  height: 32px;
  border-radius: var(--r-control);
  display: grid;
  place-items: center;
  flex-shrink: 0;
}
.sr-kpi-value {
  margin-top: 10px;
  font-size: var(--fs-3xl);
  font-weight: 700;
  line-height: 1.2;
  font-variant-numeric: tabular-nums;
  overflow-wrap: anywhere;
}
.sr-chart-panel,
.sr-transactions-panel,
.sr-breakdown {
  margin-top: var(--sp-gap);
  overflow: hidden;
}
.sr-panel-head {
  justify-content: space-between;
  padding: 18px 20px 12px;
}
.sr-period {
  margin-top: 6px;
  margin-left: 42px;
  color: var(--c-muted);
  font-size: var(--fs-2xs);
}
.sr-chart-toggle {
  display: flex;
  padding: 3px;
  border: 1px solid var(--c-border);
  border-radius: var(--r-control);
  background: var(--c-surface-2);
}
.sr-chart-toggle button {
  border: 0;
  background: transparent;
  color: var(--c-text-3);
  padding: 6px 12px;
  border-radius: 5px;
  font: inherit;
  font-size: var(--fs-2xs);
  font-weight: 600;
  cursor: pointer;
}
.sr-chart-toggle .is-active {
  background: var(--c-brand-tint);
  color: var(--c-brand);
}
.sr-chart-toggle button:focus-visible {
  outline: 2px solid var(--c-brand);
  outline-offset: 2px;
}
.sr-chart {
  min-width: 0;
  padding: 0 10px;
}
.sr-chart-empty {
  padding: 0 20px 16px;
  text-align: center;
  color: var(--c-muted);
  font-size: var(--fs-xs);
}
.sr-count {
  min-width: 24px;
  padding: 2px 7px;
  border-radius: var(--r-pill);
  background: var(--c-surface-2);
  font-size: var(--fs-2xs);
  font-weight: 600;
  text-align: center;
  color: var(--c-muted);
}
.sr-transactions .vp-table,
.sales-table .vp-table {
  min-width: 540px;
}
.sr-transactions th:first-child {
  width: 100px;
}
.sr-transactions th:nth-child(3) {
  width: 80px;
}
.sr-transactions th:last-child {
  width: 150px;
}
.sr-transactions .vp-table th,
.sales-table .vp-table th {
  white-space: nowrap;
}
.sr-transactions .vp-table td,
.sales-table .vp-table td {
  vertical-align: middle;
}
.sr-transactions .vp-table tr:last-child td,
.sales-table .vp-table tr:last-child td {
  border-bottom: 0;
}
.sr-order {
  font-weight: 600;
}
.sr-muted {
  color: var(--c-muted);
}
.sr-amount {
  font-weight: 700;
  font-variant-numeric: tabular-nums;
}
.sales-table {
  max-height: 280px;
}
.sr-skeleton {
  border-radius: var(--r-surface);
}
.sr-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 28px;
  color: var(--c-muted);
  font-size: var(--fs-xs);
}
.sr-breakdown :deep(.q-item) {
  min-height: 56px;
  padding: 14px 20px;
}
.sr-breakdown :deep(.q-item__section--avatar) {
  min-width: 32px;
  padding-right: 10px;
  color: var(--c-muted);
}
.sr-breakdown :deep(.q-item__label) {
  font-size: var(--fs-sm);
  font-weight: 600;
}
@media (max-width: 900px) {
  .sr-filters {
    grid-template-columns: 1fr;
  }
  .sr-date-input {
    flex: 1;
    width: auto;
    min-width: 0;
  }
}
@media (max-width: 600px) {
  .sr-header {
    align-items: flex-start;
  }
  h2 {
    font-size: var(--fs-md);
  }
  .sr-kpis {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
  }
  .sales-metric {
    padding: 12px;
  }
  .sr-kpi-top {
    align-items: flex-start;
    gap: 4px;
    font-size: var(--fs-2xs);
  }
  .sr-kpi-icon {
    width: 26px;
    height: 26px;
  }
  .sr-kpi-value {
    font-size: var(--fs-xl);
  }
  .sr-dates {
    width: 100%;
    flex-wrap: wrap;
  }
  .sr-date-input {
    flex: 1;
    width: auto;
    min-width: 110px;
  }
  .sr-apply {
    width: 100%;
  }
  .sr-panel-head {
    padding: 16px;
    flex-wrap: wrap;
  }
}
</style>
