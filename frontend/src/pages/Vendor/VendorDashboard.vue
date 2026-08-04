<template>
  <q-page class="vendor-page">
    <div v-if="checkingAccess" class="checking-access">
      <q-spinner color="primary" size="32px" />
    </div>

    <div v-else class="page-container">
      <div class="welcome-banner premium-glass-card q-mb-xl q-pa-lg row items-center justify-between">
        <div>
          <h1 class="text-h4 text-weight-bolder text-dark q-ma-none">Welcome back, {{ userName }}</h1>
          <p class="text-subtitle1 text-grey-7 q-mt-sm q-mb-none">Here's what's happening with your store today.</p>
        </div>
        <q-btn label="View Live Store" unelevated color="dark" icon="visibility" class="btn-premium q-px-md" no-caps />
      </div>

      <!-- ================= TOP METRICS ================= -->
      <div class="row q-col-gutter-lg q-mb-xl">
        <div class="col-12 col-sm-6 col-md-3">
          <q-card class="premium-glass-card metric-card">
            <q-card-section>
              <div class="row items-center q-mb-md">
                <div class="icon-premium-box bg-blue-50 border-blue-light text-blue-7">
                  <q-icon name="shopping_cart" size="24px" />
                </div>
                <div class="text-subtitle2 text-weight-bold text-grey-7 q-ml-sm text-uppercase">Placed Orders</div>
              </div>
              <div class="text-h3 text-weight-bold text-dark">{{ stats.placed_orders }}</div>
            </q-card-section>
          </q-card>
        </div>

        <div class="col-12 col-sm-6 col-md-3">
          <q-card class="premium-glass-card metric-card">
            <q-card-section>
              <div class="row items-center q-mb-md">
                <div class="icon-premium-box bg-amber-50 border-amber-light text-amber-7">
                  <q-icon name="soup_kitchen" size="24px" />
                </div>
                <div class="text-subtitle2 text-weight-bold text-grey-7 q-ml-sm text-uppercase">Preparing Orders</div>
              </div>
              <div class="text-h3 text-weight-bold text-dark">{{ stats.preparing_orders }}</div>
            </q-card-section>
          </q-card>
        </div>

        <div class="col-12 col-sm-6 col-md-3">
          <q-card class="premium-glass-card metric-card">
            <q-card-section>
              <div class="row items-center q-mb-md">
                <div class="icon-premium-box bg-green-50 border-green-light text-green-7">
                  <q-icon name="check_circle" size="24px" />
                </div>
                <div class="text-subtitle2 text-weight-bold text-grey-7 q-ml-sm text-uppercase">Completed Orders</div>
              </div>
              <div class="text-h3 text-weight-bold text-dark">{{ stats.completed_orders }}</div>
            </q-card-section>
          </q-card>
        </div>

        <div class="col-12 col-sm-6 col-md-3">
          <q-card class="premium-glass-card metric-card">
            <q-card-section>
              <div class="row items-center q-mb-md">
                <div class="icon-premium-box bg-red-50 border-red-light text-red-7">
                  <q-icon name="cancel" size="24px" />
                </div>
                <div class="text-subtitle2 text-weight-bold text-grey-7 q-ml-sm text-uppercase">Cancelled Orders</div>
              </div>
              <div class="text-h3 text-weight-bold text-dark">{{ stats.cancelled_orders }}</div>
            </q-card-section>
          </q-card>
        </div>
      </div>

      <!-- ================= RECENT ORDERS TABLE ================= -->
      <q-card class="premium-glass-card q-mb-xl">
        <q-card-section class="panel-header row items-center justify-between q-pa-lg">
          <div class="text-h6 text-weight-bold text-dark row items-center">
            <div class="header-accent-red q-mr-md"></div>
            Recent Customer Order Requests
          </div>
          <q-btn label="View All Orders" flat color="primary" no-caps />
        </q-card-section>
        
        <q-table
          flat
          class="custom-premium-table"
          :rows="recentOrders"
          :columns="orderColumns"
          row-key="id"
          hide-bottom
          :pagination="{ rowsPerPage: 5 }"
        >
          <template #body-cell-customer="props">
            <q-td :props="props">
              <div class="row items-center no-wrap">
                <q-avatar size="32px" class="q-mr-sm">
                  <img :src="props.row.avatar" v-if="props.row.avatar" />
                  <q-icon name="person" color="grey-7" size="20px" v-else />
                </q-avatar>
                <div class="text-weight-bold">{{ props.row.customer }}</div>
              </div>
            </q-td>
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
              <q-btn flat round dense icon="more_vert" color="grey-7" :loading="updatingOrderId === props.row.id">
                <q-menu anchor="bottom right" self="top right">
                  <q-list style="min-width: 150px">
                    <q-item clickable v-close-popup @click="goToOrder(props.row.id)">
                      <q-item-section avatar class="min-w-0 q-pr-sm">
                        <q-icon name="visibility" size="20px" color="primary" />
                      </q-item-section>
                      <q-item-section>View Details</q-item-section>
                    </q-item>
                    
                    <q-separator />
                    
                    <q-item clickable>
                      <q-item-section avatar class="min-w-0 q-pr-sm">
                        <q-icon name="update" size="20px" color="dark" />
                      </q-item-section>
                      <q-item-section>Update Status</q-item-section>
                      <q-item-section side>
                        <q-icon name="keyboard_arrow_right" />
                      </q-item-section>
                      
                      <q-menu anchor="top end" self="top start">
                        <q-list>
                          <q-item clickable v-close-popup @click="updateStatus(props.row.id, 'preparing')" v-if="['placed'].includes(props.row.status.toLowerCase())">
                            <q-item-section>Preparing</q-item-section>
                          </q-item>
                          <q-item clickable v-close-popup @click="updateStatus(props.row.id, 'ready_for_pickup')" v-if="['placed', 'preparing'].includes(props.row.status.toLowerCase())">
                            <q-item-section>Ready for Pickup</q-item-section>
                          </q-item>
                          <q-item clickable v-close-popup @click="updateStatus(props.row.id, 'picked_up')" v-if="['ready_for_pickup'].includes(props.row.status.toLowerCase())">
                            <q-item-section>Picked Up</q-item-section>
                          </q-item>
                          <q-item clickable v-close-popup @click="promptCancelOrder(props.row.id)" v-if="!['picked_up', 'cancelled'].includes(props.row.status.toLowerCase())">
                            <q-item-section class="text-red">Cancelled</q-item-section>
                          </q-item>
                        </q-list>
                      </q-menu>
                    </q-item>
                  </q-list>
                </q-menu>
              </q-btn>
            </q-td>
          </template>
        </q-table>
      </q-card>

      <!-- ================= REVENUE & ML BLUEPRINT ================= -->
      <div class="row q-col-gutter-lg">
        
        <!-- Revenue Chart -->
        <div class="col-12 col-md-7">
          <q-card class="premium-glass-card h-full">
            <q-card-section class="row items-center justify-between q-pa-lg">
              <div class="text-h6 text-weight-bold text-dark">Revenue Overview</div>
              <q-btn-group flat class="bg-grey-2 border-radius-8">
                <q-btn label="Daily" unelevated color="white" class="text-dark border-radius-8 shadow-1" no-caps size="sm" />
                <q-btn label="Weekly" flat color="grey-7" no-caps size="sm" />
                <q-btn label="Monthly" flat color="grey-7" no-caps size="sm" />
              </q-btn-group>
            </q-card-section>
            <q-card-section class="flex flex-center" style="height: 250px;">
              <div class="text-grey-5">Line Chart Placeholder</div>
            </q-card-section>
          </q-card>
        </div>

        <!-- ML Blueprint Container -->
        <div class="col-12 col-md-5">
          <q-card class="premium-glass-card ml-blueprint-card h-full bg-gradient-dark text-white">
            <q-card-section class="q-pa-lg">
              <div class="row items-center q-mb-md">
                <q-icon name="auto_graph" size="28px" color="amber-4" class="q-mr-sm" />
                <div class="text-h6 text-weight-bold">Predictive Insights & Demand Forecasting</div>
              </div>
              
              <p class="text-grey-4 text-body2 q-mb-lg">
                Unlock AI-powered insights to optimize your inventory and predict upcoming sales trends based on historical data.
              </p>

              <div class="ml-container-glass flex flex-center" style="height: 140px; border-radius: 12px;">
                <!-- RANDOM FOREST ML INTEGRATION BLUEPRINT: This container will house the predictive charts and inventory demand suggestions driven by the Python/Flask ML microservice in the final sprint. Ensure data props here remain isolated and reactive. -->
                <div class="text-center">
                  <q-spinner-dots size="40px" color="amber-4" />
                  <div class="text-caption text-amber-2 q-mt-sm font-monospace">Predictive modeling loading...</div>
                </div>
              </div>
            </q-card-section>
          </q-card>
        </div>

      </div>

    </div>

    <!-- Cancellation Dialog -->
    <q-dialog v-model="showCancelDialog" persistent>
      <q-card style="min-width: 350px; border-radius: 12px;" class="premium-glass-card">
        <q-card-section>
          <div class="text-h6 text-weight-bold text-dark row items-center">
            <q-icon name="warning" color="red" size="24px" class="q-mr-sm" />
            Cancel Order
          </div>
        </q-card-section>

        <q-card-section class="q-pt-none">
          <q-checkbox v-model="cancelReasonOutOfStock" label="Item out of stock" class="q-mb-md text-dark" color="red-8" />
          <q-input
            v-model="cancelReasonText"
            type="textarea"
            label="Cancellation Reason (Required)"
            outlined
            color="red-8"
            autofocus
            :rules="[val => !!val || 'Reason is required']"
          />
        </q-card-section>

        <q-card-actions align="right" class="text-primary q-pa-md">
          <q-btn flat label="Back" color="grey-7" v-close-popup no-caps />
          <q-btn flat label="Confirm Cancellation" color="red-8" @click="confirmCancelOrder" :loading="updatingOrderId !== null" no-caps class="text-weight-bold" />
        </q-card-actions>
      </q-card>
    </q-dialog>

  </q-page>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '@/boot/axios'
