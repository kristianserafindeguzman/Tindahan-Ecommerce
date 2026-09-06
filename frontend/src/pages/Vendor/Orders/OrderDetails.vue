<template>
  <!-- ================= EMBEDDED MODE (Used inside CustomerOrders.vue) ================= -->
  <div v-if="isEmbedded" class="embedded-order-details" :class="{ 'is-mobile-view': $q.screen.lt.md }">
    
    <!-- Top Bar: Back Button -->
    <div class="row items-center q-mb-md">
      <q-btn 
        flat 
        no-caps 
        dense 
        icon="arrow_back" 
        color="slate-800" 
        label="Back to Orders" 
        @click="$emit('back')" 
        class="text-weight-bold back-btn q-px-sm" 
      />
    </div>

    <!-- Loading State -->
    <div v-if="!order" class="flex flex-center q-pa-xl" style="min-height: 40vh;">
      <q-spinner-dots size="40px" color="red-9" />
    </div>

    <!-- Main Content Stack -->
    <div v-else class="embedded-content-stack">
      
      <!-- ================= HERO HEADER BANNER ================= -->
      <div class="header-gradient text-white q-pa-md q-pa-md-lg shadow-soft q-mb-lg" style="border-radius: 14px;">
        <div class="row items-center justify-between no-wrap q-col-gutter-md">
          <!-- Left Info Block: Header & Status Badge separated cleanly without overlap -->
          <div class="row items-center no-wrap col min-w-0">
            <div class="icon-box-white q-mr-md shadow-soft flex flex-center flex-shrink-0" :style="$q.screen.lt.md ? 'width: 40px; height: 40px; border-radius: 10px;' : 'width: 48px; height: 48px; border-radius: 12px;'">
              <q-icon name="receipt_long" :size="$q.screen.lt.md ? '22px' : '26px'" color="red-9" />
            </div>
            
            <div class="col min-w-0">
              <div class="embedded-banner-title-row q-mb-xs">
                <span class="order-id-title tracking-tight">
                  Order #{{ order.order_id }}
                </span>
                <q-chip 
                  size="sm" 
                  :color="getStatusColor(order.status)" 
                  text-color="white" 
                  class="text-weight-bolder shadow-1 q-ma-none status-badge-embedded"
                >
                  {{ formatStatus(order.status) }}
                </q-chip>
              </div>
              <div class="text-caption font-medium text-red-1 ellipsis" style="font-size: 13px; opacity: 0.95;">
                Placed {{ formatDate(order.created_at) }} • {{ order.consumer?.full_name || order.customer_name || 'Customer' }}
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="row items-center q-gutter-x-sm no-wrap flex-shrink-0">
            <q-btn 
              unelevated 
              icon="print" 
              label="Print Receipt" 
              text-color="red-9" 
              class="bg-white text-weight-bold q-px-md" 
              style="border-radius: 8px; font-size: 13px; height: 36px;"
              no-caps 
              :loading="isExporting" 
              @click="printOrder" 
            />

            <template v-if="order.status !== 'picked_up' && order.status !== 'cancelled' && order.status !== 'completed'">
              <q-btn-dropdown 
                :loading="isUpdating" 
                outline 
                color="white" 
                label="Update Status" 
                no-caps 
                class="text-weight-bold q-px-md"
                style="border-radius: 8px; font-size: 13px; height: 36px; background: rgba(255,255,255,0.12);"
              >
                <q-list class="premium-dropdown-list">
                  <q-item clickable v-close-popup @click="updateStatus('preparing')" v-if="['placed'].includes(order.status)" class="hover-grey">
                    <q-item-section avatar class="min-w-0 q-pr-sm"><q-icon name="inventory_2" color="purple-5" size="18px"/></q-item-section>
                    <q-item-section class="text-weight-medium">Pack / Prepare</q-item-section>
                  </q-item>
                  <q-item clickable v-close-popup @click="updateStatus('ready_for_pickup')" v-if="['placed', 'preparing'].includes(order.status)" class="hover-grey">
                    <q-item-section avatar class="min-w-0 q-pr-sm"><q-icon name="storefront" color="orange-6" size="18px"/></q-item-section>
                    <q-item-section class="text-weight-medium">Ready for Pickup</q-item-section>
                  </q-item>
                  <q-item clickable v-close-popup @click="updateStatus('picked_up')" v-if="['ready_for_pickup'].includes(order.status)" class="hover-grey">
                    <q-item-section avatar class="min-w-0 q-pr-sm"><q-icon name="check_circle" color="green-6" size="18px"/></q-item-section>
                    <q-item-section class="text-weight-medium">Picked up</q-item-section>
                  </q-item>
                  <q-separator class="q-my-xs" />
                  <q-item clickable v-close-popup @click="promptCancelOrder" v-if="!['picked_up', 'cancelled'].includes(order.status)" class="hover-red">
                    <q-item-section avatar class="min-w-0 q-pr-sm"><q-icon name="cancel" color="red-9" size="18px"/></q-item-section>
                    <q-item-section class="text-weight-bold text-red-9">Cancel Order</q-item-section>
                  </q-item>
                </q-list>
              </q-btn-dropdown>
            </template>
            <template v-else>
              <div class="finalized-indicator flex flex-center shadow-soft">
                <q-icon name="lock" size="18px" color="red-9" />
                <q-tooltip anchor="top middle" self="bottom middle" class="bg-dark text-caption">Order Finalized</q-tooltip>
              </div>
            </template>
          </div>
        </div>
      </div>

      <!-- Cancellation Alert Card -->
      <q-card v-if="order.status === 'cancelled'" flat class="cancellation-alert-card q-pa-md q-mb-lg">
        <div class="text-subtitle1 text-weight-bolder text-red-9 row items-center q-mb-xs">
          <q-icon name="cancel" size="20px" class="q-mr-xs" />
          Order Cancelled
        </div>
        <div class="text-body2 text-slate-700 font-medium">
          {{ order.cancellation_reason || 'No cancellation reason provided by the system.' }}
        </div>
      </q-card>

      <!-- ================= SECTION 1: TIMELINE & ORDER TRACKING ================= -->
      <div class="embedded-grid-row q-mb-xl items-start">
        <!-- Order Status Tracker: Height adjusts dynamically to content -->
        <div class="grid-col-timeline">
          <q-card flat class="clean-section-card timeline-card-responsive">
            <div class="clean-card-header row items-center justify-between q-pa-md">
              <div class="row items-center">
                <div class="accent-header-icon bg-red-50 text-brand-red q-mr-sm flex flex-center">
                  <q-icon name="timeline" size="18px" />
                </div>
                <span class="text-subtitle1 text-weight-bolder text-slate-800 tracking-tight">Order Status</span>
              </div>
            </div>

            <q-card-section class="q-pa-md q-pa-md-lg">
              <div class="custom-step-tracker" :class="{ 'compact-cancelled-tracker': order.status === 'cancelled' }">
                <!-- Placed -->
                <div class="tracker-item row items-start no-wrap">
                  <div class="tracker-badge-col column items-center q-mr-md">
                    <div class="status-check-circle check-active flex flex-center">
                      <q-icon name="check" size="14px" color="white" />
                    </div>
                    <div class="step-connector-line"></div>
                  </div>
                  <div class="tracker-content-col row items-center no-wrap col">
                    <div class="step-icon-box bg-red-50 text-red-9 q-mr-md flex flex-center">
                      <q-icon name="receipt" size="20px" />
                    </div>
                    <div>
                      <div class="text-weight-bolder text-slate-800 text-subtitle2">Placed</div>
                      <div class="text-caption text-slate-500 font-medium q-mt-xs">{{ formatDateHour(order.created_at) }}</div>
                    </div>
                  </div>
                </div>

                <!-- Cancelled Case (2-Step only, no trailing whitespace) -->
                <template v-if="order.status === 'cancelled'">
                  <div class="tracker-item row items-start no-wrap tracker-item-last">
                    <div class="tracker-badge-col column items-center q-mr-md">
                      <div class="status-check-circle check-cancelled flex flex-center">
                        <q-icon name="close" size="14px" color="white" />
                      </div>
                    </div>
                    <div class="tracker-content-col row items-center no-wrap col">
                      <div class="step-icon-box bg-red-50 text-red-9 q-mr-md flex flex-center">
                        <q-icon name="cancel" size="20px" />
                      </div>
                      <div>
                        <div class="text-weight-bolder text-slate-800 text-subtitle2">Order Cancelled</div>
                        <div class="text-caption text-slate-500 font-medium q-mt-xs">
                          {{ order.updated_at ? formatDateHour(order.updated_at) : 'Terminated' }}
                        </div>
                      </div>
                    </div>
                  </div>
                </template>

                <!-- Normal 4-Step Flow -->
                <template v-else>
                  <div class="tracker-item row items-start no-wrap">
                    <div class="tracker-badge-col column items-center q-mr-md">
                      <div class="status-check-circle flex flex-center" :class="{ 'check-active': isStatusActive('preparing') }">
                        <q-icon v-if="isStatusActive('preparing')" name="check" size="14px" color="white" />
                        <div v-else class="empty-dot"></div>
                      </div>
                      <div class="step-connector-line"></div>
                    </div>
                    <div class="tracker-content-col row items-center no-wrap col">
                      <div class="step-icon-box bg-orange-50 text-orange-9 q-mr-md flex flex-center">
                        <q-icon name="inventory_2" size="20px" />
                      </div>
                      <div>
                        <div class="text-weight-bolder text-slate-800 text-subtitle2">Preparing Order</div>
                        <div class="text-caption text-slate-500 font-medium q-mt-xs">
                          {{ isStatusActive('preparing') ? (order.preparing_at ? formatDateHour(order.preparing_at) : 'In Progress') : '--:--' }}
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="tracker-item row items-start no-wrap">
                    <div class="tracker-badge-col column items-center q-mr-md">
                      <div class="status-check-circle flex flex-center" :class="{ 'check-active': isStatusActive('ready_for_pickup') }">
                        <q-icon v-if="isStatusActive('ready_for_pickup')" name="check" size="14px" color="white" />
                        <div v-else class="empty-dot"></div>
                      </div>
                      <div class="step-connector-line"></div>
                    </div>
                    <div class="tracker-content-col row items-center no-wrap col">
                      <div class="step-icon-box bg-green-50 text-green-9 q-mr-md flex flex-center">
                        <q-icon name="directions_walk" size="20px" />
                      </div>
                      <div>
                        <div class="text-weight-bolder text-slate-800 text-subtitle2">Ready for Pickup</div>
                        <div class="text-caption text-slate-500 font-medium q-mt-xs">
                          {{ isStatusActive('ready_for_pickup') ? (order.ready_at ? formatDateHour(order.ready_at) : 'At Counter') : '--:--' }}
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="tracker-item row items-start no-wrap tracker-item-last">
                    <div class="tracker-badge-col column items-center q-mr-md">
                      <div class="status-check-circle flex flex-center" :class="{ 'check-active': isStatusActive('picked_up') }">
                        <q-icon v-if="isStatusActive('picked_up')" name="check" size="14px" color="white" />
                        <div v-else class="empty-dot"></div>
                      </div>
                    </div>
                    <div class="tracker-content-col row items-center no-wrap col">
                      <div class="step-icon-box bg-blue-grey-50 text-blue-grey-8 q-mr-md flex flex-center">
                        <q-icon name="archive" size="20px" />
                      </div>
                      <div>
                        <div class="text-weight-bolder text-slate-800 text-subtitle2">Picked up</div>
                        <div class="text-caption text-slate-500 font-medium q-mt-xs">
                          {{ isStatusActive('picked_up') ? formatDateHour(order.picked_up_at) : '--:--' }}
                        </div>
                      </div>
                    </div>
                  </div>
                </template>
              </div>
            </q-card-section>
          </q-card>
        </div>

        <!-- Order Tracking Map -->
        <div class="grid-col-map">
          <q-card flat class="clean-section-card overflow-hidden flex column">
            <div class="clean-card-header row items-center q-pa-md">
              <div class="row items-center">
                <div class="accent-header-icon bg-red-50 text-brand-red q-mr-sm flex flex-center">
                  <q-icon name="map" size="18px" />
                </div>
                <span class="text-subtitle1 text-weight-bolder text-slate-800 tracking-tight">Order Tracking</span>
              </div>
            </div>

            <div class="relative-position map-wrapper">
              <OrderTrackingMap 
                :storeLat="order.store?.latitude"
                :storeLng="order.store?.longitude"
                :consumerLat="order.consumer_latitude"
                :consumerLng="order.consumer_longitude"
                :storeName="order.store?.store_name"
                :consumerName="order.consumer?.full_name || order.customer_name || 'Customer'"
              />
            </div>
          </q-card>
        </div>
      </div>

      <!-- ================= SECTION 2: PURCHASED ITEMS & CUSTOMER / PICKUP ================= -->
      <div class="embedded-grid-row items-start">
        <!-- Purchased Items & Cost Breakdown -->
        <div class="grid-col-items">
          <q-card flat class="clean-section-card q-pa-md q-pa-md-lg">
            <div class="clean-card-header row items-center justify-between q-pb-md q-mb-md">
              <div class="row items-center">
                <div class="accent-header-icon bg-red-50 text-brand-red q-mr-sm flex flex-center">
                  <q-icon name="shopping_bag" size="18px" />
                </div>
                <span class="text-subtitle1 text-weight-bolder text-slate-800 tracking-tight">Purchased Items</span>
              </div>
              <q-badge color="red-50" text-color="red-9" class="text-weight-bold q-px-sm" style="font-size: 11px; border-radius: 6px;">
                {{ (order.items || []).length }} item(s)
              </q-badge>
            </div>

            <div class="column q-gutter-y-sm">
              <div 
                v-for="item in order.items" 
                :key="item.order_item_id" 
                class="row items-center justify-between no-wrap q-py-sm border-bottom-subtle"
              >
                <div class="row items-center no-wrap col min-w-0">
                  <q-avatar rounded size="48px" class="bg-slate-100 shadow-soft q-mr-md flex-shrink-0">
                    <img v-if="item.inventory?.image_url || item.image_url" :src="item.inventory?.image_url || item.image_url" style="object-fit: cover;" />
                    <q-icon v-else name="inventory_2" color="blue-grey-3" size="22px" />
                  </q-avatar>
                  <div class="col ellipsis">
                    <div class="text-weight-bold text-slate-800 text-body2 ellipsis">
                      {{ item.inventory?.product_name || item.product_name || 'Product' }}
                    </div>
                    <div class="text-caption text-slate-500 font-medium q-mt-xs">
                      {{ item.quantity }} Item{{ item.quantity > 1 ? 's' : '' }}
                    </div>
                  </div>
                </div>

                <div class="text-weight-bold text-slate-800 text-body2 q-pl-md flex-shrink-0">
                  PHP {{ formatNumber(item.subtotal || (item.price * item.quantity)) }}
                </div>
              </div>
            </div>

            <div class="border-dotted q-my-md"></div>

            <div class="row justify-between items-center text-body2 text-slate-600 q-mb-sm font-medium">
              <span>Subtotal</span>
              <span class="text-weight-bold text-slate-800">PHP {{ formatNumber(order.total_amount) }}</span>
            </div>

            <q-separator class="q-my-sm opacity-40" />

            <div class="row justify-between items-center">
              <span class="text-h6 text-weight-bolder text-brand-red">Total</span>
              <span class="text-h6 text-weight-bolder text-brand-red">PHP {{ formatNumber(order.total_amount) }}</span>
            </div>
          </q-card>
        </div>

        <!-- Customer Details & Store Location -->
        <div class="grid-col-sidebar column q-gutter-y-lg">
          <!-- Customer Info -->
          <q-card flat class="clean-section-card q-pa-md q-pa-md-lg">
            <div class="clean-card-header row items-center justify-between q-pb-md q-mb-md">
              <div class="row items-center">
                <div class="accent-header-icon bg-red-50 text-brand-red q-mr-sm flex flex-center">
                  <q-icon name="person" size="18px" />
                </div>
                <span class="text-subtitle1 text-weight-bolder text-slate-800 tracking-tight">Customer Info</span>
              </div>
              <q-badge color="red-50" text-color="red-9" class="text-weight-bold q-px-sm" style="font-size: 11px; border-radius: 6px;">
                {{ order.consumer?.total_orders || order.customer_orders_count || 1 }} Orders
              </q-badge>
            </div>

            <div class="row items-center q-mb-md no-wrap">
              <q-avatar size="46px" class="q-mr-md shadow-soft bg-slate-200 text-blue-grey-8 text-weight-bolder flex-shrink-0">
                <img v-if="order.consumer?.profile_picture_url" :src="order.consumer.profile_picture_url">
                <span v-else>{{ getInitials(order.consumer?.full_name || order.customer_name) }}</span>
              </q-avatar>
              <div class="col ellipsis">
                <div class="text-subtitle2 text-weight-bold text-slate-800 leading-tight ellipsis">
                  {{ order.consumer?.full_name || order.customer_name || 'Customer' }}
                </div>
                <div class="text-caption text-slate-400 font-medium q-mt-xs">Verified Customer</div>
              </div>
            </div>

            <div class="bg-slate-50 q-pa-md rounded-borders text-caption text-slate-700 font-medium column q-gutter-y-sm">
              <div class="row items-center no-wrap">
                <q-icon name="email" color="blue-grey-4" size="16px" class="q-mr-sm flex-shrink-0" />
                <span class="ellipsis">{{ order.consumer?.email || 'No email provided' }}</span>
              </div>
              <div class="row items-center no-wrap">
                <q-icon name="phone" color="blue-grey-4" size="16px" class="q-mr-sm flex-shrink-0" />
                <span>{{ order.consumer?.phone_number || order.customer_phone || 'No phone provided' }}</span>
              </div>
            </div>
          </q-card>

          <!-- Pickup Location -->
          <q-card flat class="clean-section-card q-pa-md q-pa-md-lg">
            <div class="clean-card-header row items-center justify-between q-pb-md q-mb-md">
              <div class="row items-center">
                <div class="accent-header-icon bg-red-50 text-brand-red q-mr-sm flex flex-center">
                  <q-icon name="storefront" size="18px" />
                </div>
                <span class="text-subtitle1 text-weight-bolder text-slate-800 tracking-tight">Pickup Location</span>
              </div>
            </div>

            <div class="row items-start no-wrap q-mb-md">
              <div class="bg-red-50 rounded-borders q-mr-md flex flex-center shrink-none" style="width: 36px; height: 36px;">
                <q-icon name="place" color="red-9" size="20px" />
              </div>
              <div class="col min-w-0">
                <div class="text-weight-bold text-slate-800 text-body2 leading-tight ellipsis">
                  {{ order.store?.store_name || 'Store Location' }}
                </div>
                <div class="text-slate-500 q-mt-xs text-caption font-medium leading-snug">
                  {{ order.store?.address || 'Store address' }}
                </div>
              </div>
            </div>

            <q-btn 
              outline 
              icon="directions" 
              label="Get Directions" 
              color="red-9" 
              class="full-width btn-glass-outline text-weight-bold" 
              no-caps 
              size="sm"
              :disable="!order.consumer_latitude || !order.store?.latitude"
              @click="openDirections" 
            />
          </q-card>
        </div>
      </div>
    </div>
  </div>

  <!-- ================= STANDALONE PAGE MODE (/vendor/orders/:id) ================= -->
  <q-page v-else class="vendor-page relative-position" :class="{ 'mobile-page-padding': $q.screen.lt.md }">
    <div class="bg-glow bg-glow-primary desktop-only"></div>
    <div class="bg-glow bg-glow-secondary desktop-only"></div>

    <div class="page-container relative-position" style="z-index: 1;" v-if="order">
      <!-- Breadcrumbs -->
      <div class="q-mb-md row items-center justify-between" :class="{ 'q-pt-sm': $q.screen.lt.md }">
        <q-breadcrumbs class="text-slate-500 text-weight-medium" active-color="dark">
          <q-breadcrumbs-el label="Order List" to="/vendor/orders/list" />
          <q-breadcrumbs-el label="Order Details" />
        </q-breadcrumbs>
      </div>

      <!-- Hero Header Desktop -->
      <div v-if="!$q.screen.lt.md" class="page-header q-mb-xl row items-center justify-between header-gradient text-white q-pa-lg shadow-4" style="border-radius: 16px;">
        <div class="row items-center">
          <div class="icon-box-white q-mr-lg shadow-1 flex flex-center">
            <q-icon name="receipt_long" size="32px" color="red-9" />
          </div>
          <div>
            <div class="row items-center q-mb-xs">
              <h1 class="text-h4 text-weight-bolder q-ma-none q-mr-md tracking-tight">Order #{{ order.order_id }}</h1>
              <q-chip 
                size="sm" 
                :color="getStatusColor(order.status)" 
                text-color="white" 
                class="text-weight-bolder shadow-1 q-px-md" 
                style="font-size: 13px; min-height: 26px;"
              >
                {{ formatStatus(order.status) }}
              </q-chip>
            </div>
            <div class="text-subtitle1 text-weight-medium" style="opacity: 0.85;">
              Placed {{ formatDate(order.created_at) }} <span class="q-mx-sm">•</span> {{ order.consumer?.full_name || 'Unknown Customer' }}
            </div>
          </div>
        </div>

        <div class="row q-gutter-md q-mt-md q-md-none">
          <q-btn unelevated icon="print" label="Print Receipt" text-color="red-9" class="bg-white text-weight-bold q-px-md btn-hero-action" no-caps :loading="isExporting" @click="printOrder" />
          <template v-if="order.status !== 'picked_up' && order.status !== 'cancelled' && order.status !== 'completed'">
            <q-btn-dropdown :loading="isUpdating" outline color="white" label="Update Status" no-caps class="text-weight-bold q-px-md btn-hero-outline">
              <q-list class="premium-dropdown-list">
                <q-item clickable v-close-popup @click="updateStatus('preparing')" v-if="['placed'].includes(order.status)" class="hover-grey">
                  <q-item-section avatar class="min-w-0 q-pr-sm"><q-icon name="inventory_2" color="purple-5" size="18px"/></q-item-section>
                  <q-item-section class="text-weight-medium">Pack / Prepare</q-item-section>
                </q-item>
                <q-item clickable v-close-popup @click="updateStatus('ready_for_pickup')" v-if="['placed', 'preparing'].includes(order.status)" class="hover-grey">
                  <q-item-section avatar class="min-w-0 q-pr-sm"><q-icon name="storefront" color="orange-6" size="18px"/></q-item-section>
                  <q-item-section class="text-weight-medium">Ready for Pickup</q-item-section>
                </q-item>
                <q-item clickable v-close-popup @click="updateStatus('picked_up')" v-if="['ready_for_pickup'].includes(order.status)" class="hover-grey">
                  <q-item-section avatar class="min-w-0 q-pr-sm"><q-icon name="check_circle" color="green-6" size="18px"/></q-item-section>
                  <q-item-section class="text-weight-medium">Picked up (Complete)</q-item-section>
                </q-item>
                <q-separator class="q-my-xs" />
                <q-item clickable v-close-popup @click="promptCancelOrder" v-if="!['picked_up', 'cancelled'].includes(order.status)" class="hover-red">
                  <q-item-section avatar class="min-w-0 q-pr-sm"><q-icon name="cancel" color="red-9" size="18px"/></q-item-section>
                  <q-item-section class="text-weight-bold text-red-9">Cancel Order</q-item-section>
                </q-item>
              </q-list>
            </q-btn-dropdown>
          </template>
          <template v-else>
            <div class="bg-white text-slate-700 text-weight-bold q-px-lg q-py-sm shadow-1" style="border-radius: 8px; font-size: 14px;">
              <q-icon name="lock" class="q-mr-xs" size="16px" /> Order Finalized
            </div>
          </template>
        </div>
      </div>

      <!-- Hero Header Mobile -->
      <q-card v-else flat class="q-mb-lg shadow-4 border-none header-gradient text-white" style="border-radius: 16px;">
        <q-card-section class="q-pa-md">
          <div class="row justify-between items-center q-mb-xs no-wrap">
            <h1 class="text-h6 text-weight-bolder q-ma-none tracking-tight">Order #{{ order.order_id }}</h1>
            <q-chip 
              :color="getStatusColor(order.status)" 
              text-color="white" 
              class="text-weight-bolder shadow-1 q-ma-none q-px-sm" 
              style="font-size: 11px; height: 24px; border: 1px solid rgba(255,255,255,0.4);"
            >
              {{ formatStatus(order.status) }}
            </q-chip>
          </div>
          <div class="text-caption text-weight-medium q-mb-sm" style="opacity: 0.85;">Placed {{ formatDate(order.created_at) }}</div>
          
          <q-separator color="white" style="opacity: 0.25;" class="q-my-md" />
          
          <div class="row items-center q-mb-md">
            <q-avatar size="32px" class="q-mr-sm bg-white text-red-9 shadow-soft">
              <img v-if="order.consumer?.profile_picture_url" :src="order.consumer.profile_picture_url">
              <q-icon v-else name="person" size="18px" />
            </q-avatar>
            <div class="text-body2 text-weight-bold">{{ order.consumer?.full_name || 'Unknown Customer' }}</div>
          </div>

          <div class="row q-col-gutter-sm">
            <div class="col-6">
              <q-btn unelevated icon="print" label="Print Receipt" text-color="red-9" class="bg-white text-weight-bold full-width" style="border-radius: 8px;" no-caps size="sm" :loading="isExporting" @click="printOrder" />
            </div>
            <div class="col-6">
              <template v-if="order.status !== 'picked_up' && order.status !== 'cancelled' && order.status !== 'completed'">
                <q-btn-dropdown :loading="isUpdating" outline color="white" label="Update Status" no-caps size="sm" class="text-weight-bold full-width" style="border-radius: 8px; background: rgba(255,255,255,0.1);">
                  <q-list class="premium-dropdown-list">
                    <q-item clickable v-close-popup @click="updateStatus('preparing')" v-if="['placed'].includes(order.status)" class="hover-grey">
                      <q-item-section avatar class="min-w-0 q-pr-sm"><q-icon name="inventory_2" color="purple-5" size="18px"/></q-item-section>
                      <q-item-section class="text-weight-medium">Pack / Prepare</q-item-section>
                    </q-item>
                    <q-item clickable v-close-popup @click="updateStatus('ready_for_pickup')" v-if="['placed', 'preparing'].includes(order.status)" class="hover-grey">
                      <q-item-section avatar class="min-w-0 q-pr-sm"><q-icon name="storefront" color="orange-6" size="18px"/></q-item-section>
                      <q-item-section class="text-weight-medium">Ready for Pickup</q-item-section>
                    </q-item>
                    <q-item clickable v-close-popup @click="updateStatus('picked_up')" v-if="['ready_for_pickup'].includes(order.status)" class="hover-grey">
                      <q-item-section avatar class="min-w-0 q-pr-sm"><q-icon name="check_circle" color="green-6" size="18px"/></q-item-section>
                      <q-item-section class="text-weight-medium">Picked up</q-item-section>
                    </q-item>
                    <q-separator class="q-my-xs" />
                    <q-item clickable v-close-popup @click="promptCancelOrder" v-if="!['picked_up', 'cancelled'].includes(order.status)" class="hover-red">
                      <q-item-section avatar class="min-w-0 q-pr-sm"><q-icon name="cancel" color="red-9" size="18px"/></q-item-section>
                      <q-item-section class="text-weight-bold text-red-9">Cancel Order</q-item-section>
                    </q-item>
                  </q-list>
                </q-btn-dropdown>
              </template>
              <template v-else>
                <div class="bg-white text-slate-700 text-weight-bold flex flex-center shadow-1" style="border-radius: 8px; font-size: 11px; height: 100%; min-height: 32px;">
                  <q-icon name="lock" class="q-mr-xs" size="12px" /> Order Finalized
                </div>
              </template>
            </div>
          </div>
        </q-card-section>
      </q-card>

      <!-- Standalone Grid (12-col Layout) -->
      <div class="row q-col-gutter-lg q-col-gutter-md-xl">
        <div class="col-12 col-md-8">
          <!-- Timeline & Map Row -->
          <div class="row q-col-gutter-lg q-mb-lg q-mb-md-xl">
            <!-- Timeline -->
            <div class="col-12 col-md-6">
              <q-card class="premium-glass-card h-full">
                <q-card-section class="q-pa-md q-pa-md-lg">
                  <div class="text-subtitle1 text-md-h6 text-weight-bold text-dark q-mb-lg row items-center">
                    <div class="header-accent-red q-mr-md"></div>
                    Order Timeline
                  </div>
                  <q-timeline color="red-9" class="q-px-xs q-px-md-sm">
                    <q-timeline-entry title="Order Placed" :subtitle="formatDate(order.created_at)" icon="shopping_cart" />
                    <template v-if="order.status === 'cancelled'">
                      <q-timeline-entry title="Order Cancelled" :subtitle="order.updated_at ? formatDate(order.updated_at) : 'Terminated'" icon="cancel" color="red-7" />
                    </template>
                    <template v-else>
                      <q-timeline-entry title="Preparing & Packing" :subtitle="isStatusActive('preparing') ? 'In Progress' : 'Pending'" icon="inventory_2" :color="isStatusActive('preparing') ? 'purple-5' : 'grey-4'" />
                      <q-timeline-entry title="Ready for Pickup" :subtitle="isStatusActive('ready_for_pickup') ? 'At Counter' : 'Pending'" icon="storefront" :color="isStatusActive('ready_for_pickup') ? 'orange-6' : 'grey-4'" />
                      <q-timeline-entry title="Picked up" :subtitle="isStatusActive('picked_up') ? 'Completed' : 'Pending'" icon="task_alt" :color="order.status === 'picked_up' ? 'green-6' : 'grey-4'" />
                    </template>
                  </q-timeline>
                </q-card-section>
              </q-card>
            </div>

            <!-- Tracking Map -->
            <div class="col-12 col-md-6">
              <q-card class="premium-glass-card h-full overflow-hidden border-slate-light" style="min-height: 250px; padding: 0;">
                <OrderTrackingMap 
                  :storeLat="order.store?.latitude"
                  :storeLng="order.store?.longitude"
                  :consumerLat="order.consumer_latitude"
                  :consumerLng="order.consumer_longitude"
                  :storeName="order.store?.store_name"
                  :consumerName="order.consumer?.full_name || 'Customer'"
                />
              </q-card>
            </div>
          </div>

          <!-- Items Card -->
          <q-card class="premium-glass-card shadow-4 q-mb-lg q-mb-md-none">
            <q-card-section class="q-pa-md q-pa-md-lg border-bottom bg-white" style="border-radius: 16px 16px 0 0;">
              <div class="text-subtitle1 text-md-h6 text-weight-bold text-dark row items-center">
                <div class="header-accent-red q-mr-md"></div>
                Purchased Items
              </div>
            </q-card-section>

            <q-list separator class="q-px-sm q-px-md-md q-py-xs bg-white">
              <q-item v-for="item in order.items" :key="item.order_item_id" class="q-py-md q-py-md-lg">
                <q-item-section avatar class="q-pr-sm q-pr-md-md">
                  <q-avatar rounded size="48px" class="bg-slate-100 shadow-soft">
                    <img v-if="item.inventory?.image_url" :src="item.inventory.image_url" style="object-fit: cover;" />
                    <q-icon v-else name="inventory_2" color="blue-grey-3" size="24px" />
                  </q-avatar>
                </q-item-section>
                <q-item-section>
                  <q-item-label class="text-weight-bold text-subtitle2 text-md-subtitle1 text-slate-800">{{ item.inventory?.product_name || 'Product' }}</q-item-label>
                  <q-item-label caption class="text-blue-grey-5 font-medium q-mt-xs" style="font-size: 11px;">
                    ₱{{ formatNumber(item.subtotal / item.quantity) }} per item
                  </q-item-label>
                </q-item-section>
                <q-item-section side>
                  <div class="text-weight-bold text-slate-600 bg-slate-100 q-px-sm q-py-xs" style="border-radius: 6px; font-size: 11px;">
                    Qty: {{ item.quantity }}
                  </div>
                </q-item-section>
                <q-item-section side class="q-pl-sm q-pl-md-lg">
                  <div class="text-subtitle2 text-md-h6 text-weight-bold text-slate-800">₱{{ formatNumber(item.subtotal) }}</div>
                </q-item-section>
              </q-item>
            </q-list>

            <q-card-section class="q-pa-md q-pa-md-lg bg-grey-1" style="border-radius: 0 0 16px 16px; border-top: 1px solid #e2e8f0;">
              <div class="row justify-end">
                <div class="col-12 col-sm-6 col-md-5">
                  <div class="row justify-between q-mb-sm text-slate-600 font-medium text-body2">
                    <div>Subtotal</div>
                    <div class="text-weight-bold text-slate-800">₱{{ formatNumber(order.total_amount) }}</div>
                  </div>
                  <div class="row justify-between q-mb-md text-slate-600 font-medium text-body2">
                    <div>Platform Fee</div>
                    <div class="text-weight-bold text-slate-800">₱{{ formatNumber(order.platform_fee || 0) }}</div>
                  </div>
                  <div class="border-dotted q-my-sm q-my-md-md"></div>
                  <div class="row justify-between items-end text-dark q-mt-sm">
                    <div class="text-subtitle2 text-md-subtitle1 text-weight-bold">Total</div>
                    <div class="text-h5 text-md-h4 text-weight-bolder text-brand-red tracking-tight">₱{{ formatNumber(order.total_amount) }}</div>
                  </div>
                </div>
              </div>
            </q-card-section>
          </q-card>
        </div>

        <!-- Right Column: Customer Info & Pickup Address -->
        <div class="col-12 col-md-4">
          <!-- Customer Info -->
          <q-card class="premium-glass-card q-mb-lg">
            <q-card-section class="q-pa-md q-pa-md-lg">
              <div class="text-subtitle1 text-md-h6 text-weight-bold text-dark q-mb-lg row items-center">
                <div class="header-accent-red q-mr-md"></div>
                Customer Info
              </div>

              <div class="row items-center q-mb-lg">
                <q-avatar size="48px" class="q-mr-md shadow-soft bg-slate-200 text-blue-grey-8 text-weight-bolder text-subtitle1" style="border: 2px solid #fff;">
                  <img v-if="order.consumer?.profile_picture_url" :src="order.consumer.profile_picture_url">
                  <span v-else>{{ getInitials(order.consumer?.full_name) }}</span>
                </q-avatar>
                <div>
                  <div class="text-subtitle2 text-md-h6 text-weight-bold text-slate-800 leading-tight">{{ order.consumer?.full_name || 'Unknown' }}</div>
                  <q-badge color="red-50" text-color="red-9" class="text-weight-bold q-pa-xs q-px-sm q-mt-xs border-red-light" style="font-size: 10px; letter-spacing: 0.5px;">
                    <q-icon name="shopping_bag" size="12px" class="q-mr-xs" /> {{ order.consumer?.total_orders || 1 }} Orders
                  </q-badge>
                </div>
              </div>

              <div class="bg-slate-100 q-pa-sm q-pa-md-md border-radius-12 border-slate-light">
                <div class="row items-center q-mb-sm">
                  <q-icon name="email" color="blue-grey-4" size="18px" class="q-mr-md" />
                  <div class="text-slate-700 font-medium text-caption text-md-body2" style="word-break: break-all;">{{ order.consumer?.email || 'No email provided' }}</div>
                </div>
                <div class="row items-center">
                  <q-icon name="phone" color="blue-grey-4" size="18px" class="q-mr-md" />
                  <div class="text-slate-700 font-medium text-caption text-md-body2">{{ order.consumer?.phone_number || 'No phone provided' }}</div>
                </div>
              </div>
            </q-card-section>
          </q-card>

          <!-- Pickup Location -->
          <q-card class="premium-glass-card">
            <q-card-section class="q-pa-md q-pa-md-lg">
              <div class="text-subtitle1 text-md-h6 text-weight-bold text-dark q-mb-lg row items-center">
                <div class="header-accent-red q-mr-md"></div>
                Pickup Location
              </div>

              <div class="row items-start q-mb-md q-mb-md-lg no-wrap">
                <div class="bg-red-50 q-pa-sm rounded-borders q-mr-md border-red-light shrink-none">
                  <q-icon name="storefront" color="red-9" size="20px" />
                </div>
                <div class="col">
                  <div class="text-subtitle2 text-md-subtitle1 text-weight-bold text-slate-800 leading-tight">{{ order.store?.store_name }}</div>
                  <div class="text-blue-grey-5 q-mt-xs text-caption font-medium leading-tight">{{ order.store?.address }}</div>
                </div>
              </div>

              <q-btn 
                outline 
                icon="directions" 
                label="Get Directions" 
                color="blue-grey-8" 
                class="full-width btn-glass-outline text-weight-bold" 
                no-caps 
                :size="$q.screen.lt.md ? 'sm' : 'md'"
                :disable="!order.consumer_latitude || !order.store?.latitude"
                @click="openDirections"
              />
            </q-card-section>
          </q-card>
        </div>
      </div>
    </div>

    <!-- Standalone Loading State -->
    <div v-else class="flex flex-center full-height" style="min-height: 60vh;">
      <q-spinner-dots size="50px" color="red-9" />
    </div>
  </q-page>

  <!-- Shared Cancellation Dialog -->
  <q-dialog v-model="showCancelDialog" persistent>
    <q-card style="width: 400px; max-width: 90vw; border-radius: 14px;" class="bg-white shadow-soft">
      <q-card-section class="q-pa-md bg-red-50 border-bottom-red">
        <div class="text-subtitle1 text-weight-bolder text-red-9 row items-center">
          <q-icon name="warning" size="20px" class="q-mr-xs" />
          Cancel Order
        </div>
      </q-card-section>

      <q-card-section class="q-pa-md">
        <div class="text-caption text-slate-600 q-mb-sm">Specify reason for cancelling this order. Action cannot be undone.</div>
        <q-checkbox v-model="cancelReasonOutOfStock" label="Item(s) out of stock" class="q-mb-sm text-slate-800 font-medium" color="red-9" dense />
        <q-input
          v-model="cancelReasonText"
          type="textarea"
          label="Details (Required)"
          outlined
          dense
          color="red-9"
          autofocus
          rows="2"
          :rules="[val => !!val || 'Reason is required']"
          class="custom-glass-input"
        />
      </q-card-section>

      <q-card-actions align="right" class="q-pa-md bg-slate-50 border-top-light q-gutter-xs">
        <q-btn flat label="Back" color="blue-grey-6" v-close-popup no-caps class="text-weight-bold" size="sm" />
        <q-btn unelevated label="Confirm Cancellation" color="red-9" @click="confirmCancelOrder" :loading="isUpdating" no-caps class="text-weight-bold q-px-sm" size="sm" style="border-radius: 6px;" />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api } from '@/boot/axios'
