<template>
  <q-layout view="hHh LpR fFf" class="admin-layout">

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
            HQ Control Panel
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
      :width="280"
      :breakpoint="1024"
      class="admin-fixed-sidebar"
    >
      <div class="sidebar-wrapper column full-height justify-between">

        <!-- TOP: BRAND, SLEEK THEME SWITCHER & NAV -->
        <div>
          <!-- BRAND & EXPANDED LOGO PLATE -->
          <div class="sidebar-brand-section q-pa-lg">
            <div class="brand-canopy-card column flex-center text-center q-pa-md">
              <div class="brand-awning-roof row no-wrap">
                <span class="awn-red"></span><span class="awn-white"></span>
                <span class="awn-red"></span><span class="awn-white"></span>
                <span class="awn-red"></span><span class="awn-white"></span>
                <span class="awn-red"></span>
              </div>

              <!-- Expanded Logo Frame -->
              <div class="sidebar-logo-frame q-my-sm flex flex-center">
                <img
                  src="@/assets/tindahan-mobile.png"
                  alt="Tindahan"
                  class="logo-img-expanded"
                  @error="onLogoError"
                />
                <div v-if="logoFailed" class="fallback-logo row items-center no-wrap">
                  <q-icon name="storefront" size="28px" color="red-9" class="q-mr-sm" />
                  <span class="text-weight-bolder text-red-9 text-h6">Tindahan</span>
                </div>
              </div>

              <div class="store-badge-tag q-mt-xs">
                ADMINISTRATION HQ
              </div>
            </div>

            <!-- Integrated Header Segmented Mode Switcher -->
            <div class="theme-segmented-bar row items-center no-wrap q-mt-md q-pa-xs">
              <div
                class="segmented-tab row items-center justify-center flex-1 cursor-pointer"
                :class="{ 'segmented-tab-active': !$q.dark.isActive }"
                @click="setTheme(false)"
              >
                <q-icon name="light_mode" size="15px" class="q-mr-xs" />
                <span>Light</span>
              </div>
              <div
                class="segmented-tab row items-center justify-center flex-1 cursor-pointer"
                :class="{ 'segmented-tab-active': $q.dark.isActive }"
                @click="setTheme(true)"
              >
                <q-icon name="dark_mode" size="15px" class="q-mr-xs" />
                <span>Dark</span>
              </div>
            </div>
          </div>

          <!-- SPACIOUS NAVIGATION MENU -->
          <div class="sidebar-nav-container q-px-lg q-pt-md">
            <div class="sidebar-section-label q-px-sm q-mb-sm">
              MAIN NAVIGATION
            </div>

            <q-list class="sidebar-nav-list q-gutter-y-sm">
              <q-item
                v-for="item in navItems"
                :key="item.path"
                :to="item.path"
                clickable
                v-ripple
                active-class="nav-item-active"
                class="sidebar-nav-item row items-center no-wrap"
              >
                <q-item-section avatar class="nav-icon-slot">
                  <q-icon :name="item.icon" size="22px" class="nav-glyph" />
                </q-item-section>
                <q-item-section class="nav-label text-weight-bold">
                  {{ item.label }}
                </q-item-section>
              </q-item>
            </q-list>
          </div>
        </div>

        <!-- FOOTER: CLEAN ISOLATED LOGOUT -->
        <div class="sidebar-footer-section q-pa-lg">
          <button
            type="button"
            class="sidebar-logout-btn row items-center justify-center full-width cursor-pointer"
            @click="handleLogout"
          >
            <q-icon name="logout" size="18px" class="q-mr-sm" />
            <span class="text-weight-bolder">Sign Out Account</span>
          </button>
        </div>

      </div>
    </q-drawer>

    <!-- MAIN PAGE CONTAINER -->
    <q-page-container>
      <router-view />
    </q-page-container>

  </q-layout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
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
  $q.dark.toggle()
  localStorage.setItem('admin_dark_mode', $q.dark.isActive ? 'true' : 'false')
}

const setTheme = (isDark) => {
  $q.dark.set(isDark)
  localStorage.setItem('admin_dark_mode', isDark ? 'true' : 'false')
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
  if (savedDarkMode === 'true') {
    $q.dark.set(true)
  }
  runInitialLoader()
})

const handleLogout = () => {
  $q.dialog({
    title: 'Confirm Sign Out',
    message: 'Are you sure you want to log out of the administrator panel?',
    cancel: { flat: true, color: 'grey-7', label: 'Cancel', noCaps: true, class: 'q-px-md text-weight-bold' },
    ok: { unelevated: true, color: 'red-9', label: 'Sign Out', noCaps: true, class: 'q-px-md text-weight-bold' },
    persistent: true
  }).onOk(async () => {
    try {
      await api.post('/logout')
    } catch {
      // Ignore network errors
    }
    sessionStorage.removeItem('admin_session_loaded')
    localStorage.removeItem('auth_token')
    localStorage.removeItem('auth_user')
    localStorage.removeItem('auth_role')
    router.push('/login')
  })
}
</script>

