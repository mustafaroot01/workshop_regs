<template>
  <div>
    <div class="page-header">
      <div>
        <h2 class="gradient-text">الاستمارات</h2>
        <p style="color:var(--text-3);margin-top:0.3rem;font-size:0.9rem">إدارة استمارات التسجيل</p>
      </div>
      <button v-if="adminRole === 'admin'" class="btn btn-primary" @click="showModal = true">+ استمارة جديدة</button>
    </div>

    <!-- Loading -->
    <div v-if="loading" style="text-align:center;padding:4rem">
      <div class="spinner" style="width:40px;height:40px;border-width:3px" />
    </div>

    <!-- Forms Grid -->
    <div v-else>
      <div v-if="forms.length === 0" class="empty-state container card" style="margin-top:2rem">
        <div style="font-size:3.5rem">📋</div>
        <h3 style="margin-top:1rem">لا توجد استمارات بعد</h3>
        <p style="color:var(--text-3);margin-top:0.5rem">ابدأ بإنشاء استمارة ورشة جديدة الآن.</p>
        <button class="btn btn-primary" style="margin-top:1.5rem" @click="showModal = true">+ أنشئ استمارتك الأولى</button>
      </div>

      <div class="forms-grid">
        <div v-for="f in forms" :key="f.id" class="form-card card">
          <!-- Top Row: Status & Actions -->
          <div class="card-top">
            <span class="badge" :class="f.is_open ? 'badge-success' : 'badge-error'">
              {{ f.is_open ? 'مفتوحة الآن' : 'مغلقة' }}
            </span>
            <div v-if="adminRole === 'admin'" class="card-mgmt">
               <button class="mgmt-btn" @click="toggleOpen(f)" :title="f.is_open ? 'إيقاف التسجيل' : 'فتح التسجيل'">
                 {{ f.is_open ? '🚫' : '✅' }}
               </button>
               <button class="mgmt-btn delete" @click="prepDelete(f)" title="حذف الاستمارة">
                 🗑
               </button>
            </div>
          </div>

          <!-- Main Info -->
          <div class="card-main">
            <div class="card-info">
              <h3 class="card-title">{{ f.title }}</h3>
              <p v-if="f.description" class="card-desc">{{ f.description }}</p>
            </div>
            
            <div class="card-stat-box">
              <span class="stat-num">{{ f.students_count }}</span>
              <span class="stat-label">مسجّل</span>
            </div>
          </div>

          <!-- Actions Footer -->
          <div class="card-actions">
            <router-link v-if="adminRole === 'admin'" :to="{ name: 'AdminFormDetail', params: { id: f.id } }" class="btn btn-secondary flex-1">
              عرض الطلاب
            </router-link>
            <router-link :to="{ name: 'AdminAttendance', params: { formId: f.id } }" class="btn btn-primary flex-1">
              مَسح الحضور
            </router-link>
          </div>

          <!-- Registration Link -->
          <div v-if="adminRole === 'admin'" class="card-link" @click="copyLink(f.id)" :title="copied === f.id ? 'تم النسخ!' : 'نسخ الرابط'">
            <div class="link-label">رابط التسجيل</div>
            <div class="link-val">
               <span class="link-url">/register/{{ f.id }}</span>
               <span class="link-copy-icon">{{ copied === f.id ? '✅' : '📋' }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <Transition name="fade">
      <div v-if="deletingForm" class="modal-backdrop" @click.self="deletingForm = null">
        <div class="modal card" style="max-width:380px;text-align:center">
          <div style="font-size:3rem;margin-bottom:1rem">⚠️</div>
          <h3 style="color:var(--text-1)">تأكيد الحذف</h3>
          <p style="color:var(--text-3);margin-top:0.5rem;line-height:1.6">
            هل أنت متأكد من حذف <b>"{{ deletingForm.title }}"</b>؟<br/>
            سيتم حذف جميع سجلات الطلاب والحضور المرتبطة بها نهائياً.
          </p>
          <div style="display:flex;gap:0.8rem;margin-top:1.8rem">
            <button class="btn btn-danger" style="flex:1" @click="executeDelete" :disabled="isDeleting">
              <span v-if="isDeleting" class="spinner" /> نعم، احذف
            </button>
            <button class="btn btn-ghost" style="flex:1" @click="deletingForm = null">إلغاء</button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Create Form Modal -->
    <Transition name="fade">
      <div v-if="showModal" class="modal-backdrop" @click.self="showModal = false">
        <div class="modal card">
          <h3 style="margin-bottom:1.5rem">إنشاء استمارة جديدة</h3>
          <div class="form-group" style="margin-bottom:1rem">
            <label class="form-label">عنوان الورشة *</label>
            <input v-model="newTitle" type="text" class="form-input" placeholder="مثال: ورشة عمل في البرمجة" autoFocus @keyup.enter="createForm" />
          </div>
          <div class="form-group" style="margin-bottom:1.5rem">
            <label class="form-label">وصف (اختياري)</label>
            <textarea v-model="newDesc" class="form-textarea" placeholder="اكتب تفاصيل إضافية..."></textarea>
          </div>
          <div style="display:flex;gap:0.8rem">
            <button class="btn btn-primary" style="flex:1" @click="createForm" :disabled="!newTitle.trim() || creating">
              <span v-if="creating" class="spinner" /> حفظ
            </button>
            <button class="btn btn-ghost" style="flex:1" @click="showModal = false">إلغاء</button>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { getForms, createForm as apiCreate, updateForm, deleteForm } from '../../stores/api.js'

const adminRole = ref(localStorage.getItem('admin_role') || 'admin')
const forms = ref([])
const loading = ref(true)
const showModal = ref(false)
const newTitle = ref('')
const newDesc = ref('')
const creating = ref(false)
const deletingForm = ref(null)
const isDeleting = ref(false)

async function loadForms() {
  try { const { data } = await getForms(); forms.value = data }
  finally { loading.value = false }
}

onMounted(loadForms)

async function createForm() {
  if (!newTitle.value.trim()) return
  creating.value = true
  try {
    await apiCreate({ title: newTitle.value, description: newDesc.value })
    newTitle.value = ''; newDesc.value = ''; showModal.value = false
    await loadForms()
  } finally { creating.value = false }
}

async function toggleOpen(f) {
  try {
    const nextState = !f.is_open
    await updateForm(f.id, { is_open: nextState })
    f.is_open = nextState
  } catch (e) {
    alert('حدث خطأ أثناء تحديث الحالة.')
  }
}

function prepDelete(f) {
  deletingForm.value = f
}

async function executeDelete() {
  if (!deletingForm.value) return
  isDeleting.value = true
  try {
    await deleteForm(deletingForm.value.id)
    deletingForm.value = null
    await loadForms()
  } catch (e) {
    alert('خطأ: لا يمكن حذف الاستمارة لوجود سجلات مرتبطة بها أو مشكلة في السيرفر.')
  } finally { isDeleting.value = false }
}

function copyLink(id) {
  navigator.clipboard.writeText(`${window.location.origin}/register/${id}`)
  copied.value = id
  setTimeout(() => { copied.value = null }, 2500)
}
</script>

<style scoped>
.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 2rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.forms-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(min(340px, 100%), 1fr));
  gap: 1.5rem;
}