import { useQuasar } from 'quasar'
import OrderTrackingMap from '@/components/shared/OrderTrackingMap.vue'

const props = defineProps({
  orderId: { type: [String, Number], default: null },
  isEmbedded: { type: Boolean, default: false }
})

const emit = defineEmits(['back'])

const route = useRoute()
const router = useRouter()
const $q = useQuasar()
const order = ref(null)
const isUpdating = ref(false)
const isExporting = ref(false)

const showCancelDialog = ref(false)
const cancelReasonOutOfStock = ref(false)
const cancelReasonText = ref('')

watch(cancelReasonOutOfStock, (val) => {
  if (val) {
    cancelReasonText.value = 'Item out of stock'
  } else if (cancelReasonText.value === 'Item out of stock') {
    cancelReasonText.value = ''
  }
})

const promptCancelOrder = () => {
  cancelReasonOutOfStock.value = false
  cancelReasonText.value = ''
  showCancelDialog.value = true
}

const printOrder = async () => {
  if (!order.value) return

  try {
    isExporting.value = true
    const response = await api.get(`/vendor/orders/${order.value.order_id}/export`, { responseType: 'blob' })
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `Tindahan-Customer-Order-#${order.value.order_id}.pdf`)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
  } catch (error) {
    console.error('Print failed:', error)
    $q.notify({ type: 'negative', message: 'Failed to generate print document' })
  } finally {
    isExporting.value = false
  }
}

