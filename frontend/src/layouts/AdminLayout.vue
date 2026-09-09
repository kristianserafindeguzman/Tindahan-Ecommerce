<template>
  <q-layout 
    view="hHh LpR fFf" 
    class="admin-layout"
    :class="{ 'admin-dark-mode': $q.dark.isActive }"
  >

    <!-- ================= INITIAL ENTRY SPLASH SCREEN (ONE-TIME ONLY) ================= -->
    <transition name="splash-fade">
      <div v-if="isInitialLoading" class="admin-entry-splash fixed-full flex flex-center">
        <div class="ambient-orb orb-1"></div>
        <div class="ambient-orb orb-2"></div>
        <div class="ambient-mesh-grid fixed-full"></div>

        <div class="splash-card column items-center text-center relative-position z-top">
          <!-- 3D Awning Banner Header -->
          <div class="splash-store-roof row no-wrap q-mb-md">
            <span class="awn-red"></span><span class="awn-white"></span>
            <span class="awn-red"></span><span class="awn-white"></span>
            <span class="awn-red"></span><span class="awn-white"></span>
            <span class="awn-red"></span><span class="awn-white"></span>
          </div>

          <!-- Tactile Animated Brand Box -->
          <div class="splash-brand-cube flex flex-center q-mb-lg relative-position">
            <q-icon name="storefront" size="52px" color="red-9" class="brand-store-icon" />
            <div class="sonar-wave wave-1"></div>
            <div class="sonar-wave wave-2"></div>
            <div class="sonar-wave wave-3"></div>
          </div>

          <!-- Brand Title -->
          <div class="text-h4 text-weight-bolder tracking-tight q-mb-xs splash-title">
            Tindahan Admin
          </div>
          
          <div class="text-caption text-weight-bold tracking-wider text-uppercase text-red-7 q-mb-lg">
            Marketplace Control Center
          </div>

          <!-- Dynamic Status Counter & Label -->
          <div class="splash-status-pill row items-center justify-between q-px-md q-py-xs q-mb-md">
            <span class="text-caption text-weight-bold opacity-80">{{ loadingStepText }}</span>
            <span class="text-caption text-weight-bolder font-mono q-ml-md">{{ loadProgress }}%</span>
          </div>

          <!-- Progress Bar -->
          <div class="splash-progress-track overflow-hidden q-mb-md">
            <div class="splash-progress-fill" :style="{ width: loadProgress + '%' }"></div>
          </div>

          <div class="text-caption opacity-60 text-weight-medium">
            Initializing system modules & permissions...
          </div>
        </div>
      </div>
    </transition>

    <!-- HEADER (Mobile only — toggles sidebar) -->
    <q-header class="admin-header-mobile" elevated>
      <q-toolbar class="q-px-md">
        <q-btn
          flat
          dense
          round
          icon="menu"
          color="white"
          @click="drawerOpen = !drawerOpen"
        />
        <q-toolbar-title class="header-title text-weight-bold row items-center no-wrap">
          <q-icon name="storefront" size="20px" class="q-mr-xs text-white" />
          <span>Tindahan Admin</span>
        </q-toolbar-title>

        <q-btn
          flat
          round
          dense
          color="white"
          :icon="$q.dark.isActive ? 'light_mode' : 'dark_mode'"
          @click="toggleDarkMode"
        />
      </q-toolbar>
    </q-header>

    <!-- LEFT SIDEBAR -->
    <q-drawer
      v-model="drawerOpen"
      show-if-above
      :width="284"
      :breakpoint="1024"
      class="admin-fixed-sidebar"
    >
      <div class="sidebar-wrapper column full-height justify-between">

        <!-- TOP: BRAND, THEME SWITCHER & NAV -->
        <div>
          <!-- BRAND -->
          <div class="sidebar-brand-section q-pa-md">
            <div class="brand-logo-wrap flex flex-center q-mb-xs">
              <img
                v-if="!logoFailed"
                src="@/assets/tindahan-logo.png"
                alt="Tindahan"
                class="logo-img-transparent"
                @error="onLogoError"
              />
              <div v-else class="fallback-logo row items-center no-wrap">
                <q-icon name="storefront" size="34px" color="white" class="q-mr-sm" />
                <span class="text-weight-bolder text-white text-h5">Tindahan</span>
              </div>
            </div>

            <div class="store-badge-tag text-center">
              SARI-SARI STORES CONTROL CENTER
            </div>

            <!-- Segmented Mode Switcher -->
            <div class="theme-segmented-bar row items-center no-wrap q-mt-sm q-pa-xs">
              <div
                class="segmented-tab row items-center justify-center no-wrap"
                :class="{ 'segmented-tab-active': !$q.dark.isActive }"
                @click="setTheme(false)"
              >
                <q-icon name="light_mode" size="15px" class="q-mr-xs" />
                <span>Light</span>
              </div>
              <div
                class="segmented-tab row items-center justify-center no-wrap"
                :class="{ 'segmented-tab-active': $q.dark.isActive }"
                @click="setTheme(true)"
              >
                <q-icon name="dark_mode" size="15px" class="q-mr-xs" />
                <span>Dark</span>
              </div>
            </div>
          </div>

          <div class="sidebar-divider"></div>

          <!-- NAVIGATION -->
          <div class="sidebar-nav-container q-px-md q-pt-md">
            <div class="sidebar-section-label q-px-sm q-mb-sm">
              MAIN NAVIGATION
            </div>

            <q-list class="sidebar-nav-list q-gutter-y-xs">
              <q-item
                v-for="(item, index) in navItems"
                :key="item.path"
                :to="item.path"
                clickable
                v-ripple
                active-class="nav-item-active"
                class="sidebar-nav-item row items-center no-wrap nav-item-enter"
                :style="{ '--i': index }"
              >
                <q-item-section avatar class="nav-icon-slot">
                  <q-icon :name="item.icon" size="21px" class="nav-glyph" />
                </q-item-section>
                <q-item-section class="nav-label text-weight-bold">
                  {{ item.label }}
                </q-item-section>
              </q-item>
            </q-list>
          </div>
        </div>

        <!-- FOOTER: LOGOUT -->
        <div class="sidebar-footer-section q-pa-lg">
          <button
            type="button"
            class="sidebar-logout-btn row items-center justify-center full-width cursor-pointer"
            @click="showLogoutModal = true"
          >
            <q-icon name="logout" size="18px" class="q-mr-sm" />
            <span class="text-weight-bolder">Sign Out Account</span>
          </button>
        </div>

      </div>
    </q-drawer>

    <!-- MAIN PAGE CONTAINER -->
    <q-page-container class="admin-page-container">
      <router-view />
    </q-page-container>

    <!-- ================= REVITALIZED LOGOUT CONFIRMATION MODAL ================= -->
    <q-dialog 
      v-model="showLogoutModal" 
      persistent 
      transition-show="scale" 
      transition-hide="scale"
    >
      <q-card class="logout-modal-card overflow-hidden">
        <!-- Awning Header Banner -->
        <div class="logout-hero-banner relative-position q-pa-lg text-center">
          <div class="banner-awning-strip row no-wrap">
            <span class="awn-red"></span><span class="awn-white"></span>
            <span class="awn-red"></span><span class="awn-white"></span>
            <span class="awn-red"></span><span class="awn-white"></span>
            <span class="awn-red"></span><span class="awn-white"></span>
          </div>

          <div class="logout-banner-glow"></div>

          <!-- 3D Logout Badge with Pulsing Ring -->
          <div class="logout-badge-wrap flex flex-center q-mx-auto relative-position z-top">
            <div class="logout-icon-ring flex flex-center">
              <q-icon name="logout" size="34px" color="red-9" class="logout-animated-icon" />
            </div>
            <div class="logout-pulse-halo"></div>
          </div>

          <div class="relative-position z-top q-mt-md">
            <span class="logout-chip text-weight-bolder text-uppercase">
              ADMIN SESSION TERMINATION
            </span>
            <div class="text-h5 text-weight-bolder text-white q-mt-xs tracking-tight">
              Sign Out Account?
            </div>
          </div>
        </div>

        <!-- Body Section -->
        <q-card-section class="q-pa-lg logout-body-section text-center">
          <p class="text-body2 text-slate-600 q-mb-md line-height-relaxed">
            Are you sure you want to end your administrative session? You will need to log back in with your credentials to access marketplace management.
          </p>

          <div class="logout-notice-box row items-center no-wrap q-pa-md text-left">
            <q-icon name="info" size="22px" color="amber-9" class="q-mr-sm flex-shrink-0" />
            <div class="text-caption text-slate-700 text-weight-medium">
              Any unsaved changes on open review modals will be discarded.
            </div>
          </div>
        </q-card-section>

        <q-separator class="logout-divider" />

        <!-- Actions -->
        <q-card-actions align="center" class="q-pa-md logout-footer-actions gap-sm">
          <q-btn
            flat
            no-caps
            label="Stay Logged In"
            class="logout-btn-cancel text-weight-bolder"
            :disable="isLoggingOut"
            v-close-popup
          />
          <q-btn
            unelevated
            no-caps
            label="Confirm Sign Out"
            icon="power_settings_new"
            class="logout-btn-confirm text-weight-bolder"
            :loading="isLoggingOut"
            @click="executeLogout"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

  </q-layout>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { api } from '@/boot/axios'

