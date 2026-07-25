<template>
  <q-page class="admin-page">
    <div class="page-container">

      <div class="page-header">
        <h1>Dashboard</h1>
        <p class="page-subtitle">Welcome back, {{ userName }}</p>
      </div>

      <!-- STAT CARDS -->
      <div class="stats-grid">

        <div class="stat-card" @click="$router.push('/admin/approvals')">
          <div class="stat-icon-wrap stat-pending">
            <q-icon name="pending_actions" size="24px" color="white" />
          </div>
          <div class="stat-value">{{ stats.pending_approvals }}</div>
          <div class="stat-label">Pending Approvals</div>
        </div>

        <div class="stat-card" @click="$router.push('/admin/vendors')">
          <div class="stat-icon-wrap stat-vendors">
            <q-icon name="store" size="24px" color="white" />
          </div>
          <div class="stat-value">{{ stats.total_vendors }}</div>
          <div class="stat-label">Total Vendors</div>
        </div>

        <div class="stat-card" @click="$router.push('/admin/consumers')">
          <div class="stat-icon-wrap stat-consumers">
            <q-icon name="people" size="24px" color="white" />
          </div>
          <div class="stat-value">{{ stats.total_consumers }}</div>
          <div class="stat-label">Total Consumers</div>
        </div>

        <div class="stat-card">
          <div class="stat-icon-wrap stat-total">
            <q-icon name="group" size="24px" color="white" />
          </div>
          <div class="stat-value">{{ stats.total_users }}</div>
          <div class="stat-label">Total Users</div>
        </div>

      </div>

      <!-- QUICK ACTIONS -->
      <div class="section-title">Quick Actions</div>
      <div class="actions-grid">
        <div class="action-card" @click="$router.push('/admin/approvals')">
          <q-icon name="fact_check" class="action-icon" />
          <div class="action-label">Review Applications</div>
        </div>
        <div class="action-card" @click="$router.push('/admin/vendors')">
          <q-icon name="manage_accounts" class="action-icon" />
          <div class="action-label">Manage Vendors</div>
        </div>
        <div class="action-card" @click="$router.push('/admin/consumers')">
          <q-icon name="supervisor_account" class="action-icon" />
          <div class="action-label">Manage Consumers</div>
        </div>
      </div>

    </div>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { api } from '@/boot/axios'

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

onMounted(async () => {
  try {
    const res = await api.get('/admin/stats')
    stats.value = res.data
  } catch {
    // Stats will show 0
  }
})
</script>

<style scoped>
.admin-page {
  background: #f4f5f7;

  font-family: 'Roboto', Arial, sans-serif;
}

.page-container {
  max-width: 960px;

  margin: 0 auto;

  padding: 32px 28px;
}

.page-header {
  margin-bottom: 28px;
}

.page-header h1 {
  margin: 0;

  font-size: 24px;
  font-weight: 700;

  color: #111111;
}

.page-subtitle {
  margin: 4px 0 0;

  font-size: 13px;

  color: #8992a2;
}

/* STATS */

.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);

  gap: 16px;

  margin-bottom: 36px;
}

.stat-card {
  padding: 22px 20px;

  background: #ffffff;
  border-radius: 10px;

  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);

  cursor: pointer;

  transition: box-shadow 0.2s;
}

.stat-card:hover {
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
}

.stat-icon-wrap {
  display: inline-flex;
  align-items: center;
  justify-content: center;

  width: 44px;
  height: 44px;

  border-radius: 10px;

  margin-bottom: 14px;
}

.stat-pending { background: #f59e0b; }
.stat-vendors { background: #3b82f6; }
.stat-consumers { background: #22c55e; }
.stat-total { background: #8b5cf6; }

.stat-value {
  font-size: 28px;
  font-weight: 700;

  color: #111111;

  line-height: 1;

  margin-bottom: 4px;
}

.stat-label {
  font-size: 12px;

  color: #8992a2;
}

/* ACTIONS */

.section-title {
  margin-bottom: 14px;

  font-size: 14px;
  font-weight: 700;

  color: #333333;
}

.actions-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);

  gap: 16px;
}

.action-card {
  display: flex;
  align-items: center;

  gap: 14px;

  padding: 18px 20px;

  background: #ffffff;
  border-radius: 10px;

  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);

  cursor: pointer;

  transition: box-shadow 0.2s;
}

.action-card:hover {
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
}

.action-icon {
  font-size: 24px;

  color: #bd2427;
}

.action-label {
  font-size: 13px;
  font-weight: 600;

  color: #222222;
}

/* RESPONSIVE */

@media (max-width: 768px) {
  .stats-grid {
    grid-template-columns: 1fr 1fr;
  }

  .actions-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 480px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }

  .page-container {
    padding: 20px 16px;
  }
}
</style>
