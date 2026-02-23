<template>
  <div>
    <div class="page-header">
      <div style="display:flex;align-items:center;gap:1rem">
        <router-link :to="{ name: 'AdminForms' }" class="btn btn-ghost" style="padding:0.4rem 0.8rem">← رجوع</router-link>
        <div>
          <h2 class="gradient-text">{{ formInfo?.title }}</h2>
          <p style="color:var(--text-3);font-size:0.85rem;margin-top:0.2rem">
            {{ students.length }} طالب مسجل
          </p>
        </div>
      </div>
    </div>

    <!-- Actions Row -->
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem">
      <router-link :to="{ name: 'AdminAttendance', params: { formId: route.params.id } }" class="btn btn-primary">
        📷 تسجيل الحضور
      </router-link>
      <button class="btn btn-secondary" @click="exportToExcel">
        📥 تصدير كملف Excel
      </button>
    </div>

    <!-- Stats -->
    <div class="stats-grid" style="margin-bottom:1.5rem" v-if="stats">
      <div class="stat-card card">
        <div class="stat-num gradient-text">{{ stats.total }}</div>
        <div class="stat-label">إجمالي الطلاب</div>
      </div>
      <div class="stat-card card">
        <div class="stat-num" style="color:var(--success)">{{ stats.present }}</div>
        <div class="stat-label">حاضر</div>
      </div>
      <div class="stat-card card">
        <div class="stat-num" style="color:var(--error)">{{ stats.absent }}</div>
        <div class="stat-label">غائب</div>
      </div>
      <div class="stat-card card">
        <div class="stat-num" style="color:var(--secondary)">
          {{ stats.total > 0 ? Math.round((stats.present / stats.total) * 100) : 0 }}%
        </div>
        <div class="stat-label">نسبة الحضور</div>
      </div>
    </div>

    <!-- Search -->
    <div style="margin-bottom:1rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:0.75rem;">
      <input v-model="search" type="text" class="form-input" placeholder="🔍 بحث عن طالب..." style="flex:1;min-width:200px;max-width:340px" />
      <span style="color:var(--text-3);font-size:0.85rem;white-space:nowrap">صفحة {{ currentPage }} من {{ totalPages || 1 }}</span>
    </div>

    <!-- Table -->
    <div class="card">
      <div v-if="loading" style="text-align:center;padding:3rem"><div class="spinner" style="width:36px;height:36px" /></div>
      <div v-else class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>الاسم</th>
              <th>الجنس</th>
              <th>المرحلة</th>
              <th>الدراسة</th>
              <th>القسم</th>
              <th>التواصل</th>
              <th>الاهتمامات</th>
              <th>الحضور</th>
              <th>تفاصيل</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(s, i) in paginated" :key="s.id" class="clickable-row">
              <td style="color:var(--text-3)">{{ (currentPage - 1) * itemsPerPage + i + 1 }}</td>
              <td style="color:var(--text-1);font-weight:600">{{ s.name }}</td>
              <td>{{ s.gender === 'male' ? '👨' : '👩' }}</td>
              <td><span class="badge badge-secondary">{{ s.stage }}</span></td>
              <td>{{ s.study_type === 'morning' ? '☀️ صباحي' : '🌙 مسائي' }}</td>
              <td>{{ s.department }}</td>
              <td style="font-size:0.8rem">{{ s.email || s.phone }}</td>
              <td>
                <div class="tags">
                  <span v-for="int in s.interests.slice(0, 2)" :key="int" class="badge badge-primary" style="margin:2px">{{ int }}</span>
                  <span v-if="s.interests.length > 2" class="badge badge-ghost" style="margin:2px">+{{ s.interests.length - 2 }}</span>
                </div>
              </td>
              <td>
                <span class="badge" :class="s.attended ? 'badge-success' : 'badge-error'">
                  {{ s.attended ? '✅ حاضر' : '❌ غائب' }}
                </span>
              </td>
              <td>
                <div style="display:flex; gap:0.3rem">
                  <button class="btn btn-ghost" style="padding:0.3rem 0.5rem;font-size:0.8rem" @click="openStudent(s)">عرض</button>
                  <button class="btn btn-ghost" style="padding:0.3rem 0.5rem;font-size:0.8rem;color:var(--primary)" @click="openEdit(s)">تعديل</button>
                  <button class="btn btn-danger" style="padding:0.3rem 0.5rem;font-size:0.8rem" @click="deletingStudentId = s.id">حذف</button>
                </div>
              </td>
            </tr>
            <tr v-if="paginated.length === 0">
              <td colspan="9" style="text-align:center;padding:2rem;color:var(--text-3)">لا توجد نتائج</td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <!-- Pagination Controls -->
      <div v-if="totalPages > 1" class="pagination">
        <button class="btn btn-ghost" :disabled="currentPage === 1" @click="currentPage--">السابق</button>
        <span class="page-info">{{ currentPage }} / {{ totalPages }}</span>
        <button class="btn btn-ghost" :disabled="currentPage === totalPages" @click="currentPage++">التالي</button>
      </div>
    </div>

    <!-- Student Details Modal -->
    <Transition name="fade">
      <div v-if="selectedStudent" class="modal-backdrop" @click.self="closeStudent">
        <div class="modal card">
          <div class="modal-header">
            <h3>{{ selectedStudent.name }} {{ selectedStudent.gender === 'male' ? '👨' : '👩' }}</h3>
            <button class="btn-close" @click="closeStudent">×</button>
          </div>
          
          <div class="modal-body">
            <div class="detail-grid">
              <div class="detail-box">
                <span class="detail-lbl">المرحلة</span>
                <span class="detail-val">{{ selectedStudent.stage }}</span>
              </div>
              <div class="detail-box">
                <span class="detail-lbl">الدراسة</span>
                <span class="detail-val">{{ selectedStudent.study_type === 'morning' ? '☀️ صباحية' : '🌙 مسائية' }}</span>
              </div>
              <div class="detail-box">
                <span class="detail-lbl">القسم</span>
                <span class="detail-val">{{ selectedStudent.department }}</span>
              </div>
              <div class="detail-box">
                <span class="detail-lbl">تاريخ التسجيل</span>
                <span class="detail-val" style="direction:ltr">{{ selectedStudent.registered_at }}</span>
              </div>
              <div class="detail-box" style="display:flex; flex-direction:column; justify-content:space-between; align-items:flex-start">
                <div>
                  <span class="detail-lbl">حالة الحضور</span>
                  <span class="badge" :class="selectedStudent.attended ? 'badge-success' : 'badge-error'" style="margin-bottom:8px; display:inline-block">
                    {{ selectedStudent.attended ? '✅ حضر' + (selectedStudent.attended_at ? ' (' + selectedStudent.attended_at + ')' : '') : '❌ غائب' }}
                  </span>
                </div>
                <button 
                  class="btn" 
                  :class="selectedStudent.attended ? 'btn-ghost' : 'btn-primary'" 
                  style="padding:0.3rem 0.6rem; font-size:0.8rem; width:100%"
                  @click="toggleStudentAttendance(selectedStudent)"
                  :disabled="isToggling"
                >
                  {{ isToggling ? 'جاري التحديث...' : (selectedStudent.attended ? 'إلغاء الحضور' : 'تسجيل الحضور يدوياً') }}
                </button>
              </div>
            </div>

            <div class="detail-section">
              <span class="detail-lbl">معلومات التواصل</span>
              <div class="contact-info">
                <span v-if="selectedStudent.email">📧 {{ selectedStudent.email }}</span>
                <span v-if="selectedStudent.phone">📞 {{ selectedStudent.phone }}</span>
              </div>
            </div>

            <div class="detail-section" v-if="selectedStudent.interests.length">
              <span class="detail-lbl">الاهتمامات</span>
              <div class="tags" style="margin-top:6px">
                <span v-for="int in selectedStudent.interests" :key="int" class="badge badge-primary">{{ int }}</span>
              </div>
            </div>

            <div class="detail-section" v-if="selectedStudent.description">
              <span class="detail-lbl">وصف يتحدث عن نفسه</span>
              <div class="desc-box">
                {{ selectedStudent.description }}
              </div>
            </div>

            <div class="detail-section" style="text-align:center">
              <span class="detail-lbl" style="text-align:right">رمز QR</span>
              <div style="margin:1rem auto;display:inline-flex;padding:12px;background:white;border-radius:12px">
                <canvas ref="qrCanvas" />
              </div>
            </div>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Delete Confirmation Modal -->
    <Transition name="fade">
      <div v-if="deletingStudentId" class="modal-backdrop" @click.self="deletingStudentId = null">
        <div class="modal card" style="max-width:380px;text-align:center">
          <div style="font-size:3rem;margin-bottom:1rem">⚠️</div>
          <h3 style="color:var(--text-1)">تأكيد الحذف</h3>
          <p style="color:var(--text-3);margin-top:0.5rem;line-height:1.6">
            هل أنت متأكد من حذف حساب وتسجيل هذا الطالب بشكل نهائي؟
          </p>
          <div style="display:flex;gap:0.8rem;margin-top:1.8rem">
            <button class="btn btn-danger" style="flex:1" @click="executeDelete" :disabled="isSaving">
              <span v-if="isSaving" class="spinner" /> نعم، احذف
            </button>
            <button class="btn btn-ghost" style="flex:1" @click="deletingStudentId = null">إلغاء</button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Edit Student Modal -->
    <Transition name="fade">
      <div v-if="editingStudent" class="modal-backdrop" @click.self="editingStudent = null" style="z-index: 200;">
        <div class="modal card">
          <div class="modal-header">
            <h3>تعديل بيانات {{ editingStudent.name }}</h3>
            <button class="btn-close" @click="editingStudent = null">×</button>
          </div>
          <div class="modal-body">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem">
              <!-- Name -->
              <div style="grid-column:1/-1">
                <label style="display:block;margin-bottom:4px;font-size:0.85rem">الاسم</label>
                <input v-model="editFormData.name" class="form-input" style="width:100%" />
                <span class="form-error" v-if="editErrors.name">{{ editErrors.name }}</span>
              </div>
              <div style="grid-column:1/-1; display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                 <!-- Gender -->
                <div>
                  <label style="display:block;margin-bottom:4px;font-size:0.85rem">الجنس</label>
                  <select v-model="editFormData.gender" class="form-select" style="width:100%">
                    <option value="male">ذكر</option>
                    <option value="female">أنثى</option>
                  </select>
                </div>
                <!-- Stage -->
                <div>
                  <label style="display:block;margin-bottom:4px;font-size:0.85rem">المرحلة</label>
                  <select v-model="editFormData.stage" class="form-select" style="width:100%">
                    <option :value="1">المرحلة الأولى</option>
                    <option :value="2">المرحلة الثانية</option>
                    <option :value="3">المرحلة الثالثة</option>
                    <option :value="4">المرحلة الرابعة</option>
                  </select>
                </div>
                <!-- Study Type -->
                <div>
                  <label style="display:block;margin-bottom:4px;font-size:0.85rem">الدراسة</label>
                  <select v-model="editFormData.study_type" class="form-select" style="width:100%">
                    <option value="morning">صباحي</option>
                    <option value="evening">مسائي</option>
                  </select>
                </div>
              </div>

              <!-- Dept -->
              <div style="grid-column:1/-1">
                <label style="display:block;margin-bottom:4px;font-size:0.85rem">القسم</label>
                <select v-model="editFormData.department_id" class="form-select" style="width:100%">
                  <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.name }}</option>
                </select>
              </div>

              <!-- Contact -->
              <div>
                <label style="display:block;margin-bottom:4px;font-size:0.85rem">البريد الإلكتروني</label>
                <input v-model="editFormData.email" class="form-input" style="width:100%" type="email"/>
              </div>
              <div>
                <label style="display:block;margin-bottom:4px;font-size:0.85rem">رقم الهاتف</label>
                <input v-model="editFormData.phone" type="tel" class="form-input" style="width:100%" @input="editFormData.phone = editFormData.phone.replace(/[^0-9]/g, '')" />
              </div>

              <!-- Interests -->
              <div style="grid-column:1/-1" v-if="availableEditInterests.length">
                <label style="display:block;margin-bottom:8px;font-size:0.85rem">الاهتمامات</label>
                <div style="display:flex; flex-wrap:wrap; gap:8px">
                  <label v-for="int in availableEditInterests" :key="int.id" style="display:flex;align-items:center;gap:4px;background:rgba(255,255,255,0.05);padding:4px 8px;border-radius:6px;cursor:pointer;font-size:0.85rem">
                    <input type="checkbox" :value="int.id" v-model="editFormData.interests" /> {{ int.name }}
                  </label>
                </div>
              </div>
            </div>

            <div v-if="saveError" class="alert alert-error" style="margin-top:1rem">{{ saveError }}</div>
            <div style="margin-top:2rem;text-align:left">
              <button class="btn btn-ghost" style="margin-left:8px" @click="editingStudent = null">إلغاء</button>
              <button class="btn btn-primary" @click="saveEdit" :disabled="isSaving">
                <span v-if="isSaving" class="spinner" /> <span v-else>حفظ التعديلات</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch, nextTick, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { getStudents, getStats, updateStudent, deleteStudent, getDepartments, getInterests, toggleAttendance as toggleAttendanceApi } from '../../stores/api.js'
