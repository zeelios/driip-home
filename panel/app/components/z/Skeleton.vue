<template>
  <span
    class="z-skeleton"
    :class="[variantClass, { 'z-skeleton--rounded': rounded }]"
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

const variantClass = computed(() => `z-skeleton--${props.variant}`);

const styleObj = computed(() => ({
  ...(props.width ? { width: props.width } : {}),
  ...(props.height ? { height: props.height } : {}),
}));
</script>

<style scoped>
.z-skeleton {
  display: block;
  background: linear-gradient(90deg, #ebebeb 25%, #f5f5f4 50%, #ebebeb 75%);
  background-size: 200% 100%;
  animation: z-shimmer 1.4s ease infinite;
  border-radius: 5px;
  flex-shrink: 0;
}

.z-skeleton--text {
  height: 0.875rem;
  width: 100%;
}

.z-skeleton--rect {
  height: 2.5rem;
  width: 100%;
}

.z-skeleton--circle {
  height: 2.5rem;
  width: 2.5rem;
  border-radius: 50%;
}

.z-skeleton--rounded {
  border-radius: 999px;
}

@keyframes z-shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}
</style>
