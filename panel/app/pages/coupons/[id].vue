<template>
  <div>
    <!-- Loading skeleton -->
    <template v-if="detailState === 'loading'">
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
      <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5 mb-6">
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
      v-else-if="detailState === 'error'"
      title="Không thể tải mã giảm giá"
      :description="detailError ?? ''"
      :icon="ERROR_ICON"
    >
      <template #action>
        <ZButton variant="outline" size="sm" @click="fetchCoupon"
          >Thử lại</ZButton
        >
      </template>
    </ZEmptyState>

    <!-- Content -->
    <template v-else-if="coupon">
      <!-- Page header -->
      <div class="flex items-start justify-between gap-4 mb-5 flex-wrap">
        <div class="flex items-center flex-wrap gap-3">
          <NuxtLink
            to="/coupons"
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
            Mã giảm giá
          </NuxtLink>
          <div class="flex items-center gap-3">
            <div
              class="w-12 h-12 rounded-full bg-gradient-to-br from-amber-500 to-amber-600 text-white text-xl font-bold flex items-center justify-center shrink-0"
            >
              %
            </div>
            <div>
              <h1 class="m-0 text-lg font-bold text-white/95">
                {{ coupon.name }}
              </h1>
              <p class="m-0 text-sm text-white/50 font-mono">
                {{ coupon.code }}
              </p>
            </div>
          </div>
          <ZBadge :variant="coupon.is_active ? 'success' : 'neutral'">
            {{ coupon.is_active ? "Đang hoạt động" : "Tắt" }}
          </ZBadge>
        </div>
        <div class="flex gap-2 flex-wrap">
          <ZButton variant="outline" size="sm" @click="openEditModal"
            >Chỉnh sửa</ZButton
          >
          <ZButton
            v-if="coupon.is_active"
            variant="ghost"
            size="sm"
            :loading="togglePending"
            @click="showDeactivateConfirm = true"
          >
            Tắt mã
          </ZButton>
          <ZButton
            v-else
            variant="outline"
            size="sm"
            :loading="togglePending"
            @click="handleActivate"
          >
            Bật mã
          </ZButton>
        </div>
      </div>

      <!-- Stats bar -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
        <div class="bg-[#111111] border border-white/8 rounded-[10px] p-4">
          <p
            class="m-0 mb-1 text-[0.6875rem] font-bold uppercase tracking-[0.07em] text-white/50"
          >
            Loại giảm giá
          </p>
          <p class="m-0 text-base font-bold text-white/95">
            {{ couponTypeLabel(coupon.type) }}
          </p>
        </div>
        <div class="bg-[#111111] border border-white/8 rounded-[10px] p-4">
          <p
            class="m-0 mb-1 text-[0.6875rem] font-bold uppercase tracking-[0.07em] text-white/50"
          >
            Giá trị
          </p>
          <p class="m-0 text-base font-bold text-white/95">
            {{
              coupon.type === "percent"
                ? `${coupon.value}%`
                : formatVnd(coupon.value)
            }}
          </p>
        </div>
        <div class="bg-[#111111] border border-white/8 rounded-[10px] p-4">
          <p
            class="m-0 mb-1 text-[0.6875rem] font-bold uppercase tracking-[0.07em] text-white/50"
          >
            Đã sử dụng
          </p>
          <p class="m-0 text-base font-bold text-white/95">
            {{ coupon.used_count
            }}{{ coupon.max_uses ? ` / ${coupon.max_uses}` : "" }}
          </p>
        </div>
        <div class="bg-[#111111] border border-white/8 rounded-[10px] p-4">
          <p
            class="m-0 mb-1 text-[0.6875rem] font-bold uppercase tracking-[0.07em] text-white/50"
          >
            Hiệu lực
          </p>
          <p class="m-0 text-base font-bold text-white/95">
            {{ formatDate(coupon.starts_at) }} -
            {{ formatDate(coupon.expires_at) }}
          </p>
        </div>
      </div>

      <!-- Detail grid -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5 mb-6">
        <!-- Coupon info -->
        <div class="bg-[#111111] border border-white/8 rounded-[10px] p-4.5">
          <p
            class="m-0 mb-3.5 text-[0.6875rem] font-bold tracking-[0.07em] uppercase text-white/50"
          >
            Thông tin mã
          </p>
          <dl class="m-0">
            <div
              class="flex justify-between gap-3 py-1.25 border-b border-white/6 text-sm last:border-b-0"
            >
              <dt class="text-white/50 shrink-0">Mã giảm giá</dt>
              <dd
                class="m-0 text-white/85 text-right wrap-break-word font-mono text-[0.8125rem] font-semibold"
              >
                {{ coupon.code }}
              </dd>
            </div>
            <div
              class="flex justify-between gap-3 py-1.25 border-b border-white/6 text-sm last:border-b-0"
            >
              <dt class="text-white/50 shrink-0">Tên</dt>
              <dd class="m-0 text-white/85 text-right wrap-break-word">
                {{ coupon.name }}
              </dd>
            </div>
            <div
              class="flex justify-between gap-3 py-1.25 border-b border-white/6 text-sm last:border-b-0"
            >
              <dt class="text-white/50 shrink-0">Mô tả</dt>
              <dd class="m-0 text-white/85 text-right wrap-break-word">
                {{ coupon.description ?? "—" }}
              </dd>
            </div>
            <div
              class="flex justify-between gap-3 py-1.25 border-b border-white/6 text-sm last:border-b-0"
            >
              <dt class="text-white/50 shrink-0">Loại</dt>
              <dd class="m-0 text-white/85 text-right wrap-break-word">
                {{ couponTypeLabel(coupon.type) }}
              </dd>
            </div>
            <div
              class="flex justify-between gap-3 py-1.25 border-b border-white/6 text-sm last:border-b-0"
            >
              <dt class="text-white/50 shrink-0">Giá trị</dt>
              <dd
                class="m-0 text-white/85 text-right wrap-break-word font-bold"
              >
                {{
                  coupon.type === "percent"
                    ? `${coupon.value}%`
                    : formatVnd(coupon.value)
                }}
              </dd>
            </div>
          </dl>
        </div>

        <!-- Usage limits -->
        <div class="bg-[#111111] border border-white/8 rounded-[10px] p-4.5">
          <p
            class="m-0 mb-3.5 text-[0.6875rem] font-bold tracking-[0.07em] uppercase text-white/50"
          >
            Giới hạn sử dụng
          </p>
          <dl class="m-0">
            <div
              class="flex justify-between gap-3 py-1.25 border-b border-white/6 text-sm last:border-b-0"
            >
              <dt class="text-white/50 shrink-0">Sử dụng tối đa</dt>
              <dd class="m-0 text-white/85 text-right wrap-break-word">
                {{ coupon.max_uses ?? "Không giới hạn" }}
              </dd>
            </div>
            <div
              class="flex justify-between gap-3 py-1.25 border-b border-white/6 text-sm last:border-b-0"
            >
              <dt class="text-white/50 shrink-0">Mỗi khách tối đa</dt>
              <dd class="m-0 text-white/85 text-right wrap-break-word">
                {{ coupon.max_uses_per_customer }}
              </dd>
            </div>
            <div
              class="flex justify-between gap-3 py-1.25 border-b border-white/6 text-sm last:border-b-0"
            >
              <dt class="text-white/50 shrink-0">Đơn tối thiểu</dt>
              <dd class="m-0 text-white/85 text-right wrap-break-word">
                {{
                  coupon.min_order_amount
                    ? formatVnd(coupon.min_order_amount)
                    : "—"
                }}
              </dd>
            </div>
            <div
              class="flex justify-between gap-3 py-1.25 border-b border-white/6 text-sm last:border-b-0"
            >
              <dt class="text-white/50 shrink-0">Số SP tối thiểu</dt>
              <dd class="m-0 text-white/85 text-right wrap-break-word">
                {{ coupon.min_items ?? "—" }}
              </dd>
            </div>
            <div
              class="flex justify-between gap-3 py-1.25 border-b border-white/6 text-sm last:border-b-0"
            >
              <dt class="text-white/50 shrink-0">Giảm tối đa</dt>
              <dd class="m-0 text-white/85 text-right wrap-break-word">
                {{
                  coupon.max_discount_amount
                    ? formatVnd(coupon.max_discount_amount)
                    : "—"
                }}
              </dd>
            </div>
          </dl>
        </div>

        <!-- Validity -->
        <div class="bg-[#111111] border border-white/8 rounded-[10px] p-4.5">
          <p
            class="m-0 mb-3.5 text-[0.6875rem] font-bold tracking-[0.07em] uppercase text-white/50"
          >
            Thời hạn
          </p>
          <dl class="m-0">
            <div
              class="flex justify-between gap-3 py-1.25 border-b border-white/6 text-sm last:border-b-0"
            >
              <dt class="text-white/50 shrink-0">Bắt đầu</dt>
              <dd class="m-0 text-white/85 text-right wrap-break-word">
                {{ formatDate(coupon.starts_at) }}
              </dd>
            </div>
            <div
              class="flex justify-between gap-3 py-1.25 border-b border-white/6 text-sm last:border-b-0"
            >
              <dt class="text-white/50 shrink-0">Kết thúc</dt>
              <dd class="m-0 text-white/85 text-right wrap-break-word">
                {{ formatDate(coupon.expires_at) }}
              </dd>
            </div>
            <div
              class="flex justify-between gap-3 py-1.25 border-b border-white/6 text-sm last:border-b-0"
            >
              <dt class="text-white/50 shrink-0">Công khai</dt>
              <dd class="m-0 text-white/85 text-right wrap-break-word">
                {{ coupon.is_public ? "Có" : "Không" }}
              </dd>
            </div>
            <div
              class="flex justify-between gap-3 py-1.25 border-b border-white/6 text-sm last:border-b-0"
            >
              <dt class="text-white/50 shrink-0">Trạng thái</dt>
              <dd class="m-0 text-white/85 text-right wrap-break-word">
                <ZBadge :variant="coupon.is_active ? 'success' : 'neutral'">
                  {{ coupon.is_active ? "Đang hoạt động" : "Tắt" }}
                </ZBadge>
              </dd>
            </div>
          </dl>
        </div>
      </div>

      <!-- Usage history section -->
      <div class="my-6 pb-3 border-b border-white/10">
        <h2 class="m-0 text-base font-semibold text-white/90">
          Lịch sử sử dụng
        </h2>
      </div>

      <!-- Usage Error -->
      <div
        v-if="usageState === 'error'"
        class="flex items-center justify-between gap-3 py-3 px-4 mb-3.5 bg-red-500/10 border border-red-500/30 rounded-lg text-sm text-red-500"
      >
        <span>{{ usageError }}</span>
        <ZButton variant="ghost" size="sm" @click="fetchUsages"
          >Thử lại</ZButton
        >
      </div>

      <!-- Usage Table -->
      <ZTable
        :columns="usageColumns"
        :rows="usages"
        :loading="usageState === 'loading'"
        :skeleton-rows="8"
        row-key="id"
        empty-title="Chưa có lượt sử dụng"
        empty-description="Mã này chưa được sử dụng."
      >
        <template #cell-customer="{ row }">
          <span class="font-medium text-white/90">{{
            (row as CouponUsage).customer?.name ?? "Khách vãng lai"
          }}</span>
        </template>
        <template #cell-order="{ row }">
          <span class="font-mono text-[0.8125rem] font-semibold">{{
            (row as CouponUsage).order_id
          }}</span>
        </template>
        <template #cell-discount_amount="{ row }">
          <span class="font-semibold">{{
            formatVnd((row as CouponUsage).discount_amount)
          }}</span>
        </template>
        <template #cell-used_at="{ row }">
          <span class="text-[0.8125rem] text-white/50">{{
            formatDatetime((row as CouponUsage).used_at)
          }}</span>
        </template>
      </ZTable>

      <!-- Usage Pagination -->
      <div
        class="flex items-center justify-between gap-3 pt-4 flex-wrap"
        v-if="usageState === 'loaded' && usages.length > 0"
      >
        <p class="m-0 text-[0.8125rem] text-white/40">
          {{ usageMeta.total }} lượt sử dụng
        </p>
        <ZPagination
          :current-page="usageMeta.current_page"
          :total-pages="usageMeta.last_page"
          @change="onUsagePageChange"
        />
      </div>
    </template>

    <!-- Edit modal -->
    <ZModal v-model="showEditModal" title="Chỉnh sửa mã giảm giá" size="md">
      <div
        class="grid grid-cols-2 gap-4 [&>*:nth-child(2)]:col-span-full [&>*:nth-child(7)]:col-span-full max-sm:grid-cols-1"
      >
        <ZInput
          v-model="editForm.code"
          label="Mã giảm giá *"
          placeholder="SUMMER20"
          :error="editErrors.code"
          disabled
        />
        <ZInput
          v-model="editForm.name"
          label="Tên *"
          placeholder="Khuyến mãi mùa hè"
          :error="editErrors.name"
        />
        <ZSelect
          v-model="editForm.type"
          :options="typeOptions"
          label="Loại giảm giá *"
          :error="editErrors.type"
          disabled
        />
        <ZInput
          v-model="editForm.value"
          label="Giá trị *"
          type="number"
          placeholder="20"
          :error="editErrors.value"
          disabled
        />
        <ZInput v-model="editForm.starts_at" label="Ngày bắt đầu" type="date" />
        <ZInput
          v-model="editForm.expires_at"
          label="Ngày hết hạn"
          type="date"
        />
        <ZInput
          v-model="editForm.max_uses"
          label="Số lần dùng tối đa"
          type="number"
          placeholder="Không giới hạn"
        />
        <ZInput
          v-model="editForm.max_uses_per_customer"
          label="Mỗi khách tối đa"
          type="number"
          placeholder="1"
        />
        <ZInput
          v-model="editForm.min_order_amount"
          label="Đơn tối thiểu"
          type="number"
          placeholder="0"
        />
        <ZInput
          v-model="editForm.max_discount_amount"
          label="Giảm tối đa"
          type="number"
          placeholder="Không giới hạn"
        />
      </div>
      <template #footer>
        <ZButton
          variant="outline"
          size="sm"
          :disabled="editPending"
          @click="showEditModal = false"
          >Hủy</ZButton
        >
        <ZButton
          variant="primary"
          size="sm"
          :loading="editPending"
          @click="handleUpdate"
          >Lưu thay đổi</ZButton
        >
      </template>
    </ZModal>

    <!-- Deactivate confirm -->
    <ZConfirmDialog
      v-model="showDeactivateConfirm"
      title="Tắt mã giảm giá"
      message="Bạn có chắc muốn tắt mã giảm giá này? Khách hàng sẽ không thể sử dụng mã này nữa."
      confirm-label="Tắt mã"
      :dangerous="true"
      :loading="togglePending"
      @confirm="handleDeactivate"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from "vue";
