<template>
  <q-page class="vendor-page relative-position">
    <!-- Subtle Ambient Background Glows (Strict Red, No Pink) -->
    <div class="bg-glow bg-glow-primary"></div>
    <div class="bg-glow bg-glow-secondary"></div>

    <div class="page-container relative-position" style="z-index: 1;">
      
      <!-- ================= HEADER AREA ================= -->
      <div class="page-header q-mb-xl q-mt-sm row items-center justify-between">
        <div class="row items-center">
          <div class="glass-icon-box q-mr-md">
            <q-icon name="people" size="26px" color="red-8" />
          </div>
          <div>
            <h1 class="text-h4 text-weight-bolder text-blue-grey-9 q-ma-none tracking-tight">Customer Orders</h1>
            <p class="text-body1 text-blue-grey-5 q-mt-xs q-mb-none">View order history and details for specific customers.</p>
          </div>
        </div>
      </div>

      <div class="row q-col-gutter-xl h-full-container">
        
        <!-- ================= LEFT COLUMN: CUSTOMERS ================= -->
        <div class="col-12 col-md-4">
          <q-card class="premium-glass-card h-full flex column">
            <q-card-section class="q-pa-lg border-bottom">
              <div class="text-h6 text-weight-bold text-dark q-mb-md row items-center">
                <div class="header-accent-red q-mr-sm"></div>
                Customer Directory
              </div>
              <q-input v-model="search" outlined dense class="custom-glass-input" placeholder="Search customer name..." hide-bottom-space>
                <template v-slot:prepend>
                  <q-icon name="search" color="blue-grey-5" />
                </template>
              </q-input>
            </q-card-section>
            
            <q-card-section class="q-pa-none scroll" style="flex: 1; max-height: 600px; overflow-y: auto;">
              
              <div v-if="filteredCustomers.length === 0" class="full-width row flex-center q-pa-xl">
                <div class="text-center z-top relative-position">
                  <div class="empty-icon-wrapper q-mb-md">
                    <q-icon name="group_off" size="48px" color="blue-grey-3" class="drop-shadow-icon" />
                  </div>
                  <div class="text-subtitle1 text-weight-bold text-blue-grey-8">No customers found</div>
                  <div class="text-caption text-blue-grey-5 q-mt-xs">Try adjusting your search.</div>
                </div>
              </div>

              <q-list v-else separator class="bg-transparent">
                <q-item
                  v-for="customer in filteredCustomers"
                  :key="customer.user_id"
                  clickable
                  v-ripple
                  :active="selectedCustomer?.user_id === customer.user_id"
                  active-class="active-customer-item"
                  @click="selectCustomer(customer)"
                  class="q-pa-md customer-item"
                >
                  <q-item-section avatar>
                    <q-avatar size="42px" class="shadow-1">
                      <img :src="customer.profile_picture_url || 'https://cdn.quasar.dev/img/avatar.png'">
                    </q-avatar>
                  </q-item-section>
                  <q-item-section>
                    <q-item-label class="text-weight-bold text-blue-grey-9">{{ customer.full_name }}</q-item-label>
                    <q-item-label caption class="text-blue-grey-5 row items-center q-mt-xs">
                      <q-icon name="phone" size="14px" class="q-mr-xs" />
                      {{ customer.phone_number || 'N/A' }}
                    </q-item-label>
                  </q-item-section>
                  <q-item-section side>
                    <q-icon name="chevron_right" :color="selectedCustomer?.user_id === customer.user_id ? 'red-8' : 'grey-4'" size="20px" />
                  </q-item-section>
                </q-item>
              </q-list>
            </q-card-section>
          </q-card>
        </div>

        <!-- ================= RIGHT COLUMN: ORDERS ================= -->
        <div class="col-12 col-md-8">
          <q-card class="premium-glass-card h-full flex column">
            <q-card-section class="panel-header q-pa-lg">
              <div class="text-h6 text-weight-bold text-dark row items-center justify-between">
                <div class="row items-center">
                  <div class="header-accent-red q-mr-md"></div>
                  {{ selectedCustomer ? selectedCustomer.full_name + "'s Orders" : "Order History" }}
                </div>
              </div>
            </q-card-section>

            <!-- Conditional Rendering: Details OR Table -->
            <div v-if="selectedOrder" class="flex-1 h-full scroll q-pa-md">
              <OrderDetails :orderId="selectedOrder.order_id" isEmbedded @back="selectedOrder = null" />
            </div>

            <q-table
              v-else
              flat
              class="custom-premium-table bg-transparent flex-1"
              :rows="customerOrders"
              :columns="columns"
              row-key="order_id"
              :loading="ordersLoading"
              hide-bottom
              :pagination="{ rowsPerPage: 10 }"
              @row-click="onRowClick"
            >
              <template #no-data>
                <div class="full-width row flex-center q-pa-xl empty-state-glass">
                  <div class="text-center z-top relative-position">
                    <div class="empty-icon-wrapper q-mb-lg">
                      <q-icon name="receipt_long" size="56px" color="blue-grey-3" class="drop-shadow-icon" />
                    </div>
                    <div class="text-h6 text-weight-bold text-blue-grey-8">
                      {{ selectedCustomer ? 'No orders found' : 'Select a Customer' }}
                    </div>
                    <div class="text-body2 text-blue-grey-5 q-mt-xs">
                      {{ selectedCustomer ? 'This customer has not placed any orders yet.' : 'Choose a customer from the directory to view their history.' }}
                    </div>
                  </div>
                </div>
              </template>

              <template #body-cell-status="props">
                <q-td :props="props">
                  <q-chip 
                    size="sm" 
                    :color="getStatusColor(props.row.status)" 
                    text-color="white" 
                    class="text-weight-bold shadow-1"
                  >
                    {{ formatStatus(props.row.status) }}
                  </q-chip>
                </q-td>
              </template>
              
              <template #body-cell-price="props">
                <q-td :props="props" class="text-weight-bold text-blue-grey-9">
                  ₱{{ formatNumber(props.row.total_amount) }}
                </q-td>
              </template>

              <template #body-cell-action="props">
                <q-td :props="props" class="text-right">
                  <q-btn flat round dense icon="chevron_right" color="grey-6" @click.stop="goToOrder(props.row)" />
                </q-td>
              </template>
            </q-table>
          </q-card>
        </div>

      </div>
    </div>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { api } from '@/boot/axios'
