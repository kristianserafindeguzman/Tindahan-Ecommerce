<template>
  <q-page class="vendor-page">
    <!-- Decorative Ambient Background Glows -->
    <div class="bg-glow bg-glow-primary"></div>
    <div class="bg-glow bg-glow-secondary"></div>

    <div v-if="checkingAccess" class="checking-access flex flex-center h-full">
      <q-spinner-puff color="red-9" size="60px" thickness="3" />
    </div>

    <div v-else class="page-container">
      
      <!-- ================= DYNAMIC TIME-SYNCED HEADER ================= -->
      <div 
        class="welcome-banner q-mb-md q-pa-lg row items-center justify-between transition-theme"
        :class="headerThemeClass"
        style="border-radius: 16px;"
      >
        <div>
          <div class="row items-center q-mb-sm">
            <q-icon name="today" size="18px" :class="subTextClass" class="q-mr-sm opacity-80" />
            <span class="text-caption text-weight-bold tracking-wide text-uppercase" :class="subTextClass">
              {{ currentDate }}
            </span>
          </div>
          <h1 class="text-h3 text-weight-bolder q-ma-none header-title" :class="headerTextClass">
            {{ timeGreeting }}, <span class="text-weight-black">{{ userName }}</span>
          </h1>
          <p class="text-subtitle1 q-mt-sm q-mb-none opacity-80" :class="subTextClass">
            Here's what's happening with your neighborhood store today.
          </p>
        </div>
        
        <div class="row items-center q-gutter-md">
          <div class="store-status-badge row items-center q-px-md q-py-sm border-radius-8 bg-glass shadow-soft">
            <div class="status-dot q-mr-sm"></div>
            <span class="text-weight-bold text-caption" :class="headerTextClass">Store is Open</span>
          </div>
          <q-btn 
            label="View Live Store" 
            unelevated 
            color="white" 
            text-color="dark" 
            icon="storefront" 
            class="btn-premium q-px-lg shadow-1" 
            no-caps 
          />
        </div>
      </div>

      <!-- ================= TOP METRICS (Solid Clean Design) ================= -->
      <div class="row q-col-gutter-lg q-mb-xl">
        <div class="col-12 col-sm-6 col-md-3">
          <q-card class="clean-solid-card card-hover">
            <q-card-section>
              <div class="row items-center justify-between q-mb-md">
                <div class="text-subtitle2 text-weight-bold text-blue-9 text-uppercase tracking-wide">Placed Orders</div>
                <div class="icon-premium-box bg-blue-1 text-blue-7 shadow-soft">
                  <q-icon name="shopping_cart_checkout" size="22px" />
                </div>
              </div>
              <div class="text-h3 text-weight-bolder text-dark">{{ stats.placed_orders }}</div>
            </q-card-section>
          </q-card>
        </div>

        <div class="col-12 col-sm-6 col-md-3">
          <q-card class="clean-solid-card card-hover">
            <q-card-section>
              <div class="row items-center justify-between q-mb-md">
                <div class="text-subtitle2 text-weight-bold text-amber-9 text-uppercase tracking-wide">Preparing</div>
                <div class="icon-premium-box bg-amber-1 text-amber-7 shadow-soft">
                  <q-icon name="inventory_2" size="22px" />
                </div>
              </div>
              <div class="text-h3 text-weight-bolder text-dark">{{ stats.preparing_orders }}</div>
            </q-card-section>
          </q-card>
        </div>

        <div class="col-12 col-sm-6 col-md-3">
          <q-card class="clean-solid-card card-hover">
            <q-card-section>
              <div class="row items-center justify-between q-mb-md">
                <div class="text-subtitle2 text-weight-bold text-green-9 text-uppercase tracking-wide">Completed</div>
                <div class="icon-premium-box bg-green-1 text-green-7 shadow-soft">
                  <q-icon name="task_alt" size="22px" />
                </div>
              </div>
              <div class="text-h3 text-weight-bolder text-dark">{{ stats.completed_orders }}</div>
            </q-card-section>
          </q-card>
        </div>

        <div class="col-12 col-sm-6 col-md-3">
          <q-card class="clean-solid-card card-hover">
            <q-card-section>
              <div class="row items-center justify-between q-mb-md">
                <div class="text-subtitle2 text-weight-bold text-red-9 text-uppercase tracking-wide">Cancelled</div>
                <div class="icon-premium-box bg-red-1 text-red-7 shadow-soft">
                  <q-icon name="block" size="22px" />
                </div>
              </div>
              <div class="text-h3 text-weight-bolder text-dark">{{ stats.cancelled_orders }}</div>
            </q-card-section>
          </q-card>
        </div>
      </div>

      <!-- ================= RECENT ORDERS TABLE ================= -->
      <q-card class="premium-glass-card q-mb-xl card-hover" style="border-radius: 16px;">
        <q-card-section class="panel-header row items-center justify-between q-pa-md">
          <div class="text-h6 text-weight-bold text-dark row items-center">
            <div class="header-accent-red q-mr-md"></div>
            Recent Customer Order Requests
          </div>
          <q-btn label="View All Orders" flat color="red-9" class="text-weight-bold border-radius-8" no-caps />
        </q-card-section>
        
        <q-table
          flat
          class="custom-premium-table bg-transparent"
          :rows="recentOrders"
          :columns="orderColumns"
          row-key="id"
          hide-bottom
          :pagination="{ rowsPerPage: 5 }"
        >
          <template #body-cell-customer="props">
            <q-td :props="props">
              <div class="row items-center no-wrap">
                <q-avatar size="34px" class="q-mr-md shadow-soft border-white">
                  <img :src="props.row.avatar" v-if="props.row.avatar" />
                  <q-icon name="face" color="blue-grey-6" size="22px" class="bg-blue-grey-1" v-else />
                </q-avatar>
                <div class="text-weight-bold text-blue-grey-9 text-body2">{{ props.row.customer }}</div>
              </div>
            </q-td>
          </template>
          
          <template #body-cell-status="props">
            <q-td :props="props">
              <q-chip 
                size="sm" 
                :color="getStatusColor(props.row.status)" 
                text-color="white" 
                class="text-weight-bold shadow-soft q-px-md"
              >
                {{ props.row.status }}
              </q-chip>
            </q-td>
          </template>

          <template #body-cell-action="props">
            <q-td :props="props" class="text-right">
              <q-btn flat round dense icon="chevron_right" color="blue-grey-4" class="hover-icon-btn" />
            </q-td>
          </template>
        </q-table>
      </q-card>

      <!-- ================= REVENUE & ML BLUEPRINT ================= -->
      <div class="row q-col-gutter-lg">
        
        <!-- Revenue Chart -->
        <div class="col-12 col-md-7">
          <q-card class="premium-glass-card h-full card-hover" style="border-radius: 16px;">
            <q-card-section class="panel-header row items-center justify-between q-pa-md">
              <div class="text-h6 text-weight-bold text-dark row items-center">
                <div class="header-accent-red q-mr-md"></div>
                Revenue Overview
              </div>
              <!-- Fixed Filter Options -->
              <q-btn-group flat class="bg-slate-50 border-slate-light rounded-borders p-1">
                <q-btn 
                  v-for="filter in ['Daily', 'Weekly', 'Monthly']" 
                  :key="filter"
                  :label="filter"
                  :unelevated="activeRevenueFilter === filter"
                  :flat="activeRevenueFilter !== filter"
                  :class="activeRevenueFilter === filter ? 'bg-white text-dark shadow-1 text-weight-bold' : 'text-blue-grey-6 hover-text-dark'"
                  no-caps size="sm" class="border-radius-8 transition-ease"
                  @click="activeRevenueFilter = filter"
                />
              </q-btn-group>
            </q-card-section>
            
            <q-card-section class="flex flex-center chart-placeholder-bg" style="height: 250px;">
              <div class="column items-center text-blue-grey-4 text-center z-top">
                <q-icon name="insights" size="48px" class="q-mb-sm opacity-50"/> 
                <span class="text-weight-bold tracking-wide">REVENUE DATA VISUALIZATION</span>
                <span class="text-caption text-blue-grey-5 q-mt-xs">Chart component will render here</span>
              </div>
            </q-card-section>
          </q-card>
        </div>

        <!-- ML Blueprint Container (Reverted to Indigo/Amber) -->
        <div class="col-12 col-md-5">
          <q-card class="ml-blueprint-card h-full card-hover" style="border-radius: 16px;">
            <q-card-section class="q-pa-md relative-position h-full column">
              <div class="ml-glow-overlay"></div>
              
              <div class="row items-center justify-between q-mb-md relative-position z-top">
                <div class="row items-center">
                  <q-icon name="model_training" size="32px" color="amber-3" class="q-mr-sm drop-shadow-icon" />
                  <div class="text-h6 text-weight-bold text-white">Demand Forecast</div>
                </div>
                <q-chip color="white" text-color="indigo-10" size="sm" class="text-weight-bolder shadow-1">
                  <q-icon name="memory" size="14px" class="q-mr-xs"/> AI Engine
                </q-chip>
              </div>
              
              <p class="text-indigo-1 text-body2 q-mb-lg opacity-80 relative-position z-top leading-relaxed flex-grow-1">
                Your historical sales data is currently being analyzed by the Random Forest model to predict upcoming inventory needs.
              </p>

              <div class="ml-container-glass flex flex-center relative-position overflow-hidden shadow-soft q-mt-auto" style="height: 140px; border-radius: 12px;">
                <div class="ml-animated-bg"></div>
                
                <div class="text-center z-top">
                  <q-spinner-orbit size="45px" color="amber-3" />
                  <div class="text-caption text-amber-2 q-mt-md font-monospace text-weight-bold tracking-wide">
                    PROCESSING BEHAVIORS...
                  </div>
                </div>
              </div>
            </q-card-section>
          </q-card>
        </div>

      </div>
    </div>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { api } from '@/boot/axios'
