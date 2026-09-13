<template>
  <q-page class="vp-page">
    <div class="vp-container">
      <AdminHero
        icon="o_storefront"
        title="Manage Vendors"
        subtitle="See every approved store, how it's doing, and manage its account."
        :stat-label="STAT_LABEL[active]"
        :stat-value="totalFor(active)"
        :stat-unit="totalFor(active) === 1 ? 'account' : 'accounts'"
        :loading="loading"
      />

      <div class="vp-card">
        <div class="vp-toolbar adm-toolbar">
          <q-input
            v-model="search"
            outlined
            dense
            clearable
            clear-icon="o_close"
            hide-bottom-space
            placeholder="Search store, owner or email"
            class="vp-search"
          >
            <template #prepend>
              <q-icon name="o_search" size="18px" />
            </template>
          </q-input>

          <!-- Every status, plus the deleted accounts, each with its count. -->
          <div class="vp-chips" role="tablist" aria-label="Filter vendors">
            <button
              v-for="filter in FILTERS"
              :key="filter.key"
              type="button"
              role="tab"
              class="vp-chip"
              :class="{ 'vp-chip--active': active === filter.key }"
              :aria-selected="active === filter.key"
              @click="active = filter.key"
            >
              {{ filter.label }}
              <span class="vp-chip-count">{{
                loading ? '–' : countFor(filter.key)
              }}</span>
            </button>
          </div>

          <q-btn
            outline
            no-caps
            color="primary"
            icon="o_download"
            label="Export Report"
            class="vp-pill-btn adm-export"
            :loading="isExporting"
            @click="handleExport"
          />
        </div>

        <SkeletonTable
          v-if="loading"
          :columns="SKELETON_COLUMNS"
          :list="$q.screen.lt.md"
          thumb
        />

        <div v-else-if="!filtered.length" class="vp-empty">
          <div class="vp-empty-icon"
            ><q-icon name="o_storefront" size="24px"
          /></div>
          <div class="vp-empty-title">{{
            search ? 'No matching vendors' : EMPTY[active].title
          }}</div>
          <div class="vp-empty-text">{{
            search ? 'Try another name, store or email.' : EMPTY[active].text
          }}</div>
        </div>

        <!-- A table on wide screens; a row opens the store's profile. -->
        <div v-else-if="!$q.screen.lt.md" class="vp-table-wrap">
          <table class="vp-table vd-table">
            <thead>
              <tr>
                <th>Store</th>
                <th class="col-contact">Contact</th>
                <th class="col-num text-right">Products</th>
                <th class="col-num text-right">Orders</th>
                <th class="col-date">Last active</th>
                <th class="col-status">Status</th>
                <th class="col-actions text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="vendor in pagedVendors"
                :key="vendor.user_id"
                class="vp-row"
                tabindex="0"
                @click="openView(vendor)"
                @keydown.enter="openView(vendor)"
              >
                <td>
                  <div class="vp-person">
                    <span class="adm-thumb">
                      <img
                        v-if="photoOf(vendor)"
                        :src="photoOf(vendor)"
                        alt=""
                      />
                      <q-icon v-else name="o_storefront" size="18px" />
                    </span>
                    <span class="adm-two-lines">
                      <span class="vp-name">{{
                        vendor.store_name || 'Unnamed store'
                      }}</span>
                      <span class="adm-sub">{{ vendor.full_name }}</span>
                    </span>
                  </div>
                </td>
                <td>
                  <span class="adm-two-lines">
                    <span class="adm-email">{{ vendor.email || '—' }}</span>
                    <span class="adm-sub">{{
                      vendor.phone_number || 'No phone'
                    }}</span>
                  </span>
                </td>
                <td class="text-right vp-muted">{{
                  vendor.active_products ?? 0
                }}</td>
                <td class="text-right vp-muted">{{
                  vendor.orders_count ?? 0
                }}</td>
                <td class="vp-muted">{{
                  formatActivity(vendor.last_activity_at)
                }}</td>
                <td>
                  <span
                    class="vp-status"
                    :class="`vp-status--${accountStatusTone(statusOf(vendor))}`"
                    >{{ accountStatusLabel(statusOf(vendor)) }}</span
                  >
                </td>
                <td class="text-right">
                  <div class="adm-row-actions" @click.stop @keydown.enter.stop>
                    <q-btn
                      flat
                      no-caps
                      label="View"
                      class="adm-btn adm-btn--view"
                      @click="openView(vendor)"
                    />
                    <q-btn
                      v-if="!vendor.deleted"
                      flat
                      icon="o_more_vert"
                      class="adm-btn adm-btn--more"
                      :aria-label="`Change ${vendor.full_name}'s status`"
                    >
                      <q-menu anchor="bottom right" self="top right" auto-close>
                        <q-list class="vp-menu-list">
                          <q-item
                            v-for="option in statusOptions(vendor)"
                            :key="option.status"
                            clickable
                            :class="{ 'vp-menu-item--danger': option.danger }"
                            @click="changeStatus(vendor, option.status)"
                          >
                            <q-item-section avatar
                              ><q-icon :name="option.icon" size="18px"
                            /></q-item-section>
                            <q-item-section>{{ option.label }}</q-item-section>
                          </q-item>
                        </q-list>
                      </q-menu>
                    </q-btn>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- A tappable list on phones; the profile carries the status change. -->
        <div v-else class="vp-list">
          <button
            v-for="vendor in pagedVendors"
            :key="vendor.user_id"
            type="button"
            class="vp-list-item"
            @click="openView(vendor)"
          >
            <span class="adm-thumb adm-thumb--lg">
              <img v-if="photoOf(vendor)" :src="photoOf(vendor)" alt="" />
              <q-icon v-else name="o_storefront" size="20px" />
            </span>
            <div class="vp-list-body">
              <span class="vp-name">{{
                vendor.store_name || 'Unnamed store'
              }}</span>
              <div class="vp-list-meta"
                >{{ vendor.full_name }} ·
                {{ formatActivity(vendor.last_activity_at) }}</div
              >
            </div>
            <div class="vp-list-side">
              <span
                class="vp-status"
                :class="`vp-status--${accountStatusTone(statusOf(vendor))}`"
                >{{ accountStatusLabel(statusOf(vendor)) }}</span
              >
            </div>
          </button>
        </div>

        <div v-if="!loading && pageCount > 1" class="vp-pager">
          <span
            >Showing {{ rangeStart }}–{{ rangeEnd }} of
            {{ filtered.length }}</span
          >
          <div class="vp-pager-btns">
            <q-btn
              outline
              no-caps
              color="primary"
              icon="o_chevron_left"
              class="vp-pill-btn"
              aria-label="Previous page"
              :disable="page === 1"
              @click="page--"
            />
            <q-btn
              outline
              no-caps
              color="primary"
              icon="o_chevron_right"
              class="vp-pill-btn"
              aria-label="Next page"
              :disable="page === pageCount"
              @click="page++"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- VIEW — the store's full profile, the same one the approvals review shows. -->
    <StoreProfileDialog
      v-if="viewing"
      v-model="viewOpen"
      :name="viewing.store_name"
      :owner="viewing.full_name"
      :photo="viewing.store_picture_url"
      :operating-days="viewing.operating_days"
      :opening-time="viewing.opening_time"
      :closing-time="viewing.closing_time"
      :email="viewing.email"
      :phone="viewing.phone_number"
      :latitude="viewing.latitude"
      :longitude="viewing.longitude"
      :address="viewing.address || ''"
      :info="viewInfo"
      :stats="viewStats"
    >
      <template #badge>
        <span
          class="vp-status"
          :class="`vp-status--${accountStatusTone(statusOf(viewing))}`"
          >{{ accountStatusLabel(statusOf(viewing)) }}</span
        >
      </template>
      <template #actions>
        <q-btn
          v-close-popup
          outline
          no-caps
          color="primary"
          label="Close"
          class="vp-dialog-btn"
        />
        <q-btn
          v-if="!viewing.deleted"
          unelevated
          no-caps
          color="primary"
          label="Change Status"
          icon-right="o_expand_more"
          class="vp-dialog-btn"
        >
          <q-menu
            anchor="top right"
            self="bottom right"
            :offset="[0, 6]"
            auto-close
          >
            <q-list class="vp-menu-list">
              <q-item
                v-for="option in statusOptions(viewing)"
                :key="option.status"
                clickable
                :class="{ 'vp-menu-item--danger': option.danger }"
                @click="changeStatus(viewing, option.status)"
              >
                <q-item-section avatar
                  ><q-icon :name="option.icon" size="18px"
                /></q-item-section>
                <q-item-section>{{ option.label }}</q-item-section>
              </q-item>
            </q-list>
          </q-menu>
        </q-btn>
      </template>
    </StoreProfileDialog>

    <AccountStatusDialog
      ref="statusDialog"
      kind="vendors"
      noun="vendor"
      delete-text="will be signed out, and their store will be hidden from customers. Their order history is kept."
      @changed="onChanged"
      @deleted="onDeleted"
    />
  </q-page>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { api } from '@/boot/axios'
