<template>
  <div>
    <!-- Loading skeleton -->
    <template v-if="store.isDetailLoading">
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
          <ZSkeleton variant="circle" width="3.5rem" height="3.5rem" />
          <div class="flex flex-col gap-1.5 flex-1">
            <ZSkeleton height="1.25rem" width="160px" />
            <ZSkeleton height="0.875rem" width="100px" />
          </div>
        </div>
        <ZSkeleton height="2.25rem" width="120px" />
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
        <ZButton variant="outline" size="sm" @click="store.fetchCustomer(id)"
          >Thử lại</ZButton
        >
      </template>
    </ZEmptyState>

    <!-- Content -->
    <template v-else-if="store.currentCustomer">
      <!-- Page header -->
      <div class="flex items-start justify-between gap-4 mb-5 flex-wrap">
        <div class="flex items-center flex-wrap gap-3">
          <NuxtLink
            to="/customers"
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
            Khách hàng
          </NuxtLink>
          <div class="flex items-center gap-3">
            <div
              class="w-12 h-12 rounded-full bg-[#f0efed] text-[#333] text-base font-bold flex items-center justify-center shrink-0"
            >
              {{ customerInitials }}
            </div>
            <div>
              <h1 class="m-0 text-lg font-bold text-white/95">
                {{ customerFullName }}
              </h1>
              <p class="m-0 text-[0.6875rem] text-white/40 font-mono">
                {{ customer.customer_code }}
              </p>
            </div>
          </div>
          <ZBadge v-if="customer.is_blocked" variant="danger">Đã khóa</ZBadge>
          <ZBadge v-else variant="success">Hoạt động</ZBadge>
        </div>
        <div class="flex gap-2 flex-wrap">
          <ZButton variant="outline" size="sm" @click="openEditModal"
            >Chỉnh sửa</ZButton
          >
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
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
        <div class="bg-[#111111] border border-white/8 rounded-[10px] p-4">
          <p
            class="m-0 mb-1 text-[0.6875rem] font-bold uppercase tracking-[0.07em] text-white/50"
          >
            Đơn hàng
          </p>
          <p class="m-0 text-lg font-bold text-white/95">
            {{ customer.total_orders }}
          </p>
        </div>
        <div class="bg-[#111111] border border-white/8 rounded-[10px] p-4">
          <p
            class="m-0 mb-1 text-[0.6875rem] font-bold uppercase tracking-[0.07em] text-white/50"
          >
            Tổng chi tiêu
          </p>
          <p class="m-0 text-lg font-bold text-white/95">
            {{ formatVnd(customer.total_spent) }}
          </p>
        </div>
        <div class="bg-[#111111] border border-white/8 rounded-[10px] p-4">
          <p
            class="m-0 mb-1 text-[0.6875rem] font-bold uppercase tracking-[0.07em] text-white/50"
          >
            Đặt hàng gần đây
          </p>
          <p class="m-0 text-lg font-bold text-white/95">
            {{ formatDate(customer.last_ordered_at) }}
          </p>
        </div>
        <div class="bg-[#111111] border border-white/8 rounded-[10px] p-4">
          <p
            class="m-0 mb-1 text-[0.6875rem] font-bold uppercase tracking-[0.07em] text-white/50"
          >
            Ngày tạo
          </p>
          <p class="m-0 text-lg font-bold text-white/95">
            {{ formatDate(customer.created_at) }}
          </p>
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
                {{ customer.email ?? "—" }}
              </dd>
            </div>
            <div
              class="flex justify-between gap-3 py-1.25 border-b border-white/6 text-sm last:border-b-0"
            >
              <dt class="text-white/50 shrink-0">Điện thoại</dt>
              <dd class="m-0 text-white/85 text-right wrap-break-word">
                {{ customer.phone ?? "—" }}
              </dd>
            </div>
            <div
              class="flex justify-between gap-3 py-1.25 border-b border-white/6 text-sm last:border-b-0"
            >
              <dt class="text-white/50 shrink-0">Giới tính</dt>
              <dd class="m-0 text-white/85 text-right wrap-break-word">
                {{ genderLabel(customer.gender) }}
              </dd>
            </div>
            <div
              class="flex justify-between gap-3 py-1.25 border-b border-white/6 text-sm last:border-b-0"
            >
              <dt class="text-white/50 shrink-0">Nguồn</dt>
              <dd class="m-0 text-white/85 text-right wrap-break-word">
                {{ customer.source ?? "—" }}
              </dd>
            </div>
          </dl>
        </div>

        <!-- Loyalty info -->
        <div
          class="bg-[#111111] border border-white/8 rounded-[10px] p-4.5"
          v-if="customer.loyaltyAccount"
        >
          <p
            class="m-0 mb-3.5 text-[0.6875rem] font-bold tracking-[0.07em] uppercase text-white/50"
          >
            Điểm tích lũy
          </p>
          <dl class="m-0">
            <div
              class="flex justify-between gap-3 py-1.25 border-b border-white/6 text-sm last:border-b-0"
            >
              <dt class="text-white/50 shrink-0">Số dư điểm</dt>
              <dd
                class="m-0 text-white/85 text-right wrap-break-word font-bold"
              >
                {{ formatNumber(customer.loyaltyAccount.points_balance) }} điểm
              </dd>
            </div>
            <div
              class="flex justify-between gap-3 py-1.25 border-b border-white/6 text-sm last:border-b-0"
            >
              <dt class="text-white/50 shrink-0">Điểm vĩnh cửu</dt>
              <dd class="m-0 text-white/85 text-right wrap-break-word">
                {{ formatNumber(customer.loyaltyAccount.lifetime_points) }}
              </dd>
            </div>
            <div
              class="flex justify-between gap-3 py-1.25 border-b border-white/6 text-sm last:border-b-0"
            >
              <dt class="text-white/50 shrink-0">Hạng</dt>
              <dd class="m-0 text-white/85 text-right wrap-break-word">
                {{ customer.loyaltyAccount.tier?.name ?? "—" }}
              </dd>
            </div>
          </dl>
        </div>

        <!-- Notes -->
        <div
          class="bg-[#111111] border border-white/8 rounded-[10px] p-4.5"
          v-if="customer.notes"
        >
          <p
            class="m-0 mb-3.5 text-[0.6875rem] font-bold tracking-[0.07em] uppercase text-white/50"
          >
            Ghi chú
          </p>
          <p class="m-0 text-sm text-white/65 leading-relaxed">
            {{ customer.notes }}
          </p>
        </div>
      </div>
    </template>

    <!-- Edit modal -->
    <ZModal v-model="showEditModal" title="Chỉnh sửa khách hàng" size="md">
      <div
        class="grid grid-cols-2 gap-4 [&>*:last-child]:col-span-full max-sm:grid-cols-1"
      >
        <ZInput
          v-model="editForm.first_name"
          label="Họ *"
          placeholder="Nguyễn"
          :error="editErrors.first_name"
        />
        <ZInput
          v-model="editForm.last_name"
          label="Tên *"
          placeholder="Văn An"
          :error="editErrors.last_name"
        />
        <ZInput
          v-model="editForm.email"
          label="Email"
          type="email"
          placeholder="khach@email.com"
          :error="editErrors.email"
        />
        <ZInput
          v-model="editForm.phone"
          label="Điện thoại"
          type="tel"
          placeholder="0901234567"
        />
        <ZSelect
          v-model="editForm.gender"
          :options="genderOptions"
          label="Giới tính"
          placeholder="Chọn giới tính"
        />
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
import {
  formatVnd,
  formatNumber,
  formatDate,
  genderLabel,
  sanitizeString,
  sanitizeEmail,
} from "~/utils/format";
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

const editForm = reactive({
  first_name: "",
  last_name: "",
  email: "",
  phone: "",
  gender: "",
});
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
  editErrors.first_name = sanitizeString(editForm.first_name)
    ? ""
    : "Họ là bắt buộc";
  editErrors.last_name = sanitizeString(editForm.last_name)
    ? ""
    : "Tên là bắt buộc";
  editErrors.email =
    editForm.email && !sanitizeEmail(editForm.email)
      ? "Email không hợp lệ"
      : "";
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
