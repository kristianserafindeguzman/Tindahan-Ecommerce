<template>
  <q-page class="vendor-page" :class="{ 'mobile-page-padding': $q.screen.lt.md }">
    <!-- Decorative Ambient Background Glows -->
    <div class="bg-glow bg-glow-primary"></div>
    <div class="bg-glow bg-glow-secondary"></div>

    <!-- Warm Glassmorphic Sari-Sari Store Loading Screen -->
    <transition name="fade-fast">
      <div v-if="checkingAccess" class="checking-access absolute-full z-max flex flex-center glass-backdrop">
        <div class="bg-glow bg-glow-primary pulse-bg-glow" style="opacity: 0.4;"></div>
        
        <div class="loader-glass-card column flex-center shadow-soft">
          <!-- Animated Sari-Sari Hub with Floating Retail Motifs -->
          <div class="store-icon-wrapper relative-position flex flex-center q-mb-md">
            <div class="soft-glow-ring"></div>
            
            <span class="floating-particle p-1">
              <q-icon name="local_mall" size="22px" color="amber-8" />
            </span>
            <span class="floating-particle p-2">
              <span class="peso-coin shadow-soft">₱</span>
            </span>
            <span class="floating-particle p-3">
              <q-icon name="receipt_long" size="22px" color="blue-7" />
            </span>

            <div class="store-avatar-box shadow-3 flex flex-center">
              <q-icon name="storefront" size="44px" class="storefront-icon-anim" />
              <div class="store-awning-bar"></div>
            </div>
          </div>
          
          <!-- Context-Aware Dynamic Messages -->
          <div class="text-h6 text-weight-bolder text-blue-grey-9 tracking-wide q-mt-sm row items-center no-wrap">
            <span>{{ loadingTitle }}</span>
            <span class="loading-dots"></span>
          </div>
          <div class="text-caption text-blue-grey-6 q-mt-xs text-weight-medium text-center">
            {{ loadingSubtitle }}
          </div>

          <div class="sari-loading-bar-track q-mt-md">
            <div class="sari-loading-bar-fill"></div>
          </div>
        </div>
      </div>
    </transition>

    <div v-show="!checkingAccess" class="page-container">
      
      <!-- ========================================================= -->
      <!-- ==================== DESKTOP LAYOUT ===================== -->
      <!-- ========================================================= -->
      <div v-if="!$q.screen.lt.md" class="desktop-layout">
        
        <!-- ================= DYNAMIC TIME-SYNCED HEADER ================= -->
        <div class="welcome-banner q-mb-lg q-pa-lg row items-center justify-between transition-theme card-rounded" :class="headerThemeClass">
          <!-- Left: Date, Greeting, Subtitle -->
          <div class="col-12 col-md-7 col-lg-7">
            <div class="row items-center q-mb-xs">
              <q-icon name="today" size="16px" :class="subTextClass" class="q-mr-xs opacity-80" />
              <span class="text-caption text-weight-bold tracking-wide text-uppercase" :class="subTextClass">
                {{ currentDate }}
              </span>
            </div>
            <h1 class="text-h3 text-weight-bolder q-ma-none header-title" :class="headerTextClass" style="line-height: 1.15;">
              {{ timeGreeting }},
              <span class="text-weight-black">{{ userName }}</span>
            </h1>
            <p class="text-subtitle1 q-mt-xs q-mb-none opacity-80" :class="subTextClass">
              Here's what's happening with your neighborhood store today.
            </p>
          </div>

          <!-- Right: Unified Store Control Capsule -->
          <div class="col-12 col-md-5 col-lg-5 flex justify-end items-center">
            <div class="unified-store-capsule row items-center no-wrap shadow-soft" :class="capsuleThemeClass">
              <div class="store-status-section row items-center no-wrap q-px-md q-py-sm">
                <span class="status-pulse-wrapper q-mr-sm">
                  <span class="status-pulse-ring" :class="isStoreOpen ? 'pulse-open' : 'pulse-closed'"></span>
                  <span class="status-pulse-core" :class="isStoreOpen ? 'core-open' : 'core-closed'"></span>
                </span>
                <span class="text-weight-bold text-caption status-text no-wrap" :class="capsuleTextClass">
                  {{ isStoreOpen ? 'Store is Open' : 'Store is Closed' }}
                </span>
              </div>

              <div class="capsule-divider"></div>

              <button type="button" class="capsule-action-btn row items-center no-wrap cursor-pointer" @click="liveStoreModal = true">
                <q-icon name="storefront" size="18px" class="q-mr-xs action-icon" />
                <span class="text-weight-bold text-caption">Live Store</span>
              </button>
            </div>
          </div>
        </div>

        <!-- ================= TOP METRICS ================= -->
        <div class="row q-col-gutter-md q-col-gutter-lg-lg q-mb-xl">
          <div class="col-6 col-md-3">
            <q-card class="clean-solid-card card-hover h-full">
              <q-card-section class="q-pa-md q-pa-md-lg">
                <div class="row items-center justify-between q-mb-sm">
                  <div class="text-caption text-weight-bold text-blue-9 text-uppercase tracking-wide line-height-tight">Placed<br class="mobile-only"> Orders</div>
                  <div class="icon-premium-box bg-blue-1 text-blue-7 shadow-soft">
                    <q-icon name="shopping_cart_checkout" size="20px" />
                  </div>
                </div>
                <div class="text-h4 text-md-h3 text-weight-bolder text-dark">{{ stats.placed_orders }}</div>
              </q-card-section>
            </q-card>
          </div>

          <div class="col-6 col-md-3">
            <q-card class="clean-solid-card card-hover h-full">
              <q-card-section class="q-pa-md q-pa-md-lg">
                <div class="row items-center justify-between q-mb-sm">
                  <div class="text-caption text-weight-bold text-amber-9 text-uppercase tracking-wide line-height-tight">Preparing</div>
                  <div class="icon-premium-box bg-amber-1 text-amber-7 shadow-soft">
                    <q-icon name="inventory_2" size="20px" />
                  </div>
                </div>
                <div class="text-h4 text-md-h3 text-weight-bolder text-dark">{{ stats.preparing_orders }}</div>
              </q-card-section>
            </q-card>
          </div>

          <div class="col-6 col-md-3">
            <q-card class="clean-solid-card card-hover h-full">
              <q-card-section class="q-pa-md q-pa-md-lg">
                <div class="row items-center justify-between q-mb-sm">
                  <div class="text-caption text-weight-bold text-green-9 text-uppercase tracking-wide line-height-tight">Picked<br class="mobile-only"> Up</div>
                  <div class="icon-premium-box bg-green-1 text-green-7 shadow-soft">
                    <q-icon name="task_alt" size="20px" />
                  </div>
                </div>
                <div class="text-h4 text-md-h3 text-weight-bolder text-dark">{{ stats.picked_up_orders }}</div>
              </q-card-section>
            </q-card>
          </div>

          <div class="col-6 col-md-3">
            <q-card class="clean-solid-card card-hover h-full">
              <q-card-section class="q-pa-md q-pa-md-lg">
                <div class="row items-center justify-between q-mb-sm">
                  <div class="text-caption text-weight-bold text-red-9 text-uppercase tracking-wide line-height-tight">Cancelled</div>
                  <div class="icon-premium-box bg-red-1 text-red-7 shadow-soft">
                    <q-icon name="block" size="20px" />
                  </div>
                </div>
                <div class="text-h4 text-md-h3 text-weight-bolder text-dark">{{ stats.cancelled_orders }}</div>
              </q-card-section>
            </q-card>
          </div>
        </div>

        <!-- ================= REVENUE & ML BLUEPRINT ================= -->
        <div class="row q-col-gutter-lg q-mb-xl">
          <!-- Revenue Chart -->
          <div class="col-12 col-md-7">
            <q-card class="premium-glass-card card-rounded h-full card-hover flex column">
              <q-card-section class="panel-header row items-center justify-between q-pa-md q-pa-md-lg">
                <div class="text-h6 text-weight-bold text-dark row items-center q-mb-sm q-mb-sm-none">
                  <div class="header-accent-red q-mr-md"></div>
                  Revenue Overview
                </div>
                <div class="desktop-filter-toggle row items-center no-wrap">
                  <button
                    v-for="filter in ['Daily', 'Weekly', 'Monthly']"
                    :key="filter"
                    type="button"
                    class="filter-toggle-btn"
                    :class="{ 'filter-toggle-active': activeRevenueFilter === filter }"
                    @click="activeRevenueFilter = filter"
                  >
                    {{ filter }}
                  </button>
                </div>
              </q-card-section>

              <q-card-section class="chart-container relative-position q-pa-none flex-grow-1">
                <div v-if="chartLoading" class="absolute-full flex flex-center z-top" style="background: rgba(255, 255, 255, 0.6); backdrop-filter: blur(2px); border-radius: 0 0 16px 16px;">
                  <q-spinner-dots size="40px" color="red-8" />
                </div>
                <VueApexCharts
                  v-if="!checkingAccess && chartSeries[0]?.data"
                  class="full-width"
                  style="width: 100%; display: block;"
                  type="area"
                  height="100%"
                  width="100%"
                  :options="chartOptions"
                  :series="chartSeries"
                />
              </q-card-section>
            </q-card>
          </div>

          <!-- ML Blueprint Container -->
          <div class="col-12 col-md-5">
            <q-card class="ml-blueprint-card card-rounded h-full card-hover">
              <q-card-section class="q-pa-md q-pa-md-lg relative-position h-full column">
                <div class="ml-glow-overlay"></div>
                <div class="row items-center justify-between q-mb-md">
                  <div class="row items-center">
                    <q-icon name="model_training" size="24px" color="amber-3" class="q-mr-sm drop-shadow-icon" />
                    <span class="text-h6 text-white text-weight-bolder tracking-tight">Demand Forecast</span>
                  </div>
                </div>

                <template v-if="mlForecast.loading">
                  <p class="text-indigo-1 text-body2 q-mb-lg opacity-80 relative-position z-top leading-relaxed flex-grow-1">Fetching latest demand forecast data...</p>
                  <div class="ml-container-glass flex flex-center relative-position overflow-hidden shadow-soft q-mt-auto">
                    <div class="ml-animated-bg"></div>
                    <div class="text-center z-top">
                      <q-spinner-orbit size="45px" color="amber-3" />
                      <div class="text-caption text-amber-2 q-mt-md font-monospace text-weight-bold tracking-wide">LOADING PREDICTIONS...</div>
                    </div>
                  </div>
                </template>
                
                <template v-else-if="mlForecast.error">
                  <p class="text-red-2 text-body2 q-mb-lg opacity-80 relative-position z-top leading-relaxed flex-grow-1">Unable to connect to the forecasting service. Please try again later.</p>
                  <div class="ml-container-glass flex flex-center relative-position overflow-hidden shadow-soft q-mt-auto p-4" style="background-color: rgba(150, 0, 0, 0.2);">
                    <div class="text-center z-top q-pa-md">
                      <q-icon name="error_outline" size="40px" color="red-4" class="q-mb-sm" />
                      <div class="text-caption text-red-3 font-monospace text-weight-bold tracking-wide">SYSTEM ERROR</div>
                    </div>
                  </div>
                </template>

                <template v-else-if="!mlForecast.has_forecast">
                  <p class="text-indigo-1 text-body2 q-mb-lg opacity-80 relative-position z-top leading-relaxed flex-grow-1">Awaiting more completed orders to establish baseline sales patterns.</p>
                  <div class="ml-container-glass flex flex-center relative-position overflow-hidden shadow-soft q-mt-auto p-4 bg-indigo-9">
                    <div class="text-center z-top q-pa-md">
                      <q-icon name="analytics" size="40px" color="blue-grey-4" class="q-mb-sm" />
                      <div class="text-caption text-blue-grey-2 font-monospace text-weight-bold tracking-wide">AWAITING MORE DATA</div>
                    </div>
                  </div>
                </template>
                
                <template v-else>
                  <div v-if="mlForecast.low_data_warning" class="low-data-warning q-mb-md">
                    <q-icon name="warning" size="16px" class="q-mr-xs" />
                    {{ mlForecast.low_data_warning }}
                  </div>
                  <p class="text-indigo-1 text-body2 q-mb-md opacity-80 relative-position z-top leading-relaxed">Based on recent trends, here are your top predicted demand items for today.</p>
                  <div class="text-white relative-position z-top flex-grow-1">
                    <div v-for="(item, idx) in mlForecast.top_products" :key="idx" class="row justify-between items-center q-mb-sm bg-indigo-9 q-pa-sm rounded-borders">
                      <div class="text-weight-bold ellipsis" style="max-width: 70%;">{{ idx + 1 }}. {{ item.product_name }}</div>
                      <q-chip color="amber-3" text-color="indigo-10" size="sm" class="text-weight-bold shadow-1 q-ma-none">{{ item.predicted_quantity }} units</q-chip>
                    </div>
                  </div>
                  <div class="text-caption text-indigo-3 text-right q-mt-md relative-position z-top font-monospace">
                    <q-icon name="update" class="q-mr-xs" /> Last updated: {{ new Date(mlForecast.generated_at).toLocaleString() }}
                  </div>
                </template>
              </q-card-section>
            </q-card>
          </div>
        </div>

        <!-- ================= RECENT ORDERS TABLE ================= -->
        <q-card class="premium-glass-card card-rounded q-mb-xl card-hover bg-transparent-mobile">
          <q-card-section class="panel-header row items-center justify-between q-pa-md q-pa-md-lg bg-transparent-mobile-header">
            <div class="text-h6 text-weight-bold text-dark row items-center">
              <div class="header-accent-red q-mr-md"></div> Recent Customer Orders
            </div>
            <q-btn label="View All" flat color="red-9" class="text-weight-bold border-radius-8" no-caps @click="router.push('/vendor/orders/list')" />
          </q-card-section>
          <q-table flat class="custom-premium-table bg-transparent" :rows="recentOrders" :columns="orderColumns" row-key="id" hide-bottom :pagination="{ rowsPerPage: 5 }">
            <template #body-cell-id="props">
              <q-td :props="props"><span class="text-weight-bold text-red-8 font-monospace q-px-sm q-py-xs bg-red-1" style="border: 1px solid rgba(220, 38, 38, 0.3); border-radius: 6px;">#{{ props.row.id }}</span></q-td>
            </template>
            <template #body-cell-customer="props">
              <q-td :props="props">
                <div class="row items-center no-wrap">
                  <q-avatar size="34px" class="q-mr-md shadow-soft border-white bg-blue-grey-1">
                    <img v-if="props.row.avatar" :src="props.row.avatar" />
                    <q-icon v-else name="person" color="blue-grey-6" size="22px" />
                  </q-avatar>
                  <div class="text-weight-bold text-blue-grey-9 text-body2">{{ props.row.customer }}</div>
                </div>
              </q-td>
            </template>
            <template #body-cell-status="props">
              <q-td :props="props"><q-chip dense :color="getStatusColor(props.row.status)" text-color="white" class="text-weight-bold shadow-soft q-px-sm q-py-xs">{{ props.row.status }}</q-chip></q-td>
            </template>
            <template #body-cell-action="props">
              <q-td :props="props" class="text-right"><q-btn flat round dense icon="chevron_right" color="blue-grey-4" class="hover-icon-btn" @click="router.push('/vendor/orders/' + props.row.id)" /></q-td>
            </template>
          </q-table>
        </q-card>
      </div>

      <!-- ========================================================= -->
      <!-- ==================== MOBILE LAYOUT ====================== -->
      <!-- ========================================================= -->
      <div v-else class="mobile-layout">
        
        <!-- Clean Airy Mobile Header -->
        <div class="mobile-hero-banner q-mb-lg q-pa-md transition-theme" :class="headerThemeClass">
          <!-- Top Row: Date & Status Badge -->
          <div class="row items-center justify-between no-wrap q-mb-xs">
            <div class="row items-center no-wrap">
              <q-icon name="today" size="14px" :class="subTextClass" class="q-mr-xs opacity-80" />
              <span class="text-caption text-weight-bold tracking-wide text-uppercase" :class="subTextClass" style="font-size: 11px;">
                {{ currentDate }}
              </span>
            </div>
            
            <!-- Compact Status Tag -->
            <div class="mobile-status-tag row items-center no-wrap q-px-sm q-py-xs" :class="capsuleThemeClass">
              <span class="status-pulse-wrapper q-mr-xs">
                <span class="status-pulse-ring" :class="isStoreOpen ? 'pulse-open' : 'pulse-closed'"></span>
                <span class="status-pulse-core" :class="isStoreOpen ? 'core-open' : 'core-closed'"></span>
              </span>
              <span class="text-weight-bold" :class="capsuleTextClass" style="font-size: 11px;">
                {{ isStoreOpen ? 'Store is Open' : 'Store is Closed' }}
              </span>
            </div>
          </div>

          <!-- Bottom Row: Greeting & Store Avatar Button -->
          <div class="row items-center justify-between no-wrap q-pt-xs">
            <div class="col ellipsis q-pr-md">
              <div class="mobile-greeting-text ellipsis" :class="headerTextClass">
                {{ timeGreeting }}, <span class="text-weight-black">{{ userName }}</span>
              </div>
              <div class="text-caption q-mt-none opacity-80 ellipsis" :class="subTextClass" style="font-size: 12px;">
                Neighborhood store overview
              </div>
            </div>
            
            <!-- Store Preview Avatar with Button Overlay -->
            <div class="col-auto relative-position">
              <q-avatar size="52px" class="bg-white text-red-9 cursor-pointer shadow-2 border-white" @click="liveStoreModal = true">
                <img v-if="vendorStore?.store_picture_url" :src="vendorStore.store_picture_url" />
                <q-icon v-else name="storefront" size="26px" color="red-9" />
              </q-avatar>
              <div class="avatar-preview-badge flex flex-center" @click="liveStoreModal = true">
                <q-icon name="visibility" size="11px" color="white" />
              </div>
            </div>
          </div>
        </div>

        <!-- Spacious 2x2 Metric Grid -->
        <div class="row q-col-gutter-md q-mb-lg">
          <div class="col-6">
            <div class="mobile-stat-card column justify-center q-pa-md">
              <div class="row items-center justify-between q-mb-xs">
                <div class="mobile-stat-label">Placed</div>
                <div class="mobile-icon-tile bg-blue-1 text-blue-7 flex flex-center">
                  <q-icon name="shopping_cart_checkout" size="18px" />
                </div>
              </div>
              <div class="mobile-stat-value text-dark">{{ stats.placed_orders }}</div>
            </div>
          </div>
          <div class="col-6">
            <div class="mobile-stat-card column justify-center q-pa-md">
              <div class="row items-center justify-between q-mb-xs">
                <div class="mobile-stat-label">Preparing</div>
                <div class="mobile-icon-tile bg-amber-1 text-amber-8 flex flex-center">
                  <q-icon name="inventory_2" size="18px" />
                </div>
              </div>
              <div class="mobile-stat-value text-dark">{{ stats.preparing_orders }}</div>
            </div>
          </div>
          <div class="col-6">
            <div class="mobile-stat-card column justify-center q-pa-md">
              <div class="row items-center justify-between q-mb-xs">
                <div class="mobile-stat-label">Picked Up</div>
                <div class="mobile-icon-tile bg-green-1 text-green-7 flex flex-center">
                  <q-icon name="task_alt" size="18px" />
                </div>
              </div>
              <div class="mobile-stat-value text-dark">{{ stats.picked_up_orders }}</div>
            </div>
          </div>
          <div class="col-6">
            <div class="mobile-stat-card column justify-center q-pa-md">
              <div class="row items-center justify-between q-mb-xs">
                <div class="mobile-stat-label">Cancelled</div>
                <div class="mobile-icon-tile bg-red-1 text-red-7 flex flex-center">
                  <q-icon name="block" size="18px" />
                </div>
              </div>
              <div class="mobile-stat-value text-dark">{{ stats.cancelled_orders }}</div>
            </div>
          </div>
        </div>

        <!-- Mobile Revenue Card -->
        <q-card class="mobile-clean-card q-mb-lg overflow-hidden">
          <div class="mobile-card-header row items-center justify-between q-pa-md">
            <div class="row items-center no-wrap">
              <div class="header-accent-red-sm q-mr-sm"></div>
              <span class="mobile-card-title text-dark">Revenue Overview</span>
            </div>
            
            <div class="mobile-filter-tabs row items-center no-wrap">
              <button
                v-for="filter in ['Daily', 'Weekly', 'Monthly']"
                :key="filter"
                type="button"
                class="mobile-filter-btn"
                :class="{ 'mobile-filter-active': activeRevenueFilter === filter }"
                @click="activeRevenueFilter = filter"
              >
                {{ filter }}
              </button>
            </div>
          </div>
          
          <div class="q-px-md q-pt-md text-center">
            <div class="mobile-revenue-caption">Total Income</div>
            <div class="mobile-revenue-amount text-dark">₱{{ formatNumber(totalRevenue) }}</div>
          </div>

          <div class="q-pa-none" style="margin-top: -4px;">
            <div v-if="chartLoading" class="flex flex-center q-py-lg">
              <q-spinner-dots size="28px" color="red-8" />
            </div>
            <VueApexCharts
              v-else-if="!checkingAccess && chartSeries[0]?.data"
              class="full-width"
              style="width: 100%; display: block;"
              type="area"
              height="160"
              width="100%"
              :options="chartOptions"
              :series="chartSeries"
            />
          </div>
        </q-card>

        <!-- Mobile Demand Forecast (ML) -->
        <q-card class="ml-blueprint-card mobile-ml-card q-mb-lg">
          <div class="q-pa-md relative-position column">
            <div class="ml-glow-overlay" style="width: 110px; height: 110px;"></div>
            <div class="row items-center justify-between q-mb-md relative-position z-top">
              <div class="row items-center">
                <q-icon name="model_training" size="20px" color="amber-3" class="q-mr-sm drop-shadow-icon" />
                <span class="mobile-card-title text-white">Demand Forecast</span>
              </div>
            </div>

            <template v-if="mlForecast.loading">
              <div class="ml-container-glass flex flex-center relative-position overflow-hidden shadow-soft" style="min-height: 110px;">
                <div class="ml-animated-bg"></div>
                <div class="text-center z-top">
                  <q-spinner-orbit size="28px" color="amber-3" />
                  <div class="text-caption text-amber-2 q-mt-xs font-monospace text-weight-bold" style="font-size: 10px;">ANALYZING...</div>
                </div>
              </div>
            </template>
            <template v-else-if="!mlForecast.has_forecast">
              <div class="ml-container-glass flex flex-center relative-position overflow-hidden shadow-soft q-pa-md bg-indigo-9" style="min-height: 110px; border: 1px solid rgba(255,255,255,0.1);">
                <div class="text-center z-top">
                  <q-icon name="analytics" size="26px" color="indigo-2" class="opacity-50 q-mb-xs" />
                  <div class="text-caption text-indigo-2 font-monospace text-weight-bold opacity-80" style="font-size: 10px;">AWAITING MORE DATA</div>
                </div>
              </div>
            </template>
            <template v-else>
              <div v-if="mlForecast.low_data_warning" class="low-data-warning q-mb-sm q-mx-sm">
                <q-icon name="warning" size="14px" class="q-mr-xs" />
                {{ mlForecast.low_data_warning }}
              </div>
              <div class="text-white relative-position z-top">
                <div v-for="(item, idx) in mlForecast.top_products.slice(0,3)" :key="idx" class="mobile-forecast-row row items-center q-mb-sm q-pa-sm rounded-borders">
                  <div class="forecast-rank-badge q-mr-sm bg-amber-3 text-indigo-10 flex flex-center text-weight-bolder">
                    {{ idx + 1 }}
                  </div>
                  <div class="col ellipsis text-weight-medium text-white text-body2">{{ item.product_name }}</div>
                  <div class="col-auto">
                    <span class="text-amber-3 text-weight-bolder text-caption">{{ item.predicted_quantity }} units</span>
                  </div>
                </div>
              </div>
            </template>
          </div>
        </q-card>

        <!-- Mobile Recent Orders Card -->
        <q-card class="mobile-clean-card q-mb-xl overflow-hidden">
          <div class="mobile-card-header row items-center justify-between q-pa-md">
            <div class="row items-center no-wrap">
              <div class="header-accent-red-sm q-mr-sm"></div>
              <span class="mobile-card-title text-dark">Recent Orders</span>
            </div>
            <q-btn flat dense color="red-9" class="text-weight-bold text-caption" label="View All" no-caps @click="router.push('/vendor/orders/list')" />
          </div>
          
          <div class="q-pa-sm bg-slate-50">
            <div v-if="recentOrders.length === 0" class="text-center text-grey-5 q-py-lg text-caption">No recent orders found.</div>
            
            <q-list v-else class="q-gutter-y-xs">
              <q-item v-for="order in recentOrders.slice(0,3)" :key="order.id" clickable v-ripple @click="router.push('/vendor/orders/' + order.id)" class="mobile-order-item rounded-borders">
                <q-item-section avatar class="q-pr-sm" style="min-width: 44px;">
                  <q-avatar size="38px" class="shadow-1 border-white bg-blue-grey-1">
                    <img v-if="order.avatar" :src="order.avatar" />
                    <q-icon v-else name="person" color="blue-grey-6" size="20px" />
                  </q-avatar>
                </q-item-section>

                <q-item-section class="q-pr-xs">
                  <q-item-label class="text-weight-bold text-blue-grey-9 text-body2 ellipsis">{{ order.customer }}</q-item-label>
                  <q-item-label class="q-mt-xs">
                    <span class="mobile-order-id">#{{ order.id }}</span>
                  </q-item-label>
                </q-item-section>

                <q-item-section side class="items-end">
                  <q-item-label class="text-weight-bolder text-dark text-subtitle2">₱{{ formatNumber(order.price) }}</q-item-label>
                  <q-item-label class="q-mt-xs">
                    <q-chip dense :color="getStatusColor(order.status)" text-color="white" class="mobile-status-chip">
                      {{ order.status }}
                    </q-chip>
                  </q-item-label>
                </q-item-section>
              </q-item>
            </q-list>
          </div>
        </q-card>

        <!-- Mobile Floating Bottom Navigation -->
        <div class="mobile-bottom-nav row justify-around items-center">
          <div class="nav-item-wrapper" @click="router.push('/vendor/dashboard')">
            <q-btn flat round class="mobile-nav-btn nav-active shadow-2">
              <q-icon name="home" size="22px" />
            </q-btn>
          </div>
          <div class="nav-item-wrapper" @click="router.push('/vendor/orders/list')">
            <q-btn flat round class="mobile-nav-btn text-blue-grey-4">
              <q-icon name="receipt_long" size="24px" />
            </q-btn>
          </div>
          <div class="nav-item-wrapper" @click="router.push('/vendor/products/list')">
            <q-btn flat round class="mobile-nav-btn text-blue-grey-4">
              <q-icon name="inventory_2" size="24px" />
            </q-btn>
          </div>
          <div class="nav-item-wrapper" @click="router.push('/vendor/sales')">
            <q-btn flat round class="mobile-nav-btn text-blue-grey-4">
              <q-icon name="analytics" size="24px" />
            </q-btn>
          </div>
        </div>

      </div>
    </div>

    <!-- ================= LIVE STORE MODAL ================= -->
    <q-dialog v-model="liveStoreModal" transition-show="scale" transition-hide="scale" @show="initMap">
      <q-card class="premium-glass-card column no-wrap live-store-modal card-rounded">
        <q-card-section class="row items-center justify-center q-pb-sm bg-white col-auto z-top-10">
          <div class="text-h6 text-weight-bold">{{ vendorStore?.store_name || 'My Store' }}</div>
        </q-card-section>
        <q-separator />
        <q-card-section class="q-pa-none col scroll">
          <div class="full-width">
            <q-img v-if="vendorStore?.store_picture_url" :src="vendorStore.store_picture_url" class="store-banner" fit="cover" />
            <div v-else class="bg-grey-3 flex flex-center full-width store-placeholder">
              <q-icon name="storefront" size="80px" color="grey-6" />
            </div>
          </div>
          <div class="q-pa-md">
            <q-list>
              <q-item class="q-px-none">
                <q-item-section avatar><q-icon color="red-8" name="person" /></q-item-section>
                <q-item-section>
                  <q-item-label class="text-weight-bold text-dark">Store Owner</q-item-label>
                  <q-item-label caption>{{ ownerFullName || 'Not provided' }}</q-item-label>
                </q-item-section>
              </q-item>
              <q-item class="q-px-none">
                <q-item-section avatar><q-icon color="red-8" name="call" /></q-item-section>
                <q-item-section>
                  <q-item-label class="text-weight-bold text-dark">Contact</q-item-label>
                  <q-item-label caption>{{ vendorPhone || 'Not provided' }}</q-item-label>
                </q-item-section>
              </q-item>
              <q-item class="q-px-none items-start">
                <q-item-section avatar class="q-pt-xs"><q-icon color="red-8" name="schedule" /></q-item-section>
                <q-item-section>
                  <q-item-label class="text-weight-bold text-dark q-mb-sm">Store Schedule</q-item-label>
                  <div class="bg-grey-1 rounded-borders q-pa-sm custom-glass-input">
                    <div v-for="day in weekDays" :key="day.name" class="row justify-between items-center q-py-xs" :class="{ 'text-dark': day.isOpen, 'text-blue-grey-4': !day.isOpen }">
                      <span class="text-weight-medium text-body2">{{ day.name }}</span>
                      <span v-if="day.isOpen" class="text-caption text-weight-bold">{{ formatTime(day.openTime) }} - {{ formatTime(day.closeTime) }}</span>
                      <span v-else class="text-caption text-italic">Closed</span>
                    </div>
                  </div>
                </q-item-section>
              </q-item>
              <q-item class="q-px-none">
                <q-item-section avatar><q-icon color="red-8" name="location_on" /></q-item-section>
                <q-item-section>
                  <q-item-label class="text-weight-bold text-dark">Address</q-item-label>
                  <q-item-label caption>{{ vendorStore?.address || 'Not provided' }}</q-item-label>
                </q-item-section>
              </q-item>
            </q-list>
            <div id="store-preview-map" class="border-radius-8 overflow-hidden shadow-soft q-mt-sm store-preview-map-container"></div>
          </div>
        </q-card-section>
        <q-separator />
        <q-card-actions align="right" class="bg-white col-auto q-pa-md z-top-10">
          <q-btn flat label="Close" color="blue-grey-8" class="text-weight-bold q-px-md" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '@/boot/axios'