import SkeletonTable from '@/components/vendor/SkeletonTable.vue'
import AdminHero from '@/components/admin/AdminHero.vue'
import AccountStatusDialog from '@/components/admin/AccountStatusDialog.vue'
import StoreProfileDialog from '@/components/shared/StoreProfileDialog.vue'
import {
  accountStatusTone,
  accountStatusLabel,
  formatActivity
} from '@/utils/accountStatus'
import '@/css/admin-pages.scss'

const $q = useQuasar()

const FILTERS = [
  { key: 'all', label: 'All' },
  { key: 'active', label: 'Active' },
  { key: 'inactive', label: 'Inactive' },
  { key: 'suspended', label: 'Suspended' },
  { key: 'deleted', label: 'Deleted' }
]

// The banner's count follows the chosen chip.
const STAT_LABEL = {
  all: 'All vendors',
  active: 'Active',
  inactive: 'Inactive',
  suspended: 'Suspended',
  deleted: 'Deleted'
}

const EMPTY = {
  all: {
    title: 'No vendors yet',
    text: 'Stores show up here once their application is approved.'
  },
  active: {
    title: 'No active vendors',
    text: 'Vendors who can sign in and sell are listed here.'
  },
  inactive: {
    title: 'No inactive vendors',
    text: 'Vendors you set inactive are listed here.'
  },
  suspended: {
    title: 'No suspended vendors',
    text: 'Vendors you suspend are listed here.'
  },
  deleted: {
    title: 'No deleted vendors',
    text: 'Deleted vendor accounts are kept here for the record.'
  }
}

