<template>
  <div class="max-w-xl mx-auto mt-12 p-6">
    <h1 class="text-xl font-bold mb-6">Account</h1>
    <div v-if="auth.customer">
      <div class="mb-4">
        <label class="block text-sm text-zinc-400 mb-1">Name</label>
        <input v-model="form.name" class="w-full px-3 py-2 bg-zinc-900 border border-zinc-700 rounded text-sm" />
      </div>
      <div class="mb-4">
        <label class="block text-sm text-zinc-400 mb-1">Email</label>
        <input v-model="form.email" class="w-full px-3 py-2 bg-zinc-900 border border-zinc-700 rounded text-sm" />
      </div>
      <div class="mb-4">
        <label class="block text-sm text-zinc-400 mb-1">Phone</label>
        <input v-model="form.phone" class="w-full px-3 py-2 bg-zinc-900 border border-zinc-700 rounded text-sm" />
      </div>
      <p v-if="auth.error" class="text-red-400 text-sm mb-3">{{ auth.error }}</p>
      <div class="flex gap-3">
        <button @click="save" :disabled="auth.loading" class="px-4 py-2 bg-white text-black rounded font-medium disabled:opacity-50">
          {{ $t('save') }}
        </button>
        <NuxtLink to="/account/orders" class="px-4 py-2 border border-zinc-700 rounded font-medium">
          {{ $t('orders') }}
        </NuxtLink>
      </div>
      <button @click="auth.logout" class="mt-6 text-sm text-zinc-500 hover:text-zinc-300">
        {{ $t('logout') }}
      </button>
    </div>
    <div v-else>
      <p class="text-zinc-400 mb-4">Not logged in.</p>
      <NuxtLink to="/account/login" class="px-4 py-2 bg-white text-black rounded font-medium">{{ $t('login') }}</NuxtLink>
    </div>
  </div>
</template>

<script setup lang="ts">
const auth = useAuthStore()
const form = reactive({
  name: auth.customer?.name || '',
  email: auth.customer?.email || '',
  phone: auth.customer?.phone || '',
})

watch(() => auth.customer, (c) => {
  if (c) {
    form.name = c.name
    form.email = c.email
    form.phone = c.phone || ''
  }
})

onMounted(() => {
  auth.fetchMe()
})

async function save () {
  await auth.updateProfile({
    name: form.name,
    email: form.email,
    phone: form.phone || undefined,
  })
}
</script>
