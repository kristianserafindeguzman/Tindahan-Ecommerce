<template>
  <q-page class="admin-page">
    <div class="page-container">

      <div class="page-header">
        <h1>Vendor Approvals</h1>
        <p class="page-subtitle">Review and process pending vendor applications</p>
      </div>

      <!-- TOOLBAR -->
      <div class="toolbar">
        <q-input
          v-model="search"
          outlined
          dense
          placeholder="Search by name or email..."
          class="search-input"
          @update:model-value="fetchPending"
        >
          <template #prepend>
            <q-icon name="search" color="grey-6" />
          </template>
        </q-input>

        <q-btn
          label="Export"
          no-caps
          flat
          icon="print"
          class="export-btn"
          @click="handleExport"
        />
      </div>

      <!-- TABS -->
      <q-tabs
        v-model="currentTab"
        dense
        class="text-grey"
        active-color="primary"
        indicator-color="primary"
        align="left"
        narrow-indicator
        @update:model-value="fetchPending"
      >
        <q-tab name="pending" label="Pending Applications" />
        <q-tab name="rejected" label="Rejected Applications" />
      </q-tabs>

      <!-- TABLE -->
      <div class="table-card q-mt-sm">
        <q-table
          flat
          :rows="filteredApplications"
          :columns="columns"
          row-key="approval_id"
          :loading="loading"
          no-data-label="No applications found"
          class="data-table"
          @row-click="openVendorInfo"
        >
          <template #body-cell-actions="props">
            <q-td :props="props">
              <template v-if="props.row.status === 'pending'">
                <q-btn
                  label="Approve"
                  no-caps
                  dense
                  unelevated
                  class="approve-btn q-mr-xs"
                  @click.stop="handleApprove(props.row)"
                />
                <q-btn
                  label="Reject"
                  no-caps
                  dense
                  flat
                  class="reject-btn"
                  @click.stop="openRejectModal(props.row)"
                />
              </template>
              <template v-else>
                <q-chip dense color="red-1" text-color="red-6" class="q-ma-none text-weight-bold">
                  Rejected
                </q-chip>
              </template>
            </q-td>
          </template>
        </q-table>
      </div>

    </div>

    <!-- REJECT MODAL -->
    <q-dialog v-model="showRejectModal">
      <q-card class="reject-dialog">
        <q-card-section class="q-pt-md">
          <div class="modal-title text-red-6">Reject Vendor</div>
          <p class="modal-subtitle">Please provide a reason for rejecting this application.</p>
          <q-input
            v-model="rejectionReason"
            type="textarea"
            outlined
            dense
            placeholder="e.g., Incomplete documentation, suspicious activity..."
            class="q-mt-md"
            autofocus
          />
        </q-card-section>
        <q-card-actions align="right" class="q-pb-md q-px-md">
          <q-btn flat label="Cancel" color="grey-7" no-caps @click="showRejectModal = false" />
          <q-btn
            unelevated
            label="Confirm Rejection"
            color="red-6"
            no-caps
            :loading="actionLoading"
            @click="handleReject"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- APPROVE MODAL -->
    <q-dialog v-model="showApproveModal">
      <q-card class="reject-dialog">
        <q-card-section class="q-pt-md">
          <div class="modal-title text-green-6">Approve Vendor</div>
          <p class="modal-subtitle">Are you sure you want to approve this application? They will gain access to the Vendor Dashboard.</p>
        </q-card-section>
        <q-card-actions align="right" class="q-pb-md q-px-md">
          <q-btn flat label="Cancel" color="grey-7" no-caps @click="showApproveModal = false" />
          <q-btn
            unelevated
            label="Confirm Approval"
            color="green-6"
            no-caps
            :loading="actionLoading"
            @click="handleApproveConfirm"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- VENDOR INFO MODAL -->
    <q-dialog v-model="showVendorInfoModal">
      <q-card class="vendor-info-dialog">
        <q-card-section class="info-header text-center" v-if="selectedVendor">
          <div class="info-store-name q-mb-sm">{{ selectedVendor.store?.store_name || 'N/A' }}</div>
          <div class="info-owner-name text-grey-7 q-mb-md">{{ selectedVendor.store?.owner?.full_name }}</div>
          
          <!-- If Image Exists -->
          <q-img 
            v-if="selectedVendor.store?.store_picture_url && selectedVendor.store.store_picture_url !== 'null' && selectedVendor.store.store_picture_url.trim() !== ''" 
            :src="selectedVendor.store.store_picture_url" 
            style="width: 100%; height: 200px"
            fit="cover"
            class="rounded-borders"
          >
            <template v-slot:error>
              <div class="absolute-full flex flex-center bg-grey-3">
                <q-icon name="storefront" size="64px" color="grey-7" />
              </div>
            </template>
          </q-img>
        
          <!-- If Image is Null/Empty in Database -->
          <div 
            v-else 
            class="bg-grey-3 flex flex-center full-width rounded-borders" 
            style="height: 200px;"
          >
            <q-icon name="storefront" size="64px" color="grey-7" />
          </div>
        </q-card-section>

        <q-card-section v-if="selectedVendor">
          <div class="info-details q-mb-md">
            <div><strong>Operating Days:</strong> {{ formatOperatingDays(selectedVendor.store?.operating_days) }}</div>
            <div><strong>Hours:</strong> {{ selectedVendor.store?.opening_time || 'N/A' }} - {{ selectedVendor.store?.closing_time || 'N/A' }}</div>
            <div><strong>Coordinates:</strong> {{ selectedVendor.store?.latitude }}, {{ selectedVendor.store?.longitude }}</div>
          </div>

          <div class="map-container" v-if="selectedVendor.store?.latitude && selectedVendor.store?.longitude">
            <iframe 
              :src="'https://www.openstreetmap.org/export/embed.html?bbox=' + (selectedVendor.store.longitude - 0.01) + '%2C' + (selectedVendor.store.latitude - 0.01) + '%2C' + (selectedVendor.store.longitude + 0.01) + '%2C' + (selectedVendor.store.latitude + 0.01) + '&amp;layer=mapnik&amp;marker=' + selectedVendor.store.latitude + '%2C' + selectedVendor.store.longitude"
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
                :href="'https://www.google.com/maps/dir/?api=1&destination=' + selectedVendor.store.latitude + ',' + selectedVendor.store.longitude"
                target="_blank"
              />
            </div>
          </div>
        </q-card-section>

        <q-card-actions align="right" class="modal-actions" v-if="selectedVendor">
          <template v-if="selectedVendor.status === 'pending'">
            <q-btn flat label="Reject" color="red-6" no-caps @click="openRejectModal(selectedVendor); showVendorInfoModal = false;" />
            <q-btn unelevated label="Approve" color="green-6" no-caps @click="handleApprove(selectedVendor); showVendorInfoModal = false;" />
          </template>
          <template v-else-if="selectedVendor.status === 'rejected'">
            <q-btn flat label="Close" color="grey-8" no-caps @click="showVendorInfoModal = false" />
            <q-btn unelevated label="Approve" color="green-6" no-caps @click="handleApprove(selectedVendor); showVendorInfoModal = false;" />
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
import { ref, computed, onMounted } from 'vue'
import { api } from '@/boot/axios'