const confirmCancelOrder = async () => {
  if (!cancelReasonText.value) {
    $q.notify({ type: 'warning', message: 'Please provide a cancellation reason.' })
    return
  }
  updateStatus('cancelled', cancelReasonText.value)
}

const getStatusColor = (status) => {
  switch (String(status).toLowerCase()) {
    case 'placed': return 'blue-6'
    case 'preparing': return 'amber-7'
    case 'ready_for_pickup': return 'orange-5'
    case 'picked_up': return 'green-6'
    case 'cancelled': return 'red-6'
    default: return 'grey-6'
  }
}

const formatStatus = (status) => {
  if (!status) return ''
  return status.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ')
}

const formatNumber = (num) => Number(num || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
const formatDate = (dateString) => {
  if (!dateString) return ''
  const d = new Date(dateString)
  return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

const formatDateHour = (dateString) => {
  if (!dateString) return ''
  const d = new Date(dateString)
  return d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })
}

const getInitials = (name) => {
  if (!name) return '?'
  const parts = name.trim().split(' ')
  if (parts.length === 1) return parts[0].charAt(0).toUpperCase()
  return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase()
}

const isStatusActive = (step) => {
  const flow = ['placed', 'preparing', 'ready_for_pickup', 'picked_up']
  const currentIndex = flow.indexOf(order.value?.status)
  const stepIndex = flow.indexOf(step)
  return currentIndex >= stepIndex
}

