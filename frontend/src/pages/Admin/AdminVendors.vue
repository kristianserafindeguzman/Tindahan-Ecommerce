<template>
  <q-page class="admin-page">
    <div class="page-container">
      <!-- ================= PAGE BANNER ================= -->
      <div class="management-header-color q-mb-lg row items-center justify-between q-pa-lg">
        <div class="header-bg-glow"></div>

        <div class="row items-center col-12 col-md-8 relative-position" style="z-index: 2">
          <!-- White Block with Red Storefront Icon and Pulse Effect -->
          <div class="header-white-block q-mr-lg flex flex-center relative-position">
            <q-icon name="storefront" size="36px" color="red-9" />
            <div class="pulse-ring"></div>
          </div>

          <div>
            <div class="q-mb-xs flex items-center">
              <span class="badge-dark-capsule">
                <q-icon name="admin_panel_settings" size="14px" class="q-mr-xs" />
                ADMINISTRATION
              </span>
              <span class="text-caption text-white opacity-80 q-ml-sm text-weight-bold tracking-wide">
                VENDOR DIRECTORY
              </span>
            </div>

            <h1 class="header-main-title text-weight-bolder text-white q-mt-none q-mb-xs line-height-tight">
              Manage Vendors
            </h1>

            <div class="text-white opacity-80 row items-center text-body2 text-weight-medium">
              View all vendors, update statuses, and review quick insights.
            </div>
          </div>
        </div>

        <!-- Right Side Dynamic Tab-Specific Stat Counter Box -->
        <div class="col-12 col-md-auto q-mt-md q-mt-md-none flex justify-end" style="z-index: 2">
          <div class="header-stat-box column flex-center text-center">
            <span class="stat-box-label text-weight-bolder text-uppercase">
              {{ currentTab === 'active' ? 'ACTIVE VENDORS' : 'DELETED VENDORS' }}
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
            placeholder="Search by name, email, or store..."
            class="search-input-glass"
            debounce="300"
            @update:model-value="fetchVendors"
          >
            <template #prepend>
              <q-icon name="search" color="red-4" />
            </template>
            <template #append v-if="search">
              <q-icon name="close" class="cursor-pointer" size="18px" @click="search = ''; fetchVendors()" />
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
            @update:model-value="fetchVendors"
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
            @update:model-value="fetchVendors"
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
          :rows="vendors"
          :columns="columns"
          row-key="user_id"
          :loading="loading"
          @row-click="openVendorInfo"
        >
          <!-- FIXED LOADING STATE -->
          <template #loading>
            <div class="full-width column flex-center q-py-xl table-loading-wrapper">
              <q-spinner-dots size="48px" color="red-7" />
              <div class="text-subtitle2 text-weight-bold q-mt-md text-grey-7">
                Fetching vendors...
              </div>
            </div>
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
              <span
                class="print-only text-weight-bold text-uppercase"
                style="display: none"
              >
                {{ props.row.account_status }}
              </span>
            </q-td>
          </template>

          <!-- LAST ACTIVITY / APPLIED -->
          <template #body-cell-last_activity_at="props">
            <q-td :props="props" class="text-grey-8 text-weight-medium">
              <div class="row items-center no-wrap">
                <q-icon
                  name="schedule"
                  color="red-4"
                  class="q-mr-xs print-hide"
                  size="16px"
                />
                <span>{{ formatActivity(props.row.last_activity_at) }}</span>
              </div>
            </q-td>
          </template>

          <!-- ACTIONS / INSIGHTS (CENTERED) -->
          <template #body-cell-insights="props">
            <q-td :props="props" class="text-center">
              <div class="insights-cell print-hide justify-center">
                <span class="insight-chip-glass orders-chip">
                  <q-icon name="receipt_long" size="14px" />
                  {{ props.row.orders_count || 0 }} orders
                </span>
                <span class="insight-chip-glass products-chip">
                  <q-icon name="inventory_2" size="14px" />
                  {{ props.row.store?.inventory_count || props.row.active_products || 0 }} products
                </span>
              </div>
              <div class="print-only text-grey-8 text-center" style="display: none">
                {{ props.row.orders_count || 0 }} Orders,
                {{ props.row.store?.inventory_count || props.row.active_products || 0 }} Products
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
              <div class="text-h6 text-weight-bold">
                No vendors found
              </div>
              <div class="text-body2 text-grey-6">
                There are currently no accounts matching your criteria.
              </div>
            </div>
          </template>
        </q-table>
      </q-card>
    </div>

    <!-- ================= SUSPEND VENDOR MODAL ================= -->
    <q-dialog
      v-model="showSuspendModal"
      persistent
      transition-show="scale"
      transition-hide="scale"
    >
      <q-card class="review-dialog-glass text-center">
        <div class="dialog-bg-glow-red"></div>
        <q-card-section
          class="q-pt-xl q-pb-md relative-position"
          style="z-index: 2"
        >
          <div class="action-icon-glass bg-red-1 text-red-8 q-mb-md q-mx-auto">
            <q-icon name="gavel" size="36px" />
          </div>
          <div class="text-h5 text-weight-bold q-mb-sm">
            Suspend Vendor
          </div>
          <p class="text-body2 q-px-md opacity-80">
            Please provide a reason for suspending this vendor account. This
            message will be shown to the vendor upon login.
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
          <q-btn
            flat
            label="Cancel"
            no-caps
            class="btn-glass-flat q-px-md q-mr-sm"
            @click="cancelSuspension"
          />
          <q-btn
            unelevated
            label="Confirm Suspension"
            color="red-8"
            no-caps
            class="btn-glass-solid q-px-md"
            :loading="actionLoading"
            @click="confirmSuspension"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- ================= VENDOR INFO MODAL ================= -->
    <q-dialog
      v-model="showVendorInfoModal"
      transition-show="scale"
      transition-hide="scale"
    >
      <q-card class="review-dialog-glass vendor-info-dialog">
        <q-card-section
          class="row items-center justify-between q-pa-md panel-header"
        >
          <div class="text-h6 text-weight-bold row items-center">
            <div class="header-accent-glass q-mr-sm"></div>
            Vendor Details
          </div>
          <q-btn
            icon="close"
            flat
            round
            dense
            color="grey-7"
            class="close-btn-glass"
            v-close-popup
          />
        </q-card-section>

        <q-card-section
          class="q-pa-lg scroll"
          style="max-height: 70vh"
          v-if="selectedVendor"
        >
          <div class="text-center q-mb-lg">
            <div class="info-store-name q-mb-xs">{{
              selectedVendor.store?.store_name || selectedVendor.store_name || 'N/A'
            }}</div>
            <div class="info-owner-name text-red-7 q-mb-md"
              >Owned by: {{ selectedVendor.full_name }}</div
            >

            <div class="image-glass-container">
              <q-img
                v-if="
                  selectedVendor.store?.store_picture_url ||
                  selectedVendor.store_picture_url
                "
                :src="selectedVendor.store?.store_picture_url || selectedVendor.store_picture_url"
                style="width: 100%; height: 220px"
                fit="cover"
                class="rounded-borders"
              >
                <template v-slot:error>
                  <div class="absolute-full flex flex-center empty-state-glass">
                    <q-icon name="storefront" size="64px" color="red-2" />
                  </div>
                </template>
              </q-img>

              <div
                v-else
                class="empty-state-glass flex flex-center full-width rounded-borders"
                style="height: 220px"
              >
                <q-icon name="storefront" size="64px" color="red-2" />
              </div>
            </div>
          </div>

          <div
            class="row q-col-gutter-y-md q-col-gutter-x-xl q-mb-lg info-grid-glass q-pa-md"
          >
            <div class="col-12 col-sm-6">
              <div
                class="modal-label-sub text-caption text-uppercase text-weight-bold"
                >Operating Days</div
              >
              <div class="modal-value-main text-subtitle2 text-weight-bold">{{
                formatOperatingDays(selectedVendor.store?.operating_days || selectedVendor.operating_days)
              }}</div>
            </div>
            <div class="col-12 col-sm-6">
              <div
                class="modal-label-sub text-caption text-uppercase text-weight-bold"
                >Business Hours</div
              >
              <div class="modal-value-main text-subtitle2 text-weight-bold"
                >{{ (selectedVendor.store?.opening_time || selectedVendor.opening_time) || 'N/A' }} -
                {{ (selectedVendor.store?.closing_time || selectedVendor.closing_time) || 'N/A' }}</div
              >
            </div>
            <div class="col-12">
              <div
                class="modal-label-sub text-caption text-uppercase text-weight-bold"
                >Map Coordinates</div
              >
              <div class="modal-value-main text-subtitle2 text-weight-bold font-mono"
                >{{ selectedVendor.store?.latitude || selectedVendor.latitude || 'N/A' }},
                {{ selectedVendor.store?.longitude || selectedVendor.longitude || 'N/A' }}</div
              >
            </div>
          </div>

          <div
            class="map-container-glass"
            v-if="isValidLocation(selectedVendor.store || selectedVendor)"
          >
            <iframe
              :src="
                getMapUrl((selectedVendor.store?.latitude || selectedVendor.latitude), (selectedVendor.store?.longitude || selectedVendor.longitude))
              "
              width="100%"
              height="200"
              style="border: none; border-radius: 8px"
              allowfullscreen=""
              loading="lazy"
            >
            </iframe>
            <div class="q-mt-md text-right">
              <q-btn
                label="Open in Maps"
                no-caps
                class="btn-glass q-px-md"
                color="red-7"
                outline
                icon="map"
                :href="`https://www.google.com/maps/dir/?api=1&destination=${selectedVendor.store?.latitude || selectedVendor.latitude},${selectedVendor.store?.longitude || selectedVendor.longitude}`"
                target="_blank"
              />
            </div>
          </div>
        </q-card-section>

        <q-separator class="modal-divider" />

        <q-card-actions
          align="right"
          class="q-pa-md dialog-actions-glass"
          v-if="selectedVendor"
        >
          <template v-if="selectedVendor.approval_status === 'pending'">
            <q-btn
              flat
              label="Reject"
              color="red-6"
              no-caps
              class="btn-glass-flat q-px-md"
              @click="rejectFromInfo"
            />
            <q-btn
              unelevated
              label="Approve Vendor"
              icon="check_circle"
              color="green-6"
              no-caps
              class="btn-glass-solid q-px-md q-ml-sm"
              @click="approveFromInfo"
            />
          </template>
          <template v-else-if="selectedVendor.approval_status === 'rejected'">
            <q-btn
              flat
              label="Close"
              no-caps
              class="btn-glass-flat q-px-md"
              @click="showVendorInfoModal = false"
            />
            <q-btn
              unelevated
              label="Approve Vendor"
              icon="check_circle"
              color="green-6"
              no-caps
              class="btn-glass-solid q-px-md q-ml-sm"
              @click="approveFromInfo"
            />
          </template>
          <template v-else>
            <q-btn
              outline
              label="Close"
              no-caps
              class="btn-glass q-px-md"
              @click="showVendorInfoModal = false"
            />
          </template>
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
const vendors = ref([])
const currentTab = ref('active')