import { useAuth } from '@/composables/useAuth'
import { useQuasar } from 'quasar'

const router = useRouter()
const $q = useQuasar()
const { logout } = useAuth()
const checkingAccess = ref(true)
const userName = ref('Vendor')
const updatingOrderId = ref(null)

const showCancelDialog = ref(false)
const cancelReasonOutOfStock = ref(false)
const cancelReasonText = ref('')
const cancellingOrderId = ref(null)

watch(cancelReasonOutOfStock, (val) => {
  if (val) {
    cancelReasonText.value = 'Item out of stock'
  } else if (cancelReasonText.value === 'Item out of stock') {
    cancelReasonText.value = ''
  }
})

const promptCancelOrder = (id) => {
  cancellingOrderId.value = id
  cancelReasonOutOfStock.value = false
  cancelReasonText.value = ''
  showCancelDialog.value = true
}

const confirmCancelOrder = () => {
  if (!cancelReasonText.value) {
    $q.notify({ type: 'warning', message: 'Please provide a cancellation reason.' })
    return
  }
  updateStatus(cancellingOrderId.value, 'cancelled', cancelReasonText.value)
}

const orderColumns = [
  { name: 'id', label: 'Order ID', field: 'id', align: 'left', sortable: true },
  { name: 'date', label: 'Date', field: 'date', align: 'left', sortable: true },
  { name: 'customer', label: 'Customer', field: 'customer', align: 'left' },
  { name: 'price', label: 'Price', field: 'price', align: 'left', sortable: true },
  { name: 'status', label: 'Status', field: 'status', align: 'left' },
  { name: 'action', label: '', field: 'action', align: 'right' }
]

