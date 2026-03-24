<template>
  <div class="ccm-list">
    <div v-for="item in items" :key="item.id" class="ccm-card">
      <!-- Top row: name + delete -->
      <div class="ccm-card-head">
        <div class="ccm-name-block">
          <p class="ccm-name">CK {{ item.skuLabel }}</p>
          <p class="ccm-meta">Size {{ item.size }} · {{ item.colorLabel }}</p>
        </div>
        <button
          type="button"
          class="ccm-delete"
          aria-label="Xóa sản phẩm"
          @click="emit('remove', item.id)"
        >
          <span class="ccm-delete-icon">✕</span>
          <span class="ccm-delete-label">XÓA</span>
        </button>
      </div>

      <!-- Bottom row: qty stepper + price -->
      <div class="ccm-card-foot">
        <div class="ccm-qty">
          <button
            type="button"
            class="ccm-qty-btn"
            :disabled="item.boxes <= 1"
            aria-label="Giảm số hộp"
            @click="emit('decrease', item.id, item.boxes)"
          >
            −
          </button>
          <span class="ccm-qty-num">{{ item.boxes }}</span>
          <span class="ccm-qty-unit">hộp</span>
          <button
            type="button"
            class="ccm-qty-btn"
            aria-label="Tăng số hộp"
            @click="emit('increase', item.id, item.boxes)"
          >
            +
          </button>
        </div>

        <div class="ccm-price">
          <span class="ccm-price-final">{{ formatVndCurrency(item.finalTotal) }}</span>
          <span class="ccm-price-compare">{{ formatVndCurrency(item.compareTotal) }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { CartItem } from "~/stores/cart";
import { formatVndCurrency } from "~/utils/pricing";

defineProps<{
  items: CartItem[];
}>();

const emit = defineEmits<{
  remove: [id: string];
  increase: [id: string, boxes: number];
  decrease: [id: string, boxes: number];
}>();
</script>

<style scoped>
.ccm-list {
  display: flex;
  flex-direction: column;
  gap: 0;
  border: 1px solid rgba(255, 255, 255, 0.1);
  margin-bottom: 16px;
}

.ccm-card {
  padding: 16px 16px 14px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.ccm-card:last-child {
  border-bottom: none;
}

/* Head */
.ccm-card-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.ccm-name-block {
  flex: 1;
  min-width: 0;
}

.ccm-name {
  font-family: var(--font-display);
  font-size: 22px;
  letter-spacing: 0.03em;
  color: var(--white);
  line-height: 1.1;
  margin-bottom: 5px;
}

.ccm-meta {
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.14em;
  color: rgba(255, 255, 255, 0.4);
  text-transform: uppercase;
}

/* Delete button */
.ccm-delete {
  display: flex;
  align-items: center;
  gap: 5px;
  background: transparent;
  border: 1px solid rgba(255, 80, 80, 0.22);
  color: rgba(255, 90, 90, 0.6);
  padding: 6px 10px;
  cursor: pointer;
  flex-shrink: 0;
  transition: color 0.18s, border-color 0.18s, background 0.18s;
}

.ccm-delete:active {
  color: rgba(255, 90, 90, 1);
  border-color: rgba(255, 90, 90, 0.55);
  background: rgba(255, 80, 80, 0.08);
}

.ccm-delete-icon {
  font-size: 11px;
  line-height: 1;
}

.ccm-delete-label {
  font-size: 9px;
  font-weight: 800;
  letter-spacing: 0.2em;
  text-transform: uppercase;
}

/* Foot */
.ccm-card-foot {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

/* Qty stepper */
.ccm-qty {
  display: flex;
  align-items: center;
  gap: 0;
  border: 1px solid rgba(255, 255, 255, 0.14);
}

.ccm-qty-btn {
  width: 44px;
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: transparent;
  border: none;
  color: rgba(255, 255, 255, 0.55);
  font-size: 20px;
  cursor: pointer;
  transition: color 0.15s, background 0.15s;
  -webkit-tap-highlight-color: transparent;
}

.ccm-qty-btn:active:not(:disabled) {
  color: var(--white);
  background: rgba(255, 255, 255, 0.08);
}

.ccm-qty-btn:disabled {
  opacity: 0.22;
  cursor: not-allowed;
}

.ccm-qty-num {
  min-width: 40px;
  text-align: center;
  font-family: var(--font-display);
  font-size: 26px;
  font-weight: 700;
  color: var(--white);
  line-height: 44px;
  border-left: 1px solid rgba(255, 255, 255, 0.1);
  border-right: 1px solid rgba(255, 255, 255, 0.1);
  padding: 0 4px;
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex: 0 0 auto;
}

.ccm-qty-unit {
  font-size: 9px;
  font-weight: 700;
  letter-spacing: 0.18em;
  color: rgba(255, 255, 255, 0.3);
  text-transform: uppercase;
  padding: 0 10px 0 8px;
  height: 44px;
  display: flex;
  align-items: center;
  border-right: 1px solid rgba(255, 255, 255, 0.1);
}

/* Price */
.ccm-price {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 3px;
}

.ccm-price-final {
  font-size: 15px;
  font-weight: 700;
  letter-spacing: 0.04em;
  color: var(--white);
}

.ccm-price-compare {
  font-size: 11px;
  color: rgba(255, 255, 255, 0.22);
  text-decoration: line-through;
}
</style>
