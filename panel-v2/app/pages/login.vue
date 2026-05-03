<template>
  <div class="login-card">
    <div class="login-head">
      <p class="login-eyebrow">Staff Access</p>
      <h1 class="login-title">Đăng nhập</h1>
    </div>

    <form @submit.prevent="submit" class="login-form">
      <PInput v-model="email"    type="email"    label="Email"     placeholder="staff@driip.com" required autocomplete="email" />
      <PInput v-model="password" type="password" label="Mật khẩu" placeholder="••••••••"        required autocomplete="current-password" />

      <Transition name="fade">
        <p v-if="auth.error" class="login-error">{{ auth.error }}</p>
      </Transition>

      <PBtn type="submit" :loading="auth.status === 'loading'" size="md" block>Đăng nhập</PBtn>
    </form>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ layout: 'auth' })

const auth     = useAuthStore()
const email    = ref('')
const password = ref('')

onMounted(() => { if (auth.isAuthenticated) navigateTo('/') })

async function submit () {
  const ok = await auth.login(email.value, password.value)
  if (ok) navigateTo('/')
}
</script>

<style scoped>
.login-card {
  width: 100%; max-width: 360px;
  background: var(--bg-card); border: 1px solid var(--border);
  border-radius: 0.875rem; padding: 2rem;
}
.login-head { margin-bottom: 1.5rem; }
.login-eyebrow {
  font-size: 0.65rem; font-weight: 600; letter-spacing: 0.15em;
  text-transform: uppercase; color: var(--text-mute); margin-bottom: 0.35rem;
}
.login-title {
  font-family: "Barlow Condensed", ui-sans-serif, sans-serif;
  font-size: 1.75rem; font-weight: 700; text-transform: uppercase;
  letter-spacing: 0.04em; color: var(--text);
}
.login-form { display: flex; flex-direction: column; gap: 0.875rem; }
.login-error {
  font-size: 0.75rem; color: #ef4444;
  padding: 0.5rem 0.75rem;
  background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.2);
  border-radius: 0.375rem;
}
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
