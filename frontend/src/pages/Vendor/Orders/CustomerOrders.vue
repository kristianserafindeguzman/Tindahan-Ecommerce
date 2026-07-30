<template>
  <q-page class="vendor-page">
    <div class="page-container">
      
      <!-- ================= HEADER AREA ================= -->
      <div class="page-header q-mb-lg row items-center justify-between">
        <div>
          <h1 class="text-h4 text-weight-bold q-ma-none">Customer Orders</h1>
          <p class="text-subtitle1 text-grey-7 q-mt-sm q-mb-none">View order history by specific customers.</p>
        </div>
      </div>

      <div class="row q-col-gutter-lg h-full">
        
        <!-- ================= LEFT COLUMN: CUSTOMERS ================= -->
        <div class="col-12 col-md-4">
          <q-card class="premium-glass-card h-full flex column">
            <q-card-section class="q-pa-md border-bottom">
              <div class="text-h6 text-weight-bold text-dark q-mb-md row items-center">
                <div class="header-accent-red q-mr-sm"></div>
                Customer Directory
              </div>
              <q-input v-model="search" outlined dense class="custom-glass-input" placeholder="Search customer name...">
                <template v-slot:prepend>
                  <q-icon name="search" />
                </template>
              </q-input>
            </q-card-section>
            
            <q-card-section class="q-pa-none scroll" style="flex: 1; max-height: 600px;">
              
              <div v-if="filteredCustomers.length === 0" class="q-pa-xl text-center text-grey-6">
                <q-icon name="group_off" size="36px" class="opacity-50 q-mb-sm" />
                <div>No customers found.</div>
              </div>

              <q-list v-else separator>
                <q-item
                  v-for="customer in filteredCustomers"
                  :key="customer.user_id"
                  clickable
                  v-ripple
                  :active="selectedCustomer?.user_id === customer.user_id"
                  active-class="bg-red-50 text-dark border-left-red"
                  @click="selectCustomer(customer)"
                  class="q-pa-md customer-item"
                >
                  <q-item-section avatar>
                    <q-avatar size="40px">
                      <img :src="customer.profile_picture_url || 'https://cdn.quasar.dev/img/avatar.png'">
                    </q-avatar>
                  </q-item-section>
                  <q-item-section>
                    <q-item-label class="text-weight-bold">{{ customer.full_name }}</q-item-label>
                    <q-item-label caption class="text-grey-7">
                      <q-icon name="phone" size="14px" class="q-mr-xs" />
                      {{ customer.phone_number || 'N/A' }}
                    </q-item-label>
                  </q-item-section>
                  <q-item-section side>
                    <q-icon name="chevron_right" color="grey-5" />
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
                  {{ selectedCustomer ? selectedCustomer.full_name + "'s Orders" : "Select a Customer" }}
                </div>
              </div>
            </q-card-section>

            <q-table
              flat
              class="custom-premium-table flex-1"
              :rows="customerOrders"
              :columns="columns"
              row-key="order_id"
              :loading="ordersLoading"
              hide-bottom
              :pagination="{ rowsPerPage: 10 }"
              @row-click="onRowClick"
            >
              <template #no-data>
                <div class="full-width row flex-center text-grey-6 q-pa-xl empty-state-glass">
                  <div class="text-center">
                    <q-icon name="receipt_long" size="48px" class="q-mb-md opacity-50" />
                    <div class="text-subtitle1 text-weight-medium">
                      {{ selectedCustomer ? 'No orders for this customer.' : 'Select a customer from the left to view their orders.' }}
                    </div>
                  </div>
                </div>
              </template>

              <template #body-cell-status="props">
                <q-td :props="props">
                  <q-chip size="sm" :color="getStatusColor(props.row.status)" text-color="white" class="text-weight-bold shadow-1">
                    {{ props.row.status }}
                  </q-chip>
                </q-td>
              </template>

              <template #body-cell-action="props">
                <q-td :props="props" class="text-right">
                  <q-btn flat round dense icon="chevron_right" color="grey-7" @click.stop="goToOrder(props.row.order_id)" />
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
import { useRouter } from 'vue-router'
import { api } from '@/boot/axios'

const router = useRouter()
const search = ref('')
const customers = ref([])
const selectedCustomer = ref(null)
const customerOrders = ref([])
const ordersLoading = ref(false)

const columns = [
  { name: 'order_id', label: 'Order ID', field: 'order_id', align: 'left', sortable: true },
  { name: 'date', label: 'Date', field: row => formatDate(row.created_at), align: 'left', sortable: true },
  { name: 'price', label: 'Price (₱)', field: row => formatNumber(row.total_amount), align: 'left', sortable: true },
  { name: 'status', label: 'Status', field: 'status', align: 'left' },
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
    case 'ready for pickup': return 'orange-5'
    case 'picked up': return 'green-6'
    case 'completed': return 'green-6'
    case 'cancelled': return 'red-6'
    default: return 'grey-6'
  }
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

const goToOrder = (id) => {
  router.push('/vendor/orders/' + id)
}

const onRowClick = (evt, row) => {
  goToOrder(row.order_id)
}

onMounted(() => {
  fetchCustomers()
})
</script>

<style scoped>
.vendor-page {
  padding: 24px;
  background: #f8fafc;
  min-height: 100vh;
}
.page-container {
  max-width: 1400px;
  margin: 0 auto;
}
.premium-glass-card {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.8);
  border-radius: 16px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
}
.h-full {
  height: calc(100vh - 150px);
  min-height: 500px;
}
.flex-1 {
  flex: 1;
}
.border-bottom {
  border-bottom: 1px solid rgba(226, 232, 240, 0.8);
}
.header-accent-red {
  width: 4px;
  height: 20px;
  background: #B91C1C;
  border-radius: 4px;
  box-shadow: 2px 0 8px rgba(185, 28, 28, 0.3);
}
.custom-glass-input :deep(.q-field__control) {
  background: rgba(241, 245, 249, 0.6);
  border-radius: 8px;
}
.bg-red-50 {
  background-color: #FEF2F2 !important;
}
.border-left-red {
  border-left: 4px solid #B91C1C;
}
.customer-item {
  transition: all 0.2s ease;
  border-left: 4px solid transparent;
}
.customer-item:hover {
  background: rgba(241, 245, 249, 0.5);
}
.panel-header {
  background: linear-gradient(90deg, rgba(255,255,255,0.8) 0%, rgba(255,255,255,0.4) 100%);
  border-bottom: 1px solid rgba(226, 232, 240, 0.8);
}
:deep(.custom-premium-table thead tr th) {
  background: rgba(248, 250, 252, 0.7); backdrop-filter: blur(8px); font-weight: 700;
  color: #64748B; text-transform: uppercase; font-size: 11px; letter-spacing: 0.05em; padding: 16px 20px; border-bottom: 1px solid rgba(226, 232, 240, 0.8);
}
:deep(.custom-premium-table tbody td) {
  padding: 16px 20px; border-bottom: 1px solid rgba(226, 232, 240, 0.5); cursor: pointer;
}
:deep(.custom-premium-table tbody tr:hover td) {
  background: rgba(241, 245, 249, 0.4);
}
.empty-state-glass {
  background: rgba(248, 250, 252, 0.5);
  border: 1px dashed #E2E8F0;
  border-radius: 12px;
  margin: 16px;
}
</style>
