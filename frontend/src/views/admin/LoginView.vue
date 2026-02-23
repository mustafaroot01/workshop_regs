<template>
  <div class="page login-page">
    <div class="orb orb-1" /><div class="orb orb-2" />

    <Transition name="slide-up" appear>
      <div class="login-card card">
        <div class="login-logo">
          <img :src="'/logo.png'" alt="شعار المركز" style="width: 70px; height: 70px; object-fit: contain; border-radius: 50%; box-shadow: 0 4px 15px rgba(0,0,0,0.2);" onerror="this.style.display='none'">
        </div>
        <h2 class="gradient-text" style="font-size:1.8rem; margin-bottom:0.2rem">مركز التعليم المستمر</h2>
        <h3 style="color:#b0d4f5; font-size:1.1rem; margin-bottom:0.5rem">هندسة تقنيات الحاسوب</h3>
        <p class="hint">لوحة تحكم المشرف</p>

        <form @submit.prevent="login" style="margin-top:1.5rem">
          <div class="form-group" style="margin-bottom:1rem">
            <label class="form-label">البريد الإلكتروني</label>
            <input v-model="email" type="email" class="form-input" placeholder="admin@workshop.com" autofocus />
          </div>
          <div class="form-group" style="margin-bottom:1.5rem">
            <label class="form-label">كلمة المرور</label>
            <input v-model="password" type="password" class="form-input" placeholder="••••••••" />
          </div>
          <div v-if="error" class="alert alert-error" style="margin-bottom:1rem">{{ error }}</div>
          <button type="submit" class="btn btn-primary btn-full" :disabled="loading" style="padding:0.85rem">
            <span v-if="loading" class="spinner" />
            <span v-else>دخول →</span>
          </button>
        </form>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { adminLogin } from '../../stores/api.js'

const router = useRouter()
const email = ref('admin@workshop.com')
const password = ref('')
const error = ref('')
const loading = ref(false)

async function login() {
  loading.value = true
  error.value = ''
  try {
    const { data } = await adminLogin({ email: email.value, password: password.value })
    localStorage.setItem('admin_token', data.token)
    localStorage.setItem('admin_name', data.user.name)
    localStorage.setItem('admin_role', data.user.role)
    router.push({ name: 'AdminDashboard' })
  } catch (err) {
    error.value = err.response?.data?.message || 'فشل تسجيل الدخول.'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.login-page {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  position: relative;
  overflow: hidden;
}
.orb { position: fixed; border-radius: 50%; filter: blur(80px); pointer-events: none; z-index: 0; }
.orb-1 { width: 500px; height: 500px; background: radial-gradient(circle, rgba(124,58,237,0.2) 0%, transparent 70%); top: -150px; left: -150px; }
.orb-2 { width: 400px; height: 400px; background: radial-gradient(circle, rgba(6,182,212,0.15) 0%, transparent 70%); bottom: -100px; right: -100px; }
.login-card { position: relative; z-index: 1; max-width: 400px; width: 100%; padding: 2.5rem; text-align: center; }
.login-logo { margin-bottom: 1rem; }
.hint { color: var(--text-3); font-size:0.9rem; margin-top:0.3rem; }
</style>
