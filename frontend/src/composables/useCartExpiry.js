import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import { useCart } from '@/composables/useCart'
import { useConsumerLanguage } from '@/composables/useConsumerLanguage'

/** Live expiry state for cart reservations: the server's `isExpired` is only accurate at fetch time, so a local clock keeps an open page honest. */
export function useCartExpiry() {
  const { items, fetchCart } = useCart()
  const { t } = useConsumerLanguage()

  const now = ref(Date.now())
  let timer = null

  onMounted(() => {
    timer = setInterval(() => {
      now.value = Date.now()
    }, 1000)
  })

  onBeforeUnmount(() => clearInterval(timer))

  const hasExpired = (item) =>
    item.isExpired || (!!item.expiresAt && new Date(item.expiresAt).getTime() <= now.value)

  const formatTimeLeft = (expiresAt) => {
    if (!expiresAt) return null
    const diff = new Date(expiresAt).getTime() - now.value
    if (diff <= 0) return t('Expired')
    const minutes = Math.floor(diff / 60000)
    const seconds = Math.floor((diff % 60000) / 1000)
    return t('Expires in {min}:{sec}', { min: minutes, sec: seconds.toString().padStart(2, '0') })
  }

  // One refetch the moment a reservation lapses, so availability and the header badge catch up.
  const expiredCount = computed(() => items.value.filter(hasExpired).length)

  watch(expiredCount, (count, previousCount) => {
    if (count > previousCount) fetchCart({ silent: true })
  })

  return { now, hasExpired, formatTimeLeft, expiredCount }
}
