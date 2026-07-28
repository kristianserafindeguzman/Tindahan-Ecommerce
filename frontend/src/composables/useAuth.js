import { useRouter } from 'vue-router'
import { api } from '@/boot/axios'

export function useAuth () {
  const router = useRouter()

  const logout = async () => {
    try {
      await api.post('/logout')
    } catch {
      // Token may already be invalid/expired — proceed with local cleanup anyway
    }

    localStorage.removeItem('auth_token')
    localStorage.removeItem('auth_user')
    localStorage.removeItem('auth_role')

    router.push('/login')
  }

  return { logout }
}