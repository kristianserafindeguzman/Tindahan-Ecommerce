<template>
  <q-page class="vp-page">
    <div class="vp-container">

      <div v-if="!selectedOrder" class="vp-header">
        <div>
          <h1 class="vp-title">{{ t('title') }}</h1>
          <p class="vp-subtitle">{{ t('subtitle') }}</p>
        </div>
      </div>

      <!-- An open order takes the full width, so its cards have room for two columns. -->
      <div class="co-grid" :class="{ 'co-grid--full': selectedOrder }">

        <!-- CUSTOMER DIRECTORY -->
        <aside v-if="!selectedOrder" class="vp-card co-directory">
          <div class="co-directory-head">
            <div class="co-card-title">
              {{ t('customersLabel') }}
              <span class="co-count">{{ customersLoading ? '…' : filteredCustomers.length }}</span>
            </div>
            <q-input
              v-model="customerSearch"
              outlined
              dense
              clearable
              clear-icon="o_close"
              hide-bottom-space
              :placeholder="t('searchCustomer')"
              class="vp-search co-directory-search"
            >
              <template #prepend>
                <q-icon name="o_search" size="18px" />
              </template>
            </q-input>
          </div>

          <div class="co-directory-list">
            <div v-if="customersLoading" class="co-directory-skeletons">
              <div v-for="n in 5" :key="n" class="co-skeleton-row">
                <q-skeleton type="QAvatar" size="38px" />
                <div class="co-skeleton-lines">
                  <q-skeleton type="text" width="65%" />
                  <q-skeleton type="text" width="40%" />
                </div>
              </div>
            </div>

            <div v-else-if="!filteredCustomers.length" class="vp-empty co-empty-small">
              <div class="vp-empty-icon"><q-icon name="o_group_off" size="24px" /></div>
              <div class="vp-empty-title">{{ t('noCustomersTitle') }}</div>
              <div class="vp-empty-text">{{ customers.length ? t('noCustomersTextAlt') : t('noCustomersText') }}</div>
            </div>

            <template v-else>
              <button
                v-for="customer in filteredCustomers"
                :key="customer.user_id"
                type="button"
                class="co-customer"
                :class="{ 'co-customer--active': selectedCustomer?.user_id === customer.user_id }"
                :aria-pressed="selectedCustomer?.user_id === customer.user_id"
                @click="selectCustomer(customer)"
              >
                <q-avatar size="38px" font-size="13px" class="vp-avatar co-avatar">
                  <img v-if="customer.profile_picture_url" :src="customer.profile_picture_url" alt="" />
                  <span v-else>{{ initials(customer.full_name) }}</span>
                </q-avatar>
                <span class="co-customer-body">
                  <span class="vp-name">{{ customer.full_name }}</span>
                  <span class="co-customer-phone">{{ customer.phone_number || t('noPhone') }}</span>
                </span>
                <q-icon name="o_chevron_right" size="18px" class="co-customer-arrow" />
              </button>
            </template>
          </div>
        </aside>

        <!-- ORDERS OR THE CHOSEN ORDER -->
        <section class="co-main">
          <OrderDetails v-if="selectedOrder" :orderId="selectedOrder.order_id" isEmbedded @back="selectedOrder = null" @status-changed="onStatusChanged" />

          <div v-else class="vp-card">
            <div v-if="!selectedCustomer" class="vp-empty co-pick">
              <div class="vp-empty-icon"><q-icon name="o_person_search" size="24px" /></div>
              <div class="vp-empty-title">{{ t('chooseCustomerTitle') }}</div>
              <div class="vp-empty-text">{{ t('chooseCustomerText') }}</div>
            </div>

            <template v-else>
              <div class="co-orders-head">
                <div class="co-person">
                  <q-avatar size="44px" font-size="13px" class="vp-avatar co-avatar">
                    <img v-if="selectedCustomer.profile_picture_url" :src="selectedCustomer.profile_picture_url" alt="" />
                    <span v-else>{{ initials(selectedCustomer.full_name) }}</span>
                  </q-avatar>
                  <div class="co-person-text">
                    <div class="co-card-title co-person-name">{{ selectedCustomer.full_name }}</div>
                    <div class="co-person-meta">
                      {{ ordersLoading ? t('loadingOrders') : `${customerOrders.length} ${customerOrders.length === 1 ? t('order') : t('orders')}` }}
                    </div>
                  </div>
                </div>
                <q-btn
                  outline
                  no-caps
                  color="primary"
                  icon="o_download"
                  :label="t('exportReport')"
                  class="vp-pill-btn"
                  :disable="!filteredCustomerOrders.length || ordersLoading"
                  :loading="isExporting"
                  @click="exportCustomerOrdersPDF"
                />
              </div>

              <div class="vp-toolbar">
                <div class="vp-search-row">
                  <q-input
                    v-model="orderSearch"
                    outlined
                    dense
                    clearable
                    clear-icon="o_close"
                    hide-bottom-space
                    :placeholder="t('searchOrder')"
                    class="vp-search"
                  >
                    <template #prepend>
                      <q-icon name="o_search" size="18px" />
                    </template>
                  </q-input>
                  <OrderTableFilters v-model="filters" :result-count="filteredCustomerOrders.length" />
                </div>

                <div class="vp-chips" role="tablist" aria-label="Filter orders by status">
                  <button
                    v-for="filter in localizedFilters"
                    :key="filter.key"
                    type="button"
                    role="tab"
                    class="vp-chip"
                    :class="{ 'vp-chip--active': selectedStatusFilter === filter.key }"
                    :aria-selected="selectedStatusFilter === filter.key"
                    @click="selectedStatusFilter = filter.key"
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

              <SkeletonTable v-if="ordersLoading" :columns="SKELETON_COLUMNS" :rows="5" :list="$q.screen.lt.md" />

              <div v-else-if="!filteredCustomerOrders.length" class="vp-empty">
                <div class="vp-empty-icon"><q-icon name="o_receipt_long" size="24px" /></div>
                <div class="vp-empty-title">{{ customerOrders.length ? t('noMatchTitle') : t('noOrdersTitle') }}</div>
                <div class="vp-empty-text">
                  {{ customerOrders.length ? t('noMatchText') : t('noOrdersText') }}
                </div>
                <q-btn v-if="customerOrders.length && filterChips.length" outline no-caps color="primary" :label="t('clearFilters')" class="vp-pill-btn co-empty-btn" @click="resetOrderFilters(filters)" />
              </div>

              <div v-else-if="!$q.screen.lt.md" class="vp-table-wrap">
                <table class="vp-table co-table">
                  <thead>
                    <tr>
                      <th class="col-order" :aria-sort="ariaSort('id')">
                        <button type="button" class="vp-sort" :class="{ 'vp-sort--on': sortDirection(filters.sort, 'id') }" @click="sortBy('id')">
                          {{ t('colOrder') }} <q-icon :name="sortIcon('id')" size="14px" />
                        </button>
                      </th>
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
                      v-for="order in filteredCustomerOrders"
                      :key="order.order_id"
                      class="vp-row"
                      tabindex="0"
                      @click="goToOrder(order)"
                      @keydown.enter="goToOrder(order)"
                    >
                      <td><span class="vp-id">#{{ order.order_id }}</span></td>
                      <td class="vp-muted">{{ formatDate(order.created_at) }}</td>
                      <td class="text-right vp-amount">₱{{ formatNumber(order.total_amount) }}</td>
                      <td>
                        <span class="vp-status" :class="`vp-status--${getStatusTone(order.status)}`">
                          {{ translateStatus(order.status) }}
                        </span>
                      </td>
                      <td class="text-right">
                        <q-btn flat round dense icon="o_chevron_right" class="vp-open-btn" :aria-label="`Open order #${order.order_id}`" @click.stop="goToOrder(order)" />
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div v-else class="vp-list">
                <button v-for="order in filteredCustomerOrders" :key="order.order_id" type="button" class="vp-list-item" @click="goToOrder(order)">
                  <div class="vp-list-body">
                    <span class="vp-name">{{ t('colOrder') }} #{{ order.order_id }}</span>
                    <div class="vp-list-meta">{{ formatDate(order.created_at) }}</div>
                  </div>
                  <div class="vp-list-side">
                    <span class="vp-amount">₱{{ formatNumber(order.total_amount) }}</span>
                    <span class="vp-status" :class="`vp-status--${getStatusTone(order.status)}`">
                      {{ translateStatus(order.status) }}
                    </span>
                  </div>
                </button>
              </div>
            </template>
          </div>
        </section>

      </div>
    </div>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { api } from '@/boot/axios'
