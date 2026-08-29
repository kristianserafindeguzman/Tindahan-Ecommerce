<template>
  <q-page class="admin-page">
    <div class="page-container">
      <!-- ================= PAGE BANNER ================= -->
      <div class="consumer-mgmt-header q-mb-lg row items-center justify-between q-pa-lg">
        <div class="header-bg-glow"></div>

        <div class="row items-center col-12 col-md-8 relative-position" style="z-index: 2">
          <!-- White Block with Red Groups Icon and Pulse Effect -->
          <div class="header-white-block q-mr-lg flex flex-center relative-position">
            <q-icon name="groups" size="36px" color="red-9" />
            <div class="pulse-ring"></div>
          </div>

          <div>
            <div class="q-mb-xs flex items-center">
              <span class="badge-dark-capsule">
                <q-icon name="admin_panel_settings" size="14px" class="q-mr-xs" />
                ADMINISTRATION
              </span>
              <span class="text-caption text-white opacity-80 q-ml-sm text-weight-bold tracking-wide">
                CONSUMER DIRECTORY
              </span>
            </div>

            <h1 class="header-main-title text-weight-bolder text-white q-mt-none q-mb-xs line-height-tight">
              Manage Consumers
            </h1>

            <div class="text-white opacity-80 row items-center text-body2 text-weight-medium">
              View consumer accounts, update access levels, and monitor platform interactions.
            </div>
          </div>
        </div>

        <!-- Right Side Dynamic Tab-Specific Stat Counter Box -->
        <div class="col-12 col-md-auto q-mt-md q-mt-md-none flex justify-end" style="z-index: 2">
          <div class="header-stat-box column flex-center text-center">
            <span class="stat-box-label text-weight-bolder text-uppercase">
              {{ currentTab === 'active' ? 'ACTIVE USERS' : 'DELETED USERS' }}
            </span>
            <div class="row items-baseline no-wrap q-mt-xs">
              <span class="stat-box-value font-mono">{{ currentTab === 'active' ? counts.active : counts.deleted }}</span>
              <span class="stat-box-unit q-ml-xs">accounts</span>
            </div>
          </div>
        </div>
      </div>

      <!-- ================= TOOLBAR ================= -->
      <div class="toolbar glass-toolbar q-pa-md q-mb-md row items-center justify-between">
        <div class="row items-center gap-md flex-1">
          <!-- Search Input -->
          <q-input
            v-model="search"
            outlined
            dense
            placeholder="Search by name, email, or mobile..."
            class="search-input-glass"
            debounce="300"
            @update:model-value="fetchConsumers"
          >
            <template #prepend>
              <q-icon name="search" color="red-4" />
            </template>
            <template #append v-if="search">
              <q-icon name="close" class="cursor-pointer" size="18px" @click="search = ''; fetchConsumers()" />
            </template>
          </q-input>

          <!-- Status Filter -->
          <q-select
            v-model="statusFilter"
            outlined
            dense
            emit-value
            map-options
            clearable
            placeholder="Filter by status"
            class="filter-select-glass"
            :options="statusFilterOptions"
            @update:model-value="fetchConsumers"
          >
            <template #prepend>
              <q-icon name="filter_list" color="red-4" size="20px" />
            </template>
          </q-select>
        </div>

        <!-- Export Action -->
        <q-btn
          label="Export List"
          no-caps
          outline
          icon="print"
          class="btn-glass export-btn q-ml-auto"
          color="red-7"
          @click="handleExport"
          :loading="isExporting"
        />
      </div>

      <!-- ================= MAIN CONTENT AREA ================= -->
      <q-card flat class="glass-card table-glass-container">
        <!-- TABS -->
        <div class="panel-header q-pt-sm">
          <q-tabs
            v-model="currentTab"
            dense
            class="text-grey-7"
            active-color="red-9"
            indicator-color="red-9"
            align="left"
            narrow-indicator
            @update:model-value="fetchConsumers"
          >
            <q-tab
              name="active"
              label="Active Accounts"
              class="text-weight-bold"
            />
            <q-tab
              name="deleted"
              label="Deleted Accounts"
              class="text-weight-bold"
            />
          </q-tabs>
        </div>

        <!-- TABLE -->
        <q-table
          flat
          class="custom-glass-table interactive-table"
          :rows="consumers"
          :columns="columns"
          row-key="user_id"
          :loading="loading"
        >
          <!-- FIXED LOADING STATE -->
          <template #loading>
            <div class="full-width column flex-center q-py-xl table-loading-wrapper">
              <q-spinner-dots size="48px" color="red-7" />
              <div class="text-subtitle2 text-weight-bold q-mt-md text-grey-7">
                Fetching consumers...
              </div>
            </div>
          </template>

          <!-- AVATAR & NAME COLUMN -->
          <template #body-cell-full_name="props">
            <q-td :props="props">
              <div class="row items-center no-wrap">
                <div class="avatar-3d-wrapper q-mr-md print-hide flex flex-center">
                  <q-avatar size="34px" color="grey-2" text-color="grey-7">
                    <img v-if="props.row.profile_picture_url" :src="props.row.profile_picture_url" />
                    <q-icon v-else name="person" size="18px" />
                  </q-avatar>
                </div>
                <div class="text-weight-bold">{{ props.row.full_name }}</div>
              </div>
            </q-td>
          </template>

          <!-- STATUS COLUMN (CENTERED) -->
          <template #body-cell-account_status="props">
            <q-td :props="props" class="text-center">
              <div class="row items-center justify-center">
                <q-select
                  v-model="props.row.account_status"
                  dense
                  borderless
                  emit-value
                  map-options
                  class="status-select-glass print-hide"
                  :class="'status-' + props.row.account_status"
                  :options="statusOptions"
                  @update:model-value="val => updateStatus(props.row.user_id, val)"
                >
                  <template v-slot:selected>
                    <div class="text-weight-bold row items-center no-wrap text-capitalize status-selected-label" @click.stop>
                      <span class="status-indicator-dot q-mr-xs"></span>
                      <span>{{ props.row.account_status }}</span>
                    </div>
                  </template>
                </q-select>
              </div>
              <span class="print-only text-weight-bold text-uppercase" style="display: none;">
                {{ props.row.account_status }}
              </span>
            </q-td>
          </template>

          <!-- LAST ACTIVITY -->
          <template #body-cell-last_activity_at="props">
            <q-td :props="props" class="text-grey-8 text-weight-medium">
              <div class="row items-center no-wrap">
                <q-icon name="schedule" color="red-4" class="q-mr-xs print-hide" size="16px" />
                <span>{{ formatActivity(props.row.last_activity_at) }}</span>
              </div>
            </q-td>
          </template>

          <!-- ACTIONS (CENTERED) -->
          <template #body-cell-actions="props">
            <q-td :props="props" class="text-center">
              <div class="row items-center justify-center no-wrap q-gutter-x-xs">
                <q-btn
                  label="View"
                  icon="visibility"
                  no-caps
                  dense
                  outline
                  class="btn-view-3d q-px-sm print-hide"
                  @click="viewDetails(props.row)"
                />
                <q-btn
                  label="Delete"
                  icon="person_remove"
                  no-caps
                  dense
                  outline
                  class="btn-delete-3d q-px-sm print-hide"
                  @click="confirmDelete(props.row)"
                />
              </div>
            </q-td>
          </template>

          <!-- FIXED NO DATA SLOT (HIDDEN WHEN LOADING) -->
          <template #no-data>
            <div
              v-if="!loading"
              class="full-width column flex-center q-py-xl empty-state-glass"
            >
              <div class="empty-icon-glass q-mb-md">
                <q-icon name="people_outline" color="red-3" size="40px" />
              </div>
              <div class="text-h6 text-weight-bold">No consumers found</div>
              <div class="text-body2 text-grey-6">There are currently no accounts matching your search.</div>
            </div>
          </template>
        </q-table>
      </q-card>
    </div>

    <!-- ================= VIEW DETAILS MODAL ================= -->
    <q-dialog v-model="showViewModal" transition-show="scale" transition-hide="scale">
      <q-card class="consumer-profile-card overflow-hidden">
        <!-- Top Banner Header -->
        <div class="profile-hero-banner relative-position q-pa-lg">
          <div class="banner-awning-strip row no-wrap">
            <span class="awn-red"></span><span class="awn-white"></span>
            <span class="awn-red"></span><span class="awn-white"></span>
            <span class="awn-red"></span><span class="awn-white"></span>
            <span class="awn-red"></span><span class="awn-white"></span>
          </div>

          <div class="banner-mesh-glow"></div>

          <!-- Top Role Tag & Close Button -->
          <div class="row items-center justify-between relative-position z-top">
            <span class="profile-role-chip row items-center no-wrap q-px-sm q-py-xs">
              <q-icon name="shopping_bag" size="13px" class="q-mr-xs text-red-9" />
              <span class="text-caption text-weight-bolder text-red-9 tracking-wider">CONSUMER ACCOUNT</span>
            </span>
            <q-btn icon="close" flat round dense v-close-popup color="white" class="modal-close-btn" />
          </div>

          <!-- Avatar & Centerpiece -->
          <div class="column items-center text-center q-mt-md relative-position z-top">
            <div class="profile-avatar-frame q-mb-sm shadow-sm flex flex-center">
              <q-avatar size="84px" color="white" text-color="red-9">
                <img v-if="viewTarget?.profile_picture_url" :src="viewTarget.profile_picture_url" />
                <q-icon v-else name="person" size="44px" color="red-9" />
              </q-avatar>
            </div>

            <div class="text-h5 text-weight-bolder text-white line-height-tight q-mb-xs text-glow">
              {{ viewTarget?.full_name || 'Consumer Name' }}
            </div>

            <div class="text-caption text-red-1 opacity-90 font-mono">
              ID: #{{ viewTarget?.user_id || '---' }}
            </div>
          </div>
        </div>

        <!-- Profile Detail Cards with Explicit Light/Dark Palette -->
        <q-card-section class="q-pa-lg profile-body-section" v-if="viewTarget">
          <!-- Status Summary Pill -->
          <div class="status-summary-row row items-center justify-between q-pa-md q-mb-md">
            <div class="row items-center no-wrap">
              <div class="status-icon-box q-mr-md flex flex-center" :class="'box-' + viewTarget.account_status">
                <q-icon
                  :name="viewTarget.account_status === 'active' ? 'check_circle' : viewTarget.account_status === 'suspended' ? 'gavel' : 'pause_circle'"
                  size="20px"
                />
              </div>
              <div>
                <div class="modal-label-sub text-caption text-weight-bolder text-uppercase">Account Status</div>
                <div class="modal-value-main text-subtitle2 text-weight-bolder text-capitalize">
                  {{ viewTarget.account_status }}
                </div>
              </div>
            </div>

            <span class="status-indicator-pill" :class="'pill-' + viewTarget.account_status">
              <span class="dot-indicator"></span>
              {{ viewTarget.account_status }}
            </span>
          </div>

          <!-- Metric Cards Grid -->
          <div class="row q-col-gutter-sm">
            <!-- Email -->
            <div class="col-12 col-sm-6">
              <div class="info-metric-box q-pa-md h-full">
                <div class="row items-center no-wrap q-mb-xs">
                  <q-icon name="mail_outline" size="18px" color="red-9" class="q-mr-xs" />
                  <span class="modal-label-sub text-caption text-weight-bolder text-uppercase">Email Address</span>
                </div>
                <div class="modal-value-main text-body2 text-weight-bold ellipsis">
                  {{ viewTarget.email || 'N/A' }}
                </div>
              </div>
            </div>

            <!-- Mobile -->
            <div class="col-12 col-sm-6">
              <div class="info-metric-box q-pa-md h-full">
                <div class="row items-center no-wrap q-mb-xs">
                  <q-icon name="phone_iphone" size="18px" color="red-9" class="q-mr-xs" />
                  <span class="modal-label-sub text-caption text-weight-bolder text-uppercase">Contact Mobile</span>
                </div>
                <div class="modal-value-main text-body2 text-weight-bold font-mono">
                  {{ viewTarget.phone_number || 'N/A' }}
                </div>
              </div>
            </div>

            <!-- Last Activity -->
            <div class="col-12">
              <div class="info-metric-box q-pa-md">
                <div class="row items-center no-wrap q-mb-xs">
                  <q-icon name="schedule" size="18px" color="red-9" class="q-mr-xs" />
                  <span class="modal-label-sub text-caption text-weight-bolder text-uppercase">Last Known Activity</span>
                </div>
                <div class="modal-value-main text-body2 text-weight-bold">
                  {{ formatActivity(viewTarget.last_activity_at) }}
                </div>
              </div>
            </div>
          </div>
        </q-card-section>

        <q-separator class="modal-divider" />

        <!-- Footer Actions -->
        <q-card-actions align="right" class="q-pa-md modal-footer-actions">
          <q-btn
            unelevated
            label="Close Profile"
            color="grey-9"
            no-caps
            class="btn-primary-close q-px-lg text-weight-bolder"
            v-close-popup
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- ================= DELETE CONFIRMATION MODAL ================= -->
    <q-dialog v-model="showDeleteModal" persistent transition-show="scale" transition-hide="scale">
      <q-card class="review-dialog-glass text-center">
        <div class="dialog-bg-glow-red"></div>
        <q-card-section class="q-pt-xl q-pb-md relative-position" style="z-index: 2;">
          <div class="action-icon-3d bg-red-1 text-red-9 q-mb-md q-mx-auto">
            <q-icon name="warning" size="36px" />
          </div>
          <div class="text-h5 text-weight-bold q-mb-sm">Delete Account</div>
          <p class="text-body1 q-px-md opacity-80">
            Are you sure you want to permanently delete <strong>{{ deleteTarget?.full_name }}</strong>? This action cannot be undone.
          </p>
        </q-card-section>

        <q-card-actions align="center" class="q-pa-md dialog-actions-glass">
          <q-btn flat label="Cancel" no-caps class="btn-3d-outline q-px-md q-mr-sm" v-close-popup />
          <q-btn
            unelevated
            label="Confirm Deletion"
            color="red-9"
            no-caps
            class="btn-3d q-px-md"
            :loading="actionLoading"
            @click="handleDelete"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- ================= SUSPEND CONSUMER MODAL ================= -->
    <q-dialog v-model="showSuspendModal" persistent transition-show="scale" transition-hide="scale">
      <q-card class="review-dialog-glass text-center">
        <div class="dialog-bg-glow-red"></div>
        <q-card-section class="q-pt-xl q-pb-md relative-position" style="z-index: 2;">
          <div class="action-icon-3d bg-red-1 text-red-9 q-mb-md q-mx-auto">
            <q-icon name="gavel" size="36px" />
          </div>
          <div class="text-h5 text-weight-bold q-mb-sm">Suspend Consumer</div>
          <p class="text-body2 q-px-md opacity-80">
            Please provide a reason for suspending this consumer account. This message will be shown to the consumer upon login.
          </p>
          <q-input
            v-model="suspensionMessage"
            type="textarea"
            outlined
            dense
            placeholder="e.g., Violation of terms and conditions..."
            class="custom-glass-input text-left q-mt-md"
            autofocus
          />
        </q-card-section>

        <q-card-actions align="center" class="q-pa-md dialog-actions-glass">
          <q-btn flat label="Cancel" no-caps class="btn-3d-outline q-px-md q-mr-sm" @click="cancelSuspension" />
          <q-btn
            unelevated
            label="Confirm Suspension"
            color="red-9"
            no-caps
            class="btn-3d q-px-md"
            :loading="actionLoading"
            @click="confirmSuspension"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { api } from '@/boot/axios'