import OrderDetails from './OrderDetails.vue'

const search = ref('')
const customers = ref([])
const selectedCustomer = ref(null)
const selectedOrder = ref(null)
const customerOrders = ref([])
const ordersLoading = ref(false)

const columns = [
  { name: 'order_id', label: 'Order ID', field: 'order_id', align: 'left', sortable: true },
  { name: 'date', label: 'Date', field: row => formatDate(row.created_at), align: 'left', sortable: true },
  { name: 'price', label: 'Price (₱)', field: row => row.total_amount, align: 'left', sortable: true },
  { name: 'status', label: 'Status', field: row => formatStatus(row.status), align: 'left' },
  { name: 'action', label: '', field: 'action', align: 'right' }
]

const filteredCustomers = computed(() => {
  if (!search.value) return customers.value
  const needle = search.value.toLowerCase()
  return customers.value.filter(c => (c.full_name || '').toLowerCase().includes(needle))
})

const getStatusColor = (status) => {
  switch (String(status).toLowerCase()) {
    case 'placed': return 'blue-6'
    case 'preparing': return 'amber-7'
    case 'ready_for_pickup': return 'orange-5'
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
  return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}

const fetchCustomers = async () => {
  try {
    const res = await api.get('/vendor/customers')
    customers.value = res.data || []
  } catch (error) {
    console.error('Failed to load customers', error)
  }
}

const selectCustomer = async (customer) => {
  selectedCustomer.value = customer
  selectedOrder.value = null
  customerOrders.value = []
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

const goToOrder = (row) => {
  selectedOrder.value = row
}

const onRowClick = (evt, row) => {
  goToOrder(row)
}

onMounted(() => {
  fetchCustomers()
})
</script>

<style scoped>
/* Core Page Styling */
.vendor-page {
  padding: 32px 24px;
  background-color: #f8fafc;
  min-height: 100vh;
}
.page-container {
  max-width: 1300px;
  margin: 0 auto;
}

/* Subtle Ambient Glows - STRICT RED, NO PINK */
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

/* Typography Utilities */
.tracking-tight { letter-spacing: -0.02em; }
.leading-tight { line-height: 1.2; }
.h-full-container { height: calc(100vh - 180px); min-height: 600px; }
.h-full { height: 100%; }
.flex-1 { flex: 1; }

/* Beautiful Header Glass Icon Box */
.glass-icon-box {
  width: 48px;
  height: 48px;
  background: rgba(255, 255, 255, 0.7);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.9);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 12px rgba(185, 28, 28, 0.08);
}

/* Clean Glassmorphism Cards */
.premium-glass-card {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.8);
  border-radius: 16px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
}

