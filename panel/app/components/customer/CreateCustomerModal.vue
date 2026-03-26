<template>
  <ZModal
    :model-value="modelValue"
    title="Tạo khách hàng mới"
    size="md"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <div class="flex flex-col gap-4">
      <!-- Name -->
      <ZInput
        v-model="form.first_name"
        label="Tên *"
        placeholder="Nguyễn"
        :error="errors.first_name"
        @blur="validateField('first_name')"
      />
      <ZInput
        v-model="form.last_name"
        label="Họ *"
        placeholder="Văn A"
        :error="errors.last_name"
        @blur="validateField('last_name')"
      />

      <!-- Phone with real-time validation -->
      <div class="relative">
        <ZInput
          v-model="form.phone"
          label="Số điện thoại *"
          placeholder="0901234567"
          type="tel"
          :error="errors.phone"
          @input="onPhoneInput"
          @blur="validateField('phone')"
        />
        <!-- Phone conflict banner -->
        <PhoneConflictBanner
          v-if="phoneCheck.hasConflict"
          :existing-customer="phoneCheck.existingCustomer"
          :is-loading="phoneCheck.isChecking"
          @use-existing="handleUseExisting"
          @overwrite="handleOverwrite"
          @create-unlink="handleCreateUnlink"
        />
        <p
          v-else-if="
            form.phone && !phoneCheck.isChecking && !phoneCheck.hasConflict
          "
          class="mt-1 text-xs text-green-500"
        >
          <span class="inline-flex items-center gap-1">
            <svg
              width="12"
              height="12"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
            >
              <polyline points="20 6 9 17 4 12" />
            </svg>
            Số điện thoại có thể sử dụng
          </span>
        </p>
      </div>

      <!-- Email -->
      <ZInput
        v-model="form.email"
        label="Email"
        placeholder="khach@email.com"
        type="email"
        :error="errors.email"
        @blur="validateField('email')"
      />

      <!-- Gender -->
      <ZSelect
        v-model="form.gender"
        label="Giới tính"
        placeholder="Chọn giới tính"
        :options="genderOptions"
      />

      <!-- Loyalty warning -->
      <div class="mt-2 p-3 rounded-lg bg-white/5 border border-white/8">
        <p class="text-xs text-white/70 flex items-start gap-2">
          <svg
            width="14"
            height="14"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            class="text-[#C4A77D] mt-0.5"
          >
            <circle cx="12" cy="12" r="10" />
            <line x1="12" y1="8" x2="12" y2="12" />
            <line x1="12" y1="16" x2="12.01" y2="16" />
          </svg>
          <span>
            Điểm tích lũy và lịch sử đơn hàng sẽ
            <strong class="text-white">không được chuyển</strong> sang khách
            hàng mới. Mỗi khách hàng giữ nguyên tài khoản tích lũy riêng.
          </span>
        </p>
      </div>
    </div>

    <template #footer>
      <ZButton
        variant="outline"
        size="sm"
        :disabled="isSubmitting"
        @click="handleCancel"
      >
        Hủy
      </ZButton>
      <ZButton
        variant="primary"
        size="sm"
        :loading="isSubmitting"
        :disabled="!canSubmit"
        @click="handleSubmit"
      >
        {{ submitButtonText }}
      </ZButton>
    </template>
  </ZModal>
</template>

<script setup lang="ts">
import { ref, computed, watch } from "vue";
import type { CustomerModel } from "~~/types/generated/backend-models.generated";

interface PhoneCheckResult {
  isChecking: boolean;
  hasConflict: boolean;
  existingCustomer: CustomerModel | null;
  hasOrders: boolean;
  loyaltyPoints: number;
}

interface CreateCustomerForm {
  first_name: string;
  last_name: string;
  phone: string;
  email: string;
  gender: string;
}

interface FormErrors {
  first_name: string;
  last_name: string;
  phone: string;
  email: string;
}

type ConflictResolution = "none" | "overwrite" | "unlink";

const props = defineProps<{
  modelValue: boolean;
}>();

const emit = defineEmits<{
  "update:modelValue": [value: boolean];
  submit: [
    data: { customer: Partial<CustomerModel>; resolution: ConflictResolution }
  ];
  "use-existing": [customer: CustomerModel];
  cancel: [];
}>();

// Form state
const form = ref<CreateCustomerForm>({
  first_name: "",
  last_name: "",
  phone: "",
  email: "",
  gender: "",
});

const errors = ref<FormErrors>({
  first_name: "",
  last_name: "",
  phone: "",
  email: "",
});

const isSubmitting = ref(false);
const conflictResolution = ref<ConflictResolution>("none");

// Phone check state
const phoneCheck = ref<PhoneCheckResult>({
  isChecking: false,
  hasConflict: false,
  existingCustomer: null,
  hasOrders: false,
  loyaltyPoints: 0,
});

// Debounce timer for phone check
let phoneCheckTimer: ReturnType<typeof setTimeout> | null = null;

const genderOptions = [
  { value: "male", label: "Nam" },
  { value: "female", label: "Nữ" },
  { value: "other", label: "Khác" },
];

