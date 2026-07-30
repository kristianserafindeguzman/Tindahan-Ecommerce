<template>
  <q-page class="admin-dashboard">
    <!-- Richer Soft Enterprise Background -->
    <div class="ambient-mesh-bg"></div>
    <div class="enterprise-dot-pattern"></div>

    <div class="dashboard-container">
      
      <!-- ================= WELCOME BANNER ================= -->
      <div 
        class="welcome-banner q-mb-xl row items-center justify-between q-pa-lg transition-theme shadow-premium"
        :class="timeOfDayTheme"
      >
        <div class="banner-glow-overlay"></div>
        <div class="banner-glass-accent"></div>

        <!-- Left: Profile & Greeting -->
        <div class="row items-center col-12 col-md-8 banner-content-layer">
          <div>
            <div class="text-overline text-white opacity-80 tracking-widest line-height-tight q-mb-xs">
              TINDAHAN ADMIN PANEL
            </div>
            <!-- Scaled down from text-h3 to text-h4 -->
            <h1 class="text-h4 text-weight-bolder text-white q-mt-none q-mb-sm line-height-tight letter-spacing-tight text-glow">
              {{ timeBasedGreeting }}, {{ userName }} <span class="wave-emoji">👋</span>
            </h1>
            <div class="text-white opacity-90 row items-center text-body2 text-weight-medium">
              Here is the executive overview of your marketplace today.
              
              <div v-if="stats.pending_approvals > 0" class="attention-badge q-ml-md flex items-center text-caption text-weight-bolder shadow-premium cursor-pointer hover-scale">
                <div class="pulse-dot-white q-mr-sm"></div>
                Action Required: {{ stats.pending_approvals }} tasks
              </div>
            </div>
          </div>
        </div>

        <!-- Right: Controls & Time -->
        <div class="col-12 col-md-auto row items-center justify-end q-mt-lg q-mt-md-none banner-content-layer">
          <q-btn 
            flat round color="white" icon="sync" size="sm"
            class="q-mr-md refresh-btn-glass hover-rotate"
            :loading="loading" @click="refreshDashboard"
          >
            <q-tooltip class="bg-dark text-white text-weight-medium border-radius-sm">Sync Data</q-tooltip>
          </q-btn>

          <div class="time-card-glass premium-lift">
            <div class="column text-right">
              <span class="text-caption text-white opacity-80 text-weight-bold text-uppercase tracking-widest q-mb-xs" style="font-size: 10px;">
                {{ currentDate }}
              </span>
              <!-- Scaled down from text-h5 to text-h6 -->
              <span class="text-h6 text-white text-weight-bolder tracking-tight line-height-tight font-mono text-glow">
                {{ currentTime }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- ================= ASYMMETRIC BENTO METRICS ================= -->
      <div class="row q-col-gutter-lg q-mb-xl">
        
        <!-- Hero Metric: Pending Approvals -->
        <div class="col-12 col-lg-5 flex">
          <q-card 
            flat 
            ref="heroCardRef"
            class="premium-glass-card hero-card fit column justify-between cursor-pointer overflow-hidden" 
            @mousemove="handleHeroHover"
            @mouseleave="resetHeroHover"
            :style="{ '--mouse-x': mouseX, '--mouse-y': mouseY }"
            @click="$router.push('/admin/approvals')"
          >
            <div class="interactive-hue-layer"></div>
            <div class="hero-accent-line"></div>
            
            <q-card-section class="q-pa-xl column justify-between full-height card-content-layer">
              <div class="row justify-between items-start q-mb-xl">
                <div class="icon-premium-box bg-white text-red-9 border-red-light shadow-red-glow">
                  <q-icon name="pending_actions" size="28px" />
                </div>
                <q-chip v-if="stats.pending_approvals > 0" color="red-1" text-color="red-9" class="text-weight-bolder shadow-soft q-ma-none tracking-wide" size="sm">
                  PRIORITY
                </q-chip>
              </div>
              
              <div class="hero-text-content">
                <div class="text-overline text-red-9 text-uppercase tracking-widest q-mb-xs text-weight-bold">Needs Attention</div>
                <!-- Scaled down from text-h1 to text-h2 -->
                <div class="text-h2 text-weight-bolder text-dark line-height-tight q-mb-sm letter-spacing-tight hero-number">
                  {{ stats.pending_approvals }}
                </div>
                <div class="text-body2 text-grey-8 text-weight-medium">Pending vendor applications awaiting review and authorization.</div>
              </div>
            </q-card-section>
          </q-card>
        </div>

        <!-- Secondary Metrics Grid -->
        <div class="col-12 col-lg-7">
          <div class="row q-col-gutter-lg full-height">
            <div class="col-12 col-sm-6 flex">
              <q-card flat class="premium-glass-card fit cursor-pointer hover-lift-blue overflow-hidden" @click="$router.push('/admin/vendors')">
                <div class="card-glow-blue"></div>
                <q-card-section class="q-pa-lg row items-center no-wrap card-content-layer full-height">
                  <div class="icon-premium-box-sm bg-blue-50 text-blue-8 border-blue-light q-mr-lg">
                    <q-icon name="storefront" size="22px" />
                  </div>
                  <div class="col">
                    <div class="text-caption text-weight-bold text-grey-5 text-uppercase tracking-widest q-mb-xs">Approved Vendors</div>
                    <!-- Scaled down from text-h3 to text-h4 -->
                    <div class="text-h4 text-weight-bolder text-dark letter-spacing-tight">{{ stats.total_vendors }}</div>
                  </div>
                </q-card-section>
              </q-card>
            </div>

            <div class="col-12 col-sm-6 flex">
              <q-card flat class="premium-glass-card fit cursor-pointer hover-lift-green overflow-hidden" @click="$router.push('/admin/consumers')">
                <div class="card-glow-green"></div>
                <q-card-section class="q-pa-lg row items-center no-wrap card-content-layer full-height">
                  <div class="icon-premium-box-sm bg-green-50 text-green-8 border-green-light q-mr-lg">
                    <q-icon name="groups" size="22px" />
                  </div>
                  <div class="col">
                    <div class="text-caption text-weight-bold text-grey-5 text-uppercase tracking-widest q-mb-xs">Active Consumers</div>
                    <!-- Scaled down from text-h3 to text-h4 -->
                    <div class="text-h4 text-weight-bolder text-dark letter-spacing-tight">{{ stats.total_consumers }}</div>
                  </div>
                </q-card-section>
              </q-card>
            </div>

            <div class="col-12 flex">
              <q-card flat class="premium-glass-card fit overflow-hidden">
                <div class="card-glow-slate"></div>
                <q-card-section class="q-pa-lg row items-center justify-between no-wrap card-content-layer full-height">
                  <div class="row items-center">
                    <div class="icon-premium-box bg-grey-100 text-dark border-subtle q-mr-lg shadow-soft">
                      <q-icon name="public" size="28px" />
                    </div>
                    <div>
                      <div class="text-caption text-weight-bold text-grey-5 text-uppercase tracking-widest q-mb-xs">Total Platform Users</div>
                      <div class="text-body2 text-grey-6 text-weight-medium">Combined aggregate of all registered marketplace accounts</div>
                    </div>
                  </div>
                  <!-- Scaled down from text-h2 to text-h3 -->
                  <div class="text-h3 text-weight-bolder text-dark letter-spacing-tight">{{ stats.total_users }}</div>
                </q-card-section>
              </q-card>
            </div>
          </div>
        </div>
      </div>

      <!-- ================= PENDING APPLICATIONS TABLE ================= -->
      <q-card flat class="premium-glass-card table-glass-container q-mb-xl">
        <div class="panel-header row items-center justify-between q-pa-lg relative-position">
          <div class="row items-center card-content-layer">
            <div class="header-accent-red q-mr-md"></div>
            <div>
              <!-- Scaled down to text-h6 -->
              <h2 class="text-h6 text-weight-bolder text-dark q-ma-none letter-spacing-tight">Latest Applications</h2>
              <div class="text-caption text-grey-6 text-weight-medium q-mt-xs">Merchants requesting to join Tindahan.</div>
            </div>
          </div>
          <q-btn unelevated color="red-9" icon-right="arrow_forward" label="View Directory" size="sm" no-caps class="btn-premium hover-scale card-content-layer" @click="$router.push('/admin/approvals')" />
        </div>

        <q-table
          flat class="custom-premium-table" :rows="pendingApplications" :columns="columns"
          row-key="approval_id" :loading="loading" hide-bottom :rows-per-page-options="[0]"
        >
          <template #loading><q-inner-loading showing color="red-9" class="bg-white-transparent"><q-spinner-dots size="40px" /></q-inner-loading></template>
          
          <template #body-cell-store_name="props">
            <q-td :props="props">
              <span class="text-weight-bold text-dark text-body2">{{ props.row.store_name }}</span>
            </q-td>
          </template>
          
          <template #body-cell-owner_name="props">
            <q-td :props="props">
              <span class="text-grey-7 text-weight-medium text-caption">{{ props.row.owner_name }}</span>
            </q-td>
          </template>
          
          <template #body-cell-applied_at="props">
            <q-td :props="props">
              <q-chip dense class="premium-chip text-red-9 text-weight-bold q-px-sm shadow-xs" icon="schedule" size="sm">
                {{ formatDate(props.row.applied_at) }}
              </q-chip>
            </q-td>
          </template>
          
          <template #body-cell-action="props">
            <q-td :props="props" align="right">
              <q-btn outline color="red-9" icon="remove_red_eye" label="Review" size="sm" padding="4px 12px" no-caps class="btn-premium-outline action-btn-hover" @click="viewApplication(props.row)" />
            </q-td>
          </template>
          
          <template #no-data>
            <div class="full-width column flex-center q-py-xl empty-state-glass">
              <div class="empty-icon-premium q-mb-lg shadow-soft"><q-icon name="check_circle" color="green-6" size="36px" /></div>
              <div class="text-h6 text-weight-bolder text-dark letter-spacing-tight q-mb-sm">All caught up!</div>
              <div class="text-body2 text-grey-6 text-weight-medium">There are no pending vendor applications to review.</div>
            </div>
          </template>
        </q-table>
      </q-card>

      <!-- ================= QUICK ACTIONS ================= -->
      <div class="text-h6 text-weight-bolder text-dark q-mb-md letter-spacing-tight">Quick Navigation</div>
      <div class="row q-col-gutter-lg">
        
        <div class="col-12 col-md-4">
          <q-card flat class="premium-glass-card hover-lift-action cursor-pointer action-card overflow-hidden" @click="$router.push('/admin/approvals')">
            <div class="action-card-glow text-red-9"></div>
            <q-card-section class="row items-center no-wrap q-pa-lg card-content-layer">
              <div class="action-icon-premium bg-red-50 text-red-9 q-mr-lg"><q-icon name="fact_check" size="24px" /></div>
              <div class="col">
                <div class="text-subtitle2 text-weight-bolder text-dark line-height-tight">Review Center</div>
                <div class="text-caption text-grey-6 text-weight-medium line-height-tight q-mt-xs">Process store applications</div>
              </div>
              <q-icon name="chevron_right" color="grey-4" size="24px" class="action-arrow" />
            </q-card-section>
          </q-card>
        </div>

        <div class="col-12 col-md-4">
          <q-card flat class="premium-glass-card hover-lift-action cursor-pointer action-card overflow-hidden" @click="$router.push('/admin/vendors')">
            <div class="action-card-glow text-red-9"></div>
            <q-card-section class="row items-center no-wrap q-pa-lg card-content-layer">
              <div class="action-icon-premium bg-red-50 text-red-9 q-mr-lg"><q-icon name="storefront" size="24px" /></div>
              <div class="col">
                <div class="text-subtitle2 text-weight-bolder text-dark line-height-tight">Merchant Directory</div>
                <div class="text-caption text-grey-6 text-weight-medium line-height-tight q-mt-xs">Manage active vendors</div>
              </div>
              <q-icon name="chevron_right" color="grey-4" size="24px" class="action-arrow" />
            </q-card-section>
          </q-card>
        </div>

        <div class="col-12 col-md-4">
          <q-card flat class="premium-glass-card hover-lift-action cursor-pointer action-card overflow-hidden" @click="$router.push('/admin/consumers')">
            <div class="action-card-glow text-red-9"></div>
            <q-card-section class="row items-center no-wrap q-pa-lg card-content-layer">
              <div class="action-icon-premium bg-red-50 text-red-9 q-mr-lg"><q-icon name="groups" size="24px" /></div>
              <div class="col">
                <div class="text-subtitle2 text-weight-bolder text-dark line-height-tight">Consumer Accounts</div>
                <div class="text-caption text-grey-6 text-weight-medium line-height-tight q-mt-xs">View registered shoppers</div>
              </div>
              <q-icon name="chevron_right" color="grey-4" size="24px" class="action-arrow" />
            </q-card-section>
          </q-card>
        </div>

      </div>
    </div>

    <!-- ================= REVIEW DIALOG ================= -->
    <q-dialog v-model="showApplicationDialog" persistent transition-show="scale" transition-hide="scale">
      <q-card class="premium-dialog-glass">
        <q-card-section class="row items-center q-pa-lg bg-red-9 text-white overflow-hidden" style="position: relative;">
          <div class="dialog-bg-glow"></div>
          <div class="dialog-pattern-overlay"></div>
          
          <q-icon name="storefront" size="32px" class="q-mr-md card-content-layer text-white" />
          
          <div class="col card-content-layer">
            <div class="text-h6 text-weight-bolder letter-spacing-tight line-height-tight">{{ selectedApplication.store_name }}</div>
            <div class="text-caption text-red-2 text-weight-medium q-mt-xs">Vendor Application Review</div>
          </div>
          <q-btn icon="close" flat round dense v-close-popup class="text-white card-content-layer hover-rotate" size="sm" />
        </q-card-section>

        <q-card-section class="q-pa-lg dialog-body-glass">
          <div class="text-overline text-grey-5 tracking-widest q-mb-md text-weight-bold">Applicant Information</div>
          <div class="row q-col-gutter-y-lg q-col-gutter-x-lg">
            <div class="col-12 col-sm-6">
              <div class="text-caption text-grey-5 text-uppercase tracking-wide text-weight-bold q-mb-xs">Store Owner</div>
              <div class="text-subtitle1 text-weight-bold text-dark line-height-tight">{{ selectedApplication.owner_name }}</div>
            </div>
            <div class="col-12 col-sm-6">
              <div class="text-caption text-grey-5 text-uppercase tracking-wide text-weight-bold q-mb-xs">Contact Number</div>
              <div class="text-subtitle1 text-weight-bold text-dark line-height-tight">{{ selectedApplication.phone || 'Not Provided' }}</div>
            </div>
            <div class="col-12 col-sm-6">
              <div class="text-caption text-grey-5 text-uppercase tracking-wide text-weight-bold q-mb-xs">Email Address</div>
              <div class="text-subtitle1 text-weight-bold text-dark line-height-tight" style="word-wrap: break-word;">{{ selectedApplication.email }}</div>
            </div>
            <div class="col-12 col-sm-6">
              <div class="text-caption text-grey-5 text-uppercase tracking-wide text-weight-bold q-mb-xs">Date Applied</div>
              <div class="text-subtitle1 text-weight-bold text-dark line-height-tight">{{ formatDate(selectedApplication.applied_at) }}</div>
            </div>
          </div>
        </q-card-section>

        <q-card-actions align="right" class="q-pa-md dialog-actions-glass">
          <q-btn flat label="Cancel" color="grey-7" no-caps class="btn-premium-outline q-px-md text-weight-bold" v-close-popup size="sm" />
          <q-btn outline color="red-9" label="Reject" no-caps class="btn-premium-outline q-px-md q-ml-sm text-weight-bold" @click="rejectApplication(selectedApplication)" size="sm" />
          <q-btn unelevated color="green-7" label="Approve Vendor" icon="check_circle" no-caps class="btn-premium q-px-md q-ml-sm text-weight-bold" @click="approveApplication(selectedApplication)" size="sm" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { useQuasar } from 'quasar'
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { api } from '@/boot/axios'

const $q = useQuasar()

// State
const stats = ref({
  pending_approvals: 0,
  total_vendors: 0,
  total_consumers: 0,
  total_users: 0
})
const pendingApplications = ref([])
const showApplicationDialog = ref(false)
const loading = ref(false)
const selectedApplication = ref({
  approval_id: null, store_id: null, store_name: '', owner_name: '', email: '', phone: '', applied_at: ''
})

// Interactive Hue Logic
const heroCardRef = ref(null)
const mouseX = ref('80%')
const mouseY = ref('80%')

const handleHeroHover = (e) => {
  if (!heroCardRef.value) return
  const rect = heroCardRef.value.$el.getBoundingClientRect()
  mouseX.value = `${e.clientX - rect.left}px`
  mouseY.value = `${e.clientY - rect.top}px`
}
const resetHeroHover = () => {
  mouseX.value = '80%'
  mouseY.value = '80%'
}

// Table Configuration
const columns = [
  { name: 'store_name', label: 'Store Profile', field: 'store_name', align: 'left' },
  { name: 'owner_name', label: 'Owner Details', field: 'owner_name', align: 'left' },
  { name: 'applied_at', label: 'Date Applied', field: 'applied_at', align: 'left' },
  { name: 'action', label: 'Review Action', field: 'action', align: 'right' }
]

const currentDate = ref('')
const currentTime = ref('')
const currentHour = ref(new Date().getHours())
let timer = null

// Dynamic Logic for Time of Day
const timeBasedGreeting = computed(() => {
  if (currentHour.value < 12) return 'Good morning'
  if (currentHour.value < 18) return 'Good afternoon'
  return 'Good evening'
})

const timeOfDayTheme = computed(() => {
  if (currentHour.value >= 5 && currentHour.value < 12) return 'theme-morning'
  if (currentHour.value >= 12 && currentHour.value < 17) return 'theme-afternoon'
  return 'theme-evening'
})

const updateDateTime = () => {
  const now = new Date()
  currentHour.value = now.getHours()
  currentDate.value = now.toLocaleDateString('en-US', {
    weekday: 'long', month: 'long', day: 'numeric', year: 'numeric'
  })
  currentTime.value = now.toLocaleTimeString('en-US', {
    hour: '2-digit', minute: '2-digit', second: '2-digit'
  })
}

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleString('en-US', {
    month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit'
  })
}

