<template>
  <q-page class="vendor-page" :class="{ 'mobile-page-padding': $q.screen.lt.md }">
    <!-- Decorative Ambient Background Glows -->
    <div class="bg-glow bg-glow-primary"></div>
    <div class="bg-glow bg-glow-secondary"></div>

    <div v-if="checkingAccess" class="checking-access flex flex-center h-full">
      <q-spinner-puff color="red-9" size="60px" thickness="3" />
    </div>

    <div v-else class="page-container">
      
      <!-- ========================================================= -->
      <!-- ==================== DESKTOP LAYOUT ===================== -->
      <!-- ========================================================= -->
      <div v-if="!$q.screen.lt.md" class="desktop-layout">
        
        <!-- ================= DYNAMIC TIME-SYNCED HEADER ================= -->
        <div class="welcome-banner q-mb-lg q-pa-md q-pa-md-lg row items-center justify-between transition-theme card-rounded" :class="headerThemeClass">
          <div class="col-12 col-md-7 q-mb-md q-mb-md-none">
            <div class="row items-center q-mb-sm">
              <q-icon name="today" size="18px" :class="subTextClass" class="q-mr-sm opacity-80" />
              <span class="text-caption text-weight-bold tracking-wide text-uppercase" :class="subTextClass">
                {{ currentDate }}
              </span>
            </div>
            <h1 class="text-h3 text-weight-bolder q-ma-none header-title" :class="headerTextClass" style="line-height: 1.1;">
              {{ timeGreeting }},
              <span class="text-weight-black">{{ userName }}</span>
            </h1>
            <p class="text-subtitle1 q-mt-sm q-mb-none opacity-80" :class="subTextClass">
              Here's what's happening with your neighborhood store today.
            </p>
          </div>

          <div class="col-12 col-md-5 flex justify-md-end">
            <div class="row items-center q-gutter-sm full-width-mobile justify-between justify-md-end">
              <div class="store-status-badge row items-center q-px-md q-py-sm border-radius-8 shadow-soft bg-glass flex-grow-1-mobile justify-center">
                <q-icon name="fiber_manual_record" size="12px" :color="isStoreOpen ? 'green-5' : 'red-5'" class="q-mr-sm" :class="{ 'status-dot': isStoreOpen }" />
                <span class="text-weight-bold text-caption text-white" style="letter-spacing: 0.5px;">{{ isStoreOpen ? 'Store is Open' : 'Store is Closed' }}</span>
              </div>
              <q-btn label="Live Store" unelevated color="white" text-color="dark" icon="storefront" class="btn-premium q-px-md shadow-1 flex-grow-1-mobile" no-caps @click="liveStoreModal = true" />
            </div>
          </div>
        </div>

        <!-- ================= TOP METRICS (Solid Clean Design) ================= -->
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
                <div class="scroll-container">
                  <q-btn-group flat class="bg-slate-50 border-slate-light rounded-borders p-1 no-wrap">
                    <q-btn v-for="filter in ['Daily', 'Weekly', 'Monthly']" :key="filter" :label="filter" :unelevated="activeRevenueFilter === filter" :flat="activeRevenueFilter !== filter" :class="activeRevenueFilter === filter ? 'bg-white text-dark shadow-1 text-weight-bold' : 'text-blue-grey-6 hover-text-dark'" no-caps size="sm" class="border-radius-8 transition-ease q-px-md" @click="activeRevenueFilter = filter" />
                  </q-btn-group>
                </div>
              </q-card-section>

              <q-card-section class="chart-container relative-position q-pa-none flex-grow-1">
                <div v-if="chartLoading" class="absolute-full flex flex-center z-top" style="background: rgba(255, 255, 255, 0.6); backdrop-filter: blur(2px); border-radius: 0 0 16px 16px;">
                  <q-spinner-dots size="40px" color="red-8" />
                </div>
                <VueApexCharts class="full-width" style="width: 100%; display: block;" type="area" height="100%" width="100%" :options="chartOptions" :series="chartSeries" />
              </q-card-section>
            </q-card>
          </div>

          <!-- ML Blueprint Container -->
          <div class="col-12 col-md-5">
            <q-card class="ml-blueprint-card card-rounded h-full card-hover">
              <q-card-section class="q-pa-md q-pa-md-lg relative-position h-full column">
                <div class="ml-glow-overlay"></div>
                <div class="row items-center justify-between q-mb-md relative-position z-top">
                  <div class="row items-center">
                    <q-icon name="model_training" size="32px" color="amber-3" class="q-mr-sm drop-shadow-icon" />
                    <div class="text-h6 text-weight-bold text-white">Demand Forecast</div>
                  </div>
                  <q-chip color="white" text-color="indigo-10" size="sm" class="text-weight-bolder shadow-1">
                    <q-icon name="memory" size="14px" class="q-mr-xs" /> AI Engine
                  </q-chip>
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
                
                <template v-else-if="!mlForecast.has_forecast">
                  <p class="text-indigo-1 text-body2 q-mb-lg opacity-80 relative-position z-top leading-relaxed flex-grow-1">Insufficient historical data to generate a reliable demand forecast. The AI engine requires more completed orders to establish baseline sales patterns.</p>
                  <div class="ml-container-glass flex flex-center relative-position overflow-hidden shadow-soft q-mt-auto p-4 bg-indigo-9">
                    <div class="text-center z-top q-pa-md">
                      <q-icon name="analytics" size="40px" color="blue-grey-4" class="q-mb-sm" />
                      <div class="text-caption text-blue-grey-2 font-monospace text-weight-bold tracking-wide">AWAITING MORE DATA</div>
                    </div>
                  </div>
                </template>
                
                <template v-else>
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
      <div v-else class="mobile-layout q-px-sm q-pt-sm">
        
        <!-- Premium Time-Synced Mobile Header -->
        <q-card class="welcome-banner q-mb-lg q-pa-md transition-theme card-rounded shadow-soft" :class="headerThemeClass">
          <div class="row items-center justify-between no-wrap q-mb-md">
            <div class="col" style="min-width: 0;">
              <div class="row items-center q-mb-xs">
                <q-icon name="today" size="14px" :class="subTextClass" class="q-mr-sm opacity-80" />
                <span class="text-caption text-weight-bold tracking-wide text-uppercase ellipsis" :class="subTextClass" style="font-size: 10px;">
                  {{ currentDate }}
                </span>
              </div>
              <h1 class="text-weight-bolder q-ma-none" :class="headerTextClass" style="font-size: 24px; line-height: 1.2; word-wrap: break-word;">
                {{ timeGreeting }},<br><span class="text-weight-black">{{ userName }}</span>
              </h1>
            </div>
            
            <div class="col-auto q-pl-sm">
              <q-avatar size="54px" class="bg-red-1 text-red-9 cursor-pointer shadow-3" @click="liveStoreModal = true" style="border: 2px solid rgba(255,255,255,0.9);">
                <img v-if="vendorStore?.store_picture_url" :src="vendorStore.store_picture_url" />
                <q-icon v-else name="storefront" size="26px" />
              </q-avatar>
            </div>
          </div>
          
          <div class="row items-center justify-between">
            <div class="store-status-badge row items-center q-px-sm q-py-xs border-radius-8 bg-glass" style="max-width: fit-content;">
              <q-icon name="fiber_manual_record" size="10px" :color="isStoreOpen ? 'green-4' : 'red-4'" class="q-mr-xs" :class="{ 'status-dot': isStoreOpen }" />
              <span class="text-weight-bold text-white" style="font-size: 11px;">{{ isStoreOpen ? 'Store is Open' : 'Store is Closed' }}</span>
            </div>
            <q-btn flat dense icon="storefront" label="Live Preview" color="white" class="text-weight-bold text-caption bg-glass q-px-sm border-radius-8" no-caps @click="liveStoreModal = true" />
          </div>
        </q-card>

        <!-- Premium Mobile Top Metrics (Side-by-side icon and text layout) -->
        <div class="row q-col-gutter-sm q-mb-lg">
          <!-- Placed -->
          <div class="col-6">
            <q-card class="mobile-metric-card shadow-soft h-full">
              <q-card-section class="q-pa-md row items-center no-wrap h-full">
                <div class="mobile-icon-box-new bg-blue-1 text-blue-7 q-mr-md flex flex-center">
                  <q-icon name="shopping_cart_checkout" size="22px" />
                </div>
                <div class="column justify-center">
                  <div class="text-grey-8 text-weight-medium" style="font-size: 13px;">Placed</div>
                  <div class="text-h5 text-weight-bolder text-dark" style="margin-top: 2px; line-height: 1;">{{ stats.placed_orders }}</div>
                </div>
              </q-card-section>
            </q-card>
          </div>
          <!-- Preparing -->
          <div class="col-6">
            <q-card class="mobile-metric-card shadow-soft h-full">
              <q-card-section class="q-pa-md row items-center no-wrap h-full">
                <div class="mobile-icon-box-new bg-amber-1 text-amber-8 q-mr-md flex flex-center">
                  <q-icon name="inventory_2" size="22px" />
                </div>
                <div class="column justify-center">
                  <div class="text-grey-8 text-weight-medium" style="font-size: 13px;">Preparing</div>
                  <div class="text-h5 text-weight-bolder text-dark" style="margin-top: 2px; line-height: 1;">{{ stats.preparing_orders }}</div>
                </div>
              </q-card-section>
            </q-card>
          </div>
          <!-- Cancelled -->
          <div class="col-6">
            <q-card class="mobile-metric-card shadow-soft h-full">
              <q-card-section class="q-pa-md row items-center no-wrap h-full">
                <div class="mobile-icon-box-new bg-red-1 text-red-7 q-mr-md flex flex-center">
                  <q-icon name="block" size="22px" />
                </div>
                <div class="column justify-center">
                  <div class="text-grey-8 text-weight-medium" style="font-size: 13px;">Cancelled</div>
                  <div class="text-h5 text-weight-bolder text-dark" style="margin-top: 2px; line-height: 1;">{{ stats.cancelled_orders }}</div>
                </div>
              </q-card-section>
            </q-card>
          </div>
          <!-- Complete / Picked Up -->
          <div class="col-6">
            <q-card class="mobile-metric-card shadow-soft h-full">
              <q-card-section class="q-pa-md row items-center no-wrap h-full">
                <div class="mobile-icon-box-new bg-green-1 text-green-7 q-mr-md flex flex-center">
                  <q-icon name="task_alt" size="22px" />
                </div>
                <div class="column justify-center">
                  <div class="text-grey-8 text-weight-medium" style="font-size: 13px;">Complete</div>
                  <div class="text-h5 text-weight-bolder text-dark" style="margin-top: 2px; line-height: 1;">{{ stats.picked_up_orders }}</div>
                </div>
              </q-card-section>
            </q-card>
          </div>
        </div>

        <!-- Premium Mobile Revenue Chart (Red Gradient Header) -->
        <q-card class="premium-glass-card card-rounded q-mb-lg flex column overflow-hidden">
          <q-card-section class="row items-center justify-between q-pa-md text-white" style="background: linear-gradient(135deg, #b91c1c 0%, #7f1d1d 100%);">
            <div class="text-subtitle1 text-weight-bold row items-center">
              Revenue Overview
            </div>
            <q-btn-dropdown unelevated dense no-caps class="bg-white text-red-9 text-weight-bold rounded-borders q-px-sm shadow-1" size="sm" :label="activeRevenueFilter">
              <q-list>
                <q-item v-for="filter in ['Daily', 'Weekly', 'Monthly']" :key="filter" clickable v-close-popup @click="activeRevenueFilter = filter">
                  <q-item-section><q-item-label>{{ filter }}</q-item-label></q-item-section>
                </q-item>
              </q-list>
            </q-btn-dropdown>
          </q-card-section>
          
          <q-card-section class="q-pt-md q-px-md z-top text-center">
            <div class="text-caption text-grey-6 text-uppercase tracking-wide q-mb-xs" style="font-size: 10px;">Total Income</div>
            <div class="text-h3 text-weight-bolder text-dark" style="letter-spacing: -1px;">₱{{ formatNumber(totalRevenue) }}</div>
          </q-card-section>

          <q-card-section class="q-pa-none" style="margin-top: -15px;">
             <div v-if="chartLoading" class="flex flex-center q-py-lg"><q-spinner-dots size="30px" color="red-8" /></div>
             <VueApexCharts v-else class="full-width" style="width: 100%; display: block;" type="area" height="150" width="100%" :options="chartOptions" :series="chartSeries" />
          </q-card-section>
        </q-card>

        <!-- Premium Mobile ML Forecast -->
        <q-card class="ml-blueprint-card card-rounded q-mb-lg">
          <q-card-section class="q-pa-md relative-position column">
            <div class="ml-glow-overlay" style="width: 120px; height: 120px;"></div>
            <div class="row items-center justify-between q-mb-md relative-position z-top">
              <div class="row items-center">
                <q-icon name="model_training" size="24px" color="amber-3" class="q-mr-sm drop-shadow-icon" />
                <div class="text-subtitle1 text-weight-bold text-white">Demand Forecast</div>
              </div>
              <q-chip color="white" text-color="indigo-10" size="sm" class="text-weight-bolder shadow-1 q-ma-none">
                <q-icon name="memory" size="12px" class="q-mr-xs" /> AI
              </q-chip>
            </div>

            <template v-if="mlForecast.loading">
              <div class="ml-container-glass flex flex-center relative-position overflow-hidden shadow-soft" style="min-height: 120px;">
                <div class="ml-animated-bg"></div>
                <div class="text-center z-top">
                  <q-spinner-orbit size="30px" color="amber-3" />
                  <div class="text-caption text-amber-2 q-mt-sm font-monospace text-weight-bold" style="font-size: 10px;">ANALYZING...</div>
                </div>
              </div>
            </template>
            <template v-else-if="!mlForecast.has_forecast">
              <div class="ml-container-glass flex flex-center relative-position overflow-hidden shadow-soft p-4 bg-indigo-9" style="min-height: 120px; border: 1px solid rgba(255,255,255,0.1);">
                <div class="text-center z-top q-pa-sm">
                  <q-icon name="analytics" size="28px" color="indigo-2" class="q-mb-xs opacity-50" />
                  <div class="text-caption text-indigo-2 font-monospace text-weight-bold opacity-80" style="font-size: 10px;">AWAITING MORE DATA</div>
                </div>
              </div>
            </template>
            <template v-else>
              <div class="text-white relative-position z-top">
                <!-- Sleek pill-style items -->
                <div v-for="(item, idx) in mlForecast.top_products.slice(0,3)" :key="idx" class="row items-center q-mb-sm q-pa-sm rounded-borders" style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.05); backdrop-filter: blur(4px);">
                  <div class="q-mr-sm bg-amber-3 text-indigo-10 flex flex-center text-weight-bolder" style="width: 24px; height: 24px; border-radius: 6px; font-size: 12px;">
                    {{ idx + 1 }}
                  </div>
                  <div class="col ellipsis text-weight-bold text-body2 text-white q-pr-sm">{{ item.product_name }}</div>
                  <div class="col-auto">
                    <span class="text-amber-3 text-weight-bold" style="font-size: 12px;">{{ item.predicted_quantity }} units</span>
                  </div>
                </div>
              </div>
            </template>
          </q-card-section>
        </q-card>

        <!-- Premium Mobile Recent Orders List -->
        <q-card class="premium-glass-card card-rounded q-mb-xl overflow-hidden">
          <q-card-section class="row items-center justify-between q-pa-md text-white" style="background: linear-gradient(135deg, #b91c1c 0%, #7f1d1d 100%);">
            <div class="text-subtitle1 text-weight-bold row items-center">
              Recent Orders
            </div>
            <q-btn unelevated dense class="bg-white text-red-9 text-weight-bold rounded-borders q-px-sm shadow-1" size="sm" label="View All" no-caps @click="router.push('/vendor/orders/list')" />
          </q-card-section>
          
          <q-card-section class="q-pa-sm bg-slate-50">
            <div v-if="recentOrders.length === 0" class="text-center text-grey-5 q-py-lg">No recent orders found.</div>
            
            <q-list v-else>
              <q-item v-for="order in recentOrders.slice(0,3)" :key="order.id" clickable v-ripple @click="router.push('/vendor/orders/' + order.id)" class="q-mb-sm bg-white shadow-soft rounded-borders" style="border: 1px solid #f1f5f9; padding: 12px;">
                
                <q-item-section avatar class="q-pr-sm">
                  <q-avatar size="44px" class="shadow-1 border-white bg-blue-grey-1">
                    <img v-if="order.avatar" :src="order.avatar" />
                    <q-icon v-else name="person" color="blue-grey-6" size="22px" />
                  </q-avatar>
                </q-item-section>

                <q-item-section>
                  <q-item-label class="text-weight-bold text-blue-grey-9 text-body2 ellipsis">{{ order.customer }}</q-item-label>
                  <q-item-label class="q-mt-xs">
                    <span class="text-weight-bold text-red-8 font-monospace q-px-sm bg-red-1" style="border: 1px solid rgba(220, 38, 38, 0.3); border-radius: 6px; font-size: 11px; padding-top: 2px; padding-bottom: 2px; display: inline-block;">#{{ order.id }}</span>
                  </q-item-label>
                </q-item-section>

                <q-item-section side class="items-end">
                  <q-item-label class="text-weight-bolder text-dark text-subtitle1">₱{{ formatNumber(order.price) }}</q-item-label>
                  <q-item-label>
                    <q-chip :color="getStatusColor(order.status)" text-color="white" class="text-weight-bold shadow-soft q-ma-none q-mt-xs" style="font-size: 11px; height: 22px; padding: 0 10px;">
                      {{ order.status }}
                    </q-chip>
                  </q-item-label>
                </q-item-section>

              </q-item>
            </q-list>
          </q-card-section>
        </q-card>

        <!-- ================= PREMIUM MOBILE BOTTOM NAVIGATION ================= -->
        <div class="mobile-bottom-nav row justify-around items-center">
          <div class="nav-item-wrapper" @click="router.push('/vendor/dashboard')">
            <q-btn flat round class="mobile-nav-btn nav-active shadow-3">
              <q-icon name="home" size="24px" />
            </q-btn>
          </div>
          <div class="nav-item-wrapper" @click="router.push('/vendor/orders/list')">
            <q-btn flat round class="mobile-nav-btn text-blue-grey-4">
              <q-icon name="receipt_long" size="26px" />
            </q-btn>
          </div>
          <div class="nav-item-wrapper" @click="router.push('/vendor/products/list')">
            <q-btn flat round class="mobile-nav-btn text-blue-grey-4">
              <q-icon name="inventory_2" size="26px" />
            </q-btn>
          </div>
          <div class="nav-item-wrapper" @click="router.push('/vendor/sales')">
            <q-btn flat round class="mobile-nav-btn text-blue-grey-4">
              <q-icon name="analytics" size="26px" />
            </q-btn>
          </div>
        </div>

      </div>
    </div>

    <!-- ================= LIVE STORE MODAL (Used by Both) ================= -->
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
import { useAuth } from '@/composables/useAuth'
import VueApexCharts from 'vue3-apexcharts'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

