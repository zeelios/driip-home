<template>
  <div>
    <!-- Loading skeleton -->
    <template v-if="store.isDetailLoading">
      <div class="detail-header-skeleton">
        <div class="flex gap-3 items-center">
          <ZSkeleton variant="circle" width="3.5rem" height="3.5rem" />
          <div class="flex flex-col gap-1" style="flex:1">
            <ZSkeleton height="1.25rem" width="160px" />
            <ZSkeleton height="0.875rem" width="100px" />
          </div>
        </div>
        <ZSkeleton height="2.25rem" width="140px" />
      </div>
      <div class="detail-grid">
        <div class="detail-card" v-for="i in 2" :key="i">
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
        <ZButton variant="outline" size="sm" @click="store.fetchStaffMember(id)">Thử lại</ZButton>
      </template>
    </ZEmptyState>

    <!-- Content -->
    <template v-else-if="store.currentStaff">
      <!-- Page header -->
      <div class="detail-page-header">
        <div class="detail-page-header__left">
          <NuxtLink to="/staff" class="detail-back">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
            Nhân viên
          </NuxtLink>
          <div class="staff-identity">
            <div class="staff-avatar">{{ staffInitials }}</div>
            <div>
              <h1 class="detail-page-title">{{ staff.name }}</h1>
              <p class="staff-code">{{ staff.employee_code ?? '—' }}</p>
            </div>
          </div>
          <ZBadge :variant="staffStatusVariant(staff.status) as BadgeVariant">
            {{ staffStatusLabel(staff.status) }}
          </ZBadge>
          <div v-if="staff.roles?.length" class="staff-roles">
            <ZBadge
              v-for="role in (staff.roles as RoleItem[])"
              :key="typeof role === 'string' ? role : role.name ?? ''"
              variant="info"
            >
              {{ roleDisplayLabel(role) }}
            </ZBadge>
          </div>
        </div>
        <div class="detail-page-header__actions">
          <ZButton variant="outline" size="sm" @click="openEditModal">Chỉnh sửa</ZButton>
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
      <div class="detail-grid">
        <!-- Contact info -->
        <div class="detail-card">
          <p class="detail-card__title">Thông tin liên hệ</p>
          <dl class="detail-dl">
            <div class="detail-dl__row">
              <dt>Email</dt>
              <dd>{{ staff.email }}</dd>
            </div>
            <div class="detail-dl__row">
              <dt>Điện thoại</dt>
              <dd>{{ staff.phone ?? '—' }}</dd>
            </div>
            <div class="detail-dl__row">
              <dt>Phòng ban</dt>
              <dd>{{ staff.department ?? '—' }}</dd>
            </div>
            <div class="detail-dl__row">
              <dt>Chức vụ</dt>
              <dd>{{ staff.position ?? '—' }}</dd>
            </div>
          </dl>
        </div>

        <!-- Employment info -->
        <div class="detail-card">
          <p class="detail-card__title">Thông tin công việc</p>
          <dl class="detail-dl">
            <div class="detail-dl__row">
              <dt>Ngày vào làm</dt>
              <dd>{{ formatDate(staff.hired_at) }}</dd>
            </div>
            <div class="detail-dl__row">
              <dt>Ngày nghỉ việc</dt>
              <dd>{{ formatDate(staff.terminated_at) }}</dd>
            </div>
            <div class="detail-dl__row">
              <dt>Ngày tạo TK</dt>
              <dd>{{ formatDate(staff.created_at) }}</dd>
            </div>
          </dl>
        </div>

        <!-- Notes -->
        <div class="detail-card" v-if="staff.notes">
          <p class="detail-card__title">Ghi chú</p>
          <p class="detail-note-text">{{ staff.notes }}</p>
        </div>
      </div>
    </template>

    <!-- Edit modal -->
    <ZModal v-model="showEditModal" title="Chỉnh sửa nhân viên" size="md">
      <div class="form-grid">
        <ZInput
          v-model="editForm.name"
          label="Họ và tên *"
          :error="editErrors.name"
          class="form-grid__full"
        />
        <ZInput
          v-model="editForm.email"
          label="Email *"
          type="email"
          :error="editErrors.email"
        />
        <ZInput
          v-model="editForm.phone"
          label="Điện thoại"
          type="tel"
        />
        <ZInput v-model="editForm.department" label="Phòng ban" />
        <ZInput v-model="editForm.position" label="Chức vụ" />
      </div>
      <template #footer>
        <ZButton variant="outline" size="sm" :disabled="store.formPending" @click="showEditModal = false">Hủy</ZButton>
        <ZButton variant="primary" size="sm" :loading="store.formPending" @click="handleUpdate">Lưu thay đổi</ZButton>
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
import { formatDate, staffStatusLabel, staffStatusVariant, sanitizeString, sanitizeEmail } from "~/utils/format";

