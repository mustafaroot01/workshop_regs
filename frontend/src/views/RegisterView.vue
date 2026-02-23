<template>
  <div class="page reg-page">


    <div class="container reg-container">
      <!-- Loading State -->
      <div v-if="loading" class="center-screen">
        <div class="spinner" style="width:48px;height:48px;border-width:4px" />
        <p style="margin-top:1rem;color:var(--text-2)">جاري تحميل الاستمارة...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="loadError" class="center-screen">
        <div style="font-size:4rem">🔒</div>
        <h2 style="margin-top:1rem;color:var(--error)">تم غلق استمارة التسجيل</h2>
        <p style="color:var(--text-2);margin-top:0.5rem">عذراً، لم يعد التسجيل متاحاً في هذه الاستمارة حالياً.</p>
      </div>

      <!-- Registration Form -->
      <Transition name="slide-up" appear>
        <div v-if="!loading && !loadError" class="reg-card card">

          <!-- Header -->
          <div class="reg-header">
            <div class="reg-logo">
              <img :src="form?.logo_url || '/logo.png'" alt="شعار المركز" style="width: 100%; height: 100%; object-fit: contain; border-radius: 50%; box-shadow: 0 4px 15px rgba(0,0,0,0.1);" onerror="this.src='/logo.png'">
            </div>
            <h1 class="gradient-text" style="font-size:2rem;margin-bottom:0.2rem;line-height:1.4;">مركز التعليم المستمر</h1>
            <h2 style="font-size:1.2rem;color:var(--text-2);margin-bottom:0.5rem;">جامعة بلاد الرافدين</h2>
            <h3 style="font-size:1.1rem;color:var(--primary);margin-bottom:0.5rem;font-weight:600">{{ form?.title }}</h3>
            <p v-if="form?.description" class="reg-desc">{{ form.description }}</p>
            <div class="reg-divider" />
          </div>

          <form @submit.prevent="submitForm" novalidate>
            <div class="form-grid">

              <!-- Full Name -->
              <div class="form-group full-width">
                <label class="form-label">الاسم الثلاثي *</label>
                <input v-model="form_data.name" type="text" class="form-input"
                       :class="{ error: errors.name }" placeholder="أدخل اسمك الثلاثي الكامل" />
                <span v-if="errors.name" class="form-error">{{ errors.name }}</span>
              </div>

              <!-- Gender -->
              <div class="form-group">
                <label class="form-label">الجنس *</label>
                <div class="gender-grid">
                  <label class="gender-option" :class="{ active: form_data.gender === 'male' }">
                    <input v-model="form_data.gender" type="radio" value="male" hidden />
                    <span>👨 ذكر</span>
                  </label>
                  <label class="gender-option" :class="{ active: form_data.gender === 'female' }">
                    <input v-model="form_data.gender" type="radio" value="female" hidden />
                    <span>👩 أنثى</span>
                  </label>
                </div>
                <span v-if="errors.gender" class="form-error">{{ errors.gender }}</span>
              </div>

              <!-- Study Type -->
              <div class="form-group">
                <label class="form-label">الدراسة *</label>
                <div class="gender-grid">
                  <label class="gender-option" :class="{ active: form_data.study_type === 'morning' }">
                    <input v-model="form_data.study_type" type="radio" value="morning" hidden />
                    <span>☀️ صباحي</span>
                  </label>
                  <label class="gender-option" :class="{ active: form_data.study_type === 'evening' }">
                    <input v-model="form_data.study_type" type="radio" value="evening" hidden />
                    <span>🌙 مسائي</span>
                  </label>
                </div>
                <span v-if="errors.study_type" class="form-error">{{ errors.study_type }}</span>
              </div>

              <!-- Stage -->
              <div class="form-group">
                <label class="form-label">المرحلة الدراسية *</label>
                <select v-model="form_data.stage" class="form-select" :class="{ error: errors.stage }">
                  <option value="" disabled>اختر المرحلة</option>
                  <option value="1">المرحلة الأولى</option>
                  <option value="2">المرحلة الثانية</option>
                  <option value="3">المرحلة الثالثة</option>
                  <option value="4">المرحلة الرابعة</option>
                </select>
                <span v-if="errors.stage" class="form-error">{{ errors.stage }}</span>
              </div>

              <!-- Department -->
              <div class="form-group full-width" v-if="!form?.department_id">
                <label class="form-label">القسم *</label>
                <select v-model="form_data.department_id" class="form-select" :class="{ error: errors.department_id }">
                  <option value="" disabled>اختر القسم</option>
                  <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.name }}</option>
                </select>
                <span v-if="errors.department_id" class="form-error">{{ errors.department_id }}</span>
              </div>

              <!-- Email -->
              <div class="form-group">
                <label class="form-label">البريد الإلكتروني</label>
                <input v-model="form_data.email" type="email" class="form-input"
                       :class="{ error: errors.contact }" placeholder="example@email.com" />
              </div>

              <!-- Phone -->
              <div class="form-group">
                <label class="form-label">رقم الهاتف</label>
                <input v-model="form_data.phone" type="tel" class="form-input" @input="form_data.phone = form_data.phone.replace(/[^0-9]/g, '')"
                       :class="{ error: errors.contact }" placeholder="07XXXXXXXXX" />
                <span v-if="errors.contact" class="form-error">{{ errors.contact }}</span>
              </div>

              <!-- Interests -->
              <div class="form-group full-width" v-if="form_data.department_id && availableInterests.length">
                <label class="form-label">الاهتمامات</label>
                <div class="pill-grid">
                  <template v-for="interest in availableInterests" :key="interest.id">
                    <input type="checkbox" class="pill-item"
                           :id="`int-${interest.id}`" :value="interest.id" v-model="form_data.interests" />
                    <label :for="`int-${interest.id}`" class="pill-label">{{ interest.name }}</label>
                  </template>
                </div>
              </div>

              <!-- Description -->
              <div class="form-group full-width">
                <label class="form-label">تحدث عن نفسك</label>
                <textarea v-model="form_data.description" class="form-textarea"
                          placeholder="أخبرنا شيئاً عن نفسك، خبراتك، أهدافك..."></textarea>
              </div>

              <!-- Global Error -->
              <div v-if="submitError" class="full-width">
                <div class="alert alert-error">{{ submitError }}</div>
              </div>

              <!-- Submit -->
              <div class="form-group full-width">
                <button type="submit" class="btn btn-primary btn-full" :disabled="submitting" style="padding:1rem;font-size:1.1rem;">
                  <span v-if="submitting" class="spinner" />
                  <span v-else>✨ إرسال الاستمارة</span>
                </button>
              </div>

            </div>
          </form>
        </div>
      </Transition>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { getForm, registerStudent } from '../stores/api.js'

