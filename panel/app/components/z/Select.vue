<template>
  <div class="z-select-wrap" :class="{ 'z-select-wrap--error': !!error, 'z-select-wrap--disabled': disabled }">
    <label v-if="label" :for="selectId" class="z-select-label">{{ label }}</label>
    <div class="z-select-field">
      <select
        :id="selectId"
        v-bind="$attrs"
        class="z-select"
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
      <span class="z-select-chevron" aria-hidden="true">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <polyline points="6 9 12 15 18 9" />
        </svg>
      </span>
    </div>
    <p v-if="error" class="z-select-error" role="alert">{{ error }}</p>
    <p v-else-if="hint" class="z-select-hint">{{ hint }}</p>
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

<style scoped>
.z-select-wrap { display: flex; flex-direction: column; gap: 0.375rem; }
.z-select-label {
  font-size: 0.75rem;
  font-weight: 600;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: #6b6b68;
}
.z-select-field { position: relative; }
.z-select {
  width: 100%;
  padding: 0.5625rem 2.25rem 0.5625rem 0.875rem;
  border: 1px solid rgba(0,0,0,0.14);
  border-radius: 8px;
  background: #fff;
  font: inherit;
  font-size: 0.875rem;
  color: #1a1a18;
  outline: none;
  appearance: none;
  cursor: pointer;
  transition: border-color 150ms, box-shadow 150ms;
}
.z-select:focus {
  border-color: #111110;
  box-shadow: 0 0 0 3px rgba(17,17,16,0.08);
}
.z-select:disabled { background: #f5f5f4; color: #a0a09d; cursor: not-allowed; }
.z-select-chevron {
  position: absolute;
  right: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  display: flex;
  align-items: center;
  color: #9d9d9a;
  pointer-events: none;
}
.z-select-wrap--error .z-select { border-color: #ef4444; }
.z-select-wrap--error .z-select:focus { box-shadow: 0 0 0 3px rgba(239,68,68,0.12); }
.z-select-error { margin: 0; font-size: 0.75rem; color: #ef4444; }
.z-select-hint { margin: 0; font-size: 0.75rem; color: #9d9d9a; }
</style>