// Every change but the current status; a suspension is the one that needs care.
const STATUS_OPTIONS = [
  { status: 'active', label: 'Set active', icon: 'o_check_circle' },
  { status: 'inactive', label: 'Set inactive', icon: 'o_pause_circle' },
  { status: 'suspended', label: 'Suspend…', icon: 'o_block', danger: true },
  { status: 'delete', label: 'Delete vendor…', icon: 'o_delete', danger: true }
]

// The placeholder rows take the table's columns.
const SKELETON_COLUMNS = [
  { type: 'thumb', lines: 2 },
  { width: '22%', type: 'text' },
  { width: '9%', type: 'text', align: 'right' },
  { width: '8%', type: 'text', align: 'right' },
  { width: '12%', type: 'text' },
  { width: '11%', type: 'pill', size: 72 },
  { width: '130px', type: 'pill', size: 96, align: 'right' }
]
const PAGE_SIZE = 10

const vendors = ref([])
const loading = ref(true)
const search = ref('')
const active = ref('all')
const page = ref(1)
const isExporting = ref(false)
const statusDialog = ref(null)
const viewing = ref(null)
const viewOpen = ref(false)

const photoOf = vendor => {
  const url = vendor?.store_picture_url
  return url && url !== 'null' && String(url).trim() ? url : null
}

// A deleted account reads as Deleted whatever its last status was.
const statusOf = vendor => (vendor.deleted ? 'deleted' : vendor.account_status)

const inFilter = (vendor, key) => {
  if (key === 'deleted') return vendor.deleted
  if (vendor.deleted) return false
  return key === 'all' || vendor.account_status === key
}

const matchesSearch = vendor => {
  const needle = (search.value || '').trim().toLowerCase()
  if (!needle) return true
  return [
    vendor.store_name,
    vendor.full_name,
    vendor.email,
    vendor.phone_number
  ].some(value =>
    String(value || '')
      .toLowerCase()
      .includes(needle)
  )
}

// The banner counts every vendor in the chosen chip; the chips count what the search leaves.
const totalFor = key => vendors.value.filter(v => inFilter(v, key)).length
const searched = computed(() => vendors.value.filter(matchesSearch))
const countFor = key => searched.value.filter(v => inFilter(v, key)).length

// The most recently active first; vendors who never signed in go last.
const filtered = computed(() =>
  searched.value
    .filter(v => inFilter(v, active.value))
    .sort(
      (a, b) =>
        new Date(b.last_activity_at || 0) - new Date(a.last_activity_at || 0)
    )
)