import QRCode from 'qrcode'
import * as XLSX from 'xlsx'

const route = useRoute()
const loading = ref(true)
const formInfo = ref(null)
const students = ref([])
const stats = ref(null)

// Pagination & Search
const search = ref('')
const currentPage = ref(1)
const itemsPerPage = 30

// Modals
const selectedStudent = ref(null)
const qrCanvas = ref(null)

const deletingStudentId = ref(null)
const editingStudent = ref(null)
const isSaving = ref(false)
const saveError = ref('')

const departments = ref([])
const interests = ref([])

const editFormData = reactive({
  name: '', gender: '', stage: '', study_type: '', department_id: '', email: '', phone: '', description: '', interests: []
})
const editErrors = reactive({})

async function load() {
  const [sRes, stRes, dRes, iRes] = await Promise.all([
    getStudents(route.params.id),
    getStats(route.params.id),
    getDepartments(),
    getInterests()
  ])
  formInfo.value = sRes.data.form
  students.value = sRes.data.students
  stats.value = stRes.data
  departments.value = dRes.data
  interests.value = iRes.data
  loading.value = false
}

onMounted(() => {
  if (localStorage.getItem('admin_role') !== 'admin') {
    window.location.href = '/admin'
  } else {
    load()
  }
})

// Reset pagination on search
watch(search, () => { currentPage.value = 1 })

