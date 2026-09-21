<template>
  <q-page class="vp-page">
    <div class="vp-container">

      <div class="vp-header">
        <div>
          <h1 class="vp-title">{{ t('title') }}</h1>
          <p class="vp-subtitle">{{ t('subtitle') }}</p>
        </div>
        <q-btn outline no-caps color="primary" icon="o_download" :label="t('exportBtn')" class="vp-pill-btn" :loading="isExporting" @click="exportOrders" />
      </div>

      <div data-tour="ol-list" class="vp-card">
        <div data-tour="ol-toolbar" class="vp-toolbar">
          <div class="vp-search-row">
            <q-input
              v-model="search"
              outlined
              dense
              clearable
              clear-icon="o_close"
              hide-bottom-space
              :placeholder="$q.screen.xs ? t('searchMob') : t('searchDesk')"
              class="vp-search"
            >
              <template #prepend>
                <q-icon name="o_search" size="18px" />
              </template>
            </q-input>
            <OrderTableFilters v-model="filters" :result-count="filteredOrders.length" />
          </div>

          <!-- Each status is a chip with its count, so the busy ones stand out before they are opened. -->
          <div data-tour="ol-chips" class="vp-chips" role="tablist" aria-label="Filter orders by status">
            <button
              v-for="filter in localizedFilters"
              :key="filter.key"
              type="button"
              role="tab"
              class="vp-chip"
              :class="{ 'vp-chip--active': activeStatus === filter.key }"
              :aria-selected="activeStatus === filter.key"
              @click="activeStatus = filter.key"
            >
              {{ filter.label }}
              <span class="vp-chip-count">{{ countFor(filter.key) }}</span>
            </button>
          </div>
        </div>

        <!-- The filters in use, each removable with one tap. -->
        <div v-if="filterChips.length" class="vp-filter-summary">
          <span class="vp-filter-summary-label">{{ t('filteredBy') }}</span>
          <button v-for="chip in filterChips" :key="chip.key" type="button" class="vp-filter-chip" :aria-label="`Remove ${chip.label}`" @click="clearFilter(filters, chip.key)">
            {{ chip.label }}
            <q-icon name="o_close" size="14px" />
          </button>
          <button type="button" class="vp-filter-clear" @click="resetOrderFilters(filters)">{{ t('clearAll') }}</button>
        </div>

        <SkeletonTable v-if="loading" :columns="SKELETON_COLUMNS" :list="$q.screen.lt.md" />

        <div v-else-if="!filteredOrders.length" class="vp-empty">
          <div class="vp-empty-icon"><q-icon name="o_receipt_long" size="24px" /></div>
          <div class="vp-empty-title">{{ orders.length ? t('noMatchTitle') : t('emptyTitle') }}</div>
          <div class="vp-empty-text">
            {{ orders.length ? t('noMatchText') : t('emptyText') }}
          </div>
          <q-btn v-if="orders.length && filterChips.length" outline no-caps color="primary" :label="t('clearFilters')" class="vp-pill-btn ol-empty-btn" @click="resetOrderFilters(filters)" />
        </div>

        <!-- A table on wide screens; the Order, Date and Total headings sort their columns. -->
        <div v-else-if="!$q.screen.lt.md" class="vp-table-wrap">
          <table class="vp-table">
            <thead>
              <tr>
                <th class="col-order" :aria-sort="ariaSort('id')">
                  <button type="button" class="vp-sort" :class="{ 'vp-sort--on': sortDirection(filters.sort, 'id') }" @click="sortBy('id')">
                    {{ t('colOrder') }} <q-icon :name="sortIcon('id')" size="14px" />
                  </button>
                </th>
                <th>{{ t('colCustomer') }}</th>
                <th class="col-date" :aria-sort="ariaSort('date')">
                  <button type="button" class="vp-sort" :class="{ 'vp-sort--on': sortDirection(filters.sort, 'date') }" @click="sortBy('date')">
                    {{ t('colDate') }} <q-icon :name="sortIcon('date')" size="14px" />
                  </button>
                </th>
                <th class="text-right col-total" :aria-sort="ariaSort('total')">
                  <button type="button" class="vp-sort" :class="{ 'vp-sort--on': sortDirection(filters.sort, 'total') }" @click="sortBy('total')">
                    {{ t('colTotal') }} <q-icon :name="sortIcon('total')" size="14px" />
                  </button>
                </th>
                <th class="col-status">{{ t('colStatus') }}</th>
                <th class="col-open"><span class="vp-sr-only">Open</span></th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="order in pagedOrders"
                :key="order.order_id"
                class="vp-row"
                tabindex="0"
                @click="goToOrder(order.order_id)"
                @keydown.enter="goToOrder(order.order_id)"
              >
                <td><span class="vp-id">#{{ order.order_id }}</span></td>
                <td>
                  <div class="vp-person">
                    <q-avatar size="32px" class="vp-avatar">
                      <img v-if="order.consumer?.profile_picture_url" :src="order.consumer.profile_picture_url" alt="" />
                      <q-icon v-else name="o_person" size="18px" />
                    </q-avatar>
                    <span class="vp-name">{{ order.consumer?.full_name || t('unknownCustomer') }}</span>
                  </div>
                </td>
                <td class="vp-muted">{{ formatDate(order.created_at) }}</td>
                <td class="text-right vp-amount">₱{{ formatNumber(order.total_amount) }}</td>
                <td>
                  <span class="vp-status" :class="`vp-status--${getStatusTone(order.status)}`">
                    {{ translateStatus(order.status) }}
                  </span>
                </td>
                <td class="text-right">
                  <q-btn flat round dense icon="o_chevron_right" class="vp-open-btn" :aria-label="`Open order #${order.order_id}`" @click.stop="goToOrder(order.order_id)" />
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- A tappable list on phones, with the total and status on the right; the sort lives in the Filters panel. -->
        <div v-else class="vp-list">
          <button v-for="order in pagedOrders" :key="order.order_id" type="button" class="vp-list-item" @click="goToOrder(order.order_id)">
            <q-avatar size="38px" class="vp-avatar">
              <img v-if="order.consumer?.profile_picture_url" :src="order.consumer.profile_picture_url" alt="" />
              <q-icon v-else name="o_person" size="20px" />
            </q-avatar>
            <div class="vp-list-body">
              <span class="vp-name">{{ order.consumer?.full_name || t('unknownCustomer') }}</span>
              <div class="vp-list-meta">#{{ order.order_id }} · {{ formatDate(order.created_at) }}</div>
            </div>
            <div class="vp-list-side">
              <span class="vp-amount">₱{{ formatNumber(order.total_amount) }}</span>
              <span class="vp-status" :class="`vp-status--${getStatusTone(order.status)}`">
                {{ translateStatus(order.status) }}
              </span>
            </div>
          </button>
        </div>

        <div v-if="!loading && pageCount > 1" class="vp-pager">
          <span>{{ t('showing') }} {{ rangeStart }}–{{ rangeEnd }} {{ t('of') }} {{ filteredOrders.length }}</span>
          <div class="vp-pager-btns">
            <q-btn outline no-caps color="primary" icon="o_chevron_left" class="vp-pill-btn" aria-label="Previous page" :disable="page === 1" @click="page--" />
            <q-btn outline no-caps color="primary" icon="o_chevron_right" class="vp-pill-btn" aria-label="Next page" :disable="page === pageCount" @click="page++" />
          </div>
        </div>
      </div>

    </div>
  </q-page>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { api } from '@/boot/axios'
