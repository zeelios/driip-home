<template>
  <div>
    <!-- Toolbar -->
    <div class="page-toolbar">
      <ZInput
        v-model="search"
        placeholder="Tìm tên, email, điện thoại..."
        type="search"
        class="page-toolbar__search"
        @input="onSearchInput"
      >
        <template #prefix>
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        </template>
      </ZInput>
      <ZButton size="sm" @click="showCreateModal = true">
        <template #prefix>
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5v14"/></svg>
        </template>
        Thêm khách hàng
      </ZButton>
    </div>

    <!-- Error -->
    <div v-if="store.listState === 'error'" class="page-error-bar">
      <span>{{ store.listError }}</span>
      <ZButton variant="ghost" size="sm" @click="store.fetchCustomers()">Thử lại</ZButton>
    </div>

    <!-- Table -->
    <ZTable
      :columns="columns"
      :rows="store.customers"
      :loading="store.isListLoading"
      :skeleton-rows="12"
      row-key="id"
      empty-title="Không có khách hàng"
      empty-description="Chưa có khách hàng nào khớp với bộ lọc."
      :on-row-click="(row) => navigateTo(`/customers/${(row as CustomerRow).id}`)"
    >
      <template #cell-name="{ row }">
        <div class="cell-customer">
          <div class="cell-customer__avatar">
            {{ initials(row as CustomerRow) }}
          </div>
          <div>
            <p class="cell-customer__name">{{ fullName(row as CustomerRow) }}</p>
            <p class="cell-customer__code">{{ (row as CustomerRow).customer_code }}</p>
          </div>
        </div>
      </template>
      <template #cell-contact="{ row }">
        <div class="cell-contact">
          <span v-if="(row as CustomerRow).email" class="cell-contact__item">{{ (row as CustomerRow).email }}</span>
          <span v-if="(row as CustomerRow).phone" class="cell-contact__item">{{ (row as CustomerRow).phone }}</span>
        </div>
      </template>
      <template #cell-status="{ row }">
        <ZBadge v-if="(row as CustomerRow).is_blocked" variant="danger">Đã khóa</ZBadge>
        <ZBadge v-else variant="success">Hoạt động</ZBadge>
      </template>
      <template #cell-total_spent="{ row }">
        <span class="cell-amount">{{ formatVnd((row as CustomerRow).total_spent) }}</span>
      </template>
      <template #cell-total_orders="{ row }">
        {{ (row as CustomerRow).total_orders }}
      </template>
      <template #cell-created_at="{ row }">
        <span class="cell-date">{{ formatDate((row as CustomerRow).created_at) }}</span>
      </template>
    </ZTable>

    <!-- Pagination -->
    <div class="page-footer">
      <p class="page-footer__count">{{ store.meta.total }} khách hàng</p>
      <ZPagination
        :current-page="store.meta.current_page"
        :total-pages="store.meta.last_page"
        @change="onPageChange"
      />
    </div>

    <!-- Create modal -->
    <ZModal v-model="showCreateModal" title="Thêm khách hàng mới" size="md">
      <div class="form-grid">
        <ZInput
          v-model="form.first_name"
          label="Họ *"
          placeholder="Nguyễn"
          :error="formErrors.first_name"
          autocomplete="given-name"
        />
        <ZInput
          v-model="form.last_name"
          label="Tên *"
          placeholder="Văn An"
          :error="formErrors.last_name"
          autocomplete="family-name"
        />
        <ZInput
          v-model="form.email"
          label="Email"
          type="email"
          placeholder="khach@email.com"
          :error="formErrors.email"
          autocomplete="email"
        />
        <ZInput
          v-model="form.phone"
          label="Điện thoại"
          type="tel"
          placeholder="0901234567"
          :error="formErrors.phone"
          autocomplete="tel"
        />
        <ZSelect
          v-model="form.gender"
          :options="genderOptions"
          label="Giới tính"
          placeholder="Chọn giới tính"
          class="form-grid__full"
        />
      </div>
      <template #footer>
        <ZButton variant="outline" size="sm" :disabled="store.formPending" @click="closeCreateModal">Hủy</ZButton>
        <ZButton variant="primary" size="sm" :loading="store.formPending" @click="handleCreate">Tạo khách hàng</ZButton>
      </template>
    </ZModal>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from "vue";
import { useCustomersStore } from "~/stores/customers";
import { formatVnd, formatDate, sanitizeString, sanitizeEmail } from "~/utils/format";
import type { TableColumn } from "~/components/z/Table.vue";
import type { SelectOption } from "~/components/z/Select.vue";

definePageMeta({ layout: "panel" });

