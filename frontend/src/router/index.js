import { createRouter, createWebHistory } from 'vue-router'

const routes = [
    // ── Public ──────────────────────────────────────────────────
    {
        path: '/register/:formId',
        name: 'Register',
        component: () => import('../views/RegisterView.vue'),
    },
    {
        path: '/register/:formId/success',
        name: 'RegisterSuccess',
        component: () => import('../views/RegisterSuccessView.vue'),
    },

    // ── Admin ────────────────────────────────────────────────────
    {
        path: '/admin/login',
        name: 'AdminLogin',
        component: () => import('../views/admin/LoginView.vue'),
        meta: { guestOnly: true },
    },
    {
        path: '/admin',
        component: () => import('../views/admin/AdminLayout.vue'),
        meta: { requiresAuth: true },
        children: [
            {
                path: '',
                name: 'AdminDashboard',
                component: () => import('../views/admin/DashboardView.vue'),
            },
            {
                path: 'forms',
                name: 'AdminForms',
                component: () => import('../views/admin/FormsView.vue'),
            },
            {
                path: 'forms/:id',
                name: 'AdminFormDetail',
                component: () => import('../views/admin/FormDetailView.vue'),
            },
            {
                path: 'attendance/:formId',
                name: 'AdminAttendance',
                component: () => import('../views/admin/AttendanceView.vue'),
            },
            {
                path: 'settings',
                name: 'AdminSettings',
                component: () => import('../views/admin/SettingsView.vue'),
            },
        ],
    },

    // ── Fallback ─────────────────────────────────────────────────
    { path: '/', redirect: '/admin/login' },
]

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior: () => ({ top: 0 }),
})

router.beforeEach((to) => {
    const token = localStorage.getItem('admin_token')
    if (to.meta.requiresAuth && !token) return { name: 'AdminLogin' }
    if (to.meta.guestOnly && token) return { name: 'AdminDashboard' }
})

export default router
