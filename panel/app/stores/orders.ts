import { ref, computed } from "vue";
import { defineStore } from "pinia";
import { useApi } from "~/composables/useApi";
import { useToast } from "~/composables/useToast";
import { getErrorMessage } from "~/utils/format";
import type { OrderModel } from "~~/types/generated/backend-models.generated";

type LoadState = "idle" | "loading" | "loaded" | "error";

interface OrderListMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

interface OrderListResponse {
  data: OrderModel[];
  meta: OrderListMeta;
}

interface OrderDetailResponse {
  data: OrderModel;
}

interface OrderFilters {
  search: string;
  status: string;
  payment_status: string;
  page: number;
  per_page: number;
}

export const useOrdersStore = defineStore("orders", () => {
  const api = useApi();
  const toast = useToast();

  // ── list state ──
  const listState = ref<LoadState>("idle");
  const listError = ref<string | null>(null);
  const orders = ref<OrderModel[]>([]);
  const meta = ref<OrderListMeta>({ current_page: 1, last_page: 1, per_page: 20, total: 0 });
  const filters = ref<OrderFilters>({
    search: "",
    status: "",
    payment_status: "",
    page: 1,
    per_page: 20,
  });

  // ── detail state ──
  const detailState = ref<LoadState>("idle");
  const detailError = ref<string | null>(null);
  const currentOrder = ref<OrderModel | null>(null);

  // ── action state ──
  const actionPending = ref<Record<string, boolean>>({});

  const isListLoading = computed(() => listState.value === "loading");
  const isDetailLoading = computed(() => detailState.value === "loading");

  function setActionPending(orderId: string, pending: boolean): void {
    actionPending.value = { ...actionPending.value, [orderId]: pending };
  }

  function buildParams(): Record<string, string> {
    const f = filters.value;
    const params: Record<string, string> = {
      page: String(f.page),
      per_page: String(f.per_page),
    };
    if (f.search.trim()) params["search"] = f.search.trim();
    if (f.status) params["status"] = f.status;
    if (f.payment_status) params["payment_status"] = f.payment_status;
    return params;
  }

  async function fetchOrders(): Promise<void> {
    listState.value = "loading";
    listError.value = null;

    try {
      const params = buildParams();
      const query = new URLSearchParams(params).toString();
      const response = await api.get<OrderListResponse>(`/orders?${query}`);
      orders.value = response.data ?? [];
      meta.value = response.meta ?? { current_page: 1, last_page: 1, per_page: 20, total: 0 };
      listState.value = "loaded";
    } catch (error) {
      listState.value = "error";
      listError.value = getErrorMessage(error, "Không thể tải danh sách đơn hàng.");
    }
  }

  async function fetchOrder(id: string): Promise<void> {
    detailState.value = "loading";
    detailError.value = null;
    currentOrder.value = null;

    try {
      const response = await api.get<OrderDetailResponse>(`/orders/${id}`);
      currentOrder.value = response.data ?? null;
      detailState.value = "loaded";
    } catch (error) {
      detailState.value = "error";
      detailError.value = getErrorMessage(error, "Không thể tải thông tin đơn hàng.");
    }
  }

  async function confirmOrder(id: string): Promise<boolean> {
    setActionPending(id, true);
    try {
      await api.post(`/orders/${id}/confirm`);
      toast.success("Đã xác nhận đơn hàng");
      await fetchOrder(id);
      return true;
    } catch (error) {
      toast.error("Xác nhận thất bại", getErrorMessage(error));
      return false;
    } finally {
      setActionPending(id, false);
    }
  }

  async function cancelOrder(id: string, reason: string): Promise<boolean> {
    setActionPending(id, true);
    try {
      await api.post(`/orders/${id}/cancel`, { reason });
      toast.success("Đã hủy đơn hàng");
      await fetchOrder(id);
      return true;
    } catch (error) {
      toast.error("Hủy đơn thất bại", getErrorMessage(error));
      return false;
    } finally {
      setActionPending(id, false);
    }
  }

  async function packOrder(id: string): Promise<boolean> {
    setActionPending(id, true);
    try {
      await api.post(`/orders/${id}/pack`);
      toast.success("Đã đánh dấu đóng gói");
      await fetchOrder(id);
      return true;
    } catch (error) {
      toast.error("Thất bại", getErrorMessage(error));
      return false;
    } finally {
      setActionPending(id, false);
    }
  }

  function setPage(page: number): void {
    filters.value = { ...filters.value, page };
  }

  function setFilters(partial: Partial<Omit<OrderFilters, "page">>): void {
    filters.value = { ...filters.value, ...partial, page: 1 };
  }

  function resetFilters(): void {
    filters.value = { search: "", status: "", payment_status: "", page: 1, per_page: 20 };
  }

  return {
    listState, listError, orders, meta, filters,
    detailState, detailError, currentOrder,
    actionPending, isListLoading, isDetailLoading,
    fetchOrders, fetchOrder,
    confirmOrder, cancelOrder, packOrder,
    setPage, setFilters, resetFilters,
  };
});
