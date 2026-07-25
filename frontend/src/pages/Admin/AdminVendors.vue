<template>
  <q-page class="admin-page">
    <div class="page-container">

      <div class="page-header">
        <h1>Manage Vendors</h1>
        <p class="page-subtitle">View all vendors, update statuses, and review quick insights</p>
      </div>

      <!-- TOOLBAR -->
      <div class="toolbar">
        <q-input
          v-model="search"
          outlined
          dense
          placeholder="Search by name or email..."
          class="search-input"
          @update:model-value="fetchVendors"
        >
          <template #prepend>
            <q-icon name="search" color="grey-6" />
          </template>
        </q-input>

        <q-select
          v-model="statusFilter"
          outlined
          dense
          emit-value
          map-options
          clearable
          placeholder="Filter by status"
          class="filter-select"
          :options="statusOptions"
          @update:model-value="fetchVendors"
        />

        <q-btn
          label="Export"
          no-caps
          flat
          icon="print"
          class="export-btn"
          @click="handleExport"
        />
      </div>

      <!-- TABLE -->
      <div class="table-card">
        <q-table
          flat
          :rows="vendors"
          :columns="columns"
          row-key="user_id"
          :loading="loading"
          no-data-label="No vendors found"
          class="data-table"
          @row-click="openVendorInfo"
        >
          <!-- STATUS COLUMN -->
          <template #body-cell-account_status="props">
            <q-td :props="props">
              <q-select
                v-model="props.row.account_status"
                dense
                borderless
                emit-value
                map-options
                class="status-select"
                :class="'status-' + props.row.account_status"
                :options="statusOptions"
                @update:model-value="val => updateStatus(props.row.user_id, val)"
              />
            </q-td>
          </template>

          <!-- LAST ACTIVITY -->
          <template #body-cell-last_activity_at="props">
            <q-td :props="props">
              {{ formatActivity(props.row.last_activity_at) }}
            </q-td>
          </template>

          <!-- INSIGHTS -->
          <template #body-cell-insights="props">
            <q-td :props="props">
              <div class="insights-cell">
                <span class="insight-chip orders-chip">
                  <q-icon name="receipt_long" size="12px" />
                  {{ props.row.completed_orders }} orders
                </span>
                <span class="insight-chip products-chip">
                  <q-icon name="inventory_2" size="12px" />
                  {{ props.row.active_products }} products
                </span>
              </div>
            </q-td>
          </template>
        </q-table>
      </div>

    </div>
    
    <!-- SUSPEND VENDOR MODAL -->
    <q-dialog v-model="showSuspendModal">
      <q-card class="suspend-dialog">
        <q-card-section class="suspend-content">
          <div class="suspend-icon-wrap">
            <q-icon name="warning" size="32px" color="white" />
          </div>
          <div class="modal-title">Suspend Vendor</div>
          <p class="modal-subtitle">
            Please provide a reason for suspending this vendor account. This message will be shown to the vendor upon login.
          </p>
          <q-input
            v-model="suspensionMessage"
            type="textarea"
            outlined
            dense
            placeholder="e.g., Violation of terms and conditions..."
            class="suspension-input q-mt-md"
            autofocus
          />
        </q-card-section>
        <q-card-actions align="right" class="modal-actions">
          <q-btn
            label="Cancel"
            no-caps
            flat
            @click="cancelSuspension"
          />
          <q-btn
            label="Suspend Account"
            no-caps
            unelevated
            class="suspend-confirm-btn"
            :loading="actionLoading"
            @click="confirmSuspension"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- VENDOR INFO MODAL -->
    <q-dialog v-model="showVendorInfoModal">
      <q-card class="vendor-info-dialog">
        <q-card-section class="info-header" v-if="selectedVendor">
          <q-avatar size="64px" class="q-mr-md" v-if="selectedVendor.store_picture">
            <img :src="'http://localhost:8000/storage/' + selectedVendor.store_picture" />
          </q-avatar>
          <q-avatar size="64px" class="q-mr-md bg-grey-3 text-grey-8" v-else>
            <q-icon name="storefront" size="32px" />
          </q-avatar>
          <div>
            <div class="info-store-name">{{ selectedVendor.store_name || 'N/A' }}</div>
            <div class="info-owner-name text-grey-7">{{ selectedVendor.full_name }}</div>
          </div>
        </q-card-section>

        <q-card-section v-if="selectedVendor">
          <div class="info-details q-mb-md">
            <div><strong>Hours:</strong> {{ selectedVendor.opening_time || 'N/A' }} - {{ selectedVendor.closing_time || 'N/A' }}</div>
            <div><strong>Coordinates:</strong> {{ selectedVendor.latitude }}, {{ selectedVendor.longitude }}</div>
          </div>

          <div class="map-container" v-if="selectedVendor.latitude && selectedVendor.longitude">
            <iframe 
              :src="'https://www.openstreetmap.org/export/embed.html?bbox=' + (selectedVendor.longitude - 0.01) + '%2C' + (selectedVendor.latitude - 0.01) + '%2C' + (selectedVendor.longitude + 0.01) + '%2C' + (selectedVendor.latitude + 0.01) + '&amp;layer=mapnik&amp;marker=' + selectedVendor.latitude + '%2C' + selectedVendor.longitude"
              width="100%" 
              height="200" 
              style="border:1px solid #ccc; border-radius: 8px;" 
              allowfullscreen="" 
              loading="lazy">
            </iframe>
            <div class="q-mt-sm text-right">
              <q-btn
                label="Get Directions"
                no-caps
                flat
                dense
                color="primary"
                icon="directions"
                :href="'https://www.google.com/maps/dir/?api=1&destination=' + selectedVendor.latitude + ',' + selectedVendor.longitude"
                target="_blank"
              />
            </div>
          </div>
        </q-card-section>

        <q-card-actions align="right" class="modal-actions" v-if="selectedVendor">
          <template v-if="selectedVendor.approval_status === 'pending'">
            <q-btn flat label="Reject" color="red-6" no-caps @click="rejectFromInfo" />
            <q-btn unelevated label="Approve" color="green-6" no-caps @click="approveFromInfo" />
          </template>
          <template v-else>
            <q-btn flat label="Close" color="grey-8" no-caps @click="showVendorInfoModal = false" />
          </template>
        </q-card-actions>
      </q-card>
    </q-dialog>

  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { api } from '@/boot/axios'