/* --- Card Styles --- */
.form-card {
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  background: #fff;
}

.card-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.card-mgmt {
  display: flex;
  gap: 8px;
}

.mgmt-btn {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  border: 1px solid var(--border);
  background: var(--bg-0);
  cursor: pointer;
  transition: all 0.2s;
  font-size: 0.9rem;
}

.mgmt-btn:hover {
  background: #fff;
  border-color: var(--primary);
  transform: scale(1.05);
}

.mgmt-btn.delete:hover {
  background: #fff5f5;
  border-color: var(--error);
  color: var(--error);
}

.card-main {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
}

.card-info {
  flex: 1;
}

.card-title {
  color: var(--text-1);
  font-size: 1.2rem;
  font-weight: 700;
  line-height: 1.3;
}

.card-desc {
  color: var(--text-3);
  font-size: 0.85rem;
  margin-top: 0.4rem;
  line-height: 1.5;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.card-stat-box {
  background: var(--bg-0);
  padding: 0.75rem 1rem;
  border-radius: 12px;
  text-align: center;
  min-width: 70px;
  border: 1px solid var(--border);
}

.stat-num {
  display: block;
  font-size: 1.4rem;
  font-weight: 800;
  color: var(--primary);
  line-height: 1.1;
}

.stat-label {
  font-size: 0.7rem;
  color: var(--text-3);
  font-weight: 600;
  text-transform: uppercase;
}

.card-actions {
  display: flex;
  gap: 10px;
}

.flex-1 { flex: 1; }

.card-link {
  background: #f8fafc;
  border: 1px dashed var(--border);
  border-radius: 10px;
  padding: 0.6rem 0.8rem;
  cursor: pointer;
  transition: all 0.2s;
}

.card-link:hover {
  background: #fff;
  border-color: var(--primary);
  border-style: solid;
}

.link-label {
  font-size: 0.7rem;
  font-weight: 700;
  color: var(--text-3);
  margin-bottom: 2px;
  text-transform: uppercase;
}

.link-val {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.link-url {
  font-family: monospace;
  font-size: 0.85rem;
  color: var(--primary);
  font-weight: 600;
}

.link-copy-icon {
  font-size: 0.9rem;
}

.empty-state { padding: 4rem 2rem; text-align: center; }

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
