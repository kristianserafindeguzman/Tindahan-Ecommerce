const AUTH_KEYS = ['auth_token', 'auth_user', 'auth_role', 'consumer_selected_order_id']

// Single source of truth for session data, used by both logout and the global 401 handler so they never drift apart.
export function clearAuthStorage() {
  for (const key of AUTH_KEYS) {
    localStorage.removeItem(key)
  }
}