const router = useRouter()
const $q = useQuasar()
const drawerOpen = ref(true)
const logoFailed = ref(false)

const isInitialLoading = ref(false)
const loadProgress = ref(0)
const loadingStepText = ref('Connecting to server...')

const showLogoutModal = ref(false)
const isLoggingOut = ref(false)

const onLogoError = () => {
  logoFailed.value = true
}

const navItems = [
  { label: 'Dashboard', icon: 'grid_view', path: '/admin/dashboard' },
  { label: 'Approvals', icon: 'pending_actions', path: '/admin/approvals' },
  { label: 'Vendors', icon: 'storefront', path: '/admin/vendors' },
  { label: 'Consumers', icon: 'groups', path: '/admin/consumers' },
]

const toggleDarkMode = () => {
  setTheme(!$q.dark.isActive)
}

const setTheme = (dark) => {
  $q.dark.set(dark)
  localStorage.setItem('admin_dark_mode', dark ? 'true' : 'false')
}

const runInitialLoader = () => {
  const hasLoadedSession = sessionStorage.getItem('admin_session_loaded')
  if (!hasLoadedSession) {
    isInitialLoading.value = true

    const steps = [
      { progress: 25, text: 'Authenticating credentials...' },
      { progress: 55, text: 'Fetching merchant records...' },
      { progress: 85, text: 'Building administrative workspace...' },
      { progress: 100, text: 'Ready!' }
    ]

    let stepIndex = 0
    const interval = setInterval(() => {
      if (stepIndex < steps.length) {
        loadProgress.value = steps[stepIndex].progress
        loadingStepText.value = steps[stepIndex].text
        stepIndex++
      } else {
        clearInterval(interval)
        setTimeout(() => {
          isInitialLoading.value = false
          sessionStorage.setItem('admin_session_loaded', 'true')
        }, 500)
      }
    }, 450)
  }
}

