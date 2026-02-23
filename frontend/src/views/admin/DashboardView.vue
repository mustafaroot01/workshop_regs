<template>
  <div>
    <div style="margin-bottom: 2rem;">
      <h2 class="gradient-text">الرئيسية</h2>
      <p style="color:var(--text-3); font-size:0.9rem; margin-top:0.3rem">
        نظرة عامة على إحصائيات النظام
      </p>
    </div>

    <!-- Loading State -->
    <div v-if="loading" style="text-align:center; padding: 4rem;">
      <div class="spinner" style="width:40px;height:40px;border-width:3px" />
    </div>

    <div v-else>
      <!-- Stats Grid -->
      <div class="dashboard-grid">
        <div class="stat-card card">
          <div class="stat-icon" style="background: rgba(0, 124, 237, 0.1); color: #007ced;">📋</div>
          <div class="stat-info">
            <span class="stat-val">{{ stats.metrics.total_forms }}</span>
            <span class="stat-lbl">إجمالي الاستمارات</span>
            <div style="font-size:0.75rem; color:var(--text-3); margin-top:4px">
              منها <b style="color:var(--success)">{{ stats.metrics.open_forms }}</b> مفتوحة الآن
            </div>
          </div>
        </div>
        
        <div class="stat-card card">
          <div class="stat-icon" style="background: rgba(124, 58, 237, 0.1); color: #7c3aed;">👥</div>
          <div class="stat-info">
            <span class="stat-val">{{ stats.metrics.total_students }}</span>
            <span class="stat-lbl">الطلاب المسجلين</span>
          </div>
        </div>

        <div class="stat-card card">
          <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">✅</div>
          <div class="stat-info">
            <span class="stat-val">{{ stats.metrics.total_present }}</span>
            <span class="stat-lbl">المسجلين حضورهم</span>
            <div style="font-size:0.75rem; color:var(--text-3); margin-top:4px">
              بنسبة حضور عامة <b style="color:var(--primary)">{{ attendanceRate }}%</b>
            </div>
          </div>
        </div>

        <div class="stat-card card">
          <div class="stat-icon" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">🏛️</div>
          <div class="stat-info">
            <span class="stat-val">{{ stats.metrics.departments_count }}</span>
            <span class="stat-lbl">الأقسام العلمية</span>
          </div>
        </div>
      </div>

      <!-- Recent Registrations -->
      <div class="card" style="margin-top: 2rem;">
        <div style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
          <h3 style="font-size: 1.1rem; color: var(--text-1);">أحدث التسجيلات</h3>
          <router-link :to="{ name: 'AdminForms' }" class="btn btn-ghost" style="font-size: 0.85rem; padding: 0.3rem 0.6rem;">
            عرض الاستمارات ↗
          </router-link>
        </div>

        <div style="overflow-x:auto; -webkit-overflow-scrolling:touch;">
        <table style="width:100%; text-align:right; border-collapse:collapse; min-width:500px" v-if="stats.recent_students.length > 0">
          <thead>
            <tr style="border-bottom: 1px solid var(--border); color: var(--text-3); font-size: 0.85rem;">
              <th style="padding: 0.5rem">الاسم</th>
              <th style="padding: 0.5rem">القسم / المرحلة</th>
              <th style="padding: 0.5rem">الاستمارة</th>
              <th style="padding: 0.5rem">تاريخ التسجيل</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="s in stats.recent_students" :key="s.id" style="border-bottom: 1px solid rgba(255,255,255,0.03);">
              <td style="padding: 0.8rem 0.5rem; font-weight: 600; color: var(--text-1);">{{ s.name }}</td>
              <td style="padding: 0.8rem 0.5rem; color: var(--text-2); font-size: 0.9rem;">
                {{ s.department }} <span style="opacity:0.5">-</span> م{{ s.stage }}
              </td>
              <td style="padding: 0.8rem 0.5rem; color: var(--text-2); font-size: 0.9rem;">{{ s.form_title }}</td>
              <td style="padding: 0.8rem 0.5rem; color: var(--text-3); font-size: 0.85rem; font-family: monospace;" dir="ltr">{{ s.registered_at }}</td>
            </tr>
          </tbody>
        </table>
        
        <div v-else style="text-align: center; padding: 2rem; color: var(--text-3);">
          لا توجد أي تسجيلات حتى الآن.
        </div>
        </div><!-- end overflow wrapper -->
      </div>
      
      <!-- Charts Section -->
      <div class="dashboard-grid" style="margin-top:2rem">
        <div class="card" style="padding: 1.5rem">
          <h3 style="margin-bottom:1rem;font-size:1.1rem">تسجيلات آخر 7 أيام</h3>
          <div style="position:relative; height:250px">
            <Line v-if="chartData" :data="chartData.registrations" :options="chartOptions.line" />
            <div v-else class="spinner-container"><div class="spinner"></div></div>
          </div>
        </div>
        
        <div class="card" style="padding: 1.5rem">
          <h3 style="margin-bottom:1rem;font-size:1.1rem">مقارنة الحضور</h3>
          <div style="position:relative; height:250px; display:flex; justify-content:center">
            <Doughnut v-if="chartData" :data="chartData.attendance" :options="chartOptions.pie" />
            <div v-else class="spinner-container"><div class="spinner"></div></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { getOverviewStats, getChartStats } from '../../stores/api.js'
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  LineElement,
  PointElement,
  CategoryScale,
  LinearScale,
  ArcElement,
  BarElement
} from 'chart.js'
import { Line, Doughnut } from 'vue-chartjs'

