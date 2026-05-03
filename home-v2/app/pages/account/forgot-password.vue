<template>
  <div>
    <NuxtLink to="/account/login" class="auth-back">
      <svg class="auth-back__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
      </svg>
      Quay lại đăng nhập
    </NuxtLink>

    <div class="auth-head">
      <p class="auth-eyebrow">Tài khoản</p>
      <h1 class="auth-title">Quên mật khẩu</h1>
      <p class="auth-desc">Nhập email, chúng tôi sẽ gửi link đặt lại mật khẩu.</p>
    </div>

    <div v-if="sent" class="auth-success">
      ✓ Email đã được gửi. Kiểm tra hộp thư đến (và spam) của bạn.
    </div>

    <form v-else @submit.prevent="submit" class="auth-form">
      <ZInput
        v-model="email"
        type="email"
        label="Email"
        placeholder="hello@example.com"
        autocomplete="email"
        required />

      <ZButton type="submit" :loading="loading" block size="lg">Gửi link đặt lại</ZButton>
    </form>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ layout: 'auth' })

const auth = useAuthStore()
const email = ref('')
const sent = ref(false)
const loading = ref(false)

async function submit () {
  loading.value = true
  await auth.forgotPassword(email.value)
  sent.value = true
  loading.value = false
}
</script>

<style scoped>
.auth-back {
  display: inline-flex; align-items: center; gap: 0.375rem;
  font-size: 0.75rem; color: var(--text-mute); text-decoration: none;
  margin-bottom: 1.5rem; transition: color 0.15s ease;
}
.auth-back:hover { color: var(--text-sub); }
.auth-back__icon { width: 0.875rem; height: 0.875rem; }

.auth-head    { margin-bottom: 2rem; }
.auth-eyebrow {
  font-size: 0.7rem; font-weight: 600; letter-spacing: 0.15em;
  text-transform: uppercase; color: var(--text-mute); margin-bottom: 0.4rem;
}
.auth-title {
  font-family: "Barlow Condensed", sans-serif;
  font-weight: 700; font-size: 2rem; text-transform: uppercase;
  letter-spacing: 0.05em; color: var(--text); margin-bottom: 0.5rem;
}
.auth-desc { font-size: 0.875rem; color: var(--text-sub); }

.auth-form { display: flex; flex-direction: column; gap: 1rem; }

.auth-success {
  padding: 0.75rem 1rem;
  background: rgba(34, 197, 94, 0.08);
  border: 1px solid rgba(34, 197, 94, 0.2);
  border-radius: 0.5rem;
  font-size: 0.875rem;
  color: #22c55e;
}
</style>
