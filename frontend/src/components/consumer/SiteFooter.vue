<template>
  <footer class="site-footer">
    <div class="footer-inner">
      <img src="@/assets/tindahan-mobile.png" alt="Tindahan Logo" class="footer-logo" />

      <nav class="footer-links">
        <a href="#" class="footer-link" @click.prevent="showContactSupport = true">Contact Support</a>
        <span class="footer-divider" aria-hidden="true" />
        <a href="#" class="footer-link" @click.prevent="showPrivacy = true">Privacy Policy</a>
        <span class="footer-divider" aria-hidden="true" />
        <a href="#" class="footer-link" @click.prevent="showTerms = true">Terms &amp; Conditions</a>
      </nav>
    </div>

    <div class="footer-bottom">
      <p>&copy; {{ currentYear }} Tindahan App. All rights reserved.</p>
    </div>

    <TermsModal v-model="showTerms" />
    <PrivacyModal v-model="showPrivacy" />
    <ContactSupportModal v-model="showContactSupport" />
  </footer>
</template>

<script setup>
import { ref, computed } from 'vue'
import TermsModal from '@/components/modals/TermsModal.vue'
import PrivacyModal from '@/components/modals/PrivacyModal.vue'
import ContactSupportModal from '@/components/modals/ContactSupportModal.vue'

const showTerms = ref(false)
const showPrivacy = ref(false)
const showContactSupport = ref(false)

const currentYear = computed(() => new Date().getFullYear())
</script>

<style scoped>
.site-footer {
  margin-top: auto;

  border-top: 1px solid #ececec;
  background: #fafafa;

  font-family: 'Roboto', Arial, sans-serif;
}

.footer-inner {
  display: flex;
  align-items: center;
  justify-content: space-between;

  gap: 24px;
  max-width: 1200px;

  margin: 0 auto;
  padding: 20px 24px;
}

.footer-logo {
  display: block;

  height: 76px;
  width: auto;

  object-fit: contain;
}

.footer-links {
  display: flex;
  align-items: center;

  gap: 16px;
}

.footer-divider {
  width: 1px;
  height: 14px;

  background: #dcdcdc;
}

.footer-link {
  font-size: 13.5px;
  font-weight: 400;

  color: #1a1a1a;
  text-decoration: none;

  transition: color 0.15s;
}

.footer-link:hover {
  color: #bd2427;
}

.footer-bottom {
  border-top: 1px solid #ececec;

  padding: 14px 24px;
}

.footer-bottom p {
  max-width: 1200px;

  margin: 0 auto;

  font-size: 12px;
  text-align: center;

  color: #767676;
}

@media (max-width: 600px) {
  .footer-inner {
    flex-direction: column;
    align-items: center;

    gap: 20px;
    padding: 24px 16px;
    text-align: center;
  }

  .footer-logo {
    height: 58px;
  }

  .footer-links {
    justify-content: space-between;

    width: 100%;
    gap: 14px;
  }

  .footer-link {
    font-size: 12.5px;
    line-height: 1.4;
  }

  .footer-bottom {
    padding: 16px;
  }
}
</style>
