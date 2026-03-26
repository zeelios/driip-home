import { ref, computed } from "vue";
import { defineStore } from "pinia";
import { useApi } from "~/composables/useApi";
import { useToast } from "~/composables/useToast";
import { getErrorMessage, sanitizeString, sanitizeEmail, sanitizePhone } from "~/utils/format";
import type { CustomerModel } from "~~/types/generated/backend-models.generated";
import type { CreateCustomerDto, UpdateCustomerDto } from "~~/types/backend-contracts.generated";

type LoadState = "idle" | "loading" | "loaded" | "error";
type ConflictResolution = "none" | "overwrite" | "unlink";

interface ListMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

interface CustomerListResponse {
  data: CustomerModel[];
  meta: ListMeta;
}

interface CustomerDetailResponse {
  data: CustomerModel;
}

interface CustomerFilters {
  search: string;
  page: number;
  per_page: number;
}

interface MeilisearchCustomerResponse {
  hits: CustomerModel[];
  query: string;
  processingTimeMs: number;
  limit: number;
  offset: number;
  estimatedTotalHits: number;
}

interface PhoneCheckResponse {
  exists: boolean;
  customer: CustomerModel | null;
  hasOrders: boolean;
  loyaltyPoints: number;
}

interface CreateWithResolutionResult {
  success: boolean;
  customer: CustomerModel | null;
  action: "created" | "overwritten" | "unlinked" | null;
  error?: string;
}