const search = ref('')
const loading = ref(false)
const actionLoading = ref(false)
const pending = ref([])
const currentTab = ref('pending')

const filteredApplications = computed(() => {
  return pending.value.filter(app => app.status === currentTab.value)
})

// Reject modal
const showRejectModal = ref(false)
const showApproveModal = ref(false)
const rejectTarget = ref(null)
const approveTarget = ref(null)
const rejectionReason = ref('')

// Vendor Info modal
const showVendorInfoModal = ref(false)
const selectedVendor = ref(null)

const columns = [
  { name: 'owner_name', label: 'Name', field: 'owner_name', align: 'left', sortable: true },
  { name: 'email', label: 'Email', field: 'email', align: 'left', sortable: true },
  { name: 'phone', label: 'Phone', field: 'phone', align: 'left' },
  { name: 'store_name', label: 'Store Name', field: 'store_name', align: 'left' },
  { name: 'applied_at', label: 'Applied', field: 'applied_at', align: 'left',
    format: val => val ? new Date(val).toLocaleDateString() : '—' },
  { name: 'actions', label: 'Actions', field: 'actions', align: 'center' },
]

const fetchPending = async () => {
  loading.value = true
  try {
    const res = await api.get('/admin/vendors/pending', {
      params: {
        search: search.value || undefined
      }
    })
    pending.value = res.data
  } catch (error) {
    console.error('Error fetching applications:', error)
  } finally {
    loading.value = false
  }
}