const $q = useQuasar()

const search = ref('')
const statusFilter = ref(null)
const loading = ref(false)
const actionLoading = ref(false)
const consumers = ref([])
const currentTab = ref('active')

const counts = ref({
  active: 0,
  deleted: 0
})

// View Modal Refs
const showViewModal = ref(false)
const viewTarget = ref(null)

// Delete Modal Refs
const showDeleteModal = ref(false)
const deleteTarget = ref(null)

// Suspend Modal Refs
const showSuspendModal = ref(false)
const suspensionTarget = ref(null)
const suspensionMessage = ref('')
const originalStatus = ref('')

const statusFilterOptions = [
  { label: 'All Statuses', value: null },
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
  { label: 'Suspended', value: 'suspended' }
]

const statusOptions = [
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
  { label: 'Suspended', value: 'suspended' }
]

const columns = [
  { name: 'full_name', label: 'NAME', field: 'full_name', align: 'left', sortable: true },
  { name: 'email', label: 'EMAIL', field: 'email', align: 'left', sortable: true },
  { name: 'phone_number', label: 'PHONE', field: row => row.phone_number || 'N/A', align: 'left' },
  { name: 'last_activity_at', label: 'LAST ACTIVITY', field: 'last_activity_at', align: 'left', sortable: true },
  { 
    name: 'account_status', 
    label: 'STATUS', 
    field: 'account_status', 
    align: 'center',
    headerClasses: 'text-center'
  },
  { 
    name: 'actions', 
    label: 'ACTIONS', 
    field: 'actions', 
    align: 'center', 
    classes: 'print-hide', 
    headerClasses: 'print-hide text-center' 
  }
]

