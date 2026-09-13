<template>
  <q-page class="vp-page">
    <div class="vp-container">
      <AdminHero
        icon="o_groups"
        title="Manage Consumers"
        subtitle="See every shopper's account and manage who can use Tindahan."
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
            placeholder="Search name, email or phone"
            class="vp-search"
          >
            <template #prepend>
              <q-icon name="o_search" size="18px" />
            </template>
          </q-input>

          <!-- Every status, plus the deleted accounts, each with its count; the server has no status filter, so the page sorts them itself. -->
          <div class="vp-chips" role="tablist" aria-label="Filter consumers">
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
        />

        <div v-else-if="!filtered.length" class="vp-empty">
          <div class="vp-empty-icon"
            ><q-icon name="o_groups" size="24px"
          /></div>
          <div class="vp-empty-title">{{
            search ? 'No matching consumers' : EMPTY[active].title
          }}</div>
          <div class="vp-empty-text">{{
            search
              ? 'Try another name, email or phone number.'
              : EMPTY[active].text
          }}</div>
        </div>

        <!-- A table on wide screens; a row opens the consumer's profile. -->
        <div v-else-if="!$q.screen.lt.md" class="vp-table-wrap">
          <table class="vp-table cn-table">
            <thead>
              <tr>
                <th>Consumer</th>
                <th class="col-phone">Phone</th>
                <th class="col-date">Joined</th>
                <th class="col-date">Last active</th>
                <th class="col-status">Status</th>
                <th class="col-actions text-right">Actions</th>
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
                      <span class="vp-name">{{
                        consumer.full_name || 'Unnamed consumer'
                      }}</span>
                      <span class="adm-sub">{{
                        consumer.email || 'No email'
                      }}</span>
                    </span>
                  </div>
                </td>
                <td class="vp-muted">{{ consumer.phone_number || '—' }}</td>
                <td class="vp-muted">{{
                  formatShortDate(consumer.created_at)
                }}</td>
                <td class="vp-muted">{{
                  formatActivity(consumer.last_activity_at)
                }}</td>
                <td>
                  <span
                    class="vp-status"
                    :class="`vp-status--${accountStatusTone(statusOf(consumer))}`"
                    >{{ accountStatusLabel(statusOf(consumer)) }}</span
                  >
                </td>
                <td class="text-right">
                  <div class="adm-row-actions" @click.stop @keydown.enter.stop>
                    <q-btn
                      flat
                      no-caps
                      label="View"
                      class="adm-btn adm-btn--view"
                      @click="openView(consumer)"
                    />
                    <q-btn
                      v-if="!consumer.deleted"
                      flat
                      icon="o_more_vert"
                      class="adm-btn adm-btn--more"
                      :aria-label="`Change ${consumer.full_name}'s account`"
                    >
                      <q-menu anchor="bottom right" self="top right" auto-close>
                        <q-list class="vp-menu-list">
                          <q-item
                            v-for="option in statusOptions(consumer)"
                            :key="option.status"
                            clickable
                            :class="{ 'vp-menu-item--danger': option.danger }"
                            @click="changeStatus(consumer, option.status)"
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
            v-for="consumer in pagedConsumers"
            :key="consumer.user_id"
            type="button"
            class="vp-list-item"
            @click="openView(consumer)"
          >
            <span class="adm-thumb adm-thumb--lg adm-thumb--round">
              <img v-if="photoOf(consumer)" :src="photoOf(consumer)" alt="" />
              <q-icon v-else name="o_person" size="22px" />
            </span>
            <div class="vp-list-body">
              <span class="vp-name">{{
                consumer.full_name || 'Unnamed consumer'
              }}</span>
              <div class="vp-list-meta">{{
                formatActivity(consumer.last_activity_at)
              }}</div>
            </div>
            <div class="vp-list-side">
              <span
                class="vp-status"
                :class="`vp-status--${accountStatusTone(statusOf(consumer))}`"
                >{{ accountStatusLabel(statusOf(consumer)) }}</span
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

    <!-- VIEW — the consumer's profile, with the account change at the foot. -->
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
          label="Close"
          class="vp-dialog-btn"
        />
        <q-btn
          v-if="viewing && !viewing.deleted"
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
    </ConsumerProfileDialog>

    <AccountStatusDialog
      ref="statusDialog"
      kind="consumers"
      noun="consumer"
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
import {
  accountStatusTone,
  accountStatusLabel,
  formatActivity,
  formatShortDate
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
  all: 'All consumers',
  active: 'Active',
  inactive: 'Inactive',
  suspended: 'Suspended',
  deleted: 'Deleted'
}

const EMPTY = {
  all: {
    title: 'No consumers yet',
    text: 'Shoppers show up here once they sign up.'
  },
  active: {
    title: 'No active consumers',
    text: 'Shoppers who can sign in and order are listed here.'
  },
  inactive: {
    title: 'No inactive consumers',
    text: 'Accounts you set inactive are listed here.'
  },
  suspended: {
    title: 'No suspended consumers',
    text: 'Accounts you suspend are listed here.'
  },
  deleted: {
    title: 'No deleted consumers',
    text: 'Deleted accounts are kept here for the record.'
  }
}

// Every change but the current status; suspending and deleting are the ones that need care.
const STATUS_OPTIONS = [
  { status: 'active', label: 'Set active', icon: 'o_check_circle' },
  { status: 'inactive', label: 'Set inactive', icon: 'o_pause_circle' },
  { status: 'suspended', label: 'Suspend…', icon: 'o_block', danger: true },
  {
    status: 'delete',
    label: 'Delete consumer…',
    icon: 'o_delete',
    danger: true
  }
]

// The placeholder rows take the table's columns.
const SKELETON_COLUMNS = [
  { type: 'avatar', lines: 2 },
  { width: '14%', type: 'text' },
  { width: '13%', type: 'text' },
  { width: '13%', type: 'text' },
  { width: '11%', type: 'pill', size: 72 },
  { width: '130px', type: 'pill', size: 96, align: 'right' }
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

// A deleted account reads as Deleted whatever its last status was.
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

// The banner counts every consumer in the chosen chip; the chips count what the search leaves.
const totalFor = key => consumers.value.filter(c => inFilter(c, key)).length
const searched = computed(() => consumers.value.filter(matchesSearch))
const countFor = key => searched.value.filter(c => inFilter(c, key)).length

// The most recently active first; people who never signed in go last.
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
// A change can move a row to another chip, so a page that runs out steps back.
watch(pageCount, count => {
  if (page.value > count) page.value = count
})

const statusOptions = consumer =>
  STATUS_OPTIONS.filter(o => o.status !== consumer.account_status)

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

// The changed consumer moves to its new chip straight away.
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

// Both lists load together, so every chip can show its count from the start.
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
    $q.notify({
      type: 'negative',
      message: 'Couldn’t load the consumers. Please refresh.'
    })
  } finally {
    loading.value = false
  }
}

// The report follows the Deleted chip and the search.
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
  width: 130px;
}
</style>
