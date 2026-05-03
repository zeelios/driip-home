<template>
  <div class="page">
    <NuxtLink to="/products" class="back">← Sản phẩm</NuxtLink>

    <div v-if="store.loading && !store.current" class="skeleton-stack">
      <div class="skeleton h-28" />
      <div class="skeleton h-40" />
    </div>
    <div v-else-if="!store.current" class="empty">Không tìm thấy sản phẩm.</div>

    <template v-else>
      <!-- Header -->
      <div class="prod-header">
        <div class="prod-meta">
          <p class="prod-sku-tag">{{ store.current.sku }}</p>
          <h1 class="prod-name">{{ store.current.name }}</h1>
          <PBadge
            :status="
              store.current.stock_quantity <= 0
                ? 'danger'
                : store.current.stock_quantity <= 5
                ? 'pending'
                : 'ok'
            "
            :label="`${store.current.stock_quantity} tồn kho`"
          />
        </div>
        <div class="prod-actions">
          <PBtn variant="ghost" size="xs" @click="openEdit">Sửa</PBtn>
          <PBtn
            variant="danger"
            size="xs"
            :loading="store.actionBusy"
            @click="confirmDelete"
            >Xóa</PBtn
          >
        </div>
      </div>

      <!-- Detail grid -->
      <div class="detail-grid">
        <!-- Info card -->
        <div class="detail-card">
          <p class="card-title">Thông tin sản phẩm</p>
          <div class="meta-list">
            <div class="meta-row">
              <span class="meta-k">Tên</span>
              <span class="meta-v">{{ store.current.name }}</span>
            </div>
            <div class="meta-row">
              <span class="meta-k">SKU</span>
              <span class="meta-v mono">{{ store.current.sku }}</span>
            </div>
            <div class="meta-row">
              <span class="meta-k">Giá bán</span>
              <span class="meta-v price">{{
                formatVND(store.current.price_cents)
              }}</span>
            </div>
            <div class="meta-row">
              <span class="meta-k">Tồn kho</span>
              <span class="meta-v">{{ store.current.stock_quantity }}</span>
            </div>
            <div class="meta-row">
              <span class="meta-k">Ngày tạo</span>
              <span class="meta-v">{{
                formatDate(store.current.created_at)
              }}</span>
            </div>
            <div class="meta-row">
              <span class="meta-k">Cập nhật</span>
              <span class="meta-v">{{
                formatDate(store.current.updated_at)
              }}</span>
            </div>
          </div>
        </div>

        <!-- Description card -->
        <div class="detail-card">
          <p class="card-title">Mô tả</p>
          <p v-if="store.current.description" class="description-text">
            {{ store.current.description }}
          </p>
          <p v-else class="empty-sm">Chưa có mô tả.</p>
        </div>
      </div>
    </template>

    <!-- Edit modal -->
    <ZModal v-model="editOpen" title="Sửa sản phẩm">
      <div class="form-grid">
        <PInput v-model="form.name" label="Tên sản phẩm" />
        <PInput v-model="form.sku" label="SKU" />
        <PInput
          v-model="form.price_vnd"
          label="Giá (VND)"
          type="number"
          placeholder="379"
        />
        <PInput v-model="form.stock_quantity" label="Tồn kho" type="number" />
        <PInput
          v-model="form.description"
          label="Mô tả"
          type="textarea"
          :rows="3"
        />
      </div>
      <div v-if="store.error" class="form-error">{{ store.error }}</div>
      <template #footer>
        <PBtn variant="ghost" @click="editOpen = false">Hủy</PBtn>
        <PBtn :loading="store.actionBusy" @click="save">Lưu</PBtn>
      </template>
    </ZModal>
  </div>
</template>

<script setup lang="ts">
const store = useProductsStore();
const route = useRoute();
const id = route.params.id as string;

const editOpen = ref(false);
const form = reactive({
  name: "",
  sku: "",
  price_vnd: "",
  stock_quantity: "",
  description: "",
});

onMounted(() => store.fetchDetail(id));

watch(
  () => store.current,
  (p: typeof store.current) => {
    if (p)
      Object.assign(form, {
        name: p.name,
        sku: p.sku,
        price_vnd: String(p.price_cents / 100),
        stock_quantity: String(p.stock_quantity),
        description: p.description ?? "",
      });
  }
);

function openEdit() {
  editOpen.value = true;
}

async function save() {
  const ok = await store.update(id, {
    name: form.name,
    sku: form.sku,
    price_cents: Math.round(Number(form.price_vnd) * 100),
    stock_quantity: Number(form.stock_quantity),
    description: form.description || undefined,
  });
  if (ok) editOpen.value = false;
}

async function confirmDelete() {
  if (!confirm("Xóa sản phẩm này? Hành động không thể hoàn tác.")) return;
  const ok = await store.remove(id);
  if (ok) navigateTo("/products");
}

function formatVND(cents: number) {
  return (cents / 100).toLocaleString("vi-VN", {
    style: "currency",
    currency: "VND",
  });
}
function formatDate(iso: string) {
  return new Date(iso).toLocaleDateString("vi-VN", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
  });
}
</script>

<style scoped>
.page {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
.back {
  font-size: 0.78rem;
  color: var(--text-mute);
  text-decoration: none;
  transition: color 0.12s;
}
.back:hover {
  color: var(--text-sub);
}
.skeleton-stack {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}
.h-28 {
  height: 112px;
}
.h-40 {
  height: 160px;
}
.empty {
  padding: 2rem;
  text-align: center;
  font-size: 0.875rem;
  color: var(--text-mute);
}

/* Header */
.prod-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: 0.75rem;
  padding: 1.25rem;
  flex-wrap: wrap;
}
.prod-meta {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}
.prod-sku-tag {
  font-family: monospace;
  font-size: 0.72rem;
  color: var(--text-mute);
}
.prod-name {
  font-family: "Barlow Condensed", ui-sans-serif, sans-serif;
  font-size: 1.5rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--text);
}
.prod-actions {
  display: flex;
  gap: 0.375rem;
  flex-shrink: 0;
}

/* Grid */
.detail-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 0.875rem;
}
@media (min-width: 640px) {
  .detail-grid {
    grid-template-columns: 1fr 1fr;
  }
}

.detail-card {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: 0.75rem;
  padding: 1rem;
}
.card-title {
  font-size: 0.65rem;
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: var(--text-mute);
  margin-bottom: 0.875rem;
}

/* Meta list */
.meta-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}
.meta-row {
  display: flex;
  justify-content: space-between;
  gap: 0.75rem;
  align-items: baseline;
}
.meta-k {
  font-size: 0.68rem;
  font-weight: 600;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: var(--text-mute);
  flex-shrink: 0;
}
.meta-v {
  font-size: 0.78rem;
  color: var(--text-sub);
  text-align: right;
}
.mono {
  font-family: monospace;
}
.price {
  font-weight: 600;
  color: var(--text);
}

/* Description */
.description-text {
  font-size: 0.82rem;
  color: var(--text-sub);
  line-height: 1.6;
  white-space: pre-wrap;
}
.empty-sm {
  font-size: 0.8rem;
  color: var(--text-mute);
}

/* Form */
.form-grid {
  display: flex;
  flex-direction: column;
  gap: 0.875rem;
}
.form-error {
  font-size: 0.75rem;
  color: #ef4444;
  padding: 0.5rem 0.75rem;
  background: rgba(239, 68, 68, 0.08);
  border: 1px solid rgba(239, 68, 68, 0.2);
  border-radius: 0.375rem;
  margin-top: 0.5rem;
}
</style>
