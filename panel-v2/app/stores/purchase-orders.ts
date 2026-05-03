import { defineStore } from "pinia";

export interface PurchaseOrderItem {
  id: string;
  purchase_order_id: string;
  product_id: string;
  warehouse_id: string;
  ordered_qty: number;
  received_qty: number;
  unit_cost_cents: number;
}

export interface PurchaseOrder {
  id: string;
  supplier_name: string;
  status: string;
  expected_date: string | null;
  notes: string | null;
  created_by: string | null;
  created_at: string;
  updated_at: string;
}

export interface PurchaseOrderDetail extends PurchaseOrder {
  items: PurchaseOrderItem[];
}

export const usePurchaseOrdersStore = defineStore("purchase-orders", {
  state: () => ({
    list: [] as PurchaseOrder[],
    current: null as PurchaseOrderDetail | null,
    filters: { page: 1, per_page: 20 },
    total: 0,
    loading: false,
    actionBusy: false,
    error: null as string | null,
  }),

  getters: {
    totalPages: (s) => Math.max(1, Math.ceil(s.total / s.filters.per_page)),
  },

  actions: {
    async fetchDetail(id: string) {
      this.loading = true;
      this.error = null;
      this.current = null;
      const { get } = useApi();
      try {
        const res = await get<any>(`/purchase-orders/${id}`);
        this.current = { ...(res.order ?? res), items: res.items ?? [] };
      } catch (e: any) {
        this.error = e?.data?.message ?? "Not found";
      } finally {
        this.loading = false;
      }
    },

    async fetchList() {
      this.loading = true;
      this.error = null;
      const { get } = useApi();
      try {
        const res = await get<any>("/purchase-orders", this.filters);
        this.list = Array.isArray(res) ? res : res.data ?? [];
        this.total = Array.isArray(res)
          ? res.length
          : res.meta?.total ?? this.list.length;
      } catch (e: any) {
        this.error = e?.data?.message ?? "Failed";
      } finally {
        this.loading = false;
      }
    },

    async create(payload: { supplier_name: string; notes?: string }) {
      this.actionBusy = true;
      const { post } = useApi();
      try {
        const po = await post<PurchaseOrder>("/purchase-orders", payload);
        this.list.unshift(po);
        return po;
      } catch (e: any) {
        this.error = e?.data?.message ?? "Failed";
        return null;
      } finally {
        this.actionBusy = false;
      }
    },

    async receive(id: string) {
      this.actionBusy = true;
      const { post } = useApi();
      try {
        const po = await post<PurchaseOrder>(`/purchase-orders/${id}/receive`);
        this._patch(po);
        return true;
      } catch (e: any) {
        this.error = e?.data?.message ?? "Failed";
        return false;
      } finally {
        this.actionBusy = false;
      }
    },

    async cancel(id: string) {
      this.actionBusy = true;
      const { post } = useApi();
      try {
        const po = await post<PurchaseOrder>(`/purchase-orders/${id}/cancel`);
        this._patch(po);
        return true;
      } catch (e: any) {
        this.error = e?.data?.message ?? "Failed";
        return false;
      } finally {
        this.actionBusy = false;
      }
    },

    _patch(po: PurchaseOrder) {
      const i = this.list.findIndex((x) => x.id === po.id);
      if (i !== -1) this.list[i] = po;
    },
  },
});
