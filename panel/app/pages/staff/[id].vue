<template>
  <div>
    <!-- Loading skeleton -->
    <template v-if="store.isDetailLoading">
      <div class="flex items-center justify-between mb-6">
        <div class="flex gap-3 items-center">
          <ZSkeleton variant="circle" width="3.5rem" height="3.5rem" />
          <div class="flex flex-col gap-1 flex-1">
            <ZSkeleton height="1.25rem" width="160px" />
            <ZSkeleton height="0.875rem" width="100px" />
          </div>
        </div>
        <ZSkeleton height="2.25rem" width="140px" />
      </div>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5">
        <div
          class="bg-[#111111] border border-white/8 rounded-[10px] p-4.5"
          v-for="i in 2"
          :key="i"
        >
          <ZSkeleton height="0.75rem" width="40%" class="mb-2" />
          <ZSkeleton height="1rem" width="80%" class="mb-1" />
          <ZSkeleton height="1rem" width="65%" class="mb-1" />
          <ZSkeleton height="1rem" width="55%" />
        </div>
      </div>
    </template>

    <!-- Error -->
    <ZEmptyState
      v-else-if="store.detailState === 'error'"
      title="Không thể tải nhân viên"
      :description="store.detailError ?? ''"
      :icon="ERROR_ICON"
    >
      <template #action>
        <ZButton variant="outline" size="sm" @click="store.fetchStaffMember(id)"
          >Thử lại</ZButton
        >
      </template>
    </ZEmptyState>

    <!-- Content -->
    <template v-else-if="store.currentStaff">
      <!-- Page header -->
      <div class="flex items-start justify-between gap-4 mb-6 flex-wrap">
        <div class="flex items-center flex-wrap gap-3">
          <NuxtLink
            to="/staff"
            class="inline-flex items-center gap-1 text-[0.8125rem] text-white/50 no-underline transition-colors duration-130 hover:text-white/80"
          >
            <svg
              width="14"
              height="14"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2.5"
            >
              <polyline points="15 18 9 12 15 6" />
            </svg>
            Nhân viên
          </NuxtLink>
          <div class="flex items-center gap-3">
            <div
              class="w-12 h-12 rounded-full bg-white/10 text-white/90 text-[0.9375rem] font-bold flex items-center justify-center shrink-0"
            >
              {{ staffInitials }}
            </div>
            <div>
              <h1 class="m-0 text-lg font-bold text-white/95">
                {{ staff.name }}
              </h1>
              <p class="m-0 text-[0.6875rem] text-white/50 font-mono">
                {{ staff.employee_code ?? "—" }}
              </p>
            </div>
          </div>
          <ZBadge :variant="staffStatusVariant(staff.status) as BadgeVariant">
            {{ staffStatusLabel(staff.status) }}
          </ZBadge>
          <div v-if="staff.roles?.length" class="flex gap-1.5 flex-wrap">
            <ZBadge
              v-for="role in (staff.roles as RoleItem[])"
              :key="typeof role === 'string' ? role : role.name ?? ''"
              variant="info"
            >
              {{ roleDisplayLabel(role) }}
            </ZBadge>
          </div>
        </div>
        <div class="flex gap-2 flex-wrap">
          <ZButton variant="outline" size="sm" @click="openEditModal"
            >Chỉnh sửa</ZButton
          >
          <ZButton
            v-if="staff.status === 'active'"
            variant="danger"
            size="sm"
            @click="openStatusChange('inactive')"
          >
            Ngừng hoạt động
          </ZButton>
          <ZButton
            v-else-if="staff.status !== 'active'"
            variant="outline"
            size="sm"
            @click="openStatusChange('active')"
          >
            Kích hoạt lại
          </ZButton>
        </div>
      </div>

      <!-- Detail grid -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5">
        <!-- Contact info -->
        <div class="bg-[#111111] border border-white/8 rounded-[10px] p-4.5">
          <p
            class="m-0 mb-3.5 text-[0.6875rem] font-bold tracking-[0.07em] uppercase text-white/50"
          >
            Thông tin liên hệ
          </p>
          <dl class="m-0">
            <div
              class="flex justify-between gap-3 py-1.25 border-b border-white/6 text-sm last:border-b-0"
            >
              <dt class="text-white/50 shrink-0">Email</dt>
              <dd class="m-0 text-white/85 text-right wrap-break-word">
                {{ staff.email }}
              </dd>
            </div>
            <div
              class="flex justify-between gap-3 py-1.25 border-b border-white/6 text-sm last:border-b-0"
            >
              <dt class="text-white/50 shrink-0">Điện thoại</dt>
              <dd class="m-0 text-white/85 text-right wrap-break-word">
                {{ staff.phone ?? "—" }}
              </dd>
            </div>
            <div
              class="flex justify-between gap-3 py-1.25 border-b border-white/6 text-sm last:border-b-0"
            >
              <dt class="text-white/50 shrink-0">Phòng ban</dt>
              <dd class="m-0 text-white/85 text-right wrap-break-word">
                {{ staff.department ?? "—" }}
              </dd>
            </div>
            <div
              class="flex justify-between gap-3 py-1.25 border-b border-white/6 text-sm last:border-b-0"
            >
              <dt class="text-white/50 shrink-0">Chức vụ</dt>
              <dd class="m-0 text-white/85 text-right wrap-break-word">
                {{ staff.position ?? "—" }}
              </dd>
            </div>
          </dl>
        </div>

        <!-- Employment info -->
        <div class="bg-[#111111] border border-white/8 rounded-[10px] p-4.5">
          <p
            class="m-0 mb-3.5 text-[0.6875rem] font-bold tracking-[0.07em] uppercase text-white/50"
          >
            Thông tin công việc
          </p>
          <dl class="m-0">
            <div
              class="flex justify-between gap-3 py-1.25 border-b border-white/6 text-sm last:border-b-0"
            >
              <dt class="text-white/50 shrink-0">Ngày vào làm</dt>
              <dd class="m-0 text-white/85 text-right wrap-break-word">
                {{ formatDate(staff.hired_at) }}
              </dd>
            </div>
            <div
              class="flex justify-between gap-3 py-1.25 border-b border-white/6 text-sm last:border-b-0"
            >
              <dt class="text-white/50 shrink-0">Ngày nghỉ việc</dt>
              <dd class="m-0 text-white/85 text-right wrap-break-word">
                {{ formatDate(staff.terminated_at) }}
              </dd>
            </div>
            <div
              class="flex justify-between gap-3 py-1.25 border-b border-white/6 text-sm last:border-b-0"
            >
              <dt class="text-white/50 shrink-0">Ngày tạo TK</dt>
              <dd class="m-0 text-white/85 text-right wrap-break-word">
                {{ formatDate(staff.created_at) }}
              </dd>
            </div>
          </dl>
        </div>

        <!-- Referral link -->
        <div class="bg-[#111111] border border-white/8 rounded-[10px] p-4.5">
          <p
            class="m-0 mb-3.5 text-[0.6875rem] font-bold tracking-[0.07em] uppercase text-white/50"
          >
            Link giới thiệu
          </p>
          <div class="flex flex-col gap-3">
            <p class="m-0 text-[0.8125rem] text-white/60 leading-relaxed">
              Nhân viên chia sẻ link này để nhận hoa hồng:
            </p>
            <div class="flex gap-2">
              <input
                :value="referralUrl"
                readonly
                class="flex-1 px-3.5 py-2.5 border border-white/12 rounded-lg bg-white/4 text-[0.8125rem] text-white/85 font-mono cursor-pointer outline-none min-w-0 focus:border-white/30 focus:bg-white/6"
                @click="copyReferralUrl"
              />
              <button
                class="px-4 py-2.5 border border-white/20 rounded-lg bg-white/10 text-white/90 text-xs font-semibold cursor-pointer transition-colors duration-150 whitespace-nowrap hover:bg-white/15"
                :class="{ 'bg-green-700/80 border-green-700/90': copied }"
                @click="copyReferralUrl"
              >
                {{ copied ? "Đã copy!" : "Copy" }}
              </button>
            </div>
            <p v-if="referralCodeHint" class="m-0 text-xs text-white/40">
              Mã:
              <code
                class="bg-white/8 px-1.5 py-0.5 rounded font-mono text-white/70"
                >{{ referralCodeHint }}</code
              >
              — Sửa
              <code
                class="bg-white/8 px-1.5 py-0.5 rounded font-mono text-white/70"
                >?referal=</code
              >
              trong URL để thay đổi
            </p>
          </div>
        </div>
      </div>
    </template>

    <!-- Edit modal -->
    <ZModal v-model="showEditModal" title="Chỉnh sửa nhân viên" size="md">
      <div
        class="grid grid-cols-2 gap-4 [&>*:first-child]:col-span-full max-sm:grid-cols-1"
      >
        <ZInput
          v-model="editForm.name"
          label="Họ và tên *"
          :error="editErrors.name"
        />
        <ZInput
          v-model="editForm.email"
          label="Email *"
          type="email"
          :error="editErrors.email"
        />
        <ZInput v-model="editForm.phone" label="Điện thoại" type="tel" />
        <ZInput v-model="editForm.department" label="Phòng ban" />
        <ZInput v-model="editForm.position" label="Chức vụ" />
      </div>
      <template #footer>
        <ZButton
          variant="outline"
          size="sm"
          :disabled="store.formPending"
          @click="showEditModal = false"
          >Hủy</ZButton
        >
        <ZButton
          variant="primary"
          size="sm"
          :loading="store.formPending"
          @click="handleUpdate"
          >Lưu thay đổi</ZButton
        >
      </template>
    </ZModal>

    <!-- Status change confirm -->
    <ZConfirmDialog
      v-model="showStatusConfirm"
      :title="statusConfirmTitle"
      :message="statusConfirmMessage"
      :confirm-label="statusConfirmLabel"
      :dangerous="pendingStatus === 'inactive'"
      :loading="store.formPending"
      @confirm="handleStatusChange"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from "vue";