const recentOrders = ref([])

const stats = ref({
  placed_orders: 0,
  preparing_orders: 0,
  completed_orders: 0,
  cancelled_orders: 0
})

const getStatusColor = (status) => {
  switch (status.toLowerCase()) {
    case 'placed': return 'blue-6'
    case 'preparing': return 'amber-7'
    case 'completed': return 'green-6'
    case 'cancelled': return 'red-6'
    default: return 'grey-6'
  }
}

const fetchDashboardData = async () => {
  try {
    const statsRes = await api.get('/vendor/stats')
    if (statsRes.data) {
      stats.value.placed_orders = statsRes.data.placed_orders || 0
      stats.value.preparing_orders = statsRes.data.preparing_orders || 0
      stats.value.completed_orders = statsRes.data.completed_orders || 0
      stats.value.cancelled_orders = statsRes.data.cancelled_orders || 0
      recentOrders.value = statsRes.data.recent_orders || []
    }
  } catch (error) {
    console.error('Failed to fetch dashboard data', error)
  }
}

const goToOrder = (id) => {
  router.push('/vendor/orders/' + id)
}

const updateStatus = async (id, newStatus, reason = null) => {
  try {
    updatingOrderId.value = id
    const payload = { status: newStatus }
    if (reason) payload.cancellation_reason = reason
    await api.patch(`/vendor/orders/${id}/status`, payload)
    $q.notify({ type: 'positive', message: 'Order status updated successfully' })
    showCancelDialog.value = false
    await fetchDashboardData()
  } catch (err) {
    console.error(err.response?.data || err)
    const msg = err.response?.data?.message || err.message || "Unknown error occurred"
    $q.notify({ type: 'negative', message: msg })
  } finally {
    updatingOrderId.value = null
  }
}

