<template>
  <div class="max-w-xl mx-auto mt-12 p-6">
    <h1 class="text-xl font-bold mb-4">{{ $t('trackOrder') }}</h1>

    <div class="flex gap-2 mb-6">
      <input v-model="token" placeholder="Order token" class="flex-1 px-3 py-2 bg-zinc-900 border border-zinc-700 rounded text-sm" />
      <button @click="track" :disabled="order.loading" class="px-4 py-2 bg-white text-black rounded font-medium disabled:opacity-50">
        {{ order.loading ? '...' : 'Track' }}
      </button>
    </div>

    <p v-if="order.error" class="text-red-400 text-sm mb-3">{{ order.error }}</p>

    <div v-if="result" class="border border-zinc-800 rounded p-4 space-y-2 text-sm">
      <div><span class="text-zinc-500">Order ID:</span> {{ result.id }}</div>
      <div><span class="text-zinc-500">Status:</span> {{ result.status }}</div>
      <div><span class="text-zinc-500">Total:</span> {{ result.total_cents }}₫</div>
      <div><span class="text-zinc-500">Created:</span> {{ new Date(result.created_at).toLocaleString() }}</div>
    </div>
  </div>
</template>

<script setup lang="ts">
const order = useOrderStore()
const token = ref('')
const result = ref<any>(null)

async function track () {
  result.value = null
  try {
    result.value = await order.trackOrder(token.value)
  } catch {
    result.value = null
  }
}

onMounted(() => {
  const { getToken } = useGuestOrder()
  const t = getToken()
  if (t) {
    token.value = t
    track()
  }
})
</script>
