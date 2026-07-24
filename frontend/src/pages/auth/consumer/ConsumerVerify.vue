<template>
  <AuthModalLayout @close="goBack">
    <h2 class="title">Verify your account</h2>
    <p class="subtitle">
      We've sent a 6-digit verification code to
      <strong>{{ email }}</strong>.
    </p>

    <div class="otp-row">
      <q-input
        v-for="(digit,index) in otp"
        :key="index"
        v-model="otp[index]"
        outlined
        maxlength="1"
        class="otp-box"
        input-class="text-center"
      />
    </div>

    <q-btn
      unelevated
      color="negative"
      class="full-width q-mt-lg"
      label="Verify"
      @click="verifyOtp"
    />

    <div class="q-mt-md text-center">
      Didn't receive a code?
      <a href="#" @click.prevent="resend">Resend Code</a>
    </div>

    <div class="text-center text-grey q-mt-sm">
      <q-icon name="schedule" /> {{ timer }} sec
    </div>

    <p class="terms">
      By continuing, you agree to our Terms and Conditions and Privacy Policy.
    </p>
  </AuthModalLayout>
</template>

<script setup>
import { ref,onMounted,onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import AuthModalLayout from '@/components/auth/AuthModalLayout.vue'

const router=useRouter()
const email='example@email.com'
const otp=ref(['','','','','',''])
const timer=ref(60)
let interval

onMounted(()=>{
 interval=setInterval(()=>{
   if(timer.value>0) timer.value--
 },1000)
})

onUnmounted(()=>clearInterval(interval))

function resend(){
 timer.value=60
}

function verifyOtp(){
 router.push('/consumer/success')
}

function goBack(){
 router.back()
}
</script>

<style scoped>
.title{font-size:30px;font-weight:700;margin-bottom:8px}
.subtitle{color:#777;margin-bottom:24px}
.otp-row{display:flex;gap:12px;justify-content:center}
.otp-box{width:52px}
.terms{margin-top:24px;font-size:12px;color:#888;text-align:center}
a{color:#b62326;text-decoration:none;font-weight:600}
</style>
