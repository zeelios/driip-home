<template>
  <div class="max-w-sm mx-auto mt-12 p-6 border border-zinc-800 rounded-lg">
    <h1 class="text-xl font-bold mb-4">{{ $t('register') }}</h1>
    <form @submit.prevent="handleRegister">
      <div class="mb-4">
        <label class="block text-sm text-zinc-400 mb-1">{{ $t('name') }}</label>
        <input v-model="name" type="text" required class="w-full px-3 py-2 bg-zinc-900 border border-zinc-700 rounded text-sm" />
      </div>
      <div class="mb-4">
        <label class="block text-sm text-zinc-400 mb-1">{{ $t('email') }}</label>
        <input v-model="email" type="email" required class="w-full px-3 py-2 bg-zinc-900 border border-zinc-700 rounded text-sm" />
      </div>
      <div class="mb-4">
        <label class="block text-sm text-zinc-400 mb-1">{{ $t('phone') }}</label>
        <input v-model="phone" type="tel" class="w-full px-3 py-2 bg-zinc-900 border border-zinc-700 rounded text-sm" />
      </div>
      <div class="mb-4">
        <label class="block text-sm text-zinc-400 mb-1">{{ $t('password') }}</label>
        <input v-model="password" type="password" required minlength="8" class="w-full px-3 py-2 bg-zinc-900 border border-zinc-700 rounded text-sm" />
      </div>
      <p v-if="auth.error" class="text-red-400 text-sm mb-3">{{ auth.error }}</p>
      <button type="submit" :disabled="auth.loading" class="w-full py-2 bg-white text-black rounded font-medium disabled:opacity-50">
        {{ auth.loading ? '...' : $t('register') }}
      </button>
    </form>
    <div class="mt-4 text-sm text-center">
      <NuxtLink to="/account/login" class="text-zinc-400 hover:text-white">{{ $t('login') }}</NuxtLink>
    </div>
  </div>
</template>

<script setup lang="ts">
const auth = useAuthStore()
const name = ref('')
const email = ref('')
const phone = ref('')
const password = ref('')

async function handleRegister () {
  const ok = await auth.register({
    name: name.value,
    email: email.value,
    phone: phone.value || undefined,
    password: password.value,
  })
  if (ok) {
    await navigateTo('/account')
  }
}
</script>