import { useQuasar } from 'quasar'
import OrderDetails from './OrderDetails.vue'
import OrderTableFilters from '@/components/vendor/OrderTableFilters.vue'
import SkeletonTable from '@/components/vendor/SkeletonTable.vue'
import { statusKey } from '@/utils/orderStatus'
import { emptyOrderFilters, applyOrderFilters, describeFilters, clearFilter, resetOrderFilters, toggleSort, sortDirection } from '@/utils/orderFilters'
import { useLanguage } from '@/composables/useLanguage'

const $q = useQuasar()

// Language Dictionary for this page
const customerOrdersDict = {
  en: {
    title: 'Customer Orders',
    subtitle: "See each customer's order history and export it as a report.",
    customersLabel: 'Customers',
    searchCustomer: 'Search customer name',
    noCustomersTitle: 'No customers found',
    noCustomersTextAlt: 'Try another name.',
    noCustomersText: 'Customers appear here after their first order.',
    noPhone: 'No phone number',
    chooseCustomerTitle: 'Choose a customer',
    chooseCustomerText: 'Pick someone from the list to see their orders.',
    loadingOrders: 'Loading orders…',
    order: 'order',
    orders: 'orders',
    exportReport: 'Export Report',
    searchOrder: 'Search order ID',
    filteredBy: 'Filtered by',
    clearAll: 'Clear all',
    noMatchTitle: 'No matching orders',
    noOrdersTitle: 'No orders yet',
    noMatchText: 'Try another order ID, status or filter.',
    noOrdersText: 'This customer has not placed any orders yet.',
    clearFilters: 'Clear filters',
    colOrder: 'Order',
    colDate: 'Date',
    colTotal: 'Total',
    colStatus: 'Status',
    downloadSuccess: 'Customer order report downloaded.',
    downloadFail: 'Failed to download the PDF report. Please try again.',
    statusAll: 'All',
    statusPlaced: 'Placed',
    statusPreparing: 'Preparing',
    statusReady: 'Ready for pickup',
    statusPickedUp: 'Picked up',
    statusCancelled: 'Cancelled'
  },
  ph: {
    title: 'Order ng Customers',
    subtitle: 'Tingnan ang order history ng bawat customer at i-export ito bilang report.',
    customersLabel: 'Customers',
    searchCustomer: 'Hanapin ang pangalan ng customer',
    noCustomersTitle: 'Walang nahanap na customer',
    noCustomersTextAlt: 'Subukang ibahin ang pangalan.',
    noCustomersText: 'Dito lalabas ang mga customers pagkatapos ng una nilang order.',
    noPhone: 'Walang phone number',
    chooseCustomerTitle: 'Pumili ng customer',
    chooseCustomerText: 'Pumili sa listahan para makita ang kanilang mga order.',
    loadingOrders: 'Niloload ang mga order…',
    order: 'order',
    orders: 'orders',
    exportReport: 'I-export ang Report',
    searchOrder: 'Hanapin ang order ID',
    filteredBy: 'Naka-filter sa',
    clearAll: 'I-clear lahat',
    noMatchTitle: 'Walang nahanap na order',
    noOrdersTitle: 'Wala pang order',
    noMatchText: 'Subukang ibahin ang order ID, status o filter.',
    noOrdersText: 'Wala pang order ang customer na ito.',
    clearFilters: 'I-clear ang filters',
    colOrder: 'Order',
    colDate: 'Petsa',
    colTotal: 'Kabuuan',
    colStatus: 'Status',
    downloadSuccess: 'Na-download na ang order report ng customer.',
    downloadFail: 'Failed ma-download ang PDF report. Paki-try ulit.',
    statusAll: 'Lahat',
    statusPlaced: 'Na-order',
    statusPreparing: 'Inihahanda',
    statusReady: 'Pwede nang kunin',
    statusPickedUp: 'Nakuha na',
    statusCancelled: 'Kinansela'
  }
}

