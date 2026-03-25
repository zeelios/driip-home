<template>
  <div>
    <!-- Toolbar -->
    <div class="flex items-start justify-between gap-3 mb-4.5 flex-wrap">
      <div class="flex gap-2.5 flex-1 flex-wrap">
        <ZInput
          v-model="search"
          placeholder="Tìm tên, email..."
          type="search"
          class="flex-1 min-w-[180px] max-w-[280px]"
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
        <ZSelect
          v-model="statusFilter"
          :options="statusOptions"
          placeholder="Tất cả trạng thái"
          class="min-w-[150px]"
          @change="onFilterChange"
        />
      </div>
      <ZButton size="sm" @click="showCreateModal = true">
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
        Thêm nhân viên
      </ZButton>
    </div>

    <!-- Error -->
    <div
      v-if="store.listState === 'error'"
      class="flex items-center justify-between gap-3 py-3 px-4 mb-3.5 bg-red-500/10 border border-red-500/30 rounded-lg text-sm text-red-500"
    >
      <span>{{ store.listError }}</span>
      <ZButton variant="ghost" size="sm" @click="store.fetchStaff()"
        >Thử lại</ZButton
      >
    </div>

    <!-- Table -->
    <ZTable
      :columns="columns"
      :rows="store.staffList"
      :loading="store.isListLoading"
      :skeleton-rows="12"
      row-key="id"
      empty-title="Không có nhân viên"
      empty-description="Chưa có nhân viên nào khớp với bộ lọc."
      :on-row-click="(row) => navigateTo(`/staff/${(row as StaffRow).id}`)"
    >
      <template #cell-name="{ row }">
        <div class="flex items-center gap-2.5">
          <div
            class="w-8 h-8 rounded-full bg-white/10 text-white/90 text-[0.6875rem] font-bold flex items-center justify-center shrink-0"
          >
            {{ initials(row as StaffRow) }}
          </div>
          <div>
            <p class="m-0 font-semibold text-white/90 text-sm">
              {{ (row as StaffRow).name }}
            </p>
            <p class="m-0 text-[0.6875rem] text-white/50 font-mono">
              {{ (row as StaffRow).employee_code ?? "—" }}
            </p>
          </div>
        </div>
      </template>
      <template #cell-contact="{ row }">
        <div class="flex flex-col gap-0.5">
          <span class="text-[0.8125rem] text-white/60">{{
            (row as StaffRow).email
          }}</span>
          <span
            v-if="(row as StaffRow).phone"
            class="text-[0.8125rem] text-white/60"
            >{{ (row as StaffRow).phone }}</span
          >
        </div>
      </template>
      <template #cell-department="{ row }">
        {{ (row as StaffRow).department ?? "—" }}
      </template>
      <template #cell-position="{ row }">
        {{ (row as StaffRow).position ?? "—" }}
      </template>
      <template #cell-roles="{ row }">
        <span class="text-white/40 text-sm">{{
          formatRoles((row as StaffRow).roles)
        }}</span>
      </template>
      <template #cell-status="{ row }">
        <ZBadge
          :variant="staffStatusVariant((row as StaffRow).status) as BadgeVariant"
        >
          {{ staffStatusLabel((row as StaffRow).status) }}
        </ZBadge>
      </template>
      <template #cell-hired_at="{ row }">
        <span class="text-[0.8125rem] text-white/50">{{
          formatDate((row as StaffRow).hired_at)
        }}</span>
      </template>
    </ZTable>

    <!-- Pagination -->
    <div class="flex items-center justify-between gap-3 pt-4 flex-wrap">
      <p class="m-0 text-[0.8125rem] text-white/40">
        {{ store.meta.total }} nhân viên
      </p>
      <ZPagination
        :current-page="store.meta.current_page"
        :total-pages="store.meta.last_page"
        @change="onPageChange"
      />
    </div>

    <!-- Create modal -->
    <ZModal v-model="showCreateModal" title="Thêm nhân viên mới" size="md">
      <div
        class="grid grid-cols-2 gap-4 [&>*:nth-child(1)]:col-span-full [&>*:nth-child(7)]:col-span-full max-sm:grid-cols-1"
      >
        <ZInput
          v-model="form.name"
          label="Họ và tên *"
          placeholder="Nguyễn Văn An"
          :error="formErrors.name"
          autocomplete="name"
        />
        <ZInput
          v-model="form.email"
          label="Email *"
          type="email"
          placeholder="nhanvien@driip.vn"
          :error="formErrors.email"
          autocomplete="email"
        />
        <ZInput
          v-model="form.password"
          label="Mật khẩu *"
          type="password"
          placeholder="Tối thiểu 8 ký tự"
          :error="formErrors.password"
          autocomplete="new-password"
        />
        <ZInput
          v-model="form.phone"
          label="Điện thoại"
          type="tel"
          placeholder="0901234567"
          autocomplete="tel"
        />
        <ZInput
          v-model="form.department"
          label="Phòng ban"
          placeholder="Sales, Kho, Kế toán..."
        />
        <ZInput
          v-model="form.position"
          label="Chức vụ"
          placeholder="Nhân viên, Trưởng nhóm..."
        />
        <ZInput v-model="form.hired_at" label="Ngày vào làm" type="date" />
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
          >Tạo nhân viên</ZButton
        >
      </template>
    </ZModal>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from "vue";