onMounted(() => {
  const savedDarkMode = localStorage.getItem('admin_dark_mode')
  $q.dark.set(savedDarkMode === 'true')

  runInitialLoader()
})

onBeforeUnmount(() => {
  $q.dark.set(false)
})

const executeLogout = async () => {
  isLoggingOut.value = true
  try {
    await api.post('/logout')
  } catch {
    // Ignore network errors on logout
  } finally {
    sessionStorage.removeItem('admin_session_loaded')
    localStorage.removeItem('auth_token')
    localStorage.removeItem('auth_user')
    localStorage.removeItem('auth_role')
    isLoggingOut.value = false
    showLogoutModal.value = false
    router.push('/login')
  }
}
</script>

<style scoped>
/* ==========================================================
   BASE LAYOUT
========================================================== */
.admin-layout {
  min-height: 100vh;
  background-color: #f8fafc;
  color: #1e293b;
  transition: background-color 0.25s ease, color 0.25s ease;
}

.font-mono {
  font-family: 'SFMono-Regular', Consolas, Menlo, monospace;
}

.line-height-relaxed {
  line-height: 1.5;
}

.gap-sm {
  gap: 12px;
}

.flex-shrink-0 {
  flex-shrink: 0;
}

/* ==========================================================
   SPLASH SCREEN
========================================================== */
.admin-entry-splash {
  background: #090d16;
  z-index: 99999;
  position: fixed;
  overflow: hidden;
}

