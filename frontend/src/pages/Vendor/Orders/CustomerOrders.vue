<template>
  <q-page class="vendor-page relative-position" :class="{ 'mobile-page-padding': $q.screen.lt.md }">
    <!-- Subtle Ambient Background Glows -->
    <div class="bg-glow bg-glow-primary desktop-only"></div>
    <div class="bg-glow bg-glow-secondary desktop-only"></div>

    <div class="page-container relative-position" style="z-index: 1;">
      
      <!-- ================= HEADER AREA ================= -->
      <div 
        v-if="!selectedOrder || !$q.screen.lt.md" 
        class="page-header q-mb-lg q-mt-sm row items-center justify-between"
      >
        <div class="row items-center no-wrap">
          <div class="glass-icon-box q-mr-md" :style="$q.screen.lt.md ? 'width: 44px; height: 44px;' : 'width: 48px; height: 48px;'">
            <q-icon name="people" :size="$q.screen.lt.md ? '22px' : '26px'" class="text-brand-red" />
          </div>
          <div>
            <h1 :class="$q.screen.lt.md ? 'text-h5' : 'text-h4'" class="text-weight-bolder text-slate-800 q-ma-none tracking-tight leading-tight">
              Customer Orders
            </h1>
            <p :class="$q.screen.lt.md ? 'text-caption' : 'text-body1'" class="text-slate-500 q-mt-xs q-mb-none font-medium">
              View order history, filter statuses, and export reports per customer.
            </p>
          </div>
        </div>
      </div>

      <!-- Main Columns Grid -->
      <div class="row q-col-gutter-lg q-col-gutter-md-xl" :class="{ 'h-full-container': !$q.screen.lt.md }">
        
        <!-- ================= LEFT COLUMN: CUSTOMERS DIRECTORY ================= -->
        <div class="col-12 col-md-4" v-if="!selectedOrder || !$q.screen.lt.md">
          <q-card class="premium-glass-card h-full flex column">
            <!-- Directory Header & Search -->
            <q-card-section class="q-pa-md border-bottom-light">
              <div class="row items-center justify-between no-wrap q-mb-sm text-left">
                <div class="row items-center no-wrap">
                  <span class="header-accent-red q-mr-sm"></span>
                  <span class="text-weight-bold text-slate-800 text-subtitle1">Customer Directory</span>
                </div>
                <div class="text-caption text-slate-500 font-medium">
                  {{ customersLoading ? 'Loading...' : `${filteredCustomers.length} Customer(s)` }}
                </div>
              </div>

              <q-input 
                v-model="customerSearch" 
                outlined 
                dense 
                class="custom-glass-input q-mt-sm" 
                placeholder="Search customer by name..." 
                hide-bottom-space
              >
                <template v-slot:prepend>
                  <q-icon name="search" size="18px" color="blue-grey-4" />
                </template>
                <template v-if="customerSearch" v-slot:append>
                  <q-icon name="close" size="16px" class="cursor-pointer text-slate-400" @click="customerSearch = ''" />
                </template>
              </q-input>
            </q-card-section>
            
            <!-- Customers Scrollable List (Natural Left Alignment on Mobile) -->
            <q-card-section class="q-pa-none scroll flex-1 text-left" :style="$q.screen.lt.md ? 'max-height: 280px;' : 'max-height: calc(100vh - 300px);'">
              
              <!-- Customer Skeletons -->
              <div v-if="customersLoading" class="q-pa-xs">
                <div v-for="n in 5" :key="'cust-skel-' + n" class="row items-center no-wrap q-pa-sm q-my-xs text-left">
                  <q-skeleton type="QAvatar" size="38px" class="q-mr-sm flex-shrink-0" />
                  <div class="col">
                    <q-skeleton type="text" width="65%" height="18px" />
                    <q-skeleton type="text" width="45%" height="14px" class="q-mt-xs" />
                  </div>
                </div>
              </div>

              <!-- Empty State -->
              <div v-else-if="filteredCustomers.length === 0" class="full-width column flex-center q-pa-xl text-center">
                <div class="empty-icon-box q-mb-sm">
                  <q-icon name="group_off" size="32px" color="blue-grey-4" />
                </div>
                <div class="text-weight-bold text-slate-700">No customers found</div>
                <div class="text-caption text-slate-400 q-mt-xs">Try searching with a different keyword.</div>
              </div>

              <!-- Loaded Customer List -->
              <q-list v-else class="q-pa-xs text-left">
                <q-item
                  v-for="customer in filteredCustomers"
                  :key="customer.user_id"
                  clickable
                  v-ripple
                  :active="selectedCustomer?.user_id === customer.user_id"
                  active-class="active-customer-item"
                  @click="selectCustomer(customer)"
                  class="customer-item q-my-xs rounded-borders text-left"
                >
                  <q-item-section avatar class="min-w-0 q-pr-sm">
                    <q-avatar size="38px" class="shadow-soft" style="border: 1.5px solid rgba(226, 232, 240, 0.8);">
                      <img :src="customer.profile_picture_url || 'https://cdn.quasar.dev/img/avatar.png'">
                    </q-avatar>
                  </q-item-section>
                  
                  <q-item-section class="text-left">
                    <q-item-label class="text-weight-bold" :class="selectedCustomer?.user_id === customer.user_id ? 'text-brand-red' : 'text-slate-800'">
                      {{ customer.full_name }}
                    </q-item-label>
                    <q-item-label caption class="text-slate-500 row items-center q-mt-xs font-medium">
                      <q-icon name="call" size="13px" class="q-mr-xs text-slate-400" />
                      {{ customer.phone_number || 'No phone number' }}
                    </q-item-label>
                  </q-item-section>

                  <q-item-section side>
                    <q-icon 
                      name="chevron_right" 
                      size="18px" 
                      :color="selectedCustomer?.user_id === customer.user_id ? 'red-9' : 'grey-4'" 
                      class="transition-ease"
                    />
                  </q-item-section>
                </q-item>
              </q-list>
            </q-card-section>
          </q-card>
        </div>

        <!-- ================= RIGHT COLUMN: CUSTOMER ORDERS ================= -->
        <div class="col-12 col-md-8">
          
          <!-- MOBILE VIEW (When Order is Selected) -->
          <div v-if="selectedOrder && $q.screen.lt.md" class="mobile-details-wrapper full-width">
            <OrderDetails 
              :orderId="selectedOrder.order_id" 
              isEmbedded 
              @back="selectedOrder = null" 
            />
          </div>

          <!-- DESKTOP VIEW OR ORDERS LIST MODE -->
          <q-card v-else class="premium-glass-card h-full flex column">
            
            <!-- Panel Header -->
            <q-card-section v-if="!selectedOrder" class="q-pa-md border-bottom-light">
              <div 
                class="row items-center justify-between no-wrap q-mb-sm"
                :class="{ 'mobile-selected-center': selectedCustomer && $q.screen.lt.md }"
              >
                <div class="row items-center no-wrap col min-w-0" :class="{ 'justify-center': selectedCustomer && $q.screen.lt.md }">
                  <span class="header-accent-red q-mr-sm"></span>
                  <span class="text-weight-bold text-slate-800 text-subtitle1 ellipsis">
                    {{ selectedCustomer ? `${selectedCustomer.full_name}'s Orders` : 'Order History' }}
                  </span>
                </div>
                
                <!-- Desktop Export Button -->
                <div v-if="!$q.screen.lt.md" class="row items-center q-gutter-x-sm">
                  <q-btn
                    flat
                    no-caps
                    dense
                    icon="download"
                    label="Export Report"
                    class="export-report-btn q-px-md q-py-xs"
                    :disable="!selectedCustomer || filteredCustomerOrders.length === 0 || ordersLoading"
                    :loading="isExporting"
                    @click="exportCustomerOrdersPDF"
                  />
                </div>
              </div>

              <!-- Filter & Search Toolbar (Desktop: 2 cols | Mobile: ONE LINE Search + Filter + Export) -->
              <div v-if="selectedCustomer" class="q-pt-xs">
                <!-- Desktop Layout -->
                <div v-if="!$q.screen.lt.md" class="row items-center q-col-gutter-sm">
                  <div class="col-12 col-sm-7">
                    <q-input 
                      v-model="orderSearch" 
                      outlined 
                      dense 
                      class="custom-glass-input" 
                      placeholder="Search by Order ID..." 
                      hide-bottom-space
                    >
                      <template v-slot:prepend>
                        <q-icon name="search" size="18px" color="blue-grey-4" />
                      </template>
                      <template v-if="orderSearch" v-slot:append>
                        <q-icon name="close" size="16px" class="cursor-pointer text-slate-400" @click="orderSearch = ''" />
                      </template>
                    </q-input>
                  </div>

                  <div class="col-12 col-sm-5">
                    <q-select
                      v-model="selectedStatusFilter"
                      :options="statusFilterOptions"
                      emit-value
                      map-options
                      outlined
                      dense
                      class="custom-glass-input"
                      hide-bottom-space
                    >
                      <template v-slot:prepend>
                        <q-icon name="filter_list" size="18px" color="blue-grey-4" />
                      </template>
                    </q-select>
                  </div>
                </div>

                <!-- Mobile Layout: ONE LINE (Search + Filter Pill Button + Export) -->
                <div v-else class="row items-center no-wrap q-gutter-x-xs full-width">
                  <!-- Compact Search Input -->
                  <q-input 
                    v-model="orderSearch" 
                    outlined 
                    dense 
                    class="custom-glass-input col" 
                    placeholder="Search Order ID..." 
                    hide-bottom-space
                  >
                    <template v-slot:prepend>
                      <q-icon name="search" size="16px" color="blue-grey-4" />
                    </template>
                    <template v-if="orderSearch" v-slot:append>
                      <q-icon name="close" size="14px" class="cursor-pointer text-slate-400" @click="orderSearch = ''" />
                    </template>
                  </q-input>

                  <!-- Filter Menu Button shows 'Filter' instead of 'All' -->
                  <q-btn 
                    outline 
                    dense 
                    color="blue-grey-7" 
                    class="btn-mobile-toolbar bg-white flex-shrink-0"
                    no-caps
                  >
                    <q-icon name="filter_list" size="18px" />
                    <span class="q-ml-xs text-caption text-weight-bold">{{ getShortStatusLabel(selectedStatusFilter) }}</span>
                    <q-menu class="premium-dropdown-list shadow-4" auto-close anchor="bottom right" self="top right">
                      <q-list style="min-width: 170px;">
                        <q-item 
                          v-for="opt in statusFilterOptions" 
                          :key="opt.value" 
                          clickable 
                          @click="selectedStatusFilter = opt.value"
                          :class="{ 'bg-red-50 text-brand-red text-weight-bold': selectedStatusFilter === opt.value }"
                        >
                          <q-item-section>{{ opt.label }}</q-item-section>
                        </q-item>
                      </q-list>
                    </q-menu>
                  </q-btn>

                  <!-- Mobile Export PDF Action -->
                  <q-btn
                    outline
                    dense
                    color="red-9"
                    class="btn-mobile-toolbar bg-white flex-shrink-0"
                    icon="download"
                    :disable="!selectedCustomer || filteredCustomerOrders.length === 0 || ordersLoading"
                    :loading="isExporting"
                    @click="exportCustomerOrdersPDF"
                  >
                    <q-tooltip>Export PDF</q-tooltip>
                  </q-btn>
                </div>
              </div>
            </q-card-section>

            <!-- DESKTOP Embedded Order Details -->
            <div v-if="selectedOrder" class="flex-1 scroll q-pa-md">
              <OrderDetails 
                :orderId="selectedOrder.order_id" 
                isEmbedded 
                @back="selectedOrder = null" 
              />
            </div>

            <!-- Orders Table Mode -->
            <div v-else class="flex-1 flex column">
              <q-table
                flat
                class="custom-premium-table flex-1"
                :rows="ordersLoading ? skeletonOrders : filteredCustomerOrders"
                :columns="columns"
                row-key="order_id"
                hide-bottom
                :pagination="{ rowsPerPage: 10 }"
                @row-click="onRowClick"
                :grid="$q.screen.lt.md"
              >
                <!-- EMPTY STATE -->
                <template #no-data>
                  <div v-if="!ordersLoading" class="full-width column flex-center q-pa-xl text-center">
                    <div class="empty-icon-box q-mb-md">
                      <q-icon name="receipt_long" size="40px" color="blue-grey-3" />
                    </div>
                    <div class="text-subtitle1 text-weight-bold text-slate-800">
                      {{ selectedCustomer ? (customerOrders.length === 0 ? 'No orders recorded' : 'No matching orders found') : 'No Customer Selected' }}
                    </div>
                    <div class="text-caption text-slate-500 q-mt-xs font-medium" style="max-width: 320px;">
                      {{ selectedCustomer ? (customerOrders.length === 0 ? 'This customer has not placed any orders yet.' : 'Try changing your search query or status filter.') : 'Pick a customer from the directory to review their order history and details.' }}
                    </div>
                  </div>
                </template>

                <!-- DESKTOP ORDER ID -->
                <template #body-cell-order_id="props">
                  <q-td :props="props">
                    <q-skeleton v-if="ordersLoading" type="rect" width="60px" height="22px" style="border-radius: 6px;" />
                    <span v-else class="order-id-badge">
                      #{{ props.row.order_id }}
                    </span>
                  </q-td>
                </template>

                <!-- DESKTOP DATE -->
                <template #body-cell-date="props">
                  <q-td :props="props">
                    <q-skeleton v-if="ordersLoading" type="text" width="130px" height="20px" />
                    <span v-else class="text-slate-600 font-medium">{{ formatDate(props.row.created_at) }}</span>
                  </q-td>
                </template>

                <!-- DESKTOP PRICE -->
                <template #body-cell-price="props">
                  <q-td :props="props">
                    <q-skeleton v-if="ordersLoading" type="text" width="80px" height="20px" />
                    <span v-else class="text-slate-800 price-regular">
                      ₱{{ formatNumber(props.row.total_amount) }}
                    </span>
                  </q-td>
                </template>

                <!-- DESKTOP STATUS BADGE -->
                <template #body-cell-status="props">
                  <q-td :props="props">
                    <q-skeleton v-if="ordersLoading" type="rect" width="94px" height="26px" style="border-radius: 7px;" />
                    <q-chip 
                      v-else
                      dense
                      square
                      :color="getStatusColor(props.row.status)" 
                      text-color="white" 
                      class="status-box-chip q-px-md"
                    >
                      {{ formatStatus(props.row.status) }}
                    </q-chip>
                  </q-td>
                </template>

                <!-- DESKTOP ACTION -->
                <template #body-cell-action="props">
                  <q-td :props="props" class="text-right">
                    <q-skeleton v-if="ordersLoading" type="QBtn" size="sm" class="float-right" />
                    <q-btn v-else flat round dense icon="chevron_right" color="blue-grey-4" class="hover-text-dark" @click.stop="goToOrder(props.row)" />
                  </q-td>
                </template>

                <!-- MOBILE GRID FORMATTER -->
                <template v-slot:item="props">
                  <div class="col-12 q-pa-sm">
                    <!-- Mobile Skeleton -->
                    <q-card v-if="ordersLoading" flat class="bg-white border-slate-light shadow-soft rounded-borders q-pa-md">
                      <div class="row items-center justify-between q-mb-sm">
                        <q-skeleton type="rect" width="85px" height="24px" style="border-radius: 6px;" />
                        <q-skeleton type="rect" width="85px" height="24px" style="border-radius: 7px;" />
                      </div>
                      <q-separator color="grey-2" class="q-my-sm" />
                      <div class="row items-center justify-between text-caption q-mt-md q-pt-xs">
                        <q-skeleton type="text" width="100px" height="18px" />
                        <q-skeleton type="text" width="70px" height="18px" />
                      </div>
                    </q-card>

                    <!-- Mobile Loaded Card -->
                    <q-card v-else flat class="bg-white border-slate-light shadow-soft cursor-pointer rounded-borders q-pa-md" @click="goToOrder(props.row)">
                      <div class="row items-center justify-between q-mb-xs">
                        <span class="order-id-badge" style="font-size: 13px; padding: 4px 10px;">
                          Order #{{ props.row.order_id }}
                        </span>
                        
                        <q-chip 
                          dense
                          square
                          :color="getStatusColor(props.row.status)" 
                          text-color="white" 
                          class="status-box-chip q-ma-none"
                          style="font-size: 11.5px; height: 26px; padding: 0 12px;"
                        >
                          {{ formatStatus(props.row.status) }}
                        </q-chip>
                      </div>
                      
                      <q-separator color="grey-2" class="q-my-sm" />

                      <div class="row items-center justify-between text-caption text-slate-500 q-mt-md q-pt-xs">
                        <span class="text-slate-600 font-medium">{{ formatDate(props.row.created_at) }}</span>
                        <div class="row items-center">
                          <span class="text-slate-800 price-regular text-subtitle2 q-mr-xs">₱{{ formatNumber(props.row.total_amount) }}</span>
                          <q-icon name="chevron_right" size="18px" color="blue-grey-4" />
                        </div>
                      </div>
                    </q-card>
                  </div>
                </template>
              </q-table>
            </div>

          </q-card>
        </div>

      </div>
    </div>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { api } from '@/boot/axios'