import VueApexCharts from 'vue3-apexcharts'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

let globalDashboardLoaded = false

// ==========================================
// 1. STATE & REFS
// ==========================================
const router = useRouter()

const isFirstTimeOpening = ref(!globalDashboardLoaded)
const checkingAccess = ref(!globalDashboardLoaded)

const loadingTitle = computed(() => isFirstTimeOpening.value ? 'Loading Dashboard' : 'Updating Store Data')
const loadingSubtitle = computed(() => isFirstTimeOpening.value ? 'Preparing your sari-sari store overview' : 'Syncing your latest sales and orders')

const userName = ref('Vendor')
const ownerFullName = ref('Vendor')
const vendorStore = ref(null)
const vendorPhone = ref(null)
const liveStoreModal = ref(false)
const activeRevenueFilter = ref('Daily')

const mlForecast = ref({
  loading: true,
  has_forecast: false,
  low_data_warning: false,
  error: false,
  summary: null,
  top_products: [],
  generated_at: null
})

const recentOrders = ref([])
const stats = ref({
  placed_orders: 0,
  preparing_orders: 0,
  picked_up_orders: 0,
  cancelled_orders: 0
})

const orderColumns = [
  { name: 'id', label: 'Order ID', field: 'id', align: 'left', sortable: true },
  { name: 'date', label: 'Date', field: 'date', align: 'left', sortable: true },
  { name: 'customer', label: 'Customer', field: 'customer', align: 'left' },
  { name: 'price', label: 'Price', field: 'price', align: 'left', sortable: true },
  { name: 'status', label: 'Status', field: 'status', align: 'left' },
  { name: 'action', label: '', field: 'action', align: 'right' }
]

