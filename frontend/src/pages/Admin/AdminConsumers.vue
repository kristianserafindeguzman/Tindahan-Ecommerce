<template>
  <q-page class="admin-page">
    <div class="page-container">

      <!-- ================= PAGE BANNER (Frosted Pearl & Ruby Network Theme) ================= -->
      <div class="consumer-mgmt-header q-mb-lg row items-center justify-between q-pa-lg relative-position">
        <!-- Abstract Network Background Elements -->
        <div class="network-node node-1"></div>
        <div class="network-node node-2"></div>
        <div class="network-node node-3"></div>
        
        <div class="row items-center col-12 col-md-8 relative-position z-top">
          <!-- 3D Tactile Icon Box (Pearl) -->
          <div class="header-icon-box-3d q-mr-lg flex flex-center relative-position">
            <q-icon name="people_alt" size="36px" color="red-8" />
            <div class="pulse-ring-light"></div>
          </div>
          
          <div>
            <div class="q-mb-sm flex items-center">
              <span class="badge-light-glass">
                <q-icon name="admin_panel_settings" size="14px" class="q-mr-xs text-red-7" />
                Consumer Administration
              </span>
            </div>
            
            <h1 class="text-h4 text-weight-bolder text-dark q-mt-none q-mb-xs line-height-tight">
              Manage Consumers
            </h1>
            
            <div class="text-grey-7 row items-center text-body2 text-weight-medium">
              View consumer accounts, update access levels, and monitor platform interactions.
            </div>
          </div>
        </div>

        <!-- Quick Insights Widget -->
        <div class="col-12 col-md-4 text-right q-mt-md q-md-mt-none flex justify-end relative-position z-top">
          <div class="header-stats-pill q-pa-md row items-center">
            <div class="stat-icon-wrapper q-mr-md bg-red-1 text-red-8">
              <q-icon name="groups" size="24px" />
            </div>
            <div class="text-left">
              <div class="text-caption text-grey-6 text-weight-bold text-uppercase">Total Users</div>
              <div class="text-h6 text-weight-bolder text-dark line-height-tight">{{ consumers.length }} {{ currentTab === 'deleted' ? 'Deleted' : 'Active' }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- ================= TOOLBAR (Glass) ================= -->
      <div class="toolbar glass-toolbar q-pa-md q-mb-md row items-center justify-between">
        <div class="row items-center gap-md flex-1">
          <q-input
            v-model="search"
            outlined
            dense
            placeholder="Search by name or email..."
            class="search-input-glass"
            @update:model-value="fetchConsumers"
          >
            <template #prepend>
              <q-icon name="search" color="red-8" />
            </template>
          </q-input>
        </div>

        <q-btn
          label="Export List"
          no-caps
          outline
          icon="print"
          class="btn-3d-outline export-btn q-ml-auto"
          color="red-8"
          @click="handleExport"
        />
      </div>

      <!-- ================= MAIN CONTENT AREA (Glass Card) ================= -->
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
            <q-tab name="active" label="Active Accounts" class="text-weight-bold" />
            <q-tab name="deleted" label="Deleted Accounts" class="text-weight-bold" />
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
          no-data-label="No consumers found"
        >
          <template #loading>
            <q-inner-loading showing color="red-8" class="bg-transparent">
              <q-spinner-dots size="40px" />
            </q-inner-loading>
          </template>

          <!-- AVATAR & NAME COLUMN -->
          <template #body-cell-full_name="props">
            <q-td :props="props">
              <div class="row items-center no-wrap">
                <div class="avatar-3d-wrapper q-mr-md print-hide">
                  <q-avatar size="36px" color="grey-2" text-color="grey-7">
                    <img v-if="props.row.profile_picture_url" :src="props.row.profile_picture_url" />
                    <q-icon v-else name="person" size="20px" />
                  </q-avatar>
                </div>
                <div class="text-weight-bold text-dark">{{ props.row.full_name }}</div>
              </div>
            </q-td>
          </template>

          <!-- STATUS COLUMN -->
          <template #body-cell-account_status="props">
            <q-td :props="props">
              <!-- Web View: Interactive Dropdown -->
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
                  <div class="text-weight-bold" @click.stop>
                    {{ props.row.account_status.charAt(0).toUpperCase() + props.row.account_status.slice(1) }}
                  </div>
                </template>
              </q-select>
              <!-- Print View: Plain Text -->
              <span class="print-only text-weight-bold text-uppercase" style="display: none;">
                {{ props.row.account_status }}
              </span>
            </q-td>
          </template>

          <!-- LAST ACTIVITY -->
          <template #body-cell-last_activity_at="props">
            <q-td :props="props" class="text-grey-8 text-weight-medium">
              <q-icon name="schedule" color="grey-5" class="q-mr-xs print-hide" size="16px"/>
              {{ formatActivity(props.row.last_activity_at) }}
            </q-td>
          </template>

          <!-- ACTIONS -->
          <template #body-cell-actions="props">
            <q-td :props="props" align="right">
              <q-btn
                label="View"
                icon="visibility"
                no-caps
                dense
                outline
                class="btn-view-3d q-px-sm q-mr-sm print-hide"
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
            </q-td>
          </template>

          <template #no-data>
            <div class="full-width column flex-center q-py-xl empty-state-glass">
              <div class="empty-icon-3d q-mb-md">
                <q-icon name="person_off" color="grey-6" size="40px" />
              </div>
              <div class="text-h6 text-weight-bold text-grey-8">No consumers found</div>
              <div class="text-body2 text-grey-6">There are currently no accounts matching your search.</div>
            </div>
          </template>
        </q-table>
      </q-card>

    </div>
    
    <!-- ================= VIEW DETAILS MODAL ================= -->
    <q-dialog v-model="showViewModal" transition-show="fade" transition-hide="fade">
      <q-card class="review-dialog-glass q-pa-sm" style="min-width: 400px; max-width: 90vw;">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6 text-weight-bold text-dark">Consumer Profile</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup color="grey-7" />
        </q-card-section>

        <q-card-section class="q-pt-md" v-if="viewTarget">
          <div class="column items-center q-mb-lg">
            <div class="avatar-3d-wrapper q-mb-md" style="padding: 4px;">
              <q-avatar size="80px" color="grey-2" text-color="grey-7">
                <img v-if="viewTarget.profile_picture_url" :src="viewTarget.profile_picture_url" />
                <q-icon v-else name="person" size="40px" />
              </q-avatar>
            </div>
            <div class="text-h5 text-weight-bold text-dark line-height-tight">{{ viewTarget.full_name }}</div>
            <div class="text-body2 text-grey-6 q-mt-xs">{{ viewTarget.email }}</div>
          </div>
          
          <q-list class="glass-list q-px-sm">
            <q-item>
              <q-item-section avatar>
                <q-icon color="grey-6" name="phone" />
              </q-item-section>
              <q-item-section>
                <q-item-label class="text-grey-7 text-caption">Phone Number</q-item-label>
                <q-item-label class="text-weight-medium">{{ viewTarget.phone_number || 'N/A' }}</q-item-label>
              </q-item-section>
            </q-item>
            
            <q-item>
              <q-item-section avatar>
                <q-icon color="grey-6" name="info" />
              </q-item-section>
              <q-item-section>
                <q-item-label class="text-grey-7 text-caption">Account Status</q-item-label>
                <q-item-label class="text-weight-bold text-uppercase" :class="'text-' + (viewTarget.account_status === 'active' ? 'green-6' : viewTarget.account_status === 'suspended' ? 'orange-6' : 'grey-6')">
                  {{ viewTarget.account_status }}
                </q-item-label>
              </q-item-section>
            </q-item>

            <q-item>
              <q-item-section avatar>
                <q-icon color="grey-6" name="schedule" />
              </q-item-section>
              <q-item-section>
                <q-item-label class="text-grey-7 text-caption">Last Activity</q-item-label>
                <q-item-label class="text-weight-medium">{{ formatActivity(viewTarget.last_activity_at) }}</q-item-label>
              </q-item-section>
            </q-item>
          </q-list>
        </q-card-section>
        
        <q-card-actions align="right" class="q-pa-md">
          <q-btn flat label="Close" color="grey-8" no-caps class="btn-3d-outline q-px-md" v-close-popup />
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
          <div class="text-h5 text-weight-bold text-dark q-mb-sm">Delete Account</div>
          <p class="text-body1 text-grey-7 q-px-md">
            Are you sure you want to permanently delete <strong>{{ deleteTarget?.full_name }}</strong>? This action cannot be undone.
          </p>
        </q-card-section>
        
        <q-card-actions align="center" class="q-pa-md dialog-actions-glass">
          <q-btn flat label="Cancel" color="grey-8" no-caps class="btn-3d-outline q-px-md q-mr-sm" v-close-popup />
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
          <div class="text-h5 text-weight-bold text-dark q-mb-sm">Suspend Consumer</div>
          <p class="text-body2 text-grey-7 q-px-md">
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
          <q-btn flat label="Cancel" color="grey-8" no-caps class="btn-3d-outline q-px-md q-mr-sm" @click="cancelSuspension" />
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
import { ref, computed, onMounted } from 'vue'
import { api } from '@/boot/axios'

const search = ref('')
const loading = ref(false)
const actionLoading = ref(false)
const consumers = ref([])
const currentTab = ref('active')

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

const statusOptions = [
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
  { label: 'Suspended', value: 'suspended' },
]

const columns = [
  { name: 'full_name', label: 'Name', field: 'full_name', align: 'left', sortable: true },
  { name: 'email', label: 'Email', field: 'email', align: 'left', sortable: true },
  { name: 'phone_number', label: 'Mobile', field: 'phone_number', align: 'left' },
  { name: 'last_activity_at', label: 'Last Activity', field: 'last_activity_at', align: 'left', sortable: true },
  { name: 'account_status', label: 'Status', field: 'account_status', align: 'left' },
  { name: 'actions', label: '', field: 'actions', align: 'right', classes: 'print-hide', headerClasses: 'print-hide' },
]



const fetchConsumers = async () => {
  try {
    loading.value = true
    const params = {
      tab: currentTab.value,
      search: search.value
    }
    const res = await api.get('/admin/consumers', { params })
    consumers.value = res.data
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
  } catch {
    fetchConsumers() // Revert on failure
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
  } catch {
    fetchConsumers()
  } finally {
    actionLoading.value = false
  }
}

// View Logic
const viewDetails = (row) => {
  viewTarget.value = row
  showViewModal.value = true
}

// Delete Logic
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
  } catch (error) {
    console.error('Error deleting consumer:', error)
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

const handleExport = () => {
  window.print()
}

onMounted(() => {
  fetchConsumers()
})
</script>

<!-- Global unscoped style block for print fixes -->
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
   AMBIENT BACKGROUND - Clean Pearl Base
========================================================== */
.admin-page {
  background-color: #f4f7f9; 
  background-image: 
    radial-gradient(circle at 15% 10%, rgba(220, 38, 38, 0.03) 0%, transparent 400px),
    radial-gradient(circle at 85% 90%, rgba(153, 27, 27, 0.02) 0%, transparent 500px);
  background-attachment: fixed;
  min-height: 100vh;
  font-family: 'Roboto', Arial, sans-serif;
  overflow-x: hidden;
}

.page-container {
  max-width: 1440px;
  margin: 0 auto;
  padding: 32px 40px;
}

.tracking-wide { letter-spacing: 0.08em; }
.line-height-tight { line-height: 1.2; }
.z-top { z-index: 2; }
.gap-md { gap: 16px; }
.flex-1 { flex: 1; }
.text-dark { color: #0f172a !important; }

/* ==========================================================
   FROSTED PEARL HEADER (Dynamic & Bright)
========================================================== */
.consumer-mgmt-header {
  background: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(20px);
  border-radius: 18px;
  box-shadow: 
    0 15px 35px rgba(0, 0, 0, 0.05),
    inset 0 1px 2px rgba(255, 255, 255, 1);
  border: 1px solid rgba(255, 255, 255, 0.9);
  overflow: hidden;
}

.network-node {
  position: absolute;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(239, 68, 68, 0.1) 0%, transparent 70%);
  pointer-events: none;
  z-index: 1;
}
.node-1 { width: 300px; height: 300px; top: -100px; left: -50px; }
.node-2 { width: 200px; height: 200px; bottom: -80px; right: 20%; background: radial-gradient(circle, rgba(220, 38, 38, 0.05) 0%, transparent 70%); }
.node-3 { width: 400px; height: 400px; top: -150px; right: -100px; }

.header-icon-box-3d {
  width: 72px;
  height: 72px;
  border-radius: 16px;
  background: linear-gradient(135deg, #ffffff 0%, #fef2f2 100%);
  box-shadow: 
    8px 8px 20px rgba(0, 0, 0, 0.08),
    inset 2px 2px 4px rgba(255, 255, 255, 1);
  border: 1px solid rgba(254, 226, 226, 0.8);
}

.pulse-ring-light {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 100%;
  height: 100%;
  border-radius: 16px;
  border: 2px solid rgba(239, 68, 68, 0.2);
  animation: pulse-animation-light 2s infinite;
  pointer-events: none;
}

@keyframes pulse-animation-light {
  0% { transform: translate(-50%, -50%) scale(1); opacity: 1; }
  100% { transform: translate(-50%, -50%) scale(1.4); opacity: 0; }
}

.badge-light-glass {
  display: inline-flex;
  align-items: center;
  background: rgba(239, 68, 68, 0.08);
  color: #dc2626;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  border: 1px solid rgba(239, 68, 68, 0.15);
}

.header-stats-pill {
  background: #ffffff;
  border-radius: 14px;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.04), inset 0 1px 1px rgba(255, 255, 255, 1);
  border: 1px solid rgba(241, 245, 249, 1);
  min-width: 220px;
}

.stat-icon-wrapper {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 48px;
  height: 48px;
  border-radius: 12px;
  box-shadow: inset 1px 1px 3px rgba(255, 255, 255, 0.8), 2px 2px 8px rgba(0,0,0,0.05);
}

/* ==========================================================
   GLASSMORPHISM CORE & TINTED TABLE
========================================================== */
.glass-toolbar {
  background: rgba(255, 255, 255, 0.6);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.5);
  border-radius: 12px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
}

