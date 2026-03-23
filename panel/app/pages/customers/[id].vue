<template>
  <div>
    <!-- Loading skeleton -->
    <template v-if="store.isDetailLoading">
      <div class="detail-header-skeleton">
        <div class="flex items-center gap-3">
          <ZSkeleton variant="circle" width="3.5rem" height="3.5rem" />
          <div style="display:flex;flex-direction:column;gap:0.375rem;flex:1">
            <ZSkeleton height="1.25rem" width="160px" />
            <ZSkeleton height="0.875rem" width="100px" />
          </div>
        </div>
        <ZSkeleton height="2.25rem" width="120px" />
      </div>
      <div class="detail-grid">
        <div class="detail-card" v-for="i in 2" :key="i">
          <ZSkeleton height="0.75rem" width="40%" class="mb-2" />
          <ZSkeleton height="1rem" width="80%" class="mb-1" />
          <ZSkeleton height="1rem" width="65%" class="mb-1" />
          <ZSkeleton height="1rem" width="70%" />
        </div>
      </div>
    </template>

    <!-- Error -->
    <ZEmptyState
      v-else-if="store.detailState === 'error'"
      title="Không thể tải khách hàng"
      :description="store.detailError ?? ''"
      :icon="ERROR_ICON"
    >
      <template #action>
        <ZButton variant="outline" size="sm" @click="store.fetchCustomer(id)">Thử lại</ZButton>
      </template>
    </ZEmptyState>

    <!-- Content -->
    <template v-else-if="store.currentCustomer">
      <!-- Page header -->
      <div class="detail-page-header">
        <div class="detail-page-header__left">
          <NuxtLink to="/customers" class="detail-back">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
            Khách hàng
          </NuxtLink>
          <div class="customer-identity">
            <div class="customer-avatar">{{ customerInitials }}</div>
            <div>
              <h1 class="detail-page-title">{{ customerFullName }}</h1>
              <p class="customer-code">{{ customer.customer_code }}</p>
            </div>
          </div>
          <ZBadge v-if="customer.is_blocked" variant="danger">Đã khóa</ZBadge>
          <ZBadge v-else variant="success">Hoạt động</ZBadge>
        </div>
        <div class="detail-page-header__actions">
          <ZButton variant="outline" size="sm" @click="openEditModal">Chỉnh sửa</ZButton>
          <ZButton
            v-if="!customer.is_blocked"
            variant="danger"
            size="sm"
            :loading="store.blockPending"
            @click="showBlockConfirm = true"
          >
            Khóa tài khoản
          </ZButton>
        </div>
      </div>

      <!-- Stats bar -->
      <div class="customer-stats">
        <div class="customer-stat">
          <p class="customer-stat__label">Đơn hàng</p>
          <p class="customer-stat__value">{{ customer.total_orders }}</p>
        </div>
        <div class="customer-stat">
          <p class="customer-stat__label">Tổng chi tiêu</p>
          <p class="customer-stat__value">{{ formatVnd(customer.total_spent) }}</p>
        </div>
        <div class="customer-stat">
          <p class="customer-stat__label">Đặt hàng gần đây</p>
          <p class="customer-stat__value">{{ formatDate(customer.last_ordered_at) }}</p>
        </div>
        <div class="customer-stat">
          <p class="customer-stat__label">Ngày tạo</p>
          <p class="customer-stat__value">{{ formatDate(customer.created_at) }}</p>
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
              <dd>{{ customer.email ?? '—' }}</dd>
            </div>
            <div class="detail-dl__row">
              <dt>Điện thoại</dt>
              <dd>{{ customer.phone ?? '—' }}</dd>
            </div>
            <div class="detail-dl__row">
              <dt>Giới tính</dt>
              <dd>{{ genderLabel(customer.gender) }}</dd>
            </div>
            <div class="detail-dl__row">
              <dt>Nguồn</dt>
              <dd>{{ customer.source ?? '—' }}</dd>
            </div>
          </dl>
        </div>

        <!-- Loyalty info -->
        <div class="detail-card" v-if="customer.loyaltyAccount">
          <p class="detail-card__title">Điểm tích lũy</p>
          <dl class="detail-dl">
            <div class="detail-dl__row">
              <dt>Số dư điểm</dt>
              <dd class="font-bold">{{ formatNumber(customer.loyaltyAccount.points_balance) }} điểm</dd>
            </div>
            <div class="detail-dl__row">
              <dt>Điểm vĩnh cửu</dt>
              <dd>{{ formatNumber(customer.loyaltyAccount.lifetime_points) }}</dd>
            </div>
            <div class="detail-dl__row">
              <dt>Hạng</dt>
              <dd>{{ customer.loyaltyAccount.tier?.name ?? '—' }}</dd>
            </div>
          </dl>
        </div>

        <!-- Notes -->
        <div class="detail-card" v-if="customer.notes">
          <p class="detail-card__title">Ghi chú</p>
          <p class="detail-note-text">{{ customer.notes }}</p>
        </div>
      </div>
    </template>

    <!-- Edit modal -->
    <ZModal v-model="showEditModal" title="Chỉnh sửa khách hàng" size="md">
      <div class="form-grid">
        <ZInput v-model="editForm.first_name" label="Họ *" placeholder="Nguyễn" :error="editErrors.first_name" />
        <ZInput v-model="editForm.last_name" label="Tên *" placeholder="Văn An" :error="editErrors.last_name" />
        <ZInput v-model="editForm.email" label="Email" type="email" placeholder="khach@email.com" :error="editErrors.email" />
        <ZInput v-model="editForm.phone" label="Điện thoại" type="tel" placeholder="0901234567" />
        <ZSelect v-model="editForm.gender" :options="genderOptions" label="Giới tính" placeholder="Chọn giới tính" class="form-grid__full" />
      </div>
      <template #footer>
        <ZButton variant="outline" size="sm" :disabled="store.formPending" @click="showEditModal = false">Hủy</ZButton>
        <ZButton variant="primary" size="sm" :loading="store.formPending" @click="handleUpdate">Lưu thay đổi</ZButton>
      </template>
    </ZModal>

    <!-- Block confirm -->
    <ZConfirmDialog
      v-model="showBlockConfirm"
      title="Khóa tài khoản"
      message="Bạn có chắc muốn khóa tài khoản này? Khách hàng sẽ không thể đặt hàng."
      confirm-label="Khóa tài khoản"
      :dangerous="true"
      :loading="store.blockPending"
      @confirm="handleBlock"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from "vue";
