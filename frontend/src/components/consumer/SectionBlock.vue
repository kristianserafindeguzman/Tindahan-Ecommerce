<template>
  <section class="section-block">
    <div class="section-heading-row">
      <span class="section-heading">{{ title }}</span>
      <span v-if="viewAll" class="section-link">View All</span>
    </div>

    <slot />
  </section>
</template>

<script setup>
defineProps({
  title: {
    type: String,
    required: true
  },
  viewAll: {
    type: Boolean,
    default: false
  }
})
</script>

<style scoped>
.section-block {
  margin-bottom: 24px;
}

.section-heading-row {
  display: flex;
  align-items: center;
  justify-content: space-between;

  margin-bottom: 10px;
}

.section-heading {
  font-size: 16px;
  font-weight: 700;

  color: #111111;
}

.section-link {
  font-size: 13px;
  font-weight: 500;

  color: #bd2427;

  cursor: pointer;
}
</style>
