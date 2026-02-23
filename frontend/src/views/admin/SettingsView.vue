<template>
  <div>
    <div style="margin-bottom:1.5rem">
      <h2 class="gradient-text">الإعدادات</h2>
      <p style="color:var(--text-3);font-size:0.9rem;margin-top:0.3rem">إدارة الأقسام والاهتمامات</p>
    </div>

    <div class="settings-grid">
      <!-- Departments -->
      <div class="card settings-section">
        <div class="section-header">
          <h3>🏛️ الأقسام</h3>
          <span class="badge badge-primary">{{ departments.length }}</span>
        </div>
        <div class="add-row">
          <input v-model="newDept" type="text" class="form-input" placeholder="اسم القسم الجديد..." @keyup.enter="addDept" />
          <input type="file" ref="deptLogoInput" @change="onLogoChange" class="form-input" style="max-width: 200px; padding: 0.4rem" accept="image/*" />
          <button class="btn btn-primary" @click="addDept" :disabled="!newDept.trim() || adding.dept">
            <span v-if="adding.dept" class="spinner" /><span v-else>+</span>
          </button>
        </div>
        <div v-if="deptError" class="alert alert-error" style="margin-top:0.5rem;font-size:0.82rem">{{ deptError }}</div>
        <div class="items-list">
          <div v-if="deptLoading" style="text-align:center;padding:1.5rem"><div class="spinner" /></div>
          <div v-else-if="departments.length === 0" class="empty-msg">لا توجد أقسام بعد</div>
          <div v-for="d in departments" :key="d.id" class="item-row">
            <div style="display:flex; align-items:center; gap: 0.8rem">
              <img v-if="d.logo_url" :src="d.logo_url" style="width:28px;height:28px;border-radius:4px;object-fit:cover;background:white" />
              <div v-else style="width:28px;height:28px;border-radius:4px;background:rgba(255,255,255,0.1);display:flex;align-items:center;justify-content:center;font-size:0.75rem">🏢</div>
              <span class="item-name">{{ d.name }}</span>
            </div>
            <button class="btn btn-danger" style="padding:0.3rem 0.6rem;font-size:0.8rem" @click="prepDelete('dept', d.id, d.name)">حذف</button>
          </div>
        </div>
      </div>

      <!-- Interests -->
      <div class="card settings-section">
        <div class="section-header">
          <h3>💡 الاهتمامات</h3>
          <span class="badge badge-secondary">{{ interests.length }}</span>
        </div>
        <div class="add-row">
          <select v-model="newInterestDept" class="form-input" style="max-width:35%">
            <option value="">اختر القسم</option>
            <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.name }}</option>
          </select>
          <input v-model="newInterest" type="text" class="form-input" placeholder="اهتمام جديد..." @keyup.enter="addInterest" :disabled="!newInterestDept" />
          <button class="btn btn-primary" @click="addInterest" :disabled="!newInterest.trim() || !newInterestDept || adding.interest">
            <span v-if="adding.interest" class="spinner" /><span v-else>+</span>
          </button>
        </div>
        <div v-if="intError" class="alert alert-error" style="margin-top:0.5rem;font-size:0.82rem">{{ intError }}</div>
        <div class="items-list">
          <div v-if="!newInterestDept" class="empty-msg" style="color:var(--text-2); font-weight:500;">👆 يرجى اختيار قسم من الأعلى لعرض اهتماماته</div>
          <div v-else-if="intLoading" style="text-align:center;padding:1.5rem"><div class="spinner" /></div>
          <div v-else-if="filteredInterests.length === 0" class="empty-msg">لا توجد اهتمامات في هذا القسم بعد</div>
          <div v-for="i in filteredInterests" :key="i.id" class="item-row">
            <span class="item-name">{{ i.name }}</span>
            <button class="btn btn-danger" style="padding:0.3rem 0.6rem;font-size:0.8rem" @click="prepDelete('interest', i.id, i.name)">حذف</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Admin Users -->
    <div class="card settings-section" style="margin-top:1.5rem">
      <div class="section-header">
        <h3>👥 حسابات التطبيق (المشرفين)</h3>
        <span class="badge badge-success">{{ users.length }}</span>
      </div>
      <div class="add-row">
        <input v-model="newUser.name" type="text" class="form-input" placeholder="الاسم" />
        <input v-model="newUser.email" type="email" class="form-input" placeholder="البريد الإلكتروني" />
        <input v-model="newUser.password" type="password" class="form-input" placeholder="كلمة المرور" />
        <select v-model="newUser.role" class="form-input" style="max-width:150px" :disabled="adminDeptId">
          <option value="admin">مدير النظام (أو القسم)</option>
          <option value="scanner">ماسح QR فقط</option>
        </select>
        <select v-if="!adminDeptId" v-model="newUser.department_id" class="form-input" style="max-width:180px">
          <option value="">(عام - جميع الأقسام)</option>
          <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.name }}</option>
        </select>
        <button class="btn btn-primary" @click="addUser" :disabled="!newUser.name || !newUser.email || !newUser.password || adding.user">
          <span v-if="adding.user" class="spinner" /><span v-else>+ إضافة حساب</span>
        </button>
      </div>
      <div v-if="userError" class="alert alert-error" style="margin-top:0.5rem;font-size:0.82rem">{{ userError }}</div>
      
      <div class="items-list" style="margin-top:1rem; max-height:initial">
         <table style="width:100%;text-align:right;border-collapse:collapse" v-if="users.length > 0">
           <tr style="border-bottom:1px solid var(--border)">
             <th style="padding:0.5rem">الاسم</th>
             <th style="padding:0.5rem">البريد</th>
             <th style="padding:0.5rem">الصلاحية</th>
             <th style="padding:0.5rem">إجراء</th>
           </tr>
           <tr v-for="u in users" :key="u.id" style="border-bottom:1px solid rgba(255,255,255,0.05)">
             <td style="padding:0.8rem 0.5rem; font-weight:600">{{ u.name }}</td>
             <td style="padding:0.8rem 0.5rem;color:var(--text-2);font-family:monospace">{{ u.email }}</td>
             <td style="padding:0.8rem 0.5rem">
                <span class="badge" :class="u.role === 'admin' ? 'badge-primary' : 'badge-secondary'" style="font-size:0.75rem">
                  {{ u.role === 'admin' ? 'مدير' : 'ماسح' }}
                </span>
                <span v-if="u.department" style="font-size:0.7rem; color:var(--text-3); display:block; margin-top:4px;">
                  📌 {{ u.department.name }}
                </span>
             </td>
             <td style="padding:0.8rem 0.5rem">
               <button class="btn btn-danger" style="padding:0.3rem 0.6rem;font-size:0.8rem" @click="prepDelete('user', u.id, u.name)">حذف</button>
             </td>
           </tr>
         </table>
         <div v-else-if="usersLoading" style="text-align:center"><div class="spinner" /></div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <Transition name="fade">
      <div v-if="deletingTarget" class="modal-backdrop" @click.self="deletingTarget = null">
        <div class="modal card" style="max-width:380px;text-align:center">
          <div style="font-size:3rem;margin-bottom:1rem">⚠️</div>
          <h3 style="color:var(--text-1)">تأكيد الحذف</h3>
          <p style="color:var(--text-3);margin-top:0.5rem;line-height:1.6">
            هل أنت متأكد من حذف {{ deletingTarget.type === 'dept' ? 'القسم' : (deletingTarget.type === 'user' ? 'الحساب' : 'الاهتمام') }} <b>"{{ deletingTarget.name }}"</b>؟
          </p>
          <div style="display:flex;gap:0.8rem;margin-top:1.8rem">
            <button class="btn btn-danger" style="flex:1" @click="executeDelete" :disabled="isDeleting">
              <span v-if="isDeleting" class="spinner" /> نعم، احذف
            </button>
            <button class="btn btn-ghost" style="flex:1" @click="deletingTarget = null">إلغاء</button>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import {
  getDepartments, createDepartment, deleteDepartment,
  getInterests, createInterest, deleteInterest,
  getUsers, createUser, deleteUser
} from '../../stores/api.js'

