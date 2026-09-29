<template>
  <!-- The Filters button: a dropdown panel on desktop, and a centred dialog on phones and tablets, where a dropdown would cover the list it filters. -->
  <q-btn outline no-caps no-wrap color="primary" icon="o_tune" label="Filters" class="vp-pill-btn fs-btn" @click="openDialog">
    <span v-if="count" class="fs-count">{{ count }}</span>
    <q-menu v-if="!useDialog" anchor="bottom right" self="top right" :offset="[0, 6]">
      <div class="fs-panel">
        <div class="fs-head">
          <span class="fs-title">{{ title }}</span>
          <q-btn flat dense no-caps color="primary" label="Clear" :disable="!count" @click="$emit('clear')" />
        </div>
        <slot />
      </div>
    </q-menu>
  </q-btn>

  <q-dialog v-if="useDialog" v-model="dialogOpen">
    <q-card class="vp-dialog fs-dialog">
      <div class="fs-dialog-head">
        <span class="fs-title">{{ title }}</span>
        <q-btn v-close-popup flat round dense icon="o_close" class="vp-dialog-close" aria-label="Close filters" />
      </div>
      <div class="fs-dialog-body">
        <slot />
      </div>
      <div class="vp-dialog-actions fs-dialog-actions">
        <q-btn outline no-caps color="primary" label="Clear" class="vp-dialog-btn" :disable="!count" @click="$emit('clear')" />
        <q-btn v-close-popup unelevated no-caps color="primary" :label="doneLabel" class="vp-dialog-btn" />
      </div>
    </q-card>
  </q-dialog>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useQuasar } from 'quasar'

const props = defineProps({
  title: { type: String, default: 'Filters' },
  // How many filters are in use, shown on the button and enabling Clear.
  count: { type: Number, default: 0 },
  // How many rows the filters leave, so the dialog's button can say "Show 5 orders".
  resultCount: { type: Number, default: null },
  noun: { type: String, default: 'result' }
})

defineEmits(['clear'])

const $q = useQuasar()
const useDialog = computed(() => $q.screen.lt.md)
const dialogOpen = ref(false)

const openDialog = () => {
  if (useDialog.value) dialogOpen.value = true
}

const doneLabel = computed(() => {
  if (props.resultCount === null) return 'Done'
  return `Show ${props.resultCount} ${props.noun}${props.resultCount === 1 ? '' : 's'}`
})
</script>

<style scoped>
/* Keeps its full width beside the search box, so the icon never stacks above the label. */
.fs-btn {
  flex-shrink: 0;
}

.fs-count {
  display: inline-flex;
  align-items: center;
  justify-content: center;

  min-width: 18px;
  height: 18px;
  margin-left: 6px;
  padding: 0 5px;

  border-radius: var(--r-pill);

  background: var(--c-brand);

  font-size: 11px;
  font-weight: 700;

  color: #ffffff;
}

.fs-panel {
  display: flex;
  flex-direction: column;

  gap: 14px;
  width: 330px;
  max-width: calc(100vw - 32px);
  padding: 14px 16px 16px;
}

.fs-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.fs-title {
  font-size: var(--fs-md);
  font-weight: 700;

  color: var(--c-text);
}

/* DIALOG — a centred card on phones and tablets, its fields scrolling between a fixed title and a line above the buttons. */
.fs-dialog {
  display: flex;
  flex-direction: column;

  max-height: calc(100vh - 48px);
}

.fs-dialog-head {
  position: relative;
  flex-shrink: 0;

  padding: 20px 56px 4px 20px;
}

.fs-dialog-body {
  display: flex;
  flex-direction: column;

  gap: 14px;
  min-height: 0;
  padding: 12px 20px 18px;
  overflow-y: auto;
}

.fs-dialog-actions {
  flex-shrink: 0;

  padding: 16px 20px 20px;

  border-top: 1px solid var(--c-hairline);
}

/* Clear and Show share the row equally at every phone width. */
.fs-dialog-actions .vp-dialog-btn {
  flex: 1;

  min-width: 0;
}
</style>
