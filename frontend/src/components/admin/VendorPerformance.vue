<template>
  <section
    class="performance"
    :aria-label="
      text('Store performance rankings', 'Ranggo ng performance ng tindahan')
    "
  >
    <div class="performance-head">
      <div class="performance-heading">
        <h2>{{ text('Store performance', 'Performance ng tindahan') }}</h2>
        <span
          class="performance-live"
          :class="{ 'performance-live--stale': error }"
          role="status"
        >
          <span />{{
            error ? text('Delayed', 'Naantala') : text('Live', 'Live')
          }}
          <q-tooltip
            >{{
              text(
                'Refreshes every 15 seconds',
                'Nag-a-update bawat 15 segundo'
              )
            }}<template v-if="report"
              ><br />{{ text('Updated', 'Na-update') }}
              {{ updatedAt }}</template
            ></q-tooltip
          >
        </span>
        <q-icon
          name="o_info"
          size="16px"
          class="performance-info"
          tabindex="0"
          :aria-label="text('About rankings', 'Tungkol sa ranggo')"
        >
          <q-tooltip
            >{{
              text(
                'Completed sales from approved stores. Tied totals share a rank.',
                'Nakumpletong benta ng aprubadong tindahan. Pareho ang ranggo ng tabla.'
              )
            }}<template v-if="report && report.eligible_stores < 10"
              ><br />{{
                text(
                  'Lists may overlap with fewer than 10 stores.',
                  'Maaaring maulit sa dalawang listahan kung mas kaunti sa 10 tindahan.'
                )
              }}</template
            ></q-tooltip
          >
        </q-icon>
      </div>
      <div class="performance-controls">
        <q-select
          v-model="days"
          :options="periods"
          emit-value
          map-options
          outlined
          dense
          hide-bottom-space
          :label="text('Period', 'Panahon')"
        />
        <q-select
          v-model="metric"
          :options="metrics"
          emit-value
          map-options
          outlined
          dense
          hide-bottom-space
          :label="text('Rank by', 'Batayan ng ranggo')"
        />
        <q-btn
          flat
          round
          dense
          icon="o_refresh"
          class="performance-refresh"
          :loading="loading"
          :aria-label="text('Refresh rankings', 'I-refresh ang ranggo')"
          @click="load"
          ><q-tooltip>{{ text('Refresh', 'I-refresh') }}</q-tooltip></q-btn
        >
      </div>
    </div>
    <q-banner v-if="error" rounded class="performance-error" role="alert">{{
      error
    }}</q-banner>
    <div class="performance-grid">
      <section
        v-for="group in groups"
        :key="group.key"
        class="vp-card performance-panel"
        :class="'performance-panel--' + group.key"
      >
        <div class="performance-panel-head">
          <h3
            ><span
              class="performance-panel-icon"
              :class="
                group.key === 'top' ? 'vp-tone--success' : 'vp-tone--brand'
              "
              ><q-icon :name="group.icon" size="18px" /></span
            >{{ group.title }}</h3
          >
          <span v-if="report" class="performance-count">{{
            report[group.key].length
          }}</span>
        </div>
        <div class="performance-columns" aria-hidden="true"
          ><span>{{ text('Store', 'Tindahan') }}</span
          ><span>{{
            metric === 'revenue'
              ? text('Revenue', 'Benta')
              : text('Orders', 'Mga order')
          }}</span></div
        >
        <div v-if="loading && !report" class="performance-skeleton"
          ><q-skeleton
            v-for="n in 3"
            :key="n"
            type="rect"
            height="48px"
            class="q-mb-sm"
        /></div>
        <div
          v-else-if="report && !report[group.key].length"
          class="performance-empty"
          ><q-icon name="o_storefront" size="26px" /><span>{{
            report.eligible_stores
              ? text('No sales this period', 'Walang benta sa panahong ito')
              : text('No approved stores yet', 'Wala pang aprubadong tindahan')
          }}</span></div
        >
        <ol v-else-if="report" class="performance-list">
          <li v-for="store in report[group.key]" :key="store.store_id">
            <button
              type="button"
              class="performance-row"
              :disabled="vendorsLoading"
              :aria-label="
                text('View sales report for ', 'Tingnan ang ulat ng ') +
                store.store_name
              "
              @click="
                $emit('view', {
                  storeId: store.store_id,
                  startDate: report.start_date,
                  endDate: report.end_date
                })
              "
            >
              <span class="performance-rank">{{ store.rank }}</span>
              <span class="performance-store"
                ><strong>{{
                  store.store_name || text('Unnamed store', 'Walang pangalan')
                }}</strong
                ><span
                  class="performance-owner"
                  :class="{ 'performance-no-sales': !store.completed_orders }"
                  >{{
                    !store.completed_orders
                      ? text('No recorded sales', 'Walang naitalang benta')
                      : store.owner_name
                  }}<span
                    v-if="store.account_status !== 'active'"
                    class="performance-account"
                    >{{ statusLabel(store.account_status) }}</span
                  ></span
                ></span
              >
              <span class="performance-values"
                ><strong>{{
                  metric === 'revenue'
                    ? money(store.revenue)
                    : store.completed_orders.toLocaleString()
                }}</strong
                ><span>{{
                  metric === 'revenue'
                    ? store.completed_orders + ' ' + text('orders', 'order')
                    : money(store.revenue)
                }}</span></span
              >
              <q-icon
                name="o_arrow_forward"
                size="16px"
                class="performance-arrow"
              />
            </button>
          </li>
        </ol>
      </section>
    </div>
  </section>