const { t, lang } = useLanguage(customerOrdersDict)

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

// Dynamically mapped filters using the translation system
const localizedFilters = computed(() => [
  { key: 'all', label: t('statusAll') },
  { key: 'placed', label: t('statusPlaced') },
  { key: 'preparing', label: t('statusPreparing') },
  { key: 'ready_for_pickup', label: t('statusReady') },
  { key: 'picked_up', label: t('statusPickedUp') },
  { key: 'cancelled', label: t('statusCancelled') }
])

// The placeholder rows take the same columns as the orders table.
const SKELETON_COLUMNS = [
  { width: '16%', type: 'pill', size: 56 },
  { type: 'text' },
  { width: '18%', type: 'text', align: 'right' },
  { width: '22%', type: 'pill', size: 96 },
  { width: '8%', type: 'icon', align: 'right' }
]

const customerSearch = ref('')
const orderSearch = ref('')
const selectedStatusFilter = ref('all')
const filters = ref(emptyOrderFilters())

const customers = ref([])
const selectedCustomer = ref(null)
const selectedOrder = ref(null)
const customerOrders = ref([])

const customersLoading = ref(true)
const ordersLoading = ref(false)
const isExporting = ref(false)

const filteredCustomers = computed(() => {
  const needle = (customerSearch.value || '').trim().toLowerCase()
  if (!needle) return customers.value
  return customers.value.filter(c => (c.full_name || '').toLowerCase().includes(needle))
})