const formatOperatingDays = (days) => {
  if (!days) return 'N/A'
  try {
    const parsed = typeof days === 'string' ? JSON.parse(days) : days
    return Array.isArray(parsed) && parsed.length > 0 ? parsed.join(', ') : 'N/A'
  } catch (e) {
    return 'N/A'
  }
}


const openVendorInfo = (evt, row) => {
  selectedVendor.value = row
  showVendorInfoModal.value = true
}

const handleApprove = (row) => {
  approveTarget.value = row
  showApproveModal.value = true
}

const handleApproveConfirm = async () => {
  if (!approveTarget.value) return

  actionLoading.value = true
  try {
    await api.post(`/admin/vendors/${approveTarget.value.store_id}/approve`)
    pending.value = pending.value.filter(p => p.store_id !== approveTarget.value.store_id)
    showApproveModal.value = false
  } catch {
    // Error handling
  } finally {
    actionLoading.value = false
  }
}

const openRejectModal = (row) => {
  rejectTarget.value = row
  rejectionReason.value = ''
  showRejectModal.value = true
}

const handleReject = async () => {
  if (!rejectionReason.value) return

  actionLoading.value = true
  try {
    await api.post(`/admin/vendors/${rejectTarget.value.store_id}/reject`, {
      rejection_reason: rejectionReason.value
    })
    pending.value = pending.value.filter(p => p.store_id !== rejectTarget.value.store_id)
    showRejectModal.value = false
  } catch {
    // Error handling
  } finally {
    actionLoading.value = false
  }
}

const handleExport = () => {
  window.print()
}

onMounted(() => {
  fetchPending()
})
</script>

<style scoped>
.admin-page {
  background: #f4f5f7;
  font-family: 'Roboto', Arial, sans-serif;
}

.page-container {
  max-width: 960px;
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
  max-width: 340px;
}

.search-input :deep(.q-field__control) {
  height: 38px;
  border-radius: 8px;
  background: #ffffff;
}

.search-input :deep(.q-field__native) {
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

.approve-btn {
  background: #22c55e;
  color: #ffffff;
  font-size: 12px;
  padding: 4px 14px;
  border-radius: 6px;
}

.reject-btn {
  color: #ef4444;
  font-size: 12px;
}

/* REJECT MODAL */

.reject-dialog {
  width: 440px;
  max-width: 90vw;
  border-radius: 10px;
  font-family: 'Roboto', Arial, sans-serif;
}

.modal-title {
  font-size: 18px;
  font-weight: 700;
  color: #222222;
}

.modal-subtitle {
  margin: 6px 0 16px;
  font-size: 13px;
  line-height: 1.5;
  color: #666666;
}

.reason-input :deep(.q-field__control) {
  border-radius: 8px;
}

.modal-actions {
  padding: 10px 20px 18px;
}

.reject-confirm-btn {
  background: #ef4444;
  color: #ffffff;
  border-radius: 6px;
  font-size: 13px;
}

/* VENDOR INFO MODAL */
.vendor-info-dialog {
  width: 500px;
  max-width: 95vw;
  border-radius: 10px;
}
.info-header {
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
  .approve-btn, .reject-btn { display: none; }
}

@media (max-width: 600px) {
  .page-container {
    padding: 20px 16px;
  }
  .toolbar {
    flex-direction: column;
    align-items: stretch;
  }
  .search-input {
    max-width: 100%;
  }
}
</style>
