<template>
  <q-dialog :model-value="modelValue" @update:model-value="$emit('update:modelValue', $event)">
    <q-card class="legal-dialog">

      <q-card-section class="legal-header">
        <div class="legal-title">Terms and Conditions</div>
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

      <q-card-section class="legal-body scroll">
        <p><strong>Last Updated: July 2026</strong></p>
        <p>
          By accessing or using the Tindahan platform, you agree to be bound by these Terms and Conditions. 
          If you do not agree with any part of these terms, you must not use our services.
        </p>

        <p><strong>1. Account Registration and Verification</strong></p>
        <ol>
          <li>You must provide accurate, current, and complete information during registration.</li>
          <li>You are required to verify your account using a valid 11-digit Philippine mobile number via One-Time Password (OTP).</li>
          <li>You are responsible for safeguarding your password and for all activities that occur under your account.</li>
        </ol>

        <p><strong>2. Vendor Obligations</strong></p>
        <ul>
          <li><strong>Approvals:</strong> All vendor applications are subject to review. Administrators reserve the right to approve, reject, or request revisions on store applications.</li>
          <li><strong>Accuracy:</strong> Vendors must provide accurate store photos, locations, and operating hours.</li>
          <li><strong>Compliance:</strong> Vendors must not list illegal, prohibited, or fraudulent items on the platform.</li>
        </ul>

        <p><strong>3. Platform Rights & Account Deactivation</strong></p>
        <p>
          Tindahan administrators reserve the right to suspend, reject, or deactivate user and vendor accounts that violate these terms. 
          Deactivated accounts are safely archived (soft-deleted) for auditing and security purposes and lose all access to public platform features.
        </p>

        <p><strong>4. Limitation of Liability</strong></p>
        <p>
          Tindahan serves as a marketplace platform connecting consumers and local sari-sari store vendors. 
          We are not responsible for the quality, safety, or legality of the goods offered by vendors, nor the completion of transactions between users.
        </p>

        <p><strong>5. Modifications to Terms</strong></p>
        <p>
          We reserve the right to modify these terms at any time. Continued use of the application after changes implies your acceptance of the updated terms.
        </p>
      </q-card-section>

      <q-separator />

      <q-card-actions align="right" class="legal-actions">
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
.legal-dialog {
  width: 520px;
  max-width: 90vw;
  border-radius: 12px;
  font-family: 'Roboto', Arial, sans-serif;
}

.legal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 20px;
}

.legal-title {
  font-size: 18px;
  font-weight: 700;
  color: #222222;
}

.close-btn {
  color: #666666;
}

.legal-body {
  max-height: 350px;
  padding: 20px 24px;
  font-size: 13px;
  line-height: 1.7;
  color: #555555;
}

.legal-body p {
  margin: 0 0 12px;
}

.legal-body ul, .legal-body ol {
  margin-top: 0;
  padding-left: 20px;
  margin-bottom: 12px;
}

.legal-body li {
  margin-bottom: 4px;
}

.legal-body p:last-child {
  margin-bottom: 0;
}

.legal-actions {
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