const matchesSearch = order => {
  const term = (orderSearch.value || '').trim().toLowerCase().replace('#', '')
  return !term || String(order.order_id).toLowerCase().includes(term)
}

const matchesStatus = (order, key) => key === 'all' || statusKey(order.status) === key

// Search and the filter panel apply first, so each status chip counts what it would show.
const baseOrders = computed(() => applyOrderFilters(customerOrders.value.filter(matchesSearch), filters.value))

const countFor = key => baseOrders.value.filter(order => matchesStatus(order, key)).length

const filteredCustomerOrders = computed(() => baseOrders.value.filter(order => matchesStatus(order, selectedStatusFilter.value)))

const filterChips = computed(() => describeFilters(filters.value))

const sortBy = field => { filters.value.sort = toggleSort(filters.value.sort, field) }
const sortIcon = field => ({ asc: 'o_arrow_upward', desc: 'o_arrow_downward' }[sortDirection(filters.value.sort, field)] || 'o_unfold_more')
const ariaSort = field => ({ asc: 'ascending', desc: 'descending' }[sortDirection(filters.value.sort, field)] || 'none')

const initials = name => {
  const parts = String(name || '').trim().split(/\s+/).filter(Boolean)
  if (!parts.length) return '?'
  return (parts[0][0] + (parts.length > 1 ? parts[parts.length - 1][0] : '')).toUpperCase()
}

const formatNumber = num => Number(num || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })

const formatDate = dateString => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' })
}

const fetchCustomers = async () => {
  customersLoading.value = true
  try {
    const res = await api.get('/vendor/customers')
    customers.value = res.data || []
  } catch (error) {
    console.error('Failed to load customers', error)
  } finally {
    customersLoading.value = false
  }
}

const selectCustomer = async customer => {
  selectedCustomer.value = customer
  selectedOrder.value = null
  customerOrders.value = []
  orderSearch.value = ''
  selectedStatusFilter.value = 'all'
  filters.value = emptyOrderFilters()
  ordersLoading.value = true
  try {
    const res = await api.get(`/vendor/customers/${customer.user_id}/orders`)
    // Ignores a slow reply for a customer who is no longer selected.
    if (selectedCustomer.value?.user_id === customer.user_id) customerOrders.value = res.data || []
  } catch (error) {
    console.error('Failed to load customer orders', error)
  } finally {
    if (selectedCustomer.value?.user_id === customer.user_id) ordersLoading.value = false
  }
}

const downloadPdf = (data, filename) => {
  const url = window.URL.createObjectURL(new Blob([data], { type: 'application/pdf' }))
  const link = document.createElement('a')
  link.href = url
  link.setAttribute('download', filename)
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  setTimeout(() => window.URL.revokeObjectURL(url), 1000)
}

const exportCustomerOrdersPDF = async () => {
  if (!selectedCustomer.value || !filteredCustomerOrders.value.length) return

  const customerName = (selectedCustomer.value.full_name || 'Customer').replace(/[^a-zA-Z0-9]/g, '_')
  const filename = `Customer_Orders_${customerName}_${new Date().toISOString().split('T')[0]}.pdf`

  isExporting.value = true
  try {
    const response = await api.get(`/vendor/customers/${selectedCustomer.value.user_id}/orders/export`, { responseType: 'blob' })
    downloadPdf(response.data, filename)
    $q.notify({ type: 'positive', message: t('downloadSuccess'), position: 'top-right' })
  } catch {
    // Falls back to the general export filtered to this customer.
    try {
      const fallbackRes = await api.get('/vendor/orders/export', {
        params: { customer_id: selectedCustomer.value.user_id },
        responseType: 'blob'
      })
      downloadPdf(fallbackRes.data, filename)
    } catch (err) {
      console.error('PDF Export failed:', err)
      $q.notify({ type: 'negative', message: t('downloadFail'), position: 'top-right' })
    }
  } finally {
    isExporting.value = false
  }
}

// A status changed inside the open order is copied into the list, so its badge and the status counts are right on the way back.
const onStatusChanged = ({ orderId, status, cancellationReason }) => {
  const listed = customerOrders.value.find(o => Number(o.order_id) === Number(orderId))
  if (!listed) return
  listed.status = status
  if (cancellationReason) listed.cancellation_reason = cancellationReason
}

