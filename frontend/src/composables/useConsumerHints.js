import { ref } from 'vue'

// Small contextual hints on the consumer side: a pointer at the address pill and the
// pickup-only notice in the cart. Each one is shown until the shopper dismisses it, then
// never again.
//
// Stored per user id, so two shoppers sharing a browser each get their own first run.
// Signed-out visitors share one bucket, which is the best a device-local flag can do.
const STORAGE_PREFIX = 'consumer_hints_v1'

export const HINT_LOCATION_SETUP = 'location-setup'
export const HINT_PICKUP_ONLY = 'pickup-only'

// localStorage is not reactive, so components read through this counter: bumping it after a
// dismissal re-runs every isDismissed() call site, including ones on other pages.
const dismissedVersion = ref(0)

function currentUserId() {
  try {
    const raw = localStorage.getItem('auth_user')
    if (!raw) return null
    const user = JSON.parse(raw)
    return user?.user_id ?? null
  } catch {
    return null
  }
}

function storageKey(hint) {
  const id = currentUserId()
  return `${STORAGE_PREFIX}:${id ?? 'guest'}:${hint}`
}

export function useConsumerHints() {
  const isDismissed = (hint) => {
    // Read so Vue tracks it; the value itself is irrelevant.
    void dismissedVersion.value
    try {
      return localStorage.getItem(storageKey(hint)) === '1'
    } catch {
      // No storage means no memory of a dismissal, so the hint would reappear on every
      // page. Treating it as dismissed is the quieter failure.
      return true
    }
  }

  const dismissHint = (hint) => {
    try {
      localStorage.setItem(storageKey(hint), '1')
    } catch {
      // Dismissal still hides it for this page view.
    }
    dismissedVersion.value += 1
  }

  return { isDismissed, dismissHint }
}
