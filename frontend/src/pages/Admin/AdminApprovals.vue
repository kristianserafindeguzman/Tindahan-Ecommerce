<template>
  <q-page class="vp-page">
    <div class="vp-container">
      <AdminHero
        icon="o_how_to_reg"
        :title="t('title')"
        :subtitle="t('subtitle')"
        :stat-label="t('stat_' + active)"
        :stat-value="totalFor(active)"
        :stat-unit="totalFor(active) === 1 ? t('application') : t('applications')"
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
            :placeholder="t('searchPlaceholder')"
            class="vp-search"
          >
            <template #prepend>
              <q-icon name="o_search" size="18px" />
            </template>
          </q-input>

          <!-- Pending, approved and rejected chips -->
          <div class="vp-chips" role="tablist" :aria-label="t('filterAria')">
            <button
              v-for="filter in localizedFilters"
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
            :label="t('exportReport')"
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
            search ? t('noMatchTitle') : t('emptyTitle_' + active)
          }}</div>
          <div class="vp-empty-text">{{
            search ? t('noMatchText') : t('emptyText_' + active)
          }}</div>
        </div>

        <!-- A table on wide screens; a row opens the full application. -->
        <div v-else-if="!$q.screen.lt.md" class="vp-table-wrap">
          <table class="vp-table ap-table">
            <thead>
              <tr>
                <th>{{ t('colStore') }}</th>
                <th class="col-contact">{{ t('colContact') }}</th>
                <th class="col-date">{{ t('colApplied') }}</th>
                <th class="col-status">{{ t('colStatus') }}</th>
                <th class="text-right col-actions">{{ t('colActions') }}</th>
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
                    <div class="adm-two-lines">
                      <span class="vp-name">{{
                        app.store_name || t('unnamedStore')
                      }}</span>
                      <span class="adm-sub">{{
                        app.owner_name || t('unknownOwner')
                      }}</span>
                    </div>
                  </div>
                </td>
                <td>
                  <span class="adm-two-lines">
                    <span class="adm-email">{{ app.email || '—' }}</span>
                    <span class="adm-sub">{{ app.phone || t('noPhone') }}</span>
                  </span>
                </td>
                <td class="vp-muted">{{ formatShortDate(app.applied_at) }}</td>
                <td>
                  <span
                    class="vp-status"
                    :class="`vp-status--${accountStatusTone(app.status)}`"
                  >
                    {{ accountStatusLabel(app.status) }}
                  </span>
                </td>
                <td class="text-right">
                  <div class="adm-row-actions" @click.stop @keydown.enter.stop>
                    <q-btn
                      flat
                      no-caps
                      :label="t('btnView')"
                      class="adm-btn adm-btn--view"
                      @click="openReview(app)"
                    />
                    <template v-if="app.status === 'pending'">
                      <q-btn
                        flat
                        no-caps
                        :label="t('btnApprove')"
                        class="adm-btn adm-btn--approve"
                        @click="actions.openApprove(app)"
                      />
                      <q-btn
                        flat
                        no-caps
                        :label="t('btnReject')"
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
                app.store_name || t('unnamedStore')
              }}</span>
              <div class="vp-list-meta">
                {{ app.owner_name || t('unknownOwner') }} ·
                {{ formatShortDate(app.applied_at) }}
              </div>
            </div>
            <div class="vp-list-side">
              <span
                class="vp-status"
                :class="`vp-status--${accountStatusTone(app.status)}`"
              >
                {{ accountStatusLabel(app.status) }}
              </span>
            </div>
          </button>
        </div>

        <div v-if="!loading && pageCount > 1" class="vp-pager">
          <span>{{ t('showing') }} {{ rangeStart }}–{{ rangeEnd }} {{ t('of') }} {{ filtered.length }}</span>
          <div class="vp-pager-btns">
            <q-btn
              outline
              no-caps
              color="primary"
              icon="o_chevron_left"
              class="vp-pill-btn"
              :aria-label="t('prevPage')"
              :disable="page === 1"
              @click="page--"
            />
            <q-btn
              outline
              no-caps
              color="primary"
              icon="o_chevron_right"
              class="vp-pill-btn"
              :aria-label="t('nextPage')"
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
import { useLanguage } from '@/composables/useLanguage'
import {
  accountStatusTone,
  accountStatusLabel,
  formatShortDate
} from '@/utils/accountStatus'
import '@/css/admin-pages.scss'

