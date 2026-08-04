<template>
  <q-page class="admin-page">
    <div class="page-container">

      <div class="page-header">
        <h1>Manage Consumers</h1>
        <p class="page-subtitle">View consumer accounts and manage access</p>
      </div>

      <!-- TOOLBAR -->
      <div class="toolbar">
        <q-input
          v-model="search"
          outlined
          dense
          placeholder="Search by name or email..."
          class="search-input"
          @update:model-value="fetchConsumers"
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
        @update:model-value="fetchConsumers"
      >
        <q-tab name="active" label="Active Accounts" />
        <q-tab name="deleted" label="Deleted Accounts" />
      </q-tabs>

      <!-- TABLE -->
      <div class="table-card q-mt-sm">
        <q-table
          flat
          :rows="consumers"
          :columns="columns"
          row-key="user_id"
          :loading="loading"
          no-data-label="No consumers found"
          class="data-table"
          @row-click="openConsumerInfo"
        >
          <!-- NAME WITH AVATAR -->
          <template #body-cell-full_name="props">
            <q-td :props="props">
              <div class="flex items-center">
                <q-avatar size="32px" class="q-mr-sm bg-grey-3 text-grey-8" v-if="props.row.profile_picture_url">
                  <q-img :src="props.row.profile_picture_url" class="full-height full-width" />
                </q-avatar>
                <q-avatar size="32px" class="q-mr-sm bg-grey-3 text-grey-8" v-else>
                  <q-icon name="person" size="20px" />
                </q-avatar>
                <span>{{ props.row.full_name }}</span>
              </div>
            </q-td>
          </template>

          <!-- LAST ACTIVITY -->
          <template #body-cell-last_activity_at="props">
            <q-td :props="props">
              {{ formatActivity(props.row.last_activity_at) }}
            </q-td>
          </template>

          <!-- STATUS BADGE / SELECT -->
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

          <!-- ACTIONS -->
          <template #body-cell-actions="props">
            <q-td :props="props">
              <q-btn
                v-if="currentTab !== 'deleted'"
                label="Delete Account"
                no-caps
                dense
                flat
                class="delete-btn"
                icon="person_remove"
                @click="openDeleteModal(props.row)"
              />
            </q-td>
          </template>
        </q-table>
      </div>

    </div>

    <!-- DELETE CONFIRMATION MODAL -->
    <q-dialog v-model="showDeleteModal">
      <q-card class="delete-dialog">
        <q-card-section class="delete-content">
          <div class="delete-icon-wrap">
            <q-icon name="warning" size="32px" color="white" />
          </div>

          <div class="modal-title">Deactivate Account</div>

          <p class="modal-subtitle">
            Are you sure you want to deactivate
            <strong>{{ deleteTarget?.full_name }}'s</strong> account?
            They will no longer be able to log in. Their order history will
            be preserved for vendor accounting records.
          </p>
        </q-card-section>

        <q-card-actions align="right" class="modal-actions">
          <q-btn
            label="Cancel"
            no-caps
            flat
            @click="showDeleteModal = false"
          />
          <q-btn
            label="Deactivate Account"
            no-caps
            unelevated
            class="delete-confirm-btn"
            :loading="actionLoading"
            @click="handleDelete"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- SUSPEND CONSUMER MODAL -->
    <q-dialog v-model="showSuspendModal">
      <q-card class="suspend-dialog">
        <q-card-section class="suspend-content">
          <div class="suspend-icon-wrap">
            <q-icon name="warning" size="32px" color="white" />
          </div>
          <div class="modal-title">Suspend Consumer</div>
          <p class="modal-subtitle">
            Please provide a reason for suspending this consumer account. This message will be shown to the consumer upon login.
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

    <!-- CONSUMER INFO MODAL -->
    <q-dialog v-model="showConsumerModal">
      <q-card class="vendor-info-dialog" style="max-width: 400px; width: 100%;">
        <q-card-section class="info-header text-center" v-if="selectedConsumer">
          <q-avatar size="100px" class="q-mb-md shadow-2 bg-grey-3 text-grey-8" v-if="selectedConsumer.profile_picture_url">
            <q-img :src="selectedConsumer.profile_picture_url" class="full-height full-width" />
          </q-avatar>
          <q-avatar size="100px" class="q-mb-md shadow-2 bg-grey-3 text-grey-8" v-else>
            <q-icon name="person" size="60px" />
          </q-avatar>
          
          <div class="text-h6 text-weight-bold">{{ selectedConsumer.full_name }}</div>
          <q-chip dense :color="selectedConsumer.account_status === 'active' ? 'green-1' : (selectedConsumer.account_status === 'suspended' ? 'orange-1' : 'red-1')" 
                  :text-color="selectedConsumer.account_status === 'active' ? 'green-8' : (selectedConsumer.account_status === 'suspended' ? 'orange-8' : 'red-8')" 
                  class="text-weight-bold q-mt-sm text-uppercase">
            {{ selectedConsumer.account_status }}
          </q-chip>
        </q-card-section>

        <q-card-section v-if="selectedConsumer" class="q-pt-none">
          <div class="q-mt-md">
            <div class="flex items-center q-mb-sm">
              <q-icon name="email" color="grey-7" size="20px" class="q-mr-sm" />
              <span class="text-dark text-weight-medium">{{ selectedConsumer.email }}</span>
            </div>
            <div class="flex items-center q-mb-sm">
              <q-icon name="phone" color="grey-7" size="20px" class="q-mr-sm" />
              <span class="text-dark text-weight-medium">{{ selectedConsumer.phone_number || 'N/A' }}</span>
            </div>
          </div>
        </q-card-section>

        <q-card-actions align="right" class="modal-actions">
          <q-btn flat label="Close" color="grey-8" no-caps @click="showConsumerModal = false" />
        </q-card-actions>
      </q-card>
    </q-dialog>

  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { api } from '@/boot/axios'