import { useQuasar } from 'quasar'
import OrderDetails from './OrderDetails.vue'

const $q = useQuasar()

const customerSearch = ref('')
const orderSearch = ref('')
const selectedStatusFilter = ref('all')

const customers = ref([])
const selectedCustomer = ref(null)
const selectedOrder = ref(null)
const customerOrders = ref([])

const customersLoading = ref(true)
const ordersLoading = ref(false)
const isExporting = ref(false)

const skeletonOrders = Array.from({ length: 6 }, (_, index) => ({
  order_id: `skeleton-${index}`
}))

const statusFilterOptions = [
  { label: 'All Statuses', value: 'all' },
  { label: 'Placed', value: 'placed' },
  { label: 'Preparing', value: 'preparing' },
  { label: 'Ready for Pickup', value: 'ready_for_pickup' },
  { label: 'Picked Up', value: 'picked_up' },
  { label: 'Cancelled', value: 'cancelled' }
]

const columns = [
  { name: 'order_id', label: 'ORDER ID', field: 'order_id', align: 'left', sortable: true },
  { name: 'date', label: 'DATE', field: row => formatDate(row.created_at), align: 'left', sortable: true },
  { name: 'price', label: 'PRICE', field: row => row.total_amount, align: 'left', sortable: true },
  { name: 'status', label: 'STATUS', field: row => formatStatus(row.status), align: 'left' },
  { name: 'action', label: '', field: 'action', align: 'right' }
]