definePageMeta({ layout: "panel" });

type BadgeVariant = "default" | "success" | "warning" | "danger" | "info" | "neutral" | "amber";
interface RoleItem { name?: string }

const route = useRoute();
const id = route.params.id as string;
const store = useStaffStore();

const showEditModal = ref(false);
const showStatusConfirm = ref(false);
const pendingStatus = ref<string | null>(null);

const ERROR_ICON = `<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`;

const staff = computed(() => store.currentStaff!);

const staffInitials = computed(() => {
  const name = store.currentStaff?.name ?? "";
  return name.split(" ").slice(0, 2).map((w) => w[0]?.toUpperCase() ?? "").join("");
});

const statusConfirmTitle = computed(() =>
  pendingStatus.value === "active" ? "Kích hoạt lại tài khoản" : "Ngừng hoạt động"
);
const statusConfirmMessage = computed(() =>
  pendingStatus.value === "active"
    ? "Nhân viên này sẽ có thể đăng nhập và sử dụng hệ thống lại."
    : "Nhân viên này sẽ không thể đăng nhập vào hệ thống."
);
const statusConfirmLabel = computed(() =>
  pendingStatus.value === "active" ? "Kích hoạt" : "Ngừng hoạt động"
);

const editForm = reactive({ name: "", email: "", phone: "", department: "", position: "" });
const editErrors = reactive({ name: "", email: "" });

function roleDisplayLabel(role: RoleItem | string): string {
  const name = typeof role === "string" ? role : (role.name ?? "");
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

<style scoped>
.detail-page-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
}
.detail-page-header__left {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.75rem;
}
.detail-back {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.8125rem;
  color: #888;
  text-decoration: none;
  transition: color 130ms;
}
.detail-back:hover { color: #1a1a18; }
.detail-page-header__actions { display: flex; gap: 0.5rem; flex-wrap: wrap; }

.staff-identity { display: flex; align-items: center; gap: 0.75rem; }
.staff-avatar {
  width: 3rem; height: 3rem; border-radius: 50%;
  background: #111110; color: #f5a623;
  font-size: 0.9375rem; font-weight: 700;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.detail-page-title {
  margin: 0;
  font-size: 1.125rem;
  font-weight: 700;
  color: #1a1a18;
}
.staff-code {
  margin: 0;
  font-size: 0.6875rem;
  color: #999;
  font-family: ui-monospace, monospace;
}
.staff-roles { display: flex; gap: 0.375rem; flex-wrap: wrap; }

.detail-header-skeleton {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.5rem;
}

.detail-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 0.875rem;
}
@media (min-width: 768px) {
  .detail-grid { grid-template-columns: repeat(3, 1fr); }
}

.detail-card {
  background: #fff;
  border: 1px solid rgba(0,0,0,0.07);
  border-radius: 10px;
  padding: 1.125rem;
}
.detail-card__title {
  margin: 0 0 0.875rem;
  font-size: 0.6875rem;
  font-weight: 700;
  letter-spacing: 0.07em;
  text-transform: uppercase;
  color: #888;
}

.detail-dl { margin: 0; }
.detail-dl__row {
  display: flex;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.3125rem 0;
  border-bottom: 1px solid rgba(0,0,0,0.05);
  font-size: 0.875rem;
}
.detail-dl__row:last-child { border-bottom: none; }
.detail-dl__row dt { color: #888; flex-shrink: 0; }
.detail-dl__row dd { margin: 0; color: #1a1a18; text-align: right; word-break: break-word; }
.detail-note-text { margin: 0; font-size: 0.875rem; color: #555; line-height: 1.55; }

.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.form-grid__full { grid-column: 1 / -1; }
@media (max-width: 480px) { .form-grid { grid-template-columns: 1fr; } }

.flex { display: flex; }
.items-center { align-items: center; }
.gap-1 { gap: 0.25rem; }
.gap-3 { gap: 0.75rem; }
.flex-col { flex-direction: column; }
.mb-1 { margin-bottom: 0.25rem; }
.mb-2 { margin-bottom: 0.5rem; }
</style>