import { statusKey } from '@/utils/orderStatus'
import { emptyOrderFilters, applyOrderFilters, describeFilters, clearFilter, resetOrderFilters, toggleSort, sortDirection } from '@/utils/orderFilters'
import { useLanguage } from '@/composables/useLanguage'
import OrderTableFilters from '@/components/vendor/OrderTableFilters.vue'
import SkeletonTable from '@/components/vendor/SkeletonTable.vue'

const router = useRouter()
const $q = useQuasar()

// Language Dictionary for this page
const orderListDict = {
  en: {
    title: 'Order List',
    subtitle: 'Track and manage every order from your customers.',
    exportBtn: 'Export Report',
    searchDesk: 'Search order ID or customer',
    searchMob: 'Search orders',
    filteredBy: 'Filtered by',
    clearAll: 'Clear all',
    noMatchTitle: 'No matching orders',
    emptyTitle: 'No orders yet',
    noMatchText: 'Try another search, status or filter.',
    emptyText: 'New orders from customers will show up here.',
    clearFilters: 'Clear filters',
    colOrder: 'Order',
    colCustomer: 'Customer',
    colDate: 'Date',
    colTotal: 'Total',
    colStatus: 'Status',
    showing: 'Showing',
    of: 'of',
    unknownCustomer: 'Unknown',
    statusAll: 'All',
    statusPlaced: 'Placed',
    statusPreparing: 'Preparing',
    statusReady: 'Ready for pickup',
    statusPickedUp: 'Picked up',
    statusCancelled: 'Cancelled',
    exportFail: 'Failed to generate the order report. Please try again.'
  },
  ph: {
    title: 'Listahan ng Order',
    subtitle: 'I-track at i-manage ang mga order ng customers.',
    exportBtn: 'I-export ang Report',
    searchDesk: 'Hanapin ang order ID o customer',
    searchMob: 'Hanapin ang order',
    filteredBy: 'Naka-filter sa',
    clearAll: 'I-clear lahat',
    noMatchTitle: 'Walang nahanap na order',
    emptyTitle: 'Wala pang order',
    noMatchText: 'Subukang ibahin ang search, status o filter.',
    emptyText: 'Dito lalabas ang mga bagong order mula sa customers.',
    clearFilters: 'I-clear ang filters',
    colOrder: 'Order',
    colCustomer: 'Customer',
    colDate: 'Petsa',
    colTotal: 'Kabuuan',
    colStatus: 'Status',
    showing: 'Pinapakita ang',
    of: 'mula sa',
    unknownCustomer: 'Hindi Kilala',
    statusAll: 'Lahat',
    statusPlaced: 'Na-order',
    statusPreparing: 'Inihahanda',
    statusReady: 'Pwede nang kunin',
    statusPickedUp: 'Nakuha na',
    statusCancelled: 'Kinansela',
    exportFail: 'Failed ma-generate ang order report. Paki-try ulit.'
  }
}