import { useAuth } from '@/composables/useAuth'

const { logout } = useAuth()
const checkingAccess = ref(true)
const userName = ref('Vendor')

// Added reactive state for Revenue Filter
const activeRevenueFilter = ref('Daily')

// --- DYNAMIC TIME LOGIC ---
const currentHour = new Date().getHours()

const timeGreeting = computed(() => {
  if (currentHour < 12) return 'Good morning'
  if (currentHour < 18) return 'Good afternoon'
  return 'Good evening'
})

const headerThemeClass = computed(() => {
  if (currentHour < 12) return 'header-morning'
  if (currentHour < 18) return 'header-afternoon'
  return 'header-evening'
})

const headerTextClass = computed(() => {
  if (currentHour >= 18) return 'text-white'
  return 'text-blue-grey-9'
})

const subTextClass = computed(() => {
  if (currentHour >= 18) return 'text-indigo-1'
  return 'text-blue-grey-7'
})

const currentDate = computed(() => {
  const options = { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' }
  return new Intl.DateTimeFormat('en-US', options).format(new Date())
})
// --------------------------

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
    case 'placed': return 'blue-5'
    case 'preparing': return 'amber-6'
    case 'completed': return 'emerald-5'
    case 'cancelled': return 'red-5'
    default: return 'blue-grey-4'
  }
}

