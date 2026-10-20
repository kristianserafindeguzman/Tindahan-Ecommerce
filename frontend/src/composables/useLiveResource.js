import { onMounted, onBeforeUnmount } from 'vue'
import { liveUpdates } from '@/services/liveUpdates'

export const backgroundOptions = background =>
  background ? { timeout: 15000, headers: { 'X-Background-Sync': '1' } } : {}

export function useLiveResource(topics, refresh) {
  let unregister
  onMounted(() => {
    unregister = liveUpdates.register(topics, refresh)
  })
  onBeforeUnmount(() => {
    unregister?.()
  })
}