const { t, lang } = useLanguage(orderListDict)

// Determines the correct color class for the order status badges
const getStatusTone = (status) => {
  const s = String(status || '').toLowerCase().trim().replace(/[\s_-]+/g, '_')
  if (s.includes('ready')) return 'ready'
  if (s.includes('picked') || s.includes('complete')) return 'done'
  if (s.includes('cancel')) return 'cancelled'
  if (s.includes('prepar')) return 'preparing'
  return 'placed'
}

// Directly translates the status string according to specified mappings
const translateStatus = (status) => {
  if (!status) return ''
  const key = String(status).toLowerCase().trim().replace(/[\s-]+/g, '_')

  const statusDict = {
    en: {
      placed: 'Placed',
      preparing: 'Preparing',
      ready_for_pickup: 'Ready for pickup',
      picked_up: 'Picked up',
      cancelled: 'Cancelled',
      completed: 'Completed'
    },
    ph: {
      placed: 'Na-order',
      preparing: 'Inihahanda',
      ready_for_pickup: 'Pwede nang kunin',
      picked_up: 'Nakuha na',
      cancelled: 'Kinansela',
      completed: 'Nakuha na'
    }
  }

  return statusDict[lang.value]?.[key] || status
}

// Dynamically translated status filters
const localizedFilters = computed(() => [
  { key: 'all', label: t('statusAll') },
  { key: 'placed', label: t('statusPlaced') },
  { key: 'preparing', label: t('statusPreparing') },
  { key: 'ready_for_pickup', label: t('statusReady') },
  { key: 'picked_up', label: t('statusPickedUp') },
  { key: 'cancelled', label: t('statusCancelled') }
])

