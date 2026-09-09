<template>
  <q-page class="admin-page">
    <div class="page-container">
      <!-- ================= PAGE BANNER ================= -->
      <div class="management-header-color q-mb-lg row items-center justify-between q-pa-lg">
        <div class="header-bg-glow"></div>

        <div class="row items-center col-12 col-md-8 relative-position" style="z-index: 2">
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
          <q-input
            v-model="search"
            outlined
            dense
            placeholder="Search by name, email, or store..."
            class="search-input-glass"
            debounce="300"
            hide-bottom-space
            @update:model-value="fetchVendors"
          >
            <template #prepend>
              <q-icon name="search" color="red-4" />
            </template>
            <template #append v-if="search">
              <q-icon name="close" class="cursor-pointer" size="18px" @click="search = ''; fetchVendors()" />
            </template>
          </q-input>

          <!-- Status Filter (Plain "Filter") -->
          <q-select
            v-model="statusFilter"
            outlined
            dense
            emit-value
            map-options
            clearable
            :display-value="statusFilter ? undefined : 'Filter'"
            class="filter-select-glass"
            hide-bottom-space
            :options="statusFilterOptions"
            @update:model-value="fetchVendors"
          >
            <template #prepend>
              <q-icon name="filter_list" color="red-4" size="18px" />
            </template>
          </q-select>
        </div>

        <q-btn
          label="Export PDF"
          no-caps
          outline
          icon="print"
          class="btn-glass export-btn q-ml-auto"
          color="red-9"
          @click="handleExport"
          :loading="isExporting"
        />
      </div>

      <!-- ================= MAIN TABLE AREA ================= -->
      <q-card flat class="glass-card table-glass-container">
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
            <q-tab name="active" label="Active Accounts" class="text-weight-bold" />
            <q-tab name="deleted" label="Deleted Accounts" class="text-weight-bold" />
          </q-tabs>
        </div>

        <!-- SKELETON LOADER -->
        <q-markup-table v-if="loading" flat class="custom-glass-table full-width">
          <thead>
            <tr>
              <th class="text-left">NAME</th>
              <th class="text-left">EMAIL</th>
              <th class="text-left">PHONE</th>
              <th class="text-left">STORE NAME</th>
              <th class="text-left">APPLIED</th>
              <th class="text-center">STATUS</th>
              <th class="text-center">ACTIONS</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="n in 6" :key="n">
              <td class="text-left">
                <q-skeleton type="text" width="120px" class="q-mb-xs" animation="fade" />
                <q-skeleton type="text" width="70px" height="12px" animation="fade" />
              </td>
              <td class="text-left"><q-skeleton type="text" width="140px" animation="fade" /></td>
              <td class="text-left"><q-skeleton type="text" width="100px" animation="fade" /></td>
              <td class="text-left"><q-skeleton type="text" width="130px" animation="fade" /></td>
              <td class="text-left"><q-skeleton type="text" width="90px" animation="fade" /></td>
              <td class="text-center">
                <q-skeleton type="QBadge" width="110px" style="border-radius: 9999px; height: 28px; margin: 0 auto;" animation="fade" />
              </td>
              <td class="text-center">
                <div class="row justify-center q-gutter-x-sm">
                  <q-skeleton type="QBadge" width="80px" style="border-radius: 20px; height: 28px;" animation="fade" />
                  <q-skeleton type="QBadge" width="75px" style="border-radius: 20px; height: 28px;" animation="fade" />
                  <q-skeleton type="QBadge" width="85px" style="border-radius: 8px; height: 28px;" animation="fade" />
                </div>
              </td>
            </tr>
          </tbody>
        </q-markup-table>

        <!-- DATA TABLE -->
        <q-table
          v-else
          flat
          class="custom-glass-table interactive-table"
          :rows="vendors"
          :columns="columns"
          row-key="user_id"
          @row-click="openVendorInfo"
          :pagination="{ rowsPerPage: 15 }"
          hide-bottom
        >
          <template #body-cell-full_name="props">
            <q-td :props="props">
              <div class="text-weight-bold text-slate-800">{{ props.row.full_name }}</div>
              <div class="text-caption text-grey-6 font-mono">ID: {{ props.row.user_id }}</div>
            </q-td>
          </template>

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
                  popup-content-class="status-dropdown-popup"
                  @update:model-value="val => updateStatus(props.row.user_id, val)"
                >
                  <template v-slot:selected>
                    <div class="text-weight-bold row items-center justify-center no-wrap text-capitalize status-selected-label" @click.stop>
                      <span class="status-indicator-dot q-mr-xs"></span>
                      <span>{{ props.row.account_status }}</span>
                    </div>
                  </template>
                </q-select>
              </div>
            </q-td>
          </template>

          <template #body-cell-last_activity_at="props">
            <q-td :props="props" class="text-grey-8 text-weight-medium">
              <div class="row items-center no-wrap">
                <q-icon name="schedule" color="red-4" class="q-mr-xs print-hide" size="16px" />
                <span>{{ formatActivity(props.row.last_activity_at || props.row.created_at) }}</span>
              </div>
            </q-td>
          </template>

          <!-- REVITALIZED ACTIONS COLUMN: LABELED CHIPS + LABELED VIEW BUTTON -->
          <template #body-cell-insights="props">
            <q-td :props="props" class="text-center">
              <div class="insights-cell print-hide items-center justify-center no-wrap">
                <span class="insight-chip-glass orders-chip">
                  <q-icon name="receipt_long" size="14px" />
                  <span>{{ props.row.orders_count || 0 }} orders</span>
                </span>
                <span class="insight-chip-glass products-chip">
                  <q-icon name="inventory_2" size="14px" />
                  <span>{{ props.row.store?.inventory_count || props.row.active_products || 0 }} items</span>
                </span>

                <q-btn
                  unelevated
                  no-caps
                  dense
                  color="red-9"
                  icon="visibility"
                  label="View"
                  class="action-view-btn q-px-sm"
                  @click.stop="openVendorInfoDirect(props.row)"
                />
              </div>
            </q-td>
          </template>

          <template #no-data>
            <div class="full-width column flex-center q-py-xl empty-state-glass">
              <div class="empty-icon-glass q-mb-md">
                <q-icon name="people_outline" color="red-3" size="40px" />
              </div>
              <div class="text-h6 text-weight-bold text-slate-800">No vendors found</div>
              <div class="text-body2 text-slate-500 q-mt-xs">There are currently no accounts matching your criteria.</div>
            </div>
          </template>
        </q-table>
      </q-card>
    </div>

    <!-- ================= REVITALIZED VENDOR PROFILE DETAILS MODAL ================= -->
    <q-dialog v-model="showVendorInfoModal" transition-show="scale" transition-hide="scale">
      <q-card class="vendor-profile-dialog bg-white overflow-hidden" v-if="selectedVendor">
        
        <!-- ROOMIER & BALANCED RED GRADIENT HEADER -->
        <div class="modal-gradient-header row items-center justify-between q-py-md q-px-lg relative-position">
          <div class="header-ambient-circle"></div>
          
          <div class="row items-center q-gutter-x-md relative-position" style="z-index: 2;">
            <span class="text-h6 text-weight-bolder text-white tracking-tight">
              Vendor Information
            </span>
            <span class="vendor-header-id-pill font-mono">
              {{ selectedVendor.merchant_code || `VNDR-${selectedVendor.user_id}` }}
            </span>
          </div>

          <q-btn icon="close" flat round dense color="white" size="sm" class="relative-position" style="z-index: 2;" v-close-popup />
        </div>

        <!-- Modal Body Content -->
        <q-card-section class="q-pa-lg scroll" style="max-height: 80vh">
          <!-- Panoramic Store Banner Image -->
          <div class="vendor-banner-frame q-mb-md">
            <q-img
              v-if="selectedVendor.store?.store_picture_url || selectedVendor.store_picture_url"
              :src="selectedVendor.store?.store_picture_url || selectedVendor.store_picture_url"
              class="vendor-banner-img"
              fit="cover"
            />
            <div v-else class="vendor-banner-placeholder flex flex-center">
              <q-icon name="storefront" size="48px" color="blue-grey-3" />
            </div>
          </div>

          <!-- Store Title, Owner & Total Stats -->
          <div class="row items-start justify-between q-mb-lg">
            <div>
              <div class="row items-center q-gutter-x-sm no-wrap">
                <span class="text-h5 text-weight-bolder text-red-9 tracking-tight">
                  {{ selectedVendor.store?.store_name || selectedVendor.store_name || 'Unnamed Store' }}
                </span>
                <q-badge
                  rounded
                  :color="selectedVendor.account_status === 'active' ? 'green-1' : 'red-1'"
                  :text-color="selectedVendor.account_status === 'active' ? 'positive' : 'negative'"
                  class="status-pill text-weight-bolder q-px-sm q-py-xs"
                >
                  <span class="status-dot q-mr-xs" :class="selectedVendor.account_status === 'active' ? 'bg-positive' : 'bg-negative'"></span>
                  {{ formatStatus(selectedVendor.account_status) }}
                </q-badge>
              </div>
              <div class="text-caption text-grey-7 text-weight-medium q-mt-xs">
                Owner: {{ selectedVendor.full_name }}
              </div>
            </div>

            <!-- TOTAL ORDERS & STORE ITEMS STAT BADGES -->
            <div class="row items-center q-gutter-x-sm q-mt-xs q-mt-sm-none">
              <div class="store-metric-chip row items-center">
                <div class="metric-icon-box bg-red-50 text-red-9 flex flex-center q-mr-sm">
                  <q-icon name="receipt_long" size="16px" />
                </div>
                <div>
                  <div class="text-caption text-grey-6 text-weight-bold" style="font-size: 10px; line-height: 1;">ORDERS</div>
                  <div class="text-body2 text-weight-bolder text-slate-800 font-mono" style="line-height: 1.2;">
                    {{ selectedVendor.orders_count || 0 }}
                  </div>
                </div>
              </div>

              <div class="store-metric-chip row items-center">
                <div class="metric-icon-box bg-amber-50 text-amber-9 flex flex-center q-mr-sm">
                  <q-icon name="inventory_2" size="16px" />
                </div>
                <div>
                  <div class="text-caption text-grey-6 text-weight-bold" style="font-size: 10px; line-height: 1;">ITEMS</div>
                  <div class="text-body2 text-weight-bolder text-slate-800 font-mono" style="line-height: 1.2;">
                    {{ selectedVendor.store?.inventory_count || selectedVendor.active_products || 0 }}
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Main Layout Grid -->
          <div class="row q-col-gutter-md items-stretch">
            <!-- Left: Operating Hours -->
            <div class="col-12 col-md-5">
              <div class="info-card h-full column justify-between">
                <div>
                  <div class="card-section-label row items-center text-red-9 q-mb-sm">
                    <q-icon name="schedule" size="16px" class="q-mr-xs" />
                    <span>OPERATING HOURS</span>
                  </div>
                  <div class="column schedule-list">
                    <div
                      v-for="schedule in parseWeeklySchedule(selectedVendor.store?.operating_days || selectedVendor.operating_days, selectedVendor)"
                      :key="schedule.day"
                      class="row items-center justify-between text-caption"
                    >
                      <span class="text-grey-7 text-weight-medium">{{ schedule.day }}</span>
                      <span :class="schedule.isOpen ? 'text-slate-800 text-weight-bold' : 'text-grey-5'">
                        {{ schedule.hours }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Right: Contact Card & Location / Map View -->
            <div class="col-12 col-md-7 column q-gutter-y-md">
              <!-- Contact Card -->
              <div class="info-card">
                <div class="card-section-label row items-center text-red-9 q-mb-xs">
                  <q-icon name="alternate_email" size="15px" class="q-mr-xs" />
                  <span>EMAIL ADDRESS</span>
                </div>
                <div class="text-body2 text-weight-bold text-slate-800 ellipsis q-mb-md">
                  {{ selectedVendor.email }}
                </div>

                <div class="card-section-label row items-center text-grey-6 q-mb-xs">
                  <q-icon name="phone" size="15px" class="q-mr-xs" />
                  <span>PHONE NUMBER</span>
                </div>
                <div class="text-body2 text-weight-bold text-slate-800 font-mono">
                  {{ selectedVendor.phone_number || 'N/A' }}
                </div>
              </div>

              <!-- Location & Map -->
              <div class="row q-col-gutter-sm flex-1 items-stretch">
                <!-- Location Details Card -->
                <div class="col-12 col-sm-6">
                  <div class="info-card h-full column justify-between">
                    <div>
                      <div class="card-section-label row items-center text-red-9 q-mb-xs">
                        <q-icon name="location_on" size="15px" class="q-mr-xs" />
                        <span>LOCATION</span>
                      </div>
                      
                      <div v-if="addressResolving" class="q-my-xs">
                        <q-skeleton type="text" width="100%" />
                        <q-skeleton type="text" width="80%" />
                      </div>
                      <div v-else class="readable-location-box q-mb-xs">
                        <div class="text-body2 text-weight-bold text-slate-800 leading-snug">
                          {{ displayedAddress }}
                        </div>
                      </div>

                      <div class="text-caption text-weight-bolder text-grey-7 font-mono q-mt-xs">
                        {{ formatCoords(getVendorLat(selectedVendor), getVendorLng(selectedVendor)) }}
                      </div>
                    </div>

                    <q-btn
                      outline
                      no-caps
                      color="red-9"
                      icon="directions"
                      label="Get Directions"
                      class="full-width q-mt-sm rounded-borders text-weight-bold"
                      size="sm"
                      :href="getDirectionsUrl(selectedVendor)"
                      target="_blank"
                    />
                  </div>
                </div>

                <!-- Map Preview with Enlarge Action Button -->
                <div class="col-12 col-sm-6">
                  <div class="map-card-wrapper h-full overflow-hidden relative-position border-slate-light rounded-borders">
                    <iframe
                      v-if="isValidLocation(selectedVendor)"
                      :src="getMapUrl(getVendorLat(selectedVendor), getVendorLng(selectedVendor))"
                      width="100%"
                      height="100%"
                      style="border: none; position: absolute; top: 0; left: 0;"
                      loading="lazy"
                    ></iframe>
                    <div v-else class="absolute-full flex flex-center bg-slate-100 column text-center q-pa-sm">
                      <q-icon name="location_off" size="28px" color="blue-grey-3" class="q-mb-xs" />
                      <span class="text-caption text-grey-6">Coordinates unavailable</span>
                    </div>

                    <q-btn
                      v-if="isValidLocation(selectedVendor)"
                      round
                      dense
                      color="white"
                      text-color="grey-9"
                      icon="fullscreen"
                      size="sm"
                      class="enlarge-map-btn shadow-2"
                      @click="showEnlargedMapModal = true"
                    >
                      <q-tooltip class="bg-dark text-caption">Enlarge Map</q-tooltip>
                    </q-btn>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </q-card-section>

        <!-- Modal Footer -->
        <q-separator />
        <q-card-actions align="right" class="q-pa-md bg-white">
          <q-btn flat label="Close" color="grey-7" no-caps class="q-px-md text-weight-bold" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- ================= ENLARGED MAP MODAL ================= -->
    <q-dialog v-model="showEnlargedMapModal" transition-show="scale" transition-hide="scale">
      <q-card class="enlarged-map-dialog bg-white overflow-hidden" v-if="selectedVendor">
        <div class="modal-gradient-header row items-center justify-between q-py-sm q-px-md">
          <div class="row items-center q-gutter-x-sm">
            <q-icon name="map" size="18px" color="white" />
            <span class="text-body2 text-weight-bold text-white">
              {{ selectedVendor.store?.store_name || selectedVendor.store_name || 'Vendor Location Map' }}
            </span>
          </div>
          <q-btn icon="close" flat round dense color="white" size="xs" v-close-popup />
        </div>

        <q-card-section class="q-pa-none relative-position" style="height: 70vh; width: 100%;">
          <iframe
            :src="getEnlargedMapUrl(getVendorLat(selectedVendor), getVendorLng(selectedVendor))"
            width="100%"
            height="100%"
            style="border: none;"
            loading="lazy"
          ></iframe>
        </q-card-section>

        <q-separator />
        <q-card-actions align="between" class="q-pa-sm bg-white">
          <div class="text-caption text-grey-8 q-ml-sm ellipsis" style="max-width: 70%;">
            <q-icon name="place" color="red-9" size="16px" />
            {{ displayedAddress }}
          </div>
          <q-btn
            outline
            color="red-9"
            icon="directions"
            label="Open in Google Maps"
            size="sm"
            no-caps
            :href="getDirectionsUrl(selectedVendor)"
            target="_blank"
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
const isExporting = ref(false)
const vendors = ref([])
const currentTab = ref('active')

const counts = ref({
  active: 0,
  deleted: 0
})

const showVendorInfoModal = ref(false)
const showEnlargedMapModal = ref(false)
const selectedVendor = ref(null)
const displayedAddress = ref('Address not provided')
const addressResolving = ref(false)

const statusFilterOptions = [
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
  { name: 'store_name', label: 'STORE NAME', field: row => row.store?.store_name || row.store_name || 'N/A', align: 'left', sortable: true },
  { name: 'last_activity_at', label: 'APPLIED', field: 'last_activity_at', align: 'left', sortable: true },
  { name: 'account_status', label: 'STATUS', field: 'account_status', align: 'center' },
  { name: 'insights', label: 'ACTIONS', field: '', align: 'center' }
]

const daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']

const getVendorLat = (v) => v?.store?.latitude ?? v?.latitude ?? v?.store?.lat ?? v?.lat ?? null
const getVendorLng = (v) => v?.store?.longitude ?? v?.longitude ?? v?.store?.lng ?? v?.lng ?? null

const isValidLocation = (vendor) => {
  const lat = parseFloat(getVendorLat(vendor))
  const lng = parseFloat(getVendorLng(vendor))
  return !isNaN(lat) && !isNaN(lng) && lat !== 0 && lng !== 0
}

const formatTimeAMPM = (timeStr) => {
  if (!timeStr) return null
  const cleaned = timeStr.toString().trim()
  if (/am|pm/i.test(cleaned)) return cleaned

  const parts = cleaned.split(':')
  if (parts.length >= 2) {
    let hours = parseInt(parts[0], 10)
    const minutes = parts[1].padStart(2, '0')
    if (isNaN(hours)) return cleaned

    const period = hours >= 12 ? 'PM' : 'AM'
    hours = hours % 12 || 12
    return `${hours.toString().padStart(2, '0')}:${minutes} ${period}`
  }
  return cleaned
}

const resolveVendorAddress = async (vendor) => {
  if (!vendor) {
    displayedAddress.value = 'Address not provided'
    return
  }

  const candidates = [
    vendor.store?.address,
    vendor.address,
    vendor.store?.full_address,
    vendor.full_address,
    vendor.store?.street_address,
    vendor.street_address,
    vendor.store?.location,
    vendor.location,
    vendor.store?.store_address,
    vendor.store_address,
    vendor.stores?.[0]?.address,
    vendor.addresses?.[0]
  ]

  for (const val of candidates) {
    if (!val) continue

    if (typeof val === 'string' && val.trim() !== '') {
      const trimmed = val.trim()
      if (trimmed.startsWith('{') && trimmed.endsWith('}')) {
        try {
          const parsed = JSON.parse(trimmed)
          const parts = [
            parsed.unit,
            parsed.street || parsed.address_line_1,
            parsed.address_line_2,
            parsed.barangay,
            parsed.city || parsed.municipality,
            parsed.province,
            parsed.postal_code || parsed.zip_code
          ].filter(Boolean)

          if (parts.length) {
            displayedAddress.value = parts.join(', ')
            return
          }
        } catch {
          displayedAddress.value = trimmed
          return
        }
      }
      displayedAddress.value = trimmed
      return
    }

    if (typeof val === 'object') {
      const parts = [
        val.unit,
        val.street || val.street_address || val.address_line_1,
        val.address_line_2,
        val.barangay,
        val.city || val.municipality,
        val.province,
        val.postal_code || val.zip_code
      ].filter(Boolean)

      if (parts.length) {
        displayedAddress.value = parts.join(', ')
        return
      }
      if (val.formatted_address) {
        displayedAddress.value = val.formatted_address
        return
      }
    }
  }

  const lat = getVendorLat(vendor)
  const lng = getVendorLng(vendor)

  if (isValidLocation(vendor)) {
    try {
      addressResolving.value = true
      const osmRes = await fetch(
        `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}`
      )
      const osmData = await osmRes.json()
      if (osmData && osmData.display_name) {
        displayedAddress.value = osmData.display_name
        return
      }
    } catch {
      // Fallback below
    } finally {
      addressResolving.value = false
    }

    displayedAddress.value = `${lat}, ${lng}`
    return
  }

  displayedAddress.value = 'Address not provided'
}

const parseWeeklySchedule = (days, vendor) => {
  const rawOpen = (vendor?.store?.opening_time || vendor?.opening_time) || '08:00'
  const rawClose = (vendor?.store?.closing_time || vendor?.closing_time) || '20:00'
  const defaultHours = `${formatTimeAMPM(rawOpen)} - ${formatTimeAMPM(rawClose)}`

  if (!days) {
    return daysOfWeek.map(day => ({ day, isOpen: true, hours: defaultHours }))
  }

  try {
    const parsed = typeof days === 'string' ? JSON.parse(days) : days
    return daysOfWeek.map(day => {
      const lower = day.toLowerCase()
      const dayData = parsed[lower] || parsed[day]
      if (dayData !== undefined) {
        const isOpen = typeof dayData === 'boolean' ? dayData : !!dayData.is_open
        let hours = 'Closed'
        if (isOpen) {
          if (dayData.opening_time && dayData.closing_time) {
            hours = `${formatTimeAMPM(dayData.opening_time)} - ${formatTimeAMPM(dayData.closing_time)}`
          } else if (dayData.hours) {
            hours = dayData.hours
          } else {
            hours = defaultHours
          }
        }
        return { day, isOpen, hours }
      }
      return { day, isOpen: true, hours: defaultHours }
    })
  } catch {
    return daysOfWeek.map(day => ({ day, isOpen: true, hours: defaultHours }))
  }
}

const formatCoords = (lat, lng) => {
  const pLat = parseFloat(lat)
  const pLng = parseFloat(lng)
  if (isNaN(pLat) || isNaN(pLng)) return 'Coordinates N/A'
  const latDir = pLat >= 0 ? 'N' : 'S'
  const lngDir = pLng >= 0 ? 'E' : 'W'
  return `${Math.abs(pLat).toFixed(4)}° ${latDir}, ${Math.abs(pLng).toFixed(4)}° ${lngDir}`
}

const getMapUrl = (lat, lng) => {
  const parsedLat = parseFloat(lat)
  const parsedLng = parseFloat(lng)
  if (isNaN(parsedLat) || isNaN(parsedLng)) return ''
  const bbox = `${parsedLng - 0.008}%2C${parsedLat - 0.008}%2C${parsedLng + 0.008}%2C${parsedLat + 0.008}`
  return `https://www.openstreetmap.org/export/embed.html?bbox=${bbox}&layer=mapnik&marker=${parsedLat}%2C${parsedLng}`
}

const getEnlargedMapUrl = (lat, lng) => {
  const parsedLat = parseFloat(lat)
  const parsedLng = parseFloat(lng)
  if (isNaN(parsedLat) || isNaN(parsedLng)) return ''
  const bbox = `${parsedLng - 0.02}%2C${parsedLat - 0.02}%2C${parsedLng + 0.02}%2C${parsedLat + 0.02}`
  return `https://www.openstreetmap.org/export/embed.html?bbox=${bbox}&layer=mapnik&marker=${parsedLat}%2C${parsedLng}`
}

const getDirectionsUrl = (vendor) => {
  const lat = getVendorLat(vendor)
  const lng = getVendorLng(vendor)
  if (lat && lng) return `https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}`

  const query = encodeURIComponent(displayedAddress.value !== 'Address not provided' ? displayedAddress.value : vendor?.store?.store_name || '')
  return `https://www.google.com/maps/search/?api=1&query=${query}`
}

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

const formatStatus = (status) => {
  if (!status) return 'Unknown'
  return status.split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ')
}

const updateStatus = async (userId, newStatus) => {
  try {
    await api.patch(`/admin/vendors/${userId}/status`, { account_status: newStatus })
    if (selectedVendor.value && selectedVendor.value.user_id === userId) {
      selectedVendor.value.account_status = newStatus
    }
    fetchVendors()
    $q.notify({ type: 'positive', message: `Vendor marked as ${formatStatus(newStatus)}.`, position: 'top-right' })
  } catch {
    fetchVendors()
  }
}

const openVendorInfo = (evt, row) => {
  if (evt.target.closest('.status-select-glass') || evt.target.closest('.q-select') || evt.target.closest('.action-view-btn')) return
  selectedVendor.value = row
  resolveVendorAddress(row)
  showVendorInfoModal.value = true
}

const openVendorInfoDirect = (row) => {
  selectedVendor.value = row
  resolveVendorAddress(row)
  showVendorInfoModal.value = true
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
    $q.notify({ type: 'negative', message: 'Failed to generate PDF export.', color: 'red-7' })
  } finally {
    isExporting.value = false
  }
}

