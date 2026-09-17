<template>
  <q-page class="vp-page">
    <div class="vp-container">
      <AdminHero
        icon="o_how_to_reg"
        title="Vendor Approvals"
        subtitle="Review store applications and decide who can sell on Tindahan."
        :stat-label="STAT_LABEL[active]"
        :stat-value="totalFor(active)"
        :stat-unit="totalFor(active) === 1 ? 'application' : 'applications'"
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

          <!-- Pending, approved and rejected, each with its count. -->
          <div class="vp-chips" role="tablist" aria-label="Filter applications">
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
          <div class="vp-empty-icon"><q-icon name="o_inbox" size="24px" /></div>
          <div class="vp-empty-title">{{
            search ? 'No matching applications' : EMPTY[active].title
          }}</div>
          <div class="vp-empty-text">{{
            search ? 'Try another name, store or email.' : EMPTY[active].text
          }}</div>
        </div>

        <!-- A table on wide screens; a row opens the full application. -->
        <div v-else-if="!$q.screen.lt.md" class="vp-table-wrap">
          <table class="vp-table ap-table">
            <thead>
              <tr>
                <th>Store</th>
                <th class="col-contact">Contact</th>
                <th class="col-date">Applied</th>
                <th class="col-status">Status</th>
                <th class="text-right col-actions">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="app in pagedApplications"
                :key="app.approval_id"
                class="vp-row"
                tabindex="0"
                @click="openReview(app)"
                @keydown.enter="openReview(app)"
              >
                <td>
                  <div class="vp-person">
                    <span class="adm-thumb">
                      <img v-if="photoOf(app)" :src="photoOf(app)" alt="" />
                      <q-icon v-else name="o_storefront" size="18px" />
                    </span>
                    <span class="adm-two-lines">
                      <span class="vp-name">{{
                        app.store_name || 'Unnamed store'
                      }}</span>
                      <span class="adm-sub">{{
                        app.owner_name || 'Unknown owner'
                      }}</span>
                    </span>
                  </div>
                </td>
                <td>
                  <span class="adm-two-lines">
                    <span class="adm-email">{{ app.email || '—' }}</span>
                    <span class="adm-sub">{{ app.phone || 'No phone' }}</span>
                  </span>
                </td>
                <td class="vp-muted">{{ formatShortDate(app.applied_at) }}</td>
                <td
                  ><span
                    class="vp-status"
                    :class="`vp-status--${accountStatusTone(app.status)}`"
                    >{{ accountStatusLabel(app.status) }}</span
                  ></td
                >
                <td class="text-right">
                  <div class="adm-row-actions" @click.stop @keydown.enter.stop>
                    <q-btn
                      flat
                      no-caps
                      label="View"
                      class="adm-btn adm-btn--view"
                      @click="openReview(app)"
                    />
                    <template v-if="app.status === 'pending'">
                      <q-btn
                        flat
                        no-caps
                        label="Approve"
                        class="adm-btn adm-btn--approve"
                        @click="actions.openApprove(app)"
                      />
                      <q-btn
                        flat
                        no-caps
                        label="Reject"
                        class="adm-btn adm-btn--reject"
                        @click="actions.openReject(app)"
                      />
                    </template>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- A tappable list on phones; the review dialog carries the decision buttons. -->
        <div v-else class="vp-list">
          <button
            v-for="app in pagedApplications"
            :key="app.approval_id"
            type="button"
            class="vp-list-item"
            @click="openReview(app)"
          >
            <span class="adm-thumb adm-thumb--lg">
              <img v-if="photoOf(app)" :src="photoOf(app)" alt="" />
              <q-icon v-else name="o_storefront" size="20px" />
            </span>
            <div class="vp-list-body">
              <span class="vp-name">{{
                app.store_name || 'Unnamed store'
              }}</span>
              <div class="vp-list-meta"
                >{{ app.owner_name || 'Unknown owner' }} ·
                {{ formatShortDate(app.applied_at) }}</div
              >
            </div>
            <div class="vp-list-side">
              <span
                class="vp-status"
                :class="`vp-status--${accountStatusTone(app.status)}`"
                >{{ accountStatusLabel(app.status) }}</span
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

    <ApplicationActions ref="actions" @decided="onDecided" />
  </q-page>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { api } from '@/boot/axios'
import SkeletonTable from '@/components/vendor/SkeletonTable.vue'
import AdminHero from '@/components/admin/AdminHero.vue'
import ApplicationActions from '@/components/admin/ApplicationActions.vue'
import {
  accountStatusTone,
  accountStatusLabel,
  formatShortDate
} from '@/utils/accountStatus'
import '@/css/admin-pages.scss'

