<template>
  <div class="page">
    <PPageHeader title="Nhập hàng" eyebrow="Purchase Orders">
      <template #actions>
        <PBtn @click="createOpen = true">+ Tạo PO</PBtn>
      </template>
    </PPageHeader>
    <div v-if="store.error" class="error-bar">{{ store.error }}</div>
    <div class="table-card">
      <ZTable
        :cols="cols"
        :rows="store.list"
        :loading="store.loading"
        row-key="id"
        :on-row-click="(r) => navigateTo(`/purchase-orders/${r.id}`)"
      >
        <template #cell-id="{ value }"
          ><span class="mono"
            >#{{ value?.slice(0, 8).toUpperCase() }}</span
          ></template
        >
        <template #cell-status="{ value }"
          ><PBadge
            :status="
              value === 'received'
                ? 'ok'
                : value === 'cancelled'
                ? 'danger'
                : 'pending'
            "
            :label="statusLabel(value)"
        /></template>
        <template #cell-created_at="{ value }"
          ><span class="date">{{ formatDate(value) }}</span></template
        >
        <template #cell-actions="{ row }">
          <div class="row-actions">
            <PBtn
              variant="ghost"
              size="xs"
              @click.stop="navigateTo(`/purchase-orders/${row.id}`)"
              >Chi tiết →</PBtn
            >
          </div>
        </template>
        <template #mobile-card="{ row }">
          <div class="mc-main">
            <p class="mono">#{{ row.id?.slice(0, 8).toUpperCase() }}</p>
            <p class="date">{{ formatDate(row.created_at) }}</p>
          </div>
          <div class="mc-right">
            <PBadge
              :status="
                row.status === 'received'
                  ? 'ok'
                  : row.status === 'cancelled'
                  ? 'danger'
                  : 'pending'
              "
              :label="statusLabel(row.status)"
            />
          </div>
        </template>
      </ZTable>
    </div>
    <PPagination
      :page="store.filters.page"
      :total="store.totalPages"
      @change="changePage"
    />

    <ZModal v-model="createOpen" title="Tạo lệnh nhập hàng">
      <div class="form-grid">
        <PInput
          v-model="newForm.supplier_name"
          label="Nhà cung cấp"
          placeholder="Tên công ty / cá nhân…"
        />
        <PInput
          v-model="newForm.notes"
          label="Ghi chú"
          type="textarea"
          :rows="2"
          placeholder="Thông tin đơn nhập hàng…"
        />
      </div>
      <div v-if="store.error" class="form-error">{{ store.error }}</div>
      <template #footer>
        <PBtn variant="ghost" @click="createOpen = false">Hủy</PBtn>
        <PBtn
          :loading="store.actionBusy"
          :disabled="!newForm.supplier_name"
          @click="create"
          >Tạo PO</PBtn
        >
      </template>
    </ZModal>
  </div>
</template>
<script setup lang="ts">
import type { ZCol } from "~/components/ZTable.vue";
const store = usePurchaseOrdersStore();
const createOpen = ref(false);
const newForm = reactive({ supplier_name: "", notes: "" });
const cols: ZCol[] = [
  { key: "id", label: "Mã PO", width: "100px" },
  { key: "supplier_name", label: "Nhà cung cấp", width: "minmax(120px,2fr)" },
  { key: "status", label: "Trạng thái", width: "130px" },
  { key: "created_at", label: "Ngày tạo", width: "110px", hide: "md" },
  { key: "actions", label: "", width: "180px", align: "right", hide: "sm" },
];
onMounted(() => store.fetchList());
function changePage(p: number) {
  store.filters.page = p;
  store.fetchList();
}
function statusLabel(s: string) {
  return (
    { pending: "Chờ nhận", received: "Đã nhận", cancelled: "Đã hủy" }[s] ?? s
  );
}
function formatDate(iso: string) {
  return new Date(iso).toLocaleDateString("vi-VN");
}
async function create() {
  if (!newForm.supplier_name) return;
  const po = await store.create({
    supplier_name: newForm.supplier_name,
    notes: newForm.notes || undefined,
  });
  if (po) {
    createOpen.value = false;
    Object.assign(newForm, { supplier_name: "", notes: "" });
  }
}
</script>
<style scoped>
.page {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
.error-bar {
  padding: 0.5rem 0.75rem;
  background: var(--status-danger);
  color: var(--status-danger-t);
  border-radius: 0.375rem;
  font-size: 0.8rem;
}
.table-card {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: 0.75rem;
  overflow: hidden;
}
.mono {
  font-family: monospace;
  font-size: 0.75rem;
  color: var(--text);
}
.date {
  font-size: 0.75rem;
  color: var(--text-mute);
}
.row-actions {
  display: flex;
  gap: 0.375rem;
  justify-content: flex-end;
}
.mc-main {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}
.mc-right {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.375rem;
  flex-shrink: 0;
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