const userName = computed(() => {
  try {
    const user = JSON.parse(localStorage.getItem('auth_user') || '{}')
    return user.full_name || 'Admin'
  } catch {
    return 'Admin'
  }
})

// API calls
const loadDashboard = async () => {
  loading.value = true
  try {
    const [statsRes, pendingRes] = await Promise.all([
      api.get('/admin/stats'),
      api.get('/admin/vendors/pending')
    ])
    stats.value = statsRes.data
    pendingApplications.value = pendingRes.data.filter(app => app.status === 'pending').slice(0, 5)
  } catch (error) {
    console.error(error)
  } finally {
    loading.value = false
  }
}

const refreshDashboard = async () => {
  await loadDashboard()
  $q.notify({ type: 'positive', message: 'Dashboard synced successfully.', position: 'bottom-right', timeout: 2000 })
}

const viewApplication = (row) => {
  selectedApplication.value = { ...row }
  showApplicationDialog.value = true
}

const approveApplication = (row) => {
  $q.dialog({
    title: 'Approve Vendor',
    message: `Are you sure you want to approve "${row.store_name}"?`,
    cancel: true, persistent: true,
    ok: { unelevated: true, color: 'green-7', label: 'Approve', noCaps: true },
    cancel: { flat: true, color: 'grey-7', label: 'Cancel', noCaps: true }
  }).onOk(async () => {
    try {
      await api.post(`/admin/vendors/${row.store_id}/approve`)
      showApplicationDialog.value = false
      await loadDashboard()
      $q.notify({ type: 'positive', message: 'Vendor approved successfully.', position: 'top-right' })
    } catch (error) {
      console.error(error)
      $q.notify({ type: 'negative', message: 'Unable to approve vendor.', position: 'top-right' })
    }
  })
}