const pageCount = computed(() =>
  Math.max(1, Math.ceil(filtered.value.length / PAGE_SIZE))
)
const pagedVendors = computed(() =>
  filtered.value.slice((page.value - 1) * PAGE_SIZE, page.value * PAGE_SIZE)
)
const rangeStart = computed(() => (page.value - 1) * PAGE_SIZE + 1)
const rangeEnd = computed(() =>
  Math.min(page.value * PAGE_SIZE, filtered.value.length)
)

watch([search, active], () => {
  page.value = 1
})
// A status change can move a row to another chip, so a page that runs out steps back.
watch(pageCount, count => {
  if (page.value > count) page.value = count
})

const statusOptions = vendor =>
  STATUS_OPTIONS.filter(o => o.status !== vendor.account_status)

const viewInfo = computed(() =>
  viewing.value
    ? [
        { label: 'Vendor', value: viewing.value.full_name },
        {
          label: 'Last active',
          value: formatActivity(viewing.value.last_activity_at)
        }
      ]
    : []
)

// The profile's counts: products on sale, and orders that weren't cancelled.
const viewStats = computed(() =>
  viewing.value
    ? [
        {
          label: 'Active products',
          value: viewing.value.active_products ?? 0,
          icon: 'o_inventory_2'
        },
        {
          label: 'Orders',
          value: viewing.value.orders_count ?? 0,
          icon: 'o_receipt_long'
        }
      ]
    : []
)

const openView = vendor => {
  viewing.value = vendor
  viewOpen.value = true
}

const changeStatus = (vendor, status) => {
  viewOpen.value = false
  statusDialog.value?.open(
    { id: vendor.user_id, name: vendor.full_name },
    status
  )
}

// The changed vendor moves to its new chip straight away.
const onChanged = ({ userId, status }) => {
  const vendor = vendors.value.find(v => v.user_id === userId && !v.deleted)
  if (vendor) vendor.account_status = status
}

// A deleted vendor moves to the Deleted chip.
const onDeleted = ({ userId }) => {
  const vendor = vendors.value.find(v => v.user_id === userId && !v.deleted)
  if (vendor) vendor.deleted = true
}

const listOf = res =>
  Array.isArray(res.data) ? res.data : res.data?.data || []

// Both lists load together, so every chip can show its count from the start.
const fetchVendors = async () => {
  loading.value = true
  try {
    const [current, removed] = await Promise.all([
      api.get('/admin/vendors', { params: { tab: 'active' } }),
      api.get('/admin/vendors', { params: { tab: 'deleted' } })
    ])
    vendors.value = [
      ...listOf(current).map(v => ({ ...v, deleted: false })),
      ...listOf(removed).map(v => ({ ...v, deleted: true }))
    ]
  } catch (error) {
    console.error('Failed to load vendors', error)
    $q.notify({
      type: 'negative',
      message: 'Couldn’t load the vendors. Please refresh.'
    })
  } finally {
    loading.value = false
  }
}

// The report follows the chosen chip and the search.
const handleExport = async () => {
  if (isExporting.value) return
  isExporting.value = true
  try {
    const response = await api.get('/admin/vendors/export', {
      params: {
        tab: active.value === 'deleted' ? 'deleted' : 'active',
        search: search.value || undefined,
        status: ['active', 'inactive', 'suspended'].includes(active.value)
          ? active.value
          : undefined
      },
      responseType: 'blob'
    })
    const url = window.URL.createObjectURL(
      new Blob([response.data], { type: 'application/pdf' })
    )
    const link = document.createElement('a')
    link.href = url
    link.setAttribute(
      'download',
      `Tindahan_Admin_Vendors_${new Date().toISOString().split('T')[0]}.pdf`
    )
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    setTimeout(() => window.URL.revokeObjectURL(url), 1000)
  } catch (error) {
    console.error('Export failed', error)
    $q.notify({
      type: 'negative',
      message: 'Couldn’t create the report. Please try again.'
    })
  } finally {
    isExporting.value = false
  }
}

onMounted(fetchVendors)
</script>

<style scoped>
.vd-table .col-contact {
  width: 22%;
}
.vd-table .col-num {
  width: 8%;
}
.vd-table .col-date {
  width: 12%;
}
.vd-table .col-status {
  width: 11%;
}
.vd-table .col-actions {
  width: 130px;
}
</style>
