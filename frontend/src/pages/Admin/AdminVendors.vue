<template>
  <q-page class="vp-page">
    <div class="vp-container">
      <AdminHero
        icon="o_storefront"
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

          <!-- Status Chips -->
          <div class="vp-chips" role="tablist" :aria-label="t('filterVendors')">
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
          <div class="vp-empty-icon">
            <q-icon name="o_storefront" size="24px" />
          </div>
          <div class="vp-empty-title">{{
            search ? t('noMatchTitle') : t('emptyTitle_' + active)
          }}</div>
          <div class="vp-empty-text">{{
            search ? t('noMatchText') : t('emptyText_' + active)
          }}</div>
        </div>

        <!-- VENDOR TABLE -->
        <div v-else-if="!$q.screen.lt.md" class="vp-table-wrap">
          <table class="vp-table vd-table">
            <thead>
              <tr>
                <th class="col-store">{{ t('colStore') }}</th>
                <th class="col-contact">{{ t('colContact') }}</th>
                <th class="col-stat text-center">{{ t('colProducts') }}</th>
                <th class="col-stat text-center">{{ t('colOrders') }}</th>
                <th class="col-date">{{ t('colLastActive') }}</th>
                <th class="col-status text-center">{{ t('colStatus') }}</th>
                <th class="col-actions text-right">{{ t('colActions') }}</th>
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
                <!-- STORE & OWNER -->
                <td class="col-store">
                  <div class="vp-person">
                    <span class="adm-thumb">
                      <img
                        v-if="photoOf(vendor)"
                        :src="photoOf(vendor)"
                        alt=""
                      />
                      <q-icon v-else name="o_storefront" size="18px" />
                    </span>
                    <div class="adm-two-lines">
                      <span class="vp-name ellipsis">{{
                        vendor.store_name || t('unnamedStore')
                      }}</span>
                      <span class="adm-sub text-muted-themed ellipsis">{{ vendor.full_name }}</span>
                    </div>
                  </div>
                </td>

                <!-- CONTACT -->
                <td class="col-contact">
                  <div class="adm-two-lines">
                    <span class="adm-email ellipsis">{{ vendor.email || '—' }}</span>
                    <span class="adm-sub text-muted-themed ellipsis">{{
                      vendor.phone_number || t('noPhone')
                    }}</span>
                  </div>
                </td>

                <!-- PRODUCTS -->
                <td class="col-stat text-center">
                  <div class="row items-center justify-center no-wrap text-muted-themed">
                    <q-icon name="o_inventory_2" size="20px" class="q-mr-xs" />
                    <span class="text-weight-bold dialog-title-text" style="font-size: 13px;">
                      {{ vendor.active_products ?? 0 }}
                    </span>
                  </div>
                </td>

                <!-- ORDERS -->
                <td class="col-stat text-center">
                  <div class="row items-center justify-center no-wrap text-muted-themed">
                    <q-icon name="o_receipt_long" size="20px" class="q-mr-xs" />
                    <span class="text-weight-bold dialog-title-text" style="font-size: 13px;">
                      {{ vendor.orders_count ?? 0 }}
                    </span>
                  </div>
                </td>

                <!-- LAST ACTIVE -->
                <td class="col-date">
                  <div class="row items-center no-wrap text-muted-themed">
                    <q-icon name="o_schedule" size="14px" class="q-mr-xs" />
                    <span>{{ formatActivity(vendor.last_activity_at) }}</span>
                  </div>
                </td>

                <!-- STATUS -->
                <td class="col-status text-center">
                  <span
                    class="vp-status"
                    :class="`vp-status--${accountStatusTone(statusOf(vendor))}`"
                  >
                    {{ accountStatusLabel(statusOf(vendor)) }}
                  </span>
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
                      @click="openView(vendor)"
                    >
                      <q-tooltip anchor="top middle" self="bottom middle">{{ t('tooltipViewProfile') }}</q-tooltip>
                    </q-btn>

                    <q-btn
                      flat
                      round
                      dense
                      icon="o_inventory_2"
                      class="vd-action-icon-btn vd-action-icon-btn--primary"
                      @click="openProducts(vendor)"
                    >
                      <q-tooltip anchor="top middle" self="bottom middle">{{ t('tooltipViewProducts') }}</q-tooltip>
                    </q-btn>

                    <q-btn
                      v-if="!vendor.deleted"
                      flat
                      round
                      dense
                      icon="o_more_vert"
                      class="vd-action-icon-btn text-muted-themed"
                      :aria-label="`${t('changeStatusFor')} ${vendor.full_name}`"
                    >
                      <q-tooltip anchor="top middle" self="bottom middle">{{ t('tooltipOptions') }}</q-tooltip>
                      <q-menu anchor="bottom right" self="top right" auto-close class="compact-status-menu">
                        <q-list dense class="compact-menu-list">
                          <q-item
                            v-for="option in localizedStatusOptions(vendor)"
                            :key="option.status"
                            clickable
                            v-ripple
                            class="compact-menu-item"
                            :class="{ 'compact-menu-item--danger': option.danger }"
                            @click="changeStatus(vendor, option.status)"
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
            v-for="vendor in pagedVendors"
            :key="vendor.user_id"
            type="button"
            class="vp-list-item cursor-pointer"
            role="button"
            tabindex="0"
            @click="openView(vendor)"
          >
            <span class="adm-thumb adm-thumb--lg">
              <img v-if="photoOf(vendor)" :src="photoOf(vendor)" alt="" />
              <q-icon v-else name="o_storefront" size="20px" />
            </span>
            <div class="vp-list-body">
              <span class="vp-name">{{
                vendor.store_name || t('unnamedStore')
              }}</span>
              <div class="vp-list-meta text-muted-themed">
                {{ vendor.full_name }} · {{ formatActivity(vendor.last_activity_at) }}
              </div>
              <div class="row items-center q-gutter-x-md q-mt-xs">
                <div class="row items-center no-wrap text-muted-themed">
                  <q-icon name="o_inventory_2" size="18px" class="q-mr-xs" />
                  <span class="text-weight-bold dialog-title-text">{{ vendor.active_products ?? 0 }}</span>
                  <span class="q-ml-xs text-caption">{{ t('items') }}</span>
                </div>
                <div class="row items-center no-wrap text-muted-themed">
                  <q-icon name="o_receipt_long" size="18px" class="q-mr-xs" />
                  <span class="text-weight-bold dialog-title-text">{{ vendor.orders_count ?? 0 }}</span>
                  <span class="q-ml-xs text-caption">{{ t('ordersWord') }}</span>
                </div>
              </div>
            </div>
            <div class="vp-list-side column items-end q-gutter-y-xs">
              <span
                class="vp-status"
                :class="`vp-status--${accountStatusTone(statusOf(vendor))}`"
              >
                {{ accountStatusLabel(statusOf(vendor)) }}
              </span>
              <div class="row items-center gap-xs q-mt-xs" @click.stop>
                <q-btn
                  flat
                  round
                  dense
                  size="sm"
                  icon="o_inventory_2"
                  color="primary"
                  class="vd-action-icon-btn vd-action-icon-btn--sm"
                  @click="openProducts(vendor)"
                />
                <q-btn
                  flat
                  round
                  dense
                  size="sm"
                  icon="o_visibility"
                  class="vd-action-icon-btn vd-action-icon-btn--sm"
                  @click="openView(vendor)"
                />
              </div>
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

    <!-- STORE PROFILE DIALOG -->
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
        >
          {{ accountStatusLabel(statusOf(viewing)) }}
        </span>
      </template>
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
          v-if="!viewing.deleted"
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
    </StoreProfileDialog>

    <!-- COMPACT & POLISHED PRODUCTS VIEW DIALOG -->
    <q-dialog v-model="productsOpen" transition-show="scale" transition-hide="scale">
      <q-card class="products-compact-dialog vp-dialog">
        <!-- Header Strip -->
        <div class="products-dialog-head row items-center justify-between no-wrap q-px-lg q-py-md">
          <div class="row items-center no-wrap ellipsis">
            <span class="dialog-title-icon q-mr-sm">
              <q-icon name="o_inventory_2" size="18px" />
            </span>
            <div class="text-subtitle1 text-weight-bold dialog-title-text ellipsis">
              {{ t('liveProducts') }} · <span class="text-primary">{{ viewingVendor?.store_name || t('unnamedStore') }}</span>
            </div>
          </div>
          <q-btn
            v-close-popup
            flat
            round
            dense
            icon="o_close"
            class="text-muted-themed hover-primary"
            :aria-label="t('close')"
          />
        </div>

        <!-- Scrollable Products Area -->
        <div class="products-dialog-body scroll">
          <div v-if="productsLoading" class="q-py-xl flex flex-center">
            <q-spinner-dots size="40px" color="primary" />
          </div>

          <div v-else-if="!vendorProducts.length" class="q-py-xl flex flex-center column text-center">
            <q-icon name="o_inventory_2" size="44px" class="q-mb-sm text-muted-themed" />
            <div class="text-body2 text-weight-bold dialog-title-text">{{ t('noProductsListed') }}</div>
            <div class="text-caption text-muted-themed">{{ t('noProductsDesc') }}</div>
          </div>

          <div v-else class="vp-table-wrap">
            <table class="vp-table live-prod-table">
              <thead>
                <tr>
                  <th class="col-prod-name">{{ t('colProduct') }}</th>
                  <th class="col-prod-cat">{{ t('colCategory') }}</th>
                  <th class="col-prod-price text-right">{{ t('colSellingPrice') }}</th>
                  <th class="col-prod-qty text-center">{{ t('colInStock') }}</th>
                  <th class="col-prod-status text-center">{{ t('colProdStatus') }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="prod in vendorProducts" :key="prod.inventory_id">
                  <td class="col-prod-name">
                    <div class="vp-person no-wrap">
                      <span class="live-prod-thumb">
                        <img v-if="prod.image_url && !prod.imageError" :src="prod.image_url" alt="" @error="prod.imageError = true" />
                        <q-icon v-else name="o_image" size="18px" />
                      </span>
                      <span class="vp-name ellipsis">{{ prod.product_name }}</span>
                    </div>
                  </td>
                  <td class="col-prod-cat text-muted-themed">{{ prod.category?.category_name || t('uncategorized') }}</td>
                  <td class="col-prod-price text-right">
                    <div class="text-weight-bold dialog-title-text">₱{{ Number(prod.price).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}</div>
                    <div v-if="prod.variants && prod.variants.length > 0" class="text-caption text-muted-themed mt-xs">
                      <!-- Vendor forms save the label under 'size', the seeded catalog under 'name'. -->
                      <div v-for="(v, vi) in prod.variants" :key="vi">
                        {{ v.size || v.name || `#${vi + 1}` }}: ₱{{ Number(v.price).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}
                      </div>
                    </div>
                  </td>
                  <td class="col-prod-qty text-center">
                    <span class="text-weight-bold dialog-title-text">{{ prod.available_quantity }}</span>
                  </td>
                  <td class="col-prod-status text-center">
                    <span
                      class="vp-status"
                      :class="`vp-status--${productStatusTone(prod.status)}`"
                    >
                      {{ formatProductStatus(prod.status) }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Compact Footer -->
        <div class="products-dialog-foot row items-center justify-end q-px-lg q-py-sm">
          <q-btn
            v-close-popup
            flat
            no-caps
            :label="t('close')"
            class="vp-dialog-btn text-weight-bold text-muted-themed"
          />
        </div>
      </q-card>
    </q-dialog>

    <AccountStatusDialog
      ref="statusDialog"
      kind="vendors"
      noun="vendor"
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
import StoreProfileDialog from '@/components/shared/StoreProfileDialog.vue'
import { useLanguage } from '@/composables/useLanguage'
import {
  accountStatusTone,
  accountStatusLabel,
  formatActivity
} from '@/utils/accountStatus'
import '@/css/admin-pages.scss'

const $q = useQuasar()

// --- DICTIONARY FOR LANGUAGE SWITCHER (NATURAL TAGLISH) ---
const vendorsDict = {
  en: {
    title: 'Manage Vendors',
    subtitle: "See every approved store, how it's doing, and manage its account.",
    searchPlaceholder: 'Search store, owner or email',
    filterAll: 'All',
    filterActive: 'Active',
    filterInactive: 'Inactive',
    filterSuspended: 'Suspended',
    filterDeleted: 'Deleted',
    stat_all: 'All vendors',
    stat_active: 'Active',
    stat_inactive: 'Inactive',
    stat_suspended: 'Suspended',
    stat_deleted: 'Deleted',
    account: 'account',
    accounts: 'accounts',
    exportReport: 'Export Report',
    emptyTitle_all: 'No vendors yet',
    emptyText_all: 'Stores show up here once their application is approved.',
    emptyTitle_active: 'No active vendors',
    emptyText_active: 'Vendors who can sign in and sell are listed here.',
    emptyTitle_inactive: 'No inactive vendors',
    emptyText_inactive: 'Vendors you set inactive are listed here.',
    emptyTitle_suspended: 'No suspended vendors',
    emptyText_suspended: 'Vendors you suspend are listed here.',
    emptyTitle_deleted: 'No deleted vendors',
    emptyText_deleted: 'Deleted vendor accounts are kept here for the record.',
    noMatchTitle: 'No matching vendors',
    noMatchText: 'Try another name, store or email.',
    colStore: 'Store & Owner',
    colContact: 'Contact',
    colProducts: 'Products',
    colOrders: 'Orders',
    colLastActive: 'Last active',
    colStatus: 'Status',
    colActions: 'Actions',
    unnamedStore: 'Unnamed store',
    noPhone: 'No phone',
    items: 'items',
    ordersWord: 'orders',
    tooltipViewProfile: 'View Vendor Profile',
    tooltipViewProducts: 'View Store Products',
    tooltipOptions: 'Account Options',
    changeStatusFor: 'Change status for',
    showing: 'Showing',
    of: 'of',
    prevPage: 'Previous page',
    nextPage: 'Next page',
    close: 'Close',
    changeStatus: 'Change Status',
    liveProducts: 'Live Products',
    noProductsListed: 'No products listed',
    noProductsDesc: 'This vendor has not published any live items yet.',
    colProduct: 'Product',
    colCategory: 'Category',
    colSellingPrice: 'Selling Price',
    colInStock: 'In Stock',
    colProdStatus: 'Product Status',
    uncategorized: 'Uncategorized',
    deleteWarning: 'will be signed out, and their store will be hidden from customers. Their order history is kept.',
    optSetActive: 'Set active',
    optSetInactive: 'Set inactive',
    optSuspend: 'Suspend...',
    optDelete: 'Delete vendor...',
    vendorInfoLabel: 'Vendor',
    lastActiveLabel: 'Last active'
  },
  ph: {
    title: 'Manage Vendors',
    subtitle: 'Tingnan ang lahat ng approved stores, ang status nila, at i-manage ang accounts nila.',
    searchPlaceholder: 'Mag-search ng store, owner o email',
    filterAll: 'Lahat',
    filterActive: 'Active',
    filterInactive: 'Inactive',
    filterSuspended: 'Suspended',
    filterDeleted: 'Deleted',
    stat_all: 'Lahat ng vendors',
    stat_active: 'Active',
    stat_inactive: 'Inactive',
    stat_suspended: 'Suspended',
    stat_deleted: 'Deleted',
    account: 'account',
    accounts: 'accounts',
    exportReport: 'Export Report',
    emptyTitle_all: 'Wala pang vendors',
    emptyText_all: 'Dito lalabas ang mga stores kapag approved na ang application nila.',
    emptyTitle_active: 'Walang active vendors',
    emptyText_active: 'Dito nakalista ang mga vendors na pwedeng mag-sign in at magbenta.',
    emptyTitle_inactive: 'Walang inactive vendors',
    emptyText_inactive: 'Dito nakalista ang mga vendors na sinet mong inactive.',
    emptyTitle_suspended: 'Walang suspended vendors',
    emptyText_suspended: 'Dito nakalista ang mga vendors na sinuspend mo.',
    emptyTitle_deleted: 'Walang deleted vendors',
    emptyText_deleted: 'Naka-store dito ang mga deleted accounts for record purposes.',
    noMatchTitle: 'Walang nag-match',
    noMatchText: 'Try mag-search ng ibang pangalan, store o email.',
    colStore: 'Store & Owner',
    colContact: 'Contact',
    colProducts: 'Products',
    colOrders: 'Orders',
    colLastActive: 'Last active',
    colStatus: 'Status',
    colActions: 'Actions',
    unnamedStore: 'Unnamed store',
    noPhone: 'Walang phone number',
    items: 'items',
    ordersWord: 'orders',
    tooltipViewProfile: 'View Vendor Profile',
    tooltipViewProducts: 'View Store Products',
    tooltipOptions: 'Account Options',
    changeStatusFor: 'Change status ni',
    showing: 'Showing',
    of: 'of',
    prevPage: 'Previous page',
    nextPage: 'Next page',
    close: 'Close',
    changeStatus: 'Change Status',
    liveProducts: 'Live Products',
    noProductsListed: 'Walang naka-list na products',
    noProductsDesc: 'Wala pang pinu-publish na live items ang vendor na ito.',
    colProduct: 'Product',
    colCategory: 'Category',
    colSellingPrice: 'Selling Price',
    colInStock: 'In Stock',
    colProdStatus: 'Status',
    uncategorized: 'Uncategorized',
    deleteWarning: 'ay masa-sign out, at maha-hide ang store nila sa customers. Mananatili pa rin ang order history nila.',
    optSetActive: 'Set as active',
    optSetInactive: 'Set as inactive',
    optSuspend: 'Suspend...',
    optDelete: 'Delete vendor...',
    vendorInfoLabel: 'Vendor',
    lastActiveLabel: 'Last active'
  }
}

const { t } = useLanguage(vendorsDict)

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
  { type: 'thumb', lines: 2 },
  { width: '22%', type: 'text' },
  { width: '10%', type: 'text', align: 'center' },
  { width: '10%', type: 'text', align: 'center' },
  { width: '14%', type: 'text' },
  { width: '11%', type: 'pill', size: 72, align: 'center' },
  { width: '110px', type: 'pill', size: 90, align: 'right' }
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

const totalFor = key => vendors.value.filter(v => inFilter(v, key)).length
const searched = computed(() => vendors.value.filter(matchesSearch))
const countFor = key => searched.value.filter(v => inFilter(v, key)).length

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

watch(pageCount, count => {
  if (page.value > count) page.value = count
})

const localizedStatusOptions = vendor => {
  const options = [
    { status: 'active', label: t('optSetActive'), icon: 'o_check_circle' },
    { status: 'inactive', label: t('optSetInactive'), icon: 'o_pause_circle' },
    { status: 'suspended', label: t('optSuspend'), icon: 'o_block', danger: true },
    { status: 'delete', label: t('optDelete'), icon: 'o_delete', danger: true }
  ]
  return options.filter(o => o.status !== vendor.account_status)
}

const productStatusTone = status => {
  switch (String(status || 'active').toLowerCase()) {
    case 'active':
      return 'success'
    case 'deactivated':
    case 'inactive':
      return 'neutral'
    case 'suspended':
    case 'deleted':
    case 'cancelled':
      return 'danger'
    default:
      return 'neutral'
  }
}

const formatProductStatus = status => {
  if (!status) return 'Active'
  const s = String(status).toLowerCase()
  return s.charAt(0).toUpperCase() + s.slice(1)
}

const viewInfo = computed(() =>
  viewing.value
    ? [
        { label: t('vendorInfoLabel'), value: viewing.value.full_name },
        {
          label: t('lastActiveLabel'),
          value: formatActivity(viewing.value.last_activity_at)
        }
      ]
    : []
)

const viewStats = computed(() =>
  viewing.value
    ? [
        {
          label: t('colProducts'),
          value: viewing.value.active_products ?? 0,
          icon: 'o_inventory_2'
        },
        {
          label: t('colOrders'),
          value: viewing.value.orders_count ?? 0,
          icon: 'o_receipt_long'
        }
      ]
    : []
)

const productsOpen = ref(false)
const productsLoading = ref(false)
const viewingVendor = ref(null)
const vendorProducts = ref([])

const openProducts = async (vendor) => {
  viewingVendor.value = vendor
  productsOpen.value = true
  productsLoading.value = true
  vendorProducts.value = []
  
  try {
    const { data } = await api.get(`/admin/vendors/${vendor.store_id}/products`)
    if (data && data.products) {
      vendorProducts.value = data.products
    }
  } catch (err) {
    console.error('Failed to load products for vendor:', err)
  } finally {
    productsLoading.value = false
  }
}

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

const onChanged = ({ userId, status }) => {
  const vendor = vendors.value.find(v => v.user_id === userId && !v.deleted)
  if (vendor) vendor.account_status = status
}

const onDeleted = ({ userId }) => {
  const vendor = vendors.value.find(v => v.user_id === userId && !v.deleted)
  if (vendor) vendor.deleted = true
}

const listOf = res =>
  Array.isArray(res.data) ? res.data : res.data?.data || []

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
  } finally {
    loading.value = false
  }
}

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
  } finally {
    isExporting.value = false
  }
}

