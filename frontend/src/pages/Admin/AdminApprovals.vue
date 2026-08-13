<template>
  <q-page class="admin-page">
    <div class="page-container">
      <!-- ================= PAGE BANNER (Colored Processing Hub Header) ================= -->
      <div
        class="approvals-header-colored q-mb-lg row items-center justify-between q-pa-lg"
      >
        <div class="header-bg-glow"></div>

        <div
          class="row items-center col-12 col-md-8 relative-position"
          style="z-index: 2"
        >
          <div
            class="header-icon-box-3d q-mr-lg flex flex-center relative-position"
          >
            <q-icon name="how_to_reg" size="36px" color="red-9" />
            <div class="pulse-ring"></div>
          </div>

          <div>
            <div class="q-mb-sm flex items-center">
              <span class="badge-dark-glass">
                <q-icon
                  name="admin_panel_settings"
                  size="14px"
                  class="q-mr-xs"
                />
                Administration
              </span>
              <span
                class="text-caption text-white opacity-80 q-ml-sm text-weight-bold tracking-wide"
              >
                APPLICATION QUEUE
              </span>
            </div>

            <h1
              class="text-h4 text-weight-bolder text-white q-mt-none q-mb-xs line-height-tight"
            >
              Vendor Approvals
            </h1>

            <div
              class="text-white opacity-80 row items-center text-body2 text-weight-medium"
            >
              Review, verify, and process pending vendor applications.
            </div>
          </div>
        </div>

        <div
          class="col-12 col-md-4 text-right q-mt-md q-md-mt-none flex justify-end relative-position"
          style="z-index: 2"
        >
          <div class="stats-widget-glass q-pa-md">
            <div
              class="text-caption text-white opacity-70 text-weight-bold text-uppercase q-mb-xs"
              >Pending Review</div
            >
            <div class="row items-baseline justify-end">
              <span class="text-h3 text-weight-bolder text-white">{{
                pendingCount
              }}</span>
              <span
                class="text-body2 text-white opacity-80 text-weight-bold q-ml-sm"
                >applications</span
              >
            </div>
          </div>
        </div>
      </div>

      <!-- ================= TOOLBAR (Glass) ================= -->
      <div
        class="toolbar glass-toolbar q-pa-md q-mb-md row items-center justify-between"
      >
        <div class="row items-center gap-md flex-1">
          <q-input
            v-model="search"
            outlined
            dense
            placeholder="Search by name, email, or store..."
            class="search-input-glass"
            @update:model-value="fetchPending"
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
          :loading="isExporting"
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
            @update:model-value="fetchPending"
          >
            <q-tab
              name="pending"
              label="Pending Applications"
              class="text-weight-bold"
            />
            <q-tab
              name="rejected"
              label="Rejected Applications"
              class="text-weight-bold"
            />
          </q-tabs>
        </div>

        <!-- TABLE -->
        <q-table
          flat
          class="custom-glass-table interactive-table"
          :rows="filteredApplications"
          :columns="columns"
          row-key="approval_id"
          :loading="loading"
          no-data-label="No applications found"
          @row-click="openVendorInfo"
        >
          <template #loading>
            <q-inner-loading showing color="red-8" class="bg-transparent">
              <q-spinner-dots size="40px" />
            </q-inner-loading>
          </template>

          <!-- INTERACTIVE ACTIONS -->
          <template #body-cell-actions="props">
            <q-td :props="props" align="right">
              <div class="flex items-center gap-sm justify-end">
                <template v-if="props.row.status === 'pending'">
                  <q-btn
                    label="Approve"
                    icon="check_circle"
                    no-caps
                    dense
                    unelevated
                    class="btn-approve-3d q-px-sm"
                    @click.stop="handleApprove(props.row)"
                  />
                  <q-btn
                    label="Reject"
                    icon="cancel"
                    no-caps
                    dense
                    outline
                    class="btn-reject-3d q-px-sm"
                    @click.stop="openRejectModal(props.row)"
                  />
                </template>
                <template v-else>
                  <q-chip
                    dense
                    class="rejected-chip-3d text-weight-bold q-px-md"
                    icon="block"
                  >
                    Rejected
                  </q-chip>
                </template>
              </div>
            </q-td>
          </template>

          <template #no-data>
            <div
              class="full-width column flex-center q-py-xl empty-state-glass"
            >
              <div class="empty-icon-3d q-mb-md">
                <q-icon name="inbox" color="grey-6" size="40px" />
              </div>
              <div class="text-h6 text-weight-bold text-grey-8"
                >No applications found</div
              >
              <div class="text-body2 text-grey-6"
                >There are currently no items matching your criteria.</div
              >
            </div>
          </template>
        </q-table>
      </q-card>
    </div>

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
          <div class="text-h6 text-weight-bold text-dark row items-center">
            <div class="header-accent-3d q-mr-sm"></div>
            Application Review
          </div>
          <q-btn
            icon="close"
            flat
            round
            dense
            color="grey-7"
            @click="showVendorInfoModal = false"
          />
        </q-card-section>

        <q-card-section
          class="q-pa-lg scroll"
          style="max-height: 65vh"
          v-if="selectedVendor"
        >
          <div class="text-center q-mb-lg">
            <div class="info-store-name q-mb-xs">{{
              selectedVendor.store?.store_name || 'N/A'
            }}</div>
            <div class="info-owner-name text-grey-7 q-mb-md"
              >Owned by:
              {{
                selectedVendor.store?.owner?.full_name ||
                selectedVendor.owner_name
              }}</div
            >

            <div class="image-3d-container">
              <q-img
                v-if="
                  selectedVendor.store?.store_picture_url &&
                  selectedVendor.store.store_picture_url !== 'null' &&
                  selectedVendor.store.store_picture_url.trim() !== ''
                "
                :src="selectedVendor.store.store_picture_url"
                style="width: 100%; height: 220px"
                fit="cover"
                class="rounded-borders"
              >
                <template v-slot:error>
                  <div class="absolute-full flex flex-center empty-state-glass">
                    <q-icon name="storefront" size="64px" color="grey-5" />
                  </div>
                </template>
              </q-img>

              <div
                v-else
                class="empty-state-glass flex flex-center full-width rounded-borders"
                style="height: 220px"
              >
                <q-icon name="storefront" size="64px" color="grey-5" />
              </div>
            </div>
          </div>

          <div class="row q-col-gutter-y-md q-col-gutter-x-xl q-mb-lg">
            <div class="col-12 col-sm-6">
              <div
                class="text-caption text-grey-6 text-uppercase text-weight-bold"
                >Contact Email</div
              >
              <div class="text-subtitle2 text-weight-bold text-dark">{{
                selectedVendor.email
              }}</div>
            </div>
            <div class="col-12 col-sm-6">
              <div
                class="text-caption text-grey-6 text-uppercase text-weight-bold"
                >Contact Phone</div
              >
              <div class="text-subtitle2 text-weight-bold text-dark">{{
                selectedVendor.phone || 'N/A'
              }}</div>
            </div>
            <div class="col-12 col-sm-6">
              <div
                class="text-caption text-grey-6 text-uppercase text-weight-bold"
                >Operating Days</div
              >
              <div class="text-subtitle2 text-weight-bold text-dark">{{
                formatOperatingDays(selectedVendor.store?.operating_days)
              }}</div>
            </div>
            <div class="col-12 col-sm-6">
              <div
                class="text-caption text-grey-6 text-uppercase text-weight-bold"
                >Business Hours</div
              >
              <div class="text-subtitle2 text-weight-bold text-dark"
                >{{ selectedVendor.store?.opening_time || 'N/A' }} -
                {{ selectedVendor.store?.closing_time || 'N/A' }}</div
              >
            </div>
          </div>

          <div class="map-container-3d" v-if="isValidLocation(selectedVendor)">
            <iframe
              :src="
                getMapUrl(
                  selectedVendor.store.latitude,
                  selectedVendor.store.longitude
                )
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
                label="Open in Google Maps"
                no-caps
                class="btn-3d-outline q-px-md"
                text-color="blue-8"
                icon="map"
                :href="`https://www.google.com/maps/dir/?api=1&destination=${selectedVendor.store.latitude},${selectedVendor.store.longitude}`"
                target="_blank"
              />
            </div>
          </div>
        </q-card-section>

        <q-separator color="grey-3" />

        <q-card-actions
          align="right"
          class="q-pa-md dialog-actions-glass"
          v-if="selectedVendor"
        >
          <template v-if="selectedVendor.status === 'pending'">
            <q-btn
              flat
              label="Reject Application"
              color="red-8"
              no-caps
              class="btn-reject-3d q-px-md"
              @click="openRejectModal(selectedVendor); showVendorInfoModal = false;"
            />
            <q-btn
              unelevated
              label="Approve Vendor"
              icon="check_circle"
              color="white"
              no-caps
              class="btn-approve-3d q-px-md q-ml-sm"
              @click="handleApprove(selectedVendor); showVendorInfoModal = false;"
            />
          </template>
          <template v-else-if="selectedVendor.status === 'rejected'">
            <q-btn
              flat
              label="Close"
              color="grey-8"
              no-caps
              class="btn-3d-outline q-px-md"
              @click="showVendorInfoModal = false"
            />
            <q-btn
              unelevated
              label="Re-evaluate & Approve"
              icon="restore"
              color="white"
              no-caps
              class="btn-approve-3d q-px-md q-ml-sm"
              style="
                background: linear-gradient(
                  180deg,
                  #f97316 0%,
                  #ea580c 100%
                ) !important;
              "
              @click="handleApprove(selectedVendor); showVendorInfoModal = false;"
            />
          </template>
          <template v-else>
            <q-btn
              outline
              label="Close"
              color="grey-8"
              no-caps
              class="btn-3d-outline q-px-md"
              @click="showVendorInfoModal = false"
            />
          </template>
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- ================= REJECT MODAL ================= -->
    <q-dialog
      v-model="showRejectModal"
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
          <div class="action-icon-3d bg-red-1 text-red-9 q-mb-md q-mx-auto">
            <q-icon name="warning" size="36px" />
          </div>
          <div class="text-h5 text-weight-bold text-dark q-mb-sm"
            >Reject Application</div
          >
          <p class="text-body2 text-grey-7 q-px-md">
            Action requires justification. Please provide a reason for rejecting
            this application. This will be visible in records.
          </p>
          <q-input
            v-model="rejectionReason"
            type="textarea"
            outlined
            dense
            placeholder="e.g., Incomplete documentation, suspicious activity..."
            class="custom-glass-input text-left q-mt-md"
            autofocus
          />
        </q-card-section>

        <q-card-actions align="center" class="q-pa-md dialog-actions-glass">
          <q-btn
            flat
            label="Cancel"
            color="grey-8"
            no-caps
            class="btn-3d-outline q-px-md q-mr-sm"
            v-close-popup
          />
          <q-btn
            unelevated
            label="Confirm Rejection"
            color="red-9"
            no-caps
            class="btn-3d q-px-md"
            :loading="actionLoading"
            @click="handleReject"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- ================= APPROVE MODAL ================= -->
    <q-dialog
      v-model="showApproveModal"
      persistent
      transition-show="scale"
      transition-hide="scale"
    >
      <q-card class="review-dialog-glass text-center">
        <div class="dialog-bg-glow-green"></div>
        <q-card-section
          class="q-pt-xl q-pb-md relative-position"
          style="z-index: 2"
        >
          <div class="action-icon-3d bg-green-1 text-green-7 q-mb-md q-mx-auto">
            <q-icon name="check_circle" size="36px" />
          </div>
          <div class="text-h5 text-weight-bold text-dark q-mb-sm"
            >Approve Vendor</div
          >
          <p class="text-body1 text-grey-7 q-px-md">
            Are you sure you want to approve this application?
            <strong>{{ approveTarget?.store_name }}</strong> will immediately
            gain full access to the Vendor Dashboard.
          </p>
        </q-card-section>

        <q-card-actions align="center" class="q-pa-md dialog-actions-glass">
          <q-btn
            flat
            label="Cancel"
            color="grey-8"
            no-caps
            class="btn-3d-outline q-px-md q-mr-sm"
            v-close-popup
          />
          <q-btn
            unelevated
            label="Confirm Approval"
            color="green-7"
            no-caps
            class="btn-approve-3d q-px-md"
            :loading="actionLoading"
            @click="handleApproveConfirm"
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
const pending = ref([])
const currentTab = ref('pending')