const updateStatus = async (newStatus, reason = null) => {
  if (order.value) {
    try {
      isUpdating.value = true
      const payload = { status: newStatus }
      if (reason) payload.cancellation_reason = reason

      const res = await api.patch(`/vendor/orders/${order.value.order_id}/status`, payload)
      order.value.status = res.data.order.status
      if (res.data.order.cancellation_reason) {
        order.value.cancellation_reason = res.data.order.cancellation_reason
      }
      $q.notify({ type: 'positive', message: `Order status updated to ${formatStatus(newStatus)}` })
      showCancelDialog.value = false
    } catch (err) {
      console.error(err.response?.data || err)
      const msg = err.response?.data?.message || err.message || "Unknown error occurred"
      $q.notify({ type: 'negative', message: msg })
    } finally {
      isUpdating.value = false
    }
  }
}

const openDirections = () => {
  if (!order.value) return
  const oLat = order.value.consumer_latitude
  const oLng = order.value.consumer_longitude
  const dLat = order.value.store?.latitude
  const dLng = order.value.store?.longitude

  if (oLat && oLng && dLat && dLng) {
    window.open(`https://www.google.com/maps/dir/?api=1&origin=${oLat},${oLng}&destination=${dLat},${dLng}`, '_blank')
  }
}

const fetchOrderDetails = async () => {
  const id = props.orderId || route.params.id
  if (!id) return

  try {
    const res = await api.get(`/vendor/orders/${id}`)
    order.value = res.data
  } catch (error) {
    console.error('Failed to load order details', error)
  }
}

