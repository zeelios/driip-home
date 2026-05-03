<template>
  <div>
    <div class="auth-head">
      <p class="auth-eyebrow">Tài khoản</p>
      <h1 class="auth-title">Đăng ký</h1>
    </div>

    <form @submit.prevent="handleRegister" class="auth-form">
      <ZInput
        v-model="name"
        type="text"
        label="Họ tên"
        placeholder="Nguyễn Văn A"
        autocomplete="name"
        required />

      <ZInput
        v-model="email"
        type="email"
        label="Email"
        placeholder="hello@example.com"
        autocomplete="email"
        required />

      <ZInput
        v-model="phone"
        type="tel"
        label="Số điện thoại"
        placeholder="0901 234 567"
        autocomplete="tel"
        :optional="true" />

      <ZInput
        v-model="password"
        type="password"
        label="Mật khẩu"
        placeholder="Tối thiểu 8 ký tự"
        autocomplete="new-password"
        minlength="8"
        required />

      <Transition name="z-msg">
        <p v-if="auth.error" class="auth-error">{{ auth.error }}</p>
      </Transition>

      <ZButton type="submit" :loading="auth.loading" block size="lg">Tạo tài khoản</ZButton>
    </form>

    <div class="auth-divider"><span>hoặc</span></div>

    <p class="auth-switch">
      Đã có tài khoản?
      <NuxtLink to="/account/login" class="auth-switch__link">Đăng nhập</NuxtLink>
    </p>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ layout: 'auth' })

const auth = useAuthStore()
const name = ref('')
const email = ref('')
const phone = ref('')
const password = ref('')

onMounted(() => { if (auth.isAuthenticated) navigateTo('/account') })

async function handleRegister () {
  const ok = await auth.register({
    name: name.value, email: email.value,
    phone: phone.value || undefined, password: password.value,
  })
  if (ok) navigateTo('/account')
}
</script>

<style scoped>
.auth-head    { margin-bottom: 2rem; }
.auth-eyebrow {
  font-size: 0.7rem; font-weight: 600; letter-spacing: 0.15em;
  text-transform: uppercase; color: var(--text-mute); margin-bottom: 0.4rem;
}
.auth-title {
  font-family: "Barlow Condensed", sans-serif;
  font-weight: 700; font-size: 2rem; text-transform: uppercase;
  letter-spacing: 0.05em; color: var(--text);
}
.auth-form  { display: flex; flex-direction: column; gap: 1rem; }
.auth-error {
  font-size: 0.75rem; color: #ef4444;
  padding: 0.5rem 0.75rem;
  background: rgba(239,68,68,0.08);
  border: 1px solid rgba(239,68,68,0.2);
  border-radius: 0.5rem; margin: 0;
}
.auth-divider {
  display: flex; align-items: center; gap: 0.75rem;
  margin: 1.5rem 0; font-size: 0.75rem; color: var(--text-mute);
}
.auth-divider::before, .auth-divider::after {
  content: ''; flex: 1; height: 1px; background-color: var(--border);
}
.auth-switch { text-align: center; font-size: 0.875rem; color: var(--text-sub); }
.auth-switch__link {
  color: var(--text); font-weight: 600;
  text-decoration: underline; text-underline-offset: 4px;
  margin-left: 0.25rem; transition: color 0.15s ease;
}
.auth-switch__link:hover { color: var(--text-sub); }

.z-msg-enter-active, .z-msg-leave-active { transition: opacity 0.2s ease, transform 0.2s ease; }
.z-msg-enter-from, .z-msg-leave-to { opacity: 0; transform: translateY(-4px); }
</style>