ChartJS.register(
  Title,
  Tooltip,
  Legend,
  LineElement,
  PointElement,
  CategoryScale,
  LinearScale,
  ArcElement,
  BarElement
)

const loading = ref(true)
const stats = ref(null)
const chartData = ref(null)

const isDark = computed(() => document.documentElement.classList.contains('dark'))

// Chart options ...
const chartOptions = computed(() => {
  const textColor = isDark.value ? '#cbd5e1' : '#475569'
  const gridColor = isDark.value ? '#334155' : '#e2e8f0'
  
  return {
    line: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: { beginAtZero: true, ticks: { stepSize: 1, color: textColor }, grid: { color: gridColor } },
        x: { ticks: { color: textColor }, grid: { color: gridColor } }
      },
      plugins: { legend: { display: false } }
    },
    pie: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { 
        legend: { position: 'bottom', labels: { color: textColor, font: { family: 'Tajawal' } } } 
      }
    }
  }
})

const attendanceRate = computed(() => {
  if (!stats.value || stats.value.metrics.total_students === 0) return 0
  return Math.round((stats.value.metrics.total_present / stats.value.metrics.total_students) * 100)
})

async function fetchStats() {
  try {
    const [overviewRes, chartsRes] = await Promise.all([
      getOverviewStats(),
      getChartStats()
    ])
    
    stats.value = overviewRes.data
    const chartResData = chartsRes.data
    
    chartData.value = {
      registrations: {
        labels: chartResData.registrationsChart.labels,
        datasets: [{
          label: 'تسجيلات',
          data: chartResData.registrationsChart.data,
          borderColor: '#007ced',
          backgroundColor: 'rgba(0, 124, 237, 0.1)',
          tension: 0.3,
          fill: true
        }]
      },
      attendance: {
        labels: ['حاضر', 'غائب'],
        datasets: [{
          data: [chartResData.attendanceChart.present, chartResData.attendanceChart.absent],
          backgroundColor: ['#10b981', '#ef4444'],
          borderWidth: 0
        }]
      }
    }
  } catch (err) {
    console.error("Failed to load dashboard stats", err)
  }
}

onMounted(async () => {
  if (localStorage.getItem('admin_role') !== 'admin') {
    window.location.href = '/admin/forms'
    return
  }
  
  await fetchStats()
  loading.value = false

  // Setup WebSockets Listeners
  if (window.Echo) {
    window.Echo.channel('dashboard')
      .listen('StudentRegistered', (e) => {
        // Just refetch for simplicity to update numbers & charts & recent students list
        fetchStats()
      })
      .listen('StudentAttended', (e) => {
        fetchStats()
      })
  }
})
</script>

<style scoped>
.dashboard-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(min(240px, 100%), 1fr));
  gap: 1.5rem;
}

@media (max-width: 640px) {
  .dashboard-grid { grid-template-columns: repeat(2, 1fr); gap: 1rem; }
  .stat-card { padding: 1rem; gap: 0.75rem; }
  .stat-val { font-size: 1.4rem; }
  .stat-icon { width: 44px; height: 44px; font-size: 1.2rem; border-radius: 12px; }
}

.stat-card {
  display: flex;
  align-items: center;
  padding: 1.5rem;
  gap: 1.25rem;
  background: var(--bg-card);
}

.stat-icon {
  width: 56px;
  height: 56px;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  flex-shrink: 0;
}

.stat-info {
  display: flex;
  flex-direction: column;
}

.stat-val {
  font-size: 1.8rem;
  font-weight: 800;
  color: var(--text-1);
  line-height: 1.1;
}

.stat-lbl {
  font-size: 0.85rem;
  color: var(--text-2);
  margin-top: 2px;
  font-weight: 500;
}
</style>
