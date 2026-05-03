<template>
  <div>
    <NuxtLink to="/account/orders" class="inline-flex items-center gap-1.5 text-xs text-zinc-500 hover:text-zinc-300 transition-colors mb-6">
      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
      </svg>
      Tất cả đơn hàng
    </NuxtLink>

    <div v-if="loading" class="space-y-4">
      <div class="h-24 bg-zinc-900/50 rounded-xl animate-pulse" />
      <div class="h-40 bg-zinc-900/50 rounded-xl animate-pulse" />
    </div>

    <div v-else-if="!order" class="text-center py-20">
      <p class="text-zinc-500">Không tìm thấy đơn hàng.</p>
    </div>

    <div v-else class="space-y-4">
      <!-- Order header -->
      <div class="bg-zinc-900/30 border border-zinc-800/50 rounded-xl p-5">
        <div class="flex items-start justify-between gap-3 mb-4">
          <div>
            <p class="font-['Barlow_Condensed',sans-serif] font-bold text-xl tracking-wide">
              #{{ order.id.slice(0, 8).toUpperCase() }}
            </p>
            <p class="text-xs text-zinc-500 mt-0.5">{{ formatDate(order.created_at) }}</p>
          </div>
          <span class="px-3 py-1 rounded-full text-xs font-semibold tracking-wider uppercase"
            :class="statusClass(order.status)">
            {{ statusLabel(order.status) }}
          </span>
        </div>

        <!-- Status timeline -->
        <div class="flex items-center gap-0 mt-4">
          <div v-for="(step, i) in statusSteps" :key="step.key" class="flex items-center gap-0 flex-1 last:flex-none">
            <div class="flex flex-col items-center">
              <div class="w-6 h-6 rounded-full border-2 flex items-center justify-center transition-colors"
                :class="isStepDone(step.key)
                  ? 'border-white bg-white'
                  : isCurrentStep(step.key)
                    ? 'border-zinc-400 bg-transparent'
                    : 'border-zinc-800 bg-transparent'">
                <svg v-if="isStepDone(step.key)" class="w-3 h-3 text-black" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
              </div>
              <p class="text-[9px] text-zinc-600 mt-1 text-center leading-tight max-w-[48px]">{{ step.label }}</p>
            </div>
            <div v-if="i < statusSteps.length - 1" class="flex-1 h-px mb-4"
              :class="isStepDone(step.key) ? 'bg-white/30' : 'bg-zinc-800'" />
          </div>
        </div>
      </div>

      <!-- Items -->
      <div class="bg-zinc-900/30 border border-zinc-800/50 rounded-xl p-5">
        <h3 class="text-xs text-zinc-500 uppercase tracking-wider mb-4">Sản phẩm</h3>
        <div class="space-y-3">
          <div v-for="item in order.items" :key="item.id" class="flex items-center gap-3">
            <div class="w-12 h-14 bg-zinc-800 rounded-lg flex items-center justify-center flex-shrink-0">
              <span class="text-xs text-zinc-600 font-['Barlow_Condensed',sans-serif]">CK</span>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium truncate">{{ item.product_name || 'Sản phẩm' }}</p>
              <p class="text-xs text-zinc-500">SL: {{ item.quantity }}</p>
            </div>
            <p class="text-sm text-zinc-300 flex-shrink-0">{{ formatVND(item.unit_price_cents * item.quantity) }}</p>
          </div>
        </div>
      </div>

      <!-- Totals -->
      <div class="bg-zinc-900/30 border border-zinc-800/50 rounded-xl p-5">
        <h3 class="text-xs text-zinc-500 uppercase tracking-wider mb-4">Tổng kết</h3>
        <div class="space-y-2 text-sm">
          <div class="flex justify-between text-zinc-400">
            <span>Tạm tính</span>
            <span>{{ formatVND(order.total_cents) }}</span>
          </div>
          <div class="flex justify-between text-zinc-400">
            <span>Phí vận chuyển</span>
            <span>{{ order.shipping_fee_cents ? formatVND(order.shipping_fee_cents) : 'Miễn phí' }}</span>
          </div>
          <div class="flex justify-between font-semibold text-white border-t border-zinc-800 pt-2 mt-2">
            <span>Tổng cộng</span>
            <span>{{ formatVND(order.grand_total_cents) }}</span>
          </div>
        </div>
      </div>

      <!-- Shipping address -->
      <div v-if="order.shipping_address" class="bg-zinc-900/30 border border-zinc-800/50 rounded-xl p-5">
        <h3 class="text-xs text-zinc-500 uppercase tracking-wider mb-3">Địa chỉ giao hàng</h3>
        <p class="text-sm text-zinc-300">{{ order.shipping_address.street }}</p>
        <p class="text-sm text-zinc-500">{{ order.shipping_address.ward }}, {{ order.shipping_address.district }}, {{ order.shipping_address.city }}</p>
      </div>

      <!-- Payment -->
      <div class="bg-zinc-900/30 border border-zinc-800/50 rounded-xl p-5">
        <h3 class="text-xs text-zinc-500 uppercase tracking-wider mb-3">Thanh toán</h3>
        <div class="flex items-center gap-2">
          <svg class="w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
          <span class="text-sm text-zinc-300">Thanh toán khi nhận hàng (COD)</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ layout: 'account' })

const route = useRoute()
const { driipFetch } = useDriipApi()
const order = ref<any>(null)
const loading = ref(true)

const statusSteps = [
  { key: 'pending', label: 'Đặt hàng' },
  { key: 'confirmed', label: 'Xác nhận' },
  { key: 'packing', label: 'Đóng gói' },
  { key: 'shipped', label: 'Đang giao' },
  { key: 'delivered', label: 'Đã giao' },
]

const statusOrder = ['pending', 'confirmed', 'packing', 'shipped', 'delivered']

function isStepDone (key: string) {
  if (!order.value || order.value.status === 'cancelled') return false
  return statusOrder.indexOf(order.value.status) > statusOrder.indexOf(key)
}
function isCurrentStep (key: string) {
  return order.value?.status === key
}

onMounted(async () => {
  try {
    order.value = await driipFetch<any>(`/public/orders/${route.params.id}`)
  } catch {
    order.value = null
  } finally {
    loading.value = false
  }
})

function formatDate (iso: string) {
  return new Date(iso).toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}
function formatVND (cents: number) {
  return (cents / 100).toLocaleString('vi-VN', { style: 'currency', currency: 'VND' })
}
function statusLabel (status: string) {
  const map: Record<string, string> = {
    pending: 'Chờ xác nhận', confirmed: 'Đã xác nhận', packing: 'Đang đóng gói',
    shipped: 'Đang giao', delivered: 'Đã giao', cancelled: 'Đã hủy',
  }
  return map[status] ?? status
}
function statusClass (status: string) {
  if (status === 'delivered') return 'bg-green-500/15 text-green-400'
  if (status === 'cancelled') return 'bg-red-500/15 text-red-400'
  if (status === 'shipped') return 'bg-blue-500/15 text-blue-400'
  return 'bg-zinc-700/50 text-zinc-400'
}
</script>
