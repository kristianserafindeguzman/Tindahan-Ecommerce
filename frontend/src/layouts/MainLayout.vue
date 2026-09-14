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
  document.documentElement.lang = pathname.startsWith('/consumer/') ? language : originalLanguage
}, { immediate: true })
onBeforeUnmount(() => { document.documentElement.lang = originalLanguage })
</script>