// ==========================================
// 2. CHART CONFIGURATIONS
// ==========================================
const chartLoading = ref(false)
const chartSeries = ref([{ name: 'Revenue', data: [] }])

const totalRevenue = computed(() => {
  if (!chartSeries.value[0]?.data) return 0
  return chartSeries.value[0].data.reduce((a, b) => a + b, 0)
})

const chartOptions = ref({
  chart: { type: 'area', toolbar: { show: false }, zoom: { enabled: false } },
  colors: ['#c62828'],
  dataLabels: { enabled: false },
  stroke: { curve: 'smooth', width: 2.5 },
  xaxis: { categories: [], labels: { style: { colors: '#78909c', fontSize: '11px' } }, axisBorder: { show: false }, axisTicks: { show: false } },
  yaxis: { labels: { style: { colors: '#78909c', fontSize: '11px' }, formatter: value => '₱' + Number(value).toLocaleString('en-PH', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) } },
  grid: { borderColor: '#eceff1', strokeDashArray: 3 },
  fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.05, stops: [0, 100] } }
})

// ==========================================
// 3. COMPUTED THEMES & ALIGNMENT
// ==========================================
const currentHour = new Date().getHours()
const timeGreeting = computed(() => {
  if (currentHour < 12) return 'Good morning'
  if (currentHour < 18) return 'Good afternoon'
  return 'Good evening'
})