const departments = ref([])
const interests = ref([])
const users = ref([])

const newDept = ref('')
const newDeptLogo = ref(null)
const deptLogoInput = ref(null)
const newInterest = ref('')
const newInterestDept = ref('')
const newUser = ref({ name: '', email: '', password: '', role: 'scanner', department_id: '' })

// Get the current admin's department ID from user details (we need to fetch it or rely on existing state)
// For simplicity, we can fetch it when checking session, or we can just see if they have one during load
const adminDeptId = ref(null)

const deptLoading = ref(true)
const intLoading = ref(true)
const usersLoading = ref(true)

const deptError = ref('')
const intError = ref('')
const userError = ref('')
const adding = ref({ dept: false, interest: false, user: false })

const deletingTarget = ref(null)
const isDeleting = ref(false)

const filteredInterests = computed(() => {
  if (!newInterestDept.value) return []
  return interests.value.filter(i => i.department_id === newInterestDept.value)
})

function onLogoChange(e) {
  newDeptLogo.value = e.target.files[0] || null
}

async function loadAll() {
  const [d, i, u] = await Promise.all([getDepartments(), getInterests(), getUsers()])
  departments.value = d.data; deptLoading.value = false
  interests.value = i.data; intLoading.value = false
  users.value = u.data; usersLoading.value = false
  
  // Set adminDeptId based on localStorage or an API call. 
  // Fast approach: we saved the whole user object on login? Let's assume we need to get their info or we can get it from an API
  const storedUser = JSON.parse(localStorage.getItem('admin_user') || '{}')
  adminDeptId.value = storedUser.department_id || null

  // If they are a department admin, force role to scanner
  if (adminDeptId.value) {
    newUser.value.role = 'scanner'
    newUser.value.department_id = adminDeptId.value
  }
}