import { useRoute } from "vue-router";
import { useApi } from "~/composables/useApi";
import { useToast } from "~/composables/useToast";
import {
  formatVnd,
  formatDate,
  formatDatetime,
  getErrorMessage,
  sanitizeString,
  sanitizePositiveInt,
} from "~/utils/format";
import type { TableColumn } from "~/components/z/Table.vue";
import type { SelectOption } from "~/components/z/Select.vue";

definePageMeta({ layout: "panel" });

const ERROR_ICON = `<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`;

interface Customer {
  id?: string;
  name?: string;
}

interface CouponUsage {
  id: string;
  coupon_id: string;
  customer_id: string | null;
  order_id: string;
  discount_amount: number;
  used_at: string;
  customer?: Customer | null;
}

interface Coupon {
  id: string;
  code: string;
  name: string;
  description: string | null;
  type: string;
  value: number;
  min_order_amount: number | null;
  min_items: number | null;
  max_discount_amount: number | null;
  max_uses: number | null;
  max_uses_per_customer: number;
  used_count: number;
  is_public: boolean;
  is_active: boolean;
  starts_at: string | null;
  expires_at: string | null;
  created_at: string | null;
}

interface ListMeta {
  current_page: number;
  last_page: number;
  total: number;
  per_page: number;
}

