<template>
  <q-page class="admin-dashboard">
    <div class="dashboard-container">
      
      <!-- ================= WELCOME BANNER (HEADER) ================= -->
      <div class="welcome-banner q-mb-xl row items-center justify-between q-pa-lg">
        
        <!-- Left: Profile & Greeting -->
        <div class="row items-center col-12 col-md-8">
          <!-- Profile Icon (3D Raised) -->
          <div class="profile-3d q-mr-lg">
            <q-avatar size="68px" class="bg-white text-red-9">
              <q-icon name="admin_panel_settings" size="36px" />
            </q-avatar>
          </div>
          
          <!-- Text Content -->
          <div>
            <div class="text-overline text-white opacity-70 tracking-wide line-height-tight">
              ADMIN PANEL
            </div>
            <h1 class="text-h4 text-weight-bold text-white q-mt-none q-mb-xs line-height-tight">
              {{ timeBasedGreeting }}, {{ userName }} <span class="wave-emoji">👋</span>
            </h1>
            <div class="text-white opacity-80 row items-center text-body2">
              Here's what's happening with your store metrics today.
              
              <!-- Dynamic Attention Badge (Glass) -->
              <span v-if="stats.pending_approvals > 0" class="attention-badge q-ml-md flex items-center text-caption text-weight-bold">
                <div class="pulse-dot-white q-mr-sm"></div>
                {{ stats.pending_approvals }} task(s) need attention
              </span>
            </div>
          </div>
        </div>

        <!-- Right: Controls & Time (Glassmorphism) -->
        <div class="col-12 col-md-auto row items-center justify-end q-mt-lg q-mt-md-none">
          <q-btn 
            flat 
            round 
            color="white" 
            icon="sync" 
            class="q-mr-md refresh-btn-dark hover-scale"
            :loading="loading"
            @click="refreshDashboard"
          >
            <q-tooltip class="bg-white text-dark text-weight-medium">Sync Dashboard</q-tooltip>
          </q-btn>

          <div class="time-card-dark">
            <div class="column text-right">
              <span class="text-caption text-white opacity-70 text-weight-medium">
                {{ currentDate }}
              </span>
              <span class="text-h6 text-white text-weight-bold tracking-tight line-height-tight">
                {{ currentTime }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- ================= SUMMARY CARDS (Glass & 3D Icons) ================= -->
      <div class="row q-col-gutter-lg q-mb-xl">
        
        <!-- Pending Approvals -->
        <div class="col-12 col-sm-6 col-lg-3">
          <q-card flat v-ripple class="glass-card hover-3d-lift cursor-pointer" @click="$router.push('/admin/approvals')">
            <q-card-section class="row items-center justify-between q-pa-lg">
              <div class="column">
                <span class="text-caption text-weight-bold text-grey-7 text-uppercase row items-center">
                  Pending Approvals
                  <div v-if="stats.pending_approvals > 0" class="pulse-dot q-ml-sm"></div>
                </span>
                <span class="text-h4 text-weight-bold text-dark q-mt-xs">{{ stats.pending_approvals }}</span>
              </div>
              <div class="icon-3d icon-red">
                <q-icon name="pending_actions" size="26px" color="white" />
              </div>
            </q-card-section>
          </q-card>
        </div>

        <!-- Total Vendors -->
        <div class="col-12 col-sm-6 col-lg-3">
          <q-card flat v-ripple class="glass-card hover-3d-lift cursor-pointer" @click="$router.push('/admin/vendors')">
            <q-card-section class="row items-center justify-between q-pa-lg">
              <div class="column">
                <span class="text-caption text-weight-bold text-grey-7 text-uppercase">Vendors</span>
                <span class="text-h4 text-weight-bold text-dark q-mt-xs">{{ stats.total_vendors }}</span>
              </div>
              <div class="icon-3d icon-blue">
                <q-icon name="storefront" size="26px" color="white" />
              </div>
            </q-card-section>
          </q-card>
        </div>

        <!-- Total Consumers -->
        <div class="col-12 col-sm-6 col-lg-3">
          <q-card flat v-ripple class="glass-card hover-3d-lift cursor-pointer" @click="$router.push('/admin/consumers')">
            <q-card-section class="row items-center justify-between q-pa-lg">
              <div class="column">
                <span class="text-caption text-weight-bold text-grey-7 text-uppercase">Consumers</span>
                <span class="text-h4 text-weight-bold text-dark q-mt-xs">{{ stats.total_consumers }}</span>
              </div>
              <div class="icon-3d icon-green">
                <q-icon name="groups" size="26px" color="white" />
              </div>
            </q-card-section>
          </q-card>
        </div>

        <!-- Total Users -->
        <div class="col-12 col-sm-6 col-lg-3">
          <q-card flat class="glass-card hover-3d-lift">
            <q-card-section class="row items-center justify-between q-pa-lg">
              <div class="column">
                <span class="text-caption text-weight-bold text-grey-7 text-uppercase">Total Users</span>
                <span class="text-h4 text-weight-bold text-dark q-mt-xs">{{ stats.total_users }}</span>
              </div>
              <div class="icon-3d icon-purple">
                <q-icon name="group" size="26px" color="white" />
              </div>
            </q-card-section>
          </q-card>
        </div>
      </div>

      <!-- ================= PENDING APPLICATIONS TABLE (Glass) ================= -->
      <q-card flat class="glass-card table-glass-container q-mb-xl">
        <div class="panel-header row items-center justify-between q-pa-lg">
          <div class="row items-center">
            <div class="header-accent-3d q-mr-md"></div>
            <div>
              <h2 class="text-h6 text-weight-bold text-red-10 q-ma-none">Pending Vendor Applications</h2>
              <div class="text-caption text-red-8 q-mt-xs">Latest vendor applications awaiting administrator review.</div>
            </div>
          </div>
          <q-btn 
            unelevated 
            color="red-8" 
            icon="visibility" 
            label="View All" 
            no-caps 
            class="btn-3d hover-scale"
            @click="$router.push('/admin/approvals')" 
          />
        </div>

        <q-table
          flat
          class="custom-glass-table"
          :rows="pendingApplications"
          :columns="columns"
          row-key="approval_id"
          :loading="loading"
          hide-bottom
          :rows-per-page-options="[0]"
        >
          <template #loading>
            <q-inner-loading showing color="red-8" class="bg-transparent">
              <q-spinner-dots size="40px" />
            </q-inner-loading>
          </template>

          <template #body-cell-store_name="props">
            <q-td :props="props">
              <span class="text-weight-bold text-dark">{{ props.row.store_name }}</span>
            </q-td>
          </template>

          <template #body-cell-owner_name="props">
            <q-td :props="props">
              <span class="text-grey-8 text-weight-medium">{{ props.row.owner_name }}</span>
            </q-td>
          </template>

          <template #body-cell-applied_at="props">
            <q-td :props="props">
              <q-chip dense class="glass-chip text-red-9 text-weight-medium q-px-sm" icon="schedule">
                {{ formatDate(props.row.applied_at) }}
              </q-chip>
            </q-td>
          </template>

          <template #body-cell-action="props">
            <q-td :props="props" align="right">
              <q-btn
                outline
                color="red-8"
                icon="remove_red_eye"
                label="Review"
                size="sm"
                padding="6px 16px"
                no-caps
                class="btn-3d-outline action-btn-hover"
                @click="viewApplication(props.row)"
              />
            </q-td>
          </template>

          <template #no-data>
            <div class="full-width column flex-center q-py-xl empty-state-glass">
              <div class="empty-icon-3d q-mb-md">
                <q-icon name="check_circle" color="green-5" size="40px" />
              </div>
              <div class="text-h6 text-weight-bold text-grey-8">All caught up!</div>
              <div class="text-body2 text-grey-6">There are no pending vendor applications to review.</div>
            </div>
          </template>
        </q-table>
      </q-card>

      <!-- ================= QUICK ACTIONS (3D Horizontal Cards) ================= -->
      <div class="text-h6 text-weight-bold text-dark q-mb-md">
        Quick Actions
      </div>
      
      <div class="row q-col-gutter-lg">
        
        <!-- Review Applications -->
        <div class="col-12 col-md-4">
          <q-card flat v-ripple class="glass-card hover-3d-lift cursor-pointer" @click="$router.push('/admin/approvals')">
            <q-card-section class="row items-center no-wrap q-pa-md">
              <div class="action-icon-3d text-red-8 q-mr-md">
                <q-icon name="fact_check" size="28px" />
              </div>
              <div class="col">
                <div class="text-subtitle2 text-weight-bold text-dark line-height-tight">Review Applications</div>
                <div class="text-caption text-grey-6 line-height-tight q-mt-xs">Approve or reject vendors</div>
              </div>
              <q-icon name="chevron_right" color="grey-4" size="24px" class="action-arrow" />
            </q-card-section>
          </q-card>
        </div>

        <!-- Manage Vendors -->
        <div class="col-12 col-md-4">
          <q-card flat v-ripple class="glass-card hover-3d-lift cursor-pointer" @click="$router.push('/admin/vendors')">
            <q-card-section class="row items-center no-wrap q-pa-md">
              <div class="action-icon-3d text-red-8 q-mr-md">
                <q-icon name="storefront" size="28px" />
              </div>
              <div class="col">
                <div class="text-subtitle2 text-weight-bold text-dark line-height-tight">Manage Vendors</div>
                <div class="text-caption text-grey-6 line-height-tight q-mt-xs">View all approved vendors</div>
              </div>
              <q-icon name="chevron_right" color="grey-4" size="24px" class="action-arrow" />
            </q-card-section>
          </q-card>
        </div>

        <!-- Manage Consumers -->
        <div class="col-12 col-md-4">
          <q-card flat v-ripple class="glass-card hover-3d-lift cursor-pointer" @click="$router.push('/admin/consumers')">
            <q-card-section class="row items-center no-wrap q-pa-md">
              <div class="action-icon-3d text-red-8 q-mr-md">
                <q-icon name="groups" size="28px" />
              </div>
              <div class="col">
                <div class="text-subtitle2 text-weight-bold text-dark line-height-tight">Manage Consumers</div>
                <div class="text-caption text-grey-6 line-height-tight q-mt-xs">View registered accounts</div>
              </div>
              <q-icon name="chevron_right" color="grey-4" size="24px" class="action-arrow" />
            </q-card-section>
          </q-card>
        </div>

      </div>

    </div>

    <!-- ================= REVIEW DIALOG ================= -->
    <q-dialog v-model="showApplicationDialog" persistent transition-show="scale" transition-hide="scale">
      <q-card class="review-dialog-glass">
        
        <q-card-section class="row items-center q-pa-lg bg-red-9 text-white relative-position overflow-hidden">
          <div class="dialog-bg-glow"></div>
          
          <!-- Elevated local context to ensure visibility above glow -->
          <div class="profile-3d q-mr-md relative-position" style="z-index: 2;">
            <q-avatar class="bg-white text-red-9" size="48px">
              <q-icon name="storefront" size="24px" />
            </q-avatar>
          </div>
          <div class="col relative-position" style="z-index: 2;">
            <div class="text-h6 text-weight-bold">{{ selectedApplication.store_name }}</div>
            <div class="text-caption text-red-1">Vendor Application Review</div>
          </div>
          <q-btn icon="close" flat round dense v-close-popup class="text-white relative-position" style="z-index: 2;" />
        </q-card-section>

        <q-card-section class="q-pa-lg dialog-body-glass">
          <div class="text-overline text-grey-6 q-mb-sm">Applicant Details</div>
          
          <div class="row q-col-gutter-y-md q-col-gutter-x-xl">
            <div class="col-12 col-sm-6">
              <div class="text-caption text-grey-6">Store Owner</div>
              <div class="text-subtitle2 text-weight-bold text-dark">{{ selectedApplication.owner_name }}</div>
            </div>
            
            <div class="col-12 col-sm-6">
              <div class="text-caption text-grey-6">Contact Number</div>
              <div class="text-subtitle2 text-weight-bold text-dark">{{ selectedApplication.phone || 'Not Provided' }}</div>
            </div>

            <div class="col-12 col-sm-6">
              <div class="text-caption text-grey-6">Email Address</div>
              <div class="text-subtitle2 text-weight-bold text-dark">{{ selectedApplication.email }}</div>
            </div>

            <div class="col-12 col-sm-6">
              <div class="text-caption text-grey-6">Date Applied</div>
              <div class="text-subtitle2 text-weight-bold text-dark">{{ formatDate(selectedApplication.applied_at) }}</div>
            </div>
          </div>
        </q-card-section>

        <q-separator color="grey-3" />

        <q-card-actions align="right" class="q-pa-md dialog-actions-glass">
          <q-btn flat label="Cancel" color="grey-7" no-caps class="btn-3d-outline q-px-md" v-close-popup />
          <q-btn outline color="red-8" label="Reject" no-caps class="btn-3d-outline q-px-md q-ml-sm" @click="rejectApplication(selectedApplication)" />
          <q-btn unelevated color="green-7" label="Approve Vendor" icon="check_circle" no-caps class="btn-3d q-px-md q-ml-sm" @click="approveApplication(selectedApplication)" />
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
  approval_id: null,
  store_id: null,
  store_name: '',
  owner_name: '',
  email: '',
  phone: '',
  applied_at: ''
})