const filteredApplications = computed(() => {
  return pending.value.filter(app => app.status === currentTab.value)
})

const pendingCount = computed(() => {
  return pending.value.filter(app => app.status === 'pending').length
})

const showRejectModal = ref(false)
const showApproveModal = ref(false)
const rejectTarget = ref(null)
const approveTarget = ref(null)
const rejectionReason = ref('')

const showVendorInfoModal = ref(false)
const selectedVendor = ref(null)

// FIXED: Added classes: 'print-hide' and headerClasses: 'print-hide' to the actions column
const columns = [
  {
    name: 'owner_name',
    label: 'Name',
    field: 'owner_name',
    align: 'left',
    sortable: true
  },
  {
    name: 'email',
    label: 'Email',
    field: 'email',
    align: 'left',
    sortable: true
  },
  { name: 'phone', label: 'Phone', field: 'phone', align: 'left' },
  {
    name: 'store_name',
    label: 'Store Name',
    field: 'store_name',
    align: 'left'
  },
  {
    name: 'applied_at',
    label: 'Applied',
    field: 'applied_at',
    align: 'left',
    format: val => (val ? new Date(val).toLocaleDateString() : '—')
  },
  {
    name: 'actions',
    label: 'Actions',
    field: 'actions',
    align: 'right',
    classes: 'print-hide',
    headerClasses: 'print-hide'
  }
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
  } catch (e) {
    return 'N/A'
  }
}

