<template>
  <span
    class="inline-flex items-center whitespace-nowrap rounded-full text-[0.6875rem] font-semibold tracking-[0.02em] leading-[1.4] py-0.75 px-2"
    :class="variantClass"
  >
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

const variantClass = computed(() => {
  const map: Record<BadgeVariant, string> = {
    default: "bg-white/10 text-white/85",
    neutral: "bg-white/10 text-white/85",
    success: "bg-white/[0.12] text-white/95",
    warning: "bg-white/[0.08] text-white/75",
    danger: "bg-red-500/15 text-red-500",
    info: "bg-white/[0.06] text-white/70",
    amber: "bg-amber-500/15 text-amber-500",
  };
  return map[props.variant];
});
</script>