const canSubmit = computed(() => {
  const hasRequiredFields =
    form.value.first_name.trim() &&
    form.value.last_name.trim() &&
    form.value.phone.trim();
  const hasNoErrors =
    !errors.value.first_name &&
    !errors.value.last_name &&
    !errors.value.phone &&
    !errors.value.email;
  const hasResolvedConflict =
    !phoneCheck.value.hasConflict || conflictResolution.value !== "none";
  return (
    hasRequiredFields &&
    hasNoErrors &&
    hasResolvedConflict &&
    !phoneCheck.value.isChecking
  );
});

const submitButtonText = computed(() => {
  if (conflictResolution.value === "overwrite") return "Cập nhật khách hàng cũ";
  if (conflictResolution.value === "unlink") return "Tạo mới & Xóa SĐT cũ";
  return "Tạo khách hàng";
});

// Reset form when modal opens
watch(
  () => props.modelValue,
  (isOpen) => {
    if (isOpen) {
      resetForm();
    }
  }
);

function resetForm(): void {
  form.value = {
    first_name: "",
    last_name: "",
    phone: "",
    email: "",
    gender: "",
  };
  errors.value = {
    first_name: "",
    last_name: "",
    phone: "",
    email: "",
  };
  phoneCheck.value = {
    isChecking: false,
    hasConflict: false,
    existingCustomer: null,
    hasOrders: false,
    loyaltyPoints: 0,
  };
  conflictResolution.value = "none";
}

function onPhoneInput(): void {
  // Clear resolution when phone changes
  conflictResolution.value = "none";

  // Debounced phone check
  if (phoneCheckTimer) clearTimeout(phoneCheckTimer);

  const phone = form.value.phone.trim();
  if (!phone || phone.length < 9) {
    phoneCheck.value.hasConflict = false;
    phoneCheck.value.existingCustomer = null;
    return;
  }

  phoneCheck.value.isChecking = true;
  phoneCheckTimer = setTimeout(() => {
    checkPhoneDuplicate(phone);
  }, 300);
}

async function checkPhoneDuplicate(phone: string): Promise<void> {
  try {
    // TODO: Replace with actual API call
    // const response = await api.get(`/customers/check-phone?phone=${encodeURIComponent(phone)}`);

    // Mock response for now - simulate finding a duplicate
    await new Promise((resolve) => setTimeout(resolve, 200));

    // Simulate finding duplicate (remove this in production)
    const mockDuplicate = phone.includes("090");

    if (mockDuplicate) {
      phoneCheck.value = {
        isChecking: false,
        hasConflict: true,
        existingCustomer: {
          id: "mock-id",
          first_name: "Nguyễn",
          last_name: "Văn A",
          phone: phone,
          email: "old@email.com",
          customer_code: "KH-1234",
          total_orders: 5,
          loyaltyAccount: {
            points_balance: 1500,
          },
        } as unknown as CustomerModel,
        hasOrders: true,
        loyaltyPoints: 1500,
      };
    } else {
      phoneCheck.value = {
        isChecking: false,
        hasConflict: false,
        existingCustomer: null,
        hasOrders: false,
        loyaltyPoints: 0,
      };
    }
  } catch {
    phoneCheck.value.isChecking = false;
    // Fail silently - user can still proceed
  }
}

function validateField(field: keyof FormErrors): void {
  const value = form.value[field];

  switch (field) {
    case "first_name":
      errors.value.first_name = value.trim() ? "" : "Vui lòng nhập tên";
      break;
    case "last_name":
      errors.value.last_name = value.trim() ? "" : "Vui lòng nhập họ";
      break;
    case "phone":
      if (!value.trim()) {
        errors.value.phone = "Vui lòng nhập số điện thoại";
      } else if (!/^0\d{9,10}$/.test(value.trim())) {
        errors.value.phone = "Số điện thoại không hợp lệ";
      } else {
        errors.value.phone = "";
      }
      break;
    case "email":
      if (value.trim() && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value.trim())) {
        errors.value.email = "Email không hợp lệ";
      } else {
        errors.value.email = "";
      }
      break;
  }
}

function validateForm(): boolean {
  validateField("first_name");
  validateField("last_name");
  validateField("phone");
  validateField("email");

  return (
    !errors.value.first_name &&
    !errors.value.last_name &&
    !errors.value.phone &&
    !errors.value.email
  );
}

function handleUseExisting(): void {
  if (phoneCheck.value.existingCustomer) {
    emit("use-existing", phoneCheck.value.existingCustomer);
    emit("update:modelValue", false);
  }
}

function handleOverwrite(): void {
  conflictResolution.value = "overwrite";
}

function handleCreateUnlink(): void {
  conflictResolution.value = "unlink";
}

function handleCancel(): void {
  emit("cancel");
  emit("update:modelValue", false);
}

async function handleSubmit(): Promise<void> {
  if (!validateForm()) return;

  // If there's a conflict but no resolution chosen, show error
  if (phoneCheck.value.hasConflict && conflictResolution.value === "none") {
    errors.value.phone = "Vui lòng chọn cách xử lý trùng lặp";
    return;
  }

  isSubmitting.value = true;

  try {
    const customerData: Partial<CustomerModel> = {
      first_name: form.value.first_name.trim(),
      last_name: form.value.last_name.trim(),
      phone: form.value.phone.trim(),
      email: form.value.email.trim() || null,
      gender: form.value.gender || null,
    };

    emit("submit", {
      customer: customerData,
      resolution: conflictResolution.value,
    });
  } finally {
    isSubmitting.value = false;
  }
}
</script>