const fetchConsumers = async () => {
  try {
    loading.value = true
    const params = {
      tab: currentTab.value,
      search: search.value || undefined,
      status: statusFilter.value || undefined
    }
    const res = await api.get('/admin/consumers', { params })
    consumers.value = res.data.data || res.data || []

    if (res.data.counts) {
      counts.value.active = res.data.counts.active || 0
      counts.value.deleted = res.data.counts.deleted || 0
    } else {
      if (currentTab.value === 'active') {
        counts.value.active = consumers.value.length
      } else {
        counts.value.deleted = consumers.value.length
      }
    }
  } catch (error) {
    console.error('Error fetching consumers:', error)
  } finally {
    loading.value = false
  }
}

const updateStatus = async (userId, newStatus) => {
  if (newStatus === 'suspended') {
    suspensionTarget.value = userId
    const consumer = consumers.value.find(c => c.user_id === userId)
    if (consumer) {
      originalStatus.value = consumer.account_status
    }
    suspensionMessage.value = ''
    showSuspendModal.value = true
    return
  }

  try {
    await api.patch(`/admin/consumers/${userId}/status`, {
      account_status: newStatus
    })
    $q.notify({ type: 'positive', message: 'Consumer status updated.', position: 'top-right' })
  } catch {
    fetchConsumers()
  }
}