onMounted(() => {
  const role = localStorage.getItem('admin_role') || 'admin'
  if (role !== 'admin') {
    window.location.href = '/admin'
  } else {
    loadAll()
  }
})

async function addDept() {
  if (!newDept.value.trim()) return
  adding.value.dept = true; deptError.value = ''
  try {
    const formData = new FormData()
    formData.append('name', newDept.value.trim())
    if (newDeptLogo.value) {
      formData.append('logo', newDeptLogo.value)
    }

    await createDepartment(formData)
    newDept.value = ''
    newDeptLogo.value = null
    if (deptLogoInput.value) deptLogoInput.value.value = ''
    
    const { data } = await getDepartments()
    departments.value = data
  } catch (e) {
    deptError.value = e.response?.data?.errors?.name?.[0] || e.response?.data?.message || 'خطأ في الإضافة'
  } finally { adding.value.dept = false }
}

async function addInterest() {
  if (!newInterest.value.trim() || !newInterestDept.value) return
  adding.value.interest = true; intError.value = ''
  try {
    await createInterest({ name: newInterest.value.trim(), department_id: newInterestDept.value })
    newInterest.value = ''
    const { data } = await getInterests()
    interests.value = data
  } catch (e) {
    intError.value = e.response?.data?.errors?.name?.[0] || e.response?.data?.message || 'خطأ في الإضافة'
  } finally { adding.value.interest = false }
}

async function addUser() {
  if (!newUser.value.name || !newUser.value.email || !newUser.value.password) return
  adding.value.user = true; userError.value = ''
  try {
    const payload = { ...newUser.value }
    // Clean up empty general dept
    if (!payload.department_id) delete payload.department_id

    await createUser(payload)
    newUser.value = { name: '', email: '', password: '', role: adminDeptId.value ? 'scanner' : 'scanner', department_id: adminDeptId.value || '' }
    const { data } = await getUsers()
    users.value = data
  } catch (e) {
    userError.value = e.response?.data?.errors?.email?.[0] || e.response?.data?.message || 'حدث خطأ غير متوقع أثناء أضافة المستخدم.'
  } finally { adding.value.user = false }
}

function prepDelete(type, id, name) {
  deletingTarget.value = { type, id, name }
}

async function executeDelete() {
  if (!deletingTarget.value) return
  isDeleting.value = true
  try {
    if (deletingTarget.value.type === 'dept') {
      await deleteDepartment(deletingTarget.value.id)
      const { data } = await getDepartments()
      departments.value = data
    } else if (deletingTarget.value.type === 'interest') {
      await deleteInterest(deletingTarget.value.id)
      const { data } = await getInterests()
      interests.value = data
    } else if (deletingTarget.value.type === 'user') {
      await deleteUser(deletingTarget.value.id)
      const { data } = await getUsers()
      users.value = data
    }
    deletingTarget.value = null
  } catch (e) {
    alert(e.response?.data?.message || 'تعذر الحذف.')
  } finally {
    isDeleting.value = false
  }
}
</script>

<style scoped>
.settings-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.2rem; }
@media (max-width: 700px) { .settings-grid { grid-template-columns: 1fr; } }

.settings-section { padding: 1.5rem; }
.section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; }
.section-header h3 { font-size: 1rem; }

.add-row { display: flex; gap: 8px; }
.add-row .form-input { flex: 1; }

.items-list { margin-top: 1rem; display: flex; flex-direction: column; gap: 6px; max-height: 360px; overflow-y: auto; }
.item-row {
  display: flex; align-items: center; justify-content: space-between;
  padding: 0.6rem 0.8rem;
  border-radius: var(--radius-sm);
  background: rgba(255,255,255,0.03);
  border: 1px solid var(--border);
  transition: border-color 0.2s;
}
.item-row:hover { border-color: rgba(124,58,237,0.3); }
.item-name { font-size: 0.92rem; color: var(--text-2); }
.empty-msg { text-align: center; color: var(--text-3); font-size: 0.85rem; padding: 1.5rem; }

.modal-backdrop {
  position: fixed; inset: 0; z-index: 1000;
  background: rgba(15, 23, 42, 0.6);
  display: flex; align-items: center; justify-content: center; padding: 1rem;
  backdrop-filter: blur(4px);
}
.modal {
  width: 100%; max-width: 480px;
  padding: 2rem;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}
</style>
