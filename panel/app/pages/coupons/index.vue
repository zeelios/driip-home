<template>
  <div>
    <!-- Toolbar -->
    <div class="page-toolbar">
      <div class="page-toolbar__filters">
        <ZInput
          v-model="search"
          placeholder="Tìm mã giảm giá..."
          type="search"
          class="page-toolbar__search"
          @input="onSearchInput"
        >
          <template #prefix>
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
          </template>
        </ZInput>
      </div>
      <ZButton size="sm" @click="showCreateModal = true">
        <template #prefix>
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5v14"/></svg>
        </template>
        Tạo mã giảm giá
      </ZButton>
    </div>

    <!-- Error -->
    <div v-if="listState === 'error'" class="page-error-bar">
      <span>{{ listError }}</span>
      <ZButton variant="ghost" size="sm" @click="fetchCoupons">Thử lại</ZButton>
    </div>

    <!-- Table -->
    <ZTable
      :columns="columns"
      :rows="coupons"
      :loading="listState === 'loading'"
      :skeleton-rows="10"
      row-key="id"
      empty-title="Không có mã giảm giá"
      empty-description="Chưa có mã giảm giá nào. Tạo mã đầu tiên."
    >
      <template #cell-code="{ row }">
        <span class="mono-id">{{ (row as CouponRow).code }}</span>
      </template>
      <template #cell-type="{ row }">
        <ZBadge variant="info">{{ couponTypeLabel((row as CouponRow).type) }}</ZBadge>
      </template>
      <template #cell-value="{ row }">
        <span class="cell-amount">
          {{ (row as CouponRow).type === 'percent' ? `${(row as CouponRow).value}%` : formatVnd((row as CouponRow).value) }}
        </span>
      </template>
      <template #cell-is_active="{ row }">
        <ZBadge :variant="(row as CouponRow).is_active ? 'success' : 'neutral'">
          {{ (row as CouponRow).is_active ? 'Đang hoạt động' : 'Tắt' }}
        </ZBadge>
      </template>
      <template #cell-usage="{ row }">
        {{ (row as CouponRow).used_count }}{{ (row as CouponRow).max_uses ? ` / ${(row as CouponRow).max_uses}` : '' }}
      </template>
      <template #cell-expires_at="{ row }">
        <span class="cell-date">{{ formatDate((row as CouponRow).expires_at) }}</span>
      </template>
    </ZTable>

    <!-- Pagination -->
    <div class="page-footer">
      <p class="page-footer__count">{{ meta.total }} mã giảm giá</p>
      <ZPagination
        :current-page="meta.current_page"
        :total-pages="meta.last_page"
        @change="onPageChange"
      />
    </div>

    <!-- Create modal -->
    <ZModal v-model="showCreateModal" title="Tạo mã giảm giá" size="md">
      <div class="form-grid">
        <ZInput
          v-model="form.code"
          label="Mã giảm giá *"
          placeholder="SUMMER20"
          :error="formErrors.code"
          class="form-grid__full"
        />
        <ZInput
          v-model="form.name"
          label="Tên *"
          placeholder="Khuyến mãi mùa hè"
          :error="formErrors.name"
          class="form-grid__full"
        />
        <ZSelect
          v-model="form.type"
          :options="typeOptions"
          label="Loại giảm giá *"
          :error="formErrors.type"
        />
        <ZInput
          v-model="form.value"
          label="Giá trị *"
          type="number"
          placeholder="20"
          :error="formErrors.value"
        />
        <ZInput
          v-model="form.starts_at"
          label="Ngày bắt đầu"
          type="date"
        />
        <ZInput
          v-model="form.expires_at"
          label="Ngày hết hạn"
          type="date"
        />
        <ZInput
          v-model="form.max_uses"
          label="Số lần dùng tối đa"
          type="number"
          placeholder="Không giới hạn"
          class="form-grid__full"
        />
      </div>
      <template #footer>
        <ZButton variant="outline" size="sm" :disabled="formPending" @click="closeCreateModal">Hủy</ZButton>
        <ZButton variant="primary" size="sm" :loading="formPending" @click="handleCreate">Tạo mã</ZButton>
      </template>
    </ZModal>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from "vue";
import { useApi } from "~/composables/useApi";
import { useToast } from "~/composables/useToast";
import { formatVnd, formatDate, getErrorMessage, sanitizeString } from "~/utils/format";
import type { TableColumn } from "~/components/z/Table.vue";
import type { SelectOption } from "~/components/z/Select.vue";

definePageMeta({ layout: "panel" });

interface CouponRow {
  id: string;
  code: string;
  name: string;
  type: string;
  value: number;
  used_count: number;
  max_uses: number | null;
  is_active: boolean;
  expires_at: string | null;
}

interface ListMeta { current_page: number; last_page: number; total: number; per_page: number; }

const api = useApi();
const toast = useToast();

const listState = ref<"idle" | "loading" | "loaded" | "error">("idle");
const listError = ref<string | null>(null);
const coupons = ref<CouponRow[]>([]);
const meta = ref<ListMeta>({ current_page: 1, last_page: 1, total: 0, per_page: 20 });
const search = ref("");
const currentPage = ref(1);