const counts = ref({
  active: 0,
  deleted: 0
})

const showSuspendModal = ref(false)
const suspensionTarget = ref(null)
const suspensionMessage = ref('')
const originalStatus = ref(null)

const showVendorInfoModal = ref(false)
const selectedVendor = ref(null)

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
  {
    name: 'full_name',
    label: 'NAME',
    field: 'full_name',
    align: 'left',
    sortable: true
  },
  {
    name: 'email',
    label: 'EMAIL',
    field: 'email',
    align: 'left',
    sortable: true
  },
  {
    name: 'phone_number',
    label: 'PHONE',
    field: row => row.phone_number || 'N/A',
    align: 'left'
  },
  {
    name: 'store_name',
    label: 'STORE NAME',
    field: row => row.store?.store_name || row.store_name || 'N/A',
    align: 'left',
    sortable: true
  },
  {
    name: 'last_activity_at',
    label: 'APPLIED',
    field: 'last_activity_at',
    align: 'left',
    sortable: true
  },
  {
    name: 'account_status',
    label: 'STATUS',
    field: 'account_status',
    align: 'center',
    headerClasses: 'text-center'
  },
  { 
    name: 'insights', 
    label: 'ACTIONS', 
    field: '', 
    align: 'center',
    headerClasses: 'text-center'
  }
]