const goToOrder = order => {
  if (ordersLoading.value) return
  selectedOrder.value = order
  if ($q.screen.lt.md) nextTick(() => window.scrollTo({ top: 0, behavior: 'smooth' }))
}

onMounted(fetchCustomers)
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

.co-grid {
  display: grid;
  grid-template-columns: 300px minmax(0, 1fr);
  align-items: start;

  gap: var(--sp-gap);
}

.co-grid--full {
  grid-template-columns: minmax(0, 1fr);
}

.co-directory {
  position: sticky;
  top: 24px;

  display: flex;
  flex-direction: column;

  overflow: hidden;
}

.co-directory-head {
  padding: 16px;

  border-bottom: 1px solid var(--c-hairline);
}

.co-card-title {
  display: flex;
  align-items: center;

  gap: 8px;

  font-size: var(--fs-lg);
  font-weight: 700;

  color: var(--c-text);
}

.co-count {
  padding: 1px 8px;

  border-radius: var(--r-pill);

  background: var(--c-surface);

  font-size: var(--fs-2xs);
  font-weight: 700;

  color: var(--c-text-3);
}

.co-directory-search {
  max-width: none;
  margin-top: 12px;
}

.co-directory-list {
  max-height: calc(100vh - 260px);
  padding: 6px;
  overflow-y: auto;
}

.co-directory-skeletons {
  padding: 4px;
}

.co-skeleton-row {
  display: flex;
  align-items: center;

  gap: 10px;
  padding: 8px 6px;
}

.co-skeleton-lines {
  flex: 1;
}

.co-empty-small {
  padding: 32px 12px;
}

.co-customer {
  position: relative;

  display: flex;
  align-items: center;

  gap: 10px;
  width: 100%;
  padding: 10px;

  border: none;
  border-radius: var(--r-control);

  background: transparent;

  font-family: inherit;
  text-align: left;

  cursor: pointer;

  transition: background-color 0.15s;
}

.co-customer:hover {
  background: var(--c-surface-2);
}

.co-customer:focus-visible {
  outline: 2px solid var(--c-brand);
  outline-offset: -2px;
}

.co-customer--active,
.co-customer--active:hover {
  background: var(--c-brand-tint);
}

/* A short brand bar marks the chosen customer. */
.co-customer--active::before {
  content: '';

  position: absolute;
  top: 10px;
  bottom: 10px;
  left: 0;

  width: 3px;

  border-radius: var(--r-pill);

  background: var(--c-brand);
}

.co-customer--active .vp-name {
  color: var(--c-brand);
}

.co-avatar {
  font-size: var(--fs-xs);
  font-weight: 700;
}

.co-customer-body {
  display: flex;
  flex-direction: column;
  flex: 1;

  min-width: 0;

  font-size: var(--fs-sm);
}

.co-customer-phone {
  margin-top: 2px;

  font-size: var(--fs-xs);

  color: var(--c-muted);
}

.co-customer-arrow {
  flex-shrink: 0;

  color: var(--c-border-strong);
}

.co-customer--active .co-customer-arrow {
  color: var(--c-brand);
}

.co-pick {
  padding-block: 72px;
}

.co-orders-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;

  gap: 12px;
  padding: 16px 20px;

  border-bottom: 1px solid var(--c-hairline);
}

.co-person {
  display: flex;
  align-items: center;

  gap: 12px;
  min-width: 0;
}

.co-person-text {
  min-width: 0;
}

.co-person-name {
  display: block;
  overflow: hidden;

  white-space: nowrap;
  text-overflow: ellipsis;
}

.co-person-meta {
  margin-top: 2px;

  font-size: var(--fs-xs);

  color: var(--c-muted);
}

.co-table {
  min-width: 560px;
}

.co-empty-btn {
  margin-top: 10px;
}

.co-table .col-order { width: 16%; }
.co-table .col-total { width: 18%; }
.co-table .col-status { width: 22%; }
.co-table .col-open { width: 8%; }

/* Tablets and phones stack the directory above the orders. */
@media (max-width: 1023px) {
  .co-grid {
    grid-template-columns: minmax(0, 1fr);
  }

  .co-directory {
    position: static;
  }

  .co-directory-list {
    max-height: 280px;
  }
}

@media (max-width: 600px) {
  .co-orders-head {
    padding: 14px 16px;
  }

  .co-orders-head .vp-pill-btn {
    width: 100%;
  }
}
</style>