<template>
  <div class="page success-page">
    <div class="orb orb-1" />
    <div class="orb orb-2" />

    <Transition name="slide-up" appear>
      <div class="success-card card">
        <!-- Checkmark Animation -->
        <div class="check-wrap">
          <svg class="check-icon" viewBox="0 0 52 52" xmlns="http://www.w3.org/2000/svg">
            <circle class="check-circle" cx="26" cy="26" r="25" fill="none" />
            <path class="check-mark" fill="none" d="M14 27l8 8 16-16" />
          </svg>
        </div>

        <h1 class="gradient-text">تم التسجيل بنجاح! 🎉</h1>
        <p class="sub">مرحباً <strong>{{ name }}</strong>! لقد تم تسجيلك بنجاح.</p>
        <p class="sub-2">احتفظ بهذا الرمز، ستحتاجه لتسجيل حضورك في الورشة.</p>

        <!-- QR Code -->
        <div class="qr-section">
          <div class="qr-label">رمز الحضور الخاص بك</div>
          <div class="qr-box">
            <canvas ref="qrCanvas" />
          </div>
          <p class="qr-note">📌 خذ صورة لهذا الرمز أو احفظه</p>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import QRCode from 'qrcode'

const route = useRoute()
const qrCanvas = ref(null)
const name = route.query.name || 'الطالب'
const qrToken = route.query.qr || ''

onMounted(async () => {
  if (qrCanvas.value && qrToken) {
    await QRCode.toCanvas(qrCanvas.value, qrToken, {
      width: 220,
      margin: 2,
      color: {
        dark: '#1a1040',
        light: '#ffffff',
      },
      errorCorrectionLevel: 'H',
    })
  }
})
</script>

<style scoped>
.success-page {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  padding: 2rem 1rem;
  position: relative;
  overflow: hidden;
}

.orb {
  position: fixed;
  border-radius: 50%;
  filter: blur(80px);
  pointer-events: none;
  z-index: 0;
}
.orb-1 {
  width: 500px; height: 500px;
  background: radial-gradient(circle, rgba(16,185,129,0.2) 0%, transparent 70%);
  top: -150px; right: -150px;
}
.orb-2 {
  width: 400px; height: 400px;
  background: radial-gradient(circle, rgba(124,58,237,0.15) 0%, transparent 70%);
  bottom: -100px; left: -100px;
}

.success-card {
  position: relative;
  z-index: 1;
  max-width: 460px;
  width: 100%;
  padding: 2.5rem 2rem;
  text-align: center;
}

/* Animated Check */
.check-wrap {
  display: flex;
  justify-content: center;
  margin-bottom: 1.5rem;
}
.check-icon {
  width: 72px;
  height: 72px;
}
.check-circle {
  stroke: #10b981;
  stroke-width: 2;
  stroke-dasharray: 166;
  stroke-dashoffset: 166;
  animation: dash-circle 0.6s ease forwards;
}
.check-mark {
  stroke: #10b981;
  stroke-width: 3;
  stroke-linecap: round;
  stroke-linejoin: round;
  stroke-dasharray: 48;
  stroke-dashoffset: 48;
  animation: dash-mark 0.4s ease forwards 0.5s;
}
@keyframes dash-circle {
  to { stroke-dashoffset: 0; }
}
@keyframes dash-mark {
  to { stroke-dashoffset: 0; }
}

.sub {
  color: var(--text-2);
  margin-top: 0.5rem;
  font-size: 1rem;
}
.sub strong { color: var(--text-1); }
.sub-2 {
  color: var(--text-3);
  font-size: 0.88rem;
  margin-top: 0.4rem;
}

.qr-section {
  margin-top: 2rem;
}
.qr-label {
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--text-3);
  text-transform: uppercase;
  letter-spacing: 0.08em;
  margin-bottom: 1rem;
}
.qr-box {
  display: inline-flex;
  padding: 16px;
  background: white;
  border-radius: var(--radius-md);
  box-shadow: 0 0 40px rgba(124, 58, 237, 0.3);
}
.qr-note {
  margin-top: 1rem;
  font-size: 0.85rem;
  color: var(--text-3);
}
</style>
