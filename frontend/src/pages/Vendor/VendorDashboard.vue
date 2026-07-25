<template>
  <q-page class="dashboard-page">
    <div class="dashboard-container">

      <!-- HEADER -->
      <div class="dashboard-header">
        <div class="header-left">
          <img
            src="@/assets/tindahan-mobile.png"
            alt="Tindahan Logo"
            class="header-logo"
          />
          <div>
            <h1>Vendor Dashboard</h1>
            <p class="header-subtitle">Welcome, {{ userName }}</p>
          </div>
        </div>

        <q-btn
          label="Logout"
          no-caps
          flat
          icon="logout"
          class="logout-btn"
          @click="handleLogout"
        />
      </div>

      <!-- DASHBOARD CARDS -->
      <div class="cards-grid">

        <div class="dash-card">
          <q-icon name="inventory_2" class="card-icon" />
          <div class="card-title">Inventory</div>
          <div class="card-desc">
            Manage your product listings, update stock levels, and add new items.
          </div>
        </div>

        <div class="dash-card">
          <q-icon name="shopping_bag" class="card-icon" />
          <div class="card-title">Orders</div>
          <div class="card-desc">
            View and process incoming orders from your customers.
          </div>
        </div>

        <div class="dash-card">
          <q-icon name="insights" class="card-icon" />
          <div class="card-title">Analytics</div>
          <div class="card-desc">
            Track your sales performance, best-selling products, and trends.
          </div>
        </div>

        <div class="dash-card">
          <q-icon name="storefront" class="card-icon" />
          <div class="card-title">Store Profile</div>
          <div class="card-desc">
            Update your store information, hours, and exterior photo.
          </div>
        </div>

      </div>

    </div>
  </q-page>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '@/boot/axios'

const router = useRouter()

const userName = computed(() => {
  try {
    const user = JSON.parse(localStorage.getItem('auth_user') || '{}')
    return user.full_name || 'Vendor'
  } catch {
    return 'Vendor'
  }
})

const handleLogout = async () => {
  try {
    await api.post('/logout')
  } catch {
    // Token may already be invalid
  }

  localStorage.removeItem('auth_token')
  localStorage.removeItem('auth_user')
  localStorage.removeItem('auth_role')

  router.push('/login')
}
</script>

<style scoped>
.dashboard-page {
  min-height: 100vh;

  background: #f4f4f4;

  font-family: 'Roboto', Arial, sans-serif;
}

.dashboard-container {
  max-width: 960px;

  margin: 0 auto;

  padding: 40px 24px;
}

/* HEADER */

.dashboard-header {
  display: flex;
  align-items: center;
  justify-content: space-between;

  margin-bottom: 36px;
  padding: 20px 28px;

  background: #ffffff;
  border-radius: 10px;

  box-shadow:
    0 4px 16px rgba(0, 0, 0, 0.06);
}

.header-left {
  display: flex;
  align-items: center;

  gap: 16px;
}

.header-logo {
  width: 44px;
  height: auto;
}

.dashboard-header h1 {
  margin: 0;

  font-size: 20px;
  font-weight: 700;

  color: #111111;
}

.header-subtitle {
  margin: 2px 0 0;

  font-size: 13px;

  color: #8992a2;
}

.logout-btn {
  color: #bd2427;

  font-size: 13px;
}

/* CARDS */

.cards-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;

  gap: 20px;
}

.dash-card {
  padding: 28px 24px;

  background: #ffffff;
  border-radius: 10px;

  box-shadow:
    0 4px 16px rgba(0, 0, 0, 0.06);

  transition: box-shadow 0.2s;
}

.dash-card:hover {
  box-shadow:
    0 8px 24px rgba(0, 0, 0, 0.1);
}

.card-icon {
  font-size: 30px;

  color: #bd2427;

  margin-bottom: 12px;
}

.card-title {
  font-size: 16px;
  font-weight: 700;

  color: #222222;

  margin-bottom: 6px;
}

.card-desc {
  font-size: 13px;
  line-height: 1.5;

  color: #888888;
}

/* MOBILE */

@media (max-width: 600px) {
  .dashboard-container {
    padding: 20px 16px;
  }

  .dashboard-header {
    flex-direction: column;
    align-items: flex-start;

    gap: 12px;

    padding: 16px 20px;
  }

  .cards-grid {
    grid-template-columns: 1fr;
  }
}
</style>
