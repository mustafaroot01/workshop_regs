<template>
  <div>
    <div class="page-header">
      <div style="display:flex;align-items:center;gap:1rem">
        <router-link :to="{ name: 'AdminForms' }" class="btn btn-ghost" style="padding:0.4rem 0.8rem">← رجوع</router-link>
        <div>
          <h2 class="gradient-text">تسجيل الحضور</h2>
          <p style="color:var(--text-3);font-size:0.85rem;margin-top:0.2rem">امسح رمز QR الخاص بالطالب</p>
        </div>
      </div>
    </div>

    <div class="scanner-grid">
      <!-- Camera Card -->
      <div class="card scanner-card">
        <div class="camera-wrap" ref="cameraWrap">
          <video ref="videoEl" autoplay playsinline muted class="camera-video" />
          <canvas ref="canvasEl" style="display:none" />
          <!-- Scan Frame -->
          <div class="scan-frame">
            <div class="scan-corner tl" /><div class="scan-corner tr" />
            <div class="scan-corner bl" /><div class="scan-corner br" />
            <div class="scan-line" />
          </div>
        </div>

        <div style="padding:1rem;text-align:center">
          <div v-if="cameras.length > 1" style="margin-bottom:1rem">
            <select v-model="selectedCamera" class="form-select" style="max-width:300px;margin:auto">
              <option v-for="c in cameras" :key="c.deviceId" :value="c.deviceId">{{ c.label || 'كاميرا ' + (cameras.indexOf(c) + 1) }}</option>
            </select>
          </div>
          <button v-if="!cameraOn" class="btn btn-primary" @click="startCamera" style="padding:0.85rem 2rem">
            📷 تشغيل الكاميرا
          </button>
          <button v-else class="btn btn-danger" @click="stopCamera" style="padding:0.85rem 2rem">
            ⏹ إيقاف الكاميرا
          </button>
          <p style="color:var(--text-3);font-size:0.82rem;margin-top:0.75rem">
            {{ cameraOn ? 'وجّه الكاميرا حتى يكون الرمز داخل المربع لسرعة القراءة' : 'اضغط لتشغيل الكاميرا وبدء المسح' }}
          </p>
        </div>
      </div>

      <!-- Result & Stats Card -->
      <div style="display:flex;flex-direction:column;gap:1rem">

        <!-- Last Scan Result -->
        <div class="card result-card" v-if="lastResult">
          <div class="result-icon" :class="lastResult.type">
            {{ lastResult.type === 'success' ? '✅' : lastResult.type === 'warn' ? '⚠️' : '❌' }}
          </div>
          <h3 class="result-name">{{ lastResult.name }}</h3>
          <p class="result-msg">{{ lastResult.message }}</p>
          <div class="result-details" v-if="lastResult.student">
            <div class="detail-row">
              <span class="detail-lbl">القسم</span>
              <span class="detail-val">{{ lastResult.student.department }}</span>
            </div>
            <div class="detail-row">
              <span class="detail-lbl">المرحلة</span>
              <span class="detail-val">{{ lastResult.student.stage }}</span>
            </div>
            <div class="detail-row">
              <span class="detail-lbl">الورشة</span>
              <span class="detail-val">{{ lastResult.student.form_title }}</span>
            </div>
          </div>
        </div>
        <div v-else class="card result-card result-empty">
          <div style="font-size:3rem">📡</div>
          <p style="color:var(--text-3);margin-top:0.75rem">في انتظار مسح رمز QR...</p>
        </div>

        <!-- Attendance Counter -->
        <div class="card" style="padding:1.2rem">
          <h3 style="color:var(--text-2);font-size:0.85rem;text-transform:uppercase;letter-spacing:0.08em">إحصائيات الجلسة</h3>
          <div class="counter-grid">
            <div class="counter-item success">
              <div class="counter-num">{{ sessionCounts.present }}</div>
              <div class="counter-lbl">تم تسجيل حضورهم</div>
            </div>
            <div class="counter-item warn">
              <div class="counter-num">{{ sessionCounts.already }}</div>
              <div class="counter-lbl">مسجل مسبقاً</div>
            </div>
            <div class="counter-item error">
              <div class="counter-num">{{ sessionCounts.invalid }}</div>
              <div class="counter-lbl">رمز غير صالح</div>
            </div>
          </div>
        </div>

        <!-- Recent Scans -->
        <div class="card" style="padding:1.2rem" v-if="recentScans.length">
          <h3 style="color:var(--text-2);font-size:0.85rem;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:0.75rem">آخر المسحات</h3>
          <div class="recent-list">
            <div v-for="(s, i) in recentScans" :key="i" class="recent-item" :class="s.type">
              <span class="ri-icon">{{ s.type === 'success' ? '✅' : s.type === 'warn' ? '⚠️' : '❌' }}</span>
              <span class="ri-name">{{ s.name }}</span>
              <span class="ri-time">{{ s.time }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'
import { scanAttendance } from '../../stores/api.js'

const route = useRoute()
const videoEl = ref(null)
const canvasEl = ref(null)
const cameraOn = ref(false)
const lastResult = ref(null)
const recentScans = ref([])
const sessionCounts = ref({ present: 0, already: 0, invalid: 0 })

const cameras = ref([])
const selectedCamera = ref('')

let stream = null
let scanInterval = null
let processing = false
let lastToken = ''

// Setup Audio
const audioCtx = new (window.AudioContext || window.webkitAudioContext)()
function playSound(type) {
  if (audioCtx.state === 'suspended') audioCtx.resume()
  const osc = audioCtx.createOscillator()
  const gain = audioCtx.createGain()
  osc.connect(gain)
  gain.connect(audioCtx.destination)
  
  if (type === 'success') {
    osc.type = 'sine'
    osc.frequency.setValueAtTime(800, audioCtx.currentTime)
    osc.frequency.exponentialRampToValueAtTime(1200, audioCtx.currentTime + 0.1)
    gain.gain.setValueAtTime(0.5, audioCtx.currentTime)
    gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.1)
    osc.start(); osc.stop(audioCtx.currentTime + 0.1)
  } else if (type === 'warn') {
    osc.type = 'triangle'
    osc.frequency.setValueAtTime(300, audioCtx.currentTime)
    osc.frequency.setValueAtTime(400, audioCtx.currentTime + 0.1)
    gain.gain.setValueAtTime(0.5, audioCtx.currentTime)
    gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.2)
    osc.start(); osc.stop(audioCtx.currentTime + 0.2)
  } else {
    osc.type = 'sawtooth'
    osc.frequency.setValueAtTime(150, audioCtx.currentTime)
    gain.gain.setValueAtTime(0.5, audioCtx.currentTime)
    gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.3)
    osc.start(); osc.stop(audioCtx.currentTime + 0.3)
  }
}