watch(() => props.orderId, (newId) => {
  if (newId) {
    order.value = null
    fetchOrderDetails()
  }
})

onMounted(() => {
  fetchOrderDetails()
})
</script>

<style scoped>
/* Embedded Scope */
.embedded-order-details {
  animation: fadeIn 0.25s ease-in-out;
  width: 100%;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(4px); }
  to { opacity: 1; transform: translateY(0); }
}

.back-btn {
  border-radius: 8px;
  transition: all 0.2s ease;
}
.back-btn:hover {
  background: #f1f5f9;
}

/* Header row layout with no overlapping */
.embedded-banner-title-row {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 10px;
}

.order-id-title {
  font-size: 20px;
  font-weight: 800;
  line-height: 1.2;
  white-space: nowrap;
}

.status-badge-embedded {
  border-radius: 6px;
  padding: 4px 10px;
  font-size: 11px;
}

/* Explicit Flex Grid for Embedded View */
.embedded-grid-row {
  display: flex;
  flex-wrap: wrap;
  column-gap: 20px;
  row-gap: 20px;
}

.grid-col-timeline {
  flex: 0 0 calc(41.6666% - 10px);
  max-width: calc(41.6666% - 10px);
}

.grid-col-map {
  flex: 0 0 calc(58.3333% - 10px);
  max-width: calc(58.3333% - 10px);
}