// ==========================================
// 1. STATE & REFS
// ==========================================
const { logout } = useAuth()
const router = useRouter()

const checkingAccess = ref(true)
const userName = ref('Vendor')
const ownerFullName = ref('Vendor')
const vendorStore = ref(null)
const vendorPhone = ref(null)
const liveStoreModal = ref(false)
const activeRevenueFilter = ref('Daily')

const mlForecast = ref({
  loading: true,
  has_forecast: false,
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
  colors: ['#c62828'], // Premium Red
  dataLabels: { enabled: false },
  stroke: { curve: 'smooth', width: 3 },
  xaxis: { categories: [], labels: { style: { colors: '#78909c' } }, axisBorder: { show: false }, axisTicks: { show: false } },
  yaxis: { labels: { style: { colors: '#78909c' }, formatter: value => { return '₱' + Number(value).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) } } },
  grid: { borderColor: '#eceff1', strokeDashArray: 4 },
  fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05, stops: [0, 100] } }
})

// ==========================================
// 3. COMPUTED PROPERTIES
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
  return 'text-blue-grey-9'
})

const subTextClass = computed(() => {
  if (currentHour >= 18) return 'text-indigo-1'
  return 'text-blue-grey-7'
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
  const normalizedStatus = status.toLowerCase().replace(/\s+/g, '_')
  switch (normalizedStatus) {
    case 'placed': return 'blue-5'
    case 'preparing': return 'amber-6'
    case 'picked_up': return 'green-6'
    case 'cancelled': return 'red-5'
    default: return 'blue-grey-4'
  }
}

const formatTime = timeString => {
  if (!timeString) return 'Not set'
  return new Date(`1970-01-01T${timeString}`).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}

const formatNumber = num => {
  const cleanNum = Number(String(num).replace(/[^0-9.-]+/g,""))
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
  try {
    fetchChartData()

    const res = await api.get('/vendor/profile')
    if (res.data) {
      const profile = res.data
      userName.value = profile.full_name ? profile.full_name.split(' ')[0] : 'Vendor'
      ownerFullName.value = profile.full_name || 'Vendor'
      vendorPhone.value = profile.phone_number || null
      vendorStore.value = profile.store ? { ...profile.store } : null
    }

    const statsRes = await api.get('/vendor/stats')
    if (statsRes.data) {
      stats.value.placed_orders = statsRes.data.placed_orders || 0
      stats.value.preparing_orders = statsRes.data.preparing_orders || 0
      stats.value.picked_up_orders = statsRes.data.picked_up_orders || 0
      stats.value.cancelled_orders = statsRes.data.cancelled_orders || 0
      recentOrders.value = statsRes.data.recent_orders || []
    }
    
    try {
      const mlRes = await api.get('/vendor/demand-forecast')
      if (mlRes.data) {
        mlForecast.value.has_forecast = mlRes.data.has_forecast
        mlForecast.value.summary = mlRes.data.summary
        mlForecast.value.top_products = mlRes.data.top_products || []
        mlForecast.value.generated_at = mlRes.data.generated_at
      }
    } catch (err) {
      console.error('Failed to load demand forecast:', err)
      mlForecast.value.has_forecast = false
    } finally {
      mlForecast.value.loading = false
    }
  } catch (error) {
    console.error('Dashboard init error:', error)
    userName.value = 'Vendor'
  } finally {
    checkingAccess.value = false
  }
})

