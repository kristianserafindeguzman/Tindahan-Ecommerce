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

      <!-- TABLE -->
      <div class="table-card">
        <q-table
          flat
          :rows="consumers"
          :columns="columns"
          row-key="user_id"
          :loading="loading"
          no-data-label="No consumers found"
          class="data-table"
        >
          <!-- LAST ACTIVITY -->
          <template #body-cell-last_activity_at="props">
            <q-td :props="props">
              {{ formatActivity(props.row.last_activity_at) }}
            </q-td>
          </template>

          <!-- STATUS BADGE -->
          <template #body-cell-account_status="props">
            <q-td :props="props">
              <span class="status-badge" :class="'badge-' + props.row.account_status">
                {{ props.row.account_status }}
              </span>
            </q-td>
          </template>

          <!-- ACTIONS -->
          <template #body-cell-actions="props">
            <q-td :props="props">
              <q-btn
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

  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { api } from '@/boot/axios'

const search = ref('')
const loading = ref(false)
const actionLoading = ref(false)
const consumers = ref([])

// Delete modal
const showDeleteModal = ref(false)
const deleteTarget = ref(null)

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
      params: { search: search.value || undefined }
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

/* STATUS */

.status-badge {
  display: inline-block;
  padding: 2px 10px;
  border-radius: 10px;
  font-size: 11px;
  font-weight: 600;
  text-transform: capitalize;
}

.badge-active {
  background: #f0fdf4;
  color: #16a34a;
}

.badge-inactive {
  background: #f5f5f5;
  color: #9ca3af;
}

.badge-deleted {
  background: #fef2f2;
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