.grid-col-items {
  flex: 0 0 calc(58.3333% - 10px);
  max-width: calc(58.3333% - 10px);
}

.grid-col-sidebar {
  flex: 0 0 calc(41.6666% - 10px);
  max-width: calc(41.6666% - 10px);
}

/* Responsive adjustment: Timeline hugs content height naturally */
.timeline-card-responsive {
  height: fit-content !important;
}

/* Embedded Mobile Collapse */
@media (max-width: 1023px) {
  .embedded-grid-row {
    flex-direction: column;
    gap: 16px;
  }
  .grid-col-timeline,
  .grid-col-map,
  .grid-col-items,
  .grid-col-sidebar {
    flex: 0 0 100% !important;
    max-width: 100% !important;
  }
  .map-wrapper {
    height: 220px !important;
    min-height: 220px !important;
  }
  .order-id-title {
    font-size: 17px;
  }
}

/* Clean Cards with Subtle Elevation */
.clean-section-card {
  background: #ffffff;
  border-radius: 14px;
  border: 1px solid rgba(226, 232, 240, 0.6);
  box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
}

.clean-card-header {
  border-bottom: 1px solid #f8fafc;
}

.accent-header-icon {
  width: 30px;
  height: 30px;
  border-radius: 8px;
}

/* Step Tracker */
.custom-step-tracker {
  display: flex;
  flex-direction: column;
}