onMounted(fetchVendors)
</script>

<style scoped>
/* TABLE GEOMETRY */
.vd-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
}

.vd-table th {
  padding: 12px 16px;
  font-size: 11px;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  font-weight: 700;
  color: var(--c-muted, #64748b);
  border-bottom: 1px solid var(--c-hairline, #e2e8f0);
}

.vd-table td {
  padding: 14px 16px;
  vertical-align: middle;
  border-bottom: 1px solid var(--c-hairline, #f1f5f9);
}

.vd-table .col-store { width: 27%; }
.vd-table .col-contact { width: 23%; }
.vd-table .col-stat { width: 9%; }
.vd-table .col-date { width: 14%; }
.vd-table .col-status { width: 12%; }
.vd-table .col-actions { width: 15%; }

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

/* PRODUCTS MODAL */
.products-compact-dialog {
  width: 820px;
  max-width: 94vw;
  border-radius: 16px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.products-dialog-head {
  border-bottom: 1px solid var(--c-hairline, #e2e8f0);
  background: var(--c-surface, #ffffff);
}

.dialog-title-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 30px;
  height: 30px;
  border-radius: 8px;
  background: var(--c-brand-tint, rgba(201, 35, 42, 0.08));
  color: var(--c-brand, #c9232a);
}

.products-dialog-body {
  max-height: 60vh;
  padding: 0;
}

.products-dialog-foot {
  border-top: 1px solid var(--c-hairline, #e2e8f0);
  background: var(--c-surface-2, #fbfcfd);
}

/* LIVE PRODUCTS TABLE */
.live-prod-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
}

.live-prod-table th {
  padding: 12px 16px;
  font-size: 11px;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  font-weight: 700;
  color: var(--c-muted, #64748b);
  border-bottom: 1px solid var(--c-hairline, #e2e8f0);
  background: var(--c-surface-2, #f8fafc);
}

.live-prod-table td {
  padding: 12px 16px;
  vertical-align: middle;
  border-bottom: 1px solid var(--c-hairline, #f1f5f9);
  font-size: 13px;
}

.live-prod-table .col-prod-name { width: 34%; }
.live-prod-table .col-prod-cat { width: 20%; }
.live-prod-table .col-prod-price { width: 18%; }
.live-prod-table .col-prod-qty { width: 13%; }
.live-prod-table .col-prod-status { width: 15%; }

.live-prod-thumb {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: 6px;
  overflow: hidden;
  background: var(--c-surface-2, #f1f5f9);
  border: 1px solid var(--c-hairline, #e2e8f0);
  margin-right: 8px;
  flex-shrink: 0;
}

.live-prod-thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.mt-xs {
  margin-top: 4px;
}

.text-muted-themed {
  color: var(--c-muted, #64748b);
}

.dialog-title-text {
  color: var(--c-text, #1e293b);
}

/* FLOATING CLOSE BUTTON */
.vp-dialog-close-float {
  position: absolute;
  top: 24px;
  right: 24px;
  z-index: 2;
  background: rgba(255, 255, 255, 0.94);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
  color: var(--c-text-2);
}

@media (max-width: 600px) {
  .vp-dialog-close-float {
    top: 18px;
    right: 18px;
  }
}

</style>

<!--
  DARK MODE OVERRIDES — moved to an unscoped block.

  The rules this replaced paired ":deep(.body--dark) X" with
  ":global(.admin-layout--dark) X", but neither actually reached this dialog:
  Quasar teleports every <q-dialog>/<q-menu> (the products dialog, the ⋮
  status menu) to a node appended directly under <body>, outside
  .admin-layout's own DOM subtree. ":global(.admin-layout--dark) X" is a
  plain descendant selector expecting .admin-layout--dark to be an ancestor
  in the real DOM, which stops being true the moment the content is
  teleported — so these rules silently never matched, and the dialog stayed
  its light-mode white while the rest of the admin UI went dark. (":deep(.body--dark) X"
  never worked either: :deep() is for reaching into a *child* component's
  scoped styles from an ancestor, not for treating an ancestor body class as
  if it were a descendant.)

  These selectors read correctly off body.body--dark.admin-dark-mode instead
  — a marker the layout toggles on <body> itself alongside $q.dark (see
  setDarkMode in AdminLayout.vue), which every teleported node can see since
  its real parent is <body>. The admin-dark-mode marker (rather than bare
  body.body--dark) keeps this from ever matching while the vendor module is
  the one in dark mode, even though some of these class names (vp-dialog,
  vp-status) are shared with it.
-->
<style>
body.body--dark.admin-dark-mode .vp-dialog,
body.body--dark.admin-dark-mode .products-compact-dialog {
  background: #181b20 !important;
  border: 1px solid #262a32 !important;
}

body.body--dark.admin-dark-mode .products-dialog-head {
  background: #181b20 !important;
  border-color: #262a32 !important;
}

body.body--dark.admin-dark-mode .products-dialog-foot {
  background: #1f2329 !important;
  border-color: #262a32 !important;
}

body.body--dark.admin-dark-mode .live-prod-table th {
  background: #1f2329 !important;
  border-color: #262a32 !important;
  color: #94a3b8 !important;
}

body.body--dark.admin-dark-mode .live-prod-table td {
  border-color: #262a32 !important;
  color: #f1f5f9 !important;
}

body.body--dark.admin-dark-mode .text-muted-themed,
body.body--dark.admin-dark-mode .adm-sub {
  color: #94a3b8 !important;
}

body.body--dark.admin-dark-mode .dialog-title-text,
body.body--dark.admin-dark-mode .vp-name,
body.body--dark.admin-dark-mode .adm-email {
  color: #f8fafc !important;
}

body.body--dark.admin-dark-mode .live-prod-thumb {
  background: #20242b !important;
  border-color: #2a2e35 !important;
}

body.body--dark.admin-dark-mode .vp-dialog-close-float {
  background: rgba(30, 34, 40, 0.94) !important;
  color: #94a3b8 !important;
}

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

/* Status badges */
body.body--dark.admin-dark-mode .vp-status--success {
  background: rgba(21, 128, 61, 0.22) !important;
  color: #4ade80 !important;
  border: 1px solid rgba(74, 222, 128, 0.4) !important;
}

body.body--dark.admin-dark-mode .vp-status--neutral {
  background: rgba(100, 116, 139, 0.22) !important;
  color: #94a3b8 !important;
  border: 1px solid rgba(148, 163, 184, 0.3) !important;
}

body.body--dark.admin-dark-mode .vp-status--danger {
  background: rgba(220, 38, 38, 0.22) !important;
  color: #f87171 !important;
  border: 1px solid rgba(248, 113, 113, 0.4) !important;
}
</style>