import { boot } from 'quasar/wrappers'
import { Notify } from 'quasar'

/** Restyles every toast once here by re-registering Quasar's built-in types, with the look defined under .app-toast in app.scss. */
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
