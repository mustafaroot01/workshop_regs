<template>
  <div class="admin-layout">
    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="sidebar-logo">
        <img :src="adminDeptLogo || '/logo.png'" alt="شعار المركز" style="width: 40px; height: 40px; object-fit: contain; border-radius: 50%; background: white;" onerror="this.src='/logo.png'">
        <div style="display:flex; flex-direction:column; line-height: 1.2;">
          <span class="logo-text" style="font-size:0.9rem;">{{ adminDeptName || 'مركز التعليم المستمر' }}</span>
          <span style="color:#b0d4f5; font-size:0.75rem; font-weight:600;">{{ adminDeptName ? 'نظام التسجيل' : 'جامعة بلاد الرافدين' }}</span>
        </div>
      </div>

      <nav class="sidebar-nav" style="margin-top: 2rem;">
        <router-link :to="{ name: 'AdminDashboard' }" class="nav-item" active-class="active" exact-active-class="active">
          <span class="nav-icon">📊</span> لوحة القيادة
        </router-link>
        <router-link :to="{ name: 'AdminForms' }" class="nav-item" active-class="active">
          <span class="nav-icon">📋</span> الاستمارات
        </router-link>
        <router-link v-if="adminRole === 'admin' && !adminDeptId" :to="{ name: 'AdminSettings' }" class="nav-item" active-class="active">
          <span class="nav-icon">⚙️</span> الإعدادات
        </router-link>
        
        <button class="nav-item" @click="toggleDarkMode" style="background:transparent;border:none;width:100%;text-align:right;cursor:pointer;margin-top:auto">
          <span class="nav-icon">{{ isDarkMode ? '☀️' : '🌙' }}</span> 
          <span>{{ isDarkMode ? 'الوضع الفاتح' : 'الوضع الليلي' }}</span>
        </button>
      </nav>

      <div class="sidebar-footer">
        <div class="admin-name">{{ adminName }}</div>
        <button class="btn btn-ghost logout-btn" @click="logout" style="padding:0.4rem 0.8rem;font-size:0.85rem;color:#b0d4f5;border-color:rgba(255,255,255,0.1)">
          خروج
        </button>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="admin-main">
      <router-view />
    </main>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { adminLogout } from '../../stores/api.js'

const router = useRouter()
const adminName = ref(localStorage.getItem('admin_name') || 'المشرف')
const adminRole = ref(localStorage.getItem('admin_role') || 'admin')
const adminUserStr = localStorage.getItem('admin_user')
const adminUserLocal = adminUserStr ? JSON.parse(adminUserStr) : null
const adminDeptId = ref(adminUserLocal ? adminUserLocal.department_id : null)
const adminDeptName = ref(adminUserLocal ? adminUserLocal.department_name : null)
const adminDeptLogo = ref(adminUserLocal ? adminUserLocal.department_logo : null)

const isDarkMode = ref(false)

onMounted(() => {
  if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
    isDarkMode.value = true
    document.documentElement.classList.add('dark')
  } else {
    isDarkMode.value = false
    document.documentElement.classList.remove('dark')
  }
})

function toggleDarkMode() {
  isDarkMode.value = !isDarkMode.value
  if (isDarkMode.value) {
    document.documentElement.classList.add('dark')
    localStorage.setItem('theme', 'dark')
  } else {
    document.documentElement.classList.remove('dark')
    localStorage.setItem('theme', 'light')
  }
}

async function logout() {
  try { await adminLogout() } catch {}
  localStorage.removeItem('admin_token')
  localStorage.removeItem('admin_name')
  localStorage.removeItem('admin_role')
  router.push({ name: 'AdminLogin' })
}
</script>

<style scoped>
.admin-layout {
  display: flex;
  min-height: 100vh;
}

.sidebar {
  width: 240px;
  min-height: 100vh;
  background: #051630; /* IT Diyala Deep Blue */
  display: flex;
  flex-direction: column;
  padding: 1.5rem 1rem;
  position: sticky;
  top: 0;
  height: 100vh;
}

.sidebar-logo {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 2rem;
}
.logo-text {
  font-weight: 700;
  font-size: 1.1rem;
  color: #ffffff;
}

.sidebar-nav { flex: 1; display: flex; flex-direction: column; gap: 4px; }
.nav-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 0.75rem 1rem;
  border-radius: var(--radius-sm);
  color: #b0d4f5; /* Light blue/gray */
  text-decoration: none;
  font-size: 0.95rem;
  font-weight: 500;
  transition: all 0.2s;
}
.nav-item:hover { background: rgba(255,255,255,0.05); color: #ffffff; }
.nav-item.active { 
  background: rgba(0, 124, 237, 0.2); 
  color: #ffffff; 
  border-right: 3px solid #b2d900; 
  border-radius: 0 var(--radius-sm) var(--radius-sm) 0;
}
.nav-icon { font-size: 1.1rem; }

.sidebar-footer { margin-top: auto; text-align: center; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 1rem; }
.admin-name { font-weight: 600; color: #ffffff; font-size: 0.9rem; margin-bottom: 0.5rem; }

.admin-main {
  flex: 1;
  padding: 2rem;
  overflow-y: auto;
}

@media (max-width: 768px) {
  .admin-layout { flex-direction: column; }
  .sidebar { 
    width: 100%; height: auto; min-height: auto; position: fixed; bottom: 0; top: auto;
    flex-direction: row; align-items: center; padding: 0.5rem; z-index: 1000;
    box-shadow: 0 -2px 10px rgba(0,0,0,0.2); 
  }
  .sidebar-logo { display: none; }
  .sidebar-nav { flex-direction: row; justify-content: space-around; margin-top: 0 !important; gap: 0; }
  .nav-item { flex-direction: column; gap: 4px; padding: 0.5rem; justify-content: center; font-size: 0.75rem; text-align: center; }
  .nav-item.active { border-right: none; border-bottom: 3px solid #b2d900; border-radius: 0; color: #b2d900; background: transparent; }
  .nav-item span:last-child { display: block; }
  .sidebar-footer { border-top: none; padding-top: 0; margin-top: 0; border-right: 1px solid rgba(255,255,255,0.1); padding-right: 0.5rem; margin-right: 0.5rem; }
  .admin-name { display: none; }
  .sidebar-footer .logout-btn { padding: 0.4rem; font-size: 0.75rem; display: flex; flex-direction: column; gap: 4px; border: none; align-items: center; }
  .sidebar-footer .logout-btn::before { content: '🚪'; font-size: 1.2rem; }
  .admin-main { padding: 1rem 1rem 5rem 1rem; }
}
</style>
