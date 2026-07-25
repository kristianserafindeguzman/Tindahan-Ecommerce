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
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { api } from '@/boot/axios'

const search = ref('')
const statusFilter = ref(null)
const loading = ref(false)
const vendors = ref([])

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
  try {
    await api.patch(`/admin/vendors/${userId}/status`, {
      account_status: newStatus
    })
  } catch {
    // Revert on failure — refetch
    fetchVendors()
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