import { useStaffStore } from "~/stores/staff";
import {
  formatDate,
  staffStatusLabel,
  staffStatusVariant,
  sanitizeString,
  sanitizeEmail,
} from "~/utils/format";
import type { TableColumn } from "~/components/z/Table.vue";
import type { SelectOption } from "~/components/z/Select.vue";

definePageMeta({ layout: "panel" });

interface StaffRow {
  id: string;
  employee_code: string | null;
  name: string;
  email: string;
  phone: string | null;
  department: string | null;
  position: string | null;
  status: string;
  hired_at: string | null;
  roles?: { name: string }[];
}

type BadgeVariant =
  | "default"
  | "success"
  | "warning"
  | "danger"
  | "info"
  | "neutral";

const store = useStaffStore();
const search = ref(store.filters.search);
const statusFilter = ref(store.filters.status);
const showCreateModal = ref(false);

let searchTimer: ReturnType<typeof setTimeout> | null = null;

const form = reactive({
  name: "",
  email: "",
  password: "",
  phone: "",
  department: "",
  position: "",
  hired_at: "",
});

const formErrors = reactive({ name: "", email: "", password: "" });

const columns: TableColumn[] = [
  { key: "name", label: "Nhân viên", skeletonWidth: "180px" },
  { key: "contact", label: "Liên hệ", skeletonWidth: "160px" },
  { key: "department", label: "Phòng ban", skeletonWidth: "100px" },
  { key: "roles", label: "Vai trò", skeletonWidth: "120px" },
  { key: "status", label: "Trạng thái", skeletonWidth: "90px" },
  { key: "hired_at", label: "Ngày vào làm", skeletonWidth: "100px" },
];

const statusOptions: SelectOption[] = [
  { value: "active", label: "Đang làm việc" },
  { value: "inactive", label: "Nghỉ việc" },
  { value: "suspended", label: "Tạm dừng" },
];

function formatRoles(roles: { name: string }[] | undefined): string {
  if (!roles || roles.length === 0) return "—";
  return roles.map((r) => r.name).join(", ");
}

function initials(row: StaffRow): string {
  return row.name
    .split(" ")
    .slice(0, 2)
    .map((w) => w[0]?.toUpperCase() ?? "")
    .join("");
}

function validateForm(): boolean {
  let valid = true;
  formErrors.name = sanitizeString(form.name) ? "" : "Họ tên là bắt buộc";
  formErrors.email = sanitizeEmail(form.email) ? "" : "Email không hợp lệ";
  formErrors.password =
    form.password.length >= 8 ? "" : "Mật khẩu tối thiểu 8 ký tự";
  if (formErrors.name || formErrors.email || formErrors.password) valid = false;
  return valid;
}

async function handleCreate(): Promise<void> {
  if (!validateForm()) return;
  const ok = await store.createStaffMember({
    name: form.name,
    email: form.email,
    password: form.password,
    phone: form.phone || null,
    department: form.department || null,
    position: form.position || null,
    hired_at: form.hired_at || null,
  });
  if (ok) closeCreateModal();
}

function closeCreateModal(): void {
  showCreateModal.value = false;
  form.name = "";
  form.email = "";
  form.password = "";
  form.phone = "";
  form.department = "";
  form.position = "";
  form.hired_at = "";
  formErrors.name = "";
  formErrors.email = "";
  formErrors.password = "";
}

function onSearchInput(): void {
  if (searchTimer) clearTimeout(searchTimer);
  searchTimer = setTimeout(() => {
    store.setFilters({ search: search.value });
    store.fetchStaff();
  }, 350);
}

function onFilterChange(): void {
  store.setFilters({ status: statusFilter.value });
  store.fetchStaff();
}

function onPageChange(page: number): void {
  store.setPage(page);
  store.fetchStaff();
}

onMounted(() => {
  store.fetchStaff();
});
</script>
