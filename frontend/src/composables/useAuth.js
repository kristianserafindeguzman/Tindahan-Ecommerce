import { useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { api } from '@/boot/axios'

export function useAuth () {
  const router = useRouter()
  const $q = useQuasar()

  const logout = () => {
    $q.dialog({
      class: 'glass-logout-dialog',
      title: 'Confirm Logout',
      message: 'Are you sure you want to log out of your account?',
      cancel: { flat: true, color: 'grey-7', label: 'Cancel', noCaps: true, class: 'glass-logout-btn-cancel q-px-md' },
      ok: { unelevated: true, label: 'Logout', noCaps: true, class: 'glass-logout-btn-ok q-px-md' },
      persistent: true
    }).onOk(async () => {
      try {
        await api.post('/logout')
      } catch {
        // Token may already be invalid/expired
      }

      localStorage.removeItem('auth_token')
      localStorage.removeItem('auth_user')
      localStorage.removeItem('auth_role')

      router.push('/login')
    })
  }

  return { logout }
}