const showCreateModal = ref(false);
const formPending = ref(false);

const form = reactive({ code: "", name: "", type: "", value: "", starts_at: "", expires_at: "", max_uses: "" });
const formErrors = reactive({ code: "", name: "", type: "", value: "" });

let searchTimer: ReturnType<typeof setTimeout> | null = null;

const columns: TableColumn[] = [
  { key: "code", label: "Mã", skeletonWidth: "100px" },
  { key: "name", label: "Tên", skeletonWidth: "140px" },
  { key: "type", label: "Loại", skeletonWidth: "80px" },
  { key: "value", label: "Giá trị", align: "right", skeletonWidth: "80px" },
  { key: "is_active", label: "Trạng thái", skeletonWidth: "80px" },
  { key: "usage", label: "Đã dùng", align: "center", skeletonWidth: "60px" },
  { key: "expires_at", label: "Hết hạn", skeletonWidth: "100px" },
];

const typeOptions: SelectOption[] = [
  { value: "percent", label: "Phần trăm (%)" },
  { value: "fixed", label: "Số tiền cố định (₫)" },
];

function couponTypeLabel(type: string): string {
  return type === "percent" ? "Phần trăm" : type === "fixed" ? "Cố định" : type;
}

async function fetchCoupons(): Promise<void> {
  listState.value = "loading";
  listError.value = null;
  try {
    const params: Record<string, string> = { page: String(currentPage.value), per_page: "20" };
    if (search.value.trim()) params["search"] = search.value.trim();
    const q = new URLSearchParams(params).toString();
    const response = await api.get<{ data: CouponRow[]; meta: ListMeta }>(`/coupons?${q}`);
    coupons.value = response.data ?? [];
    meta.value = response.meta ?? { current_page: 1, last_page: 1, total: 0, per_page: 20 };
    listState.value = "loaded";
  } catch (error) {
    listState.value = "error";
    listError.value = getErrorMessage(error, "Không thể tải mã giảm giá.");
  }
}

function validateForm(): boolean {
  let valid = true;
  formErrors.code = sanitizeString(form.code) ? "" : "Mã là bắt buộc";
  formErrors.name = sanitizeString(form.name) ? "" : "Tên là bắt buộc";
  formErrors.type = form.type ? "" : "Loại là bắt buộc";
  formErrors.value = form.value && Number(form.value) > 0 ? "" : "Giá trị phải lớn hơn 0";
  if (formErrors.code || formErrors.name || formErrors.type || formErrors.value) valid = false;
  return valid;
}

async function handleCreate(): Promise<void> {
  if (!validateForm()) return;
  formPending.value = true;
  try {
    await api.post("/coupons", {
      code: sanitizeString(form.code).toUpperCase(),
      name: sanitizeString(form.name),
      type: form.type,
      value: Number(form.value),
      starts_at: form.starts_at || null,
      expires_at: form.expires_at || null,
      max_uses: form.max_uses ? Number(form.max_uses) : null,
    });
    toast.success("Đã tạo mã giảm giá");
    closeCreateModal();
    await fetchCoupons();
  } catch (error) {
    toast.error("Tạo thất bại", getErrorMessage(error));
  } finally {
    formPending.value = false;
  }
}

function closeCreateModal(): void {
  showCreateModal.value = false;
  form.code = ""; form.name = ""; form.type = ""; form.value = "";
  form.starts_at = ""; form.expires_at = ""; form.max_uses = "";
  formErrors.code = ""; formErrors.name = ""; formErrors.type = ""; formErrors.value = "";
}

function onSearchInput(): void {
  if (searchTimer) clearTimeout(searchTimer);
  searchTimer = setTimeout(() => { currentPage.value = 1; fetchCoupons(); }, 350);
}

function onPageChange(page: number): void {
  currentPage.value = page;
  fetchCoupons();
}

onMounted(() => { fetchCoupons(); });
</script>

<style scoped>
.page-toolbar {
  display: flex; align-items: flex-start; justify-content: space-between;
  gap: 0.75rem; margin-bottom: 1.125rem; flex-wrap: wrap;
}
.page-toolbar__filters { display: flex; gap: 0.625rem; flex: 1; flex-wrap: wrap; }
.page-toolbar__search { flex: 1; min-width: 180px; max-width: 280px; }
.page-error-bar {
  display: flex; align-items: center; justify-content: space-between;
  gap: 0.75rem; padding: 0.75rem 1rem; margin-bottom: 0.875rem;
  background: #fff5f5; border: 1px solid #fecaca; border-radius: 8px;
  font-size: 0.875rem; color: #b91c1c;
}
.page-footer {
  display: flex; align-items: center; justify-content: space-between;
  gap: 0.75rem; padding-top: 1rem; flex-wrap: wrap;
}
.page-footer__count { margin: 0; font-size: 0.8125rem; color: #888; }
.mono-id { font-family: ui-monospace, monospace; font-size: 0.8125rem; font-weight: 600; }
.cell-amount { font-weight: 600; }
.cell-date { font-size: 0.8125rem; color: #666; }
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.form-grid__full { grid-column: 1 / -1; }
@media (max-width: 480px) { .form-grid { grid-template-columns: 1fr; } }
</style>