const cancelSuspension = () => {
  showSuspendModal.value = false
  suspensionTarget.value = null
  fetchConsumers()
}

const confirmSuspension = async () => {
  if (!suspensionMessage.value) return

  actionLoading.value = true
  try {
    await api.patch(`/admin/consumers/${suspensionTarget.value}/status`, {
      account_status: 'suspended',
      suspension_message: suspensionMessage.value
    })
    showSuspendModal.value = false
    suspensionTarget.value = null
    fetchConsumers()
    $q.notify({ type: 'positive', message: 'Consumer suspended.', position: 'top-right' })
  } catch {
    fetchConsumers()
  } finally {
    actionLoading.value = false
  }
}

const viewDetails = (row) => {
  viewTarget.value = row
  showViewModal.value = true
}

const confirmDelete = (row) => {
  deleteTarget.value = row
  showDeleteModal.value = true
}

const handleDelete = async () => {
  if (!deleteTarget.value) return

  actionLoading.value = true
  try {
    await api.delete(`/admin/consumers/${deleteTarget.value.user_id}`)
    consumers.value = consumers.value.filter(c => c.user_id !== deleteTarget.value.user_id)
    showDeleteModal.value = false
    $q.notify({ type: 'positive', message: 'Consumer permanently deleted.', position: 'top-right' })
    fetchConsumers()
  } catch (error) {
    console.error('Error deleting consumer:', error)
    $q.notify({ type: 'negative', message: 'Failed to delete consumer.', position: 'top-right' })
  } finally {
    actionLoading.value = false
  }
}