.glass-card {
  background: rgba(255, 255, 255, 0.75);
  backdrop-filter: blur(16px);
  border: 1px solid rgba(255, 255, 255, 0.6);
  border-top: 1px solid rgba(255, 255, 255, 1);
  border-left: 1px solid rgba(255, 255, 255, 1);
  border-radius: 18px;
  box-shadow: 
    0 10px 30px rgba(0, 0, 0, 0.04),
    inset 0 -2px 5px rgba(255, 255, 255, 0.3);
}

.table-glass-container { overflow: hidden; }

.panel-header {
  background: linear-gradient(90deg, rgba(254, 242, 242, 0.6) 0%, transparent 100%);
  border-bottom: 1px solid rgba(255, 255, 255, 0.5);
}

/* ==========================================================
   TABLE & INTERACTIVE ELEMENTS
========================================================== */
:deep(.custom-glass-table) { background: transparent; }

:deep(.custom-glass-table thead tr th) {
  background: rgba(220, 38, 38, 0.04);
  backdrop-filter: blur(4px);
  font-weight: 800;
  color: #64748b;
  text-transform: uppercase;
  font-size: 12px;
  letter-spacing: 0.05em;
  padding: 16px 24px;
  border-bottom: 1px solid rgba(220, 38, 38, 0.1);
}