const filtered = computed(() => {
  const q = search.value.toLowerCase()
  if (!q) return students.value
  return students.value.filter(s =>
    s.name.toLowerCase().includes(q) ||
    (s.department || '').toLowerCase().includes(q)
  )
})

const totalPages = computed(() => Math.ceil(filtered.value.length / itemsPerPage))

const paginated = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage
  return filtered.value.slice(start, start + itemsPerPage)
})

async function openStudent(s) {
  selectedStudent.value = s
  await nextTick()
  if (qrCanvas.value) {
    await QRCode.toCanvas(qrCanvas.value, s.qr_token, {
      width: 150, margin: 1,
      color: { dark: '#0f172a', light: '#ffffff' },
      errorCorrectionLevel: 'M',
    })
  }
}

function exportToExcel() {
  if (!students.value || students.value.length === 0) {
    alert('لا يوجد طلاب لتصديرهم.')
    return
  }

  // Format data for Excel
  const excelData = students.value.map((s, index) => ({
    'التسلسل': index + 1,
    'الاسم': s.name,
    'الجنس': s.gender === 'male' ? 'ذكر' : 'أنثى',
    'المرحلة': `المرحلة ${s.stage}`,
    'الدراسة': s.study_type === 'morning' ? 'صباحي' : 'مسائي',
    'القسم': s.department || 'غير محدد',
    'البريد الإلكتروني': s.email || '-',
    'رقم الهاتف': s.phone || '-',
    'الاهتمامات': (s.interests || []).join(', '),
    'تاريخ التسجيل': s.registered_at,
    'حالة الحضور': s.attended ? 'حاضر' : 'غائب',
    'وقت تسجيل الحضور': s.attended_at || '-'
  }))

  const worksheet = XLSX.utils.json_to_sheet(excelData)
  
  // Right to left layout for arabic support in excel
  if (!worksheet['!cols']) worksheet['!cols'] = []
  worksheet['!dir'] = 'rtl'

  const workbook = XLSX.utils.book_new()
  XLSX.utils.book_append_sheet(workbook, worksheet, 'الطلاب')

  const safeTitle = (formInfo.value?.title || 'ورشة').replace(/[^a-zA-Z0-9\u0600-\u06FF\s]/g, '_')
  const fileName = `سجل_طلاب_${safeTitle}.xlsx`

  XLSX.writeFile(workbook, fileName)
}