async function getCameras() {
  try {
    const devices = await navigator.mediaDevices.enumerateDevices()
    cameras.value = devices.filter(d => d.kind === 'videoinput')
    const backCam = cameras.value.find(c => c.label.toLowerCase().includes('back') || c.label.toLowerCase().includes('environment'))
    if (backCam) selectedCamera.value = backCam.deviceId
    else if (cameras.value.length) selectedCamera.value = cameras.value[0].deviceId
  } catch (e) {}
}

onMounted(() => { getCameras() })

watch(selectedCamera, (newVal) => {
  if (cameraOn.value && newVal) {
    stopCamera()
    startCamera()
  }
})

async function startCamera() {
  try {
    const constraints = {
      video: selectedCamera.value 
        ? { deviceId: { exact: selectedCamera.value }, width: { ideal: 1280 }, height: { ideal: 720 } }
        : { facingMode: 'environment', width: { ideal: 1280 }, height: { ideal: 720 } }
    }
    stream = await navigator.mediaDevices.getUserMedia(constraints)
    videoEl.value.srcObject = stream
    cameraOn.value = true
    scanInterval = setInterval(scanFrame, 250) // Reduced interval to 250ms for faster response
  } catch {
    alert('تعذر الوصول إلى الكاميرا. تأكد من إعطاء الصلاحيات.')
  }
}

function stopCamera() {
  clearInterval(scanInterval)
  if (stream) stream.getTracks().forEach(t => t.stop())
  cameraOn.value = false
}

async function scanFrame() {
  if (processing || videoEl.value?.readyState !== 4) return
  const video = videoEl.value
  const canvas = canvasEl.value
  
  // Fast Scan Area: Only process the center of the video (60% square)
  const minDim = Math.min(video.videoWidth, video.videoHeight)
  const boxSize = Math.floor(minDim * 0.6)
  const sx = (video.videoWidth - boxSize) / 2
  const sy = (video.videoHeight - boxSize) / 2
  
  canvas.width = boxSize
  canvas.height = boxSize
  const ctx = canvas.getContext('2d')
  
  // Draw only the cropped box
  ctx.drawImage(video, sx, sy, boxSize, boxSize, 0, 0, boxSize, boxSize)
  
  try {
    const imageData = ctx.getImageData(0, 0, boxSize, boxSize)
    const jsQR = (await import('jsqr')).default
    // dontInvert saves CPU time if the QR is dark on light
    const code = jsQR(imageData.data, boxSize, boxSize, { inversionAttempts: 'dontInvert' })
    
    if (code && code.data && code.data !== lastToken) {
      lastToken = code.data
      await processQr(code.data)
      // 4-second debounce to prevent multiple scans of the same token
      setTimeout(() => { if (lastToken === code.data) lastToken = '' }, 4000)
    }
  } catch {}
}

