// Scroll-reveal handler for Quasar's v-intersection directive.
//
//   <section v-intersection.once="onReveal" class="reveal"> ... </section>
//
// The directive (registered in quasar.config.js) owns the IntersectionObserver; this
// decides what happens when an element comes into view, and backstops the one case the
// observer cannot cover.
//
// QIntersection was the obvious alternative but is wrong for grids: it keeps its slot out
// of the DOM until visible, so every wrapper would collapse to zero height, stack at the
// top of the page, and all intersect at once.

const VISIBLE_CLASS = 'reveal--in'

// How long scrolling must be quiet before the backstop runs. Long enough never to
// pre-empt the observer mid-scroll, short enough to be imperceptible when it does fire.
const SETTLE_MS = 160

let sweepAttached = false
let settleTimer = null

/*
  Backstop.

  IntersectionObserver samples position; it does not promise an entry for every element
  the viewport crosses. A jump that moves an element from below the fold to above it in a
  single frame — the End key, an anchor link, a restored scroll position, a hard flick —
  produces no entry at all, and that element would sit at opacity 0 permanently. Measured:
  without this, jumping to the bottom of the home page left one section invisible.

  Invisible content is a far worse failure than a missed animation, so anything already
  scrolled into view is shown outright.
*/
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

// One listener pair for the whole app, not one per component using the composable, and
// deliberately never removed: every route brings fresh .reveal elements, so tearing it
// down on unmount would leave the next page without a backstop. It is bounded (two passive
// listeners for the session) and the debounced handler early-returns the moment nothing is
// pending, so a fully-revealed page costs one querySelectorAll per 160ms of scrolling.
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

