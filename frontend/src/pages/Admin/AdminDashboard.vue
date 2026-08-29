<template>
  <q-page class="admin-dashboard" :class="{ 'dark-mode-active': $q.dark.isActive }">
    <!-- Ambient Canvas & Background Accents -->
    <div class="ambient-mesh-bg"></div>
    <div class="enterprise-dot-pattern"></div>

    <div class="dashboard-container">
      <!-- ================= CLEAN GREETINGS HEADER ================= -->
      <div
        class="welcome-banner q-mb-lg row items-center justify-between q-pa-lg transition-theme shadow-premium"
        :class="[$q.dark.isActive ? 'theme-dark-banner' : timeOfDayTheme]"
      >
        <div class="banner-glow-overlay"></div>
        <div class="banner-animated-shimmer"></div>

        <!-- Left: Tag, Greeting, and Status -->
        <div class="row items-center col-12 col-md-8 banner-content-layer">
          <div>
            <div class="row items-center no-wrap q-mb-xs">
              <span class="header-tag-pill row items-center no-wrap q-px-sm q-py-xs">
                <q-icon name="admin_panel_settings" size="13px" class="q-mr-xs text-white" />
                <span class="text-overline text-white tracking-widest line-height-tight">
                  TINDAHAN ADMIN PANEL
                </span>
              </span>
            </div>

            <h1 class="text-h4 text-weight-bolder text-white q-mt-xs q-mb-xs line-height-tight letter-spacing-tight text-glow">
              {{ timeBasedGreeting }}, <span class="header-name-highlight">{{ userName }}</span>
            </h1>

            <div class="text-white opacity-90 row items-center text-body2 text-weight-medium q-mt-xs">
              <span>Here is the executive overview of your marketplace today.</span>

              <div
                v-if="stats.pending_approvals > 0"
                class="attention-badge q-ml-md flex items-center text-caption text-weight-bolder shadow-premium cursor-pointer hover-scale"
                @click="$router.push('/admin/approvals')"
              >
                <div class="pulse-dot-white q-mr-sm"></div>
                Action Required: {{ stats.pending_approvals }} tasks
              </div>
            </div>
          </div>
        </div>

        <!-- Right: Time Counter, Dark Mode Toggle & Sync Action -->
        <div class="col-12 col-md-auto row items-center justify-end q-mt-lg q-mt-md-none banner-content-layer q-gutter-x-sm">
          <!-- Dark Mode Toggle Button -->
          <q-btn
            flat
            round
            color="white"
            :icon="$q.dark.isActive ? 'light_mode' : 'dark_mode'"
            size="sm"
            class="header-tool-btn hover-scale"
            @click="toggleDarkMode"
          >
            <q-tooltip class="bg-dark text-white text-weight-medium border-radius-sm">
              {{ $q.dark.isActive ? 'Switch to Light Mode' : 'Switch to Dark Mode' }}
            </q-tooltip>
          </q-btn>

          <!-- Sync Action Button -->
          <q-btn
            flat
            round
            color="white"
            icon="sync"
            size="sm"
            class="header-tool-btn hover-rotate"
            :loading="loading"
            @click="refreshDashboard"
          >
            <q-tooltip class="bg-dark text-white text-weight-medium border-radius-sm">Sync Data</q-tooltip>
          </q-btn>

          <!-- Time & Date Capsule -->
          <div class="time-card-glass premium-lift">
            <div class="column text-right">
              <span
                class="text-caption text-white opacity-80 text-weight-bold text-uppercase tracking-widest q-mb-xs font-mono"
                style="font-size: 10px"
              >
                {{ currentDate }}
              </span>
              <span class="text-h6 text-white text-weight-bolder tracking-tight line-height-tight font-mono text-glow">
                {{ currentTime }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- ================= ASYMMETRIC BENTO METRICS ================= -->
      <div class="row q-col-gutter-lg q-mb-xl">
        <!-- Hero Metric: Pending Approvals -->
        <div class="col-12 col-lg-5 flex">
          <q-card
            flat
            ref="heroCardRef"
            class="premium-glass-card hero-card fit column justify-between cursor-pointer overflow-hidden"
            @mousemove="handleHeroHover"
            @mouseleave="resetHeroHover"
            :style="{ '--mouse-x': mouseX, '--mouse-y': mouseY }"
            @click="$router.push('/admin/approvals')"
          >
            <div class="metric-bg-watermark watermark-red">
              <q-icon name="pending_actions" />
            </div>

            <div class="interactive-hue-layer"></div>
            <div class="hero-accent-line"></div>

            <q-card-section class="q-pa-lg column justify-between full-height card-content-layer">
              <div class="row justify-between items-center q-mb-md">
                <div class="icon-badge-box bg-red-badge text-red-9 border-red-soft shadow-xs flex flex-center">
                  <q-icon name="pending_actions" size="24px" />
                </div>
                <q-chip
                  v-if="stats.pending_approvals > 0"
                  color="red-9"
                  text-color="white"
                  class="text-weight-bolder shadow-xs q-ma-none tracking-wide"
                  size="sm"
                >
                  PRIORITY
                </q-chip>
              </div>

              <div class="hero-text-content">
                <div class="text-overline text-red-9 text-uppercase tracking-widest q-mb-xs text-weight-bolder">
                  Needs Attention
                </div>
                <div class="text-h3 text-weight-bolder text-heading line-height-tight q-mb-xs letter-spacing-tight hero-number">
                  {{ stats.pending_approvals }}
                </div>
                <div class="text-body2 text-subtext text-weight-medium">
                  Pending vendor applications awaiting review and authorization.
                </div>
              </div>
            </q-card-section>
          </q-card>
        </div>

        <!-- Secondary Metrics Grid -->
        <div class="col-12 col-lg-7">
          <div class="row q-col-gutter-lg full-height">
            <!-- Approved Vendors -->
            <div class="col-12 col-sm-6 flex">
              <q-card
                flat
                class="premium-glass-card fit cursor-pointer card-hover-lift overflow-hidden border-accent-blue"
                @click="$router.push('/admin/vendors')"
              >
                <div class="metric-bg-watermark watermark-blue">
                  <q-icon name="storefront" />
                </div>

                <div class="card-glow-blue"></div>
                <q-card-section class="q-pa-lg row items-center no-wrap card-content-layer full-height">
                  <div class="icon-badge-box-sm bg-blue-badge text-blue-9 border-blue-soft q-mr-md flex flex-center">
                    <q-icon name="storefront" size="22px" />
                  </div>
                  <div class="col">
                    <div class="text-caption text-weight-bolder text-subtext text-uppercase tracking-wider q-mb-xs">
                      Approved Vendors
                    </div>
                    <div class="text-h4 text-weight-bolder text-heading letter-spacing-tight line-height-tight">
                      {{ stats.total_vendors }}
                    </div>
                  </div>
                </q-card-section>
              </q-card>
            </div>

            <!-- Active Consumers -->
            <div class="col-12 col-sm-6 flex">
              <q-card
                flat
                class="premium-glass-card fit cursor-pointer card-hover-lift overflow-hidden border-accent-green"
                @click="$router.push('/admin/consumers')"
              >
                <div class="metric-bg-watermark watermark-green">
                  <q-icon name="groups" />
                </div>

                <div class="card-glow-green"></div>
                <q-card-section class="q-pa-lg row items-center no-wrap card-content-layer full-height">
                  <div class="icon-badge-box-sm bg-green-badge text-green-9 border-green-soft q-mr-md flex flex-center">
                    <q-icon name="groups" size="22px" />
                  </div>
                  <div class="col">
                    <div class="text-caption text-weight-bolder text-subtext text-uppercase tracking-wider q-mb-xs">
                      Active Consumers
                    </div>
                    <div class="text-h4 text-weight-bolder text-heading letter-spacing-tight line-height-tight">
                      {{ stats.total_consumers }}
                    </div>
                  </div>
                </q-card-section>
              </q-card>
            </div>

            <!-- Total Platform Users -->
            <div class="col-12 flex">
              <q-card flat class="premium-glass-card fit card-hover-lift overflow-hidden border-accent-slate">
                <div class="metric-bg-watermark watermark-slate">
                  <q-icon name="public" />
                </div>

                <div class="card-glow-slate"></div>
                <q-card-section class="q-pa-lg row items-center justify-between no-wrap card-content-layer full-height">
                  <div class="row items-center no-wrap">
                    <div class="icon-badge-box bg-slate-box border-slate-soft q-mr-md flex flex-center shadow-xs">
                      <q-icon name="public" size="24px" />
                    </div>
                    <div>
                      <div class="text-caption text-weight-bolder text-subtext text-uppercase tracking-wider q-mb-xs">
                        Total Platform Users
                      </div>
                      <div class="text-body2 text-subtext text-weight-medium">
                        Combined aggregate of registered marketplace accounts
                      </div>
                    </div>
                  </div>
                  <div class="text-h4 text-weight-bolder text-heading letter-spacing-tight line-height-tight q-ml-md">
                    {{ stats.total_users }}
                  </div>
                </q-card-section>
              </q-card>
            </div>
          </div>
        </div>
      </div>

      <!-- ================= ANALYTICS & INSIGHTS CHARTS ================= -->
      <div class="row q-col-gutter-lg q-mb-xl">
        <!-- Growth Trends (Bar Chart) -->
        <div class="col-12 col-lg-8">
          <q-card flat class="premium-glass-card h-full column justify-between">
            <q-card-section class="panel-header row items-center justify-between q-pa-md q-pa-md-lg">
              <div class="row items-center no-wrap">
                <div class="header-accent-red q-mr-md"></div>
                <div>
                  <div class="text-subtitle1 text-weight-bolder text-heading line-height-tight">
                    Platform User Registrations
                  </div>
                  <div class="text-caption text-subtext text-weight-medium">
                    Periodic registration activity
                  </div>
                </div>
              </div>

              <div class="row items-center no-wrap q-gutter-x-sm">
                <span class="legend-badge legend-vendor text-caption">Vendors</span>
                <span class="legend-badge legend-consumer text-caption">Consumers</span>
              </div>
            </q-card-section>

            <q-card-section class="q-pa-md flex-grow-1 relative-position">
              <div v-if="chartLoading" class="absolute-full flex flex-center z-top bg-chart-overlay">
                <q-spinner-dots size="36px" color="red-9" />
              </div>
              <VueApexCharts
                type="bar"
                height="240"
                width="100%"
                :options="barChartOptions"
                :series="barChartSeries"
              />
            </q-card-section>
          </q-card>
        </div>

        <!-- Ecosystem Distribution (Donut Chart) -->
        <div class="col-12 col-lg-4">
          <q-card flat class="premium-glass-card h-full column justify-between">
            <q-card-section class="panel-header row items-center justify-between q-pa-md q-pa-md-lg">
              <div class="row items-center no-wrap">
                <div class="header-accent-red q-mr-md"></div>
                <div>
                  <div class="text-subtitle1 text-weight-bolder text-heading line-height-tight">
                    Ecosystem Ratio
                  </div>
                  <div class="text-caption text-subtext text-weight-medium">
                    Active user distribution
                  </div>
                </div>
              </div>
            </q-card-section>

            <q-card-section class="q-pa-md flex-grow-1 flex flex-center relative-position">
              <VueApexCharts
                type="donut"
                height="220"
                width="100%"
                :options="donutChartOptions"
                :series="donutChartSeries"
              />
            </q-card-section>
          </q-card>
        </div>
      </div>

      <!-- ================= PENDING APPLICATIONS TABLE ================= -->
      <q-card flat class="premium-glass-card table-glass-container q-mb-xl">
        <div class="panel-header row items-center justify-between q-pa-lg no-wrap">
          <div class="row items-center no-wrap col">
            <div class="header-accent-red q-mr-md"></div>
            <div>
              <h2 class="text-h6 text-weight-bolder text-heading q-ma-none letter-spacing-tight line-height-tight">
                Latest Applications
              </h2>
              <div class="text-caption text-subtext text-weight-medium q-mt-xs">
                Merchants requesting to join Tindahan.
              </div>
            </div>
          </div>
          
          <div class="col-auto q-pl-md">
            <q-btn
              unelevated
              color="red-9"
              icon-right="arrow_forward"
              label="View Directory"
              size="sm"
              no-caps
              class="btn-view-directory text-weight-bolder no-wrap"
              @click="$router.push('/admin/approvals')"
            />
          </div>
        </div>

        <q-table
          flat
          class="custom-premium-table"
          :rows="pendingApplications"
          :columns="columns"
          row-key="approval_id"
          :loading="loading"
          hide-bottom
          :rows-per-page-options="[0]"
        >
          <template #loading>
            <q-inner-loading showing color="red-9" class="bg-table-glass">
              <q-spinner-dots size="40px" />
            </q-inner-loading>
          </template>

          <template #body-cell-store_name="props">
            <q-td :props="props">
              <div class="row items-center no-wrap">
                <div class="store-mini-avatar q-mr-sm flex flex-center shadow-xs">
                  <q-icon name="storefront" size="16px" color="red-9" />
                </div>
                <span class="text-weight-bold text-heading text-body2">{{ props.row.store_name }}</span>
              </div>
            </q-td>
          </template>

          <template #body-cell-owner_name="props">
            <q-td :props="props">
              <span class="text-subtext text-weight-medium text-caption">{{ props.row.owner_name }}</span>
            </q-td>
          </template>

          <template #body-cell-applied_at="props">
            <q-td :props="props">
              <q-chip
                dense
                class="premium-chip text-red-9 text-weight-bold q-px-sm shadow-xs"
                icon="schedule"
                size="sm"
              >
                {{ formatDate(props.row.applied_at) }}
              </q-chip>
            </q-td>
          </template>

          <template #body-cell-action="props">
            <q-td :props="props" align="right">
              <q-btn
                outline
                color="red-9"
                icon="remove_red_eye"
                label="Review"
                size="sm"
                padding="4px 12px"
                no-caps
                class="btn-premium-outline action-btn-hover"
                @click="viewApplication(props.row)"
              />
            </q-td>
          </template>

          <!-- Explicit Empty State Feedback -->
          <template #no-data>
            <div class="full-width column flex-center q-py-xl empty-state-glass">
              <div class="empty-icon-shield q-mb-md flex flex-center shadow-xs">
                <q-icon name="assignment_late" size="32px" />
              </div>
              <div class="text-h6 text-weight-bolder text-heading letter-spacing-tight q-mb-xs">
                There is no applications yet.
              </div>
              <div class="text-body2 text-subtext text-weight-medium text-center q-px-md">
                New vendor registration requests will automatically appear here for verification and review.
              </div>
            </div>
          </template>
        </q-table>
      </q-card>

      <!-- ================= QUICK ACTIONS ================= -->
      <div class="text-h6 text-weight-bolder text-heading q-mb-md letter-spacing-tight">
        Quick Navigation
      </div>
      <div class="row q-col-gutter-lg">
        <div class="col-12 col-md-4">
          <q-card
            flat
            class="premium-glass-card hover-lift-action cursor-pointer action-card overflow-hidden"
            @click="$router.push('/admin/approvals')"
          >
            <div class="action-card-glow text-red-9"></div>
            <q-card-section class="row items-center no-wrap q-pa-lg card-content-layer">
              <div class="action-icon-stamp bg-red-badge text-red-9 border-red-soft q-mr-md flex flex-center">
                <q-icon name="fact_check" size="22px" />
              </div>
              <div class="col">
                <div class="text-subtitle2 text-weight-bolder text-heading line-height-tight">
                  Review Center
                </div>
                <div class="text-caption text-subtext text-weight-medium line-height-tight q-mt-xs">
                  Process store applications
                </div>
              </div>
              <q-icon name="chevron_right" color="grey-5" size="22px" class="action-arrow" />
            </q-card-section>
          </q-card>
        </div>

        <div class="col-12 col-md-4">
          <q-card
            flat
            class="premium-glass-card hover-lift-action cursor-pointer action-card overflow-hidden"
            @click="$router.push('/admin/vendors')"
          >
            <div class="action-card-glow text-red-9"></div>
            <q-card-section class="row items-center no-wrap q-pa-lg card-content-layer">
              <div class="action-icon-stamp bg-blue-badge text-blue-9 border-blue-soft q-mr-md flex flex-center">
                <q-icon name="storefront" size="22px" />
              </div>
              <div class="col">
                <div class="text-subtitle2 text-weight-bolder text-heading line-height-tight">
                  Merchant Directory
                </div>
                <div class="text-caption text-subtext text-weight-medium line-height-tight q-mt-xs">
                  Manage active vendors
                </div>
              </div>
              <q-icon name="chevron_right" color="grey-5" size="22px" class="action-arrow" />
            </q-card-section>
          </q-card>
        </div>

        <div class="col-12 col-md-4">
          <q-card
            flat
            class="premium-glass-card hover-lift-action cursor-pointer action-card overflow-hidden"
            @click="$router.push('/admin/consumers')"
          >
            <div class="action-card-glow text-red-9"></div>
            <q-card-section class="row items-center no-wrap q-pa-lg card-content-layer">
              <div class="action-icon-stamp bg-green-badge text-green-9 border-green-soft q-mr-md flex flex-center">
                <q-icon name="groups" size="22px" />
              </div>
              <div class="col">
                <div class="text-subtitle2 text-weight-bolder text-heading line-height-tight">
                  Consumer Accounts
                </div>
                <div class="text-caption text-subtext text-weight-medium line-height-tight q-mt-xs">
                  View registered shoppers
                </div>
              </div>
              <q-icon name="chevron_right" color="grey-5" size="22px" class="action-arrow" />
            </q-card-section>
          </q-card>
        </div>
      </div>
    </div>

    <!-- ================= REVIEW DIALOG ================= -->
    <q-dialog
      v-model="showApplicationDialog"
      transition-show="scale"
      transition-hide="scale"
    >
      <q-card class="review-dialog-glass vendor-info-dialog">
        <q-card-section class="row items-center justify-between q-pa-md panel-header">
          <div class="text-h6 text-weight-bold text-heading row items-center">
            <div class="header-accent-red q-mr-sm"></div>
            Application Review
          </div>
          <q-btn icon="close" flat round dense class="text-subtext" @click="showApplicationDialog = false" />
        </q-card-section>

        <q-card-section class="q-pa-lg scroll" style="max-height: 65vh" v-if="selectedApplication">
          <div class="text-center q-mb-lg">
            <div class="info-store-name text-heading q-mb-xs">{{
              selectedApplication.store?.store_name || selectedApplication.store_name || 'N/A'
            }}</div>
            <div class="info-owner-name text-subtext q-mb-md">
              Owned by: {{ selectedApplication.store?.owner?.full_name || selectedApplication.owner_name }}
            </div>

            <div class="image-frame-container">
              <q-img
                v-if="selectedApplication.store?.store_picture_url && selectedApplication.store.store_picture_url !== 'null' && selectedApplication.store.store_picture_url.trim() !== ''"
                :src="selectedApplication.store.store_picture_url"
                style="width: 100%; height: 220px"
                fit="cover"
                class="rounded-borders"
              >
                <template #error>
                  <div class="absolute-full flex flex-center empty-state-glass">
                    <q-icon name="storefront" size="64px" color="grey-5" />
                  </div>
                </template>
              </q-img>
              <div v-else class="empty-state-glass flex flex-center full-width rounded-borders" style="height: 220px">
                <q-icon name="storefront" size="64px" color="grey-5" />
              </div>
            </div>
          </div>

          <div class="row q-col-gutter-y-md q-col-gutter-x-xl q-mb-lg">
            <div class="col-12 col-sm-6">
              <div class="text-caption text-subtext text-uppercase text-weight-bold">Contact Email</div>
              <div class="text-subtitle2 text-weight-bold text-heading">{{ selectedApplication.email }}</div>
            </div>
            <div class="col-12 col-sm-6">
              <div class="text-caption text-subtext text-uppercase text-weight-bold">Contact Phone</div>
              <div class="text-subtitle2 text-weight-bold text-heading">{{ selectedApplication.phone || 'N/A' }}</div>
            </div>
            <div class="col-12 col-sm-6">
              <div class="text-caption text-subtext text-uppercase text-weight-bold">Operating Days</div>
              <div class="text-subtitle2 text-weight-bold text-heading">
                {{ formatOperatingDays(selectedApplication.store?.operating_days) }}
              </div>
            </div>
            <div class="col-12 col-sm-6">
              <div class="text-caption text-subtext text-uppercase text-weight-bold">Business Hours</div>
              <div class="text-subtitle2 text-weight-bold text-heading">
                {{ selectedApplication.store?.opening_time || 'N/A' }} - {{ selectedApplication.store?.closing_time || 'N/A' }}
              </div>
            </div>
          </div>

          <div class="map-container-box" v-if="isValidLocation(selectedApplication)">
            <iframe
              :src="getMapUrl(selectedApplication.store.latitude, selectedApplication.store.longitude)"
              width="100%"
              height="200"
              style="border: none; border-radius: 8px"
              allowfullscreen=""
              loading="lazy"
            ></iframe>
            <div class="q-mt-md text-right">
              <q-btn
                label="Open in Google Maps"
                no-caps
                class="btn-outline-custom q-px-md"
                text-color="blue-8"
                icon="map"
                :href="`https://www.google.com/maps/dir/?api=1&destination=${selectedApplication.store.latitude},${selectedApplication.store.longitude}`"
                target="_blank"
              />
            </div>
          </div>
        </q-card-section>

        <q-separator />

        <q-card-actions align="right" class="q-pa-md dialog-actions-glass">
          <q-btn flat label="Cancel" class="btn-outline-custom q-px-md text-subtext" no-caps @click="showApplicationDialog = false" />
          <q-btn flat label="Reject Application" color="red-8" no-caps class="btn-reject-custom q-px-md q-ml-sm" @click="openRejectModal(selectedApplication); showApplicationDialog = false;" />
          <q-btn unelevated label="Approve Vendor" icon="check_circle" color="green-7" no-caps class="btn-approve-custom q-px-md q-ml-sm" @click="handleApprove(selectedApplication); showApplicationDialog = false;" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- ================= REJECT MODAL ================= -->
    <q-dialog v-model="showRejectModal" persistent transition-show="scale" transition-hide="scale">
      <q-card class="review-dialog-glass text-center">
        <q-card-section class="q-pt-xl q-pb-md relative-position" style="z-index: 2">
          <div class="modal-stamp-disc bg-red-badge text-red-9 q-mb-md q-mx-auto flex flex-center">
            <q-icon name="warning" size="32px" />
          </div>
          <div class="text-h5 text-weight-bold text-heading q-mb-sm">Reject Application</div>
          <p class="text-body2 text-subtext q-px-md">
            Action requires justification. Please provide a reason for rejecting this application.
          </p>
          <q-input
            v-model="rejectionReason"
            type="textarea"
            outlined
            dense
            placeholder="e.g., Incomplete documentation, suspicious activity..."
            class="custom-glass-input text-left q-mt-md"
            autofocus
          />
        </q-card-section>

        <q-card-actions align="center" class="q-pa-md dialog-actions-glass">
          <q-btn flat label="Cancel" class="btn-outline-custom q-px-md q-mr-sm text-subtext" no-caps v-close-popup />
          <q-btn unelevated label="Confirm Rejection" color="red-9" no-caps class="btn-reject-confirm q-px-md" :loading="actionLoading" @click="handleRejectConfirm" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- ================= APPROVE MODAL ================= -->
    <q-dialog v-model="showApproveModal" persistent transition-show="scale" transition-hide="scale">
      <q-card class="review-dialog-glass text-center">
        <q-card-section class="q-pt-xl q-pb-md relative-position" style="z-index: 2">
          <div class="modal-stamp-disc bg-green-badge text-green-7 q-mb-md q-mx-auto flex flex-center">
            <q-icon name="check_circle" size="32px" />
          </div>
          <div class="text-h5 text-weight-bold text-heading q-mb-sm">Approve Vendor</div>
          <p class="text-body1 text-subtext q-px-md">
            Are you sure you want to approve this application? <strong>{{ approveTarget?.store_name }}</strong> will gain immediate access.
          </p>
        </q-card-section>

        <q-card-actions align="center" class="q-pa-md dialog-actions-glass">
          <q-btn flat label="Cancel" class="btn-outline-custom q-px-md q-mr-sm text-subtext" no-caps v-close-popup />
          <q-btn unelevated label="Confirm Approval" color="green-7" no-caps class="btn-approve-custom q-px-md" :loading="actionLoading" @click="handleApproveConfirm" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { useQuasar } from 'quasar'
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { api } from '@/boot/axios'
import VueApexCharts from 'vue3-apexcharts'

const $q = useQuasar()

// State
const stats = ref({
  pending_approvals: 0,
  total_vendors: 0,
  total_consumers: 0,
  total_users: 0
})
const pendingApplications = ref([])
const showApplicationDialog = ref(false)
const loading = ref(false)
const chartLoading = ref(false)

const selectedApplication = ref({
  approval_id: null,
  store_id: null,
  store_name: '',
  owner_name: '',
  email: '',
  phone: '',
  applied_at: ''
})

const showApproveModal = ref(false)
const showRejectModal = ref(false)
const approveTarget = ref(null)
const rejectTarget = ref(null)
const actionLoading = ref(false)
const rejectionReason = ref('')

// Interactive Hue Logic
const heroCardRef = ref(null)
const mouseX = ref('80%')
const mouseY = ref('80%')

const handleHeroHover = e => {
  if (!heroCardRef.value) return
  const rect = heroCardRef.value.$el.getBoundingClientRect()
  mouseX.value = `${e.clientX - rect.left}px`
  mouseY.value = `${e.clientY - rect.top}px`
}
const resetHeroHover = () => {
  mouseX.value = '80%'
  mouseY.value = '80%'
}

// Table Configuration
const columns = [
  { name: 'store_name', label: 'Store Profile', field: 'store_name', align: 'left' },
  { name: 'owner_name', label: 'Owner Details', field: 'owner_name', align: 'left' },
  { name: 'applied_at', label: 'Date Applied', field: 'applied_at', align: 'left' },
  { name: 'action', label: 'Review Action', field: 'action', align: 'right' }
]

const currentDate = ref('')
const currentTime = ref('')
const currentHour = ref(new Date().getHours())
let timer = null

// Dynamic Logic for Time of Day
const timeBasedGreeting = computed(() => {
  if (currentHour.value < 12) return 'Good morning'
  if (currentHour.value < 18) return 'Good afternoon'
  return 'Good evening'
})

const timeOfDayTheme = computed(() => {
  if (currentHour.value >= 5 && currentHour.value < 12) return 'theme-morning'
  if (currentHour.value >= 12 && currentHour.value < 18) return 'theme-afternoon'
  return 'theme-evening'
})

// ================= Dark Mode Operations =================
const toggleDarkMode = () => {
  $q.dark.toggle()
  localStorage.setItem('admin_dark_mode', $q.dark.isActive ? 'true' : 'false')
  updateChartThemes()
}

// ================= Registration Analytics Chart =================
const registrationCategories = ref(['2 Wks Ago', 'Last Wk', 'This Wk'])
const registrationVendorData = ref([0, 0, 0])
const registrationConsumerData = ref([0, 0, 0])

const barChartSeries = computed(() => [
  { name: 'Vendors', data: registrationVendorData.value },
  { name: 'Consumers', data: registrationConsumerData.value }
])

const barChartOptions = ref({
  chart: { type: 'bar', toolbar: { show: false }, stacked: false },
  colors: ['#2563eb', '#16a34a'],
  plotOptions: { bar: { horizontal: false, columnWidth: '38%', borderRadius: 6 } },
  dataLabels: { enabled: false },
  stroke: { show: true, width: 2, colors: ['transparent'] },
  xaxis: { categories: registrationCategories.value, labels: { style: { colors: '#64748b', fontSize: '11px', fontWeight: 600 } }, axisBorder: { show: false }, axisTicks: { show: false } },
  yaxis: { labels: { style: { colors: '#64748b', fontSize: '11px', fontWeight: 600 } } },
  grid: { borderColor: '#f1f5f9', strokeDashArray: 4 },
  legend: { show: false }
})

const donutChartSeries = computed(() => {
  const v = Number(stats.value.total_vendors) || 0
  const c = Number(stats.value.total_consumers) || 0
  return (v === 0 && c === 0) ? [1, 1] : [v, c]
})

const donutChartOptions = ref({
  chart: { type: 'donut' },
  colors: ['#2563eb', '#16a34a'],
  labels: ['Approved Vendors', 'Active Consumers'],
  dataLabels: { enabled: false },
  legend: { position: 'bottom', labels: { colors: '#64748b' }, fontWeight: 600, fontSize: '11px' },
  plotOptions: {
    pie: {
      donut: {
        size: '72%',
        labels: {
          show: true,
          total: {
            show: true,
            label: 'Total Accounts',
            color: '#64748b',
            fontSize: '11px',
            fontWeight: 700,
            formatter: () => String(stats.value.total_users || 0)
          }
        }
      }
    }
  },
  stroke: { width: 0 }
})

const updateChartThemes = () => {
  const isDark = $q.dark.isActive
  const fontColor = isDark ? '#94a3b8' : '#64748b'
  const gridColor = isDark ? 'rgba(255,255,255,0.06)' : '#f1f5f9'

  barChartOptions.value = {
    ...barChartOptions.value,
    xaxis: { ...barChartOptions.value.xaxis, labels: { style: { colors: fontColor, fontSize: '11px', fontWeight: 600 } } },
    yaxis: { labels: { style: { colors: fontColor, fontSize: '11px', fontWeight: 600 } } },
    grid: { borderColor: gridColor, strokeDashArray: 4 }
  }

  donutChartOptions.value = {
    ...donutChartOptions.value,
    legend: { ...donutChartOptions.value.legend, labels: { colors: fontColor } },
    plotOptions: {
      pie: {
        donut: {
          ...donutChartOptions.value.plotOptions.pie.donut,
          labels: {
            ...donutChartOptions.value.plotOptions.pie.donut.labels,
            total: {
              ...donutChartOptions.value.plotOptions.pie.donut.labels.total,
              color: fontColor
            }
          }
        }
      }
    }
  }
}

watch(() => $q.dark.isActive, updateChartThemes)

const updateDateTime = () => {
  const now = new Date()
  currentHour.value = now.getHours()
  currentDate.value = now.toLocaleDateString('en-US', {
    weekday: 'long',
    month: 'long',
    day: 'numeric',
    year: 'numeric'
  })
  currentTime.value = now.toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  })
}