.ambient-mesh-grid {
  background-image: radial-gradient(rgba(255, 255, 255, 0.08) 1.5px, transparent 1.5px);
  background-size: 28px 28px;
  pointer-events: none;
}

.ambient-orb {
  position: absolute;
  border-radius: 50%;
  pointer-events: none;
  filter: blur(80px);
}

.orb-1 {
  width: 400px;
  height: 400px;
  background: radial-gradient(circle, rgba(220, 38, 38, 0.28) 0%, transparent 70%);
  top: 15%;
  left: 20%;
  animation: floatOrb 8s infinite alternate ease-in-out;
}

.orb-2 {
  width: 450px;
  height: 450px;
  background: radial-gradient(circle, rgba(185, 28, 28, 0.22) 0%, transparent 70%);
  bottom: 10%;
  right: 15%;
  animation: floatOrb 10s infinite alternate-reverse ease-in-out;
}

@keyframes floatOrb {
  0% { transform: translate(0, 0) scale(1); }
  100% { transform: translate(40px, 30px) scale(1.15); }
}

.splash-card {
  width: 440px;
  max-width: 90vw;
  background: rgba(15, 23, 42, 0.85);
  backdrop-filter: blur(25px);
  -webkit-backdrop-filter: blur(25px);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 24px;
  padding: 36px 32px 28px;
  box-shadow: 0 25px 60px rgba(0, 0, 0, 0.6);
  animation: splashPop 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes splashPop {
  0% { opacity: 0; transform: scale(0.92) translateY(20px); }
  100% { opacity: 1; transform: scale(1) translateY(0); }
}

.splash-store-roof {
  height: 4px;
  width: 140px;
  border-radius: 2px;
  overflow: hidden;
}
.splash-store-roof span { flex: 1; }
.awn-red { background: #dc2626; }
.awn-white { background: #ffffff; }

.splash-brand-cube {
  width: 96px;
  height: 96px;
  background: #ffffff;
  border-radius: 26px;
  box-shadow: 0 16px 36px rgba(220, 38, 38, 0.3);
}

.brand-store-icon {
  animation: iconBounce 2s infinite ease-in-out;
}

@keyframes iconBounce {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-4px); }
}

.sonar-wave {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 100%;
  height: 100%;
  border-radius: 26px;
  border: 2px solid #ef4444;
  pointer-events: none;
}

.wave-1 { animation: sonarWave 2.4s infinite cubic-bezier(0, 0.2, 0.8, 1); }
.wave-2 { animation: sonarWave 2.4s infinite 0.8s cubic-bezier(0, 0.2, 0.8, 1); }
.wave-3 { animation: sonarWave 2.4s infinite 1.6s cubic-bezier(0, 0.2, 0.8, 1); }

@keyframes sonarWave {
  0% { transform: translate(-50%, -50%) scale(1); opacity: 0.9; }
  100% { transform: translate(-50%, -50%) scale(1.8); opacity: 0; }
}

.splash-title {
  color: #ffffff;
  letter-spacing: -0.02em;
}

.splash-status-pill {
  width: 100%;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 12px;
  color: #cbd5e1;
}

.splash-progress-track {
  width: 100%;
  height: 6px;
  background: rgba(255, 255, 255, 0.08);
  border-radius: 999px;
  position: relative;
}

.splash-progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #dc2626 0%, #ef4444 100%);
  border-radius: 999px;
  transition: width 0.35s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 0 12px rgba(239, 68, 68, 0.6);
}

.splash-fade-enter-active,
.splash-fade-leave-active {
  transition: opacity 0.5s cubic-bezier(0.4, 0, 0.2, 1), transform 0.5s ease;
}

