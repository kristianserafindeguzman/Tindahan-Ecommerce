<template>
  <q-page class="vp-page">
    <div class="vp-container">
      <AdminHero
        icon="o_groups"
        :title="t('title')"
        :subtitle="t('subtitle')"
        :stat-label="t('stat_' + active)"
        :stat-value="totalFor(active)"
        :stat-unit="totalFor(active) === 1 ? t('account') : t('accounts')"
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

          <div class="vp-chips" role="tablist" :aria-label="t('filterAll')">
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
        />

        <div v-else-if="!filtered.length" class="vp-empty">
          <div class="vp-empty-icon"
            ><q-icon name="o_groups" size="24px"
          /></div>
          <div class="vp-empty-title">{{
            search ? t('noMatchTitle') : t('emptyTitle_' + active)
          }}</div>
          <div class="vp-empty-text">{{
            search
              ? t('noMatchText')
              : t('emptyText_' + active)
          }}</div>
        </div>

        <!-- A table on wide screens -->
        <div v-else-if="!$q.screen.lt.md" class="vp-table-wrap">
          <table class="vp-table cn-table">
            <thead>
              <tr>
                <th>{{ t('colConsumer') }}</th>
                <th class="col-phone">{{ t('colPhone') }}</th>
                <th class="col-date">{{ t('colJoined') }}</th>
                <th class="col-date">{{ t('colLastActive') }}</th>
                <th class="col-status text-center">{{ t('colStatus') }}</th>
                <th class="col-actions text-right">{{ t('colActions') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="consumer in pagedConsumers"
                :key="consumer.user_id"
                class="vp-row"
                tabindex="0"
                @click="openView(consumer)"
                @keydown.enter="openView(consumer)"
              >
                <td>
                  <div class="vp-person">
                    <span class="adm-thumb adm-thumb--round">
                      <img
                        v-if="photoOf(consumer)"
                        :src="photoOf(consumer)"
                        alt=""
                      />
                      <q-icon v-else name="o_person" size="20px" />
                    </span>
                    <span class="adm-two-lines">
                      <span class="vp-name ellipsis">{{
                        consumer.full_name || t('unnamedConsumer')
                      }}</span>
                      <span class="adm-sub text-muted-themed ellipsis">{{
                        consumer.email || t('noEmail')
                      }}</span>
                    </span>
                  </div>
                </td>
                <td class="vp-muted">{{ consumer.phone_number || t('noPhone') }}</td>
                <td class="vp-muted">{{
                  formatShortDate(consumer.created_at)
                }}</td>
                <td class="vp-muted">{{
                  formatActivity(consumer.last_activity_at)
                }}</td>
                <td class="col-status text-center">
                  <span
                    class="vp-status"
                    :class="`vp-status--${accountStatusTone(statusOf(consumer))}`"
                    >{{ accountStatusLabel(statusOf(consumer)) }}</span
                  >
                </td>

                <!-- ALL-ICON UNIFIED ACTION GROUP -->
                <td class="col-actions text-right" @click.stop @keydown.enter.stop>
                  <div class="vd-icon-action-group">
                    <q-btn
                      flat
                      round
                      dense
                      icon="o_visibility"
                      class="vd-action-icon-btn"
                      @click="openView(consumer)"
                    >
                      <q-tooltip anchor="top middle" self="bottom middle">{{ t('tooltipViewProfile') }}</q-tooltip>
                    </q-btn>

                    <q-btn
                      v-if="!consumer.deleted"
                      flat
                      round
                      dense
                      icon="o_more_vert"
                      class="vd-action-icon-btn text-muted-themed"
                      :aria-label="`Change ${consumer.full_name}'s account`"
                    >
                      <q-tooltip anchor="top middle" self="bottom middle">{{ t('tooltipOptions') }}</q-tooltip>
                      <q-menu anchor="bottom right" self="top right" auto-close class="compact-status-menu">
                        <q-list dense class="compact-menu-list">
                          <q-item
                            v-for="option in localizedStatusOptions(consumer)"
                            :key="option.status"
                            clickable
                            v-ripple
                            class="compact-menu-item"
                            :class="{ 'compact-menu-item--danger': option.danger }"
                            @click="changeStatus(consumer, option.status)"
                          >
                            <q-item-section avatar class="compact-menu-avatar">
                              <q-icon :name="option.icon" size="16px" />
                            </q-item-section>
                            <q-item-section class="compact-menu-label">{{ option.label }}</q-item-section>
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

        <!-- TAPPABLE LIST ON PHONES -->
        <div v-else class="vp-list">
          <button
            v-for="consumer in pagedConsumers"
            :key="consumer.user_id"
            type="button"
            class="vp-list-item cursor-pointer"
            @click="openView(consumer)"
          >
            <span class="adm-thumb adm-thumb--lg adm-thumb--round">
              <img v-if="photoOf(consumer)" :src="photoOf(consumer)" alt="" />
              <q-icon v-else name="o_person" size="22px" />
            </span>
            <div class="vp-list-body">
              <span class="vp-name">{{
                consumer.full_name || t('unnamedConsumer')
              }}</span>
              <div class="vp-list-meta text-muted-themed">{{
                formatActivity(consumer.last_activity_at)
              }}</div>
            </div>
            <div class="vp-list-side column items-end q-gutter-y-xs">
              <span
                class="vp-status"
                :class="`vp-status--${accountStatusTone(statusOf(consumer))}`"
                >{{ accountStatusLabel(statusOf(consumer)) }}</span
              >
              <div class="row items-center gap-xs q-mt-xs" @click.stop>
                <q-btn
                  flat
                  round
                  dense
                  size="sm"
                  icon="o_visibility"
                  class="vd-action-icon-btn vd-action-icon-btn--sm"
                  @click="openView(consumer)"
                />
              </div>
            </div>
          </button>
        </div>

        <div v-if="!loading && pageCount > 1" class="vp-pager">
          <span
            >{{ t('showing') }} {{ rangeStart }}–{{ rangeEnd }} {{ t('of') }}
            {{ filtered.length }}</span
          >
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

    <!-- VIEW — the consumer's profile -->
    <ConsumerProfileDialog
      v-model="viewOpen"
      :consumer="viewing"
      :deleted="!!viewing?.deleted"
    >
      <template #actions>
        <q-btn
          v-close-popup
          outline
          no-caps
          color="primary"
          :label="t('close')"
          class="vp-dialog-btn"
        />
        <q-btn
          v-if="viewing && !viewing.deleted"
          unelevated
          no-caps
          color="primary"
          :label="t('changeStatus')"
          icon-right="o_expand_more"
          class="vp-dialog-btn"
        >
          <q-menu
            anchor="top right"
            self="bottom right"
            :offset="[0, 6]"
            auto-close
            class="compact-status-menu"
          >
            <q-list dense class="compact-menu-list">
              <q-item
                v-for="option in localizedStatusOptions(viewing)"
                :key="option.status"
                clickable
                v-ripple
                class="compact-menu-item"
                :class="{ 'compact-menu-item--danger': option.danger }"
                @click="changeStatus(viewing, option.status)"
              >
                <q-item-section avatar class="compact-menu-avatar">
                  <q-icon :name="option.icon" size="18px" />
                </q-item-section>
                <q-item-section class="compact-menu-label">{{ option.label }}</q-item-section>
              </q-item>
            </q-list>
          </q-menu>
        </q-btn>
      </template>
    </ConsumerProfileDialog>

    <AccountStatusDialog
      ref="statusDialog"
      kind="consumers"
      noun="consumer"
      :delete-text="t('deleteWarning')"
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
import ConsumerProfileDialog from '@/components/admin/ConsumerProfileDialog.vue'
import { useLanguage } from '@/composables/useLanguage'
import {
  accountStatusTone,
  accountStatusLabel,
  formatActivity,
  formatShortDate
} from '@/utils/accountStatus'
import '@/css/admin-pages.scss'

const $q = useQuasar()

// --- DICTIONARY FOR LANGUAGE SWITCHER (NATURAL TAGLISH) ---
const consumersDict = {
  en: {
    title: 'Manage Consumers',
    subtitle: "See every shopper's account and manage who can use Tindahan.",
    searchPlaceholder: 'Search name, email or phone',
    filterAll: 'All',
    filterActive: 'Active',
    filterInactive: 'Inactive',
    filterSuspended: 'Suspended',
    filterDeleted: 'Deleted',
    stat_all: 'All consumers',
    stat_active: 'Active',
    stat_inactive: 'Inactive',
    stat_suspended: 'Suspended',
    stat_deleted: 'Deleted',
    account: 'account',
    accounts: 'accounts',
    exportReport: 'Export Report',
    emptyTitle_all: 'No consumers yet',
    emptyText_all: 'Shoppers show up here once they sign up.',
    emptyTitle_active: 'No active consumers',
    emptyText_active: 'Shoppers who can sign in and order are listed here.',
    emptyTitle_inactive: 'No inactive consumers',
    emptyText_inactive: 'Accounts you set inactive are listed here.',
    emptyTitle_suspended: 'No suspended consumers',
    emptyText_suspended: 'Accounts you suspend are listed here.',
    emptyTitle_deleted: 'No deleted consumers',
    emptyText_deleted: 'Deleted accounts are kept here for the record.',
    noMatchTitle: 'No matching consumers',
    noMatchText: 'Try another name, email or phone number.',
    colConsumer: 'Consumer',
    colPhone: 'Phone',
    colJoined: 'Joined',
    colLastActive: 'Last active',
    colStatus: 'Status',
    colActions: 'Actions',
    unnamedConsumer: 'Unnamed consumer',
    noEmail: 'No email',
    noPhone: '—',
    tooltipViewProfile: 'View Consumer Profile',
    tooltipOptions: 'Account Options',
    showing: 'Showing',
    of: 'of',
    prevPage: 'Previous page',
    nextPage: 'Next page',
    close: 'Close',
    changeStatus: 'Change Status',
    deleteWarning: 'will be signed out and unable to log in. Their order history is kept.',
    optSetActive: 'Set active',
    optSetInactive: 'Set inactive',
    optSuspend: 'Suspend...',
    optDelete: 'Delete consumer...'
  },
  ph: {
    title: 'Manage Consumers',
    subtitle: 'Tingnan ang accounts ng mga shoppers at i-manage kung sino ang pwedeng gumamit ng Tindahan.',
    searchPlaceholder: 'Mag-search ng pangalan, email o phone',
    filterAll: 'Lahat',
    filterActive: 'Active',
    filterInactive: 'Inactive',
    filterSuspended: 'Suspended',
    filterDeleted: 'Deleted',
    stat_all: 'Lahat ng consumers',
    stat_active: 'Active',
    stat_inactive: 'Inactive',
    stat_suspended: 'Suspended',
    stat_deleted: 'Deleted',
    account: 'account',
    accounts: 'accounts',
    exportReport: 'Export Report',
    emptyTitle_all: 'Wala pang consumers',
    emptyText_all: 'Dito lalabas ang mga shoppers kapag nag-sign up na sila.',
    emptyTitle_active: 'Walang active consumers',
    emptyText_active: 'Dito nakalista ang mga shoppers na pwedeng mag-sign in at umorder.',
    emptyTitle_inactive: 'Walang inactive consumers',
    emptyText_inactive: 'Dito nakalista ang mga accounts na sinet mong inactive.',
    emptyTitle_suspended: 'Walang suspended consumers',
    emptyText_suspended: 'Dito nakalista ang mga accounts na sinuspend mo.',
    emptyTitle_deleted: 'Walang deleted consumers',
    emptyText_deleted: 'Naka-store dito ang mga deleted accounts for record purposes.',
    noMatchTitle: 'Walang nag-match',
    noMatchText: 'Try mag-search ng ibang pangalan, email o phone number.',
    colConsumer: 'Consumer',
    colPhone: 'Phone',
    colJoined: 'Joined',
    colLastActive: 'Last active',
    colStatus: 'Status',
    colActions: 'Actions',
    unnamedConsumer: 'Unnamed consumer',
    noEmail: 'No email',
    noPhone: '—',
    tooltipViewProfile: 'View Consumer Profile',
    tooltipOptions: 'Account Options',
    showing: 'Showing',
    of: 'of',
    prevPage: 'Previous page',
    nextPage: 'Next page',
    close: 'Close',
    changeStatus: 'Change Status',
    deleteWarning: 'ay masa-sign out at hindi na makaka-log in. Mananatili pa rin ang order history nila.',
    optSetActive: 'Set as active',
    optSetInactive: 'Set as inactive',
    optSuspend: 'Suspend...',
    optDelete: 'Delete consumer...'
  }
}

const { t } = useLanguage(consumersDict)

// Reactive Filters computing from dictionary
const localizedFilters = computed(() => [
  { key: 'all', label: t('filterAll') },
  { key: 'active', label: t('filterActive') },
  { key: 'inactive', label: t('filterInactive') },
  { key: 'suspended', label: t('filterSuspended') },
  { key: 'deleted', label: t('filterDeleted') }
])

// Keep original array structure for logic mapping
const FILTERS = [
  { key: 'all' },
  { key: 'active' },
  { key: 'inactive' },
  { key: 'suspended' },
  { key: 'deleted' }
]

const STAT_LABEL = {
  all: 'stat_all',
  active: 'stat_active',
  inactive: 'stat_inactive',
  suspended: 'stat_suspended',
  deleted: 'stat_deleted'
}

const SKELETON_COLUMNS = [
  { type: 'avatar', lines: 2 },
  { width: '14%', type: 'text' },
  { width: '13%', type: 'text' },
  { width: '13%', type: 'text' },
  { width: '11%', type: 'pill', size: 72 },
  { width: '80px', type: 'pill', size: 60, align: 'right' }
]
const PAGE_SIZE = 10

const consumers = ref([])
const loading = ref(true)
const search = ref('')
const active = ref('all')
const page = ref(1)
const isExporting = ref(false)
const statusDialog = ref(null)
const viewing = ref(null)
const viewOpen = ref(false)

const photoOf = consumer => {
  const url = consumer?.profile_picture_url
  return url && url !== 'null' && String(url).trim() ? url : null
}

const statusOf = consumer =>
  consumer.deleted ? 'deleted' : consumer.account_status

const inFilter = (consumer, key) => {
  if (key === 'deleted') return consumer.deleted
  if (consumer.deleted) return false
  return key === 'all' || consumer.account_status === key
}

const matchesSearch = consumer => {
  const needle = (search.value || '').trim().toLowerCase()
  if (!needle) return true
  return [consumer.full_name, consumer.email, consumer.phone_number].some(
    value =>
      String(value || '')
        .toLowerCase()
        .includes(needle)
  )
}

const totalFor = key => consumers.value.filter(c => inFilter(c, key)).length
const searched = computed(() => consumers.value.filter(matchesSearch))
const countFor = key => searched.value.filter(c => inFilter(c, key)).length

const filtered = computed(() =>
  searched.value
    .filter(c => inFilter(c, active.value))
    .sort(
      (a, b) =>
        new Date(b.last_activity_at || 0) - new Date(a.last_activity_at || 0)
    )
)

const pageCount = computed(() =>
  Math.max(1, Math.ceil(filtered.value.length / PAGE_SIZE))
)
const pagedConsumers = computed(() =>
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

const localizedStatusOptions = consumer => {
  const options = [
    { status: 'active', label: t('optSetActive'), icon: 'o_check_circle' },
    { status: 'inactive', label: t('optSetInactive'), icon: 'o_pause_circle' },
    { status: 'suspended', label: t('optSuspend'), icon: 'o_block', danger: true },
    {
      status: 'delete',
      label: t('optDelete'),
      icon: 'o_delete',
      danger: true
    }
  ]
  return options.filter(o => o.status !== consumer.account_status)
}

const openView = consumer => {
  viewing.value = consumer
  viewOpen.value = true
}

const changeStatus = (consumer, status) => {
  viewOpen.value = false
  statusDialog.value?.open(
    { id: consumer.user_id, name: consumer.full_name },
    status
  )
}

const onChanged = ({ userId, status }) => {
  const consumer = consumers.value.find(c => c.user_id === userId && !c.deleted)
  if (consumer) consumer.account_status = status
}

const onDeleted = ({ userId }) => {
  const consumer = consumers.value.find(c => c.user_id === userId && !c.deleted)
  if (consumer) consumer.deleted = true
}

const listOf = res =>
  Array.isArray(res.data) ? res.data : res.data?.data || []

const fetchConsumers = async () => {
  loading.value = true
  try {
    const [current, removed] = await Promise.all([
      api.get('/admin/consumers', { params: { tab: 'active' } }),
      api.get('/admin/consumers', { params: { tab: 'deleted' } })
    ])
    consumers.value = [
      ...listOf(current).map(c => ({ ...c, deleted: false })),
      ...listOf(removed).map(c => ({ ...c, deleted: true }))
    ]
  } catch (error) {
    console.error('Failed to load consumers', error)
  } finally {
    loading.value = false
  }
}

const handleExport = async () => {
  if (isExporting.value) return
  isExporting.value = true
  try {
    const response = await api.get('/admin/consumers/export', {
      params: {
        tab: active.value === 'deleted' ? 'deleted' : 'active',
        search: search.value || undefined
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
      `Tindahan_Admin_Consumers_${new Date().toISOString().split('T')[0]}.pdf`
    )
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    setTimeout(() => window.URL.revokeObjectURL(url), 1000)
  } finally {
    isExporting.value = false
  }
}

onMounted(fetchConsumers)
</script>

<style scoped>
.cn-table .col-phone {
  width: 14%;
}
.cn-table .col-date {
  width: 13%;
}
.cn-table .col-status {
  width: 11%;
}
.cn-table .col-actions {
  width: 15%;
}

/* ALL-ICON UNIFIED ACTION GROUP */
.vd-icon-action-group {
  display: inline-flex;
  align-items: center;
  justify-content: flex-end;
  gap: 4px;
}

.vd-action-icon-btn {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  color: var(--c-text-2, #64748b);
  transition: all 0.15s ease;
}

.vd-action-icon-btn:hover {
  background: var(--c-surface-2, #f1f5f9);
  color: var(--c-text, #0f172a);
}

.vd-action-icon-btn--primary:hover {
  background: var(--c-brand-tint, rgba(201, 35, 42, 0.08));
  color: var(--c-brand, #c9232a);
}

.vd-action-icon-btn--sm {
  width: 26px;
  height: 26px;
}

/* COMPACT MENU POPUP */
:deep(.compact-status-menu) {
  border-radius: 8px !important;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12) !important;
  border: 1px solid var(--c-hairline, #e2e8f0);
  background: var(--c-surface, #ffffff) !important;
}

.compact-menu-list {
  padding: 4px 0 !important;
  min-width: 140px;
}

.compact-menu-item {
  min-height: 32px !important;
  padding: 6px 12px !important;
  font-size: 12.5px;
  color: var(--c-text, #1e293b);
  transition: background-color 0.15s ease;
}

.compact-menu-item:hover {
  background: var(--c-surface-2, #f8fafc);
}

.compact-menu-avatar {
  min-width: 24px !important;
  padding-right: 8px !important;
  color: var(--c-muted, #64748b);
}

.compact-menu-label {
  font-weight: 500;
}

.compact-menu-item--danger {
  color: #dc2626 !important;
}

.compact-menu-item--danger .compact-menu-avatar {
  color: #dc2626 !important;
}

.text-muted-themed {
  color: var(--c-muted, #64748b);
}

</style>

<!--
  DARK MODE OVERRIDES — moved to an unscoped block, same reasoning as
  AdminVendors.vue: the compact status menu is a <q-menu>, teleported by
  Quasar to a node under <body>, so neither ":deep(.body--dark) X" nor
  ":global(.admin-layout--dark) X" (a plain descendant selector expecting
  .admin-layout--dark to still be an ancestor once teleported) ever matched
  it. body.body--dark.admin-dark-mode is a marker AdminLayout.vue toggles on
  <body> itself, which the teleported menu's real parent actually is.
-->
<style>
body.body--dark.admin-dark-mode .compact-status-menu {
  background: #181b20 !important;
  border-color: #262a32 !important;
}

body.body--dark.admin-dark-mode .compact-menu-item {
  color: #e2e8f0 !important;
}

body.body--dark.admin-dark-mode .compact-menu-item:hover {
  background: #20242b !important;
}

body.body--dark.admin-dark-mode .text-muted-themed {
  color: #94a3b8 !important;
}
</style>