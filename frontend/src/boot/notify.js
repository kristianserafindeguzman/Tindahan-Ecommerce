import { boot } from 'quasar/wrappers'
import { Notify } from 'quasar'

/**
 * Toast styling, set once here rather than at the ~96 $q.notify call sites.
 *
 * Quasar's stock types paint a saturated slab (solid green, solid red) with white text,
 * which shares nothing with the rest of the consumer surface. Re-registering the built-in
 * type names keeps every existing `type: 'positive'` call working while changing how it
 * looks: a white card with a tinted status disc, the same language as .notif-icon,
 * .info-icon and the dialog headers.
 *
 * The visual treatment lives in app.scss under .app-toast — the notification is
 * teleported to <body>, so it cannot be styled from a scoped block.
 */
export default boot(() => {
  Notify.setDefaults({
    position: 'bottom',
    timeout: 2800,
    classes: 'app-toast'
  })

  const types = {
    positive: { icon: 'o_check_circle', classes: 'app-toast app-toast--positive' },
    negative: { icon: 'o_error_outline', classes: 'app-toast app-toast--negative' },
    warning: { icon: 'o_warning_amber', classes: 'app-toast app-toast--warning' },
    info: { icon: 'o_info', classes: 'app-toast app-toast--info' }
  }

  for (const [name, options] of Object.entries(types)) {
    Notify.registerType(name, options)
  }
})