const formatActivity = (timestamp) => {
  if (!timestamp) return 'Never'

  const now = new Date()
  const then = new Date(timestamp)
  const diff = Math.floor((now - then) / 1000)

  if (diff < 60) return 'Just now'
  if (diff < 3600) return `${Math.floor(diff / 60)} mins ago`
  if (diff < 86400) return `${Math.floor(diff / 3600)} hours ago`
  if (diff < 604800) return `${Math.floor(diff / 86400)} days ago`

  return then.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const handleExport = async () => {
  if (isExporting.value) return
  isExporting.value = true
  try {
    const response = await api.get('/admin/consumers/export', {
      params: {
        tab: currentTab.value,
        search: search.value,
        status: statusFilter.value
      },
      responseType: 'blob'
    })
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    const dateStr = new Date().toISOString().split('T')[0]
    link.setAttribute('download', `Tindahan_Admin_Consumers_Export_${dateStr}.pdf`)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    $q.notify({ type: 'positive', message: 'Report exported successfully.', color: 'green-7' })
  } catch (err) {
    console.error('Export failed:', err)
    $q.notify({ type: 'negative', message: 'Failed to export report. Please try again.', color: 'red-7' })
  } finally {
    isExporting.value = false
  }
}

onMounted(() => {
  fetchConsumers()
})
</script>

<style>
@media print {
  .q-drawer, .q-header, .q-footer, .q-drawer-container { display: none !important; }
  .q-page-container { padding: 0 !important; }
  .q-table__middle { overflow: visible !important; }
  .q-page { min-height: auto !important; }
}
</style>

<style scoped>
/* ==========================================================
   PAGE BASE
========================================================== */
.admin-page {
  background-color: #f1f5f9;
  min-height: 100vh;
  overflow-x: hidden;
  position: relative;
}

.page-container {
  max-width: 1440px;
  margin: 0 auto;
  padding: 32px 40px;
}

.tracking-wide { letter-spacing: 0.08em; }
.tracking-wider { letter-spacing: 0.05em; }
.line-height-tight { line-height: 1.2; }
.opacity-80 { opacity: 0.8; }
.opacity-90 { opacity: 0.9; }
.gap-md { gap: 16px; }
.flex-1 { flex: 1; }
.font-mono { font-family: 'SFMono-Regular', Consolas, Menlo, monospace; }
.text-dark { color: #0f172a !important; }
.shadow-xs { box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05); }

/* ==========================================================
   PAGE BANNER & MATCHED TITLE TYPOGRAPHY
========================================================== */
.consumer-mgmt-header {
  background: linear-gradient(90deg, #dc2626 0%, #b91c1c 45%, #7f1d1d 100%);
  border-radius: 20px;
  box-shadow: 0 10px 30px rgba(185, 28, 28, 0.22);
  position: relative;
  overflow: hidden;
}

.header-main-title {
  font-size: 2.15rem;
  letter-spacing: -0.025em;
}

.header-bg-glow {
  position: absolute;
  top: -50%;
  right: -10%;
  width: 400px;
  height: 400px;
  background: radial-gradient(circle, rgba(255, 255, 255, 0.15) 0%, transparent 70%);
  border-radius: 50%;
  pointer-events: none;
}

.header-white-block {
  width: 72px;
  height: 72px;
  border-radius: 18px;
  background: #ffffff;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
  z-index: 2;
}

.pulse-ring {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 100%;
  height: 100%;
  border-radius: 18px;
  border: 2px solid rgba(255, 255, 255, 0.6);
  animation: pulse-animation 2.5s infinite cubic-bezier(0.4, 0, 0.2, 1);
  pointer-events: none;
}

@keyframes pulse-animation {
  0% { transform: translate(-50%, -50%) scale(1); opacity: 0.8; }
  100% { transform: translate(-50%, -50%) scale(1.4); opacity: 0; }
}

.badge-dark-capsule {
  display: inline-flex;
  align-items: center;
  background: rgba(0, 0, 0, 0.35);
  color: #ffffff;
  padding: 6px 14px;
  border-radius: 24px;
  font-size: 11px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  border: 1px solid rgba(255, 255, 255, 0.15);
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.12);
}

.header-stat-box {
  background: rgba(0, 0, 0, 0.22);
  border: 1px solid rgba(255, 255, 255, 0.18);
  border-radius: 14px;
  padding: 12px 24px;
  min-width: 150px;
}

.stat-box-label {
  font-size: 10px;
  letter-spacing: 0.08em;
  color: #fecaca;
  line-height: 1;
}

.stat-box-value {
  font-size: 28px;
  font-weight: 900;
  color: #ffffff;
  line-height: 1;
}

.stat-box-unit {
  font-size: 11px;
  color: #fecaca;
  font-weight: 600;
}

/* ==========================================================
   GLASSMORPHISM CORE & TABLES
========================================================== */
.glass-toolbar {
  background: rgba(255, 255, 255, 0.65);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border: 1px solid rgba(255, 255, 255, 0.8);
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
}

.glass-card {
  background: rgba(255, 255, 255, 0.75);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.85);
  border-radius: 20px;
  box-shadow: 0 12px 40px rgba(0, 0, 0, 0.03);
}

