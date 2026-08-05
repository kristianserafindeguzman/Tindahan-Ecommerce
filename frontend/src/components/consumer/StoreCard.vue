<template>
  <q-card flat class="store-card">
    <div class="store-card-image">
      <q-icon name="storefront" size="32px" />
    </div>
    <q-card-section class="store-card-body">
      <div class="store-card-name">{{ store.name }}</div>
      <div class="store-card-status" :class="{ 'store-card-status-closed': !store.isOpen }">
        <span class="status-dot" :class="{ 'status-dot-closed': !store.isOpen }" />
        {{ store.isOpen ? `Open until ${store.closesAt}` : 'Closed' }}
      </div>
      <div class="store-card-distance">
        <q-icon name="location_on" size="13px" />
        {{ store.distance }} away
      </div>
    </q-card-section>
  </q-card>
</template>

<script setup>
defineProps({
  store: {
    type: Object,
    required: true
  }
})
</script>

<style scoped>
.store-card {
  overflow: hidden;

  border-radius: 8px;
  border: 1px solid #f0f0f0;

  background: #ffffff;

  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);

  transition: box-shadow 0.2s, transform 0.2s, border-color 0.2s;

  cursor: pointer;
}

.store-card:hover {
  border-color: #f3c6c7;

  box-shadow: 0 10px 24px rgba(189, 36, 39, 0.14);
  transform: translateY(-3px);
}

.store-card-image {
  display: flex;
  align-items: center;
  justify-content: center;

  height: 110px;
  overflow: hidden;

  background: linear-gradient(145deg, #f7f7f8 0%, #ececee 100%);
  color: #bd2427;

  transition: background 0.2s;
}

.store-card:hover .store-card-image {
  background: linear-gradient(145deg, #fdecec 0%, #fbdbdc 100%);
}

.store-card-body {
  padding: 12px;
}

.store-card-name {
  font-size: 15px;
  font-weight: 700;
  line-height: 1.3;

  color: #222222;

  margin-bottom: 8px;

  overflow: hidden;

  white-space: nowrap;
  text-overflow: ellipsis;
}

.store-card-status {
  display: flex;
  align-items: center;

  gap: 4px;
  margin-bottom: 4px;

  font-size: 12.5px;
  font-weight: 500;
  line-height: 1.3;

  color: #2e9e5b;
}

.store-card-status-closed {
  color: #c02226;
}

.status-dot {
  width: 7px;
  height: 7px;
  flex-shrink: 0;

  border-radius: 50%;

  background: #2e9e5b;
}

.status-dot-closed {
  background: #c02226;
}

.store-card-distance {
  display: flex;
  align-items: center;

  gap: 4px;

  font-size: 12px;
  line-height: 1.3;

  color: #8992a2;
}
</style>
