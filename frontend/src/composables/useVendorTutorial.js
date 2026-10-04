import { ref } from 'vue'
import { api } from '@/boot/axios'

// Onboarding state for the vendor guided tour.
//
// The tour is offered once, on the vendor's very first login. The account row keeps
// tutorial_seen_at (POST /vendor/tutorial/seen), so a second device or a cleared browser
// does not bring it back. The local flag, stored per user id, still answers straight away
// and covers a backend that has not run the migration yet.
// The version suffix is here so a future rewrite of the tour can re-introduce itself
// without clobbering the key this one wrote.
const STORAGE_PREFIX = 'vendor_tutorial_v1'

export const TUTORIAL_COMPLETED = 'completed'
export const TUTORIAL_SKIPPED = 'skipped'
// Written the moment the welcome card is shown, so closing the tab or logging out without
// pressing Skip or Start still counts as the one time it was offered.
export const TUTORIAL_OFFERED = 'offered'

// VendorTutorial.vue is mounted once in VendorLayout, but the Replay button lives on the
// profile page, which is a child route. Bumping this counter is how the child asks the
// layout-level component to run the tour again.
const replaySignal = ref(0)

function readAuthUser() {
  try {
    const raw = localStorage.getItem('auth_user')
    return raw ? JSON.parse(raw) : null
  } catch {
    return null
  }
}

function currentVendorId() {
  return readAuthUser()?.user_id ?? null
}

// What the login response said about this account, before any request of our own.
function seenOnServer() {
  return !!readAuthUser()?.tutorial_seen_at
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

  const hasSeenLocally = () => {
    const state = getTutorialState()
    return state === TUTORIAL_COMPLETED || state === TUTORIAL_SKIPPED || state === TUTORIAL_OFFERED
  }

  const hasSeenTutorial = () => hasSeenLocally() || seenOnServer()

  // Saves the flag on the account and mirrors it into the stored user, so the next login's
  // check agrees even before the login response replaces it. A failed request is left
  // alone: the local flag already stops the tour on this browser.
  const markTutorialSeen = async () => {
    if (!getTutorialState()) setTutorialState(TUTORIAL_OFFERED)
    if (seenOnServer()) return
    try {
      const { data } = await api.post('/vendor/tutorial/seen')
      const user = readAuthUser()
      if (user) {
        user.tutorial_seen_at = data?.tutorial_seen_at || new Date().toISOString()
        localStorage.setItem('auth_user', JSON.stringify(user))
      }
    } catch {
      // Offline, or the backend has no such route yet.
    }
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
    hasSeenLocally,
    markTutorialSeen,
    isApprovedVendor,
    shouldAutoStart,
    requestReplay
  }
}
