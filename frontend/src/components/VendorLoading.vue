<template>
  <transition name="fade-scale">
    <div 
      v-if="showing" 
      class="fullscreen flex flex-center z-max" 
      style="background: rgba(248, 250, 252, 0.85); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px);"
    >
      <!-- Subtle Ambient Background Glows -->
      <div class="bg-glow bg-glow-primary"></div>
      <div class="bg-glow bg-glow-secondary"></div>

      <!-- Premium Glass Loading Card -->
      <div class="premium-glass-card text-center relative-position overflow-hidden" style="width: 320px; max-width: 85vw; padding: 48px 24px;">
        
        <!-- Top Indeterminate Red Progress Bar -->
        <q-linear-progress indeterminate color="red-9" class="absolute-top" style="height: 4px;" />

        <!-- Pulsing Logo Container -->
        <div class="logo-container q-mb-lg relative-position flex flex-center mx-auto" style="width: 100px; height: 100px;">
          <div class="pulse-ring"></div>
          <div class="logo-glass-box shadow-2">
            <img src="@/assets/tindahan-mobile.png" alt="Tindahan" class="loading-logo" />
          </div>
        </div>

        <!-- Typography -->
        <h2 class="text-h6 text-weight-bolder text-slate-800 q-ma-none tracking-tight">Tindahan Vendor</h2>
        <p class="text-body2 text-slate-500 q-mt-sm font-medium leading-snug">{{ message }}</p>

        <!-- Custom Brand Spinner -->
        <div class="q-mt-lg flex flex-center">
          <q-spinner-dots color="red-9" size="40px" />
        </div>
        
      </div>
    </div>
  </transition>
</template>

<script setup>


const props = defineProps({
  showing: {
    type: Boolean,
    default: true
  },
  message: {
    type: String,
    default: 'Preparing your workspace...'
  }
})
</script>

<style scoped>
/* Typography Extensions */
.text-slate-800 { color: #1e293b; }
.text-slate-500 { color: #64748b; }
.font-medium { font-weight: 500; }
.tracking-tight { letter-spacing: -0.02em; }
.leading-snug { line-height: 1.375; }

/* Z-Index for Fullscreen Overlay */
.z-max { z-index: 9999 !important; }

/* Subtle Ambient Glows */
.bg-glow {
  position: absolute;
  width: 500px;
  height: 500px;
  border-radius: 50%;
  filter: blur(140px);
  z-index: 0;
  opacity: 0.2; 
  pointer-events: none;
}
.bg-glow-primary {
  top: -100px;
  left: -100px;
  background: radial-gradient(circle, rgba(185, 28, 28, 0.3) 0%, transparent 70%); 
}
.bg-glow-secondary {
  bottom: -100px;
  right: -100px;
  background: radial-gradient(circle, rgba(15, 23, 42, 0.2) 0%, transparent 70%); 
}

/* Glass Modal Override */
.premium-glass-card {
  background: rgba(255, 255, 255, 0.98);
  border: 1px solid rgba(226, 232, 240, 0.8);
  border-radius: 20px;
  box-shadow: 0 20px 50px rgba(15, 23, 42, 0.1); 
  z-index: 1;
}

/* Logo Box & Animations */
.logo-glass-box {
  width: 80px;
  height: 80px;
  background: #ffffff;
  border-radius: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(226, 232, 240, 0.8);
  z-index: 2;
  animation: float-logo 3s ease-in-out infinite;
}

.loading-logo {
  width: 56px;
  height: auto;
  object-fit: contain;
}

/* Outer Pulsing Ring */
.pulse-ring {
  position: absolute;
  width: 100px;
  height: 100px;
  background: rgba(185, 28, 28, 0.1);
  border-radius: 50%;
  z-index: 1;
  animation: pulse-ring-anim 2s cubic-bezier(0.215, 0.61, 0.355, 1) infinite;
}

/* Keyframes */
@keyframes float-logo {
  0% { transform: translateY(0px); }
  50% { transform: translateY(-6px); }
  100% { transform: translateY(0px); }
}

@keyframes pulse-ring-anim {
  0% { transform: scale(0.8); opacity: 0; }
  50% { opacity: 1; }
  100% { transform: scale(1.4); opacity: 0; }
}

/* Vue Transition for Smooth Entrance/Exit */
.fade-scale-enter-active,
.fade-scale-leave-active {
  transition: opacity 0.3s ease, transform 0.3s ease;
}
.fade-scale-enter-from,
.fade-scale-leave-to {
  opacity: 0;
  transform: scale(0.95);
}
</style>