const $q = useQuasar()

const approvalsDict = {
  en: {
    title: 'Vendor Approvals',
    subtitle: 'Review store applications and decide who can sell on Tindahan.',
    stat_pending: 'Pending review',
    stat_approved: 'Approved',
    stat_rejected: 'Rejected',
    application: 'application',
    applications: 'applications',
    searchPlaceholder: 'Search store, owner or email',
    filterAria: 'Filter applications',
    filterPending: 'Pending',
    filterApproved: 'Approved',
    filterRejected: 'Rejected',
    exportReport: 'Export Report',
    noMatchTitle: 'No matching applications',
    noMatchText: 'Try another name, store or email.',
    emptyTitle_pending: 'No applications waiting',
    emptyText_pending: 'New store applications will show up here for review.',
    emptyTitle_approved: 'No approved applications yet',
    emptyText_approved: 'Stores you approve will be listed here.',
    emptyTitle_rejected: 'No rejected applications',
    emptyText_rejected: 'Applications you reject will be listed here with their reason.',
    colStore: 'Store',
    colContact: 'Contact',
    colApplied: 'Applied',
    colStatus: 'Status',
    colActions: 'Actions',
    unnamedStore: 'Unnamed store',
    unknownOwner: 'Unknown owner',
    noPhone: 'No phone',
    btnView: 'View',
    btnApprove: 'Approve',
    btnReject: 'Reject',
    showing: 'Showing',
    of: 'of',
    prevPage: 'Previous page',
    nextPage: 'Next page',
    loadError: 'Couldn’t load the applications. Please refresh.',
    exportError: 'Couldn’t create the report. Please try again.'
  },
  ph: {
    title: 'Vendor Approvals',
    subtitle: 'I-review ang mga store application at magdesisyon kung sino ang pwedeng magbenta sa Tindahan.',
    stat_pending: 'Pending review',
    stat_approved: 'Approved',
    stat_rejected: 'Rejected',
    application: 'application',
    applications: 'applications',
    searchPlaceholder: 'Mag-search ng store, owner o email',
    filterAria: 'I-filter ang mga application',
    filterPending: 'Pending',
    filterApproved: 'Approved',
    filterRejected: 'Rejected',
    exportReport: 'Export Report',
    noMatchTitle: 'Walang nag-match',
    noMatchText: 'Try mag-search ng ibang pangalan, store o email.',
    emptyTitle_pending: 'Walang naghihintay na application',
    emptyText_pending: 'Dito lalabas ang mga bagong store application para ma-review.',
    emptyTitle_approved: 'Wala pang approved applications',
    emptyText_approved: 'Dito nakalista ang mga stores na na-approve mo na.',
    emptyTitle_rejected: 'Walang rejected applications',
    emptyText_rejected: 'Dito nakalista ang mga rejected application kasama ang dahilan.',
    colStore: 'Store',
    colContact: 'Contact',
    colApplied: 'Applied',
    colStatus: 'Status',
    colActions: 'Actions',
    unnamedStore: 'Unnamed store',
    unknownOwner: 'Unknown owner',
    noPhone: 'Walang phone number',
    btnView: 'View',
    btnApprove: 'Approve',
    btnReject: 'Reject',
    showing: 'Showing',
    of: 'of',
    prevPage: 'Previous page',
    nextPage: 'Next page',
    loadError: 'Hindi ma-load ang mga application. Paki-refresh.',
    exportError: 'Hindi magawa ang report. Pakisubukan ulit.'
  }
}

const { t } = useLanguage(approvalsDict)

const localizedFilters = computed(() => [
  { key: 'pending', label: t('filterPending') },
  { key: 'approved', label: t('filterApproved') },
  { key: 'rejected', label: t('filterRejected') }
])

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

const totalFor = key =>
  applications.value.filter(app => app.status === key).length
const searched = computed(() => applications.value.filter(matchesSearch))
const countFor = key => searched.value.filter(app => app.status === key).length

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
watch(pageCount, count => {
  if (page.value > count) page.value = count
})

const openReview = app => actions.value?.openReview(app)

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
      message: t('loadError')
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
      message: t('exportError')
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