const search = ref('')
const loading = ref(false)
const actionLoading = ref(false)
const consumers = ref([])
const currentTab = ref('active')

// Delete modal
const showDeleteModal = ref(false)
const deleteTarget = ref(null)

// Suspend modal state
const showSuspendModal = ref(false)
const suspensionTarget = ref(null)
const suspensionMessage = ref('')
const originalStatus = ref(null)

// Consumer Info modal state
const showConsumerModal = ref(false)
const selectedConsumer = ref(null)

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
  { name: 'actions', label: '', field: '', align: 'right' },
]

const fetchConsumers = async () => {
  loading.value = true
  try {
    const res = await api.get('/admin/consumers', {
      params: { 
        search: search.value || undefined,
        tab: currentTab.value 
      }
    })
    consumers.value = res.data
  } catch {
    consumers.value = []
  } finally {
    loading.value = false
  }
}

const openDeleteModal = (row) => {
  deleteTarget.value = row
  showDeleteModal.value = true
}

const handleDelete = async () => {
  actionLoading.value = true
  try {
    await api.delete(`/admin/consumers/${deleteTarget.value.user_id}`)
    consumers.value = consumers.value.filter(c => c.user_id !== deleteTarget.value.user_id)
    showDeleteModal.value = false
  } catch {
    // Error handling
  } finally {
    actionLoading.value = false
  }
}

const formatActivity = (dateStr) => {
  if (!dateStr) return 'Never logged in'
  const date = new Date(dateStr)
  return date.toLocaleString('en-US', {
    month: 'short', day: 'numeric', year: 'numeric',
    hour: '2-digit', minute: '2-digit'
  })
}

const openConsumerInfo = (evt, row) => {
  selectedConsumer.value = row
  showConsumerModal.value = true
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
  } catch {
    fetchConsumers()
  } finally {
    actionLoading.value = false
  }
}

const handleExport = () => {
  window.print()
}

onMounted(() => {
  fetchConsumers()
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

/* DELETE */

.delete-btn {
  color: #ef4444;
  font-size: 12px;
}

/* DELETE MODAL */

.delete-dialog {
  width: 420px;
  max-width: 90vw;
  border-radius: 10px;
  font-family: 'Roboto', Arial, sans-serif;
}

.delete-content {
  text-align: center;
  padding: 28px 28px 10px;
}

.delete-icon-wrap {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 60px;
  height: 60px;
  border-radius: 50%;
  background: #f59e0b;
  margin-bottom: 16px;
}

.modal-title {
  font-size: 18px;
  font-weight: 700;
  color: #222222;
  margin-bottom: 8px;
}

.modal-subtitle {
  margin: 0;
  font-size: 13px;
  line-height: 1.6;
  color: #666666;
}

.modal-actions {
  padding: 10px 20px 18px;
}

.delete-confirm-btn {
  background: #ef4444;
  color: #ffffff;
  border-radius: 6px;
  font-size: 13px;
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
.suspend-confirm-btn {
  background: #ef4444;
  color: #ffffff;
  border-radius: 6px;
}

/* PRINT */
@media print {
  .toolbar { display: none; }
  .delete-btn { display: none; }
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