const fetchVendors = async () => {
  try {
    loading.value = true
    const params = {
      tab: currentTab.value,
      search: search.value || undefined,
      status: statusFilter.value || undefined
    }
    const res = await api.get('/admin/vendors', { params })
    vendors.value = res.data.data || res.data || []

    if (res.data.counts) {
      counts.value.active = res.data.counts.active || 0
      counts.value.deleted = res.data.counts.deleted || 0
    } else {
      if (currentTab.value === 'active') {
        counts.value.active = vendors.value.length
      } else {
        counts.value.deleted = vendors.value.length
      }
    }
  } catch (error) {
    console.error('Error fetching vendors:', error)
  } finally {
    loading.value = false
  }
}

const formatOperatingDays = days => {
  if (!days) return 'N/A'
  try {
    const parsed = typeof days === 'string' ? JSON.parse(days) : days

    if (
      parsed !== null &&
      typeof parsed === 'object' &&
      !Array.isArray(parsed)
    ) {
      const openDays = Object.entries(parsed)
        .filter(([_, data]) => data.is_open)
        .map(([day, data]) => {
          if (data.opening_time && data.closing_time) {
            const open = data.opening_time.substring(0, 5)
            const close = data.closing_time.substring(0, 5)
            return `${day} (${open}-${close})`
          }
          return day
        })
      return openDays.length > 0 ? openDays.join(', ') : 'N/A'
    }

    if (Array.isArray(parsed)) {
      return parsed.length > 0 ? parsed.join(', ') : 'N/A'
    }

    return 'N/A'
  } catch {
    return 'N/A'
  }
}

