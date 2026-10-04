<template>
  <q-dialog :model-value="modelValue" @update:model-value="$emit('update:modelValue', $event)">
    <q-card class="legal-dialog">

      <q-card-section class="legal-header">
        <div class="legal-title">{{ doc.title }}</div>
        <q-btn
          icon="o_close"
          flat
          round
          dense
          class="close-btn"
          :aria-label="t('Close')"
          @click="$emit('update:modelValue', false)"
        />
      </q-card-section>

      <q-separator />

      <q-card-section class="legal-body scroll">
        <p><strong>{{ doc.updated }}</strong></p>

        <template v-for="(block, index) in doc.blocks" :key="index">
          <p v-if="block.heading"><strong>{{ block.heading }}</strong></p>
          <p v-else-if="block.p">{{ block.p }}</p>
          <component :is="block.ol ? 'ol' : 'ul'" v-else>
            <li v-for="(item, itemIndex) in block.ol || block.ul" :key="itemIndex">
              <template v-if="typeof item === 'string'">{{ item }}</template>
              <template v-else><strong>{{ item.label }}</strong> {{ item.text }}</template>
            </li>
          </component>
        </template>
      </q-card-section>

      <q-separator />

      <q-card-actions align="right" class="legal-actions">
        <q-btn
          :label="t('Close')"
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
import { computed } from 'vue'
import { useConsumerLanguage } from '@/composables/useConsumerLanguage'

// Shared shell for TermsModal and PrivacyModal; `content` is one of the documents in i18n/legalContent.js.
const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  content: {
    type: Object,
    required: true
  }
})

defineEmits(['update:modelValue'])

const { t, lang } = useConsumerLanguage()

const doc = computed(() => props.content[lang.value] || props.content.en)
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