const columns = [
  { name: 'store_name', label: 'Store Name', field: 'store_name', align: 'left' },
  { name: 'owner_name', label: 'Owner', field: 'owner_name', align: 'left' },
  { name: 'applied_at', label: 'Applied On', field: 'applied_at', align: 'left' },
  { name: 'action', label: 'Action', field: 'action', align: 'right' }
]

const currentDate = ref('')
const currentTime = ref('')
let timer = null

// Dynamic Greeting Logic
const timeBasedGreeting = computed(() => {
  const hour = new Date().getHours()
  if (hour < 12) return 'Good morning'
  if (hour < 18) return 'Good afternoon'
  return 'Good evening'
})

const updateDateTime = () => {
  const now = new Date()
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

const loadDashboard = async () => {
  loading.value = true
  try {
    const [statsRes, pendingRes] = await Promise.all([
      api.get('/admin/stats'),
      api.get('/admin/vendors/pending')
    ])
    stats.value = statsRes.data
    pendingApplications.value = pendingRes.data.slice(0, 5)
  } catch (error) {
    console.error(error)
  } finally {
    loading.value = false
  }
}

const refreshDashboard = async () => {
  await loadDashboard()
  $q.notify({
    type: 'positive',
    message: 'Dashboard synced successfully.',
    position: 'bottom',
    timeout: 2000,
    classes: 'glass-notify'
  })
}

const viewApplication = (row) => {
  selectedApplication.value = { ...row }
  showApplicationDialog.value = true
}

const approveApplication = (row) => {
  $q.dialog({
    title: 'Approve Vendor',
    message: `Are you sure you want to approve "${row.store_name}"?`,
    cancel: true,
    persistent: true,
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
    cancel: true,
    persistent: true,
    ok: { unelevated: true, color: 'red-8', label: 'Reject', noCaps: true },
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
   GLOBAL & AMBIENT BACKGROUND
========================================================== */
.admin-dashboard {
  /* We moved the blobs directly into the background CSS 
     to prevent ALL z-index piercing bugs entirely */
  background-color: #e2e8f0; 
  background-image: 
    radial-gradient(circle at 5% 10%, rgba(239, 68, 68, 0.12) 0%, transparent 400px),
    radial-gradient(circle at 95% 90%, rgba(59, 130, 246, 0.12) 0%, transparent 500px);
  background-attachment: fixed;
  min-height: 100vh;
  overflow-x: hidden;
}

.dashboard-container {
  max-width: 1440px;
  margin: 0 auto;
  padding: 32px 40px;
}

.tracking-wide { letter-spacing: 0.08em; }
.tracking-tight { letter-spacing: -0.02em; }
.line-height-tight { line-height: 1.2; }
.opacity-70 { opacity: 0.7; }
.opacity-80 { opacity: 0.8; }

/* ==========================================================
   WELCOME BANNER (Deep 3D Header)
========================================================== */
.welcome-banner {
  border-radius: 20px;
  background: linear-gradient(135deg, #111827 0%, #1f2937 100%);
  box-shadow: 
    0 20px 40px rgba(0,0,0,0.15),
    inset 0 1px 1px rgba(255,255,255,0.1);
  position: relative;
  overflow: hidden;
}

/* Internal Banner Glows */
.welcome-banner::before {
  content: '';
  position: absolute;
  top: -50%;
  right: -10%;
  width: 450px;
  height: 450px;
  background: radial-gradient(circle, rgba(239, 68, 68, 0.15) 0%, transparent 60%);
  border-radius: 50%;
  z-index: 1;
}

.profile-3d {
  border-radius: 50%;
  padding: 4px;
  background: linear-gradient(135deg, rgba(255,255,255,0.4), rgba(255,255,255,0.05));
  box-shadow: 
    6px 6px 15px rgba(0,0,0,0.3), 
    -4px -4px 10px rgba(255,255,255,0.05);
}

.attention-badge {
  background: rgba(239, 68, 68, 0.2);
  border: 1px solid rgba(239, 68, 68, 0.4);
  padding: 4px 10px;
  border-radius: 20px;
  backdrop-filter: blur(4px);
}

/* Glassmorphism Controls inside Banner */
.time-card-dark {
  padding: 12px 20px;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-top: 1px solid rgba(255, 255, 255, 0.2);
  border-left: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 16px;
  backdrop-filter: blur(12px);
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
  position: relative;
  z-index: 2;
}

.refresh-btn-dark {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.15);
  backdrop-filter: blur(10px);
  box-shadow: 0 4px 15px rgba(0,0,0,0.2);
  transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
  position: relative;
  z-index: 2;
}

.refresh-btn-dark:hover {
  background: rgba(255, 255, 255, 0.15);
  transform: rotate(90deg) scale(1.05);
  box-shadow: 0 8px 20px rgba(0,0,0,0.3);
}

.wave-emoji {
  display: inline-block;
  animation: wave 2.5s infinite;
  transform-origin: 70% 70%;
}
@keyframes wave {
  0% { transform: rotate(0deg); }
  10% { transform: rotate(14deg); }
  20% { transform: rotate(-8deg); }
  30% { transform: rotate(14deg); }
  40% { transform: rotate(-4deg); }
  50% { transform: rotate(10deg); }
  60% { transform: rotate(0deg); }
  100% { transform: rotate(0deg); }
}

.pulse-dot-white {
  width: 8px;
  height: 8px;
  background-color: #ffffff;
  border-radius: 50%;
  animation: pulse-white 1.5s infinite;
}
@keyframes pulse-white {
  0% { box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.7); }
  100% { box-shadow: 0 0 0 6px rgba(255, 255, 255, 0); }
}
.pulse-dot {
  width: 8px;
  height: 8px;
  background-color: #ef4444;
  border-radius: 50%;
  animation: pulse-red 1.5s infinite;
}
@keyframes pulse-red {
  0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
  100% { box-shadow: 0 0 0 6px rgba(239, 68, 68, 0); }
}

/* ==========================================================
   GLASSMORPHISM CORE (Cards & Tables)
========================================================== */
.glass-card {
  background: rgba(255, 255, 255, 0.65);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border: 1px solid rgba(255, 255, 255, 0.4);
  border-top: 1px solid rgba(255, 255, 255, 0.9);
  border-left: 1px solid rgba(255, 255, 255, 0.9);
  border-radius: 18px;
  box-shadow: 
    0 10px 30px rgba(0, 0, 0, 0.04),
    inset 0 -2px 5px rgba(255, 255, 255, 0.3);
  transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
}

.hover-3d-lift:hover {
  transform: translateY(-5px);
  background: rgba(255, 255, 255, 0.85);
  box-shadow: 
    0 20px 40px rgba(0, 0, 0, 0.08),
    inset 0 1px 0 rgba(255, 255, 255, 1);
}

/* ==========================================================
   3D CLAYMORPHIC ICONS
========================================================== */
.icon-3d {
  width: 56px;
  height: 56px;
  border-radius: 16px; 
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 
    6px 6px 12px rgba(0,0,0,0.12),
    -4px -4px 10px rgba(255,255,255,1),
    inset 2px 2px 5px rgba(255,255,255,0.4),
    inset -2px -2px 5px rgba(0,0,0,0.05);
}

.icon-red { background: linear-gradient(135deg, #f87171, #ef4444); }
.icon-blue { background: linear-gradient(135deg, #60a5fa, #3b82f6); }
.icon-green { background: linear-gradient(135deg, #4ade80, #22c55e); }
.icon-purple { background: linear-gradient(135deg, #c084fc, #a855f7); }

/* ==========================================================
   TABLE & PANEL (Glass)
========================================================== */
.table-glass-container {
  overflow: hidden;
}

.panel-header {
  background: linear-gradient(90deg, rgba(255,245,245,0.7) 0%, transparent 100%);
  border-bottom: 1px solid rgba(255, 255, 255, 0.5);
}

.header-accent-3d {
  width: 5px;
  height: 40px;
  background: linear-gradient(180deg, #f87171, #dc2626);
  border-radius: 4px;
  box-shadow: 2px 2px 5px rgba(220, 38, 38, 0.3);
}

:deep(.custom-glass-table) {
  background: transparent;
}

:deep(.custom-glass-table thead tr th) {
  background: rgba(255, 255, 255, 0.3);
  backdrop-filter: blur(4px);
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  font-size: 12px;
  letter-spacing: 0.05em;
  padding: 16px 24px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.5);
}

:deep(.custom-glass-table tbody td) {
  padding: 16px 24px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.03);
  font-size: 14px;
}

:deep(.custom-glass-table tbody tr) {
  transition: all 0.2s ease;
}

:deep(.custom-glass-table tbody tr:hover) {
  background-color: rgba(255, 255, 255, 0.9); 
}

.glass-chip {
  background: rgba(239, 68, 68, 0.1) !important;
  border: 1px solid rgba(239, 68, 68, 0.2);
  backdrop-filter: blur(4px);
}

.empty-state-glass {
  background: rgba(255, 255, 255, 0.2);
}

.empty-icon-3d {
  padding: 16px;
  border-radius: 50%;
  background: linear-gradient(135deg, #f0fdf4, #dcfce7);
  box-shadow: 
    4px 4px 10px rgba(0,0,0,0.05),
    -4px -4px 10px rgba(255,255,255,0.8),
    inset 1px 1px 3px rgba(255,255,255,1);
}

/* ==========================================================
   QUICK ACTIONS (3D Inner Blocks)
========================================================== */
.action-icon-3d {
  width: 50px;
  height: 50px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f8fafc;
  box-shadow: 
    inset 3px 3px 6px rgba(0,0,0,0.05),
    inset -3px -3px 6px rgba(255,255,255,1),
    2px 2px 5px rgba(0,0,0,0.05);
  transition: all 0.3s ease;
}

.action-arrow {
  transition: transform 0.3s ease, color 0.3s ease;
}

.glass-card:hover .action-icon-3d {
  background: linear-gradient(135deg, #f87171, #ef4444);
  color: white !important;
  box-shadow: 
    4px 4px 10px rgba(239, 68, 68, 0.3),
    inset 2px 2px 4px rgba(255,255,255,0.4);
}

.glass-card:hover .action-arrow {
  transform: translateX(4px);
  color: #ef4444 !important;
}

/* ==========================================================
   BUTTONS (3D)
========================================================== */
.btn-3d {
  border-radius: 8px !important;
  font-weight: 600;
  background: linear-gradient(135deg, #ef4444, #dc2626) !important;
  box-shadow: 0 4px 10px rgba(220, 38, 38, 0.3);
  border: 1px solid #dc2626;
  transition: all 0.2s ease;
}

.btn-3d:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 15px rgba(220, 38, 38, 0.4);
}

.btn-3d-outline {
  border-radius: 8px !important;
  font-weight: 600;
  background: rgba(255, 255, 255, 0.5) !important;
  backdrop-filter: blur(4px);
  box-shadow: 0 2px 5px rgba(0,0,0,0.05);
  transition: all 0.2s ease;
}

.action-btn-hover:hover {
  background: linear-gradient(135deg, #ef4444, #dc2626) !important;
  color: white !important;
  box-shadow: 0 4px 10px rgba(220, 38, 38, 0.3);
}

/* ==========================================================
   DIALOG (Glass)
========================================================== */
.review-dialog-glass {
  width: 650px;
  max-width: 95vw;
  border-radius: 20px !important;
  background: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.8);
  box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
  overflow: hidden;
}

.dialog-bg-glow {
  position: absolute;
  top: -50px;
  right: -50px;
  width: 200px;
  height: 200px;
  background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 70%);
  border-radius: 50%;
  z-index: 1;
}

.dialog-body-glass {
  background: transparent;
}

.dialog-actions-glass {
  background: rgba(255, 255, 255, 0.4);
}

/* ==========================================================
   RESPONSIVE
========================================================== */
@media (max-width: 1024px) {
  .dashboard-container {
    padding: 24px;
  }
}

@media (max-width: 599px) {
  .dashboard-container {
    padding: 16px;
  }
  
  .welcome-banner {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>