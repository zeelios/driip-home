<template>
  <div class="sp">

    <!-- Hero -->
    <div class="sp__hero">
      <div class="sp__hero-inner">
        <p class="sp__eyebrow">driip- / Support</p>
        <h1 class="sp__title">HỖ TRỢ<br /><span class="sp__title-sub">KHÁCH HÀNG</span></h1>
        <p class="sp__sub">Liên hệ nếu cần hỗ trợ về đơn hàng, sản phẩm hoặc bất kỳ vấn đề gì.</p>
      </div>
    </div>

    <div class="sp__body">
      <!-- Quick links -->
      <div class="sp__quick">
        <NuxtLink to="/orders/track" class="sp__quick-item">
          <svg class="sp__quick-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
          </svg>
          <div>
            <p class="sp__quick-title">Tra cứu đơn hàng</p>
            <p class="sp__quick-desc">Xem trạng thái giao hàng ngay →</p>
          </div>
        </NuxtLink>
        <a href="https://m.me/driip" target="_blank" rel="noopener" class="sp__quick-item">
          <svg class="sp__quick-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
              d="M8 12h.01M12 12h.01M16 12h.01M21 3H3v13h5l3 4 3-4h7V3z" />
          </svg>
          <div>
            <p class="sp__quick-title">Chat Messenger</p>
            <p class="sp__quick-desc">Phản hồi nhanh trong giờ hành chính →</p>
          </div>
        </a>
      </div>

      <!-- Divider -->
      <div class="sp__or">
        <div class="sp__or-line" />
        <span class="sp__or-text">Hoặc gửi yêu cầu</span>
        <div class="sp__or-line" />
      </div>

      <!-- Form -->
      <div class="sp__form-wrap">
        <!-- Success state -->
        <div v-if="sent" class="sp__success">
          <div class="sp__success-check">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
          </div>
          <h2 class="sp__success-title">Đã gửi thành công</h2>
          <p class="sp__success-body">Chúng tôi sẽ liên hệ lại qua email trong vòng 24 giờ làm việc.</p>
          <ZButton variant="ghost" @click="sent = false">Gửi yêu cầu khác</ZButton>
        </div>

        <form v-else @submit.prevent="submit" class="sp__form">
          <div class="sp__form-row">
            <ZInput v-model="form.name"  label="Họ tên"  placeholder="Nguyễn Văn A"         required />
            <ZInput v-model="form.email" label="Email"   type="email" placeholder="hello@example.com" required />
          </div>

          <ZInput v-model="form.phone"   label="Số điện thoại" type="tel" placeholder="0901 234 567" :optional="true" />

          <!-- Subject as select-like radio group -->
          <div class="sp__subject-group">
            <p class="sp__subject-label">Chủ đề</p>
            <div class="sp__subjects">
              <button
                v-for="s in subjects"
                :key="s.value"
                type="button"
                class="sp__subject-btn"
                :class="{ 'sp__subject-btn--active': form.subject === s.value }"
                @click="form.subject = s.value">
                {{ s.label }}
              </button>
            </div>
          </div>

          <ZInput
            v-model="form.body"
            type="textarea"
            label="Nội dung"
            placeholder="Mô tả chi tiết vấn đề bạn gặp phải…"
            :rows="5"
            required />

          <Transition name="z-msg">
            <p v-if="error" class="sp__error">{{ error }}</p>
          </Transition>

          <ZButton type="submit" :loading="loading" block size="lg">Gửi yêu cầu hỗ trợ</ZButton>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const loading = ref(false)
const error = ref<string | null>(null)
const sent = ref(false)

const subjects = [
  { value: 'order',   label: 'Vấn đề đơn hàng' },
  { value: 'product', label: 'Sản phẩm' },
  { value: 'payment', label: 'Thanh toán' },
  { value: 'other',   label: 'Khác' },
]

const form = reactive({
  name: '', email: '', phone: '',
  subject: subjects[0].value,
  body: '',
})

