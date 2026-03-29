import { defineStore } from "pinia";

export interface PendingPOItem {
  id: string;
  product_id: string;
  sku: string;
  product_name: string;
  size_option_id: string | null;
  size_display: string | null;
  color: string | null;
  quantity_needed: number;
  quantity_to_order: number;
  unit_cost: number;
  warehouse_id?: string;
  warehouse_name?: string;
  order_id?: string;
  order_number?: string;
}

interface PendingPOState {
  items: PendingPOItem[];
  source: "low_stock" | "unfulfillable" | "manual" | null;
}

export const usePendingPOStore = defineStore("pendingPO", {
  state: (): PendingPOState => ({
    items: [],
    source: null,
  }),

  getters: {
    hasItems: (state) => state.items.length > 0,
    itemCount: (state) => state.items.length,
  },

  actions: {
    setItems(items: PendingPOItem[], source: "low_stock" | "unfulfillable" | "manual") {
      this.items = items;
      this.source = source;
    },

    addItem(item: PendingPOItem) {
      this.items.push(item);
    },

    removeItem(index: number) {
      this.items.splice(index, 1);
    },

    updateItem(index: number, updates: Partial<PendingPOItem>) {
      this.items[index] = { ...this.items[index], ...updates };
    },

    clearItems() {
      this.items = [];
      this.source = null;
    },

    // For persisting to localStorage across page reloads
    persistToStorage() {
      if (typeof window !== "undefined") {
        localStorage.setItem("pendingPOItems", JSON.stringify(this.items));
        localStorage.setItem("pendingPOSource", this.source || "");
      }
    },

    restoreFromStorage() {
      if (typeof window !== "undefined") {
        const items = localStorage.getItem("pendingPOItems");
        const source = localStorage.getItem("pendingPOSource");
        if (items) {
          this.items = JSON.parse(items);
        }
        if (source) {
          this.source = source as "low_stock" | "unfulfillable" | "manual";
        }
      }
    },

    clearStorage() {
      if (typeof window !== "undefined") {
        localStorage.removeItem("pendingPOItems");
        localStorage.removeItem("pendingPOSource");
      }
    },
  },
});