onMounted(async () => {
  try {
    const res = await api.get('/user')
    if (res.data && res.data.user) {
      const user = res.data.user
      userName.value = user.full_name ? user.full_name.split(' ')[0] : 'Vendor'
    }

    const statsRes = await api.get('/vendor/stats')
    if (statsRes.data) {
      stats.value.placed_orders = statsRes.data.placed_orders || 0
      stats.value.preparing_orders = statsRes.data.preparing_orders || 0
      stats.value.completed_orders = statsRes.data.completed_orders || 0
      stats.value.cancelled_orders = statsRes.data.cancelled_orders || 0
      recentOrders.value = statsRes.data.recent_orders || []
    }
  } catch (error) {
    userName.value = 'Vendor'
  } finally {
    checkingAccess.value = false
  }
})
</script>

<style scoped>
/* Core Page Styling */
.vendor-page {
  padding: 32px 24px;
  background-color: #f8fafc;
  min-height: 100vh;
  position: relative;
  overflow: hidden;
}

/* Ambient Background Glows */
.bg-glow {
  position: absolute;
  width: 600px;
  height: 600px;
  border-radius: 50%;
  filter: blur(140px);
  z-index: 0;
  opacity: 0.15;
  pointer-events: none;
}
.bg-glow-primary {
  top: -150px;
  left: -150px;
  background: radial-gradient(circle, rgba(185, 28, 28, 0.4) 0%, transparent 70%); 
}
.bg-glow-secondary {
  bottom: -150px;
  right: -150px;
  background: radial-gradient(circle, rgba(15, 23, 42, 0.3) 0%, transparent 70%);
}

.page-container {
  max-width: 1400px;
  margin: 0 auto;
  position: relative;
  z-index: 1;
}

/* Clean Solid Metrics Cards (Replaced the heavily glassed ones) */
.clean-solid-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

/* Glassmorphism Panels (Slightly frosted for tables/charts) */
.premium-glass-card {
  background: rgba(255, 255, 255, 0.98);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border: 1px solid rgba(241, 245, 249, 1);
  box-shadow: 0 4px 24px rgba(15, 23, 42, 0.04);
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.card-hover:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
}

/* ================= TIME-SYNCED HEADER THEMES (Strictly Reverted) ================= */
.transition-theme {
  transition: background 1s ease, color 0.5s ease;
}
.header-morning {
  background: linear-gradient(135deg, rgba(254, 243, 199, 0.85) 0%, rgba(224, 242, 254, 0.85) 100%);
  border: 1px solid rgba(255, 255, 255, 0.9);
}
.header-afternoon {
  background: linear-gradient(135deg, rgba(224, 242, 254, 0.9) 0%, rgba(219, 234, 254, 0.7) 100%);
  border: 1px solid rgba(255, 255, 255, 0.9);
}
.header-evening {
  background: linear-gradient(135deg, #1e3a8a 0%, #312e81 100%);
  border: 1px solid rgba(255, 255, 255, 0.1);
  box-shadow: 0 15px 40px rgba(30, 58, 138, 0.3), inset 0 1px 0 rgba(255,255,255,0.1);
}

.bg-glass {
  background: rgba(255, 255, 255, 0.2);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.3);
}