const route = useRoute();
const api = useApi();
const toast = useToast();

const id = route.params.id as string;

// Detail states
const detailState = ref<"idle" | "loading" | "loaded" | "error">("idle");
const detailError = ref<string | null>(null);
const coupon = ref<Coupon | null>(null);

// Usage states
const usageState = ref<"idle" | "loading" | "loaded" | "error">("idle");
const usageError = ref<string | null>(null);
const usages = ref<CouponUsage[]>([]);
const usageMeta = ref<ListMeta>({
  current_page: 1,
  last_page: 1,
  total: 0,
  per_page: 20,
});
const usagePage = ref(1);

// Modals
const showEditModal = ref(false);
const showDeactivateConfirm = ref(false);
const editPending = ref(false);
const togglePending = ref(false);

const editForm = reactive({
  code: "",
  name: "",
  type: "",
  value: "",
  starts_at: "",
  expires_at: "",
  max_uses: "",
  max_uses_per_customer: "",
  min_order_amount: "",
  max_discount_amount: "",
});
const editErrors = reactive({
  code: "",
  name: "",
  type: "",
  value: "",
});

const typeOptions: SelectOption[] = [
  { value: "percent", label: "Phần trăm (%)" },
  { value: "fixed", label: "Số tiền cố định (₫)" },
];

