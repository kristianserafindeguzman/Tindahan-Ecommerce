<template>
  <AuthModalLayout @close="goLogin">
    <div class="wrapper">
      <div class="icon">
        <q-icon name="check" color="white" size="42px"/>
      </div>

      <h2>Verification Successful</h2>

      <p>
        Your account has been verified. You can now log in to start exploring
        local sari-sari stores.
      </p>

      <q-btn
        unelevated
        color="negative"
        label="Back to Login"
        class="full-width q-mt-lg"
        @click="goLogin"
      />
    </div>
  </AuthModalLayout>
</template>

<script setup>
import { useRouter } from 'vue-router'
import AuthModalLayout from '@/components/auth/AuthModalLayout.vue'
const router=useRouter()
function goLogin(){
 router.push('/login')
}
</script>

<style scoped>
.wrapper{text-align:center;padding-top:40px}
.icon{
width:80px;
height:80px;
margin:0 auto 24px;
border-radius:50%;
background:#b62326;
display:flex;
align-items:center;
justify-content:center;
}
h2{font-size:28px;margin-bottom:12px}
p{color:#666;line-height:1.6}
</style>
