<template>
  <div class="max-w-xl mx-auto mt-12 p-6">
    <h1 class="text-xl font-bold mb-6">{{ $t('support') }}</h1>
    <form @submit.prevent="submit" class="space-y-4">
      <div>
        <label class="block text-sm text-zinc-400 mb-1">Name</label>
        <input v-model="form.name" required class="w-full px-3 py-2 bg-zinc-900 border border-zinc-700 rounded text-sm" />
      </div>
      <div>
        <label class="block text-sm text-zinc-400 mb-1">Email</label>
        <input v-model="form.email" type="email" required class="w-full px-3 py-2 bg-zinc-900 border border-zinc-700 rounded text-sm" />
      </div>
      <div>
        <label class="block text-sm text-zinc-400 mb-1">Phone</label>
        <input v-model="form.phone" class="w-full px-3 py-2 bg-zinc-900 border border-zinc-700 rounded text-sm" />
      </div>
      <div>
        <label class="block text-sm text-zinc-400 mb-1">Subject</label>
        <input v-model="form.subject" required class="w-full px-3 py-2 bg-zinc-900 border border-zinc-700 rounded text-sm" />
      </div>
      <div>
        <label class="block text-sm text-zinc-400 mb-1">Message</label>
        <textarea v-model="form.body" required rows="4" class="w-full px-3 py-2 bg-zinc-900 border border-zinc-700 rounded text-sm"></textarea>
      </div>
      <p v-if="error" class="text-red-400 text-sm">{{ error }}</p>
      <p v-if="sent" class="text-green-400 text-sm">Message sent successfully.</p>
      <button type="submit" :disabled="loading" class="w-full py-2 bg-white text-black rounded font-medium disabled:opacity-50">
        {{ loading ? '...' : $t('submit') }}
      </button>
    </form>
  </div>
</template>

<script setup lang="ts">
const loading = ref(false)
const error = ref<string | null>(null)
const sent = ref(false)

const form = reactive({
  name: '',
  email: '',
  phone: '',
  subject: '',
  body: '',
})

async function submit () {
  loading.value = true
  error.value = null
  sent.value = false
  try {
    const { driipFetch } = useDriipApi()
    await driipFetch('/public/support', {
      method: 'POST',
      body: form,
    })
    sent.value = true
    form.name = ''
    form.email = ''
    form.phone = ''
    form.subject = ''
    form.body = ''
  } catch (err: any) {
    error.value = err?.data?.message || 'Failed to send message'
  } finally {
    loading.value = false
  }
}
</script>