const search = ref('')
const statusFilter = ref(null)
const loading = ref(false)
const actionLoading = ref(false)
const vendors = ref([])

// Suspend modal state
const showSuspendModal = ref(false)
const suspensionTarget = ref(null)
const suspensionMessage = ref('')
const originalStatus = ref(null)

// Vendor Info modal state
const showVendorInfoModal = ref(false)
const selectedVendor = ref(null)

const statusOptions = [
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
  { label: 'Suspended', value: 'suspended' },
]

const columns = [
  { name: 'full_name', label: 'Name', field: 'full_name', align: 'left', sortable: true },
  { name: 'email', label: 'Email', field: 'email', align: 'left', sortable: true },
  { name: 'phone_number', label: 'Mobile', field: 'phone_number', align: 'left' },
  { name: 'account_status', label: 'Status', field: 'account_status', align: 'left' },
  { name: 'last_activity_at', label: 'Last Activity', field: 'last_activity_at', align: 'left', sortable: true },
  { name: 'insights', label: 'Quick Insights', field: '', align: 'left' },
]

const fetchVendors = async () => {
  loading.value = true
  try {
    const res = await api.get('/admin/vendors', {
      params: {
        search: search.value || undefined,
        status: statusFilter.value || undefined,
      }
    })
    vendors.value = res.data
  } catch {
    vendors.value = []
  } finally {
    loading.value = false
  }
}

const updateStatus = async (userId, newStatus) => {
  if (newStatus === 'suspended') {
    // Intercept suspension and prompt for reason
    suspensionTarget.value = userId
    // Find vendor to store original status
    const vendor = vendors.value.find(v => v.user_id === userId)
    if (vendor) {
      originalStatus.value = vendor.account_status // Will actually be 'suspended' because v-model already updated it locally, but we will fix it if canceled.
      // Wait, since v-model updated it, if we cancel, we need to revert it.
      // Actually, since v-model changes it, we can just refetch on cancel.
    }
    suspensionMessage.value = ''
    showSuspendModal.value = true
    return
  }

  try {
    await api.patch(`/admin/vendors/${userId}/status`, {
      account_status: newStatus
    })
  } catch {
    // Revert on failure — refetch
    fetchVendors()
  }
}

const cancelSuspension = () => {
  showSuspendModal.value = false
  suspensionTarget.value = null
  fetchVendors() // Revert local v-model change
}

const confirmSuspension = async () => {
  if (!suspensionMessage.value) return

  actionLoading.value = true
  try {
    await api.patch(`/admin/vendors/${suspensionTarget.value}/status`, {
      account_status: 'suspended',
      suspension_message: suspensionMessage.value
    })
    showSuspendModal.value = false
    suspensionTarget.value = null
  } catch {
    fetchVendors()
  } finally {
    actionLoading.value = false
  }
}

const openVendorInfo = (evt, row) => {
  // Prevent opening info when clicking on the status dropdown
  if (evt.target.closest('.status-select')) return
  
  selectedVendor.value = row
  showVendorInfoModal.value = true
}

