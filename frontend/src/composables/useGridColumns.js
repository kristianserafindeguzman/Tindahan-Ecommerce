import { ref, onBeforeUnmount, watch } from 'vue'

// Reads a CSS grid's live column count from the element's template ref, so whole-row slices stay exact on an auto-fill grid.
export function useGridColumns(gridEl, fallback = 4) {
  const columns = ref(fallback)
  let observer = null

  const measure = (el) => {
    // The resolved gridTemplateColumns lists one size per column, and reads none before the element is a grid.
    const value = getComputedStyle(el).gridTemplateColumns
    if (!value || value === 'none') return
    const count = value.split(' ').length
    if (count > 0) columns.value = count
  }

  watch(gridEl, (el) => {
    observer?.disconnect()
    observer = null
    if (!el) return

    measure(el)
    observer = new ResizeObserver(() => measure(el))
    observer.observe(el)
  }, { immediate: true })

  onBeforeUnmount(() => observer?.disconnect())

  return { columns }
}

export default useGridColumns
