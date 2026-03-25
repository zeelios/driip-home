<template>
  <component
    :is="to ? NuxtLink : 'button'"
    class="inline-flex items-center justify-center gap-1.75 border border-transparent rounded-lg font-inherit font-semibold cursor-pointer no-underline transition-all duration-150 whitespace-nowrap relative select-none outline-none focus-visible:ring-[3px] focus-visible:ring-[#111110]/20 disabled:opacity-50 disabled:cursor-not-allowed disabled:pointer-events-none touch-manipulation"
    :class="[
      variantClass,
      sizeClass,
      {
        'opacity-50 cursor-not-allowed pointer-events-none':
          loading || disabled,
      },
      { 'p-2.5': iconOnly && size === 'sm' },
      { 'p-3': iconOnly && size === 'md' },
      { 'p-3.5': iconOnly && size === 'lg' },
    ]"
    :disabled="disabled || loading"
    :to="to"
    v-bind="$attrs"
  >
    <span
      v-if="loading"
      class="w-3.5 h-3.5 border-2 border-current border-t-transparent rounded-full animate-spin shrink-0"
      aria-hidden="true"
    />
    <span
      v-if="$slots.prefix && !loading"
      class="flex items-center"
      aria-hidden="true"
    >
      <slot name="prefix" />
    </span>
    <span v-if="!iconOnly"><slot /></span>
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
  {
    variant: "primary",
    size: "md",
    loading: false,
    disabled: false,
    iconOnly: false,
  }
);

const variantClass = computed((): string => {
  const variants: Record<BtnVariant, string> = {
    primary:
      "bg-white text-[#0a0a0a] border-white font-semibold hover:bg-white/90 hover:border-white/90 active:bg-white/80",
    secondary:
      "bg-white/10 text-white border-white/15 hover:bg-white/15 hover:border-white/25 active:bg-white/20",
    outline:
      "bg-transparent text-white/80 border-white/20 hover:bg-white/6 hover:border-white/35 hover:text-white",
    ghost:
      "bg-transparent text-white/60 border-transparent hover:bg-white/6 hover:text-white/90",
    danger:
      "bg-red-500/15 text-red-500 border-red-500/30 hover:bg-red-500/25 hover:border-red-500/50",
  };
  return variants[props.variant];
});

const sizeClass = computed((): string => {
  const sizes: Record<BtnSize, string> = {
    sm: props.iconOnly ? "min-w-11 min-h-11" : "py-1.5 px-3 md:py-1.5 md:px-3 text-[0.8125rem] min-h-11 md:min-h-0",
    md: props.iconOnly ? "min-w-11 min-h-11" : "py-2.5 px-4 md:py-2.5 md:px-4 text-[0.875rem] min-h-11 md:min-h-0",
    lg: props.iconOnly ? "min-w-12 min-h-12" : "py-3 px-5 md:py-3 md:px-5 text-[0.9375rem] min-h-12 md:min-h-0",
  };
  return sizes[props.size];
});
</script>
