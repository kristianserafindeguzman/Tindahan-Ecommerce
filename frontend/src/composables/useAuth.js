import { useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { api } from '@/boot/axios'
import { clearAuthStorage } from '@/utils/authStorage'
import LogoutConfirmDialog from '@/components/modals/LogoutConfirmDialog.vue'

export function useAuth() {
  const router = useRouter()
  const $q = useQuasar()

  const logout = (force = false) => {
    if (force === true) {
      executeLogout()
      return
    }

    $q.dialog({ component: LogoutConfirmDialog }).onOk(executeLogout)
  }

  const executeLogout = async () => {
    try {
      await api.post('/logout')
    } catch {
      // Token may already be invalid/expired
    }

    clearAuthStorage()

    router.push('/login')
  }

  return { logout }
}