.splash-fade-leave-to {
  opacity: 0;
  transform: scale(1.03);
}

/* ==========================================================
   SIDEBAR
========================================================== */
:deep(.q-drawer.admin-fixed-sidebar),
:deep(.admin-fixed-sidebar),
:deep(.admin-fixed-sidebar .q-drawer__content) {
  background: linear-gradient(180deg, #9f1d1d 0%, #7f1d1d 55%, #581010 100%) !important;
  color: #ffffff !important;
}

.sidebar-wrapper {
  background: transparent !important;
  min-height: 100%;
}

.sidebar-brand-section {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.brand-logo-wrap {
  width: 100%;
}

.logo-img-transparent {
  width: 100%;
  max-width: 232px;
  height: auto;
  display: block;
  filter: drop-shadow(0 6px 14px rgba(0, 0, 0, 0.35));
}

.fallback-logo {
  padding: 8px 0;
}

.store-badge-tag {
  color: #fecaca !important;
  font-size: 10px;
  font-weight: 800;
  letter-spacing: 0.1em;
  width: 100%;
}

.theme-segmented-bar {
  background: rgba(0, 0, 0, 0.32);
  border: 1px solid rgba(255, 255, 255, 0.14);
  border-radius: 999px;
  width: 100%;
  min-height: 36px;
  box-sizing: border-box;
}

.segmented-tab {
  flex: 1 1 0;
  min-width: 0;
  padding: 7px 10px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 700;
  line-height: 1;
  color: rgba(255, 255, 255, 0.72);
  white-space: nowrap;
  transition: background 0.2s ease, color 0.2s ease;
  user-select: none;
  cursor: pointer;
}

.segmented-tab:hover {
  color: #ffffff;
}

.segmented-tab-active {
  background: #ffffff;
  color: #991b1b !important;
}

.sidebar-divider {
  height: 1px;
  margin: 0 20px;
  background: rgba(255, 255, 255, 0.12);
}

.sidebar-section-label {
  font-size: 10.5px;
  font-weight: 800;
  color: #fca5a5 !important;
  letter-spacing: 0.1em;
}

.sidebar-nav-list {
  background: transparent !important;
  border: none !important;
}

.sidebar-nav-item {
  border-radius: 10px;
  color: #ffffff !important;
  opacity: 0.86;
  min-height: 46px;
  padding: 10px 14px !important;
  background: transparent !important;
  position: relative;
  transition: background 0.18s ease, opacity 0.18s ease, transform 0.18s ease;
}

.sidebar-nav-item::before {
  content: '';
  position: absolute;
  left: -2px;
  top: 8px;
  bottom: 8px;
  width: 3px;
  border-radius: 3px;
  background: transparent;
  transition: background 0.18s ease;
}

.nav-icon-slot {
  min-width: 30px !important;
  max-width: 30px !important;
  padding-right: 12px !important;
  display: flex;
  align-items: center;
  justify-content: center;
}

.nav-glyph {
  color: #ffffff !important;
  font-size: 20px !important;
}

.nav-label {
  color: #ffffff !important;
  font-size: 14px;
  letter-spacing: -0.01em;
}

.sidebar-nav-item:hover {
  opacity: 1;
  background: rgba(255, 255, 255, 0.1) !important;
  transform: translateX(2px);
}

.nav-item-active {
  opacity: 1 !important;
  background: #ffffff !important;
}

.nav-item-active::before {
  background: transparent;
}

.nav-item-active .nav-label,
.nav-item-active .nav-glyph {
  color: #991b1b !important;
}

@keyframes navItemEnter {
  from { opacity: 0; transform: translateX(-8px); }
  to { opacity: 1; transform: translateX(0); }
}
.nav-item-enter {
  animation: navItemEnter 0.4s cubic-bezier(0.16, 1, 0.3, 1) both;
  animation-delay: calc(var(--i, 0) * 60ms);
}
@media (prefers-reduced-motion: reduce) {
  .nav-item-enter { animation: none; }
}

.sidebar-footer-section {
  border-top: 1px solid rgba(255, 255, 255, 0.12);
  background: rgba(0, 0, 0, 0.16) !important;
}

.sidebar-logout-btn {
  background: rgba(0, 0, 0, 0.22) !important;
  border: 1px solid rgba(255, 255, 255, 0.15);
  color: #fecaca !important;
  border-radius: 10px;
  padding: 12px;
  font-size: 13.5px;
  transition: background 0.2s ease, color 0.2s ease, border-color 0.2s ease;
}

.sidebar-logout-btn:hover {
  background: #b91c1c !important;
  color: #ffffff !important;
  border-color: #b91c1c;
}

/* ==========================================================
   MOBILE HEADER
========================================================== */
.admin-header-mobile {
  background: linear-gradient(90deg, #dc2626 0%, #b91c1c 50%, #7f1d1d 100%);
}

.header-title {
  font-size: 16px;
  letter-spacing: 0.02em;
}

@media (min-width: 1025px) {
  .admin-header-mobile {
    display: none;
  }
}

/* ==========================================================
   REVITALIZED LOGOUT MODAL AESTHETICS
========================================================== */
.logout-modal-card {
  width: 440px;
  max-width: 92vw;
  border-radius: 24px !important;
  background: #ffffff;
  border: 1.5px solid #e2e8f0;
  box-shadow: 0 25px 60px rgba(15, 23, 42, 0.2) !important;
}

.logout-hero-banner {
  background: linear-gradient(135deg, #dc2626 0%, #b91c1c 50%, #7f1d1d 100%);
  position: relative;
  overflow: hidden;
  padding-top: 26px;
  padding-bottom: 22px;
}

.banner-awning-strip {
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 4px;
  display: flex;
}
.banner-awning-strip span { flex: 1; }
.awn-red { background: #991b1b; }
.awn-white { background: #fee2e2; }

.logout-banner-glow {
  position: absolute;
  top: -40px;
  left: 50%;
  transform: translateX(-50%);
  width: 220px;
  height: 220px;
  background: radial-gradient(circle, rgba(255, 255, 255, 0.25) 0%, transparent 70%);
  border-radius: 50%;
  pointer-events: none;
}

.logout-badge-wrap {
  width: 72px;
  height: 72px;
}

.logout-icon-ring {
  width: 72px;
  height: 72px;
  border-radius: 20px;
  background: #ffffff;
  box-shadow: 0 10px 24px rgba(0, 0, 0, 0.18);
  position: relative;
  z-index: 2;
}

.logout-animated-icon {
  animation: pulseIcon 2s infinite ease-in-out;
}

@keyframes pulseIcon {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.08); }
}

.logout-pulse-halo {
  position: absolute;
  top: 0; left: 0; right: 0; bottom: 0;
  border-radius: 20px;
  border: 2px solid rgba(255, 255, 255, 0.6);
  animation: haloPulse 2.2s infinite cubic-bezier(0.4, 0, 0.2, 1);
  pointer-events: none;
}

@keyframes haloPulse {
  0% { transform: scale(1); opacity: 0.8; }
  100% { transform: scale(1.35); opacity: 0; }
}

.logout-chip {
  display: inline-block;
  background: rgba(0, 0, 0, 0.3);
  color: #fecaca;
  padding: 4px 12px;
  border-radius: 999px;
  font-size: 10px;
  letter-spacing: 0.08em;
  border: 1px solid rgba(255, 255, 255, 0.15);
}

.logout-body-section {
  background: #ffffff;
}

.logout-notice-box {
  background: #fffbeb;
  border: 1px solid #fef3c7;
  border-radius: 12px;
}

.logout-divider {
  border-color: #f1f5f9;
}

.logout-footer-actions {
  background: #f8fafc;
}

.logout-btn-cancel {
  border-radius: 9999px !important;
  font-size: 13px !important;
  padding: 8px 20px !important;
  color: #64748b !important;
  background: #ffffff !important;
  border: 1px solid #e2e8f0 !important;
  transition: all 0.2s ease;
}
.logout-btn-cancel:hover {
  background: #f1f5f9 !important;
  color: #0f172a !important;
}

.logout-btn-confirm {
  border-radius: 9999px !important;
  font-size: 13px !important;
  padding: 8px 24px !important;
  background: #c5221f !important;
  color: #ffffff !important;
  box-shadow: 0 4px 14px rgba(185, 28, 28, 0.35) !important;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}
.logout-btn-confirm:hover {
  background: #a91b18 !important;
  box-shadow: 0 6px 18px rgba(185, 28, 28, 0.45) !important;
  transform: translateY(-1px);
}
</style>

<!-- ==========================================================
     UNSCOPED GLOBAL OVERRIDES (Only active inside .admin-dark-mode)
========================================================== -->
<style>
.admin-layout.admin-dark-mode,
.admin-layout.admin-dark-mode .q-page-container,
.admin-layout.admin-dark-mode .q-page {
  background-color: #0b0f19 !important;
  color: #f1f5f9 !important;
}

.admin-layout.admin-dark-mode .q-page-container > * {
  background-color: #0b0f19 !important;
  color: #f1f5f9 !important;
}

.admin-layout.admin-dark-mode .q-card,
.admin-layout.admin-dark-mode .premium-glass-card {
  background-color: #111827 !important;
  color: #f1f5f9 !important;
  border-color: rgba(255, 255, 255, 0.08) !important;
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.45) !important;
}

.admin-layout.admin-dark-mode .text-slate-800,
.admin-layout.admin-dark-mode .text-dark {
  color: #f8fafc !important;
}
.admin-layout.admin-dark-mode .text-slate-700 {
  color: #e2e8f0 !important;
}
.admin-layout.admin-dark-mode .text-slate-600,
.admin-layout.admin-dark-mode .text-slate-500 {
  color: #94a3b8 !important;
}
.admin-layout.admin-dark-mode .text-slate-400 {
  color: #64748b !important;
}

.admin-layout.admin-dark-mode .border-slate-light,
.admin-layout.admin-dark-mode .border-bottom-light,
.admin-layout.admin-dark-mode .border-top-light {
  border-color: rgba(255, 255, 255, 0.08) !important;
}

.admin-layout.admin-dark-mode .q-field__control {
  background-color: #1f2937 !important;
  color: #f8fafc !important;
}
.admin-layout.admin-dark-mode .q-field__control:before {
  border-color: rgba(255, 255, 255, 0.12) !important;
}
.admin-layout.admin-dark-mode .q-field__native,
.admin-layout.admin-dark-mode .q-field__input {
  color: #f8fafc !important;
}

.admin-layout.admin-dark-mode .q-table {
  background-color: #111827 !important;
  color: #f8fafc !important;
}
.admin-layout.admin-dark-mode .q-table thead tr th {
  background-color: #111827 !important;
  color: #94a3b8 !important;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
}
.admin-layout.admin-dark-mode .q-table tbody tr td {
  border-bottom: 1px solid rgba(255, 255, 255, 0.04) !important;
  color: #e2e8f0 !important;
}
.admin-layout.admin-dark-mode .q-table tbody tr:hover td {
  background-color: rgba(255, 255, 255, 0.03) !important;
}

/* Dark Mode Overrides for Revitalized Logout Modal */
.body--dark .logout-modal-card {
  background: #0f172a !important;
  border-color: rgba(255, 255, 255, 0.12) !important;
}

.body--dark .logout-body-section {
  background: #0f172a !important;
}

.body--dark .logout-icon-ring {
  background: #1e293b !important;
  box-shadow: 0 10px 24px rgba(0, 0, 0, 0.4) !important;
}

.body--dark .logout-notice-box {
  background: rgba(245, 158, 11, 0.12) !important;
  border-color: rgba(245, 158, 11, 0.25) !important;
}

.body--dark .logout-footer-actions {
  background: #162032 !important;
}

.body--dark .logout-divider {
  border-color: rgba(255, 255, 255, 0.08) !important;
}

.body--dark .logout-btn-cancel {
  background: rgba(30, 41, 59, 0.8) !important;
  color: #94a3b8 !important;
  border-color: rgba(255, 255, 255, 0.12) !important;
}
.body--dark .logout-btn-cancel:hover {
  background: rgba(51, 65, 85, 0.9) !important;
  color: #ffffff !important;
}
</style>