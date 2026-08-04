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
            <h1>Welcome, {{ userName }}!</h1>
            <p class="header-subtitle">Discover sari-sari stores near you</p>
          </div>
        </div>

        <div class="header-right flex items-center">
          <q-avatar size="40px" class="q-mr-md" v-if="userProfilePicture">
            <img :src="'http://localhost:8000/storage/' + userProfilePicture" />
          </q-avatar>
          <q-avatar size="40px" class="q-mr-md bg-grey-3 text-grey-8" v-else>
            <q-icon name="person" size="24px" />
          </q-avatar>

          <q-btn
            label="Logout"
            no-caps
            flat
            icon="logout"
            class="logout-btn"
            @click="handleLogout"
          />
        </div>
      </div>

      <!-- SEARCH BAR -->
      <div class="search-bar">
        <q-input
          outlined
          dense
          placeholder="Search for products or stores..."
          class="search-input"
        >
          <template #prepend>
            <q-icon name="search" color="grey-6" />
          </template>
        </q-input>
      </div>

      <!-- PLACEHOLDER CONTENT -->
      <div class="cards-grid">

        <div class="dash-card">
          <q-icon name="explore" class="card-icon" />
          <div class="card-title">Nearby Stores</div>
          <div class="card-desc">
            Browse sari-sari stores in your neighborhood.
          </div>
        </div>

        <div class="dash-card">
          <q-icon name="shopping_cart" class="card-icon" />
          <div class="card-title">My Cart</div>
          <div class="card-desc">
            View items in your cart and proceed to checkout.
          </div>
        </div>

        <div class="dash-card">
          <q-icon name="receipt_long" class="card-icon" />
          <div class="card-title">Order History</div>
          <div class="card-desc">
            Track your past and current orders.
          </div>
        </div>

        <div class="dash-card">
          <q-icon name="person" class="card-icon" />
          <div class="card-title">My Profile</div>
          <div class="card-desc">
            Update your account details and preferences.
          </div>
        </div>

      </div>

    </div>
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { api } from '@/boot/axios'

const router = useRouter()
const $q = useQuasar()

const userName = ref('')
const userProfilePicture = ref('')

onMounted(async () => {
  try {
    const res = await api.get('/user')
    if (res.data && res.data.user) {
      const user = res.data.user
      userName.value = user.full_name ? user.full_name.split(' ')[0] : 'Consumer'
      userProfilePicture.value = user.profile_picture || null
    }
  } catch (error) {
    userName.value = 'Consumer'
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

  margin-bottom: 24px;
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

/* SEARCH */

.search-bar {
  margin-bottom: 28px;
}

.search-input :deep(.q-field__control) {
  height: 44px;

  border-radius: 10px;

  background: #ffffff;
}

.search-input :deep(.q-field__native) {
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

  cursor: pointer;
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