<style scoped>
.admin-layout {
  min-height: 100vh;
}

.font-mono {
  font-family: 'SFMono-Regular', Consolas, Menlo, monospace;
}

.flex-1 {
  flex: 1;
}

/* ==========================================================
   INITIAL ENTRY SPLASH SCREEN
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
   SPACIOUS & EXPANDED RED SIDEBAR DRAWER
========================================================== */
:deep(.q-drawer.admin-fixed-sidebar),
:deep(.admin-fixed-sidebar),
:deep(.admin-fixed-sidebar .q-drawer__content) {
  background: linear-gradient(180deg, #991b1b 0%, #7f1d1d 55%, #581010 100%) !important;
  color: #ffffff !important;
}

.sidebar-wrapper {
  background: transparent !important;
  min-height: 100%;
}

.sidebar-brand-section {
  border-bottom: 1px solid rgba(255, 255, 255, 0.12);
}

.brand-canopy-card {
  background: rgba(0, 0, 0, 0.22) !important;
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 16px;
  position: relative;
  overflow: hidden;
}

.brand-awning-roof {
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 4px;
  display: flex;
}
.brand-awning-roof span { flex: 1; }
.awn-red { background: #b91c1c; }
.awn-white { background: #fee2e2; }

/* Expanded Logo Housing */
.sidebar-logo-frame {
  background: #ffffff !important;
  border-radius: 12px;
  padding: 10px 16px;
  width: 100%;
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.22);
}

.logo-img-expanded {
  width: 100%;
  max-width: 180px;
  height: 52px;
  object-fit: contain;
  display: block;
}

.store-badge-tag {
  color: #fecaca !important;
  font-size: 10px;
  font-weight: 800;
  letter-spacing: 0.08em;
}

/* Header Segmented Pill Theme Switcher */
.theme-segmented-bar {
  background: rgba(0, 0, 0, 0.35);
  border: 1px solid rgba(255, 255, 255, 0.14);
  border-radius: 999px;
  width: 100%;
}

.segmented-tab {
  padding: 6px 12px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 700;
  color: rgba(255, 255, 255, 0.7);
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  user-select: none;
}

.segmented-tab:hover {
  color: #ffffff;
}

.segmented-tab-active {
  background: #ffffff;
  color: #991b1b !important;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

/* Airy Navigation Menu */
.sidebar-section-label {
  font-size: 10.5px;
  font-weight: 800;
  color: #fca5a5 !important;
  letter-spacing: 0.08em;
}

.sidebar-nav-list {
  background: transparent !important;
  border: none !important;
}

.sidebar-nav-item {
  border-radius: 12px;
  color: #ffffff !important;
  opacity: 0.88;
  min-height: 48px;
  padding: 10px 16px !important;
  background: transparent !important;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.nav-icon-slot {
  min-width: 32px !important;
  max-width: 32px !important;
  padding-right: 12px !important;
  display: flex;
  align-items: center;
  justify-content: center;
}

.nav-glyph {
  color: #ffffff !important;
  font-size: 22px !important;
}

.nav-label {
  color: #ffffff !important;
  font-size: 14px;
  letter-spacing: -0.01em;
}

.sidebar-nav-item:hover {
  opacity: 1;
  background: rgba(255, 255, 255, 0.15) !important;
  transform: translateX(3px);
}

.nav-item-active {
  opacity: 1 !important;
  background: #ffffff !important;
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.25);
}

.nav-item-active .nav-label,
.nav-item-active .nav-glyph {
  color: #991b1b !important;
}

/* Footer Section & Sign Out Button */
.sidebar-footer-section {
  border-top: 1px solid rgba(255, 255, 255, 0.12);
  background: rgba(0, 0, 0, 0.18) !important;
}

.sidebar-logout-btn {
  background: rgba(0, 0, 0, 0.25) !important;
  border: 1px solid rgba(255, 255, 255, 0.15);
  color: #fecaca !important;
  border-radius: 12px;
  padding: 12px;
  font-size: 13.5px;
  transition: all 0.2s ease;
}

.sidebar-logout-btn:hover {
  background: #b91c1c !important;
  color: #ffffff !important;
  border-color: #b91c1c;
  box-shadow: 0 4px 14px rgba(185, 28, 28, 0.4);
}

/* Mobile Header */
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
</style>