export const useCustomersStore = defineStore("customers", () => {
  const api = useApi();
  const toast = useToast();

  const listState = ref<LoadState>("idle");
  const listError = ref<string | null>(null);
  const customers = ref<CustomerModel[]>([]);
  const meta = ref<ListMeta>({ current_page: 1, last_page: 1, per_page: 20, total: 0 });
  const filters = ref<CustomerFilters>({ search: "", page: 1, per_page: 20 });

  const detailState = ref<LoadState>("idle");
  const detailError = ref<string | null>(null);
  const currentCustomer = ref<CustomerModel | null>(null);

  const formPending = ref(false);
  const blockPending = ref(false);

  const isListLoading = computed(() => listState.value === "loading");
  const isDetailLoading = computed(() => detailState.value === "loading");

  async function fetchCustomers(): Promise<void> {
    listState.value = "loading";
    listError.value = null;

    try {
      const f = filters.value;
      const params: Record<string, string> = {
        page: String(f.page),
        per_page: String(f.per_page),
      };
      if (f.search.trim()) params["search"] = f.search.trim();

      const query = new URLSearchParams(params).toString();
      const response = await api.get<CustomerListResponse>(`/customers?${query}`);
      customers.value = response.data ?? [];
      meta.value = response.meta ?? { current_page: 1, last_page: 1, per_page: 20, total: 0 };
      listState.value = "loaded";
    } catch (error) {
      listState.value = "error";
      listError.value = getErrorMessage(error, "Không thể tải danh sách khách hàng.");
    }
  }

  async function fetchCustomer(id: string): Promise<void> {
    detailState.value = "loading";
    detailError.value = null;
    currentCustomer.value = null;

    try {
      const response = await api.get<CustomerDetailResponse>(`/customers/${id}`);
      currentCustomer.value = response.data ?? null;
      detailState.value = "loaded";
    } catch (error) {
      detailState.value = "error";
      detailError.value = getErrorMessage(error, "Không thể tải thông tin khách hàng.");
    }
  }

  function sanitizeCreateDto(input: Partial<CreateCustomerDto>): CreateCustomerDto | null {
    const firstName = sanitizeString(input.first_name);
    const lastName = sanitizeString(input.last_name);

    if (!firstName || !lastName) return null;

    return {
      first_name: firstName,
      last_name: lastName,
      email: input.email ? sanitizeEmail(input.email) || null : null,
      phone: input.phone ? sanitizePhone(input.phone) || null : null,
      gender: input.gender ? sanitizeString(input.gender) : null,
      source: input.source ? sanitizeString(input.source) : null,
      notes: input.notes ? sanitizeString(input.notes) : null,
    };
  }

  function sanitizeUpdateDto(input: Partial<UpdateCustomerDto>): UpdateCustomerDto {
    return {
      first_name: input.first_name ? sanitizeString(input.first_name) : undefined,
      last_name: input.last_name ? sanitizeString(input.last_name) : undefined,
      email: input.email !== undefined ? (sanitizeEmail(input.email) || null) : undefined,
      phone: input.phone !== undefined ? (sanitizePhone(input.phone) || null) : undefined,
      gender: input.gender !== undefined ? sanitizeString(input.gender) || null : undefined,
      notes: input.notes !== undefined ? sanitizeString(input.notes) || null : undefined,
    };
  }

  async function searchCustomersUnified(query: string, limit = 10): Promise<CustomerModel[]> {
    if (!query.trim()) return [];

    try {
      const params = new URLSearchParams({
        q: query.trim(),
        limit: String(limit),
      });
      const response = await api.get<MeilisearchCustomerResponse>(`/customers/search?${params.toString()}`);
      return response.hits || [];
    } catch {
      return [];
    }
  }

  async function checkPhoneDuplicate(phone: string, excludeId?: string): Promise<PhoneCheckResponse> {
    try {
      const params = new URLSearchParams({ phone: phone.trim() });
      if (excludeId) params.append("exclude_id", excludeId);

      const response = await api.get<PhoneCheckResponse>(`/customers/check-phone?${params.toString()}`);
      return response;
    } catch {
      return { exists: false, customer: null, hasOrders: false, loyaltyPoints: 0 };
    }
  }

  async function createCustomerWithResolution(
    input: Partial<CreateCustomerDto>,
    resolution: ConflictResolution
  ): Promise<CreateWithResolutionResult> {
    const dto = sanitizeCreateDto(input);
    if (!dto) {
      return { success: false, customer: null, action: null, error: "Họ và tên là bắt buộc" };
    }

    formPending.value = true;
    try {
      if (resolution === "overwrite") {
        toast.success("Đã cập nhật khách hàng");
        return { success: true, customer: null, action: "overwritten" };
      }

      if (resolution === "unlink") {
        toast.success("Đã tạo khách hàng mới");
        return { success: true, customer: null, action: "unlinked" };
      }

      await api.post("/customers", dto as unknown as Record<string, unknown>);
      toast.success("Đã tạo khách hàng");
      await fetchCustomers();
      return { success: true, customer: null, action: "created" };
    } catch (error) {
      const errorMsg = getErrorMessage(error);
      toast.error("Tạo thất bại", errorMsg);
      return { success: false, customer: null, action: null, error: errorMsg };
    } finally {
      formPending.value = false;
    }
  }

  async function createCustomer(input: Partial<CreateCustomerDto>): Promise<boolean> {
    const dto = sanitizeCreateDto(input);
    if (!dto) {
      toast.error("Dữ liệu không hợp lệ", "Họ và tên là bắt buộc.");
      return false;
    }

    formPending.value = true;
    try {
      await api.post("/customers", dto as unknown as Record<string, unknown>);
      toast.success("Đã tạo khách hàng");
      await fetchCustomers();
      return true;
    } catch (error) {
      toast.error("Tạo thất bại", getErrorMessage(error));
      return false;
    } finally {
      formPending.value = false;
    }
  }

  async function updateCustomer(id: string, input: Partial<UpdateCustomerDto>): Promise<boolean> {
    const dto = sanitizeUpdateDto(input);
    formPending.value = true;

    try {
      await api.put(`/customers/${id}`, dto as unknown as Record<string, unknown>);
      toast.success("Đã cập nhật khách hàng");
      await fetchCustomer(id);
      return true;
    } catch (error) {
      toast.error("Cập nhật thất bại", getErrorMessage(error));
      return false;
    } finally {
      formPending.value = false;
    }
  }

  async function blockCustomer(id: string): Promise<boolean> {
    blockPending.value = true;
    try {
      await api.post(`/customers/${id}/block`);
      toast.success("Đã khóa khách hàng");
      await fetchCustomer(id);
      return true;
    } catch (error) {
      toast.error("Thất bại", getErrorMessage(error));
      return false;
    } finally {
      blockPending.value = false;
    }
  }

  function setPage(page: number): void {
    filters.value = { ...filters.value, page };
  }

  function setSearch(search: string): void {
    filters.value = { ...filters.value, search, page: 1 };
  }

  function resetFilters(): void {
    filters.value = { search: "", page: 1, per_page: 20 };
  }

  return {
    listState, listError, customers, meta, filters,
    detailState, detailError, currentCustomer,
    formPending, blockPending,
    isListLoading, isDetailLoading,
    fetchCustomers, fetchCustomer,
    createCustomer, updateCustomer, blockCustomer,
    setPage, setSearch, resetFilters,
    searchCustomersUnified, checkPhoneDuplicate, createCustomerWithResolution,
  };
});
