<template>
  <div>
    <div v-if="!auth.customer" class="cp-empty">
      <p class="cp-empty__text">Vui lòng đăng nhập để xem tài khoản.</p>
      <ZButton to="/account/login">Đăng nhập</ZButton>
    </div>

    <div v-else>
      <!-- Stats -->
      <div class="cp-stats">
        <div class="cp-stat">
          <p class="cp-stat__label">Tổng đơn</p>
          <p class="cp-stat__value">{{ orderCount }}</p>
        </div>
        <div class="cp-stat">
          <p class="cp-stat__label">Thành viên</p>
          <p class="cp-stat__value cp-stat__value--muted">Standard</p>
        </div>
      </div>

      <!-- Profile form -->
      <div class="cp-card">
        <h2 class="cp-card__title">Thông tin cá nhân</h2>
        <div class="cp-card__fields">
          <ZInput v-model="form.name"  label="Họ tên"         type="text"  />
          <ZInput v-model="form.email" label="Email"          type="email" />
          <ZInput v-model="form.phone" label="Số điện thoại"  type="tel"   :optional="true" />
        </div>

        <Transition name="z-msg">
          <p v-if="auth.error" class="cp-error">{{ auth.error }}</p>
        </Transition>
        <Transition name="z-msg">
          <p v-if="saved" class="cp-success">✓ Đã lưu thành công</p>
        </Transition>

        <ZButton @click="save" :loading="auth.loading" class="cp-save-btn">Lưu thay đổi</ZButton>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ layout: 'account' })

const auth = useAuthStore()
const orderStore = useOrderStore()
const orderCount = ref(0)
const saved = ref(false)
const form = reactive({ name: '', email: '', phone: '' })

watch(() => auth.customer, (c) => {
  if (c) { form.name = c.name; form.email = c.email; form.phone = c.phone || '' }
}, { immediate: true })

onMounted(async () => {
  if (!auth.customer) await auth.fetchMe()
  try { const orders = await orderStore.listMyOrders(); orderCount.value = orders.length } catch {}
})

async function save () {
  const ok = await auth.updateProfile({ name: form.name, email: form.email, phone: form.phone || undefined })
  if (ok) { saved.value = true; setTimeout(() => { saved.value = false }, 3000) }
}
</script>

<style scoped>
.cp-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 5rem 0; gap: 1rem; }
.cp-empty__text { font-size: 0.875rem; color: var(--text-sub); }

.cp-stats { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.75rem; margin-bottom: 1.5rem; }
.cp-stat {
  background-color: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: 0.875rem;
  padding: 1rem;
  transition: border-color 0.2s ease;
}
.cp-stat__label {
  font-size: 0.65rem; font-weight: 600; letter-spacing: 0.12em;
  text-transform: uppercase; color: var(--text-mute); margin-bottom: 0.35rem;
}
.cp-stat__value {
  font-family: "Barlow Condensed", sans-serif;
  font-weight: 700; font-size: 1.75rem; color: var(--text);
}
.cp-stat__value--muted { color: var(--text-sub); }

.cp-card {
  background-color: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: 0.875rem;
  padding: 1.25rem;
}
.cp-card__title {
  font-size: 0.8rem; font-weight: 600; color: var(--text-sub);
  margin-bottom: 1.25rem;
}
.cp-card__fields { display: flex; flex-direction: column; gap: 0.875rem; margin-bottom: 1.25rem; }

.cp-error {
  font-size: 0.75rem; color: #ef4444;
  padding: 0.5rem 0.75rem;
  background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.2);
  border-radius: 0.5rem; margin-bottom: 1rem;
}
.cp-success {
  font-size: 0.75rem; color: #22c55e;
  padding: 0.5rem 0.75rem;
  background: rgba(34,197,94,0.08); border: 1px solid rgba(34,197,94,0.2);
  border-radius: 0.5rem; margin-bottom: 1rem;
}
.cp-save-btn { margin-top: 0.25rem; }

.z-msg-enter-active, .z-msg-leave-active { transition: opacity 0.2s ease, transform 0.15s ease; }
.z-msg-enter-from, .z-msg-leave-to { opacity: 0; transform: translateY(-3px); }
</style>