interface CustomerRow {
  id: string;
  customer_code: string;
  first_name: string;
  last_name: string;
  email: string | null;
  phone: string | null;
  is_blocked: boolean;
  total_spent: number;
  total_orders: number;
  created_at: string | null;
}

const store = useCustomersStore();
const search = ref(store.filters.search);
const showCreateModal = ref(false);

let searchTimer: ReturnType<typeof setTimeout> | null = null;

const form = reactive({
  first_name: "",
  last_name: "",
  email: "",
  phone: "",
  gender: "",
  notes: "",
});

const formErrors = reactive({
  first_name: "",
  last_name: "",
  email: "",
  phone: "",
});

const columns: TableColumn[] = [
  { key: "name", label: "Khách hàng", skeletonWidth: "180px" },
  { key: "contact", label: "Liên hệ", skeletonWidth: "160px" },
  { key: "status", label: "Trạng thái", skeletonWidth: "80px" },
  { key: "total_orders", label: "Đơn hàng", align: "center", skeletonWidth: "50px" },
  { key: "total_spent", label: "Chi tiêu", align: "right", skeletonWidth: "100px" },
  { key: "created_at", label: "Ngày tạo", skeletonWidth: "100px" },
];

const genderOptions: SelectOption[] = [
  { value: "male", label: "Nam" },
  { value: "female", label: "Nữ" },
  { value: "other", label: "Khác" },
];

function fullName(row: CustomerRow): string {
  return `${row.first_name} ${row.last_name}`.trim();
}

function initials(row: CustomerRow): string {
  return ((row.first_name[0] ?? "") + (row.last_name[0] ?? "")).toUpperCase();
}

function validateForm(): boolean {
  let valid = true;
  formErrors.first_name = sanitizeString(form.first_name) ? "" : "Họ là bắt buộc";
  formErrors.last_name = sanitizeString(form.last_name) ? "" : "Tên là bắt buộc";
  if (form.email && !sanitizeEmail(form.email)) {
    formErrors.email = "Email không hợp lệ";
    valid = false;
  } else {
    formErrors.email = "";
  }
  if (!formErrors.first_name && !formErrors.last_name) return valid;
  return false;
}

async function handleCreate(): Promise<void> {
  if (!validateForm()) return;
  const ok = await store.createCustomer({
    first_name: form.first_name,
    last_name: form.last_name,
    email: form.email || null,
    phone: form.phone || null,
    gender: form.gender || null,
  });
  if (ok) closeCreateModal();
}

function closeCreateModal(): void {
  showCreateModal.value = false;
  form.first_name = "";
  form.last_name = "";
  form.email = "";
  form.phone = "";
  form.gender = "";
  formErrors.first_name = "";
  formErrors.last_name = "";
  formErrors.email = "";
  formErrors.phone = "";
}

function onSearchInput(): void {
  if (searchTimer) clearTimeout(searchTimer);
  searchTimer = setTimeout(() => {
    store.setSearch(search.value);
    store.fetchCustomers();
  }, 350);
}

function onPageChange(page: number): void {
  store.setPage(page);
  store.fetchCustomers();
}

onMounted(() => {
  store.fetchCustomers();
});
</script>

<style scoped>
.page-toolbar {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 0.75rem;
  margin-bottom: 1.125rem;
  flex-wrap: wrap;
}
.page-toolbar__search { flex: 1; min-width: 180px; max-width: 320px; }

.page-error-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  margin-bottom: 0.875rem;
  background: #fff5f5;
  border: 1px solid #fecaca;
  border-radius: 8px;
  font-size: 0.875rem;
  color: #b91c1c;
}

.page-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding-top: 1rem;
  flex-wrap: wrap;
}
.page-footer__count { margin: 0; font-size: 0.8125rem; color: #888; }

.cell-customer { display: flex; align-items: center; gap: 0.625rem; }
.cell-customer__avatar {
  width: 2rem; height: 2rem; border-radius: 50%;
  background: #f0efed; color: #555; font-size: 0.6875rem;
  font-weight: 700; display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.cell-customer__name { margin: 0; font-weight: 550; color: #1a1a18; font-size: 0.875rem; }
.cell-customer__code { margin: 0; font-size: 0.6875rem; color: #999; font-family: ui-monospace, monospace; }

.cell-contact { display: flex; flex-direction: column; gap: 0.125rem; }
.cell-contact__item { font-size: 0.8125rem; color: #555; }

.cell-amount { font-weight: 600; }
.cell-date { font-size: 0.8125rem; color: #666; }

.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.form-grid__full { grid-column: 1 / -1; }

@media (max-width: 480px) {
  .form-grid { grid-template-columns: 1fr; }
}
</style>