import { useRoute } from "vue-router";
import { useCustomersStore } from "~/stores/customers";
import { formatVnd, formatNumber, formatDate, genderLabel, sanitizeString, sanitizeEmail } from "~/utils/format";
import type { SelectOption } from "~/components/z/Select.vue";

definePageMeta({ layout: "panel" });

const route = useRoute();
const id = route.params.id as string;
const store = useCustomersStore();

const showEditModal = ref(false);
const showBlockConfirm = ref(false);

const ERROR_ICON = `<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`;

const customer = computed(() => store.currentCustomer!);

const customerFullName = computed(() => {
  const c = store.currentCustomer;
  if (!c) return "";
  return `${c.first_name} ${c.last_name}`.trim();
});

const customerInitials = computed(() => {
  const c = store.currentCustomer;
  if (!c) return "";
  return ((c.first_name[0] ?? "") + (c.last_name[0] ?? "")).toUpperCase();
});

const editForm = reactive({ first_name: "", last_name: "", email: "", phone: "", gender: "" });
const editErrors = reactive({ first_name: "", last_name: "", email: "" });

const genderOptions: SelectOption[] = [
  { value: "male", label: "Nam" },
  { value: "female", label: "Nữ" },
  { value: "other", label: "Khác" },
];

function openEditModal(): void {
  const c = store.currentCustomer;
  if (!c) return;
  editForm.first_name = c.first_name;
  editForm.last_name = c.last_name;
  editForm.email = c.email ?? "";
  editForm.phone = c.phone ?? "";
  editForm.gender = c.gender ?? "";
  editErrors.first_name = "";
  editErrors.last_name = "";
  editErrors.email = "";
  showEditModal.value = true;
}

function validateEditForm(): boolean {
  editErrors.first_name = sanitizeString(editForm.first_name) ? "" : "Họ là bắt buộc";
  editErrors.last_name = sanitizeString(editForm.last_name) ? "" : "Tên là bắt buộc";
  editErrors.email = editForm.email && !sanitizeEmail(editForm.email) ? "Email không hợp lệ" : "";
  return !editErrors.first_name && !editErrors.last_name && !editErrors.email;
}

async function handleUpdate(): Promise<void> {
  if (!validateEditForm()) return;
  const ok = await store.updateCustomer(id, {
    first_name: editForm.first_name,
    last_name: editForm.last_name,
    email: editForm.email || null,
    phone: editForm.phone || null,
    gender: editForm.gender || null,
  });
  if (ok) showEditModal.value = false;
}

async function handleBlock(): Promise<void> {
  const ok = await store.blockCustomer(id);
  if (ok) showBlockConfirm.value = false;
}

onMounted(() => {
  store.fetchCustomer(id);
});
</script>

<style scoped>
.detail-page-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1.25rem;
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

.customer-identity { display: flex; align-items: center; gap: 0.75rem; }
.customer-avatar {
  width: 3rem; height: 3rem; border-radius: 50%;
  background: #f0efed; color: #333;
  font-size: 1rem; font-weight: 700;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.detail-page-title {
  margin: 0;
  font-size: 1.125rem;
  font-weight: 700;
  color: #1a1a18;
}
.customer-code {
  margin: 0;
  font-size: 0.6875rem;
  color: #999;
  font-family: ui-monospace, monospace;
}

.customer-stats {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.75rem;
  margin-bottom: 1.25rem;
}
@media (min-width: 640px) {
  .customer-stats { grid-template-columns: repeat(4, 1fr); }
}
.customer-stat {
  background: #fff;
  border: 1px solid rgba(0,0,0,0.07);
  border-radius: 10px;
  padding: 1rem;
}
.customer-stat__label {
  margin: 0 0 0.25rem;
  font-size: 0.6875rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.07em;
  color: #888;
}
.customer-stat__value {
  margin: 0;
  font-size: 1.125rem;
  font-weight: 700;
  color: #1a1a18;
}

.detail-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 0.875rem;
}
@media (min-width: 768px) {
  .detail-grid { grid-template-columns: repeat(3, 1fr); }
}

.detail-header-skeleton {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.5rem;
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

.font-bold { font-weight: 700; }
.flex { display: flex; }
.items-center { align-items: center; }
.gap-3 { gap: 0.75rem; }
.mb-1 { margin-bottom: 0.25rem; }
.mb-2 { margin-bottom: 0.5rem; }
</style>
