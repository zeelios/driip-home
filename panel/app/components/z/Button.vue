<template>
  <component
    :is="to ? NuxtLink : 'button'"
    class="z-btn"
    :class="[variantClass, sizeClass, { 'z-btn--loading': loading, 'z-btn--icon-only': iconOnly }]"
    :disabled="disabled || loading"
    :to="to"
    v-bind="$attrs"
  >
    <span v-if="loading" class="z-btn__spinner" aria-hidden="true" />
    <span v-if="$slots.prefix && !loading" class="z-btn__prefix" aria-hidden="true">
      <slot name="prefix" />
    </span>
    <span v-if="!iconOnly" class="z-btn__label">
      <slot />
    </span>
    <span v-else aria-hidden="true"><slot /></span>
  </component>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { NuxtLink } from "#components";

type BtnVariant = "primary" | "secondary" | "ghost" | "danger" | "outline";
type BtnSize = "sm" | "md" | "lg";

const props = withDefaults(
  defineProps<{
    variant?: BtnVariant;
    size?: BtnSize;
    loading?: boolean;
    disabled?: boolean;
    iconOnly?: boolean;
    to?: string;
  }>(),
  { variant: "primary", size: "md", loading: false, disabled: false, iconOnly: false }
);

const variantClass = computed(() => `z-btn--${props.variant}`);
const sizeClass = computed(() => `z-btn--${props.size}`);
</script>

<style scoped>
.z-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.4375rem;
  border: 1px solid transparent;
  border-radius: 8px;
  font: inherit;
  font-weight: 550;
  cursor: pointer;
  text-decoration: none;
  transition: background 150ms, color 150ms, border-color 150ms, opacity 150ms, box-shadow 150ms;
  white-space: nowrap;
  position: relative;
  user-select: none;
  outline: none;
}
.z-btn:focus-visible {
  box-shadow: 0 0 0 3px rgba(17, 17, 16, 0.2);
}
.z-btn:disabled,
.z-btn--loading {
  opacity: 0.48;
  cursor: not-allowed;
  pointer-events: none;
}

/* sizes */
.z-btn--sm { padding: 0.375rem 0.75rem; font-size: 0.8125rem; }
.z-btn--md { padding: 0.5625rem 1rem; font-size: 0.875rem; }
.z-btn--lg { padding: 0.75rem 1.375rem; font-size: 0.9375rem; }
.z-btn--icon-only.z-btn--sm { padding: 0.375rem; }
.z-btn--icon-only.z-btn--md { padding: 0.5625rem; }
.z-btn--icon-only.z-btn--lg { padding: 0.75rem; }

/* variants */
.z-btn--primary { background: #111110; color: #fff; border-color: #111110; }
.z-btn--primary:hover:not(:disabled) { background: #2a2a28; border-color: #2a2a28; }
.z-btn--primary:active:not(:disabled) { background: #050504; }

.z-btn--secondary { background: #f5a623; color: #111110; border-color: #f5a623; }
.z-btn--secondary:hover:not(:disabled) { background: #e8971a; border-color: #e8971a; }
.z-btn--secondary:active:not(:disabled) { background: #d98b10; }

.z-btn--outline { background: transparent; color: #1a1a18; border-color: rgba(0,0,0,0.15); }
.z-btn--outline:hover:not(:disabled) { background: rgba(0,0,0,0.04); border-color: rgba(0,0,0,0.25); }

.z-btn--ghost { background: transparent; color: #444; border-color: transparent; }
.z-btn--ghost:hover:not(:disabled) { background: rgba(0,0,0,0.05); color: #111110; }

.z-btn--danger { background: #ef4444; color: #fff; border-color: #ef4444; }
.z-btn--danger:hover:not(:disabled) { background: #dc2626; border-color: #dc2626; }

/* spinner */
.z-btn__spinner {
  width: 0.875rem;
  height: 0.875rem;
  border: 2px solid currentColor;
  border-top-color: transparent;
  border-radius: 50%;
  animation: z-spin 0.6s linear infinite;
  flex-shrink: 0;
}
.z-btn__prefix { display: flex; align-items: center; }

@keyframes z-spin { to { transform: rotate(360deg); } }
</style>