</template>
<script setup>
import { computed, ref, watch, onMounted, onBeforeUnmount } from 'vue'
import { api } from '@/boot/axios'
import { useLanguage } from '@/composables/useLanguage'

defineProps({ vendorsLoading: Boolean })
defineEmits(['view'])
const { lang } = useLanguage()
const text = (en, ph) => (lang.value === 'ph' ? ph : en)
const days = ref(30)
const metric = ref('revenue')
const report = ref(null)
const loading = ref(false)
const error = ref('')
let controller
let timer
const money = value =>
  new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(
    value
  )
const periods = computed(() => [
  { value: 1, label: text('Today', 'Ngayon') },
  { value: 7, label: text('Last 7 days', 'Huling 7 araw') },
  { value: 30, label: text('Last 30 days', 'Huling 30 araw') },
  { value: 90, label: text('Last 90 days', 'Huling 90 araw') }
])
const metrics = computed(() => [
  { value: 'revenue', label: text('Sales revenue', 'Kabuuang benta') },
  {
    value: 'completed_orders',
    label: text('Completed orders', 'Nakumpletong order')
  }
])
const groups = computed(() => [
  {
    key: 'top',
    title: text('Top performing', 'Nangunguna'),
    icon: 'o_emoji_events'
  },
  {
    key: 'least',
    title: text('Least performing', 'Pinakamababa'),
    icon: 'o_trending_down'
  }
])
const updatedAt = computed(() =>
  report.value
    ? new Intl.DateTimeFormat('en-PH', {
        timeZone: 'Asia/Manila',
        dateStyle: 'medium',
        timeStyle: 'short'
      }).format(new Date(report.value.generated_at))
    : ''
)
const statusLabel = status =>
  ({
    active: text('Active', 'Aktibo'),
    inactive: text('Inactive', 'Hindi aktibo'),
    suspended: text('Suspended', 'Suspendido')
  })[status] || status