const isValidLocation = vendor => {
  if (!vendor?.latitude || !vendor?.longitude) return false
  return (
    !isNaN(parseFloat(vendor.latitude)) && !isNaN(parseFloat(vendor.longitude))
  )
}

const getMapUrl = (lat, lng) => {
  const parsedLat = parseFloat(lat)
  const parsedLng = parseFloat(lng)

  if (isNaN(parsedLat) || isNaN(parsedLng)) return ''

  const bbox = `${parsedLng - 0.01}%2C${parsedLat - 0.01}%2C${parsedLng + 0.01}%2C${parsedLat + 0.01}`
  return `https://www.openstreetmap.org/export/embed.html?bbox=${bbox}&layer=mapnik&marker=${parsedLat}%2C${parsedLng}`
}

const updateStatus = async (userId, newStatus) => {
  if (newStatus === 'suspended') {
    suspensionTarget.value = userId
    const vendor = vendors.value.find(v => v.user_id === userId)
    if (vendor) {
      originalStatus.value = vendor.account_status
    }
    suspensionMessage.value = ''
    showSuspendModal.value = true
    return
  }

  try {
    await api.patch(`/admin/vendors/${userId}/status`, {
      account_status: newStatus
    })
    $q.notify({ type: 'positive', message: 'Vendor status updated.', position: 'top-right' })
  } catch {
    fetchVendors()
  }
}

const cancelSuspension = () => {
  showSuspendModal.value = false
  suspensionTarget.value = null
  fetchVendors()
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
    fetchVendors()
    $q.notify({ type: 'positive', message: 'Vendor suspended.', position: 'top-right' })
  } catch {
    fetchVendors()
  } finally {
    actionLoading.value = false
  }
}

