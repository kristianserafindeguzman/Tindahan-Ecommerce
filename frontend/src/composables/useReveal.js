// Scroll-reveal handler for Quasar's v-intersection directive, with a scroll backstop for elements the observer never reports.

const VISIBLE_CLASS = 'reveal--in'

// How long scrolling must be quiet before the backstop runs, long enough not to pre-empt the observer.
const SETTLE_MS = 160

let sweepAttached = false
let settleTimer = null

/* Backstop that shows any element already scrolled into view, since a single-frame jump can skip it and leave it invisible. */
const sweep = () => {
  const pending = document.querySelectorAll(`.reveal:not(.${VISIBLE_CLASS})`)
  if (pending.length === 0) return

  const viewportHeight = window.innerHeight

  // Read every rect before touching a class, so this cannot thrash layout.
  const reached = []
  for (const el of pending) {
    if (el.getBoundingClientRect().top < viewportHeight) reached.push(el)
  }
  reached.forEach((el) => el.classList.add(VISIBLE_CLASS))
}

const queueSweep = () => {
  clearTimeout(settleTimer)
  settleTimer = setTimeout(sweep, SETTLE_MS)
}

// One app-wide listener pair that is never removed, because every route brings new .reveal elements that need the backstop.
const attachSweep = () => {
  if (sweepAttached) return
  sweepAttached = true
  window.addEventListener('scroll', queueSweep, { passive: true })
  window.addEventListener('resize', queueSweep, { passive: true })
}

export function useReveal() {
  attachSweep()

  const onReveal = (entry) => {
    if (entry.isIntersecting) {
      entry.target.classList.add(VISIBLE_CLASS)
    }
  }

  return { onReveal }
}

export default useReveal
