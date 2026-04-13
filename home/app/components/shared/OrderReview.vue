<script setup lang="ts">
import { computed } from "vue";
import {
  META_ORDER_PROFILE_COOKIE_KEY,
  type MetaOrderProfileCookie,
} from "~/utils/meta-conversions";

interface ReviewItem {
  label: string;
  meta: string;
  price: string;
}

interface ReviewOrder {
  firstName?: string;
  lastName?: string;
  phone?: string;
  fullAddress?: string;
  province?: string;
  dob?: string;
}

const props = defineProps<{
  items: ReviewItem[];
  order: ReviewOrder;
  totalLabel: string;
  totalValue: string;
}>();

const cookie = useCookie<MetaOrderProfileCookie | null>(
  META_ORDER_PROFILE_COOKIE_KEY
);

const resolved = computed(() => ({
  firstName: props.order.firstName || cookie.value?.firstName || "",
  lastName: props.order.lastName || cookie.value?.lastName || "",
  phone: props.order.phone || cookie.value?.phone || "",
  fullAddress: props.order.fullAddress || cookie.value?.fullAddress || "",
  province: props.order.province || cookie.value?.province || "",
  dob: props.order.dob || cookie.value?.dob || "",
}));

const fullName = computed(() =>
  `${resolved.value.firstName} ${resolved.value.lastName}`.trim()
);
</script>

<template>
  <div class="or-wrap">
    <!-- ── CART ITEMS ─────────────────────────────────────────── -->
    <div class="or-section">
      <p class="or-label">GIỎ HÀNG</p>
      <div class="or-item-list">
        <div v-for="(item, i) in items" :key="i" class="or-item">
          <div class="or-item-info">
            <span class="or-item-name">{{ item.label }}</span>
            <span class="or-item-meta">{{ item.meta }}</span>
          </div>
          <span class="or-item-price">{{ item.price }}</span>
        </div>
      </div>
    </div>

    <!-- ── PRICE SLOT or DEFAULT TOTAL ───────────────────────── -->
    <div class="or-section or-section--total">
      <slot name="pricing">
        <div class="or-total-row">
          <span class="or-total-label">{{ totalLabel }}</span>
          <span class="or-total-value">{{ totalValue }}</span>
        </div>
      </slot>
    </div>

    <!-- ── SHIPPING INFO ──────────────────────────────────────── -->
    <div class="or-section">
      <p class="or-label">THÔNG TIN NHẬN HÀNG</p>
      <div class="or-info-list">
        <div class="or-info-row">
          <span class="or-info-key">NGƯỜI NHẬN</span>
          <span class="or-info-val">{{ fullName }}</span>
        </div>
        <div class="or-info-row">
          <span class="or-info-key">SỐ ĐT</span>
          <span class="or-info-val">{{ resolved.phone }}</span>
        </div>
        <div class="or-info-row">
          <span class="or-info-key">ĐỊA CHỈ</span>
          <span class="or-info-val"
            >{{ resolved.fullAddress }}, {{ resolved.province }}</span
          >
        </div>
        <div v-if="resolved.dob" class="or-info-row">
          <span class="or-info-key">NGÀY SINH</span>
          <span class="or-info-val">{{ resolved.dob }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* ── WRAPPER ─────────────────────────────────────────────────────── */
.or-wrap {
  border: 1px solid rgba(255, 255, 255, 0.08);
  background: rgba(255, 255, 255, 0.02);
  margin-bottom: 24px;
  overflow: hidden;
}

/* ── SECTIONS ────────────────────────────────────────────────────── */
.or-section {
  padding: 16px 20px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}
.or-section:last-child {
  border-bottom: none;
}
.or-section--total {
  padding: 12px 20px;
}

/* ── SECTION LABEL ───────────────────────────────────────────────── */
.or-label {
  margin: 0 0 10px 0;
  font-size: 8px;
  font-weight: 800;
  letter-spacing: 0.35em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.25);
}

/* ── CART ITEMS ──────────────────────────────────────────────────── */
.or-item-list {
  display: flex;
  flex-direction: column;
  gap: 0;
}

.or-item {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 12px;
  padding: 8px 0;
  border-bottom: 1px solid rgba(255, 255, 255, 0.04);
}
.or-item:last-child {
  border-bottom: none;
  padding-bottom: 0;
}
.or-item:first-child {
  padding-top: 0;
}

.or-item-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}

.or-item-name {
  font-size: 12px;
  font-weight: 600;
  letter-spacing: 0.04em;
  color: var(--white);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.or-item-meta {
  font-size: 10px;
  font-weight: 500;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.25);
}

.or-item-price {
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.04em;
  color: var(--white);
  white-space: nowrap;
  flex-shrink: 0;
}

/* ── TOTAL ───────────────────────────────────────────────────────── */
.or-total-row {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 12px;
}

.or-total-label {
  font-size: 9px;
  font-weight: 800;
  letter-spacing: 0.3em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.4);
}

.or-total-value {
  font-family: var(--font-display);
  font-size: 22px;
  font-weight: 700;
  letter-spacing: 0.02em;
  color: var(--white);
}

/* ── SHIPPING INFO ───────────────────────────────────────────────── */
.or-info-list {
  display: flex;
  flex-direction: column;
  gap: 0;
}

.or-info-row {
  display: grid;
  grid-template-columns: 80px 1fr;
  gap: 12px;
  align-items: baseline;
  padding: 6px 0;
  border-bottom: 1px solid rgba(255, 255, 255, 0.04);
}
.or-info-row:last-child {
  border-bottom: none;
  padding-bottom: 0;
}
.or-info-row:first-child {
  padding-top: 0;
}

.or-info-key {
  font-size: 8px;
  font-weight: 700;
  letter-spacing: 0.2em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.2);
  flex-shrink: 0;
}

.or-info-val {
  font-size: 12px;
  font-weight: 400;
  color: rgba(255, 255, 255, 0.75);
  line-height: 1.4;
}

::placeholder {
  color: rgba(255, 255, 255, 0.25);
}

/* ── DESKTOP ─────────────────────────────────────────────────────── */
@media (min-width: 640px) {
  .or-section {
    padding: 14px 24px;
  }
  .or-section--total {
    padding: 10px 24px;
  }
  .or-item-name {
    font-size: 11px;
  }
  .or-item-meta {
    font-size: 9px;
  }
  .or-item-price {
    font-size: 11px;
  }
  .or-total-value {
    font-size: 20px;
  }
  .or-info-row {
    grid-template-columns: 90px 1fr;
    padding: 5px 0;
  }
  .or-info-val {
    font-size: 11px;
  }
}

/* ── MOBILE ──────────────────────────────────────────────────────── */
@media (max-width: 480px) {
  .or-section {
    padding: 14px 16px;
  }
  .or-section--total {
    padding: 10px 16px;
  }
  .or-item {
    flex-direction: column;
    gap: 3px;
    align-items: flex-start;
  }
  .or-item-price {
    font-size: 13px;
    color: rgba(255, 255, 255, 0.6);
  }
  .or-info-row {
    grid-template-columns: 1fr;
    gap: 2px;
  }
  .or-info-key {
    font-size: 7px;
  }
  .or-info-val {
    font-size: 13px;
  }
}
</style>