:deep(.custom-glass-table tbody td) {
  padding: 16px 24px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.03);
  font-size: 14px;
  color: #1e293b;
}

:deep(.interactive-table tbody tr) {
  transition: all 0.2s ease;
}
:deep(.interactive-table tbody tr:hover) {
  background-color: rgba(255, 255, 255, 0.95);
  transform: scale(1.001);
  box-shadow: 0 4px 15px rgba(0,0,0,0.03);
  z-index: 2;
  position: relative;
}

/* 3D AVATAR */
.avatar-3d-wrapper {
  border-radius: 50%;
  padding: 2px;
  background: linear-gradient(135deg, #ffffff, #f1f5f9);
  box-shadow: 3px 3px 6px rgba(0,0,0,0.08), -3px -3px 6px rgba(255,255,255,1);
}

.empty-state-glass { background: rgba(255, 255, 255, 0.3); }
.empty-icon-3d {
  padding: 16px;
  border-radius: 50%;
  background: linear-gradient(135deg, #f8fafc, #f1f5f9);
  box-shadow: 4px 4px 10px rgba(0,0,0,0.05), -4px -4px 10px rgba(255,255,255,0.8), inset 1px 1px 3px rgba(255,255,255,1);
}

/* STATUS SELECT */
.status-select-glass { width: 120px; }
.status-select-glass :deep(.q-field__control) {
  background: rgba(255,255,255,0.8);
  border-radius: 6px;
  padding: 0 8px;
  box-shadow: inset 1px 1px 3px rgba(0,0,0,0.05);
}
.status-select-glass :deep(.q-field__native) { font-size: 12px; font-weight: 700; }
.status-active :deep(.q-field__native) { color: #16a34a; }
.status-inactive :deep(.q-field__native) { color: #9ca3af; }
.status-suspended :deep(.q-field__native) { color: #f59e0b; }

/* ==========================================================
   INPUTS & BUTTONS (3D)
========================================================== */
.search-input-glass { max-width: 320px; width: 100%; }
.search-input-glass :deep(.q-field__control) {
  background: rgba(255, 255, 255, 0.8);
  backdrop-filter: blur(8px);
  border-radius: 8px;
  box-shadow: inset 1px 1px 3px rgba(0,0,0,0.05);
}

.btn-3d {
  border-radius: 8px !important;
  font-weight: 600;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
  transition: all 0.2s ease;
}
.btn-3d:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2); }
.btn-3d:active { transform: translateY(1px); box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); }

.btn-delete-3d {
  border-radius: 8px !important;
  font-weight: 700;
  background: rgba(255, 255, 255, 0.9) !important;
  color: #dc2626 !important;
  border: 1px solid #fca5a5 !important;
  box-shadow: 0 2px 5px rgba(220, 38, 38, 0.1);
  transition: all 0.2s ease;
}
.btn-delete-3d:hover { background: #fef2f2 !important; transform: translateY(-1px); box-shadow: 0 4px 8px rgba(220, 38, 38, 0.15); }

.btn-view-3d {
  border-radius: 8px !important;
  font-weight: 700;
  background: rgba(255, 255, 255, 0.9) !important;
  color: #475569 !important;
  border: 1px solid #cbd5e1 !important;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
  transition: all 0.2s ease;
}
.btn-view-3d:hover { background: #f8fafc !important; transform: translateY(-1px); box-shadow: 0 4px 8px rgba(0, 0, 0, 0.08); }

.btn-3d-outline {
  border-radius: 8px !important;
  font-weight: 600;
  background: rgba(255, 255, 255, 0.8) !important;
  backdrop-filter: blur(4px);
  box-shadow: 0 2px 5px rgba(0,0,0,0.05);
  transition: all 0.2s ease;
}
.btn-3d-outline:hover { background: #ffffff !important; transform: translateY(-1px); }

/* ==========================================================
   MODALS & LISTS
========================================================== */
.review-dialog-glass {
  border-radius: 20px !important;
  background: #ffffff; 
  border: 1px solid rgba(255, 255, 255, 0.8);
  box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
  overflow: hidden;
}

.glass-list {
  background: rgba(248, 250, 252, 0.6);
  border-radius: 12px;
  border: 1px solid rgba(226, 232, 240, 0.8);
}

.dialog-bg-glow-red {
  position: absolute;
  top: -80px;
  left: 50%;
  transform: translateX(-50%);
  width: 250px;
  height: 250px;
  background: radial-gradient(circle, rgba(220, 38, 38, 0.1) 0%, transparent 70%);
  border-radius: 50%;
  z-index: 1;
  pointer-events: none;
}

.dialog-actions-glass {
  background: rgba(248, 250, 252, 0.9);
  border-top: 1px solid rgba(226, 232, 240, 1);
}

.action-icon-3d {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 72px;
  height: 72px;
  border-radius: 50%;
  box-shadow: 6px 6px 12px rgba(0, 0, 0, 0.08), -4px -4px 10px rgba(255, 255, 255, 1), inset 2px 2px 5px rgba(255, 255, 255, 0.5);
}

/* ==========================================================
   RESPONSIVE & PRINT SCOPED OVERRIDES
========================================================== */
@media print {
  @page { size: auto; margin: 10mm; }
  .consumer-mgmt-header, .toolbar, .panel-header { display: none !important; }
  :deep(.print-hide) { display: none !important; }
  :deep(.print-only) { display: block !important; }
  .admin-page { background: white !important; min-height: auto !important; }
  .page-container { max-width: 100% !important; padding: 0 !important; margin: 0 !important; width: 100vw !important; }
  .glass-card { box-shadow: none !important; border: none !important; background: white !important; }
  :deep(.custom-glass-table) { background: white !important; width: 100% !important; }
  :deep(.custom-glass-table table) { width: 100% !important; }
  :deep(.custom-glass-table thead tr th),
  :deep(.custom-glass-table tbody td) {
    padding: 8px 6px !important;
    white-space: normal !important; 
    word-break: break-word !important; 
    font-size: 11px !important; 
  }
  :deep(.custom-glass-table thead tr th) {
    color: black !important;
    background: #f1f5f9 !important;
    border-bottom: 2px solid #ccc !important;
  }
}

@media (max-width: 768px) {
  .page-container { padding: 20px; }
  .consumer-mgmt-header > .row { flex-direction: column; align-items: flex-start; }
}

@media (max-width: 600px) {
  .page-container { padding: 16px; }
  .toolbar > .row { flex-direction: column; align-items: stretch; width: 100%; }
  .search-input-glass { max-width: 100%; }
  .export-btn { margin-top: 12px; margin-left: 0; width: 100%; }
}
</style>