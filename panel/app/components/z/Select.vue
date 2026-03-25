<template>
  <div
    class="flex flex-col gap-1.5"
    :class="{
      '[&_select]:border-red-500 [&_select:focus]:shadow-[0_0_0_3px_rgba(239,68,68,0.15)]':
        !!error,
    }"
  >
    <label
      v-if="label"
      :for="selectId"
      class="text-xs font-semibold tracking-[0.06em] uppercase text-white/50"
      >{{ label }}</label
    >
    <div class="relative">
      <select
        :id="selectId"
        v-bind="$attrs"
        class="w-full py-2.5 pr-9 pl-3.5 border border-white/12 rounded-lg bg-white/4 font-inherit text-sm text-white/90 outline-none appearance-none cursor-pointer transition-all duration-150 focus:border-white/40 focus:shadow-[0_0_0_3px_rgba(255,255,255,0.08)] disabled:bg-white/2 disabled:text-white/35 disabled:cursor-not-allowed"
        :value="modelValue"
        :disabled="disabled"
        :required="required"
        @change="onChange"
      >
        <option v-if="placeholder" value="" disabled :selected="!modelValue">
          {{ placeholder }}
        </option>
        <option
          v-for="opt in options"
          :key="String(opt.value)"
          :value="opt.value"
          :disabled="opt.disabled"
        >
          {{ opt.label }}
        </option>
      </select>
      <span
        class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center text-white/40 pointer-events-none"
        aria-hidden="true"
      >
        <svg
          width="14"
          height="14"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2.5"
        >
          <polyline points="6 9 12 15 18 9" />
        </svg>
      </span>
    </div>
    <p v-if="error" class="m-0 text-xs text-red-500" role="alert">
      {{ error }}
    </p>
    <p v-else-if="hint" class="m-0 text-xs text-white/45">{{ hint }}</p>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";

export interface SelectOption {
  value: string | number;
  label: string;
  disabled?: boolean;
}

const props = withDefaults(
  defineProps<{
    modelValue?: string | number | null;
    options: SelectOption[];
    label?: string;
    placeholder?: string;
    disabled?: boolean;
    required?: boolean;
    error?: string | null;
    hint?: string;
    id?: string;
    searchable?: false;
  }>(),
  { disabled: false, required: false }
);

const emit = defineEmits<{
  "update:modelValue": [value: string | number];
  change: [value: string | number];
}>();

const selectId = computed(
  () => props.id ?? `z-select-${Math.random().toString(36).slice(2, 7)}`
);

function onChange(event: Event): void {
  const val = (event.target as HTMLSelectElement).value;
  emit("update:modelValue", val);
  emit("change", val);
}
</script>
