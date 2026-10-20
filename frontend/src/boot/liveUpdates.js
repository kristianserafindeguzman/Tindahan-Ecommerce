import { boot } from 'quasar/wrappers'
import { liveUpdates } from '@/services/liveUpdates'

export default boot(({ router }) => {
  liveUpdates.start()
  router.afterEach(() => {
    void liveUpdates.syncSession()
  })
  if (import.meta.hot) import.meta.hot.dispose(() => liveUpdates.stop())
})