.tracker-item {
  position: relative;
  display: flex;
  align-items: center;
  margin-bottom: 24px;
}

/* Natural compact spacing for cancelled state */
.compact-cancelled-tracker .tracker-item {
  margin-bottom: 20px;
}

.tracker-item-last {
  margin-bottom: 0 !important;
}

.tracker-badge-col {
  width: 24px;
  position: relative;
  display: flex;
  justify-content: center;
}

.status-check-circle {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background: #f8fafc;
  border: 2px solid #cbd5e1;
  transition: all 0.2s ease;
  z-index: 2;
}

.status-check-circle.check-active {
  background: #10b981;
  border-color: #10b981;
}

.status-check-circle.check-cancelled {
  background: #ef4444;
  border-color: #ef4444;
}

.empty-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #cbd5e1;
}

.step-connector-line {
  position: absolute;
  top: 24px;
  bottom: -24px;
  width: 2px;
  background: #e2e8f0;
  z-index: 1;
}

.compact-cancelled-tracker .step-connector-line {
  bottom: -20px;
}

.step-icon-box {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  flex-shrink: 0;
}

.map-wrapper {
  height: 280px;
  border-bottom-left-radius: 14px;
  border-bottom-right-radius: 14px;
  overflow: hidden;
}

.bg-orange-50 { background-color: #fff7ed !important; }
.text-orange-9 { color: #c2410c !important; }
.bg-green-50 { background-color: #f0fdf4 !important; }
.text-green-9 { color: #15803d !important; }
.bg-blue-grey-50 { background-color: #f8fafc !important; }

.finalized-indicator {
  width: 36px;
  height: 36px;
  background-color: #ffffff;
  border-radius: 8px;
  cursor: default;
}

.cancellation-alert-card {
  background-color: #fef2f2;
  border: 1px solid #fee2e2;
  border-radius: 12px;
}

/* ================= STANDALONE PAGE STYLES (/vendor/orders/:id) ================= */
.vendor-page {
  padding: 32px 24px;
  background-color: #f8fafc;
  min-height: 100vh;
}
.page-container {
  max-width: 1400px;
  margin: 0 auto;
}
.shrink-none, .flex-shrink-0 { flex-shrink: 0; }
.min-w-0 { min-width: 0 !important; }

/* Header & Accent Colors */
.text-brand-red { color: #b91c1c !important; }
.header-gradient { 
  background: linear-gradient(135deg, #b91c1c 0%, #7f1d1d 100%); 
}
.bg-red-50 { background-color: #fef2f2 !important; }
.border-red-light { border: 1px solid #fca5a5 !important; }
.border-bottom-red { border-bottom: 1px solid #fecaca !important; }

.bg-slate-50 { background-color: #f8fafc; }
.bg-slate-100 { background-color: #f1f5f9; }
.bg-slate-200 { background-color: #e2e8f0; }
.text-slate-400 { color: #94a3b8; }
.text-slate-500 { color: #64748b; }
.text-slate-600 { color: #475569; }
.text-slate-700 { color: #334155; }
.text-slate-800 { color: #1e293b; }
.border-slate-light { border: 1px solid #e2e8f0; }

/* Subtle Ambient Glows */
.bg-glow {
  position: absolute;
  width: 500px;
  height: 500px;
  border-radius: 50%;
  filter: blur(140px);
  z-index: 0;
  opacity: 0.15; 
  pointer-events: none;
}
.bg-glow-primary {
  top: -50px;
  left: -50px;
  background: radial-gradient(circle, rgba(185, 28, 28, 0.25) 0%, transparent 70%); 
}
.bg-glow-secondary {
  bottom: 100px;
  right: -50px;
  background: radial-gradient(circle, rgba(69, 10, 10, 0.25) 0%, transparent 70%); 
}

.tracking-tight { letter-spacing: -0.02em; }
.leading-tight { line-height: 1.2; }
.font-medium { font-weight: 500; }

.icon-box-white {
  width: 56px;
  height: 56px;
  background: rgba(255, 255, 255, 0.95);
  border-radius: 14px;
}

.premium-glass-card {
  background: rgba(255, 255, 255, 0.98);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border: 1px solid rgba(241, 245, 249, 1);
  box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04); 
  border-radius: 16px;
}

.btn-hero-action {
  border-radius: 8px;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.btn-hero-action:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}
.btn-hero-outline {
  border-radius: 8px;
  transition: all 0.2s ease;
  background: rgba(255,255,255,0.1);
}
.btn-hero-outline:hover {
  background: rgba(255,255,255,0.2);
}

.btn-glass-outline {
  border-radius: 8px !important;
  background: #ffffff !important;
  border: 1px solid rgba(203, 213, 225, 0.8);
  transition: all 0.2s ease;
  height: 36px;
}
.btn-glass-outline:hover {
  box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05);
  transform: translateY(-1px);
}

.premium-dropdown-list {
  background: rgba(255, 255, 255, 0.98);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(226, 232, 240, 0.8);
  border-radius: 8px;
  min-width: 200px;
}
.hover-grey:hover { background: rgba(241, 245, 249, 0.8); }
.hover-red:hover { background: rgba(254, 242, 242, 0.8); }

.border-bottom { border-bottom: 1px solid rgba(226, 232, 240, 0.8); }
.border-top-light { border-top: 1px solid #e2e8f0; }
.border-dotted { border-bottom: 2px dotted #cbd5e1; }
.border-radius-12 { border-radius: 12px; }
.shadow-soft { box-shadow: 0 2px 8px rgba(15,23,42,0.06); }
.h-full { height: 100%; }

.header-accent-red {
  width: 4px;
  height: 18px;
  background: #b91c1c;
  border-radius: 4px;
  flex-shrink: 0;
}

.custom-glass-input :deep(.q-field__control) {
  border-radius: 8px;
  background-color: #ffffff;
}
.custom-glass-input :deep(.q-field__control:before) { border: 1px solid #e2e8f0; }
.custom-glass-input :deep(.q-field--focused .q-field__control) {
  box-shadow: 0 0 0 1px rgba(185, 28, 28, 0.15); 
  border-color: #b91c1c;
}

/* Standalone Timeline Customization */
:deep(.q-timeline__title) {
  font-size: 15px;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 2px;
}
:deep(.q-timeline__subtitle) {
  font-size: 13px;
  font-weight: 500;
  color: #64748b;
  margin-bottom: 20px;
}
:deep(.q-timeline__dot) {
  filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
}

@media (max-width: 767px) {
  .vendor-page.mobile-page-padding { 
    padding: 16px 12px 24px 12px !important; 
  }
  .desktop-only { display: none !important; }
}
</style>