import { useRoute } from "vue-router";
import { useStaffStore } from "~/stores/staff";
import {
  formatDate,
  staffStatusLabel,
  staffStatusVariant,
  sanitizeString,
  sanitizeEmail,
} from "~/utils/format";

definePageMeta({ layout: "panel" });

type BadgeVariant =
  | "default"
  | "success"
  | "warning"
  | "danger"
  | "info"
  | "neutral"
  | "amber";
interface RoleItem {
  name?: string;
}

const route = useRoute();
const id = route.params.id as string;
const store = useStaffStore();

const showEditModal = ref(false);
const showStatusConfirm = ref(false);
const pendingStatus = ref<string | null>(null);
const copied = ref(false);

const ERROR_ICON = `<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`;

const staff = computed(() => store.currentStaff!);

const staffInitials = computed(() => {
  const name = store.currentStaff?.name ?? "";
  return name
    .split(" ")
    .slice(0, 2)
    .map((w) => w[0]?.toUpperCase() ?? "")
    .join("");
});

const HOMEPAGE_URL = "https://driip.vn/ck-underwear";

const referralUrl = computed(() => {
  const staffCode = staff.value.employee_code?.toLowerCase() ?? "";
  const firstName = staff.value.name?.split(" ").pop()?.toLowerCase() ?? "";
  const code = staffCode || firstName || "staff";
  return `${HOMEPAGE_URL}?referal=${encodeURIComponent(code)}`;
});