async function processQr(token) {
  processing = true
  try {
    const { data } = await scanAttendance(token)
    const type = data.already_attended ? 'warn' : 'success'
    if (!data.already_attended) sessionCounts.value.present++
    else sessionCounts.value.already++
    lastResult.value = { type, name: data.student.name, message: data.message, student: data.student }
    addRecent(type, data.student.name)
    playSound(type)
  } catch (err) {
    sessionCounts.value.invalid++
    lastResult.value = { type: 'error', name: '—', message: err.response?.data?.message || 'رمز غير صالح' }
    addRecent('error', '—')
    playSound('error')
  } finally {
    processing = false
  }
}

function addRecent(type, name) {
  const now = new Date().toLocaleTimeString('ar')
  recentScans.value.unshift({ type, name, time: now })
  if (recentScans.value.length > 6) recentScans.value.pop()
}

onUnmounted(() => { stopCamera() })
</script>

<style scoped>
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; }

.scanner-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.2rem;
  align-items: start;
}
@media (max-width: 900px) {
  .scanner-grid { grid-template-columns: 1fr; }
}

.scanner-card { overflow: hidden; padding: 0; }
.camera-wrap {
  position: relative;
  aspect-ratio: 4/3;
  background: #000;
  border-radius: var(--radius-lg) var(--radius-lg) 0 0;
  overflow: hidden;
}
.camera-video { width: 100%; height: 100%; object-fit: cover; }

.scan-frame {
  position: absolute;
  inset: 20%;
  pointer-events: none;
}
.scan-corner {
  position: absolute;
  width: 24px; height: 24px;
  border-color: var(--primary-light);
  border-style: solid;
}
.tl { top: 0; left: 0; border-width: 3px 0 0 3px; }
.tr { top: 0; right: 0; border-width: 3px 3px 0 0; }
.bl { bottom: 0; left: 0; border-width: 0 0 3px 3px; }
.br { bottom: 0; right: 0; border-width: 0 3px 3px 0; }
.scan-line {
  position: absolute;
  left: 0; right: 0;
  height: 2px;
  background: linear-gradient(to right, transparent, var(--primary-light), transparent);
  animation: scanMove 2s ease-in-out infinite;
}
@keyframes scanMove {
  0%, 100% { top: 0; }
  50% { top: 100%; }
}

.result-card { padding: 1.5rem; text-align: center; }
.result-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 160px; }
.result-icon { font-size: 2.5rem; margin-bottom: 0.75rem; }
.result-name { font-size: 1.2rem; font-weight: 700; color: var(--text-1); }
.result-msg { color: var(--text-2); font-size: 0.9rem; margin-top: 0.3rem; }
.result-details { margin-top: 1rem; border-top: 1px solid var(--border); padding-top: 1rem; text-align: right; display: flex; flex-direction: column; gap: 0.4rem; }
.detail-row { display: flex; justify-content: space-between; }
.detail-lbl { font-size: 0.82rem; color: var(--text-3); }
.detail-val { font-size: 0.85rem; color: var(--text-2); font-weight: 500; }

.counter-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.75rem; margin-top: 0.75rem; }
.counter-item { text-align: center; padding: 0.75rem 0.5rem; border-radius: var(--radius-sm); }
.counter-item.success { background: rgba(16,185,129,0.1); color: var(--success); }
.counter-item.warn { background: rgba(245,158,11,0.1); color: var(--accent); }
.counter-item.error { background: rgba(239,68,68,0.1); color: var(--error); }
.counter-num { font-size: 1.8rem; font-weight: 800; }
.counter-lbl { font-size: 0.72rem; margin-top: 2px; }

.recent-list { display: flex; flex-direction: column; gap: 6px; }
.recent-item { display: flex; align-items: center; gap: 8px; padding: 6px 10px; border-radius: var(--radius-sm); font-size: 0.85rem; }
.recent-item.success { background: rgba(16,185,129,0.07); }
.recent-item.warn { background: rgba(245,158,11,0.07); }
.recent-item.error { background: rgba(239,68,68,0.07); }
.ri-icon { flex-shrink: 0; }
.ri-name { flex: 1; color: var(--text-1); }
.ri-time { color: var(--text-3); font-size: 0.75rem; }
</style>
