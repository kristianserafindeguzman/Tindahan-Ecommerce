<template>
  <q-layout view="hHh lpR fFf">
    <q-page-container>
      <router-view />
    </q-page-container>
  </q-layout>
</template>

<script setup>
import { watch, onBeforeUnmount } from 'vue'
import { useRoute } from 'vue-router'
import { useConsumerLanguage } from '@/composables/useConsumerLanguage'

const route = useRoute()
const { lang } = useConsumerLanguage()
const originalLanguage = document.documentElement.lang
watch([() => route.path, lang], ([pathname, language]) => {
  const usesConsumerLanguage = pathname.startsWith('/consumer/') || [
    '/login',
    '/verification',
    '/vendor/register',
    '/auth/vendor/under-review',
    '/auth/vendor/rejected'
  ].includes(pathname)
  document.documentElement.lang = usesConsumerLanguage ? language : originalLanguage
}, { immediate: true })
onBeforeUnmount(() => { document.documentElement.lang = originalLanguage })
</script>
