<template>
  <q-dialog :model-value="modelValue" @update:model-value="$emit('update:modelValue', $event)">
    <q-card class="support-dialog">

      <q-card-section class="support-header">
        <div class="support-title">Contact Support</div>
        <q-btn
          icon="o_close"
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
          <q-icon name="o_location_on" class="support-icon" />
          <span>3rd Floor, City Hall of Mandaluyong, Maysilo Circle, Plainview, Mandaluyong City</span>
        </div>

        <div class="support-row">
          <q-icon name="o_phone" class="support-icon" />
          <span>(02) 8532-4846</span>
        </div>

        <div class="support-row">
          <q-icon name="o_email" class="support-icon" />
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

  border-radius: 12px;

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
  color: #666666;
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
  height: 48px;

  padding: 0 24px;

  border-radius: 6px;

  background: #bd2427;
  color: #ffffff;

  font-size: 13px;
  font-weight: 500;

  box-shadow: 0 2px 8px rgba(189, 36, 39, 0.25);

  transition: background-color 0.15s, box-shadow 0.2s, transform 0.2s;
}

.close-button:hover {
  background: #a91e21;

  box-shadow: 0 6px 16px rgba(189, 36, 39, 0.32);

  transform: translateY(-1px);
}

.close-button:active {
  background: #8f1a1c;

  box-shadow: 0 2px 6px rgba(189, 36, 39, 0.28);

  transform: translateY(0);
}

.close-button:focus-visible {
  outline: none;
  box-shadow: 0 0 0 3px rgba(189, 36, 39, 0.3);
}
</style>