/* Panel Header */
.panel-header {
  background: linear-gradient(90deg, rgba(255,255,255,0.8) 0%, rgba(255,255,255,0.4) 100%);
  border-bottom: 1px solid rgba(226, 232, 240, 0.8);
}
.border-bottom {
  border-bottom: 1px solid rgba(226, 232, 240, 0.8);
}
.header-accent-red {
  width: 4px;
  height: 24px;
  background: #B91C1C;
  border-radius: 4px;
  box-shadow: 2px 0 8px rgba(185, 28, 28, 0.3);
}

/* Inputs & Form Elements */
.custom-glass-input :deep(.q-field__control) {
  background: rgba(241, 245, 249, 0.6); 
  border-radius: 8px;
  transition: all 0.3s ease;
}
.custom-glass-input :deep(.q-field__control:before) { border: 1px solid rgba(226, 232, 240, 0.8); }
.custom-glass-input :deep(.q-field__control:hover) { background: #ffffff; }
.custom-glass-input :deep(.q-field--focused .q-field__control) {
  background: #ffffff;
  box-shadow: 0 2px 10px rgba(185, 28, 28, 0.06); 
}

/* Directory List Styling */
.customer-item {
  transition: all 0.2s ease;
  border-left: 4px solid transparent;
}
.customer-item:hover {
  background: rgba(241, 245, 249, 0.6);
}
.active-customer-item {
  background-color: #FEF2F2 !important;
  border-left: 4px solid #B91C1C;
}

/* Custom Premium Table Styling */
:deep(.custom-premium-table thead tr th) {
  background: rgba(248, 250, 252, 0.7);
  backdrop-filter: blur(8px);
  font-weight: 700;
  color: #64748B; 
  text-transform: uppercase; 
  font-size: 11px; 
  letter-spacing: 0.05em; 
  padding: 16px 20px; 
  border-bottom: 1px solid rgba(226, 232, 240, 0.8); 
}
:deep(.custom-premium-table tbody td) {
  padding: 16px 20px; 
  border-bottom: 1px solid rgba(226, 232, 240, 0.5); 
  cursor: pointer;
  transition: background 0.2s ease;
}
:deep(.custom-premium-table tbody tr:hover td) {
  background: rgba(241, 245, 249, 0.4);
}

/* Empty State Styling */
.empty-state-glass {
  background: rgba(248, 250, 252, 0.5);
  border: 1px dashed #E2E8F0;
  border-radius: 12px;
  margin: 16px;
  width: calc(100% - 32px);
}
.empty-icon-wrapper {
  position: relative;
  display: inline-flex;
  justify-content: center;
  align-items: center;
}
.drop-shadow-icon { filter: drop-shadow(0 4px 6px rgba(15, 23, 42, 0.05)); opacity: 0.5; }
</style>