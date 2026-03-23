import { ref, computed } from "vue";
import { defineStore } from "pinia";
import { useApi } from "~/composables/useApi";
import { useToast } from "~/composables/useToast";
import { getErrorMessage, sanitizeString, sanitizeEmail, sanitizePhone } from "~/utils/format";
import type { StaffUserModel } from "~~/types/generated/backend-models.generated";
import type { CreateStaffDto, UpdateStaffDto } from "~~/types/backend-contracts.generated";

type LoadState = "idle" | "loading" | "loaded" | "error";

interface ListMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

interface StaffListResponse {
  data: StaffUserModel[];
  meta: ListMeta;
}

interface StaffDetailResponse {
  data: StaffUserModel;
}

interface StaffFilters {
  search: string;
  department: string;
  status: string;
  page: number;
  per_page: number;
}

export const useStaffStore = defineStore("staff", () => {
  const api = useApi();
  const toast = useToast();

  const listState = ref<LoadState>("idle");
  const listError = ref<string | null>(null);
  const staffList = ref<StaffUserModel[]>([]);
  const meta = ref<ListMeta>({ current_page: 1, last_page: 1, per_page: 20, total: 0 });
  const filters = ref<StaffFilters>({ search: "", department: "", status: "", page: 1, per_page: 20 });

  const detailState = ref<LoadState>("idle");
  const detailError = ref<string | null>(null);
  const currentStaff = ref<StaffUserModel | null>(null);

  const formPending = ref(false);
  const deletePending = ref(false);

  const isListLoading = computed(() => listState.value === "loading");
  const isDetailLoading = computed(() => detailState.value === "loading");

  async function fetchStaff(): Promise<void> {
    listState.value = "loading";
    listError.value = null;

    try {
      const f = filters.value;
      const params: Record<string, string> = {
        page: String(f.page),
        per_page: String(f.per_page),
      };
      if (f.search.trim()) params["search"] = f.search.trim();
      if (f.department) params["department"] = f.department;
      if (f.status) params["status"] = f.status;

      const query = new URLSearchParams(params).toString();
      const response = await api.get<StaffListResponse>(`/staff?${query}`);
      staffList.value = response.data ?? [];
      meta.value = response.meta ?? { current_page: 1, last_page: 1, per_page: 20, total: 0 };
      listState.value = "loaded";
    } catch (error) {
      listState.value = "error";
      listError.value = getErrorMessage(error, "Không thể tải danh sách nhân viên.");
    }
  }

  async function fetchStaffMember(id: string): Promise<void> {
    detailState.value = "loading";
    detailError.value = null;
    currentStaff.value = null;

    try {
      const response = await api.get<StaffDetailResponse>(`/staff/${id}`);
      currentStaff.value = response.data ?? null;
      detailState.value = "loaded";
    } catch (error) {
      detailState.value = "error";
      detailError.value = getErrorMessage(error, "Không thể tải thông tin nhân viên.");
    }
  }

  function sanitizeCreateDto(input: Partial<CreateStaffDto>): CreateStaffDto | null {
    const name = sanitizeString(input.name);
    const email = sanitizeEmail(input.email ?? "");
    const password = sanitizeString(input.password);

    if (!name || !email || !password) return null;
    if (password.length < 8) return null;

    return {
      name,
      email,
      password,
      phone: input.phone ? sanitizePhone(input.phone) || null : null,
      department: input.department ? sanitizeString(input.department) : null,
      position: input.position ? sanitizeString(input.position) : null,
      hired_at: input.hired_at ? sanitizeString(input.hired_at) : null,
      notes: input.notes ? sanitizeString(input.notes) : null,
      roles: Array.isArray(input.roles) ? input.roles : [],
    };
  }

  function sanitizeUpdateDto(input: Partial<UpdateStaffDto>): UpdateStaffDto {
    const dto: UpdateStaffDto = {};
    if (input.name !== undefined) dto.name = sanitizeString(input.name);
    if (input.email !== undefined) dto.email = sanitizeEmail(input.email) || undefined;
    if (input.phone !== undefined) dto.phone = sanitizePhone(input.phone ?? "") || null;
    if (input.department !== undefined) dto.department = sanitizeString(input.department) || null;
    if (input.position !== undefined) dto.position = sanitizeString(input.position) || null;
    if (input.status !== undefined) dto.status = sanitizeString(input.status);
    if (input.roles !== undefined) dto.roles = Array.isArray(input.roles) ? input.roles : [];
    return dto;
  }

  async function createStaffMember(input: Partial<CreateStaffDto>): Promise<boolean> {
    const dto = sanitizeCreateDto(input);
    if (!dto) {
      toast.error("Dữ liệu không hợp lệ", "Họ tên, email và mật khẩu (ít nhất 8 ký tự) là bắt buộc.");
      return false;
    }

    formPending.value = true;
    try {
      await api.post("/staff", dto);
      toast.success("Đã tạo nhân viên");
      await fetchStaff();
      return true;
    } catch (error) {
      toast.error("Tạo thất bại", getErrorMessage(error));
      return false;
    } finally {
      formPending.value = false;
    }
  }

  async function updateStaffMember(id: string, input: Partial<UpdateStaffDto>): Promise<boolean> {
    const dto = sanitizeUpdateDto(input);
    formPending.value = true;

    try {
      await api.put(`/staff/${id}`, dto);
      toast.success("Đã cập nhật nhân viên");
      await fetchStaffMember(id);
      return true;
    } catch (error) {
      toast.error("Cập nhật thất bại", getErrorMessage(error));
      return false;
    } finally {
      formPending.value = false;
    }
  }

  async function deleteStaffMember(id: string): Promise<boolean> {
    deletePending.value = true;
    try {
      await api.delete(`/staff/${id}`);
      toast.success("Đã xóa nhân viên");
      staffList.value = staffList.value.filter((s) => s.id !== id);
      meta.value = { ...meta.value, total: Math.max(0, meta.value.total - 1) };
      return true;
    } catch (error) {
      toast.error("Xóa thất bại", getErrorMessage(error));
      return false;
    } finally {
      deletePending.value = false;
    }
  }

  function setPage(page: number): void {
    filters.value = { ...filters.value, page };
  }

  function setFilters(partial: Partial<Omit<StaffFilters, "page">>): void {
    filters.value = { ...filters.value, ...partial, page: 1 };
  }

  function resetFilters(): void {
    filters.value = { search: "", department: "", status: "", page: 1, per_page: 20 };
  }

  return {
    listState, listError, staffList, meta, filters,
    detailState, detailError, currentStaff,
    formPending, deletePending,
    isListLoading, isDetailLoading,
    fetchStaff, fetchStaffMember,
    createStaffMember, updateStaffMember, deleteStaffMember,
    setPage, setFilters, resetFilters,
  };
});