const isValidLocation = vendor => {
  if (!vendor?.store?.latitude || !vendor?.store?.longitude) return false
  return (
    !isNaN(parseFloat(vendor.store.latitude)) &&
    !isNaN(parseFloat(vendor.store.longitude))
  )
}

const getMapUrl = (lat, lng) => {
  const parsedLat = parseFloat(lat)
  const parsedLng = parseFloat(lng)

  if (isNaN(parsedLat) || isNaN(parsedLng)) return ''

  const bbox = `${parsedLng - 0.01}%2C${parsedLat - 0.01}%2C${parsedLng + 0.01}%2C${parsedLat + 0.01}`
  return `https://www.openstreetmap.org/export/embed.html?bbox=${bbox}&layer=mapnik&marker=${parsedLat}%2C${parsedLng}`
}

const openVendorInfo = (evt, row) => {
  if (evt && (evt.target.closest('.q-btn') || evt.target.closest('.gap-sm')))
    return
  selectedVendor.value = row
  showVendorInfoModal.value = true
}

const handleApprove = row => {
  approveTarget.value = row
  showApproveModal.value = true
}

const handleApproveConfirm = async () => {
  if (!approveTarget.value) return

  actionLoading.value = true
  try {
    await api.post(`/admin/vendors/${approveTarget.value.store_id}/approve`)
    pending.value = pending.value.filter(
      p => p.store_id !== approveTarget.value.store_id
    )
    showApproveModal.value = false
  } catch {
    // Error handling
  } finally {
    actionLoading.value = false
  }
}

