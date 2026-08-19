const AUTH_KEYS = ['auth_token', 'auth_user', 'auth_role', 'consumer_selected_order_id']

// Single source of truth for what counts as "session data" — used by both the logout
// flow and the global 401 handler, so they can never drift out of sync with each other.
export function clearAuthStorage() {
  for (const key of AUTH_KEYS) {
    localStorage.removeItem(key)
  }
}
