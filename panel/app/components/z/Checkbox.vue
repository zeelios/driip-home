<template>
  <label
    class="inline-flex items-center gap-3 cursor-pointer touch-manipulation"
    :class="{ 'cursor-not-allowed opacity-40': disabled }"
  >
    <!-- Touch target wrapper - fixed size, no negative margins -->
    <div
      class="relative flex items-center justify-center shrink-0"
      :class="touchTargetClass"
    >
      <!-- Visual checkbox - strict fixed dimensions -->
      <div
        class="box-border rounded border-2 transition-colors duration-150 flex items-center justify-center overflow-hidden"
        :class="[
          modelValue
            ? 'bg-white border-white'
            : 'bg-transparent border-white/30 hover:border-white/60',
          sizeClass,
        ]"
      >
        <input
          v-bind="$attrs"
          type="checkbox"
          :checked="modelValue"
          :disabled="disabled"
          class="sr-only"
          @change="handleChange"
        />
        <!-- Checkmark - absolutely positioned, never affects layout -->
        <svg
          v-if="modelValue"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="3"
          stroke-linecap="round"
          stroke-linejoin="round"
          class="absolute inset-0 m-auto text-black w-full h-full p-0.5"
        >
          <polyline points="20 6 9 17 4 12" />
        </svg>
      </div>
    </div>
    <span
      v-if="label"
      class="text-sm text-white/80 select-none font-medium"
      :class="{ 'text-white/40': disabled }"
    >
      {{ label }}
    </span>
  </label>
</template>

<script setup lang="ts">
import { computed } from "vue";

export interface CheckboxProps {
  modelValue: boolean;
  label?: string;
  disabled?: boolean;
  size?: "sm" | "md" | "lg";
}

const props = withDefaults(defineProps<CheckboxProps>(), {
  disabled: false,
  size: "md",
});

const emit = defineEmits<{
  "update:modelValue": [value: boolean];
  change: [value: boolean];
}>();

// Visual checkbox size - strict box-border sizing
const sizeClass = computed(() => {
  const sizes = {
    sm: "w-4 h-4 rounded-[3px]",
    md: "w-5 h-5 rounded",
    lg: "w-6 h-6 rounded-md",
  };
  return sizes[props.size];
});

// Touch target size - matches checkbox visual size exactly, no layout shift
const touchTargetClass = computed(() => {
  const sizes = {
    sm: "w-4 h-4",
    md: "w-5 h-5",
    lg: "w-6 h-6",
  };
  return sizes[props.size];
});

// Icon size - not needed anymore as checkmark uses absolute positioning
// Kept for backward compatibility
const iconSizeClass = computed(() => {
  const sizes = {
    sm: "w-3 h-3",
    md: "w-3.5 h-3.5",
    lg: "w-4 h-4",
  };
  return sizes[props.size];
});

function handleChange(event: Event): void {
  const target = event.target as HTMLInputElement;
  emit("update:modelValue", target.checked);
  emit("change", target.checked);
}
</script>