function closeStudent() {
  selectedStudent.value = null
}

const availableEditInterests = computed(() => {
  if (!editFormData.department_id) return []
  return interests.value.filter(i => i.department_id === editFormData.department_id)
})

watch(() => editFormData.department_id, (newVal, oldVal) => {
  if (oldVal) editFormData.interests = []
})

const isToggling = ref(false)

async function toggleStudentAttendance(student) {
  isToggling.value = true
  try {
    const res = await toggleAttendanceApi(student.id)
    student.attended = res.data.attended
    student.attended_at = res.data.attended_at
    
    // update parent stats and list if needed
    const index = students.value.findIndex(s => s.id === student.id)
    if (index !== -1) {
      students.value[index].attended = res.data.attended
      students.value[index].attended_at = res.data.attended_at
    }
    
    // refetch stats to keep them accurate
    const stRes = await getStats(route.params.id)
    stats.value = stRes.data
    
  } catch (err) {
    alert(err.response?.data?.message || 'حدث خطأ أثناء تغيير حالة الحضور.')
  } finally {
    isToggling.value = false
  }
}

function openEdit(s) {
  Object.keys(editErrors).forEach(k => delete editErrors[k])
  saveError.value = ''
  editingStudent.value = s
  editFormData.name = s.name
  editFormData.gender = s.gender
  editFormData.stage = s.stage
  editFormData.study_type = s.study_type || 'morning'
  editFormData.department_id = s.department_id || ''
  editFormData.email = s.email || ''
  editFormData.phone = s.phone || ''
  editFormData.description = s.description || ''
  editFormData.interests = s.interest_ids ? [...s.interest_ids] : []
}

