<template>
  <!-- Wraps a page's own horizontally scrolling pill row; the slot's root element is the one that scrolls. -->
  <div ref="rootEl" class="pill-scroller">
    <slot />

    <button
      v-show="canScrollLeft"
      type="button"
      class="pill-scroller-arrow pill-scroller-arrow--left"
      :aria-label="t('Scroll left')"
      @click="scrollByPage(-1)"
    >
      <q-icon name="o_chevron_left" size="20px" />
    </button>

    <button
      v-show="canScrollRight"
      type="button"
      class="pill-scroller-arrow pill-scroller-arrow--right"
      :aria-label="t('Scroll right')"
      @click="scrollByPage(1)"
    >
      <q-icon name="o_chevron_right" size="20px" />
    </button>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { useConsumerLanguage } from '@/composables/useConsumerLanguage'

const { t } = useConsumerLanguage()

const rootEl = ref(null)
const canScrollLeft = ref(false)
const canScrollRight = ref(false)

let scrollEl = null
let resizeObserver = null
let mutationObserver = null

// A pixel of slack, because fractional widths can leave scrollLeft a hair short of the true end.
const update = () => {
  if (!scrollEl) return
  canScrollLeft.value = scrollEl.scrollLeft > 1
  canScrollRight.value = scrollEl.scrollLeft + scrollEl.clientWidth < scrollEl.scrollWidth - 1
}

// Most of a visible width per click, leaving one pill overlapping so the user keeps their place.
const scrollByPage = (direction) => {
  if (!scrollEl) return
  scrollEl.scrollBy({ left: direction * scrollEl.clientWidth * 0.75, behavior: 'smooth' })
}

onMounted(() => {
  scrollEl = rootEl.value?.firstElementChild || null
  if (!scrollEl) return

  scrollEl.addEventListener('scroll', update, { passive: true })

  // Box size changes on resize; pills arriving late or changing language change the content width instead.
  resizeObserver = new ResizeObserver(update)
  resizeObserver.observe(scrollEl)

  mutationObserver = new MutationObserver(update)
  mutationObserver.observe(scrollEl, { childList: true, subtree: true, characterData: true })

  update()
})

onBeforeUnmount(() => {
  scrollEl?.removeEventListener('scroll', update)
  resizeObserver?.disconnect()
  mutationObserver?.disconnect()
})
</script>

<style scoped>
.pill-scroller {
  position: relative;
}

/* Sits over the 36px pill track only, so the row's own bottom margin and padding don't pull it off-centre. */
.pill-scroller-arrow {
  position: absolute;
  top: 0;
  z-index: 1;

  display: flex;
  align-items: center;
  justify-content: center;

  width: 88px;
  height: 36px;
  padding: 0;

  border: none;

  color: var(--c-text-2);

  cursor: pointer;
}

/* Solid white under the arrow plus a 12px gap, then a fade, so a cut-off pill trails away instead of touching it. */
.pill-scroller-arrow--left {
  left: 0;
  justify-content: flex-start;

  background: linear-gradient(to right, #ffffff 48px, rgba(255, 255, 255, 0));
}

.pill-scroller-arrow--right {
  right: 0;
  justify-content: flex-end;

  background: linear-gradient(to left, #ffffff 48px, rgba(255, 255, 255, 0));
}

/* Drawn on the icon so the whole faded strip stays clickable; same shape, height, border and hover as .category-pill. */
.pill-scroller-arrow :deep(.q-icon) {
  display: flex;
  align-items: center;
  justify-content: center;

  width: 36px;
  height: 36px;

  border: 1px solid var(--c-border);
  border-radius: var(--r-sm);

  background: #ffffff;

  box-sizing: border-box;

  transition: border-color 0.15s, background-color 0.15s, color 0.15s;
}

.pill-scroller-arrow:hover :deep(.q-icon) {
  border-color: var(--c-brand-tint-3);
  background: var(--c-brand-tint);
}

.pill-scroller-arrow:focus-visible {
  outline: none;
}

.pill-scroller-arrow:focus-visible :deep(.q-icon) {
  box-shadow: 0 0 0 3px rgba(189, 36, 39, 0.25);
}
</style>
