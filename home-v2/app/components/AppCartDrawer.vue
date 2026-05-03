<template>
  <Teleport to="body">
    <Transition name="overlay">
      <div v-if="open" class="fixed inset-0 bg-black/60 z-40" @click="$emit('close')" />
    </Transition>

    <Transition name="drawer">
      <aside v-if="open" class="fixed right-0 top-0 h-full w-full max-w-sm t-bg border-l t-border z-50 flex flex-col">
        <!-- Header -->
        <div class="flex items-center justify-between px-5 py-4 border-b border-zinc-800">
          <span class="font-semibold tracking-widest text-xs uppercase t-text-sub">Giỏ hàng</span>
          <button @click="$emit('close')" class="t-text-sub hover:t-text transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Items -->
        <div class="flex-1 overflow-y-auto px-5 py-4 space-y-4">
          <div v-if="cart.items.length === 0" class="flex flex-col items-center justify-center h-full gap-3 text-zinc-500">
            <svg class="w-12 h-12 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 11H4L5 9z" />
            </svg>
            <span class="text-sm">Giỏ hàng trống</span>
          </div>

          <div v-for="item in cart.items" :key="item.productId + (item.size || '')" class="flex gap-3">
            <div class="w-16 h-20 bg-zinc-900 rounded overflow-hidden flex-shrink-0">
              <img v-if="item.imageUrl" :src="item.imageUrl" :alt="item.name" class="w-full h-full object-cover" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium truncate">{{ item.name }}</p>
              <p v-if="item.size" class="text-xs text-zinc-500 mt-0.5">{{ item.size }}</p>
              <p class="text-sm text-zinc-400 mt-1">{{ formatVND(item.priceCents) }}</p>
              <div class="flex items-center gap-2 mt-2">
                <button @click="cart.updateQuantity(item.productId, item.quantity - 1, item.size)"
                  class="w-6 h-6 flex items-center justify-center border border-zinc-700 rounded text-zinc-400 hover:text-white hover:border-zinc-500 transition-colors text-xs">−</button>
                <span class="text-sm w-4 text-center">{{ item.quantity }}</span>
                <button @click="cart.updateQuantity(item.productId, item.quantity + 1, item.size)"
                  class="w-6 h-6 flex items-center justify-center border border-zinc-700 rounded text-zinc-400 hover:text-white hover:border-zinc-500 transition-colors text-xs">+</button>
              </div>
            </div>
            <button @click="cart.remove(item.productId, item.size)" class="text-zinc-600 hover:text-zinc-300 transition-colors self-start mt-1">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Footer -->
        <div v-if="cart.items.length > 0" class="px-5 py-5 border-t border-zinc-800 space-y-3">
          <div class="flex justify-between text-sm">
            <span class="text-zinc-400">Tạm tính</span>
            <span class="font-medium">{{ cart.totalFormatted }}</span>
          </div>
          <p class="text-xs text-zinc-600">Phí vận chuyển tính khi thanh toán</p>
          <NuxtLink to="/checkout" @click="$emit('close')"
            class="block w-full py-3 bg-white text-black text-center text-sm font-semibold tracking-wider uppercase rounded hover:bg-zinc-100 transition-colors">
            Thanh toán
          </NuxtLink>
        </div>
      </aside>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
defineProps<{ open: boolean }>()
defineEmits<{ close: [] }>()

const cart = useCartStore()

function formatVND (cents: number) {
  return (cents / 100).toLocaleString('vi-VN', { style: 'currency', currency: 'VND' })
}
</script>

<style scoped>
.overlay-enter-active, .overlay-leave-active { transition: opacity 0.25s ease; }
.overlay-enter-from, .overlay-leave-to { opacity: 0; }

.drawer-enter-active, .drawer-leave-active { transition: transform 0.3s cubic-bezier(0.32, 0.72, 0, 1); }
.drawer-enter-from, .drawer-leave-to { transform: translateX(100%); }
</style>
