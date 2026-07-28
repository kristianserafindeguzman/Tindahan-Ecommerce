<template>
  <q-page class="admin-page">
    <div v-if="checkingAccess" class="checking-access">
      <q-spinner color="primary" size="32px" />
    </div>

    <div v-else class="page-container">

      <!-- TOP BAR -->
      <div class="top-bar">
        <div class="top-bar-left">
          <img
            src="@/assets/tindahan-mobile.png"
            alt="Tindahan Logo"
            class="top-bar-logo"
          />
          <div>
            <div class="eyebrow">Admin Panel</div>
            <h1>Welcome back, {{ userName }}</h1>
          </div>
        </div>

        <q-btn
          label="Logout"
          no-caps
          outline
          icon="logout"
          class="logout-btn"
          @click="logout"
        />
      </div>

      <!-- STAT ROW -->
      <div class="stats-row">

        <div class="stat-item" @click="$router.push('/admin/approvals')">
          <div class="stat-icon-circle">
            <q-icon name="pending_actions" size="20px" />
          </div>
          <div class="stat-text">
            <div class="stat-value">{{ stats.pending_approvals }}</div>
            <div class="stat-label">Pending Approvals</div>
          </div>
        </div>

        <div class="stat-item" @click="$router.push('/admin/vendors')">
          <div class="stat-icon-circle">
            <q-icon name="store" size="20px" />
          </div>
          <div class="stat-text">
            <div class="stat-value">{{ stats.total_vendors }}</div>
            <div class="stat-label">Total Vendors</div>
          </div>
        </div>

        <div class="stat-item" @click="$router.push('/admin/consumers')">
          <div class="stat-icon-circle">
            <q-icon name="people" size="20px" />
          </div>
          <div class="stat-text">
            <div class="stat-value">{{ stats.total_consumers }}</div>
            <div class="stat-label">Total Consumers</div>
          </div>
        </div>

        <div class="stat-item">
          <div class="stat-icon-circle">
            <q-icon name="group" size="20px" />
          </div>
          <div class="stat-text">
            <div class="stat-value">{{ stats.total_users }}</div>
            <div class="stat-label">Total Users</div>
          </div>
        </div>

      </div>

      <!-- QUICK ACTIONS -->
      <div class="section-title">Quick Actions</div>

      <div class="actions-list">
        <div class="action-row" @click="$router.push('/admin/approvals')">
          <div class="action-row-left">
            <q-icon name="fact_check" class="action-icon" />
            <div>
              <div class="action-label">Review Applications</div>
              <div class="action-desc">Approve or reject pending vendor requests</div>
            </div>
          </div>
          <q-icon name="chevron_right" class="action-arrow" />
        </div>

        <div class="action-row" @click="$router.push('/admin/vendors')">
          <div class="action-row-left">
            <q-icon name="manage_accounts" class="action-icon" />
            <div>
              <div class="action-label">Manage Vendors</div>
              <div class="action-desc">View, suspend, or reinstate vendor accounts</div>
            </div>
          </div>
          <q-icon name="chevron_right" class="action-arrow" />
        </div>

        <div class="action-row" @click="$router.push('/admin/consumers')">
          <div class="action-row-left">
            <q-icon name="supervisor_account" class="action-icon" />
            <div>
              <div class="action-label">Manage Consumers</div>
              <div class="action-desc">View and manage consumer accounts</div>
            </div>
          </div>
          <q-icon name="chevron_right" class="action-arrow" />
        </div>
      </div>

    </div>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '@/boot/axios'
import { useAuth } from '@/composables/useAuth'

const router = useRouter()
const { logout } = useAuth()

const checkingAccess = ref(true)

const stats = ref({
  pending_approvals: 0,
  total_vendors: 0,
  total_consumers: 0,
  total_users: 0,
})

const userName = computed(() => {
  try {
    const user = JSON.parse(localStorage.getItem('auth_user') || '{}')
    return user.full_name || 'Admin'
  } catch {
    return 'Admin'
  }
})