const usageColumns: TableColumn[] = [
  { key: "customer", label: "Khách hàng", skeletonWidth: "140px" },
  { key: "order", label: "Mã đơn", skeletonWidth: "100px" },
  {
    key: "discount_amount",
    label: "Giảm giá",
    align: "right",
    skeletonWidth: "100px",
  },
  { key: "used_at", label: "Thời gian", skeletonWidth: "140px" },
];

function couponTypeLabel(type: string): string {
  return type === "percent" ? "Phần trăm" : type === "fixed" ? "Cố định" : type;
}

async function fetchCoupon(): Promise<void> {
  detailState.value = "loading";
  detailError.value = null;
  try {
    const response = await api.get<{ data: Coupon }>(`/coupons/${id}`);
    coupon.value = response.data ?? null;
    detailState.value = "loaded";
  } catch (error) {
    detailState.value = "error";
    detailError.value = getErrorMessage(
      error,
      "Không thể tải thông tin mã giảm giá."
    );
  }
}

async function fetchUsages(): Promise<void> {
  usageState.value = "loading";
  usageError.value = null;
  try {
    const params: Record<string, string> = {
      page: String(usagePage.value),
      per_page: "20",
      coupon_id: id,
    };
    const q = new URLSearchParams(params).toString();
    const response = await api.get<{ data: CouponUsage[]; meta: ListMeta }>(
      `/coupon-usages?${q}`
    );
    usages.value = response.data ?? [];
    usageMeta.value = response.meta ?? {
      current_page: 1,
      last_page: 1,
      total: 0,
      per_page: 20,
    };
    usageState.value = "loaded";
  } catch (error) {
    usageState.value = "error";
    usageError.value = getErrorMessage(error, "Không thể tải lịch sử sử dụng.");
  }
}