watch(activeRevenueFilter, () => { fetchChartData() })
</script>

<style scoped>
/* ================= GLOBAL STYLES ================= */
.vendor-page { padding: 32px 24px; background-color: #f8fafc; min-height: 100vh; position: relative; overflow: hidden; }

/* Ambient Background Glows */
.bg-glow { position: absolute; width: 600px; height: 600px; border-radius: 50%; filter: blur(140px); z-index: 0; opacity: 0.15; pointer-events: none; }
.bg-glow-primary { top: -150px; left: -150px; background: radial-gradient(circle, rgba(185, 28, 28, 0.4) 0%, transparent 70%); }
.bg-glow-secondary { bottom: -150px; right: -150px; background: radial-gradient(circle, rgba(15, 23, 42, 0.3) 0%, transparent 70%); }

.page-container { max-width: 1400px; margin: 0 auto; position: relative; z-index: 1; }
.card-rounded { border-radius: 16px; }
.z-top-10 { z-index: 10; }
.h-full { height: 100%; }
.border-radius-8 { border-radius: 8px; }
.p-1 { padding: 4px; }
.opacity-50 { opacity: 0.5; }
.opacity-80 { opacity: 0.8; }
.flex-grow-1 { flex-grow: 1; }
.tracking-wide { letter-spacing: 0.08em; }
.leading-relaxed { line-height: 1.6; }
.line-height-tight { line-height: 1.2; }
.mobile-only { display: none; } /* Hidden by default in standard style block */
.text-truncate { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

/* Clean Solid Metrics Cards */
.clean-solid-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05); transition: transform 0.2s ease, box-shadow 0.2s ease; }
/* Glassmorphism Panels */
.premium-glass-card { background: rgba(255, 255, 255, 0.98); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border: 1px solid rgba(241, 245, 249, 1); box-shadow: 0 4px 24px rgba(15, 23, 42, 0.04); transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
.card-hover:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06); }