const openVendorInfo = (evt, row) => {
  if (
    evt.target.closest('.status-select-glass') ||
    evt.target.closest('.q-select')
  )
    return

  selectedVendor.value = row
  showVendorInfoModal.value = true
}

const approveFromInfo = async () => {
  if (!selectedVendor.value) return
  try {
    await api.post(`/admin/vendors/${selectedVendor.value.store_id || selectedVendor.value.user_id}/approve`)
    fetchVendors()
    showVendorInfoModal.value = false
    $q.notify({ type: 'positive', message: 'Vendor approved.', position: 'top-right' })
  } catch {
    // Error handling
  }
}

const rejectFromInfo = () => {
  const reason = prompt('Enter rejection reason:')
  if (reason) {
    api
      .post(`/admin/vendors/${selectedVendor.value.store_id || selectedVendor.value.user_id}/reject`, {
        rejection_reason: reason
      })
      .then(() => {
        fetchVendors()
        showVendorInfoModal.value = false
        $q.notify({ type: 'positive', message: 'Vendor rejected.', position: 'top-right' })
      })
  }
}

const formatActivity = timestamp => {
  if (!timestamp) return 'Never'

  const now = new Date()
  const then = new Date(timestamp)
  const diff = Math.floor((now - then) / 1000)

  if (diff < 60) return 'Just now'
  if (diff < 3600) return `${Math.floor(diff / 60)} mins ago`
  if (diff < 86400) return `${Math.floor(diff / 3600)} hours ago`
  if (diff < 604800) return `${Math.floor(diff / 86400)} days ago`

  return then.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

const handleExport = async () => {
  if (isExporting.value) return
  isExporting.value = true
  try {
    const response = await api.get('/admin/vendors/export', {
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
    link.setAttribute('download', `Tindahan_Admin_Vendors_Export_${dateStr}.pdf`)
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
  fetchVendors()
})
</script>

<style>
@media print {
  .q-drawer,
  .q-header,
  .q-footer,
  .q-drawer-container {
    display: none !important;
  }

  .q-page-container {
    padding-left: 0 !important;
    padding-right: 0 !important;
    padding-top: 0 !important;
  }

  .q-table__middle {
    overflow: visible !important;
  }

  .q-page {
    min-height: auto !important;
  }
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

.tracking-wide {
  letter-spacing: 0.08em;
}
.line-height-tight {
  line-height: 1.2;
}
.opacity-80 {
  opacity: 0.8;
}
.gap-md {
  gap: 16px;
}
.flex-1 {
  flex: 1;
}
.font-mono {
  font-family: 'SFMono-Regular', Consolas, Menlo, monospace;
}
.text-dark {
  color: #0f172a !important;
}

/* ==========================================================
   PAGE BANNER & MATCHED TITLE TYPOGRAPHY
========================================================== */
.management-header-color {
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
  background: radial-gradient(
    circle,
    rgba(255, 255, 255, 0.15) 0%,
    transparent 70%
  );
  border-radius: 50%;
  pointer-events: none;
}

/* White Block with Red Icon and Pulse Effect */
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
  0% {
    transform: translate(-50%, -50%) scale(1);
    opacity: 0.8;
  }
  100% {
    transform: translate(-50%, -50%) scale(1.4);
    opacity: 0;
  }
}

/* Darker Administration Badge */
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

/* Single Tab-Specific Stat Box */
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

.header-accent-glass {
  width: 5px;
  height: 24px;
  background: linear-gradient(180deg, #dc2626, #f87171);
  border-radius: 4px;
}

/* Reference Red-Tinted Header */
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

/* INSIGHTS CHIPS */
.insights-cell {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}
.insight-chip-glass {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  background: rgba(255, 255, 255, 0.85);
  border: 1px solid rgba(255, 255, 255, 1);
  color: #475569;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
}
.insight-chip-glass:hover {
  background: #ffffff;
  transform: translateY(-1px);
}

/* ==========================================================
   INPUTS & BUTTONS
========================================================== */
.search-input-glass {
  max-width: 320px;
  width: 100%;
}

.filter-select-glass {
  width: 180px;
}

.search-input-glass :deep(.q-field__control),
.filter-select-glass :deep(.q-field__control) {
  background: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(8px);
  border-radius: 10px;
  border: 1px solid rgba(255, 255, 255, 1);
}

.btn-glass {
  border-radius: 10px !important;
  font-weight: 600;
  background: rgba(255, 255, 255, 0.85) !important;
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 1) !important;
}

.btn-glass-solid {
  border-radius: 10px !important;
  font-weight: 600;
}

.btn-glass-flat {
  border-radius: 10px !important;
  font-weight: 600;
}

/* ==========================================================
   MODALS
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

.vendor-info-dialog {
  width: 680px;
}

.dialog-bg-glow-red {
  position: absolute;
  top: -80px;
  left: 50%;
  transform: translateX(-50%);
  width: 300px;
  height: 300px;
  background: radial-gradient(
    circle,
    rgba(220, 38, 38, 0.15) 0%,
    transparent 60%
  );
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

.action-icon-glass {
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

.info-store-name {
  font-size: 26px;
  font-weight: 800;
  line-height: 1.2;
  letter-spacing: -0.02em;
}
.info-owner-name {
  font-size: 15px;
  font-weight: 500;
}

.image-glass-container {
  border-radius: 16px;
  padding: 6px;
  background: rgba(255, 255, 255, 0.8);
  border: 1px solid rgba(255, 255, 255, 1);
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
}

.info-grid-glass {
  background: rgba(255, 255, 255, 0.6);
  border-radius: 16px;
  border: 1px solid rgba(255, 255, 255, 0.9);
}

.map-container-glass {
  border-radius: 16px;
  padding: 6px;
  background: rgba(255, 255, 255, 0.8);
  border: 1px solid rgba(255, 255, 255, 1);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.04);
}

.close-btn-glass:hover {
  background: rgba(0, 0, 0, 0.05);
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

/* ==========================================================
   DARK MODE OVERRIDES SCOPED TO MANAGE VENDORS
========================================================== */
body.body--dark {
  .modal-label-sub {
    color: #94a3b8 !important;
  }

  .modal-value-main {
    color: #f8fafc !important;
  }

  .modal-divider {
    border-color: rgba(255, 255, 255, 0.08) !important;
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

  .insight-chip-glass {
    background: rgba(30, 41, 59, 0.75) !important;
    border-color: rgba(255, 255, 255, 0.12) !important;
    color: #e2e8f0 !important;
  }
  .insight-chip-glass:hover {
    background: rgba(51, 65, 85, 0.9) !important;
    color: #ffffff !important;
  }
}

/* ==========================================================
   RESPONSIVE & PRINT SCOPED OVERRIDES
========================================================== */
@media print {
  @page {
    size: auto;
    margin: 10mm;
  }

  .management-header-color,
  .toolbar,
  .panel-header {
    display: none !important;
  }

  :deep(.print-hide) {
    display: none !important;
  }
  :deep(.print-only) {
    display: block !important;
  }

  .admin-page {
    background: white !important;
    min-height: auto !important;
  }

  .page-container {
    max-width: 100% !important;
    padding: 0 !important;
    margin: 0 !important;
    width: 100vw !important;
  }

  .glass-card {
    box-shadow: none !important;
    border: none !important;
    background: white !important;
  }

  :deep(.custom-glass-table) {
    background: white !important;
    width: 100% !important;
  }

  :deep(.custom-glass-table table) {
    width: 100% !important;
  }

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
  .page-container {
    padding: 20px 16px;
  }
  .management-header-color {
    flex-direction: column;
    align-items: flex-start;
  }
  .header-stat-box {
    margin-top: 14px;
    width: 100%;
  }
}

@media (max-width: 600px) {
  .toolbar {
    flex-direction: column;
    align-items: stretch;
    gap: 12px;
  }
  .search-input-glass,
  .filter-select-glass {
    max-width: 100%;
    width: 100%;
  }
  .export-btn {
    margin-left: 0;
    width: 100%;
  }
}
</style>