const headerThemeClass = computed(() => {
  if (currentHour < 12) return 'header-morning'
  if (currentHour < 18) return 'header-afternoon'
  return 'header-evening'
})

const headerTextClass = computed(() => {
  if (currentHour >= 18) return 'text-white'
  return 'text-blue-grey-10'
})

const subTextClass = computed(() => {
  if (currentHour >= 18) return 'text-indigo-1'
  return 'text-blue-grey-8'
})

const capsuleThemeClass = computed(() => {
  if (currentHour >= 18) return 'capsule-night'
  return 'capsule-day'
})

const capsuleTextClass = computed(() => {
  if (currentHour >= 18) return 'text-white'
  return 'text-blue-grey-9'
})

const currentDate = computed(() => {
  const options = { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' }
  return new Intl.DateTimeFormat('en-US', options).format(new Date())
})

const weekDays = computed(() => {
  const fullDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']
  if (!vendorStore.value?.operating_days) return fullDays.map(name => ({ name, isOpen: false, openTime: null, closeTime: null }))

  let raw = vendorStore.value.operating_days
  if (typeof raw === 'string') { try { raw = JSON.parse(raw) } catch (e) {} }

  const defaultOpen = vendorStore.value.opening_time ? vendorStore.value.opening_time.substring(0, 5) : null
  const defaultClose = vendorStore.value.closing_time ? vendorStore.value.closing_time.substring(0, 5) : null

  return fullDays.map(dayName => {
    let isOpen = false; let openTime = defaultOpen; let closeTime = defaultClose
    if (raw !== null && typeof raw === 'object' && !Array.isArray(raw)) {
      if (raw[dayName]) {
        isOpen = !!raw[dayName].is_open
        if (raw[dayName].opening_time) openTime = raw[dayName].opening_time.substring(0, 5)
        if (raw[dayName].closing_time) closeTime = raw[dayName].closing_time.substring(0, 5)
      }
    } else if (Array.isArray(raw)) {
      isOpen = raw.some(d => dayName.toLowerCase().startsWith(String(d).toLowerCase()))
    }
    return { name: dayName, isOpen, openTime, closeTime }
  })
})

const isStoreOpen = computed(() => {
  if (!vendorStore.value?.operating_days) return false
  const dayAbbreviations = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']
  const now = new Date()
  const todayName = dayAbbreviations[now.getDay()]
  const todaySchedule = weekDays.value.find(d => d.name === todayName)
  
  if (!todaySchedule || !todaySchedule.isOpen) return false
  if (!todaySchedule.openTime || !todaySchedule.closeTime) return true

  const currentMinutes = now.getHours() * 60 + now.getMinutes()
  const [openH, openM] = todaySchedule.openTime.split(':').map(Number)
  const [closeH, closeM] = todaySchedule.closeTime.split(':').map(Number)
  const openMinutes = openH * 60 + openM
  const closeMinutes = closeH * 60 + closeM

  if (openMinutes <= closeMinutes) return currentMinutes >= openMinutes && currentMinutes <= closeMinutes
  return currentMinutes >= openMinutes || currentMinutes <= closeMinutes
})

// ==========================================
// 4. HELPER FUNCTIONS
// ==========================================
const getStatusColor = status => {
  if (!status) return 'blue-grey-4'
  const normalizedStatus = status.toLowerCase().replace(/\s+/g, '_')
  switch (normalizedStatus) {
    case 'placed': return 'blue-6'
    case 'preparing': return 'amber-7'
    case 'picked_up': return 'green-6'
    case 'cancelled': return 'red-6'
    default: return 'blue-grey-4'
  }
}

const formatTime = timeString => {
  if (!timeString) return 'Not set'
  return new Date(`1970-01-01T${timeString}`).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}

const formatNumber = num => {
  const cleanNum = Number(String(num).replace(/[^0-9.-]+/g, ""))
  return Number(cleanNum || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

// ==========================================
// 5. API FUNCTIONS
// ==========================================
const fetchChartData = async () => {
  chartLoading.value = true
  try {
    const res = await api.get('/vendor/stats/chart', { params: { filter: activeRevenueFilter.value } })
    if (res.data) {
      chartSeries.value = [{ name: 'Revenue', data: res.data.map(item => item.total) }]
      chartOptions.value = {
        ...chartOptions.value,
        xaxis: { ...chartOptions.value.xaxis, categories: [...res.data.map(item => item.period)] }
      }
    }
  } catch (error) {
    console.error('Failed to load chart data:', error)
  } finally {
    chartLoading.value = false
  }
}

// ==========================================
// 6. LEAFLET FUNCTIONS
// ==========================================
let map = null
const initMap = async () => {
  await nextTick()
  if (map) map.remove()
  const lat = vendorStore.value?.latitude || 14.5995
  const lng = vendorStore.value?.longitude || 120.9842

  map = L.map('store-preview-map').setView([lat, lng], 15)
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap contributors' }).addTo(map)

  const icon = L.icon({
    iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
    iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
    shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
    iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41]
  })

  L.marker([lat, lng], { icon }).addTo(map).bindPopup('<b>' + (vendorStore.value?.store_name || 'My Store') + '</b><br>Location').openPopup()
  setTimeout(() => { map.invalidateSize() }, 100)
}

// ==========================================
// 7. LIFECYCLE HOOKS
// ==========================================
onMounted(async () => {
  const isFirstLoad = !globalDashboardLoaded
  
  if (isFirstLoad) {
    checkingAccess.value = true
  }

  try {
    fetchChartData()

    const [profileRes, statsRes] = await Promise.allSettled([
      api.get('/vendor/profile'),
      api.get('/vendor/stats')
    ])

    if (profileRes.status === 'fulfilled' && profileRes.value.data) {
      const profile = profileRes.value.data
      userName.value = profile.full_name ? profile.full_name.split(' ')[0] : 'Vendor'
      ownerFullName.value = profile.full_name || 'Vendor'
      vendorPhone.value = profile.phone_number || null
      vendorStore.value = profile.store ? { ...profile.store } : null
    }

    if (statsRes.status === 'fulfilled' && statsRes.value.data) {
      stats.value.placed_orders = statsRes.value.data.placed_orders || 0
      stats.value.preparing_orders = statsRes.value.data.preparing_orders || 0
      stats.value.picked_up_orders = statsRes.value.data.picked_up_orders || 0
      stats.value.cancelled_orders = statsRes.value.data.cancelled_orders || 0
      recentOrders.value = statsRes.value.data.recent_orders || []
    }
    
    try {
      const mlRes = await api.get('/vendor/demand-forecast')
      if (mlRes.data) {
        mlForecast.value.has_forecast = mlRes.data.has_forecast
        mlForecast.value.low_data_warning = mlRes.data.low_data_warning || false
        mlForecast.value.summary = mlRes.data.summary
        mlForecast.value.top_products = mlRes.data.top_products || []
        mlForecast.value.generated_at = mlRes.data.generated_at
      }
    } catch (err) {
      console.error('Failed to load demand forecast:', err)
      mlForecast.value.error = true
      mlForecast.value.has_forecast = false
    } finally {
      mlForecast.value.loading = false
    }
  } catch (error) {
    console.error('Dashboard init error:', error)
    userName.value = 'Vendor'
  } finally {
    globalDashboardLoaded = true
    if (isFirstLoad) {
      setTimeout(() => {
        checkingAccess.value = false
      }, 700)
    } else {
      checkingAccess.value = false
    }
  }
})

watch(activeRevenueFilter, () => { fetchChartData() })
</script>

<style scoped>
/* ================= GLOBAL BASE ================= */
.vendor-page { padding: 32px 24px; background-color: #f8fafc; min-height: 100vh; position: relative; overflow: hidden; }

.bg-glow { position: absolute; width: 600px; height: 600px; border-radius: 50%; filter: blur(140px); z-index: 0; opacity: 0.15; pointer-events: none; }
.bg-glow-primary { top: -150px; left: -150px; background: radial-gradient(circle, rgba(185, 28, 28, 0.4) 0%, transparent 70%); }
.bg-glow-secondary { bottom: -150px; right: -150px; background: radial-gradient(circle, rgba(15, 23, 42, 0.3) 0%, transparent 70%); }

.page-container { max-width: 1400px; margin: 0 auto; position: relative; z-index: 1; }
.card-rounded { border-radius: 16px; }
.z-top-10 { z-index: 10; }
.z-max { z-index: 9999; }
.h-full { height: 100%; }
.border-radius-8 { border-radius: 8px; }
.opacity-50 { opacity: 0.5; }
.opacity-80 { opacity: 0.8; }
.flex-grow-1 { flex-grow: 1; }
.tracking-wide { letter-spacing: 0.08em; }
.leading-relaxed { line-height: 1.6; }
.line-height-tight { line-height: 1.2; }
.mobile-only { display: none; }

/* Desktop Cards */
.clean-solid-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05); transition: transform 0.2s ease, box-shadow 0.2s ease; }
.premium-glass-card { background: rgba(255, 255, 255, 0.98); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border: 1px solid rgba(241, 245, 249, 1); box-shadow: 0 4px 24px rgba(15, 23, 42, 0.04); }
.card-hover:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06); }