const rejectApplication = (row) => {
  $q.dialog({
    title: 'Reject Vendor',
    message: `Are you sure you want to reject "${row.store_name}"?`,
    cancel: true, persistent: true,
    ok: { unelevated: true, color: 'red-9', label: 'Reject', noCaps: true },
    cancel: { flat: true, color: 'grey-7', label: 'Cancel', noCaps: true }
  }).onOk(async () => {
    try {
      await api.post(`/admin/vendors/${row.store_id}/reject`)
      showApplicationDialog.value = false
      await loadDashboard()
      $q.notify({ type: 'positive', message: 'Vendor rejected successfully.', position: 'top-right' })
    } catch (error) {
      console.error(error)
      $q.notify({ type: 'negative', message: 'Unable to reject vendor.', position: 'top-right' })
    }
  })
}

onMounted(async () => {
  updateDateTime()
  timer = setInterval(updateDateTime, 1000)
  await loadDashboard()
})

onUnmounted(() => {
  if (timer) clearInterval(timer)
})
</script>

<style scoped>
/* ==========================================================
   GLOBAL PAGE LAYER - Fixed Background Color
========================================================== */
.admin-dashboard {
  background: linear-gradient(135deg, #F0F4F8 0%, #E2E8F0 100%); 
  min-height: 100vh;
  font-family: 'Inter', 'Roboto', -apple-system, sans-serif;
  color: #0F172A;
  overflow-x: hidden;
}

.ambient-mesh-bg {
  position: fixed; inset: 0;
  background-image: 
    radial-gradient(circle at 15% 10%, rgba(239, 68, 68, 0.08) 0%, transparent 500px),
    radial-gradient(circle at 85% 80%, rgba(59, 130, 246, 0.08) 0%, transparent 600px);
  z-index: -1; pointer-events: none;
}
.enterprise-dot-pattern {
  position: fixed; inset: 0;
  background-image: radial-gradient(#94A3B8 1.5px, transparent 1.5px);
  background-size: 28px 28px; opacity: 0.15;
  z-index: -1; pointer-events: none;
}

.dashboard-container { max-width: 1440px; margin: 0 auto; padding: 40px; }
.card-content-layer, .banner-content-layer { position: relative; z-index: 2; }

/* Utilities */
.tracking-widest { letter-spacing: 0.1em; }
.tracking-wide { letter-spacing: 0.05em; }
.tracking-tight { letter-spacing: -0.01em; }
.letter-spacing-tight { letter-spacing: -0.03em; }
.line-height-tight { line-height: 1.15; }
.font-mono { font-family: 'SFMono-Regular', Consolas, monospace; }

.shadow-soft { box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.05); }
.shadow-premium { box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.1), 0 4px 10px -5px rgba(0, 0, 0, 0.04); }
.shadow-red-glow { box-shadow: 0 8px 24px -4px rgba(239, 68, 68, 0.25); }

