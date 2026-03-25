<template>
  <span
    class="block rounded-[5px] shrink-0 animate-shimmer"
    :class="[variantClass, { 'rounded-full': rounded }]"
    :style="styleObj"
    aria-hidden="true"
  />
</template>

<script setup lang="ts">
import { computed } from "vue";

const props = withDefaults(
  defineProps<{
    width?: string;
    height?: string;
    variant?: "text" | "rect" | "circle";
    rounded?: boolean;
  }>(),
  { variant: "text", rounded: false }
);

const variantClass = computed(() => {
  const map: Record<string, string> = {
    text: "h-3.5 w-full",
    rect: "h-10 w-full",
    circle: "h-10 w-10 rounded-full",
  };
  return map[props.variant];
});

const styleObj = computed(() => ({
  ...(props.width ? { width: props.width } : {}),
  ...(props.height ? { height: props.height } : {}),
}));
</script>

<style scoped>
@keyframes shimmer {
  0% {
    background-position: 200% 0;
  }
  100% {
    background-position: -200% 0;
  }
}
.animate-shimmer {
  background: linear-gradient(
    90deg,
    rgba(255, 255, 255, 0.04) 25%,
    rgba(255, 255, 255, 0.08) 50%,
    rgba(255, 255, 255, 0.04) 75%
  );
  background-size: 200% 100%;
  animation: shimmer 1.4s ease infinite;
}
</style>