const filteredCustomers = computed(() => {
  if (!customerSearch.value) return customers.value
  const needle = customerSearch.value.toLowerCase()
  return customers.value.filter(c => (c.full_name || '').toLowerCase().includes(needle))
})

const filteredCustomerOrders = computed(() => {
  return customerOrders.value.filter(order => {
    const matchesSearch = !orderSearch.value || String(order.order_id).toLowerCase().includes(orderSearch.value.trim().toLowerCase().replace('#', ''))
    const matchesStatus = selectedStatusFilter.value === 'all' || String(order.status).toLowerCase() === selectedStatusFilter.value.toLowerCase()
    return matchesSearch && matchesStatus
  })
})

// Ensures mobile pill displays "Filter" by default instead of "All"
const getShortStatusLabel = (val) => {
  if (!val || val === 'all') return 'Filter'
  if (val === 'ready_for_pickup') return 'Ready'
  if (val === 'picked_up') return 'Picked'
  return formatStatus(val)
}

const getStatusColor = (status) => {
  switch (String(status).toLowerCase()) {
    case 'placed': return 'blue-6'
    case 'preparing': return 'purple-5'
    case 'ready_for_pickup': return 'orange-6'
    case 'picked_up': return 'green-6'
    case 'cancelled': return 'red-6'
    default: return 'grey-6'
  }
}