async function submit () {
  loading.value = true
  error.value = null
  try {
    const { driipFetch } = useDriipApi()
    await driipFetch('/public/support', { method: 'POST', body: form })
    sent.value = true
    Object.assign(form, { name: '', email: '', phone: '', subject: subjects[0].value, body: '' })
  } catch (err: any) {
    error.value = err?.data?.message || 'Gửi thất bại. Vui lòng thử lại.'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.sp { min-height: 90dvh; }

/* ── Hero ────────────────────────────────────────────────────────────── */
.sp__hero {
  padding: 4rem 1.5rem 2.5rem;
  border-bottom: 1px solid var(--border);
  background-color: var(--bg-raised);
}
.sp__hero-inner { max-width: 64rem; margin: 0 auto; }

.sp__eyebrow {
  font-size: 0.65rem; font-weight: 600; letter-spacing: 0.25em;
  text-transform: uppercase; color: var(--text-mute); margin-bottom: 0.75rem;
}
.sp__title {
  font-family: "Barlow Condensed", sans-serif;
  font-weight: 700; font-size: clamp(3rem, 10vw, 6.5rem);
  line-height: 0.9; text-transform: uppercase;
  color: var(--text); margin-bottom: 1rem;
}
.sp__title-sub { color: var(--text-sub); }
.sp__sub { font-size: 0.875rem; color: var(--text-sub); max-width: 28rem; }

/* ── Body ────────────────────────────────────────────────────────────── */
.sp__body { max-width: 64rem; margin: 0 auto; padding: 2.5rem 1.5rem 5rem; }

/* Quick links */
.sp__quick { display: grid; grid-template-columns: 1fr; gap: 0.75rem; margin-bottom: 2rem; }
@media (min-width: 640px) { .sp__quick { grid-template-columns: repeat(2, 1fr); } }

.sp__quick-item {
  display: flex; align-items: flex-start; gap: 0.875rem;
  padding: 1.25rem;
  background-color: var(--bg-card); border: 1px solid var(--border);
  border-radius: 0.875rem; text-decoration: none; color: inherit;
  transition: border-color 0.2s ease;
}
.sp__quick-item:hover { border-color: var(--border-hi); }
.sp__quick-icon { width: 1.25rem; height: 1.25rem; color: var(--text-mute); flex-shrink: 0; margin-top: 0.15rem; }
.sp__quick-title { font-size: 0.875rem; font-weight: 600; color: var(--text); margin-bottom: 0.2rem; }
.sp__quick-desc  { font-size: 0.75rem; color: var(--text-mute); }

/* Divider */
.sp__or {
  display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem;
  font-size: 0.72rem; color: var(--text-mute);
}
.sp__or-line { flex: 1; height: 1px; background-color: var(--border); }

/* Form wrapper */
.sp__form-wrap { max-width: 42rem; }

/* Success */
.sp__success {
  display: flex; flex-direction: column; align-items: center;
  text-align: center; padding: 3rem 0; gap: 1rem;
}
.sp__success-check {
  width: 4rem; height: 4rem; border-radius: 999px;
  background: rgba(34,197,94,0.1); border: 1px solid rgba(34,197,94,0.3);
  display: flex; align-items: center; justify-content: center;
}
.sp__success-check svg { width: 1.5rem; height: 1.5rem; color: #22c55e; }
.sp__success-title { font-family: "Barlow Condensed", sans-serif; font-size: 1.5rem; font-weight: 700; text-transform: uppercase; color: var(--text); }
.sp__success-body  { font-size: 0.875rem; color: var(--text-sub); max-width: 22rem; line-height: 1.6; }

/* Form */
.sp__form { display: flex; flex-direction: column; gap: 1rem; }
.sp__form-row { display: grid; grid-template-columns: 1fr; gap: 1rem; }
@media (min-width: 480px) { .sp__form-row { grid-template-columns: 1fr 1fr; } }

/* Subject radio group */
.sp__subject-group { display: flex; flex-direction: column; gap: 0.5rem; }
.sp__subject-label {
  font-size: 0.7rem; font-weight: 600; letter-spacing: 0.1em;
  text-transform: uppercase; color: var(--text-mute);
}
.sp__subjects { display: flex; flex-wrap: wrap; gap: 0.5rem; }
.sp__subject-btn {
  padding: 0.4rem 0.875rem;
  background: transparent; border: 1px solid var(--border-hi);
  border-radius: 0.375rem; cursor: pointer;
  font-family: "Be Vietnam Pro", sans-serif;
  font-size: 0.8rem; color: var(--text-sub);
  transition: all 0.15s ease;
}
.sp__subject-btn:hover { border-color: var(--border-focus); color: var(--text); }
.sp__subject-btn--active {
  background-color: var(--accent); color: var(--accent-fg);
  border-color: var(--accent); font-weight: 600;
}

.sp__error {
  font-size: 0.75rem; color: #ef4444;
  padding: 0.5rem 0.75rem;
  background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.2);
  border-radius: 0.5rem;
}

.z-msg-enter-active, .z-msg-leave-active { transition: opacity 0.2s ease, transform 0.15s ease; }
.z-msg-enter-from, .z-msg-leave-to { opacity: 0; transform: translateY(-3px); }
</style>
