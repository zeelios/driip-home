<template>
  <div>
    <!-- Skeleton -->
    <div v-if="address.loading && address.addresses.length === 0" class="addr-skeleton">
      <div v-for="i in 2" :key="i" class="skeleton addr-skeleton__row" />
    </div>

    <div v-else>
      <!-- List -->
      <div class="addr-list">
        <div v-if="address.addresses.length === 0" class="addr-empty">
          Chưa có địa chỉ nào. Thêm để thanh toán nhanh hơn.
        </div>

        <div v-for="addr in address.addresses" :key="addr.id" class="addr-item"
          :class="{ 'addr-item--default': addr.is_default }">
          <div class="addr-item__main">
            <div class="addr-item__info">
              <div class="addr-item__street">
                {{ addr.street }}
                <span v-if="addr.is_default" class="addr-item__badge">Mặc định</span>
              </div>
              <p class="addr-item__sub">{{ addr.ward }}, {{ addr.district }}, {{ addr.city }}</p>
            </div>
            <div class="addr-item__actions">
              <button v-if="!addr.is_default" @click="address.setDefault(addr.id)" class="addr-action addr-action--set">
                Đặt mặc định
              </button>
              <button @click="address.remove(addr.id)" class="addr-action addr-action--del" aria-label="Xóa địa chỉ">
                <svg class="addr-action__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Add form -->
      <div class="addr-form-card">
        <h3 class="addr-form-title">Thêm địa chỉ mới</h3>
        <div class="addr-form-fields">
          <ZInput v-model="form.street"   label="Số nhà, tên đường" placeholder="VD: 123 Nguyễn Huệ" />
          <div class="addr-form-row">
            <ZInput v-model="form.ward"     label="Phường/Xã"    placeholder="VD: Phường Bến Nghé" />
            <ZInput v-model="form.district" label="Quận/Huyện"   placeholder="VD: Quận 1" />
          </div>
          <ZInput v-model="form.city"     label="Tỉnh/Thành phố" placeholder="VD: Hồ Chí Minh" />
        </div>

        <Transition name="z-msg">
          <p v-if="address.error" class="addr-error">{{ address.error }}</p>
        </Transition>

        <ZButton @click="submit" :loading="address.loading" :disabled="!isFormValid">
          Thêm địa chỉ
        </ZButton>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ layout: 'account' })

const address = useAddressStore()
const form = reactive({ street: '', ward: '', district: '', city: '' })
const isFormValid = computed(() => form.street && form.ward && form.district && form.city)

onMounted(() => address.fetch())

async function submit () {
  if (!isFormValid.value) return
  try { await address.create({ ...form }); form.street = ''; form.ward = ''; form.district = ''; form.city = '' }
  catch {}
}
</script>

<style scoped>
.addr-skeleton { display: flex; flex-direction: column; gap: 0.75rem; margin-bottom: 1.5rem; }
.addr-skeleton__row { height: 5rem; }

.addr-list { display: flex; flex-direction: column; gap: 0.625rem; margin-bottom: 1.25rem; }
.addr-empty { text-align: center; padding: 2.5rem 0; font-size: 0.875rem; color: var(--text-sub); }

.addr-item {
  background-color: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: 0.875rem;
  padding: 0.875rem 1rem;
  transition: border-color 0.2s ease;
}
.addr-item--default { border-color: var(--border-hi); }

.addr-item__main { display: flex; align-items: flex-start; justify-content: space-between; gap: 0.75rem; }

.addr-item__street {
  display: flex; align-items: center; gap: 0.5rem;
  font-size: 0.875rem; color: var(--text); margin-bottom: 0.25rem;
}
.addr-item__badge {
  padding: 0.15rem 0.45rem;
  background-color: var(--bg-skeleton);
  color: var(--text-sub);
  font-size: 0.6rem; font-weight: 600;
  letter-spacing: 0.1em; text-transform: uppercase;
  border-radius: 999px;
}
.addr-item__sub { font-size: 0.8rem; color: var(--text-mute); }

.addr-item__actions { display: flex; align-items: center; gap: 0.5rem; flex-shrink: 0; }
.addr-action {
  background: none; border: none; cursor: pointer; transition: color 0.15s ease;
  font-family: "Be Vietnam Pro", sans-serif;
}
.addr-action--set {
  font-size: 0.72rem; color: var(--text-mute); text-decoration: underline;
  text-underline-offset: 3px; padding: 0;
}
.addr-action--set:hover { color: var(--text-sub); }
.addr-action--del {
  width: 1.75rem; height: 1.75rem;
  display: flex; align-items: center; justify-content: center;
  color: var(--text-mute); border-radius: 0.375rem;
}
.addr-action--del:hover { color: #ef4444; background: rgba(239,68,68,0.08); }
.addr-action__icon { width: 1rem; height: 1rem; }

.addr-form-card {
  background-color: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: 0.875rem;
  padding: 1.25rem;
}
.addr-form-title {
  font-size: 0.8rem; font-weight: 600; color: var(--text-sub); margin-bottom: 1rem;
}
.addr-form-fields { display: flex; flex-direction: column; gap: 0.875rem; margin-bottom: 1.25rem; }
.addr-form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }

.addr-error {
  font-size: 0.75rem; color: #ef4444;
  padding: 0.5rem 0.75rem;
  background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.2);
  border-radius: 0.5rem; margin-bottom: 1rem;
}

.z-msg-enter-active, .z-msg-leave-active { transition: opacity 0.2s ease, transform 0.15s ease; }
.z-msg-enter-from, .z-msg-leave-to { opacity: 0; transform: translateY(-3px); }
</style>