/* Status Dot Indicator */
.status-dot {
  width: 10px;
  height: 10px;
  background-color: #10b981;
  border-radius: 50%;
  box-shadow: 0 0 10px rgba(16, 185, 129, 0.6);
  animation: pulse-dot 2s infinite;
}
@keyframes pulse-dot {
  0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
  70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
  100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
}

.h-full { height: 100%; }

/* Buttons & Icons */
.btn-premium {
  border-radius: 10px !important;
  font-weight: 700;
  transition: all 0.2s ease;
}
.btn-premium:hover {
  transform: scale(1.02);
}

.icon-premium-box {
  width: 46px;
  height: 46px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(255,255,255,0.9);
}
.shadow-soft { box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04); }
.border-white { border: 2px solid #ffffff; }

/* Filter Buttons */
.bg-slate-50 { background-color: #f8fafc; }
.border-slate-light { border: 1px solid #e2e8f0; }
.transition-ease { transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1); }
.hover-text-dark:hover { color: #1e293b !important; }

/* Panels & Tables */
.panel-header {
  background: rgba(248, 250, 252, 0.5);
  border-bottom: 1px solid rgba(226, 232, 240, 0.6);
  border-radius: 16px 16px 0 0;
}

.header-accent-red {
  width: 6px; height: 24px; background: linear-gradient(180deg, #B91C1C 0%, #450A0A 100%); border-radius: 6px; 
}

:deep(.custom-premium-table thead tr th) {
  background: rgba(248, 250, 252, 0.5); 
  font-weight: 700;
  color: #64748b; 
  text-transform: uppercase; 
  font-size: 11px; 
  letter-spacing: 0.05em; 
  padding: 16px 20px; 
  border-bottom: 1px solid rgba(226, 232, 240, 0.8);
}
:deep(.custom-premium-table tbody td) {
  padding: 16px 20px; 
  border-bottom: 1px solid rgba(241, 245, 249, 1);
  transition: all 0.2s ease;
}
:deep(.custom-premium-table tbody tr:hover) {
  background: #ffffff;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
  transform: scale(1.002);
  z-index: 5;
  position: relative;
}
:deep(.custom-premium-table tbody tr:hover td) {
  border-bottom-color: transparent;
}
.hover-icon-btn { transition: all 0.2s ease; }
:deep(.custom-premium-table tbody tr:hover .hover-icon-btn) {
  color: #B91C1C !important; 
  transform: translateX(4px);
  background: rgba(185, 28, 28, 0.05);
}

.border-radius-8 { border-radius: 8px; }
.p-1 { padding: 4px; }

/* Fixed Chart Background */
.chart-placeholder-bg {
  background-image: 
    linear-gradient(rgba(226, 232, 240, 0.3) 1px, transparent 1px),
    linear-gradient(90deg, rgba(226, 232, 240, 0.3) 1px, transparent 1px);
  background-size: 20px 20px;
  border-radius: 0 0 16px 16px;
}

/* ML Blueprint Card - Reverted to Original Indigo/Amber Theme */
.ml-blueprint-card {
  background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
  border: 1px solid rgba(255, 255, 255, 0.08);
  box-shadow: 0 15px 40px rgba(15, 23, 42, 0.3), inset 0 1px 0 rgba(255,255,255,0.05);
}
.ml-glow-overlay {
  position: absolute;
  top: 0; right: 0;
  width: 150px; height: 150px;
  background: radial-gradient(circle, rgba(99, 102, 241, 0.2) 0%, transparent 70%);
  filter: blur(20px);
  z-index: 0;
}
.ml-container-glass {
  background: rgba(0, 0, 0, 0.25);
  border: 1px solid rgba(251, 191, 36, 0.25);
  backdrop-filter: blur(12px);
}
.ml-animated-bg {
  position: absolute;
  top: -50%; left: -50%; width: 200%; height: 200%;
  background: conic-gradient(from 0deg, transparent 0%, rgba(251, 191, 36, 0.08) 25%, transparent 50%);
  animation: rotate-bg 10s linear infinite;
}
@keyframes rotate-bg {
  100% { transform: rotate(360deg); }
}

.drop-shadow-icon {
  filter: drop-shadow(0 0 10px rgba(251, 191, 36, 0.4));
}
.opacity-50 { opacity: 0.5; }
.opacity-80 { opacity: 0.8; }
.flex-grow-1 { flex-grow: 1; }
.font-monospace {
  font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
  letter-spacing: 0.12em;
}
.tracking-wide { letter-spacing: 0.08em; }
.leading-relaxed { line-height: 1.6; }
</style>