// The placeholder rows take the same columns as the table.
const SKELETON_COLUMNS = [
  { width: '12%', type: 'pill', size: 56 },
  { type: 'avatar' },
  { width: '21%', type: 'text' },
  { width: '13%', type: 'text', align: 'right' },
  { width: '17%', type: 'pill', size: 96 },
  { width: '6%', type: 'icon', align: 'right' }
]
const PAGE_SIZE = 10

const orders = ref([])
const loading = ref(true)
const isExporting = ref(false)
const search = ref('')
const activeStatus = ref('all')
const page = ref(1)
const filters = ref(emptyOrderFilters())

const matchesSearch = order => {
  const term = (search.value || '').trim().toLowerCase().replace('#', '')
  return !term || String(order.order_id).includes(term) || (order.consumer?.full_name || '').toLowerCase().includes(term)
}

const matchesStatus = (order, key) => key === 'all' || statusKey(order.status) === key

// Search and the filter panel apply first, so each status chip counts what it would show.
const baseOrders = computed(() => applyOrderFilters(orders.value.filter(matchesSearch), filters.value))

const countFor = key => baseOrders.value.filter(order => matchesStatus(order, key)).length

const filteredOrders = computed(() => baseOrders.value.filter(order => matchesStatus(order, activeStatus.value)))

const filterChips = computed(() => describeFilters(filters.value))

const pageCount = computed(() => Math.max(1, Math.ceil(filteredOrders.value.length / PAGE_SIZE)))
const pagedOrders = computed(() => filteredOrders.value.slice((page.value - 1) * PAGE_SIZE, page.value * PAGE_SIZE))
const rangeStart = computed(() => (page.value - 1) * PAGE_SIZE + 1)
const rangeEnd = computed(() => Math.min(page.value * PAGE_SIZE, filteredOrders.value.length))

// A new search, status, filter or sort starts back on the first page.
watch([search, activeStatus, () => JSON.stringify(filters.value)], () => { page.value = 1 })

const sortBy = field => { filters.value.sort = toggleSort(filters.value.sort, field) }
const sortIcon = field => ({ asc: 'o_arrow_upward', desc: 'o_arrow_downward' }[sortDirection(filters.value.sort, field)] || 'o_unfold_more')
const ariaSort = field => ({ asc: 'ascending', desc: 'descending' }[sortDirection(filters.value.sort, field)] || 'none')

const formatNumber = num => Number(num || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })

const formatDate = dateString => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' })
}

const goToOrder = id => router.push('/vendor/orders/' + id)

const exportOrders = async () => {
  isExporting.value = true
  try {
    const response = await api.get('/vendor/orders/export', { responseType: 'blob' })
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `Tindahan-Order-List-Report-${new Date().toISOString().split('T')[0]}.pdf`)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    setTimeout(() => window.URL.revokeObjectURL(url), 1000)
  } catch (error) {
    console.error('Export failed:', error)
    $q.notify({ type: 'negative', message: t('exportFail'), position: 'top-right' })
  } finally {
    isExporting.value = false
  }
}

onMounted(async () => {
  try {
    const res = await api.get('/vendor/orders')
    if (res.data) orders.value = res.data.data || res.data
  } catch (error) {
    console.error('Failed to load orders', error)
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
/* Status badge styling */
.vp-status {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  height: 26px;
  padding: 0 12px;
  border-radius: 13px;
  font-size: 13px;
  font-weight: 700;
  white-space: nowrap;
}

.vp-status--placed {
  background-color: #dbeafe !important;
  color: #1d4ed8 !important;
}

.vp-status--preparing {
  background-color: #fef3c7 !important;
  color: #b45309 !important;
}

.vp-status--ready {
  background-color: #e0e7ff !important;
  color: #4338ca !important;
}

.vp-status--done {
  background-color: #dcfce7 !important;
  color: #15803d !important;
}

.vp-status--cancelled {
  background-color: #fee2e2 !important;
  color: #b91c1c !important;
}

/* Column widths as shares of the table, so the columns spread evenly at any width. */
.vp-table .col-order { width: 12%; }
.vp-table .col-date { width: 21%; }
.vp-table .col-total { width: 13%; }
.vp-table .col-status { width: 17%; }
.vp-table .col-open { width: 6%; }

.ol-empty-btn {
  margin-top: 10px;
}
</style>