async function saveEdit() {
  Object.keys(editErrors).forEach(k => delete editErrors[k])
  saveError.value = ''
  isSaving.value = true
  try {
    const payload = { ...editFormData }
    if (!payload.email) delete payload.email
    if (!payload.phone) delete payload.phone
    
    await updateStudent(editingStudent.value.id, payload)
    await load() // refresh data
    editingStudent.value = null
  } catch (err) {
    const errs = err.response?.data?.errors
    if (errs) {
      Object.entries(errs).forEach(([k, v]) => { editErrors[k] = v[0] })
    } else {
      saveError.value = err.response?.data?.message || 'حدث خطأ. يرجى المحاولة مجدداً.'
    }
  } finally {
    isSaving.value = false
  }
}

async function executeDelete() {
  if (!deletingStudentId.value) return
  isSaving.value = true
  try {
    await deleteStudent(deletingStudentId.value)
    await load() // refresh data
    deletingStudentId.value = null
  } catch (err) {
    alert(err.response?.data?.message || 'تعذر الحذف.')
  } finally {
    isSaving.value = false
  }
}
</script>

<style scoped>
.page-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
  gap: 1rem;
}
.tags { display: flex; flex-wrap: wrap; gap: 4px; }
.badge-ghost { background: var(--bg-card); border: 1px solid var(--border); color: var(--text-2); }

.clickable-row { transition: background 0.2s; }
.clickable-row:hover { background: rgba(255,255,255,0.03); }

.pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  padding: 1rem;
  border-top: 1px solid var(--border);
}
.page-info { font-size: 0.9rem; color: var(--text-2); font-weight: 500; }

.modal-backdrop {
  position: fixed; inset: 0; z-index: 100;
  background: rgba(0,0,0,0.6);
  display: flex; align-items: center; justify-content: center; padding: 1rem;
  backdrop-filter: blur(6px);
}
.modal { 
  width: 100%; max-width: 600px; 
  max-height: 90vh;
  display: flex; flex-direction: column;
  padding: 0;
  border: 1px solid rgba(255,255,255,0.1);
}
.modal-header {
  display: flex; justify-content: space-between; align-items: center;
  padding: 1.2rem 1.5rem;
  border-bottom: 1px solid var(--border);
  background: rgba(255,255,255,0.02);
}
.modal-header h3 { color: var(--text-1); font-size: 1.2rem; }
.btn-close {
  background: transparent; border: none; color: var(--text-3);
  font-size: 1.5rem; cursor: pointer; line-height: 1;
}
.btn-close:hover { color: var(--error); }

.modal-body {
  padding: 1.5rem;
  overflow-y: auto;
}

.detail-grid {
  display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;
  margin-bottom: 1.5rem;
}
.detail-box {
  background: rgba(255,255,255,0.02);
  padding: 1rem; border-radius: var(--radius-sm);
  border: 1px solid var(--border);
}
.detail-lbl { display: block; font-size: 0.8rem; color: var(--text-3); margin-bottom: 4px; }
.detail-val { font-size: 0.95rem; color: var(--text-1); font-weight: 600; }

.detail-section { margin-bottom: 1.5rem; }
.contact-info { display: flex; gap: 1.5rem; margin-top: 6px; font-size: 0.95rem; color: var(--text-2); }
.desc-box {
  margin-top: 6px;
  background: rgba(0,0,0,0.2);
  border-left: 3px solid var(--primary-light);
  padding: 1rem;
  border-radius: 0 var(--radius-sm) var(--radius-sm) 0;
  color: var(--text-2);
  font-size: 0.95rem;
  line-height: 1.6;
}
</style>
