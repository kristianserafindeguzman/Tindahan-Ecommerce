import { ref } from 'vue'

// Onboarding state for the vendor guided tour.
//
// The flag is stored per user id, so two vendors sharing a browser each get their own
// first run, and a vendor who finished or skipped never sees it pop up again.
// The version suffix is here so a future rewrite of the tour can re-introduce itself
// without clobbering the key this one wrote.
const STORAGE_PREFIX = 'vendor_tutorial_v1'

export const TUTORIAL_COMPLETED = 'completed'
export const TUTORIAL_SKIPPED = 'skipped'

// VendorTutorial.vue is mounted once in VendorLayout, but the Replay button lives on the
// profile page, which is a child route. Bumping this counter is how the child asks the
// layout-level component to run the tour again.
const replaySignal = ref(0)

function currentVendorId() {
  try {
    const raw = localStorage.getItem('auth_user')
    if (!raw) return null
    const user = JSON.parse(raw)
    return user?.user_id ?? null
  } catch {
    return null
  }
}

// Falls back to a shared key when the stored user is unreadable, so a broken auth_user
// still cannot make the tour reappear on every page load.
function storageKey() {
  const id = currentVendorId()
  return id ? `${STORAGE_PREFIX}:${id}` : `${STORAGE_PREFIX}:unknown`
}

export function useVendorTutorial() {
  const getTutorialState = () => {
    try {
      return localStorage.getItem(storageKey())
    } catch {
      return null
    }
  }

  const setTutorialState = (state) => {
    try {
      localStorage.setItem(storageKey(), state)
    } catch {
      // Private mode or a full quota: the tour simply offers itself again next login.
    }
  }

  const hasSeenTutorial = () => {
    const state = getTutorialState()
    return state === TUTORIAL_COMPLETED || state === TUTORIAL_SKIPPED
  }

  // The router guard already keeps pending and rejected vendors out of /vendor, so this
  // only has to confirm the session is a vendor one and not mid-approval.
  const isApprovedVendor = () => {
    try {
      if (localStorage.getItem('auth_role') !== 'Vendor') return false
      const status = localStorage.getItem('vendor_status')
      return status !== 'pending' && status !== 'rejected'
    } catch {
      return false
    }
  }

  const shouldAutoStart = () => isApprovedVendor() && !hasSeenTutorial()

  const requestReplay = () => {
    replaySignal.value += 1
  }

  return {
    replaySignal,
    getTutorialState,
    setTutorialState,
    hasSeenTutorial,
    isApprovedVendor,
    shouldAutoStart,
    requestReplay
  }
}
