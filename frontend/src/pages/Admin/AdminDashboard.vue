<template>
  <q-page class="admin-page">
    <div class="page-container">

      <div class="page-header">
        <h1>Dashboard</h1>
        <p class="page-subtitle">Welcome back, {{ userName }}</p>
      </div>

      <!-- STAT CARDS -->
      <div class="stats-grid">

        <div class="stat-card" @click="$router.push('/admin/approvals')">
          <div class="stat-icon-wrap stat-pending">
            <q-icon name="pending_actions" size="24px" color="white" />
          </div>
          <div class="stat-value">{{ stats.pending_approvals }}</div>
          <div class="stat-label">Pending Approvals</div>
        </div>

        <div class="stat-card" @click="$router.push('/admin/vendors')">
          <div class="stat-icon-wrap stat-vendors">
            <q-icon name="store" size="24px" color="white" />
          </div>
          <div class="stat-value">{{ stats.total_vendors }}</div>
          <div class="stat-label">Total Vendors</div>
        </div>

        <div class="stat-card" @click="$router.push('/admin/consumers')">
          <div class="stat-icon-wrap stat-consumers">
            <q-icon name="people" size="24px" color="white" />
          </div>
          <div class="stat-value">{{ stats.total_consumers }}</div>
          <div class="stat-label">Total Consumers</div>
        </div>

        <div class="stat-card">
          <div class="stat-icon-wrap stat-total">
            <q-icon name="group" size="24px" color="white" />
          </div>
          <div class="stat-value">{{ stats.total_users }}</div>
          <div class="stat-label">Total Users</div>
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

      <!-- QUICK ACTIONS -->
      <div class="section-title">Quick Actions</div>
      <div class="actions-grid">
        <div class="action-card" @click="$router.push('/admin/approvals')">
          <q-icon name="fact_check" class="action-icon" />
          <div class="action-label">Review Applications</div>
        </div>
        <div class="action-card" @click="$router.push('/admin/vendors')">
          <q-icon name="manage_accounts" class="action-icon" />
          <div class="action-label">Manage Vendors</div>
        </div>
        <div class="action-card" @click="$router.push('/admin/consumers')">
          <q-icon name="supervisor_account" class="action-icon" />
          <div class="action-label">Manage Consumers</div>
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
import { ref, computed, onMounted } from 'vue'
import { api } from '@/boot/axios'

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

onMounted(async () => {
  try {
    const res = await api.get('/admin/stats')
    stats.value = res.data
  } catch {
    // Stats will show 0
  }
})
</script>

<style scoped>
.admin-page {
  background: #f4f5f7;

  font-family: 'Roboto', Arial, sans-serif;
}

.checking-access {
  min-height: 100vh;

  display: flex;
  align-items: center;
  justify-content: center;
}

.page-container {
  max-width: 960px;

  margin: 0 auto;

  padding: 32px 28px;
}

.page-header {
  margin-bottom: 28px;
}

.page-header h1 {
  margin: 0;

  font-size: 24px;
  font-weight: 700;

  color: #111111;
}

.page-subtitle {
  margin: 4px 0 0;

  font-size: 13px;

  color: #8992a2;
}

/* STATS */

.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);

  gap: 16px;

  margin-bottom: 36px;
}

.stat-card {
  padding: 22px 20px;

  background: #ffffff;
  border-radius: 10px;

  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);

  cursor: pointer;

  transition: box-shadow 0.2s;
}

.stat-card:hover {
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
}

.stat-icon-wrap {
  display: inline-flex;
  align-items: center;
  justify-content: center;

  width: 44px;
  height: 44px;

  border-radius: 10px;

  margin-bottom: 14px;
}

.stat-pending { background: #f59e0b; }
.stat-vendors { background: #3b82f6; }
.stat-consumers { background: #22c55e; }
.stat-total { background: #8b5cf6; }

.stat-value {
  font-size: 28px;
  font-weight: 700;

  color: #111111;

  line-height: 1;

  margin-bottom: 4px;
}

.stat-label {
  font-size: 12px;
  letter-spacing: 0.05em;
  padding: 16px 24px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.5);
}

/* ACTIONS */

.section-title {
  margin-bottom: 14px;

  font-size: 14px;
  font-weight: 700;

  color: #333333;
}

.actions-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);

  gap: 16px;
}

.action-card {
  display: flex;
  align-items: center;

  gap: 14px;

  padding: 18px 20px;

  background: #ffffff;
  border-radius: 10px;

  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);

  cursor: pointer;

  transition: box-shadow 0.2s;
}

.action-card:hover {
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
}

.action-icon {
  font-size: 24px;

  color: #bd2427;
}

.action-label {
  font-size: 13px;
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

.action-desc {
  margin-top: 2px;

  font-size: 12px;

  color: #9a9aa2;
}

.action-arrow {
  font-size: 20px;

  color: #cccccc;
}

/* RESPONSIVE */

@media (max-width: 768px) {
  .stats-grid {
    grid-template-columns: 1fr 1fr;
  }

  .actions-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 480px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }

  .page-container {
    padding: 20px 16px;
  }
}
</style>