const $q = useQuasar()

const FILTERS = [
  { key: 'pending', label: 'Pending' },
  { key: 'approved', label: 'Approved' },
  { key: 'rejected', label: 'Rejected' }
]

// The banner's count follows the chosen chip.
const STAT_LABEL = {
  pending: 'Pending review',
  approved: 'Approved',
  rejected: 'Rejected'
}

const EMPTY = {
  pending: {
    title: 'No applications waiting',
    text: 'New store applications will show up here for review.'
  },
  approved: {
    title: 'No approved applications yet',
    text: 'Stores you approve will be listed here.'
  },
  rejected: {
    title: 'No rejected applications',
    text: 'Applications you reject will be listed here with their reason.'
  }
}

// The placeholder rows take the table's columns: store, contact, date, status and the actions.
const SKELETON_COLUMNS = [
  { type: 'thumb', lines: 2 },
  { width: '24%', type: 'text' },
  { width: '12%', type: 'text' },
  { width: '11%', type: 'pill', size: 72 },
  { width: '280px', type: 'pill', size: 200, align: 'right' }
]
const PAGE_SIZE = 10

const applications = ref([])
const loading = ref(true)
const search = ref('')
const active = ref('pending')
const page = ref(1)
const isExporting = ref(false)
const actions = ref(null)

const photoOf = app => {
  const url = app?.store?.store_picture_url
  return url && url !== 'null' && String(url).trim() ? url : null
}

const matchesSearch = app => {
  const needle = (search.value || '').trim().toLowerCase()
  if (!needle) return true
  return [app.store_name, app.owner_name, app.email, app.phone].some(value =>
    String(value || '')
      .toLowerCase()
      .includes(needle)
  )
}

// The banner counts every application with the chosen status; the chips count what the search leaves.
const totalFor = key =>
  applications.value.filter(app => app.status === key).length
const searched = computed(() => applications.value.filter(matchesSearch))
const countFor = key => searched.value.filter(app => app.status === key).length

// Newest applications first.
const filtered = computed(() =>
  searched.value
    .filter(app => app.status === active.value)
    .sort((a, b) => new Date(b.applied_at) - new Date(a.applied_at))
)

const pageCount = computed(() =>
  Math.max(1, Math.ceil(filtered.value.length / PAGE_SIZE))
)
const pagedApplications = computed(() =>
  filtered.value.slice((page.value - 1) * PAGE_SIZE, page.value * PAGE_SIZE)
)
const rangeStart = computed(() => (page.value - 1) * PAGE_SIZE + 1)
const rangeEnd = computed(() =>
  Math.min(page.value * PAGE_SIZE, filtered.value.length)
)

watch([search, active], () => {
  page.value = 1
})
// A decision moves a row to another chip, so a page that runs out steps back.
watch(pageCount, count => {
  if (page.value > count) page.value = count
})

const openReview = app => actions.value?.openReview(app)

// The decided application moves to its new chip straight away.
const onDecided = ({ storeId, status, reason }) => {
  const app = applications.value.find(a => a.store_id === storeId)
  if (!app) return
  app.status = status
  app.reviewed_at = new Date().toISOString()
  if (reason) app.rejection_reason = reason
}

const fetchApplications = async () => {
  loading.value = true
  try {
    const { data } = await api.get('/admin/vendors/pending')
    applications.value = Array.isArray(data) ? data : data?.data || []
  } catch (error) {
    console.error('Failed to load applications', error)
    $q.notify({
      type: 'negative',
      message: 'Couldn’t load the applications. Please refresh.'
    })
  } finally {
    loading.value = false
  }
}

const handleExport = async () => {
  if (isExporting.value) return
  isExporting.value = true
  try {
    const response = await api.get('/admin/vendors/pending/export', {
      params: { search: search.value || undefined },
      responseType: 'blob'
    })
    const url = window.URL.createObjectURL(
      new Blob([response.data], { type: 'application/pdf' })
    )
    const link = document.createElement('a')
    link.href = url
    link.setAttribute(
      'download',
      `Tindahan_Admin_Approvals_${new Date().toISOString().split('T')[0]}.pdf`
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

onMounted(fetchApplications)
</script>

<style scoped>
.ap-table .col-contact {
  width: 24%;
}
.ap-table .col-date {
  width: 12%;
}
.ap-table .col-status {
  width: 11%;
}
.ap-table .col-actions {
  width: 280px;
}
</style>
