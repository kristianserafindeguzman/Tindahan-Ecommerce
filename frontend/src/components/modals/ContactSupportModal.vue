<template>
  <q-dialog :model-value="modelValue" @update:model-value="$emit('update:modelValue', $event)">
    <q-card class="support-dialog">

      <q-card-section class="support-header">
        <div class="support-title">Contact Support</div>
        <q-btn
          icon="close"
          flat
          round
          dense
          class="close-btn"
          @click="$emit('update:modelValue', false)"
        />
      </q-card-section>

      <q-separator />

      <q-card-section class="support-body">
        <div class="support-org">DTI Negosyo Center - Mandaluyong</div>

        <div class="support-row">
          <q-icon name="location_on" class="support-icon" />
          <span>3rd Floor, City Hall of Mandaluyong, Maysilo Circle, Plainview, Mandaluyong City</span>
        </div>

        <div class="support-row">
          <q-icon name="phone" class="support-icon" />
          <span>(02) 8532-4846</span>
        </div>

        <div class="support-row">
          <q-icon name="email" class="support-icon" />
          <span>mandaluyong@dti.gov.ph</span>
        </div>
      </q-card-section>

      <q-separator />

      <q-card-actions align="right" class="support-actions">
        <q-btn
          label="Close"
          no-caps
          unelevated
          class="close-button"
          @click="$emit('update:modelValue', false)"
        />
      </q-card-actions>

    </q-card>
  </q-dialog>
</template>

<script setup>
defineProps({
  modelValue: {
    type: Boolean,
    default: false
  }
})

defineEmits(['update:modelValue'])
</script>

<style scoped>
.support-dialog {
  width: 440px;
  max-width: 90vw;

  border-radius: 10px;

  font-family: 'Roboto', Arial, sans-serif;
}

.support-header {
  display: flex;
  align-items: center;
  justify-content: space-between;

  padding: 16px 20px;
}

.support-title {
  font-size: 18px;
  font-weight: 700;

  color: #222222;
}

.close-btn {
  color: #888888;
}

.support-body {
  padding: 20px 24px;
}

.support-org {
  margin-bottom: 18px;

  font-size: 15px;
  font-weight: 600;

  color: #bd2427;
}

.support-row {
  display: flex;
  align-items: flex-start;

  gap: 10px;

  margin-bottom: 14px;

  font-size: 13px;
  line-height: 1.5;

  color: #444444;
}

.support-row:last-child {
  margin-bottom: 0;
}

.support-icon {
  font-size: 18px;

  color: #888888;

  margin-top: 1px;

  flex-shrink: 0;
}

.support-actions {
  padding: 12px 20px;
}

.close-button {
  height: 38px;

  padding: 0 24px;

  border-radius: 6px;

  background: #bd2427;
  color: #ffffff;

  font-size: 13px;
  font-weight: 500;
}

.close-button:hover {
  background: #a91e21;
}
</style>
