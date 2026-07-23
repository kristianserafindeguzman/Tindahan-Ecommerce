<template>
  <q-page class="flex flex-center">
    <h4>{{ message }}</h4>

     <div class="col-4">
      Ratio: 1
      <q-img src="https://picsum.photos/500/300" :ratio="1" />
    </div>
    
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { api } from '../boot/axios'

const message = ref('Loading...')

onMounted(async () => {
  try {
    const { data } = await api.get('/test')
    message.value = data.message
  } catch (error) {
    console.error(error)
    message.value = 'Failed to connect to Laravel'
  }
})
</script>