async function load() {
  if (loading.value) return
  const request = new AbortController()
  controller = request
  loading.value = true
  const end = new Intl.DateTimeFormat('en-CA', {
    timeZone: 'Asia/Manila',
    year: 'numeric',
    month: '2-digit',
    day: '2-digit'
  }).format(new Date())
  const start = new Date(`${end}T00:00:00Z`)
  start.setUTCDate(start.getUTCDate() - days.value + 1)
  try {
    const { data } = await api.get('/admin/vendors/performance', {
      params: {
        start_date: start.toISOString().slice(0, 10),
        end_date: end,
        metric: metric.value
      },
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
          'Refresh failed. Showing the last update.',
          'Hindi ma-refresh. Ipinapakita ang huling update.'
        )
  } finally {
    if (controller === request) loading.value = false
  }
}
watch([days, metric], () => {
  controller?.abort()
  loading.value = false
  report.value = null
  error.value = ''
  load()
})
onMounted(() => {
  load()
  timer = setInterval(() => {
    if (!document.hidden) load()
  }, 15000)
})
onBeforeUnmount(() => {
  clearInterval(timer)
  controller?.abort()
})
</script>

<style scoped>
.performance {
  margin-bottom: var(--sp-gap);
  color: var(--c-text);
}
.performance-head,
.performance-heading,
.performance-controls,
.performance-panel-head {
  display: flex;
  align-items: center;
  gap: 12px;
}
.performance-head {
  justify-content: space-between;
  flex-wrap: wrap;
  margin-bottom: 12px;
}
h2,
h3 {
  margin: 0;
  font-size: var(--fs-lg);
  font-weight: 700;
  line-height: 1.3;
}
h3 {
  display: flex;
  align-items: center;
  gap: 10px;
}
.performance-controls {
  gap: 8px;
}
.performance-controls .q-select {
  width: 170px;
}
.performance-controls :deep(.q-field__control) {
  border-radius: var(--r-control);
  background: var(--c-surface);
}
.performance-refresh {
  width: 38px;
  height: 38px;
  color: var(--c-text-3);
}
.performance-refresh:hover {
  color: var(--c-brand);
}
.performance-live {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 3px 8px;
  border-radius: var(--r-pill);
  color: var(--c-success);
  background: var(--c-success-wash);
  font-size: 10px;
  font-weight: 600;
  white-space: nowrap;
}
.performance-live > span {
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: currentColor;
}
.performance-live--stale {
  background: var(--c-brand-tint);
  color: var(--c-brand);
}
.performance-info {
  color: var(--c-muted);
  cursor: help;
}
.performance-info:focus-visible {
  outline: 2px solid var(--c-brand);
  outline-offset: 3px;
  border-radius: 50%;
}
.performance-error {
  color: var(--c-brand);
  background: var(--c-brand-tint);
  margin-bottom: 12px;
  font-size: var(--fs-xs);
}
.performance-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: var(--sp-gap);
  align-items: stretch;
}
.performance-panel {
  overflow: hidden;
  margin: 0;
  min-width: 0;
}
.performance-panel-head {
  min-height: 64px;
  padding: 18px 20px 14px;
  justify-content: space-between;
}
.performance-panel-icon {
  display: grid;
  place-items: center;
  width: 32px;
  height: 32px;
  border-radius: var(--r-control);
  flex-shrink: 0;
}
.performance-count {
  min-width: 24px;
  padding: 2px 7px;
  border-radius: var(--r-pill);
  background: var(--c-surface-2);
  font-size: var(--fs-2xs);
  font-weight: 600;
  text-align: center;
  color: var(--c-muted);
}
.performance-columns {
  display: flex;
  justify-content: space-between;
  padding: 8px 48px 8px 64px;
  border-top: 1px solid var(--c-hairline);
  border-bottom: 1px solid var(--c-hairline);
  background: var(--c-surface-2);
  color: var(--c-muted);
  font-size: var(--fs-2xs);
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
}
.performance-list {
  list-style: none;
  padding: 0;
  margin: 0;
}
.performance-list li + li {
  border-top: 1px solid var(--c-hairline);
}
.performance-row {
  display: flex;
  width: 100%;
  gap: 12px;
  align-items: center;
  padding: 12px 20px;
  min-height: 72px;
  border: 0;
  background: transparent;
  color: var(--c-text-2);
  text-align: left;
  font: inherit;
  cursor: pointer;
  transition: background-color 0.15s;
}
.performance-row:hover {
  background: var(--c-surface-2);
}
.performance-row:focus-visible {
  outline: none;
  background: var(--c-surface-2);
  box-shadow: inset 3px 0 0 var(--c-brand);
}
.performance-row:disabled {
  cursor: wait;
  opacity: 0.65;
}
.performance-rank {
  display: grid;
  place-items: center;
  min-width: 32px;
  height: 32px;
  border-radius: var(--r-control);
  background: var(--c-surface-2);
  color: var(--c-muted);
  font-size: var(--fs-xs);
  font-weight: 700;
  font-variant-numeric: tabular-nums;
}
.performance-panel--top .performance-rank {
  color: var(--c-success);
  background: var(--c-success-wash);
}
.performance-store,
.performance-values {
  display: flex;
  flex-direction: column;
  gap: 3px;
  min-width: 0;
}
.performance-store {
  flex: 1;
}
.performance-store strong {
  font-size: var(--fs-sm);
  font-weight: 600;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}
.performance-owner,
.performance-values > span {
  font-size: var(--fs-2xs);
  color: var(--c-muted);
}
.performance-owner {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 4px 6px;
  overflow-wrap: anywhere;
}
.performance-account {
  padding: 0 5px;
  border: 1px solid var(--c-border);
  border-radius: 4px;
  color: var(--c-text-3);
  font-size: 10px;
}
.performance-values {
  text-align: right;
  flex-shrink: 0;
  font-variant-numeric: tabular-nums;
}
.performance-values strong {
  font-size: var(--fs-sm);
  font-weight: 700;
}
.performance-no-sales {
  color: var(--c-muted);
}
.performance-arrow {
  color: var(--c-border-strong);
  transition:
    color 0.15s,
    transform 0.2s;
}
.performance-row:hover .performance-arrow {
  color: var(--c-brand);
  transform: translateX(3px);
}
.performance-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 10px;
  min-height: 150px;
  padding: 24px;
  color: var(--c-muted);
  font-size: var(--fs-xs);
}
.performance-skeleton {
  padding: 12px 20px;
}
.performance-skeleton :deep(.q-skeleton) {
  border-radius: var(--r-control);
}
@media (max-width: 1023px) {
  .performance-grid {
    grid-template-columns: 1fr;
  }
}
@media (max-width: 600px) {
  .performance-head {
    gap: 12px;
  }
  .performance-heading {
    gap: 8px;
  }
  .performance-controls {
    width: 100%;
  }
  .performance-controls .q-select {
    width: auto;
    min-width: 0;
    flex: 1;
  }
  .performance-row {
    gap: 8px;
    padding: 12px 16px;
  }
  .performance-panel-head {
    padding: 16px;
  }
  .performance-columns {
    padding-left: 56px;
    padding-right: 16px;
  }
  .performance-arrow {
    display: none;
  }
  .performance-values strong {
    font-size: var(--fs-xs);
  }
}
</style>