.table-glass-container {
  overflow: hidden;
}

.panel-header {
  background: rgba(255, 255, 255, 0.5);
  border-bottom: 1px solid rgba(255, 255, 255, 0.7);
}

:deep(.custom-glass-table) {
  background: transparent;
}

:deep(.custom-glass-table thead tr th) {
  background: #fdf2f2;
  font-weight: 800;
  color: #991b1b;
  text-transform: uppercase;
  font-size: 11px;
  letter-spacing: 0.06em;
  padding: 14px 20px;
  border-bottom: 1.5px solid #fee2e2;
}

:deep(.custom-glass-table tbody td) {
  padding: 16px 20px;
  font-size: 13.5px;
}

:deep(.interactive-table tbody tr) {
  cursor: pointer;
}

.table-loading-wrapper {
  background: transparent;
}

.empty-state-glass {
  background: transparent;
}

.empty-icon-glass {
  padding: 20px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.8);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.9);
}

/* 3D Avatar */
.avatar-3d-wrapper {
  border-radius: 50%;
  padding: 2px;
  background: #ffffff;
  border: 1px solid rgba(226, 232, 240, 0.8);
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
}

/* STATUS SELECT PILL (CENTERED & COMPACT) */
.status-select-glass {
  width: 110px;
  margin: 0 auto;
}

.status-select-glass :deep(.q-field__control) {
  border-radius: 999px;
  padding: 0 10px 0 12px !important;
  height: 28px !important;
  min-height: 28px !important;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(0, 0, 0, 0.08);
}