const formatDate = date => {
  if (!date) return '-'
  return new Date(date).toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const userName = computed(() => {
  try {
    const user = JSON.parse(localStorage.getItem('auth_user') || '{}')
    return user.full_name || 'Admin'
  } catch {
    return 'Admin'
  }
})

const formatOperatingDays = days => {
  if (!days) return 'N/A'
  try {
    const parsed = typeof days === 'string' ? JSON.parse(days) : days
    if (parsed !== null && typeof parsed === 'object' && !Array.isArray(parsed)) {
      const openDays = Object.entries(parsed)
        .filter(([_, data]) => data.is_open)
        .map(([day, data]) => {
          if (data.opening_time && data.closing_time) {
            const open = data.opening_time.substring(0, 5)
            const close = data.closing_time.substring(0, 5)
            return `${day} (${open}-${close})`
          }
          return day
        })
      return openDays.length > 0 ? openDays.join(', ') : 'N/A'
    }
    if (Array.isArray(parsed)) {
      return parsed.length > 0 ? parsed.join(', ') : 'N/A'
    }
    return 'N/A'
  } catch {
    return 'N/A'
  }
}

const isValidLocation = vendor => {
  if (!vendor?.store?.latitude || !vendor?.store?.longitude) return false
  return !isNaN(parseFloat(vendor.store.latitude)) && !isNaN(parseFloat(vendor.store.longitude))
}

const getMapUrl = (lat, lng) => {
  const parsedLat = parseFloat(lat)
  const parsedLng = parseFloat(lng)
  if (isNaN(parsedLat) || isNaN(parsedLng)) return ''
  const bbox = `${parsedLng - 0.01}%2C${parsedLat - 0.01}%2C${parsedLng + 0.01}%2C${parsedLat + 0.01}`
  return `https://www.openstreetmap.org/export/embed.html?bbox=${bbox}&layer=mapnik&marker=${parsedLat}%2C${parsedLng}`
}

const loadDashboard = async () => {
  loading.value = true
  chartLoading.value = true
  try {
    const [statsRes, pendingRes] = await Promise.all([
      api.get('/admin/stats'),
      api.get('/admin/vendors/pending')
    ])
    stats.value = statsRes.data
    pendingApplications.value = (pendingRes.data || [])
      .filter(app => app.status === 'pending')
      .slice(0, 5)

    try {
      const regRes = await api.get('/admin/stats/registrations')
      if (regRes.data) {
        registrationCategories.value = regRes.data.categories || ['2 Wks Ago', 'Last Wk', 'This Wk']
        registrationVendorData.value = regRes.data.vendors || [0, 0, 0]
        registrationConsumerData.value = regRes.data.consumers || [0, 0, 0]
      }
    } catch {
      const totalV = stats.value.total_vendors || 0
      const totalC = stats.value.total_consumers || 0
      registrationVendorData.value = [Math.max(0, totalV - 2), Math.max(0, totalV - 1), totalV]
      registrationConsumerData.value = [Math.max(0, totalC - 5), Math.max(0, totalC - 2), totalC]
    }
  } catch (error) {
    console.error('Failed to load admin dashboard:', error)
  } finally {
    loading.value = false
    chartLoading.value = false
  }
}

const refreshDashboard = async () => {
  await loadDashboard()
  $q.notify({
    type: 'positive',
    message: 'Dashboard synced successfully.',
    position: 'bottom-right',
    timeout: 2000
  })
}

const viewApplication = row => {
  selectedApplication.value = { ...row }
  showApplicationDialog.value = true
}

const handleApprove = row => {
  approveTarget.value = row
  showApproveModal.value = true
}

const handleApproveConfirm = async () => {
  if (!approveTarget.value) return
  actionLoading.value = true
  try {
    await api.post(`/admin/vendors/${approveTarget.value.store_id}/approve`)
    showApproveModal.value = false
    showApplicationDialog.value = false
    await loadDashboard()
    $q.notify({
      type: 'positive',
      message: 'Vendor approved successfully.',
      position: 'top-right'
    })
  } catch (error) {
    console.error(error)
    $q.notify({
      type: 'negative',
      message: 'Unable to approve vendor.',
      position: 'top-right'
    })
  } finally {
    actionLoading.value = false
  }
}

const openRejectModal = row => {
  rejectTarget.value = row
  rejectionReason.value = ''
  showRejectModal.value = true
}

const handleRejectConfirm = async () => {
  if (!rejectionReason.value || !rejectTarget.value) {
    $q.notify({
      type: 'warning',
      message: 'Please provide a rejection reason.',
      position: 'top-right'
    })
    return
  }

  actionLoading.value = true
  try {
    await api.post(`/admin/vendors/${rejectTarget.value.store_id}/reject`, {
      rejection_reason: rejectionReason.value
    })
    showRejectModal.value = false
    showApplicationDialog.value = false
    await loadDashboard()
    $q.notify({
      type: 'positive',
      message: 'Vendor rejected successfully.',
      position: 'top-right'
    })
  } catch (error) {
    console.error(error)
    $q.notify({
      type: 'negative',
      message: 'Unable to reject vendor.',
      position: 'top-right'
    })
  } finally {
    actionLoading.value = false
  }
}

onMounted(async () => {
  const savedDarkMode = localStorage.getItem('admin_dark_mode')
  if (savedDarkMode === 'true') {
    $q.dark.set(true)
  }
  updateChartThemes()

  updateDateTime()
  timer = setInterval(updateDateTime, 1000)
  await loadDashboard()
})

onUnmounted(() => {
  if (timer) clearInterval(timer)
})
</script>

<style scoped>
/* ==========================================================
   GLOBAL PAGE LAYER & DYNAMIC THEMES
========================================================== */
.admin-dashboard {
  background: linear-gradient(135deg, #f0f4f8 0%, #e2e8f0 100%);
  min-height: 100vh;
  color: #0f172a;
  overflow-x: hidden;
  transition: background 0.4s ease, color 0.4s ease;
}

.text-heading { color: #0f172a; }
.text-subtext { color: #64748b; }
.bg-slate-box { background-color: #f1f5f9; color: #1e293b; }

.bg-red-badge { background-color: #fee2e2; }
.bg-blue-badge { background-color: #dbeafe; }
.bg-green-badge { background-color: #dcfce7; }

/* Dark Mode Theme Overrides */
.dark-mode-active {
  background: linear-gradient(135deg, #090d16 0%, #0f172a 100%) !important;
  color: #f8fafc !important;
}
.dark-mode-active .text-heading { color: #f8fafc !important; }
.dark-mode-active .text-subtext { color: #94a3b8 !important; }

.dark-mode-active .bg-slate-box {
  background-color: rgba(255, 255, 255, 0.08) !important;
  color: #f8fafc !important;
}

.dark-mode-active .bg-red-badge {
  background-color: rgba(185, 28, 28, 0.22) !important;
}
.dark-mode-active .bg-blue-badge {
  background-color: rgba(37, 99, 235, 0.22) !important;
}
.dark-mode-active .bg-green-badge {
  background-color: rgba(22, 163, 74, 0.22) !important;
}

.dark-mode-active .border-red-soft { border-color: rgba(239, 68, 68, 0.35) !important; }
.dark-mode-active .border-blue-soft { border-color: rgba(59, 130, 246, 0.35) !important; }
.dark-mode-active .border-green-soft { border-color: rgba(34, 197, 94, 0.35) !important; }
.dark-mode-active .border-slate-soft { border-color: rgba(255, 255, 255, 0.12) !important; }

.dark-mode-active .premium-glass-card {
  background: rgba(15, 23, 42, 0.78) !important;
  border-color: rgba(255, 255, 255, 0.09) !important;
  box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.4), inset 0 1px 1px rgba(255, 255, 255, 0.05) !important;
}

.dark-mode-active .panel-header {
  background: rgba(30, 41, 59, 0.6) !important;
  border-bottom-color: rgba(255, 255, 255, 0.08) !important;
}

.dark-mode-active :deep(.custom-premium-table thead tr th) {
  background: rgba(30, 41, 59, 0.75) !important;
  color: #94a3b8 !important;
  border-bottom-color: rgba(255, 255, 255, 0.08) !important;
}
.dark-mode-active :deep(.custom-premium-table tbody td) {
  border-bottom-color: rgba(255, 255, 255, 0.05) !important;
}
.dark-mode-active :deep(.custom-premium-table tbody tr:hover td) {
  background-color: rgba(255, 255, 255, 0.04) !important;
}

.dark-mode-active .store-mini-avatar {
  background: rgba(185, 28, 28, 0.25) !important;
}
.dark-mode-active .premium-chip {
  background: rgba(185, 28, 28, 0.25) !important;
  border-color: rgba(239, 68, 68, 0.4) !important;
  color: #fca5a5 !important;
}

.dark-mode-active .empty-state-glass {
  background: rgba(15, 23, 42, 0.65) !important;
}
.dark-mode-active .empty-icon-shield {
  background: rgba(255, 255, 255, 0.06) !important;
  border-color: rgba(255, 255, 255, 0.12) !important;
  color: #94a3b8 !important;
}
.dark-mode-active .bg-table-glass {
  background: rgba(15, 23, 42, 0.8) !important;
}

.dark-mode-active .btn-premium-outline {
  background: transparent !important;
  border-color: rgba(239, 68, 68, 0.5) !important;
  color: #fca5a5 !important;
}
.dark-mode-active .btn-premium-outline:hover {
  background: rgba(185, 28, 28, 0.2) !important;
}

/* Review & Confirmation Modal Dialogs */
.dark-mode-active .review-dialog-glass {
  background: #0f172a !important;
  border: 1px solid rgba(255, 255, 255, 0.12) !important;
  color: #f8fafc !important;
}
.dark-mode-active .dialog-actions-glass {
  background: #1e293b !important;
  border-top: 1px solid rgba(255, 255, 255, 0.08) !important;
}
.dark-mode-active .image-frame-container {
  background: #1e293b !important;
  border-color: rgba(255, 255, 255, 0.1) !important;
}
.dark-mode-active .map-container-box {
  background: #1e293b !important;
  border-color: rgba(255, 255, 255, 0.1) !important;
}
.dark-mode-active .custom-glass-input :deep(.q-field__control) {
  background: #1e293b !important;
}
.dark-mode-active .btn-outline-custom {
  background: transparent !important;
  border-color: rgba(255, 255, 255, 0.2) !important;
  color: #94a3b8 !important;
}

.ambient-mesh-bg {
  position: fixed;
  inset: 0;
  background-image:
    radial-gradient(circle at 15% 10%, rgba(239, 68, 68, 0.08) 0%, transparent 500px),
    radial-gradient(circle at 85% 80%, rgba(59, 130, 246, 0.08) 0%, transparent 600px);
  z-index: -1;
  pointer-events: none;
}
.enterprise-dot-pattern {
  position: fixed;
  inset: 0;
  background-image: radial-gradient(#94a3b8 1.5px, transparent 1.5px);
  background-size: 28px 28px;
  opacity: 0.12;
  z-index: -1;
  pointer-events: none;
}

.dashboard-container {
  max-width: 1440px;
  margin: 0 auto;
  padding: 32px 24px;
}
.card-content-layer,
.banner-content-layer {
  position: relative;
  z-index: 2;
}

/* Utilities */
.tracking-widest { letter-spacing: 0.1em; }
.tracking-wider { letter-spacing: 0.05em; }
.tracking-wide { letter-spacing: 0.04em; }
.tracking-tight { letter-spacing: -0.01em; }
.letter-spacing-tight { letter-spacing: -0.03em; }
.line-height-tight { line-height: 1.15; }
.font-mono { font-family: 'SFMono-Regular', Consolas, monospace; }

.shadow-xs { box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05); }
.shadow-soft { box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.05); }
.shadow-premium {
  box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.1), 0 4px 10px -5px rgba(0, 0, 0, 0.04);
}

/* ==========================================================
   DYNAMIC TIME-BASED HEADER
========================================================== */
.welcome-banner {
  border-radius: 20px;
  position: relative;
  overflow: hidden;
  border: 1px solid rgba(255, 255, 255, 0.1);
}
.transition-theme {
  transition: background 2s ease-in-out;
}

.theme-morning {
  background: linear-gradient(135deg, #1d4ed8 0%, #0284c7 50%, #38bdf8 100%);
}
.theme-afternoon {
  background: linear-gradient(135deg, #c2410c 0%, #ea580c 50%, #f97316 100%);
}
.theme-evening {
  background: linear-gradient(135deg, #090d16 0%, #1e1b4b 60%, #311042 100%);
}
.theme-dark-banner {
  background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #881337 100%) !important;
}

.banner-glow-overlay {
  position: absolute;
  top: -50%;
  right: -10%;
  width: 700px;
  height: 700px;
  background: radial-gradient(circle, rgba(255, 255, 255, 0.14) 0%, transparent 60%);
  border-radius: 50%;
  pointer-events: none;
  z-index: 1;
}

.banner-animated-shimmer {
  position: absolute;
  top: 0; left: -100%; width: 200%; height: 100%;
  background: linear-gradient(90deg, transparent 0%, rgba(255, 255, 255, 0.06) 50%, transparent 100%);
  animation: banner-shimmer 8s infinite linear;
  pointer-events: none;
  z-index: 1;
}
@keyframes banner-shimmer {
  0% { transform: translateX(0); }
  100% { transform: translateX(50%); }
}

.header-tag-pill {
  background: rgba(255, 255, 255, 0.12);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 999px;
  backdrop-filter: blur(8px);
}

.header-name-highlight {
  color: #ffffff;
  text-shadow: 0 0 16px rgba(255, 255, 255, 0.4);
}

.text-glow {
  text-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
}
.attention-badge {
  background: rgba(255, 255, 255, 0.15);
  border: 1px solid rgba(255, 255, 255, 0.3);
  padding: 4px 12px;
  border-radius: 100px;
  backdrop-filter: blur(8px);
  transition: all 0.3s ease;
}

.time-card-glass,
.header-tool-btn {
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.2);
  backdrop-filter: blur(16px);
  border-radius: 12px;
}
.time-card-glass {
  padding: 10px 20px;
}
.header-tool-btn {
  transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
}
.hover-rotate:hover {
  background: rgba(255, 255, 255, 0.25);
  transform: rotate(180deg) scale(1.05);
}

/* ==========================================================
   PROPORTIONAL CARDS & WATERMARK BACKGROUNDS
========================================================== */
.premium-glass-card {
  background: rgba(255, 255, 255, 0.88);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.95);
  border-radius: 16px;
  box-shadow:
    0 4px 20px -2px rgba(0, 0, 0, 0.03),
    inset 0 1px 1px rgba(255, 255, 255, 1);
  transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
  position: relative;
}

.card-hover-lift {
  transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
}
.card-hover-lift:hover {
  transform: translateY(-3px);
  box-shadow: 0 12px 28px -5px rgba(15, 23, 42, 0.08);
}
.hover-scale:hover {
  transform: scale(1.02);
}

/* Properly Sized and Positioned Background Watermarks */
.metric-bg-watermark {
  position: absolute;
  right: 12px;
  bottom: 6px;
  font-size: 72px;
  line-height: 1;
  pointer-events: none;
  z-index: 1;
  transition: transform 0.3s ease, opacity 0.3s ease;
}

.watermark-red { color: #b91c1c; opacity: 0.05; }
.watermark-blue { color: #2563eb; opacity: 0.05; }
.watermark-green { color: #16a34a; opacity: 0.05; }
.watermark-slate { color: #475569; opacity: 0.05; }

.premium-glass-card:hover .metric-bg-watermark {
  transform: scale(1.06) rotate(-3deg);
  opacity: 0.09;
}

.border-accent-blue { border-left: 4px solid #2563eb !important; }
.border-accent-green { border-left: 4px solid #16a34a !important; }
.border-accent-slate { border-left: 4px solid #475569 !important; }

/* Hero Metric */
.hero-card {
  border: 1.5px solid rgba(254, 202, 202, 0.8) !important;
  transition: transform 0.3s cubic-bezier(0.25, 0.8, 0.25, 1), box-shadow 0.3s ease;
}
.hero-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 20px 40px -10px rgba(239, 68, 68, 0.2);
  border-color: rgba(254, 202, 202, 1) !important;
}

.interactive-hue-layer {
  position: absolute;
  inset: 0;
  pointer-events: none;
  z-index: 1;
  transition: background 0.15s ease-out;
  background: radial-gradient(
    circle 350px at var(--mouse-x) var(--mouse-y),
    rgba(239, 68, 68, 0.05),
    transparent 70%
  );
}
.hero-card:hover .interactive-hue-layer {
  background: radial-gradient(
    circle 400px at var(--mouse-x) var(--mouse-y),
    rgba(239, 68, 68, 0.1),
    transparent 70%
  );
}

.hero-accent-line {
  position: absolute;
  top: 0;
  left: 20px;
  right: 20px;
  height: 3px;
  background: linear-gradient(90deg, #ef4444 0%, transparent 100%);
  border-radius: 0 0 3px 3px;
  z-index: 2;
}
.hero-text-content {
  transition: transform 0.2s ease;
}
.hero-card:hover .hero-text-content {
  transform: translateX(3px);
}
.hero-number {
  transition: color 0.2s ease;
}
.hero-card:hover .hero-number {
  color: #b91c1c !important;
}

/* Glows & Icon Stamps */
.card-glow-blue {
  position: absolute;
  top: 0;
  left: 0;
  width: 140px;
  height: 140px;
  background: radial-gradient(circle, rgba(59, 130, 246, 0.05) 0%, transparent 70%);
  pointer-events: none;
  z-index: 1;
}
.card-glow-green {
  position: absolute;
  top: 0;
  left: 0;
  width: 140px;
  height: 140px;
  background: radial-gradient(circle, rgba(34, 197, 94, 0.05) 0%, transparent 70%);
  pointer-events: none;
  z-index: 1;
}
.card-glow-slate {
  position: absolute;
  bottom: 0;
  right: 0;
  width: 200px;
  height: 200px;
  background: radial-gradient(circle, rgba(15, 23, 42, 0.03) 0%, transparent 70%);
  pointer-events: none;
  z-index: 1;
}

.icon-badge-box {
  width: 48px;
  height: 48px;
  border-radius: 12px;
}
.icon-badge-box-sm {
  width: 44px;
  height: 44px;
  border-radius: 10px;
}
.border-red-soft { border: 1.5px solid #fee2e2; }
.border-blue-soft { border: 1.5px solid #dbeafe; }
.border-green-soft { border: 1.5px solid #dcfce7; }
.border-slate-soft { border: 1.5px solid #e2e8f0; }

/* ==========================================================
   CHARTS & LEGENDS
========================================================== */
.legend-badge {
  padding: 3px 8px;
  border-radius: 6px;
  font-weight: 700;
  font-size: 11px;
}
.legend-vendor { background: rgba(37, 99, 235, 0.12); color: #2563eb; }
.legend-consumer { background: rgba(22, 163, 74, 0.12); color: #16a34a; }
.bg-chart-overlay { background: rgba(255, 255, 255, 0.6); backdrop-filter: blur(2px); }

/* ==========================================================
   TABLE SECTION & VIEW DIRECTORY BUTTON
========================================================== */
.table-glass-container {
  overflow: hidden;
}
.panel-header {
  background: rgba(248, 250, 252, 0.8);
  border-bottom: 1.5px solid #e2e8f0;
}
.header-accent-red {
  width: 4px;
  height: 24px;
  background: #b91c1c;
  border-radius: 4px;
}

/* View Directory Button */
.btn-view-directory {
  border-radius: 8px !important;
  padding: 8px 18px !important;
  font-size: 12px;
  white-space: nowrap !important;
  flex-shrink: 0 !important;
  min-width: 140px;
  box-shadow: 0 2px 8px rgba(185, 28, 28, 0.25);
  transition: all 0.2s ease;
}
.btn-view-directory:hover {
  background: #991b1b !important;
  transform: translateY(-1px);
}

.custom-premium-table :deep(thead tr th) {
  background: rgba(248, 250, 252, 0.85);
  font-weight: 800;
  color: #64748b;
  text-transform: uppercase;
  font-size: 11px;
  letter-spacing: 0.05em;
  padding: 14px 18px;
  border-bottom: 1.5px solid #e2e8f0;
}
.custom-premium-table :deep(tbody td) {
  padding: 14px 18px;
  border-bottom: 1px solid rgba(226, 232, 240, 0.6);
  transition: background 0.2s ease;
}
.custom-premium-table :deep(tbody tr:hover td) {
  background-color: rgba(255, 255, 255, 0.95);
}

.store-mini-avatar {
  width: 30px;
  height: 30px;
  border-radius: 8px;
  background: #fee2e2;
}

.premium-chip {
  background: rgba(254, 226, 226, 0.6) !important;
  border: 1px solid #fecaca;
}

.empty-state-glass {
  background: rgba(248, 250, 252, 0.6);
}
.empty-icon-shield {
  width: 58px;
  height: 58px;
  border-radius: 50%;
  background: #ffffff;
  border: 1.5px solid #e2e8f0;
}
.bg-table-glass {
  background: rgba(255, 255, 255, 0.7);
  backdrop-filter: blur(4px);
}

/* ==========================================================
   QUICK ACTIONS
========================================================== */
.action-card-glow {
  position: absolute;
  top: 50%;
  left: 50%;
  width: 0;
  height: 0;
  background: radial-gradient(circle, rgba(239, 68, 68, 0.08) 0%, transparent 70%);
  transform: translate(-50%, -50%);
  transition: width 0.3s ease, height 0.3s ease;
  border-radius: 50%;
  z-index: 1;
  pointer-events: none;
}
.hover-lift-action:hover {
  transform: translateY(-3px);
  box-shadow: 0 10px 24px -5px rgba(0, 0, 0, 0.06);
  border-color: rgba(254, 202, 202, 0.8);
}
.hover-lift-action:hover .action-card-glow {
  width: 250px;
  height: 250px;
}
.hover-lift-action:hover .action-arrow {
  transform: translateX(4px);
  color: #b91c1c !important;
}

.action-icon-stamp {
  width: 44px;
  height: 44px;
  border-radius: 10px;
  flex-shrink: 0;
}

.action-arrow {
  transition: transform 0.2s cubic-bezier(0.25, 0.8, 0.25, 1), color 0.2s ease;
}

.btn-premium-outline {
  border-radius: 8px !important;
  font-weight: 700;
  background: #ffffff !important;
  border: 1px solid currentColor;
  transition: all 0.2s ease;
}
.action-btn-hover:hover {
  background: #fef2f2 !important;
  color: #991b1b !important;
}

/* ==========================================================
   REVIEW DIALOG
========================================================== */
.review-dialog-glass {
  width: 500px;
  max-width: 95vw;
  border-radius: 16px !important;
  background: #ffffff;
  border: 1px solid rgba(255, 255, 255, 0.8);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
  overflow: hidden;
}
.vendor-info-dialog {
  width: 620px;
}

.info-store-name {
  font-size: 22px;
  font-weight: 800;
  line-height: 1.2;
}
.info-owner-name {
  font-size: 14px;
}
.image-frame-container {
  border-radius: 8px;
  padding: 4px;
  background: #ffffff;
  border: 1px solid #e2e8f0;
}
.map-container-box {
  border-radius: 10px;
  padding: 6px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
}
.custom-glass-input :deep(.q-field__control) {
  background: #f8fafc;
  border-radius: 8px;
}

.btn-approve-custom {
  border-radius: 8px !important;
  font-weight: 700;
  background: #10b981 !important;
  transition: all 0.2s ease;
}
.btn-approve-custom:hover {
  background: #059669 !important;
  transform: translateY(-1.5px);
}
.btn-reject-custom {
  border-radius: 8px !important;
  font-weight: 700;
  transition: all 0.2s ease;
}
.btn-reject-custom:hover {
  background: #fef2f2 !important;
  color: #991b1b !important;
}
.btn-outline-custom {
  border-radius: 8px !important;
  font-weight: 700;
  background: #ffffff !important;
  border: 1px solid #e2e8f0;
  transition: all 0.2s ease;
}
.btn-outline-custom:hover {
  background: #f8fafc !important;
}
.btn-reject-confirm {
  border-radius: 8px !important;
  font-weight: 700;
}

.modal-stamp-disc {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  border: 1.5px solid currentColor;
}

/* Pulse Utility */
.pulse-dot-white {
  width: 6px;
  height: 6px;
  background-color: #ffffff;
  border-radius: 50%;
  animation: pulse-white 1.5s infinite;
}
@keyframes pulse-white {
  0% { box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.8); }
  100% { box-shadow: 0 0 0 6px rgba(255, 255, 255, 0); }
}

@media (max-width: 1024px) {
  .dashboard-container { padding: 24px 16px; }
}
@media (max-width: 767px) {
  .dashboard-container { padding: 16px 12px; }
  .welcome-banner {
    flex-direction: column;
    align-items: flex-start;
    padding: 20px;
  }
  .time-card-glass { margin-top: 14px; }
}
</style>