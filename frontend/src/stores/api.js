import axios from 'axios'

const api = axios.create({
    baseURL: '/api',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
})

// Attach token automatically
api.interceptors.request.use((config) => {
    const token = localStorage.getItem('admin_token')
    if (token) config.headers.Authorization = `Bearer ${token}`
    return config
})

// Handle 401 globally
api.interceptors.response.use(
    (r) => r,
    (err) => {
        if (err.response?.status === 401) {
            localStorage.removeItem('admin_token')
            window.location.href = '/admin/login'
        }
        return Promise.reject(err)
    }
)

// ── Public ──────────────────────────────────────────────────────
export const getForm = (id) => api.get(`/forms/${id}`)
export const registerStudent = (id, data) => api.post(`/forms/${id}/register`, data)

// ── Admin Auth ──────────────────────────────────────────────────
export const adminLogin = (data) => api.post('/admin/login', data)
export const adminLogout = () => api.post('/admin/logout')
export const adminMe = () => api.get('/admin/me')

// ── Dashboard ───────────────────────────────────────────────────
export const getOverviewStats = () => api.get('/admin/stats/overview')
export const getChartStats = () => api.get('/admin/stats/charts')

// ── Forms ──────────────────────────────────────────────────────
export const getForms = () => api.get('/admin/forms')
export const createForm = (data) => api.post('/admin/forms', data)
export const updateForm = (id, data) => api.put(`/admin/forms/${id}`, data)
export const deleteForm = (id) => api.delete(`/admin/forms/${id}`)

// ── Students ────────────────────────────────────────────────────
export const getStudents = (formId) => api.get(`/admin/forms/${formId}/students`)
export const updateStudent = (id, data) => api.put(`/admin/students/${id}`, data)
export const deleteStudent = (id) => api.delete(`/admin/students/${id}`)
export const getStats = (formId) => api.get(`/admin/forms/${formId}/stats`)

// ── Attendance ──────────────────────────────────────────────────
export const scanAttendance = (qr_token) => api.post('/admin/attendance/scan', { qr_token })
export const toggleAttendance = (studentId) => api.patch(`/admin/students/${studentId}/toggle-attendance`)

// ── Departments ─────────────────────────────────────────────────
export const getDepartments = () => api.get('/admin/departments')
export const createDepartment = (data) => api.post('/admin/departments', data, { headers: { 'Content-Type': 'multipart/form-data' } })
export const deleteDepartment = (id) => api.delete(`/admin/departments/${id}`)

// ── Interests ───────────────────────────────────────────────────
export const getInterests = () => api.get('/admin/interests')
export const createInterest = (data) => api.post('/admin/interests', data)
export const deleteInterest = (id) => api.delete(`/admin/interests/${id}`)

// ── Admin Users ─────────────────────────────────────────────────
export const getUsers = () => api.get('/admin/users')
export const createUser = (data) => api.post('/admin/users', data)
export const deleteUser = (id) => api.delete(`/admin/users/${id}`)