.status-select-glass :deep(.q-field__control-container) {
  padding-top: 0 !important;
  display: flex;
  align-items: center;
  justify-content: center;
}

.status-select-glass :deep(.q-field__native) {
  font-size: 11.5px;
  font-weight: 700;
  min-height: auto;
  padding: 0 !important;
  display: flex;
  align-items: center;
  justify-content: center;
}

.status-select-glass :deep(.q-field__append) {
  padding-left: 2px !important;
  min-width: auto;
  height: 100%;
}

.status-select-glass :deep(.q-field__append .q-icon) {
  font-size: 14px;
}

.status-selected-label {
  display: flex;
  align-items: center;
  justify-content: center;
  line-height: 1;
}

.status-indicator-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  flex-shrink: 0;
}

.status-active :deep(.q-field__control) { background: #f0fdf4; border-color: #bbf7d0; }
.status-active :deep(.q-field__native) { color: #15803d; }
.status-active .status-indicator-dot { background-color: #16a34a; }

.status-inactive :deep(.q-field__control) { background: #f8fafc; border-color: #e2e8f0; }
.status-inactive :deep(.q-field__native) { color: #64748b; }
.status-inactive .status-indicator-dot { background-color: #94a3b8; }

.status-suspended :deep(.q-field__control) { background: #fef2f2; border-color: #fecaca; }
.status-suspended :deep(.q-field__native) { color: #b91c1c; }
.status-suspended .status-indicator-dot { background-color: #dc2626; }

/* Action Buttons */
.search-input-glass { max-width: 320px; width: 100%; }
.filter-select-glass { width: 180px; }

.btn-delete-3d {
  border-radius: 8px !important;
  font-weight: 700;
  background: #ffffff !important;
  color: #dc2626 !important;
  border: 1px solid #fca5a5 !important;
}
.btn-delete-3d:hover { background: #fef2f2 !important; }

.btn-view-3d {
  border-radius: 8px !important;
  font-weight: 700;
  background: #ffffff !important;
  color: #475569 !important;
  border: 1px solid #cbd5e1 !important;
}
.btn-view-3d:hover { background: #f8fafc !important; }

.btn-glass {
  border-radius: 10px !important;
  font-weight: 600;
  background: #ffffff !important;
  border: 1px solid #e2e8f0 !important;
}

/* ==========================================================
   REVITALIZED CONSUMER PROFILE MODAL AESTHETICS
========================================================== */
.consumer-profile-card {
  width: 480px;
  max-width: 95vw;
  border-radius: 20px !important;
  background: #ffffff;
  border: 1.5px solid #e2e8f0;
  box-shadow: 0 25px 50px rgba(15, 23, 42, 0.15) !important;
}

.profile-hero-banner {
  background: linear-gradient(135deg, #dc2626 0%, #b91c1c 50%, #7f1d1d 100%);
  position: relative;
  overflow: hidden;
  padding-top: 24px;
  padding-bottom: 24px;
}

.banner-awning-strip {
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 4px;
  display: flex;
}
.banner-awning-strip span { flex: 1; }
.awn-red { background: #991b1b; }
.awn-white { background: #fee2e2; }

.banner-mesh-glow {
  position: absolute;
  top: -40px; right: -40px;
  width: 200px; height: 200px;
  background: radial-gradient(circle, rgba(255, 255, 255, 0.2) 0%, transparent 70%);
  border-radius: 50%;
  pointer-events: none;
}

.profile-role-chip {
  background: #ffffff;
  border: 1px solid #fee2e2;
  border-radius: 999px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

.modal-close-btn {
  background: rgba(0, 0, 0, 0.15);
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.profile-avatar-frame {
  padding: 4px;
  background: rgba(255, 255, 255, 0.25);
  border-radius: 50%;
  backdrop-filter: blur(8px);
  border: 2px solid rgba(255, 255, 255, 0.6);
}

.text-glow {
  text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
}

/* Modal Inner Data Containers */
.status-summary-row {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
}

.status-icon-box {
  width: 42px;
  height: 42px;
  border-radius: 10px;
}
.box-active { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
.box-suspended { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
.box-inactive { background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }

.status-indicator-pill {
  display: inline-flex;
  align-items: center;
  padding: 4px 12px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 800;
  text-transform: uppercase;
}

.pill-active { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
.pill-suspended { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
.pill-inactive { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }

.dot-indicator {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: currentColor;
  margin-right: 6px;
}

.info-metric-box {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  transition: all 0.2s ease;
}

.modal-label-sub {
  color: #64748b;
}

.modal-value-main {
  color: #0f172a;
}

.modal-divider {
  border-color: #e2e8f0;
}

.modal-footer-actions {
  background: #f8fafc;
}

.btn-primary-close {
  border-radius: 8px !important;
  font-size: 13px;
}

/* ==========================================================
   OTHER MODALS
========================================================== */
.review-dialog-glass {
  width: 550px;
  max-width: 95vw;
  border-radius: 24px !important;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(30px);
  -webkit-backdrop-filter: blur(30px);
  border: 1px solid rgba(255, 255, 255, 1);
  box-shadow: 0 25px 50px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.dialog-bg-glow-red {
  position: absolute;
  top: -80px;
  left: 50%;
  transform: translateX(-50%);
  width: 300px;
  height: 300px;
  background: radial-gradient(circle, rgba(220, 38, 38, 0.15) 0%, transparent 60%);
  border-radius: 50%;
  z-index: 1;
  pointer-events: none;
}

.dialog-actions-glass {
  background: rgba(255, 255, 255, 0.85);
  border-top: 1px solid rgba(255, 255, 255, 1);
}

.custom-glass-input :deep(.q-field__control) {
  background: rgba(255, 255, 255, 0.9);
  border-radius: 10px;
}

.action-icon-3d {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 72px;
  height: 72px;
  border-radius: 50%;
  background: #ffffff;
  border: 1px solid rgba(255, 255, 255, 1);
  box-shadow: 0 8px 24px rgba(220, 38, 38, 0.15);
}

/* ==========================================================
   DARK MODE OVERRIDES SCOPED TO MANAGE CONSUMERS
========================================================== */
body.body--dark {
  .consumer-profile-card {
    background: #0f172a !important;
    border-color: rgba(255, 255, 255, 0.12) !important;
  }

  .status-summary-row,
  .info-metric-box {
    background: #1e293b !important;
    border-color: rgba(255, 255, 255, 0.08) !important;
  }

  .modal-label-sub {
    color: #94a3b8 !important;
  }

  .modal-value-main {
    color: #f8fafc !important;
  }

  .modal-divider {
    border-color: rgba(255, 255, 255, 0.08) !important;
  }

  .modal-footer-actions {
    background: #162032 !important;
  }

  .avatar-3d-wrapper {
    background: #1e293b !important;
    border-color: rgba(255, 255, 255, 0.12) !important;
  }

  .avatar-3d-wrapper :deep(.q-avatar) {
    background: #334155 !important;
    color: #cbd5e1 !important;
  }

  .status-active :deep(.q-field__control) {
    background: rgba(22, 163, 74, 0.18) !important;
    border-color: rgba(34, 197, 94, 0.35) !important;
  }
  .status-active :deep(.q-field__native) {
    color: #86efac !important;
  }

  .status-inactive :deep(.q-field__control) {
    background: rgba(148, 163, 184, 0.15) !important;
    border-color: rgba(148, 163, 184, 0.25) !important;
  }
  .status-inactive :deep(.q-field__native) {
    color: #cbd5e1 !important;
  }

  .status-suspended :deep(.q-field__control) {
    background: rgba(239, 68, 68, 0.18) !important;
    border-color: rgba(239, 68, 68, 0.35) !important;
  }
  .status-suspended :deep(.q-field__native) {
    color: #fca5a5 !important;
  }

  .btn-view-3d {
    background: rgba(30, 41, 59, 0.8) !important;
    color: #94a3b8 !important;
    border-color: rgba(255, 255, 255, 0.12) !important;
  }
  .btn-view-3d:hover {
    background: rgba(51, 65, 85, 0.9) !important;
    color: #ffffff !important;
  }

  .btn-delete-3d {
    background: rgba(185, 28, 28, 0.15) !important;
    border-color: rgba(239, 68, 68, 0.3) !important;
    color: #fca5a5 !important;
  }
  .btn-delete-3d:hover {
    background: rgba(185, 28, 28, 0.3) !important;
  }
}

@media (max-width: 768px) {
  .page-container { padding: 20px 16px; }
  .consumer-mgmt-header { flex-direction: column; align-items: flex-start; }
  .header-stat-box { margin-top: 14px; width: 100%; }
}

@media (max-width: 600px) {
  .toolbar { flex-direction: column; align-items: stretch; gap: 12px; }
  .search-input-glass, .filter-select-glass { max-width: 100%; width: 100%; }
  .export-btn { margin-left: 0; width: 100%; }
}
</style>