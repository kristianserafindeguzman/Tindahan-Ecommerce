<template>
  <q-dialog :model-value="modelValue" @update:model-value="$emit('update:modelValue', $event)">
    <q-card class="legal-dialog">

      <q-card-section class="legal-header">
        <div class="legal-title">Privacy Policy</div>
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

      <q-card-section class="legal-body scroll">
        <p><strong>Last Updated: July 2026</strong></p>
        <p>
          Welcome to Tindahan. We are committed to protecting your personal information and your right to privacy. 
          This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our mobile or web application.
        </p>
        
        <p><strong>1. Information We Collect</strong></p>
        <ul>
          <li><strong>Personal Data:</strong> We collect your full name, email address, and mobile phone number during registration.</li>
          <li><strong>Media & Files:</strong> We collect optional profile pictures for consumers, and mandatory store appearance photos for vendors.</li>
          <li><strong>Business Information (Vendors):</strong> Store name, location/map coordinates, and operating schedules.</li>
        </ul>

        <p><strong>2. How We Use Your Information</strong></p>
        <ul>
          <li>To create, verify (via OTP), and secure your account.</li>
          <li>To process vendor applications and display approved stores to consumers.</li>
          <li>To provide customer support and respond to your inquiries.</li>
        </ul>

        <p><strong>3. Data Retention and Deletion</strong></p>
        <p>
          We retain personal information only for as long as necessary to fulfill the purposes outlined in this policy. 
          When an account is deactivated or deleted by an Administrator, the account undergoes a "soft delete." 
          This means your active profile is hidden from the public platform but securely archived in our database to preserve transaction histories and ensure platform integrity.
        </p>

        <p><strong>4. Your Rights (Data Privacy Act of 2012)</strong></p>
        <p>
          In accordance with the Philippine Data Privacy Act of 2012 (RA 10173), you have the right to be informed, object to processing, access, rectify, or request the erasure of your personal data, subject to platform limitations and legal obligations.
        </p>

        <p><strong>5. Contact Us</strong></p>
        <p>
          If you have questions or comments about this Privacy Policy, please contact our support team.
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

.legal-body ul {
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