const route = useRoute()
const router = useRouter()

const loading = ref(true)
const loadError = ref(false)
const submitting = ref(false)
const submitError = ref('')

const form = ref(null)
const departments = ref([])
const interests = ref([])

const form_data = reactive({
  name: '',
  gender: '',
  email: '',
  phone: '',
  stage: '',
  study_type: '',
  department_id: '',
  interests: [],
  description: '',
})
const errors = reactive({})

const availableInterests = computed(() => {
  if (!form_data.department_id) return []
  return interests.value.filter(i => i.department_id === form_data.department_id)
})

watch(() => form_data.department_id, () => {
  form_data.interests = []
})

onMounted(async () => {
  try {
    const { data } = await getForm(route.params.formId)
    form.value = data.form
    departments.value = data.departments
    interests.value = data.interests
    
    if (form.value.department_id) {
      form_data.department_id = form.value.department_id
    }
  } catch {
    loadError.value = true
  } finally {
    loading.value = false
  }
})

function validate() {
  Object.keys(errors).forEach(k => delete errors[k])
  let valid = true
  if (!form_data.name.trim() || form_data.name.trim().split(' ').length < 2) {
    errors.name = 'يرجى إدخال الاسم الثلاثي كاملاً.'
    valid = false
  }
  if (!form_data.gender) { errors.gender = 'يرجى اختيار الجنس.'; valid = false }
  if (!form_data.study_type) { errors.study_type = 'يرجى اختيار الدراسة.'; valid = false }
  if (!form_data.stage) { errors.stage = 'يرجى اختيار المرحلة.'; valid = false }
  if (!form_data.department_id) { errors.department_id = 'يرجى اختيار القسم.'; valid = false }
  if (!form_data.email && !form_data.phone) {
    errors.contact = 'يجب إدخال البريد الإلكتروني أو رقم الهاتف على الأقل.'
    valid = false
  }
  return valid
}

async function submitForm() {
  if (!validate()) return
  submitting.value = true
  submitError.value = ''
  try {
    const payload = { ...form_data, stage: Number(form_data.stage) }
    const { data } = await registerStudent(route.params.formId, payload)
    router.push({
      name: 'RegisterSuccess',
      params: { formId: route.params.formId },
      query: { name: data.student.name, qr: data.qr_token },
    })
  } catch (err) {
    const errs = err.response?.data?.errors
    if (errs) {
      Object.entries(errs).forEach(([k, v]) => { errors[k] = v[0] })
    } else {
      submitError.value = err.response?.data?.message || 'حدث خطأ. يرجى المحاولة مجدداً.'
    }
  } finally {
    submitting.value = false
  }
}
</script>

<style scoped>
.reg-page {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem 0;
  position: relative;
  overflow: hidden;
}



.reg-container { 
  position: relative; 
  z-index: 1; 
  width: 100%; 
  max-width: 720px; 
  margin: 0 auto;
  padding: 1rem; 
}

.reg-card { 
  padding: 3rem; 
  box-shadow: 0 20px 50px rgba(0,0,0,0.05);
}

.reg-header { text-align: center; margin-bottom: 2rem; }
.reg-logo {
  width: 64px; height: 64px;
  margin: 0 auto 1rem;
}
.reg-desc { color: var(--text-2); margin-top: 0.5rem; font-size: 0.95rem; }
.reg-divider {
  height: 1px;
  background: linear-gradient(to right, transparent, var(--border), transparent);
  margin-top: 1.5rem;
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.2rem;
}
.full-width { grid-column: 1 / -1; }

.gender-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.gender-option {
  display: flex; align-items: center; justify-content: center;
  padding: 0.65rem;
  border-radius: var(--radius-sm);
  border: 1px solid var(--border);
  background: var(--bg-card);
  cursor: pointer;
  font-size: 0.95rem;
  font-weight: 500;
  transition: all 0.2s;
}
.gender-option:hover { border-color: var(--primary); }
.gender-option.active {
  border-color: var(--primary);
  background: var(--primary-glow);
  color: var(--primary);
  font-weight: 700;
}

.form-input.error, .form-select.error { border-color: var(--error); }

.center-screen {
  text-align: center;
  padding: 4rem 2rem;
}

@media (max-width: 600px) {
  .form-grid { grid-template-columns: 1fr; }
  .reg-card { padding: 1.5rem; }
}
</style>