function openEditModal(): void {
  if (!coupon.value) return;
  editForm.code = coupon.value.code;
  editForm.name = coupon.value.name;
  editForm.type = coupon.value.type;
  editForm.value = String(coupon.value.value);
  editForm.starts_at = coupon.value.starts_at?.split("T")[0] ?? "";
  editForm.expires_at = coupon.value.expires_at?.split("T")[0] ?? "";
  editForm.max_uses = coupon.value.max_uses?.toString() ?? "";
  editForm.max_uses_per_customer =
    coupon.value.max_uses_per_customer?.toString() ?? "";
  editForm.min_order_amount = coupon.value.min_order_amount?.toString() ?? "";
  editForm.max_discount_amount =
    coupon.value.max_discount_amount?.toString() ?? "";
  editErrors.code = "";
  editErrors.name = "";
  editErrors.type = "";
  editErrors.value = "";
  showEditModal.value = true;
}

function validateEditForm(): boolean {
  let valid = true;
  editErrors.name = sanitizeString(editForm.name) ? "" : "Tên là bắt buộc";
  if (editErrors.name) valid = false;
  return valid;
}

async function handleUpdate(): Promise<void> {
  if (!validateEditForm()) return;
  editPending.value = true;
  try {
    await api.patch(`/coupons/${id}`, {
      name: sanitizeString(editForm.name),
      starts_at: editForm.starts_at || null,
      expires_at: editForm.expires_at || null,
      max_uses: sanitizePositiveInt(editForm.max_uses),
      max_uses_per_customer:
        sanitizePositiveInt(editForm.max_uses_per_customer) ?? 1,
      min_order_amount: sanitizePositiveInt(editForm.min_order_amount),
      max_discount_amount: sanitizePositiveInt(editForm.max_discount_amount),
    });
    toast.success("Đã cập nhật mã giảm giá");
    showEditModal.value = false;
    await fetchCoupon();
  } catch (error) {
    toast.error("Cập nhật thất bại", getErrorMessage(error));
  } finally {
    editPending.value = false;
  }
}

async function handleDeactivate(): Promise<void> {
  togglePending.value = true;
  try {
    await api.patch(`/coupons/${id}`, { is_active: false });
    toast.success("Đã tắt mã giảm giá");
    showDeactivateConfirm.value = false;
    await fetchCoupon();
  } catch (error) {
    toast.error("Thao tác thất bại", getErrorMessage(error));
  } finally {
    togglePending.value = false;
  }
}

async function handleActivate(): Promise<void> {
  togglePending.value = true;
  try {
    await api.patch(`/coupons/${id}`, { is_active: true });
    toast.success("Đã bật mã giảm giá");
    await fetchCoupon();
  } catch (error) {
    toast.error("Thao tác thất bại", getErrorMessage(error));
  } finally {
    togglePending.value = false;
  }
}

function onUsagePageChange(page: number): void {
  usagePage.value = page;
  fetchUsages();
}

onMounted(() => {
  fetchCoupon();
  fetchUsages();
});
</script>