const formatStatus = (status) => {
  if (!status) return ''
  return status.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ')
}

const formatNumber = (num) => Number(num || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })

const formatDate = (dateString) => {
  if (!dateString) return ''
  const d = new Date(dateString)
  return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit', hour12: true })
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

const selectCustomer = async (customer) => {
  selectedCustomer.value = customer
  selectedOrder.value = null
  customerOrders.value = []
  orderSearch.value = ''
  selectedStatusFilter.value = 'all'
  ordersLoading.value = true
  try {
    const res = await api.get(`/vendor/customers/${customer.user_id}/orders`)
    customerOrders.value = res.data || []
  } catch (error) {
    console.error('Failed to load customer orders', error)
  } finally {
    ordersLoading.value = false
  }
}

const exportCustomerOrdersPDF = async () => {
  if (!selectedCustomer.value || filteredCustomerOrders.value.length === 0) return

  const customerName = (selectedCustomer.value.full_name || 'Customer').replace(/[^a-zA-Z0-9]/g, '_')
  const dateStr = new Date().toISOString().split('T')[0]

  try {
    isExporting.value = true
    
    const response = await api.get(`/vendor/customers/${selectedCustomer.value.user_id}/orders/export`, {
      responseType: 'blob'
    })
    
    const blob = new Blob([response.data], { type: 'application/pdf' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `Customer_Orders_${customerName}_${dateStr}.pdf`)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    setTimeout(() => window.URL.revokeObjectURL(url), 1000)
    
    $q.notify({
      type: 'positive',
      message: 'Customer order report downloaded successfully',
      position: 'top-right'
    })
  } catch (error) {
    try {
      const fallbackRes = await api.get('/vendor/orders/export', {
        params: { customer_id: selectedCustomer.value.user_id },
        responseType: 'blob'
      })
      const blob = new Blob([fallbackRes.data], { type: 'application/pdf' })
      const url = window.URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', `Customer_Orders_${customerName}_${dateStr}.pdf`)
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      setTimeout(() => window.URL.revokeObjectURL(url), 1000)
    } catch (err) {
      console.error('PDF Export failed:', err)
      $q.notify({
        type: 'negative',
        message: 'Failed to download PDF report. Please try again.',
        position: 'top-right'
      })
    }
  } finally {
    isExporting.value = false
  }
}

const goToOrder = (row) => {
  if (ordersLoading.value) return
  selectedOrder.value = row
  if ($q.screen.lt.md) {
    nextTick(() => {
      window.scrollTo({ top: 0, behavior: 'smooth' })
    })
  }
}

const onRowClick = (evt, row) => {
  if (ordersLoading.value) return
  goToOrder(row)
}

onMounted(() => {
  fetchCustomers()
})
</script>

<style scoped>
/* Page Layout */
.vendor-page {
  padding: 32px 24px;
  background-color: #f8fafc;
  min-height: 100vh;
}
.page-container {
  max-width: 1300px;
  margin: 0 auto;
}

/* Regular Font Weight for Prices */
.price-regular {
  font-weight: 500 !important;
}

/* Mobile Details Viewport Escape */
.mobile-details-wrapper {
  margin: 0;
  padding: 0;
  width: 100%;
}

/* Background Ambient Glows */
.bg-glow {
  position: absolute;
  width: 500px;
  height: 500px;
  border-radius: 50%;
  filter: blur(140px);
  z-index: 0;
  opacity: 0.15; 
  pointer-events: none;
}
.bg-glow-primary {
  top: -50px;
  left: -50px;
  background: radial-gradient(circle, rgba(185, 28, 28, 0.4) 0%, transparent 70%); 
}
.bg-glow-secondary {
  bottom: 100px;
  right: -50px;
  background: radial-gradient(circle, rgba(185, 28, 28, 0.3) 0%, transparent 70%); 
}

/* Typography & Layout Utilities */
.tracking-tight { letter-spacing: -0.02em; }
.leading-tight { line-height: 1.2; }
.text-brand-red { color: #B91C1C !important; }
.text-slate-800 { color: #1e293b; }
.text-slate-700 { color: #334155; }
.text-slate-600 { color: #475569; }
.text-slate-500 { color: #64748b; }
.text-slate-400 { color: #94a3b8; }
.font-medium { font-weight: 500; }
.min-w-0 { min-width: 0 !important; }
.h-full-container { height: calc(100vh - 180px); min-height: 600px; }
.h-full { height: 100%; }
.flex-1 { flex: 1; }

/* Structural Card Styling */
.premium-glass-card {
  background: rgba(255, 255, 255, 0.98);
  border: 1px solid rgba(226, 232, 240, 0.8);
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(15, 23, 42, 0.04);
  overflow: hidden;
}

.glass-icon-box {
  background: rgba(255, 255, 255, 0.9);
  border: 1px solid rgba(226, 232, 240, 0.8);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 12px rgba(185, 28, 28, 0.08);
}

.empty-icon-box {
  width: 56px;
  height: 56px;
  border-radius: 16px;
  background: #f1f5f9;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

/* Standalone Header Accent Line */
.header-accent-red {
  display: inline-block;
  width: 4px;
  height: 18px;
  background: #b91c1c;
  border-radius: 4px;
  flex-shrink: 0;
}

/* Outlined Red Export Button */
.export-report-btn {
  background-color: #ffffff;
  color: #b91c1c !important;
  border: 1.5px solid #b91c1c;
  border-radius: 8px;
  font-weight: 700;
  font-size: 13px;
  letter-spacing: -0.01em;
  transition: all 0.2s ease;
}

.export-report-btn:hover:not(:disabled) {
  background-color: #fef2f2;
}

.export-report-btn:disabled {
  opacity: 0.45 !important;
  border-color: #cbd5e1 !important;
  color: #94a3b8 !important;
}

/* Compact Mobile Toolbar Buttons */
.btn-mobile-toolbar {
  height: 38px !important;
  border-radius: 8px !important;
  border: 1px solid #e2e8f0 !important;
  padding: 0 10px !important;
}

/* Order ID Badge */
.order-id-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 4px 10px;
  border-radius: 6px;
  background-color: #fff1f2;
  border: 1px solid #fecdd3;
  color: #e11d48;
  font-weight: 700;
  font-size: 13px;
  line-height: 1;
}

/* Soft Box Status Chip */
.status-box-chip {
  border-radius: 7px !important;
  font-size: 12.5px !important;
  font-weight: 700 !important;
  min-height: 28px;
  height: 28px;
  border: none !important;
  box-shadow: none !important;
  letter-spacing: -0.01em;
}

/* Inputs */
.custom-glass-input :deep(.q-field__control) {
  background: #f8fafc;
  border-radius: 8px;
  height: 38px;
  transition: all 0.2s ease;
}
.custom-glass-input :deep(.q-field__control:before) { border: 1px solid #e2e8f0; }
.custom-glass-input :deep(.q-field__control:hover) { background: #ffffff; }
.custom-glass-input :deep(.q-field--focused .q-field__control) {
  background: #ffffff;
  border-color: #B91C1C;
  box-shadow: 0 0 0 2px rgba(185, 28, 28, 0.1);
}

/* Customer Directory Items */
.customer-item {
  border-radius: 10px;
  padding: 10px 12px;
  transition: all 0.2s ease;
}
.customer-item:hover {
  background: #f1f5f9;
}
.active-customer-item {
  background: #fef2f2 !important;
  border-left: 3px solid #B91C1C;
}

/* Table Styling */
:deep(.custom-premium-table thead tr th) {
  background: #ffffff;
  font-weight: 700;
  color: #64748b;
  font-size: 11px;
  letter-spacing: 0.05em;
  padding: 16px 20px;
  border-bottom: 1px solid #f1f5f9;
}
:deep(.custom-premium-table tbody td) {
  padding: 16px 20px;
  border-bottom: 1px solid #f8fafc;
  cursor: pointer;
  transition: all 0.2s ease;
}
:deep(.custom-premium-table tbody tr:hover td) {
  background: #f8fafc;
}

/* Borders & Utility */
.border-bottom-light { border-bottom: 1px solid #e2e8f0; }
.border-top-light { border-top: 1px solid #e2e8f0; }
.border-slate-light { border: 1px solid #e2e8f0; }
.shadow-soft { box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04); }
.transition-ease { transition: all 0.25s ease; }
.hover-text-dark:hover { color: #0f172a !important; }

@media (max-width: 767px) {
  .vendor-page.mobile-page-padding { 
    padding: 12px 12px 32px 12px !important; 
  }
  .desktop-only { display: none !important; }
  .mobile-selected-center {
    justify-content: center !important;
    text-align: center !important;
  }
}
</style>