/* Header Themes */
.transition-theme { transition: background 0.8s ease, color 0.4s ease; }
.header-morning { background: linear-gradient(135deg, #fffbeb 0%, #e0f2fe 100%); border: 1px solid rgba(255, 255, 255, 0.9); box-shadow: 0 8px 24px rgba(186, 230, 253, 0.25); }
.header-afternoon { background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 60%, #fed7aa 100%); border: 1px solid rgba(255, 255, 255, 0.95); box-shadow: 0 10px 28px rgba(14, 165, 233, 0.15); }
.header-evening { background: linear-gradient(135deg, #1e3a8a 0%, #312e81 100%); border: 1px solid rgba(255, 255, 255, 0.1); box-shadow: 0 15px 40px rgba(30, 58, 138, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.1); }

/* Desktop Capsule */
.unified-store-capsule {
  display: inline-flex;
  align-items: center;
  border-radius: 999px;
  padding: 4px;
}

.capsule-day {
  background: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(14px);
  -webkit-backdrop-filter: blur(14px);
  border: 1px solid rgba(255, 255, 255, 0.95);
  box-shadow: 0 4px 16px rgba(15, 23, 42, 0.06);
}

.capsule-night {
  background: rgba(255, 255, 255, 0.15);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border: 1px solid rgba(255, 255, 255, 0.25);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
}

.status-pulse-wrapper {
  position: relative;
  width: 10px;
  height: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.status-pulse-core {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  position: relative;
  z-index: 2;
}

.status-pulse-ring {
  position: absolute;
  width: 15px;
  height: 15px;
  border-radius: 50%;
  opacity: 0.6;
  animation: pulse-ring-expand 2s cubic-bezier(0.24, 0, 0.38, 1) infinite;
}

.core-open { background-color: #10b981; }
.pulse-open { background-color: rgba(16, 185, 129, 0.45); }
.core-closed { background-color: #ef4444; }
.pulse-closed { background-color: rgba(239, 68, 68, 0.45); }

@keyframes pulse-ring-expand {
  0% { transform: scale(0.6); opacity: 0.8; }
  100% { transform: scale(1.6); opacity: 0; }
}

.capsule-divider {
  width: 1px;
  height: 18px;
  background: rgba(100, 116, 139, 0.2);
  margin: 0 3px;
}

.capsule-night .capsule-divider {
  background: rgba(255, 255, 255, 0.2);
}

.capsule-action-btn {
  background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
  color: #ffffff;
  border: none;
  outline: none;
  border-radius: 999px;
  padding: 6px 14px;
  box-shadow: 0 2px 8px rgba(220, 38, 38, 0.3);
  transition: all 0.25s ease;
}

.capsule-action-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(220, 38, 38, 0.45);
  background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%);
}

.icon-premium-box { width: 46px; height: 46px; border-radius: 14px; display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255, 255, 255, 0.9); }
.shadow-soft { box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04); }
.border-white { border: 2px solid #ffffff; }

/* Desktop Static Segmented Controls */
.desktop-filter-toggle { background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 8px; padding: 3px; }
.filter-toggle-btn { background: transparent; border: none; outline: none; font-size: 12px; font-weight: 600; color: #64748b; padding: 5px 14px; border-radius: 6px; cursor: pointer; }
.filter-toggle-btn:hover:not(.filter-toggle-active) { color: #0f172a; }
.filter-toggle-active { background: #ffffff; color: #0f172a; box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08); }

/* Desktop Panels & Tables */
.panel-header { background: rgba(248, 250, 252, 0.5); border-bottom: 1px solid rgba(226, 232, 240, 0.6); border-radius: 16px 16px 0 0; }
.header-accent-red { width: 6px; height: 24px; background: linear-gradient(180deg, #b91c1c 0%, #450a0a 100%); border-radius: 6px; }

:deep(.custom-premium-table thead tr th) { background: rgba(248, 250, 252, 0.5); font-weight: 700; color: #64748b; text-transform: uppercase; font-size: 11px; letter-spacing: 0.05em; padding: 16px 20px; border-bottom: 1px solid rgba(226, 232, 240, 0.8); }
:deep(.custom-premium-table tbody td) { padding: 16px 20px; border-bottom: 1px solid rgba(241, 245, 249, 1); }
:deep(.custom-premium-table tbody tr:hover) { background: #ffffff; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03); }
.hover-icon-btn { transition: transform 0.2s ease, color 0.2s ease; }
:deep(.custom-premium-table tbody tr:hover .hover-icon-btn) { color: #b91c1c !important; transform: translateX(3px); }

.chart-container { min-height: 250px; padding: 0; width: 100%; display: flex; flex-direction: column; overflow: hidden; }

/* ML Blueprint Card */
.ml-blueprint-card { background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%); border: 1px solid rgba(255, 255, 255, 0.08); box-shadow: 0 15px 40px rgba(15, 23, 42, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.05); }
.ml-glow-overlay { position: absolute; top: 0; right: 0; width: 150px; height: 150px; background: radial-gradient(circle, rgba(99, 102, 241, 0.2) 0%, transparent 70%); filter: blur(20px); z-index: 0; }
.ml-container-glass { background: rgba(0, 0, 0, 0.25); border: 1px solid rgba(251, 191, 36, 0.25); backdrop-filter: blur(12px); border-radius: 12px; }
.ml-animated-bg { position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: conic-gradient(from 0deg, transparent 0%, rgba(251, 191, 36, 0.08) 25%, transparent 50%); animation: rotate-bg 10s linear infinite; }
@keyframes rotate-bg { 100% { transform: rotate(360deg); } }
.drop-shadow-icon { filter: drop-shadow(0 0 10px rgba(251, 191, 36, 0.4)); }
.font-monospace { font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace; }

/* Live Store Modal */
.live-store-modal { width: 500px; max-width: 90vw; max-height: 90vh; }
.store-banner { height: 220px; }
.store-placeholder { aspect-ratio: 16/9; }
.custom-glass-input { border: 1px solid #e2e8f0; }
.store-preview-map-container { height: 200px; border: 1px solid #cfd8dc; position: relative; z-index: 1; }

/* ================= LOADER STYLES ================= */
.glass-backdrop { background: rgba(248, 250, 252, 0.82); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); }
.fade-fast-enter-active, .fade-fast-leave-active { transition: opacity 0.35s ease; }
.fade-fast-enter-from, .fade-fast-leave-to { opacity: 0; }

.loader-glass-card {
  background: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(24px);
  -webkit-backdrop-filter: blur(24px);
  border: 1.5px solid rgba(255, 255, 255, 0.95);
  border-radius: 28px;
  box-shadow: 0 20px 45px rgba(185, 28, 28, 0.08), inset 0 0 0 1px rgba(255,255,255,0.7);
  padding: 42px 56px;
  text-align: center;
  position: relative;
  overflow: hidden;
}

.store-icon-wrapper { width: 100px; height: 100px; }
.store-avatar-box {
  width: 82px; height: 82px; border-radius: 22px;
  background: linear-gradient(145deg, #ffffff, #fee2e2);
  border: 3px solid #ffffff; position: relative; z-index: 2; color: #b91c1c;
}

.storefront-icon-anim { animation: awning-lift 2.2s ease-in-out infinite; }
.store-awning-bar { position: absolute; bottom: 12px; width: 32px; height: 4px; border-radius: 4px; background: #ef4444; opacity: 0.85; animation: awning-bar-glow 2.2s ease-in-out infinite; }

.floating-particle {
  position: absolute; z-index: 3; pointer-events: none; background: transparent; padding: 0;
  display: flex; align-items: center; justify-content: center; filter: drop-shadow(0 2px 6px rgba(0, 0, 0, 0.08));
}

.p-1 { top: -4px; right: -4px; animation: float-orbit-1 3s ease-in-out infinite; }
.p-2 { bottom: 0px; left: -8px; animation: float-orbit-2 3.5s ease-in-out infinite; }
.p-3 { top: 4px; left: -6px; animation: float-orbit-3 2.8s ease-in-out infinite; }

.peso-coin {
  display: inline-flex; align-items: center; justify-content: center; width: 22px; height: 22px;
  background: linear-gradient(135deg, #22c55e 0%, #15803d 100%); color: #ffffff;
  font-weight: 900; font-size: 13px; border-radius: 50%; border: 1.5px solid #ffffff;
  box-shadow: 0 2px 6px rgba(22, 101, 52, 0.35); user-select: none;
}

.sari-loading-bar-track { width: 140px; height: 4px; background: #f1f5f9; border-radius: 99px; overflow: hidden; position: relative; }
.sari-loading-bar-fill { position: absolute; height: 100%; width: 50%; background: linear-gradient(90deg, #ef4444, #f59e0b); border-radius: 99px; animation: bar-slide 1.4s infinite ease-in-out; }

@keyframes awning-lift { 0%, 100% { transform: scale(1) translateY(0); } 50% { transform: scale(1.06) translateY(-3px); } }
@keyframes awning-bar-glow { 0%, 100% { transform: scaleX(0.9); opacity: 0.7; } 50% { transform: scaleX(1.15); opacity: 1; } }
@keyframes float-orbit-1 { 0%, 100% { transform: translateY(0) rotate(0deg); } 50% { transform: translateY(-8px) rotate(12deg); } }
@keyframes float-orbit-2 { 0%, 100% { transform: translateY(0) rotate(0deg); } 50% { transform: translateY(-6px) rotate(-10deg); } }
@keyframes float-orbit-3 { 0%, 100% { transform: translateY(0) scale(0.9); } 50% { transform: translateY(-7px) scale(1.1); } }
@keyframes bar-slide { 0% { left: -50%; } 100% { left: 100%; } }

.loading-dots::after { content: '...'; display: inline-block; animation: typing-dots 1.5s steps(4, end) infinite; width: 1em; text-align: left; }
@keyframes typing-dots { 0%, 20% { content: ''; } 40% { content: '.'; } 60% { content: '..'; } 80%, 100% { content: '...'; } }

/* ============================================================= */
/* ========== BALANCED & SPACIOUS MOBILE-ONLY UI =============== */
/* ============================================================= */
@media (max-width: 767px) {
  .vendor-page.mobile-page-padding {
    padding: 16px 14px calc(80px + env(safe-area-inset-bottom)) 14px !important;
  }
  .mobile-only { display: block; }
  
  .loader-glass-card {
    padding: 32px 24px;
    width: 86%;
    max-width: 320px;
  }

  /* 1. Balanced Mobile Header */
  .mobile-hero-banner {
    border-radius: 16px;
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.05);
  }

  .mobile-status-tag {
    border-radius: 999px;
    padding: 3px 10px;
    border: 1px solid rgba(255, 255, 255, 0.6);
  }

  .mobile-greeting-text {
    font-size: 21px;
    font-weight: 800;
    line-height: 1.2;
    letter-spacing: -0.02em;
  }

  .avatar-preview-badge {
    position: absolute;
    bottom: -2px;
    right: -2px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: #dc2626;
    border: 2px solid #ffffff;
    box-shadow: 0 2px 5px rgba(220, 38, 38, 0.4);
    cursor: pointer;
  }

  /* 2. Spacious 2x2 Metric Cards */
  .mobile-stat-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 2px 6px rgba(15, 23, 42, 0.03);
    min-height: 84px;
  }

  .mobile-icon-tile {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    color: #1a237e;
    font-size: 11px;
  }

  .mobile-stat-label {
    font-size: 12px;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.04em;
  }

  .mobile-stat-value {
    font-size: 24px;
    font-weight: 800;
    line-height: 1.1;
  }

  /* 3. Mobile Clean Card Standard */
  .mobile-clean-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    box-shadow: 0 2px 10px rgba(15, 23, 42, 0.04);
  }

  .mobile-card-header {
    background: rgba(248, 250, 252, 0.85);
    border-bottom: 1px solid #f1f5f9;
  }

  .header-accent-red-sm {
    width: 4px;
    height: 18px;
    background: linear-gradient(180deg, #b91c1c 0%, #450a0a 100%);
    border-radius: 4px;
  }

  .mobile-card-title {
    font-size: 15px;
    font-weight: 800;
  }

  .mobile-filter-tabs {
    background: #f1f5f9;
    border-radius: 8px;
    padding: 3px;
    border: 1px solid #e2e8f0;
  }

  .mobile-filter-btn {
    background: transparent;
    border: none;
    font-size: 11px;
    font-weight: 600;
    color: #64748b;
    padding: 4px 10px;
    border-radius: 6px;
    cursor: pointer;
  }

  .mobile-filter-active {
    background: #ffffff;
    color: #0f172a;
    font-weight: 700;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
  }

  .mobile-revenue-caption {
    font-size: 11px;
    font-weight: 700;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.06em;
  }

  .mobile-revenue-amount {
    font-size: 26px;
    font-weight: 900;
    letter-spacing: -0.03em;
    line-height: 1.1;
  }

  /* 4. Mobile Demand Forecast */
  .mobile-ml-card {
    border-radius: 16px;
  }

  .mobile-forecast-row {
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.06);
  }

  .forecast-rank-badge {
    width: 22px;
    height: 22px;
    border-radius: 6px;
    font-size: 11px;
  }

  /* 5. Mobile Recent Orders Item */
  .mobile-order-item {
    background: #ffffff;
    border: 1px solid #f1f5f9;
    padding: 10px 12px;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.02);
  }

  .mobile-order-id {
    font-size: 10.5px;
    font-weight: 700;
    font-family: 'SFMono-Regular', Consolas, monospace;
    color: #b91c1c;
    background: #fef2f2;
    border: 1px solid rgba(220, 38, 38, 0.2);
    border-radius: 4px;
    padding: 2px 6px;
    display: inline-block;
  }

  .mobile-status-chip {
    font-size: 10.5px;
    font-weight: 700;
    height: 20px;
    padding: 0 8px;
    margin: 0;
  }

  /* 6. Mobile Bottom Nav */
  .mobile-bottom-nav {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    height: calc(64px + env(safe-area-inset-bottom));
    padding-bottom: env(safe-area-inset-bottom);
    background: rgba(255, 255, 255, 0.92);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border-top: 1px solid rgba(226, 232, 240, 0.8);
    z-index: 2000;
    box-shadow: 0 -4px 16px rgba(15, 23, 42, 0.05);
  }
  
  .nav-item-wrapper {
    flex: 1;
    display: flex;
    justify-content: center;
    align-items: center;
  }
  
  .mobile-nav-btn {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    padding: 0;
    transition: all 0.25s ease;
  }
  
  .nav-active {
    background: linear-gradient(135deg, #b91c1c 0%, #7f1d1d 100%) !important;
    color: #ffffff !important;
    box-shadow: 0 4px 12px rgba(185, 28, 28, 0.35) !important;
    transform: translateY(-2px);
  }
}

.low-data-warning {
  background: rgba(255, 183, 77, 0.15);
  border: 1px solid rgba(255, 183, 77, 0.4);
  border-radius: 8px;
  padding: 8px 12px;
  color: #ffb74d;
  font-size: 12px;
  font-weight: 600;
  display: flex;
  align-items: center;
}
</style>