/* ==========================================================
   DYNAMIC TIME-BASED HEADER
========================================================== */
.welcome-banner {
  border-radius: 20px; position: relative; overflow: hidden;
  border: 1px solid rgba(255, 255, 255, 0.1);
}
.transition-theme { transition: background 2s ease-in-out; }

.theme-morning { background: linear-gradient(135deg, #2563EB 0%, #0EA5E9 50%, #38BDF8 100%); }
.theme-afternoon { background: linear-gradient(135deg, #EA580C 0%, #F97316 50%, #FB923C 100%); }
.theme-evening { background: linear-gradient(135deg, #0F172A 0%, #1E1B4B 100%); }

.banner-glow-overlay {
  position: absolute; top: -50%; right: -10%; width: 700px; height: 700px;
  background: radial-gradient(circle, rgba(255,255,255,0.12) 0%, transparent 60%);
  border-radius: 50%; pointer-events: none; z-index: 1;
}
.banner-glass-accent {
  position: absolute; bottom: 0; left: 0; right: 0; height: 40%;
  background: linear-gradient(to top, rgba(0,0,0,0.1), transparent);
  pointer-events: none; z-index: 1;
}

.text-glow { text-shadow: 0 2px 10px rgba(0,0,0,0.1); }
.attention-badge {
  background: rgba(255, 255, 255, 0.15); border: 1px solid rgba(255, 255, 255, 0.3);
  padding: 4px 12px; border-radius: 100px; backdrop-filter: blur(8px); transition: all 0.3s ease;
}

.time-card-glass, .refresh-btn-glass {
  background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2);
  backdrop-filter: blur(16px); border-radius: 12px;
}
.time-card-glass { padding: 10px 20px; }
.refresh-btn-glass { transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1); }
.hover-rotate:hover { background: rgba(255, 255, 255, 0.25); transform: rotate(180deg) scale(1.05); }

/* ==========================================================
   PREMIUM GLASSMORPHISM CORE
========================================================== */
.premium-glass-card {
  background: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px);
  border: 1px solid rgba(255, 255, 255, 0.9);
  border-radius: 20px;
  box-shadow: 0 4px 24px -4px rgba(0, 0, 0, 0.03), inset 0 1px 1px rgba(255, 255, 255, 1);
  transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
  position: relative;
}

.hover-lift-blue:hover { transform: translateY(-4px); box-shadow: 0 20px 40px -10px rgba(59, 130, 246, 0.12); border-color: rgba(191, 219, 254, 0.8); background: rgba(255,255,255,0.95); }
.hover-lift-green:hover { transform: translateY(-4px); box-shadow: 0 20px 40px -10px rgba(34, 197, 94, 0.12); border-color: rgba(187, 247, 208, 0.8); background: rgba(255,255,255,0.95); }
.hover-scale:hover { transform: scale(1.02); }

/* Interactive Hero */
.hero-card { transition: transform 0.4s cubic-bezier(0.25, 0.8, 0.25, 1), box-shadow 0.4s ease; }
.hero-card:hover { transform: translateY(-4px); box-shadow: 0 25px 50px -12px rgba(239, 68, 68, 0.25); border-color: rgba(254, 202, 202, 0.9); }

.interactive-hue-layer {
  position: absolute; inset: 0; pointer-events: none; z-index: 1; transition: background 0.15s ease-out;
  background: radial-gradient(circle 350px at var(--mouse-x) var(--mouse-y), rgba(239, 68, 68, 0.05), transparent 70%);
  animation: breathing-glow 4s ease-in-out infinite alternate;
}
.hero-card:hover .interactive-hue-layer {
  background: radial-gradient(circle 450px at var(--mouse-x) var(--mouse-y), rgba(239, 68, 68, 0.12), transparent 70%); animation: none; 
}
@keyframes breathing-glow { 0% { opacity: 0.6; transform: scale(0.95); } 100% { opacity: 1; transform: scale(1.05); } }

.hero-accent-line {
  position: absolute; top: 0; left: 20px; right: 20px; height: 3px;
  background: linear-gradient(90deg, #EF4444 0%, transparent 100%); border-radius: 0 0 3px 3px; z-index: 2;
}
.hero-text-content { transition: transform 0.3s ease; }
.hero-card:hover .hero-text-content { transform: translateX(4px); }
.hero-number { transition: color 0.3s ease, text-shadow 0.3s ease; }
.hero-card:hover .hero-number { color: #B91C1C !important; text-shadow: 0 4px 15px rgba(239, 68, 68, 0.2); }

/* Glows & Icons */
.card-glow-blue { position: absolute; top: 0; left: 0; width: 150px; height: 150px; background: radial-gradient(circle, rgba(59, 130, 246, 0.05) 0%, transparent 70%); pointer-events: none; z-index: 1; }
.card-glow-green { position: absolute; top: 0; left: 0; width: 150px; height: 150px; background: radial-gradient(circle, rgba(34, 197, 94, 0.05) 0%, transparent 70%); pointer-events: none; z-index: 1; }
.card-glow-slate { position: absolute; bottom: 0; right: 0; width: 250px; height: 250px; background: radial-gradient(circle, rgba(15, 23, 42, 0.03) 0%, transparent 70%); pointer-events: none; z-index: 1; }

.icon-premium-box { width: 52px; height: 52px; border-radius: 12px; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease; }
.icon-premium-box-sm { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease; }
.border-red-light { border: 1px solid #FEE2E2; }
.bg-blue-50 { background-color: #EFF6FF; } .border-blue-light { border: 1px solid #DBEAFE; }
.bg-green-50 { background-color: #F0FDF4; } .border-green-light { border: 1px solid #DCFCE7; }
.bg-grey-100 { background-color: #F1F5F9; } .border-subtle { border: 1px solid #E2E8F0; }

/* ==========================================================
   TABLE SECTION
========================================================== */
.table-glass-container { overflow: hidden; }
.panel-header { background: linear-gradient(90deg, rgba(255,255,255,0.8) 0%, rgba(255,255,255,0.4) 100%); border-bottom: 1px solid rgba(226, 232, 240, 0.8); }
.header-accent-red { width: 4px; height: 28px; background: #B91C1C; border-radius: 4px; box-shadow: 2px 0 8px rgba(185, 28, 28, 0.3); }

:deep(.custom-premium-table thead tr th) {
  background: rgba(248, 250, 252, 0.7); backdrop-filter: blur(8px); font-weight: 700;
  color: #64748B; text-transform: uppercase; font-size: 11px; letter-spacing: 0.05em; padding: 16px 20px; border-bottom: 1px solid rgba(226, 232, 240, 0.8);
}
:deep(.custom-premium-table tbody td) { padding: 16px 20px; border-bottom: 1px solid rgba(226, 232, 240, 0.5); transition: background 0.2s ease; }
:deep(.custom-premium-table tbody tr:hover td) { background-color: rgba(255, 255, 255, 0.95); }
.premium-chip { background: rgba(254, 226, 226, 0.5) !important; border: 1px solid #FECACA; }
.empty-state-glass { background: rgba(255, 255, 255, 0.4); }
.empty-icon-premium { padding: 16px; border-radius: 50%; background: linear-gradient(135deg, #F0FDF4, #DCFCE7); border: 1px solid #BBF7D0; }
.bg-white-transparent { background: rgba(255,255,255,0.7); backdrop-filter: blur(4px); }

/* ==========================================================
   QUICK ACTIONS
========================================================== */
.action-card-glow {
  position: absolute; top: 50%; left: 50%; width: 0; height: 0; background: radial-gradient(circle, rgba(239, 68, 68, 0.08) 0%, transparent 70%);
  transform: translate(-50%, -50%); transition: width 0.4s ease, height 0.4s ease; border-radius: 50%; z-index: 1; pointer-events: none;
}
.hover-lift-action:hover { transform: translateY(-4px); box-shadow: 0 12px 30px -5px rgba(0, 0, 0, 0.08); background: rgba(255,255,255,0.95); border-color: rgba(254, 202, 202, 0.6); }
.hover-lift-action:hover .action-card-glow { width: 300px; height: 300px; }
.hover-lift-action:hover .action-arrow { transform: translateX(6px); color: #B91C1C !important; }
.action-icon-premium { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; border: 1px solid #FEE2E2; transition: all 0.3s ease; }
.action-arrow { transition: transform 0.3s cubic-bezier(0.25, 0.8, 0.25, 1), color 0.3s ease; }

/* Buttons */
.btn-premium { border-radius: 8px !important; font-weight: 600; box-shadow: 0 4px 12px rgba(185, 28, 28, 0.2); transition: all 0.2s ease; }
.btn-premium:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(185, 28, 28, 0.3); }
.btn-premium-outline { border-radius: 8px !important; font-weight: 600; background: rgba(255, 255, 255, 0.9) !important; border: 1px solid currentColor; transition: all 0.2s ease; }
.action-btn-hover:hover { background: #FEF2F2 !important; color: #991B1B !important; }

/* ==========================================================
   REVIEW DIALOG 
========================================================== */
.premium-dialog-glass {
  width: 600px; max-width: 95vw; border-radius: 20px !important;
  background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(32px); border: 1px solid rgba(255, 255, 255, 0.9);
  box-shadow: 0 30px 60px -10px rgba(0, 0, 0, 0.2), 0 0 0 1px rgba(0,0,0,0.02); overflow: hidden;
}
.dialog-bg-glow {
  position: absolute; top: -100px; right: -100px; width: 400px; height: 400px;
  background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 70%); border-radius: 50%; pointer-events: none; z-index: 1;
}
.dialog-pattern-overlay {
  position: absolute; inset: 0; background-image: radial-gradient(rgba(255,255,255,0.2) 1.5px, transparent 1.5px);
  background-size: 16px 16px; opacity: 0.5; z-index: 1; pointer-events: none;
}
.dialog-actions-glass { background: rgba(248, 250, 252, 0.9); border-top: 1px solid rgba(226, 232, 240, 0.8); }

/* Utility Animations */
.pulse-dot-white { width: 6px; height: 6px; background-color: #ffffff; border-radius: 50%; animation: pulse-white 1.5s infinite; }
@keyframes pulse-white { 0% { box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.8); } 100% { box-shadow: 0 0 0 6px rgba(255, 255, 255, 0); } }
@keyframes wave { 0%, 60%, 100% { transform: rotate(0deg); } 10%, 30% { transform: rotate(14deg); } 20%, 40% { transform: rotate(-8deg); } 50% { transform: rotate(10deg); } }
.wave-emoji { display: inline-block; animation: wave 2.5s infinite; transform-origin: 70% 70%; }

@media (max-width: 1024px) { .dashboard-container { padding: 24px; } }
@media (max-width: 767px) {
  .dashboard-container { padding: 16px; }
  .welcome-banner { flex-direction: column; align-items: flex-start; padding: 24px; }
  .time-card-glass { margin-top: 16px; }
}
</style>