<template>
  <div class="context-hint" :class="arrow ? `context-hint--arrow-${arrow}` : null" role="status">
    <span class="context-hint-icon"><q-icon :name="icon" size="18px" /></span>

    <div class="context-hint-body">
      <div v-if="title" class="context-hint-title">{{ title }}</div>
      <p class="context-hint-text">{{ text }}</p>
      <slot />
    </div>

    <q-btn
      flat
      round
      dense
      icon="o_close"
      class="context-hint-close"
      :aria-label="dismissLabel"
      @click="$emit('dismiss')"
    />
  </div>
</template>

<script setup>
// A small, dismissible note shown next to the thing it explains. The parent decides when to
// render it and remembers the dismissal (see useConsumerHints); this component only draws it.
//
// One look for all of them, in the brand red the rest of the consumer UI uses, so a hint
// always reads as the same kind of thing wherever a shopper meets one.
defineProps({
  title: { type: String, default: '' },
  text: { type: String, required: true },
  icon: { type: String, default: 'o_lightbulb' },
  // Draws a pointer on the given edge, for a hint sitting directly under its target.
  arrow: { type: String, default: '' },
  dismissLabel: { type: String, default: 'Dismiss' }
})

defineEmits(['dismiss'])
</script>

<style scoped>
.context-hint {
  position: relative;

  display: flex;
  align-items: flex-start;

  gap: 10px;
  padding: 12px 40px 12px 12px;

  border: 1px solid var(--c-brand-tint-2);
  border-radius: var(--r-lg);

  background: var(--c-brand-tint);

  animation: context-hint-in 0.28s ease both;
}

@keyframes context-hint-in {
  from { opacity: 0; transform: translateY(-4px); }
  to { opacity: 1; transform: translateY(0); }
}

@media (prefers-reduced-motion: reduce) {
  .context-hint {
    animation: none;
  }
}

.context-hint-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;

  width: 28px;
  height: 28px;

  border-radius: 50%;

  background: var(--c-white);
  color: var(--c-brand);
}

.context-hint-body {
  min-width: 0;
}

.context-hint-title {
  margin-bottom: 2px;

  font-size: var(--fs-sm);
  font-weight: 700;

  color: var(--c-text);
}

.context-hint-text {
  margin: 0;

  font-size: var(--fs-xs);
  line-height: 1.55;

  color: var(--c-text-3);
}

/* Parked in the corner rather than in the flow, so a one-line hint stays one line. */
.context-hint-close {
  position: absolute;
  top: 6px;
  right: 6px;

  color: var(--c-muted);
}

.context-hint-close:hover {
  color: var(--c-text);
}

/* A pointer towards the control the hint explains, drawn as a rotated square so the hint's
   own border and fill carry through to it. */
.context-hint--arrow-top::before {
  content: '';

  position: absolute;
  top: -6px;
  left: 20px;

  width: 10px;
  height: 10px;

  border-top: 1px solid var(--c-brand-tint-2);
  border-left: 1px solid var(--c-brand-tint-2);

  background: var(--c-brand-tint);

  transform: rotate(45deg);
}

/* Phones: a tighter card, and a close button big enough to be a tap target rather than a
   click target, which needs the text to clear a little more room on the right. */
@media (max-width: 600px) {
  .context-hint {
    gap: 8px;
    padding: 10px 42px 10px 10px;
  }

  .context-hint-icon {
    width: 24px;
    height: 24px;
  }

  .context-hint-close {
    top: 3px;
    right: 3px;

    min-width: 36px;
    min-height: 36px;
  }
}
</style>
