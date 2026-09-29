<template>
  <!-- A store's full profile in a wide dialog: the profile scrolls, Close sits over the photo, and the page's buttons stay at the foot. -->
  <q-dialog v-model="open">
    <q-card class="vp-dialog spd">
      <div class="spd-scroll">
        <StoreProfile v-bind="$attrs">
          <template v-if="$slots.badge" #badge><slot name="badge" /></template>
          <template v-if="$slots.notice" #notice
            ><slot name="notice"
          /></template>
        </StoreProfile>
      </div>

      <q-btn
        v-close-popup
        round
        dense
        unelevated
        icon="o_close"
        class="spd-close"
        aria-label="Close"
      />

      <div v-if="$slots.actions" class="vp-dialog-actions spd-actions">
        <slot name="actions" />
      </div>
    </q-card>
  </q-dialog>
</template>

<script setup>
import StoreProfile from '@/components/shared/StoreProfile.vue'

// Every attribute is the store's details, passed straight to the profile rather than to the dialog.
defineOptions({ inheritAttrs: false })

const open = defineModel({ type: Boolean, default: false })
</script>

<style scoped>
/* Wide enough for the three cards side by side. */
.spd {
  position: relative;

  display: flex;
  flex-direction: column;

  width: 960px;
  max-height: calc(100vh - 48px);
}

/* Quasar caps a dialog at 560px wide, so this one restates its own limit. */
.q-dialog__inner--minimized > .spd {
  max-width: calc(100vw - 32px);
}

.spd-scroll {
  flex: 1 1 auto;

  overflow-y: auto;

  min-height: 0;
  padding: 24px 24px 20px;
}

/* Close sits over the photo's corner. */
.spd-close {
  position: absolute;
  top: 34px;
  right: 34px;
  z-index: 2;

  background: rgba(255, 255, 255, 0.94);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.16);

  color: var(--c-text-2);
}

.spd-actions {
  flex-shrink: 0;

  border-top: 1px solid var(--c-hairline);
}

@media (max-width: 600px) {
  :global(.q-dialog__inner--minimized:has(.spd)) {
    padding: 16px;
  }

  .spd {
    max-height: calc(100vh - 32px);
  }

  .spd-scroll {
    padding: 16px;
  }

  .spd-close {
    top: 24px;
    right: 24px;
  }

  .spd-actions {
    padding: 14px 16px 16px;
  }

  .spd-actions :slotted(.q-btn) {
    flex: 1;

    min-width: 0;
  }
}
</style>
