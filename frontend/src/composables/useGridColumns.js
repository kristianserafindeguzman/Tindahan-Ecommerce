import { ref, onBeforeUnmount, watch } from 'vue'

// Reads a CSS grid's live column count.
//
// The product/store grids use `repeat(auto-fill, minmax(160px, 1fr))`, so the column
// count is continuous with width rather than tied to breakpoints — measured: 6 columns
// from 1280px, 5 at 1024, 4 at 820, 2 at 390. Anything that needs whole rows (skeleton
// placeholders, "show N rows" slices) has to read the real value; a hardcoded number
// leaves a ragged part-row at most widths.
//
// Pass the template ref of the grid element. Works whether the ref lands on the loading
// grid or the loaded one, since both carry the same class and the same ref.
export function useGridColumns(gridEl, fallback = 4) {
  const columns = ref(fallback)
  let observer = null

  const measure = (el) => {
    // gridTemplateColumns resolves to a space-separated list of used sizes, so the count
    // of entries is the column count. Reads 'none' if the element is not a grid yet.
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
