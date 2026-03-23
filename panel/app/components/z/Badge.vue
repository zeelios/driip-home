<template>
  <span class="z-badge" :class="variantClass">
    <slot />
  </span>
</template>

<script setup lang="ts">
import { computed } from "vue";

type BadgeVariant =
  | "default"
  | "success"
  | "warning"
  | "danger"
  | "info"
  | "neutral"
  | "amber";

const props = withDefaults(
  defineProps<{
    variant?: BadgeVariant;
  }>(),
  { variant: "default" }
);

const variantClass = computed(() => `z-badge--${props.variant}`);
</script>

<style scoped>
.z-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.1875rem 0.5rem;
  border-radius: 999px;
  font-size: 0.6875rem;
  font-weight: 600;
  letter-spacing: 0.02em;
  white-space: nowrap;
  line-height: 1.4;
}

.z-badge--default,
.z-badge--neutral {
  background: #f0efed;
  color: #555;
}
.z-badge--success {
  background: #dcfce7;
  color: #15803d;
}
.z-badge--warning {
  background: #fef9c3;
  color: #a16207;
}
.z-badge--danger {
  background: #fee2e2;
  color: #b91c1c;
}
.z-badge--info {
  background: #dbeafe;
  color: #1d4ed8;
}
.z-badge--amber {
  background: rgba(245, 166, 35, 0.14);
  color: #b45309;
}
</style>