const approveFromInfo = async () => {
  if (!selectedVendor.value) return
  try {
    // We don't have store_id locally available in listVendors API easily, 
    // wait, we need store_id for approve! Let's check what the backend returns.
    // listVendors does not return store_id! It returns user_id, full_name, store_name, etc.
    // I need to add store_id to AdminController's listVendors mapped response!
    await api.post(`/admin/vendors/${selectedVendor.value.store_id}/approve`)
    fetchVendors()
    showVendorInfoModal.value = false
  } catch {
    // Error handling
  }
}

const rejectFromInfo = () => {
  // If we had a rejection modal here, we would show it.
  // For simplicity, we just route them to the approvals page where the full reject flow is, or we can prompt simple rejection.
  // To keep it simple, we prompt.
  const reason = prompt("Enter rejection reason:")
  if (reason) {
    api.post(`/admin/vendors/${selectedVendor.value.store_id}/reject`, {
      rejection_reason: reason
    }).then(() => {
      fetchVendors()
      showVendorInfoModal.value = false
    })
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

  return then.toLocaleDateString()
}

const handleExport = () => {
  window.print()
}

onMounted(() => {
  fetchVendors()
})
</script>

<style scoped>
.admin-page {
  background: #f4f5f7;
  font-family: 'Roboto', Arial, sans-serif;
}

.page-container {
  max-width: 1060px;
  margin: 0 auto;
  padding: 32px 28px;
}

.page-header {
  margin-bottom: 20px;
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

/* TOOLBAR */

.toolbar {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
}

.search-input {
  flex: 1;
  max-width: 300px;
}

.search-input :deep(.q-field__control) {
  height: 38px;
  border-radius: 8px;
  background: #ffffff;
}

.search-input :deep(.q-field__native) {
  font-size: 13px;
}

.filter-select {
  width: 160px;
}

.filter-select :deep(.q-field__control) {
  height: 38px;
  border-radius: 8px;
  background: #ffffff;
}

.filter-select :deep(.q-field__native) {
  font-size: 13px;
}

.export-btn {
  color: #555555;
  font-size: 13px;
}

/* TABLE */

.table-card {
  background: #ffffff;
  border-radius: 10px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
  overflow: hidden;
}

.data-table :deep(th) {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: #8992a2;
}

.data-table :deep(td) {
  font-size: 13px;
  color: #333333;
}

/* STATUS SELECT */

.status-select :deep(.q-field__native) {
  font-size: 12px;
  font-weight: 600;
  padding: 2px 6px;
  border-radius: 4px;
}

.status-active :deep(.q-field__native) {
  color: #16a34a;
}

.status-inactive :deep(.q-field__native) {
  color: #9ca3af;
}

.status-suspended :deep(.q-field__native) {
  color: #ef4444;
}

/* INSIGHTS */

.insights-cell {
  display: flex;
  gap: 6px;
}

.insight-chip {
  display: inline-flex;
  align-items: center;
  gap: 3px;
  padding: 2px 8px;
  border-radius: 10px;
  font-size: 11px;
  font-weight: 500;
}

.orders-chip {
  background: #eff6ff;
  color: #3b82f6;
}

.products-chip {
  background: #f0fdf4;
  color: #16a34a;
}

/* SUSPEND MODAL */
.suspend-dialog {
  width: 440px;
  max-width: 90vw;
  border-radius: 10px;
}
.suspend-content {
  text-align: center;
  padding: 28px 28px 10px;
}
.suspend-icon-wrap {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 60px;
  height: 60px;
  border-radius: 50%;
  background: #ef4444;
  margin-bottom: 16px;
}
.modal-title {
  font-size: 18px;
  font-weight: 700;
  color: #222222;
  margin-bottom: 8px;
}
.modal-subtitle {
  font-size: 13px;
  color: #666666;
  margin-bottom: 0;
}
.modal-actions {
  padding: 10px 20px 18px;
}
.suspend-confirm-btn {
  background: #ef4444;
  color: #ffffff;
  border-radius: 6px;
}

/* VENDOR INFO MODAL */
.vendor-info-dialog {
  width: 500px;
  max-width: 95vw;
  border-radius: 10px;
}
.info-header {
  display: flex;
  align-items: center;
  padding-bottom: 0;
}
.info-store-name {
  font-size: 20px;
  font-weight: 700;
  color: #111;
  line-height: 1.2;
}
.info-owner-name {
  font-size: 14px;
}
.info-details {
  font-size: 13px;
  color: #444;
}

/* PRINT */
@media print {
  .toolbar { display: none; }
  .status-select { pointer-events: none; }
}

@media (max-width: 600px) {
  .page-container {
    padding: 20px 16px;
  }
  .toolbar {
    flex-direction: column;
    align-items: stretch;
  }
  .search-input, .filter-select {
    max-width: 100%;
    width: 100%;
  }
}
</style>