onMounted(async () => {
  try {
    const res = await api.get('/user')
    if (res.data && res.data.user) {
      const user = res.data.user
      userName.value = user.full_name ? user.full_name.split(' ')[0] : 'Vendor'
    }
    await fetchDashboardData()
  } catch (error) {
    userName.value = 'Vendor'
  } finally {
    checkingAccess.value = false
  }
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
  height: 100%;
}
.btn-premium {
  border-radius: 8px !important;
  font-weight: 600;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
  transition: all 0.2s ease;
}
.icon-premium-box {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.bg-blue-50 { background-color: #EFF6FF; } .border-blue-light { border: 1px solid #DBEAFE; }
.bg-amber-50 { background-color: #FFFBEB; } .border-amber-light { border: 1px solid #FEF3C7; }
.bg-green-50 { background-color: #F0FDF4; } .border-green-light { border: 1px solid #DCFCE7; }
.bg-red-50 { background-color: #FEF2F2; } .border-red-light { border: 1px solid #FEE2E2; }

.panel-header {
  background: linear-gradient(90deg, rgba(255,255,255,0.8) 0%, rgba(255,255,255,0.4) 100%);
  border-bottom: 1px solid rgba(226, 232, 240, 0.8);
}
.header-accent-red {
  width: 4px;
  height: 24px;
  background: #B91C1C;
  border-radius: 4px;
  box-shadow: 2px 0 8px rgba(185, 28, 28, 0.3);
}

:deep(.custom-premium-table thead tr th) {
  background: rgba(248, 250, 252, 0.7); backdrop-filter: blur(8px); font-weight: 700;
  color: #64748B; text-transform: uppercase; font-size: 11px; letter-spacing: 0.05em; padding: 16px 20px; border-bottom: 1px solid rgba(226, 232, 240, 0.8);
}
:deep(.custom-premium-table tbody td) {
  padding: 16px 20px; border-bottom: 1px solid rgba(226, 232, 240, 0.5);
}

.border-radius-8 {
  border-radius: 8px;
}

.bg-gradient-dark {
  background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
  border: 1px solid #334155;
  box-shadow: 0 15px 35px rgba(0,0,0,0.2);
}
.ml-container-glass {
  background: rgba(255, 255, 255, 0.05);
  border: 1px dashed rgba(251, 191, 36, 0.4);
}
.font-monospace {
  font-family: 'Courier New', Courier, monospace;
  letter-spacing: 0.05em;
}
</style>
