<template>
  <div class="max-w-xl mx-auto mt-12 p-6">
    <h1 class="text-xl font-bold mb-6">{{ $t('checkout') }}</h1>

    <!-- Cart summary -->
    <div class="mb-6 border border-zinc-800 rounded p-4">
      <h2 class="font-medium mb-2">Cart ({{ cart.totalItems }})</h2>
      <ul class="space-y-2 text-sm text-zinc-400">
        <li v-for="item in cart.items" :key="item.productId + (item.size || '')">
          {{ item.name }} — {{ item.quantity }} × {{ (item.priceCents / 100).toLocaleString('vi-VN') }}₫
        </li>
      </ul>
      <div class="mt-3 font-medium">Total: {{ cart.totalFormatted }}</div>
    </div>

    <form @submit.prevent="submit" class="space-y-4">
      <div v-if="!auth.isAuthenticated">
        <label class="block text-sm text-zinc-400 mb-1">Full name</label>
        <input v-model="guestName" required class="w-full px-3 py-2 bg-zinc-900 border border-zinc-700 rounded text-sm" />
        <label class="block text-sm text-zinc-400 mb-1 mt-3">Email</label>
        <input v-model="guestEmail" type="email" required class="w-full px-3 py-2 bg-zinc-900 border border-zinc-700 rounded text-sm" />
      </div>

      <div>
        <label class="block text-sm text-zinc-400 mb-1">Shipping address</label>
        <input v-model="address.street" placeholder="Street" required class="w-full px-3 py-2 bg-zinc-900 border border-zinc-700 rounded text-sm mb-2" />
        <input v-model="address.ward" placeholder="Ward" required class="w-full px-3 py-2 bg-zinc-900 border border-zinc-700 rounded text-sm mb-2" />
        <input v-model="address.district" placeholder="District" required class="w-full px-3 py-2 bg-zinc-900 border border-zinc-700 rounded text-sm mb-2" />
        <input v-model="address.city" placeholder="City" required class="w-full px-3 py-2 bg-zinc-900 border border-zinc-700 rounded text-sm mb-2" />
      </div>

      <p v-if="order.error" class="text-red-400 text-sm">{{ order.error }}</p>
      <p v-if="successToken" class="text-green-400 text-sm">
        Order placed! Token: {{ successToken }}
      </p>

      <button type="submit" :disabled="order.loading || cart.items.length === 0" class="w-full py-2 bg-white text-black rounded font-medium disabled:opacity-50">
        {{ order.loading ? 'Placing order...' : 'Place order' }}
      </button>
    </form>
  </div>
</template>

<script setup lang="ts">
const cart = useCartStore()
const auth = useAuthStore()
const order = useOrderStore()
const guestName = ref('')
const guestEmail = ref('')
const address = reactive({ street: '', ward: '', district: '', city: '' })
const successToken = ref<string | null>(null)

async function submit () {
  const payload = {
    shipping_address: {
      name: auth.isAuthenticated ? auth.customer!.name : guestName.value,
      phone: auth.customer?.phone || '',
      ...address,
    },
    payment_method: 'cod' as const,
    items: cart.toOrderItems(),
  }

  if (auth.isAuthenticated) {
    await order.createAuthOrder(payload)
    cart.clear()
    await navigateTo('/account/orders')
  } else {
    const res = await order.createGuestOrder({
      ...payload,
      name: guestName.value,
      email: guestEmail.value,
    })
    cart.clear()
    successToken.value = res.public_token
    const { setToken } = useGuestOrder()
    setToken(res.public_token)
  }
}
</script>
