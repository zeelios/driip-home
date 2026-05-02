// stores/cart.ts
// Cookie-based cart — stays client-side, converted to order on checkout.
// NOTE: Matches the existing home cart store design for easy migration.

import { defineStore } from "pinia";

export interface CartItem {
  productId: string;
  name: string;
  priceCents: number;
  quantity: number;
  size?: string;
  imageUrl?: string;
}

interface CartState {
  items: CartItem[];
}

const CART_KEY = "driip_cart";

function loadCart(): CartItem[] {
  if (typeof window === "undefined") return [];
  try {
    const raw = localStorage.getItem(CART_KEY);
    return raw ? JSON.parse(raw) : [];
  } catch {
    return [];
  }
}

function saveCart(items: CartItem[]) {
  if (typeof window === "undefined") return;
  localStorage.setItem(CART_KEY, JSON.stringify(items));
}

export const useCartStore = defineStore("cart", {
  state: (): CartState => ({
    items: loadCart(),
  }),

  getters: {
    totalItems: (state) => state.items.reduce((sum, i) => sum + i.quantity, 0),
    totalCents: (state) =>
      state.items.reduce((sum, i) => sum + i.priceCents * i.quantity, 0),
    totalFormatted: (state) => {
      const totalCents = state.items.reduce(
        (sum, i) => sum + i.priceCents * i.quantity,
        0
      );

      return (totalCents / 100).toLocaleString("vi-VN", {
        style: "currency",
        currency: "VND",
      });
    },
  },

  actions: {
    add(item: CartItem) {
      const existing = this.items.find(
        (i) => i.productId === item.productId && i.size === item.size
      );
      if (existing) {
        existing.quantity += item.quantity;
      } else {
        this.items.push({ ...item });
      }
      saveCart(this.items);
    },

    remove(productId: string, size?: string) {
      this.items = this.items.filter(
        (i) => !(i.productId === productId && i.size === size)
      );
      saveCart(this.items);
    },

    updateQuantity(productId: string, quantity: number, size?: string) {
      const item = this.items.find(
        (i) => i.productId === productId && i.size === size
      );
      if (item) {
        item.quantity = Math.max(0, quantity);
        if (item.quantity === 0) {
          this.remove(productId, size);
        } else {
          saveCart(this.items);
        }
      }
    },

    clear() {
      this.items = [];
      saveCart(this.items);
    },

    toOrderItems() {
      return this.items.map((i) => ({
        product_id: i.productId,
        quantity: i.quantity,
      }));
    },
  },
});