const fetchStats = async () => {
  try {
    const res = await api.get('/admin/stats')
    stats.value = res.data
  } catch {
    // Stats will show 0
  }
}

const verifyAccess = async () => {
  try {
    const { data } = await api.get('/user')

    if (data.role !== 'Admin') {
      router.push('/login')
      return
    }

    checkingAccess.value = false
    fetchStats()

  } catch (error) {
    if (error.response?.status === 401) {
      logout()
    } else {
      router.push('/login')
    }
  }
}

onMounted(verifyAccess)
</script>

<style scoped>
.admin-page {
  min-height: 100vh;

  background: #ffffff;

  font-family: 'Roboto', Arial, sans-serif;
}

.checking-access {
  min-height: 100vh;

  display: flex;
  align-items: center;
  justify-content: center;
}

.page-container {
  max-width: 1000px;

  margin: 0 auto;

  padding: 40px 28px 60px;
}

/* TOP BAR */

.top-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;

  padding-bottom: 24px;
  margin-bottom: 36px;

  border-bottom: 1px solid #ececec;
}

.top-bar-left {
  display: flex;
  align-items: center;

  gap: 14px;
}

.top-bar-logo {
  width: 38px;
  height: auto;
}

.eyebrow {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;

  color: #bd2427;

  margin-bottom: 2px;
}

.top-bar h1 {
  margin: 0;

  font-size: 22px;
  font-weight: 700;

  color: #111111;
}

.logout-btn {
  height: 38px;

  border-radius: 7px;
  border-color: #e0e0e0;

  color: #555555;

  font-size: 13px;
}

/* STATS ROW */

.stats-row {
  display: grid;
  grid-template-columns: repeat(4, 1fr);

  gap: 0;

  margin-bottom: 44px;
}

.stat-item {
  display: flex;
  align-items: center;

  gap: 14px;

  padding: 6px 18px;

  border-right: 1px solid #ececec;

  cursor: pointer;
}

.stat-item:last-child {
  border-right: none;
}

.stat-item:first-child {
  padding-left: 0;
}

.stat-icon-circle {
  display: flex;
  align-items: center;
  justify-content: center;

  width: 40px;
  height: 40px;

  border-radius: 50%;

  background: #fdeeee;
  color: #bd2427;

  flex-shrink: 0;
}

.stat-value {
  font-size: 24px;
  font-weight: 700;

  color: #111111;

  line-height: 1.15;
}

.stat-label {
  font-size: 12px;

  color: #8992a2;
}

/* QUICK ACTIONS */

.section-title {
  margin-bottom: 16px;

  font-size: 13px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;

  color: #8992a2;
}

.actions-list {
  display: flex;
  flex-direction: column;
}

.action-row {
  display: flex;
  align-items: center;
  justify-content: space-between;

  padding: 18px 4px;

  border-bottom: 1px solid #ececec;

  cursor: pointer;

  transition: background 0.15s;
}

.action-row:hover {
  background: #fafafa;
}

.action-row:first-child {
  border-top: 1px solid #ececec;
}

.action-row-left {
  display: flex;
  align-items: center;

  gap: 16px;
}

.action-icon {
  font-size: 22px;

  color: #bd2427;
}

.action-label {
  font-size: 14px;
  font-weight: 600;

  color: #222222;
}

.action-desc {
  margin-top: 2px;

  font-size: 12px;

  color: #9a9aa2;
}

.action-arrow {
  font-size: 20px;

  color: #cccccc;
}

/* RESPONSIVE */

@media (max-width: 768px) {
  .stats-row {
    grid-template-columns: 1fr 1fr;

    row-gap: 24px;
  }

  .stat-item {
    border-right: none;
    padding-left: 0;
  }

  .top-bar {
    flex-direction: column;
    align-items: flex-start;

    gap: 16px;
  }
}

@media (max-width: 480px) {
  .stats-row {
    grid-template-columns: 1fr;
  }

  .page-container {
    padding: 24px 20px 40px;
  }
}
</style>