onMounted(() => {
  fetchVendors()
})
</script>

<style scoped>
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
.line-height-tight { line-height: 1.2; }
.opacity-80 { opacity: 0.8; }
.gap-md { gap: 16px; }
.flex-1 { flex: 1; }
.font-mono { font-family: 'SFMono-Regular', Consolas, Menlo, monospace; }

/* Management Banner */
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

.stat-box-label { font-size: 10px; letter-spacing: 0.08em; color: #fecaca; line-height: 1; }
.stat-box-value { font-size: 28px; font-weight: 900; color: #ffffff; line-height: 1; }
.stat-box-unit { font-size: 11px; color: #fecaca; font-weight: 600; }

/* Glass Components */
.glass-toolbar {
  background: rgba(255, 255, 255, 0.65);
  backdrop-filter: blur(16px);
  border: 1px solid rgba(255, 255, 255, 0.8);
  border-radius: 16px;
}

.glass-card {
  background: rgba(255, 255, 255, 0.75);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.85);
  border-radius: 20px;
}

.table-glass-container { overflow: hidden; }
.panel-header { background: rgba(255, 255, 255, 0.5); border-bottom: 1px solid rgba(255, 255, 255, 0.7); }

:deep(.custom-glass-table) { background: transparent; }
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
:deep(.custom-glass-table tbody td) { padding: 16px 20px; font-size: 13.5px; }
:deep(.interactive-table tbody tr) { cursor: pointer; transition: background 0.2s ease; }
:deep(.interactive-table tbody tr:hover) { background: #f8fafc; }

/* Status Pill */
.status-select-glass {
  width: fit-content;
  display: inline-flex;
  margin: 0 auto;
}

.status-select-glass :deep(.q-field__control) {
  border-radius: 9999px;
  /* Pantay na padding sa kaliwa't kanan */
  padding: 0 10px !important;
  height: 28px !important;
  min-height: 28px !important;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(0, 0, 0, 0.08);
  background: white;
}

.status-select-glass :deep(.q-field__control-container) {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex: 0 0 auto;
  gap: 5px;
}

.status-select-glass :deep(.q-field__native) {
  font-size: 11px;
  font-weight: 800;
  padding: 0 !important;
  min-height: unset;
  line-height: normal;
  display: inline-flex;
  align-items: center;
  width: auto !important;
  flex: unset !important;
}

.status-select-glass :deep(.q-field__append) {
  padding: 0 !important;
  margin-left: 4px;
  min-width: unset;
  height: auto;
}

.status-select-glass :deep(.q-field__append .q-icon) {
  font-size: 14px;
}

.status-indicator-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  flex-shrink: 0;
}

/* Status variants */
.status-active :deep(.q-field__control) { background: #f0fdf4; border-color: #bbf7d0; }
.status-active :deep(.q-field__native) { color: #15803d; }
.status-active .status-indicator-dot { background-color: #16a34a; }

.status-inactive :deep(.q-field__control) { background: #f8fafc; border-color: #e2e8f0; }
.status-inactive :deep(.q-field__native) { color: #64748b; }
.status-inactive .status-indicator-dot { background-color: #94a3b8; }

.status-suspended :deep(.q-field__control) { background: #fef2f2; border-color: #fecaca; }
.status-suspended :deep(.q-field__native) { color: #b91c1c; }
.status-suspended .status-indicator-dot { background-color: #dc2626; }

/* Revitalized Action Column (Full Labels & Button) */
.insights-cell {
  display: flex;
  gap: 8px;
  align-items: center;
}

.insight-chip-glass {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 5px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 700;
  background: rgba(255, 255, 255, 0.95);
  border: 1px solid #e2e8f0;
  color: #475569;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
}

.action-view-btn {
  border-radius: 20px !important;
  font-size: 12px !important;
  font-weight: 700 !important;
  padding: 5px 12px !important;
  height: 28px !important;
  min-height: 28px !important;
  box-shadow: 0 1px 3px rgba(185, 28, 28, 0.18);
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.action-view-btn :deep(.q-icon) {
  font-size: 14px;
}

.action-view-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 10px rgba(185, 28, 28, 0.35);
}

.search-input-glass { max-width: 320px; width: 100%; }
.filter-select-glass { width: 140px; }

.search-input-glass :deep(.q-field__control),
.filter-select-glass :deep(.q-field__control) {
  background: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(8px);
  border-radius: 10px;
  border: 1px solid rgba(255, 255, 255, 1);
}

.btn-glass {
  border-radius: 10px !important;
  font-weight: 700;
  background: #ffffff !important;
  border: 1px solid rgba(203, 213, 225, 0.8) !important;
}

/* Modal Styling */
.vendor-profile-dialog {
  width: 780px;
  max-width: 95vw;
  border-radius: 18px !important;
  box-shadow: 0 25px 50px rgba(0, 0, 0, 0.18);
}

.modal-gradient-header {
  background: linear-gradient(90deg, #dc2626 0%, #b91c1c 55%, #8f1919 100%);
  overflow: hidden;
  border-bottom: 1px solid rgba(255, 255, 255, 0.15);
}

.header-ambient-circle {
  position: absolute;
  top: -40px;
  right: -20px;
  width: 160px;
  height: 160px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(255, 255, 255, 0.22) 0%, transparent 70%);
  pointer-events: none;
}

.vendor-header-id-pill {
  background: rgba(255, 255, 255, 0.2);
  color: #ffffff;
  font-size: 11px;
  padding: 3px 10px;
  border-radius: 20px;
  font-weight: 800;
  letter-spacing: 0.04em;
  border: 1px solid rgba(255, 255, 255, 0.35);
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
}

.vendor-banner-frame {
  width: 100%;
  height: 180px;
  border-radius: 12px;
  overflow: hidden;
  border: 1px solid #e2e8f0;
}

.vendor-banner-img,
.vendor-banner-placeholder {
  width: 100%;
  height: 100%;
  background: #f1f5f9;
}

.status-pill {
  font-size: 10px;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.status-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  display: inline-block;
}

.store-metric-chip {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 6px 12px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
}

.metric-icon-box {
  width: 28px;
  height: 28px;
  border-radius: 8px;
}

.info-card {
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 14px 16px;
  background: #ffffff;
}

.card-section-label {
  font-size: 10.5px;
  font-weight: 800;
  letter-spacing: 0.05em;
}

.schedule-list > div {
  border-bottom: 1px dashed #f1f5f9;
  padding: 6px 0;
}
.schedule-list > div:last-child {
  border-bottom: none;
}

.readable-location-box {
  background: #f8fafc;
  padding: 10px 12px;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
}

.map-card-wrapper {
  min-height: 160px;
  height: 100%;
}

.enlarge-map-btn {
  position: absolute;
  top: 8px;
  right: 8px;
  z-index: 5;
  background: white !important;
}

.enlarged-map-dialog {
  width: 900px;
  max-width: 95vw;
  border-radius: 14px !important;
}

.border-slate-light { border: 1px solid #e2e8f0; }
.h-full { height: 100%; }

@media (max-width: 768px) {
  .page-container { padding: 20px 16px; }
  .management-header-color { flex-direction: column; align-items: flex-start; }
  .header-stat-box { margin-top: 14px; width: 100%; }
  .toolbar { flex-direction: column; align-items: stretch; gap: 12px; }
  .search-input-glass, .filter-select-glass, .export-btn { max-width: 100%; width: 100%; }
}
</style>