/* Header Themes */
.transition-theme { transition: background 1s ease, color 0.5s ease; }
.header-morning { background: linear-gradient(135deg, rgba(254, 243, 199, 0.85) 0%, rgba(224, 242, 254, 0.85) 100%); border: 1px solid rgba(255, 255, 255, 0.9); }
.header-afternoon { background: linear-gradient(135deg, rgba(224, 242, 254, 0.9) 0%, rgba(219, 234, 254, 0.7) 100%); border: 1px solid rgba(255, 255, 255, 0.9); }
.header-evening { background: linear-gradient(135deg, #1e3a8a 0%, #312e81 100%); border: 1px solid rgba(255, 255, 255, 0.1); box-shadow: 0 15px 40px rgba(30, 58, 138, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.1); }
.bg-glass { background: rgba(255, 255, 255, 0.2); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.3); }

/* Buttons & Icons */
.btn-premium { border-radius: 10px !important; font-weight: 700; transition: all 0.2s ease; }
.btn-premium:hover { transform: scale(1.02); }
.icon-premium-box { width: 46px; height: 46px; border-radius: 14px; display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255, 255, 255, 0.9); }
.shadow-soft { box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04); }
.border-white { border: 2px solid #ffffff; }

/* Filter Buttons & Native Swiping */
.bg-slate-50 { background-color: #f8fafc; }
.border-slate-light { border: 1px solid #e2e8f0; }
.transition-ease { transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1); }
.hover-text-dark:hover { color: #1e293b !important; }
.scroll-container { overflow-x: auto; -webkit-overflow-scrolling: touch; scrollbar-width: none; max-width: 100%; }
.scroll-container::-webkit-scrollbar { display: none; }

/* Panels & Tables */
.panel-header { background: rgba(248, 250, 252, 0.5); border-bottom: 1px solid rgba(226, 232, 240, 0.6); border-radius: 16px 16px 0 0; }
.header-accent-red { width: 6px; height: 24px; background: linear-gradient(180deg, #b91c1c 0%, #450a0a 100%); border-radius: 6px; }

:deep(.custom-premium-table thead tr th) { background: rgba(248, 250, 252, 0.5); font-weight: 700; color: #64748b; text-transform: uppercase; font-size: 11px; letter-spacing: 0.05em; padding: 16px 20px; border-bottom: 1px solid rgba(226, 232, 240, 0.8); }
:deep(.custom-premium-table tbody td) { padding: 16px 20px; border-bottom: 1px solid rgba(241, 245, 249, 1); transition: all 0.2s ease; }
:deep(.custom-premium-table tbody tr:hover) { background: #ffffff; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03); transform: scale(1.002); z-index: 5; position: relative; }
:deep(.custom-premium-table tbody tr:hover td) { border-bottom-color: transparent; }
.hover-icon-btn { transition: all 0.2s ease; }
:deep(.custom-premium-table tbody tr:hover .hover-icon-btn) { color: #b91c1c !important; transform: translateX(4px); background: rgba(185, 28, 28, 0.05); }

/* Charts */
.chart-container { min-height: 250px; padding: 0; width: 100%; display: flex; flex-direction: column; overflow: hidden; }

/* ML Blueprint Card */
.ml-blueprint-card { background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%); border: 1px solid rgba(255, 255, 255, 0.08); box-shadow: 0 15px 40px rgba(15, 23, 42, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.05); }
.ml-glow-overlay { position: absolute; top: 0; right: 0; width: 150px; height: 150px; background: radial-gradient(circle, rgba(99, 102, 241, 0.2) 0%, transparent 70%); filter: blur(20px); z-index: 0; }
.ml-container-glass { background: rgba(0, 0, 0, 0.25); border: 1px solid rgba(251, 191, 36, 0.25); backdrop-filter: blur(12px); min-height: 140px; border-radius: 12px; }
.ml-animated-bg { position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: conic-gradient(from 0deg, transparent 0%, rgba(251, 191, 36, 0.08) 25%, transparent 50%); animation: rotate-bg 10s linear infinite; }
@keyframes rotate-bg { 100% { transform: rotate(360deg); } }
.drop-shadow-icon { filter: drop-shadow(0 0 10px rgba(251, 191, 36, 0.4)); }
.font-monospace { font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace; letter-spacing: 0.12em; }

/* Live Store Modal */
.live-store-modal { width: 500px; max-width: 90vw; max-height: 90vh; }
.store-banner { height: 220px; }
.store-placeholder { aspect-ratio: 16/9; }
.custom-glass-input { border: 1px solid #e2e8f0; }
.store-preview-map-container { height: 200px; border: 1px solid #cfd8dc; position: relative; z-index: 1; }

/* Status Dot Indicator */
.status-dot { width: 10px; height: 10px; border-radius: 50%; box-shadow: 0 0 10px rgba(16, 185, 129, 0.6); animation: pulse-dot 2s infinite; }
@keyframes pulse-dot { 0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); } 70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); } 100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); } }


/* ============================================================= */
/* ==================== MOBILE ONLY STYLES ===================== */
/* ============================================================= */
@media (max-width: 767px) {
  /* Keeps the slate background and glows from desktop, adds room for navbar */
  .vendor-page.mobile-page-padding { padding: 12px 8px calc(80px + env(safe-area-inset-bottom)) 8px !important; }
  .mobile-only { display: block; }
  
  /* Metric Icon Styling */
  .mobile-metric-card { border: 1px solid #f1f5f9; border-radius: 16px; background: #ffffff; box-shadow: 0 4px 12px rgba(15, 23, 42, 0.03); }
  .mobile-icon-box-new { width: 44px; height: 44px; border-radius: 12px; flex-shrink: 0; }
  
  /* Divider styling for Mobile Lists */
  .border-bottom { border-bottom: 1px solid rgba(15, 23, 42, 0.05); }

  /* Premium Glass Floating Bottom Navigation */
  .mobile-bottom-nav {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    height: calc(75px + env(safe-area-inset-bottom));
    padding-bottom: env(safe-area-inset-bottom);
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border-top: 1px solid rgba(255, 255, 255, 0.5);
    z-index: 2000;
    box-shadow: 0 -10px 25px rgba(15, 23, 42, 0.05);
  }
  
  .nav-item-wrapper {
    flex: 1;
    display: flex;
    justify-content: center;
    align-items: center;
  }
  
  .mobile-nav-btn {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    padding: 0;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }
  
  .nav-active {
    background: linear-gradient(135deg, #b91c1c 0%, #7f1d1d 100%) !important; /* Premium Brand Red */
    color: #ffffff !important;
    box-shadow: 0 8px 16px rgba(185, 28, 28, 0.35) !important;
    transform: translateY(-4px);
  }
  .nav-active .q-icon {
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
  }
}
</style>