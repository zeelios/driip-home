<template>
  <div>
    <!-- Toolbar -->
    <div
      class="flex flex-col sm:flex-row items-start justify-between gap-3 mb-4 sm:mb-4.5"
    >
      <ZInput
        v-model="search"
        placeholder="Tìm tên, email, điện thoại..."
        type="search"
        size="sm"
        class="flex-1 w-full min-w-0"
        @input="onSearchInput"
      >
        <template #prefix>
          <svg
            width="15"
            height="15"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
          >
            <circle cx="11" cy="11" r="8" />
            <path d="m21 21-4.35-4.35" />
          </svg>
        </template>
      </ZInput>
      <ZButton
        size="sm"
        @click="showCreateModal = true"
        class="w-full sm:w-auto shrink-0"
      >
        <template #prefix>
          <svg
            width="14"
            height="14"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2.5"
          >
            <path d="M5 12h14M12 5v14" />
          </svg>
        </template>
        Thêm khách hàng
      </ZButton>
    </div>

    <!-- Error -->
    <div
      v-if="store.listState === 'error'"
      class="flex items-center justify-between gap-3 py-3 px-4 mb-3.5 bg-red-500/10 border border-red-500/30 rounded-lg text-sm text-red-500"
    >
      <span>{{ store.listError }}</span>
      <ZButton variant="ghost" size="sm" @click="store.fetchCustomers()"
        >Thử lại</ZButton
      >
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
        <div class="flex items-center gap-2.5">
          <div
            class="w-8 h-8 rounded-full bg-white/10 text-white/80 text-[0.6875rem] font-bold flex items-center justify-center shrink-0"
          >
            {{ initials(row as CustomerRow) }}
          </div>
          <div>
            <p class="m-0 font-semibold text-white/90 text-sm">
              {{ fullName(row as CustomerRow) }}
            </p>
            <p class="m-0 text-[0.6875rem] text-white/50 font-mono">
              {{ (row as CustomerRow).customer_code }}
            </p>
          </div>
        </div>
      </template>
      <template #cell-contact="{ row }">
        <div class="flex flex-col gap-0.5">
          <span
            v-if="(row as CustomerRow).email"
            class="text-[0.8125rem] text-white/60"
            >{{ (row as CustomerRow).email }}</span
          >
          <span
            v-if="(row as CustomerRow).phone"
            class="text-[0.8125rem] text-white/60"
            >{{ (row as CustomerRow).phone }}</span
          >
        </div>
      </template>
      <template #cell-status="{ row }">
        <ZBadge v-if="(row as CustomerRow).is_blocked" variant="danger"
          >Đã khóa</ZBadge
        >
        <ZBadge v-else variant="success">Hoạt động</ZBadge>
      </template>
      <template #cell-total_spent="{ row }">
        <span class="font-semibold">{{
          formatVnd((row as CustomerRow).total_spent)
        }}</span>
      </template>
      <template #cell-total_orders="{ row }">
        {{ (row as CustomerRow).total_orders }}
      </template>
      <template #cell-loyalty="{ row }">
        <ZBadge
          v-if="(row as CustomerRow).loyaltyAccount?.tier"
          :variant="'default'"
          size="sm"
        >
          {{ (row as CustomerRow).loyaltyAccount!.tier!.name }}
        </ZBadge>
        <span v-else class="text-white/40 text-sm">—</span>
      </template>
      <template #cell-created_at="{ row }">
        <span class="text-[0.8125rem] text-white/50">{{
          formatDate((row as CustomerRow).created_at)
        }}</span>
      </template>
    </ZTable>

    <!-- Pagination -->
    <div class="flex items-center justify-between gap-3 pt-4 flex-wrap">
      <p class="m-0 text-[0.8125rem] text-white/40">
        {{ store.meta.total }} khách hàng
      </p>
      <ZPagination
        :current-page="store.meta.current_page"
        :total-pages="store.meta.last_page"
        @change="onPageChange"
      />
    </div>

    <!-- Create modal -->
    <ZModal v-model="showCreateModal" title="Thêm khách hàng mới" size="md">
      <div
        class="grid grid-cols-2 gap-4 [&>*:nth-child(5)]:col-span-full max-sm:grid-cols-1"
      >
        <ZInput
          v-model="form.first_name"
          label="Họ *"
          placeholder="Nguyễn"
          size="sm"
          :error="formErrors.first_name"
          autocomplete="given-name"
        />
        <ZInput
          v-model="form.last_name"
          label="Tên *"
          placeholder="Văn An"
          size="sm"
          :error="formErrors.last_name"
          autocomplete="family-name"
        />
        <ZInput
          v-model="form.email"
          label="Email"
          type="email"
          placeholder="khach@email.com"
          size="sm"
          :error="formErrors.email"
          autocomplete="email"
        />
        <ZInput
          v-model="form.phone"
          label="Điện thoại"
          type="tel"
          placeholder="0901234567"
          size="sm"
          :error="formErrors.phone"
          autocomplete="tel"
        />
        <ZSelect
          v-model="form.gender"
          :options="genderOptions"
          label="Giới tính"
          placeholder="Chọn giới tính"
          size="sm"
        />
      </div>
      <template #footer>
        <ZButton
          variant="outline"
          size="sm"
          :disabled="store.formPending"
          @click="closeCreateModal"
          >Hủy</ZButton
        >
        <ZButton
          variant="primary"
          size="sm"
          :loading="store.formPending"
          @click="handleCreate"
          >Tạo khách hàng</ZButton
        >
      </template>
    </ZModal>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from "vue";
import { useCustomersStore } from "~/stores/customers";
import {
  formatVnd,
  formatDate,
  sanitizeString,
  sanitizeEmail,
} from "~/utils/format";
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
  loyaltyAccount?: {
    tier?: {
      name: string;
      color?: string | null;
    } | null;
    points_balance?: number;
  } | null;
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
  { key: "loyalty", label: "Hạng", skeletonWidth: "80px" },
  { key: "status", label: "Trạng thái", skeletonWidth: "80px" },
  {
    key: "total_orders",
    label: "Đơn hàng",
    align: "center",
    skeletonWidth: "50px",
  },
  {
    key: "total_spent",
    label: "Chi tiêu",
    align: "right",
    skeletonWidth: "100px",
  },
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
  formErrors.first_name = sanitizeString(form.first_name)
    ? ""
    : "Họ là bắt buộc";
  formErrors.last_name = sanitizeString(form.last_name)
    ? ""
    : "Tên là bắt buộc";
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