const referralCodeHint = computed(() => {
  const staffCode = staff.value.employee_code?.toLowerCase() ?? "";
  const firstName = staff.value.name?.split(" ").pop()?.toLowerCase() ?? "";
  return staffCode || firstName || "staff";
});

async function copyReferralUrl(): Promise<void> {
  try {
    await navigator.clipboard.writeText(referralUrl.value);
    copied.value = true;
    window.setTimeout(() => {
      copied.value = false;
    }, 2000);
  } catch {
    // Fallback: silently fail
  }
}

const statusConfirmTitle = computed(() =>
  pendingStatus.value === "active"
    ? "Kích hoạt lại tài khoản"
    : "Ngừng hoạt động"
);
const statusConfirmMessage = computed(() =>
  pendingStatus.value === "active"
    ? "Nhân viên này sẽ có thể đăng nhập và sử dụng hệ thống lại."
    : "Nhân viên này sẽ không thể đăng nhập vào hệ thống."
);
const statusConfirmLabel = computed(() =>
  pendingStatus.value === "active" ? "Kích hoạt" : "Ngừng hoạt động"
);

const editForm = reactive({
  name: "",
  email: "",
  phone: "",
  department: "",
  position: "",
});
const editErrors = reactive({ name: "", email: "" });

function roleDisplayLabel(role: RoleItem | string): string {
  const name = typeof role === "string" ? role : role.name ?? "";
  const map: Record<string, string> = {
    "super-admin": "Quản trị viên",
    admin: "Quản trị viên",
    manager: "Quản lý",
    "warehouse-staff": "Nhân viên kho",
    "sales-staff": "Nhân viên bán hàng",
  };
  return map[name] ?? name;
}

function openEditModal(): void {
  const s = store.currentStaff;
  if (!s) return;
  editForm.name = s.name;
  editForm.email = s.email;
  editForm.phone = s.phone ?? "";
  editForm.department = s.department ?? "";
  editForm.position = s.position ?? "";
  editErrors.name = "";
  editErrors.email = "";
  showEditModal.value = true;
}

function openStatusChange(status: string): void {
  pendingStatus.value = status;
  showStatusConfirm.value = true;
}

function validateEditForm(): boolean {
  editErrors.name = sanitizeString(editForm.name) ? "" : "Họ tên là bắt buộc";
  editErrors.email = sanitizeEmail(editForm.email) ? "" : "Email không hợp lệ";
  return !editErrors.name && !editErrors.email;
}

async function handleUpdate(): Promise<void> {
  if (!validateEditForm()) return;
  const ok = await store.updateStaffMember(id, {
    name: editForm.name,
    email: editForm.email,
    phone: editForm.phone || null,
    department: editForm.department || null,
    position: editForm.position || null,
  });
  if (ok) showEditModal.value = false;
}

async function handleStatusChange(): Promise<void> {
  if (!pendingStatus.value) return;
  const ok = await store.updateStaffMember(id, { status: pendingStatus.value });
  if (ok) {
    showStatusConfirm.value = false;
    pendingStatus.value = null;
  }
}

onMounted(() => {
  store.fetchStaffMember(id);
});
</script>
