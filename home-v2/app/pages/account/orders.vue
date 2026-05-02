<template>
  <div class="max-w-xl mx-auto mt-12 p-6">
    <h1 class="text-xl font-bold mb-6">{{ $t('orders') }}</h1>
    <div v-if="order.loading">Loading...</div>
    <div v-else-if="orders.length === 0" class="text-zinc-500">No orders yet.</div>
    <ul v-else class="space-y-3">
      <li v-for="o in orders" :key="o.id" class="border border-zinc-800 rounded p-4">
        <div class="flex justify-between">
          <span class="font-medium">#{{ o.id.slice(0, 8) }}</span>
          <span class="text-sm text-zinc-400">{{ o.status }}</span>
        </div>
        <div class="text-sm text-zinc-400 mt-1">{{ new Date(o.created_at).toLocaleDateString() }}</div>
      </li>
    </ul>
  </div>
</template>

<script setup lang="ts">
const order = useOrderStore()
const orders = ref<any[]>([])

onMounted(async () => {
  try {
    orders.value = await order.listMyOrders()
  } catch {
    orders.value = []
  }
})
</script>