const openRejectModal = row => {
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
    pending.value = pending.value.filter(
      p => p.store_id !== rejectTarget.value.store_id
    )
    showRejectModal.value = false
  } catch {
    // Error handling
  } finally {
    actionLoading.value = false
  }
}

const isExporting = ref(false)

const handleExport = async () => {
  if (isExporting.value) return
  isExporting.value = true
  try {
    const response = await api.get('/admin/vendors/pending/export', {
      params: { search: search.value },
      responseType: 'blob'
    })
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    const dateStr = new Date().toISOString().split('T')[0]
    link.setAttribute('download', `Tindahan_Admin_Approvals_Export_${dateStr}.pdf`)
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
  fetchPending()
})
</script>

<!-- Global unscoped style block specifically for targeting layout resets during print -->
<style>
@media print {
  /* Hide main application layout wrappers */
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

<!-- Existing scoped styles for the current component -->
<style scoped>
/* ==========================================================
   AMBIENT BACKGROUND 
========================================================== */
.admin-page {
  background-color: #f1f5f9;
  background-image:
    radial-gradient(
      circle at 0% 0%,
      rgba(220, 38, 38, 0.05) 0%,
      transparent 500px
    ),
    radial-gradient(
      circle at 100% 100%,
      rgba(15, 23, 42, 0.03) 0%,
      transparent 400px
    );
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

.tracking-wide {
  letter-spacing: 0.08em;
}
.line-height-tight {
  line-height: 1.2;
}
.opacity-70 {
  opacity: 0.7;
}
.opacity-80 {
  opacity: 0.8;
}
.gap-sm {
  gap: 8px;
}
.gap-md {
  gap: 16px;
}
.flex-1 {
  flex: 1;
}
.text-dark {
  color: #0f172a !important;
}

/* ==========================================================
   COLORED PROCESSING HEADER
========================================================== */
.approvals-header-colored {
  background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
  border-radius: 16px;
  box-shadow:
    0 15px 35px rgba(220, 38, 38, 0.25),
    inset 0 2px 5px rgba(255, 255, 255, 0.2);
  position: relative;
  overflow: hidden;
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.header-bg-glow {
  position: absolute;
  top: -50px;
  left: 10%;
  width: 300px;
  height: 300px;
  background: radial-gradient(
    circle,
    rgba(255, 255, 255, 0.15) 0%,
    transparent 70%
  );
  border-radius: 50%;
  pointer-events: none;
}

.header-icon-box-3d {
  width: 72px;
  height: 72px;
  border-radius: 16px;
  background: linear-gradient(135deg, #ffffff 0%, #fef2f2 100%);
  box-shadow:
    8px 8px 20px rgba(0, 0, 0, 0.2),
    inset 2px 2px 4px rgba(255, 255, 255, 1);
  z-index: 2;
}

.pulse-ring {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 100%;
  height: 100%;
  border-radius: 16px;
  border: 2px solid rgba(255, 255, 255, 0.6);
  animation: pulse-animation 2s infinite;
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

.badge-dark-glass {
  display: inline-flex;
  align-items: center;
  background: rgba(0, 0, 0, 0.2);
  color: #ffffff;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  border: 1px solid rgba(255, 255, 255, 0.15);
  box-shadow: inset 0 1px 2px rgba(255, 255, 255, 0.2);
}

.stats-widget-glass {
  background: rgba(0, 0, 0, 0.15);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 12px;
  box-shadow: inset 0 2px 5px rgba(255, 255, 255, 0.1);
  min-width: 180px;
}

/* ==========================================================
   GLASSMORPHISM CORE & TINTED TABLE
========================================================== */
.glass-toolbar {
  background: rgba(255, 255, 255, 0.5);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.4);
  border-radius: 12px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
}

.glass-card {
  background: rgba(255, 255, 255, 0.65);
  backdrop-filter: blur(16px);
  border: 1px solid rgba(255, 255, 255, 0.4);
  border-top: 1px solid rgba(255, 255, 255, 0.9);
  border-left: 1px solid rgba(255, 255, 255, 0.9);
  border-radius: 18px;
  box-shadow:
    0 10px 30px rgba(0, 0, 0, 0.04),
    inset 0 -2px 5px rgba(255, 255, 255, 0.3);
}

.table-glass-container {
  overflow: hidden;
}

.panel-header {
  background: linear-gradient(
    90deg,
    rgba(254, 242, 242, 0.7) 0%,
    transparent 100%
  );
  border-bottom: 1px solid rgba(255, 255, 255, 0.5);
}

.header-accent-3d {
  width: 5px;
  height: 24px;
  background: linear-gradient(180deg, #f87171, #dc2626);
  border-radius: 4px;
  box-shadow: 2px 2px 5px rgba(220, 38, 38, 0.3);
}

/* ==========================================================
   TABLE & INTERACTIVE ELEMENTS
========================================================== */
:deep(.custom-glass-table) {
  background: rgba(248, 250, 252, 0.4);
}

:deep(.custom-glass-table thead tr th) {
  background: rgba(220, 38, 38, 0.08);
  backdrop-filter: blur(4px);
  font-weight: 800;
  color: #991b1b;
  text-transform: uppercase;
  font-size: 12px;
  letter-spacing: 0.05em;
  padding: 16px 24px;
  border-bottom: 1px solid rgba(220, 38, 38, 0.15);
}

:deep(.custom-glass-table tbody td) {
  padding: 16px 24px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.03);
  font-size: 14px;
  color: #1e293b;
}

:deep(.interactive-table tbody tr) {
  transition: all 0.2s ease;
  cursor: pointer;
}

:deep(.interactive-table tbody tr:hover) {
  background-color: rgba(255, 255, 255, 0.95);
  transform: scale(1.002);
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
  z-index: 2;
  position: relative;
}

.empty-state-glass {
  background: rgba(255, 255, 255, 0.3);
}

.empty-icon-3d {
  padding: 16px;
  border-radius: 50%;
  background: linear-gradient(135deg, #f8fafc, #f1f5f9);
  box-shadow:
    4px 4px 10px rgba(0, 0, 0, 0.05),
    -4px -4px 10px rgba(255, 255, 255, 0.8),
    inset 1px 1px 3px rgba(255, 255, 255, 1);
}

/* ==========================================================
   INPUTS & BUTTONS (3D)
========================================================== */
.search-input-glass {
  max-width: 320px;
  width: 100%;
}
.search-input-glass :deep(.q-field__control) {
  background: rgba(255, 255, 255, 0.6);
  backdrop-filter: blur(8px);
  border-radius: 8px;
  box-shadow: inset 1px 1px 3px rgba(0, 0, 0, 0.05);
}

.btn-3d {
  border-radius: 8px !important;
  font-weight: 600;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
  transition: all 0.2s ease;
}
.btn-3d:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
}
.btn-3d:active {
  transform: translateY(1px);
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.btn-approve-3d {
  background: linear-gradient(180deg, #22c55e 0%, #16a34a 100%) !important;
  color: white !important;
  border-radius: 8px !important;
  font-weight: 700;
  box-shadow:
    0 4px 10px rgba(22, 163, 74, 0.3),
    inset 0 1px 1px rgba(255, 255, 255, 0.4);
  transition: all 0.2s ease;
  border: 1px solid #15803d;
}
.btn-approve-3d:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 12px rgba(22, 163, 74, 0.4);
}

.btn-reject-3d {
  border-radius: 8px !important;
  font-weight: 700;
  background: rgba(255, 255, 255, 0.9) !important;
  color: #dc2626 !important;
  border: 1px solid #ef4444 !important;
  box-shadow: 0 2px 5px rgba(220, 38, 38, 0.1);
  transition: all 0.2s ease;
}
.btn-reject-3d:hover {
  background: #fef2f2 !important;
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(220, 38, 38, 0.15);
}

.btn-3d-outline {
  border-radius: 8px !important;
  font-weight: 600;
  background: rgba(255, 255, 255, 0.5) !important;
  backdrop-filter: blur(4px);
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
  transition: all 0.2s ease;
}
.btn-3d-outline:hover {
  background: rgba(255, 255, 255, 0.8) !important;
  transform: translateY(-1px);
}

.rejected-chip-3d {
  background: linear-gradient(135deg, #fef2f2, #fee2e2) !important;
  color: #dc2626;
  border: 1px solid rgba(220, 38, 38, 0.2);
  box-shadow:
    2px 2px 5px rgba(0, 0, 0, 0.05),
    inset 1px 1px 0 rgba(255, 255, 255, 0.8);
}

/* ==========================================================
   MODALS (Solid Background for readability)
========================================================== */
.review-dialog-glass {
  width: 500px;
  max-width: 95vw;
  border-radius: 20px !important;
  background: #ffffff;
  border: 1px solid rgba(255, 255, 255, 0.8);
  box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
  overflow: hidden;
}

.vendor-info-dialog {
  width: 650px;
}

.dialog-bg-glow-red {
  position: absolute;
  top: -80px;
  left: 50%;
  transform: translateX(-50%);
  width: 250px;
  height: 250px;
  background: radial-gradient(
    circle,
    rgba(220, 38, 38, 0.1) 0%,
    transparent 70%
  );
  border-radius: 50%;
  z-index: 1;
  pointer-events: none;
}

.dialog-bg-glow-green {
  position: absolute;
  top: -80px;
  left: 50%;
  transform: translateX(-50%);
  width: 250px;
  height: 250px;
  background: radial-gradient(
    circle,
    rgba(34, 197, 94, 0.15) 0%,
    transparent 70%
  );
  border-radius: 50%;
  z-index: 1;
  pointer-events: none;
}

.dialog-actions-glass {
  background: rgba(248, 250, 252, 0.9);
  border-top: 1px solid rgba(226, 232, 240, 1);
}

.custom-glass-input :deep(.q-field__control) {
  background: rgba(255, 255, 255, 0.6);
  border-radius: 8px;
}

.action-icon-3d {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 72px;
  height: 72px;
  border-radius: 50%;
  box-shadow:
    6px 6px 12px rgba(0, 0, 0, 0.08),
    -4px -4px 10px rgba(255, 255, 255, 1),
    inset 2px 2px 5px rgba(255, 255, 255, 0.5);
}

.info-store-name {
  font-size: 24px;
  font-weight: 800;
  color: #111;
  line-height: 1.2;
}
.info-owner-name {
  font-size: 15px;
}

.image-3d-container {
  border-radius: 8px;
  padding: 4px;
  background: #ffffff;
  box-shadow:
    0 10px 25px rgba(0, 0, 0, 0.1),
    inset 0 0 0 1px rgba(0, 0, 0, 0.05);
}

.map-container-3d {
  border-radius: 12px;
  padding: 8px;
  background: #ffffff;
  box-shadow:
    inset 2px 2px 5px rgba(0, 0, 0, 0.05),
    inset -2px -2px 5px rgba(255, 255, 255, 1),
    0 4px 15px rgba(0, 0, 0, 0.05);
}

/* ==========================================================
   RESPONSIVE & PRINT SCOPED OVERRIDES
========================================================== */
@media print {
  @page {
    size: auto;
    margin: 10mm;
  }

  .approvals-header-colored,
  .toolbar,
  .panel-header {
    display: none !important;
  }

  /* FIXED: Added targeting for our specific print-hide class */
  :deep(.print-hide) {
    display: none !important;
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

  /* FIXED: Force table layout to respect paper boundaries and allow wrapping */
  :deep(.custom-glass-table) {
    background: white !important;
    width: 100% !important;
  }

  :deep(.custom-glass-table table) {
    width: 100% !important;
  }

  /* FIXED: Dramatically reduced padding and allowed wrapping for print mode */
  :deep(.custom-glass-table thead tr th),
  :deep(.custom-glass-table tbody td) {
    padding: 6px 4px !important;
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
    padding: 20px;
  }
  .approvals-header-colored > .row {
    flex-direction: column;
    align-items: flex-start;
  }
  .stats-widget-glass {
    width: 100%;
    justify-content: flex-start;
  }
}

@media (max-width: 600px) {
  .page-container {
    padding: 16px;
  }
  .toolbar > .row {
    flex-direction: column;
    align-items: stretch;
    width: 100%;
  }
  .search-input-glass {
    max-width: 100%;
  }
  .export-btn {
    margin-top: 12px;
    margin-left: 0;
    width: 100%;
  }
}
</style>
