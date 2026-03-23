<template>
  <div class="z-input-wrap" :class="{ 'z-input-wrap--error': !!error, 'z-input-wrap--disabled': disabled }">
    <label v-if="label" :for="inputId" class="z-input-label">{{ label }}</label>
    <div class="z-input-field">
      <span v-if="$slots.prefix" class="z-input-affix z-input-affix--pre" aria-hidden="true">
        <slot name="prefix" />
      </span>
      <input
        :id="inputId"
        v-bind="$attrs"
        class="z-input"
        :class="{ 'z-input--prefix': $slots.prefix, 'z-input--suffix': $slots.suffix }"
        :type="type"
        :value="modelValue"
        :placeholder="placeholder"
        :disabled="disabled"
        :required="required"
        :autocomplete="autocomplete"
        @input="onInput"
        @blur="$emit('blur', $event)"
      />
      <span v-if="$slots.suffix" class="z-input-affix z-input-affix--post" aria-hidden="true">
        <slot name="suffix" />
      </span>
    </div>
    <p v-if="error" class="z-input-error" role="alert">{{ error }}</p>
    <p v-else-if="hint" class="z-input-hint">{{ hint }}</p>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";

const props = withDefaults(
  defineProps<{
    modelValue?: string | number | null;
    label?: string;
    type?: string;
    placeholder?: string;
    disabled?: boolean;
    required?: boolean;
    error?: string | null;
    hint?: string;
    autocomplete?: string;
    id?: string;
  }>(),
  { type: "text", disabled: false, required: false }
);

const emit = defineEmits<{
  "update:modelValue": [value: string];
  blur: [event: FocusEvent];
}>();

const inputId = computed(() => props.id ?? `z-input-${Math.random().toString(36).slice(2, 7)}`);

function onInput(event: Event): void {
  const target = event.target as HTMLInputElement;
  emit("update:modelValue", target.value);
}
</script>

<style scoped>
.z-input-wrap { display: flex; flex-direction: column; gap: 0.375rem; }
.z-input-label {
  font-size: 0.75rem;
  font-weight: 600;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: #6b6b68;
}
.z-input-field { position: relative; display: flex; align-items: center; }
.z-input {
  width: 100%;
  padding: 0.5625rem 0.875rem;
  border: 1px solid rgba(0,0,0,0.14);
  border-radius: 8px;
  background: #fff;
  font: inherit;
  font-size: 0.875rem;
  color: #1a1a18;
  outline: none;
  transition: border-color 150ms, box-shadow 150ms;
  min-width: 0;
}
.z-input::placeholder { color: #b0b0ad; }
.z-input:focus {
  border-color: #111110;
  box-shadow: 0 0 0 3px rgba(17,17,16,0.08);
}
.z-input:disabled { background: #f5f5f4; color: #a0a09d; cursor: not-allowed; }
.z-input--prefix { padding-left: 2.5rem; }
.z-input--suffix { padding-right: 2.5rem; }
.z-input-affix {
  position: absolute;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 2.5rem;
  color: #9d9d9a;
  pointer-events: none;
}
.z-input-affix--pre { left: 0; }
.z-input-affix--post { right: 0; }
.z-input-wrap--error .z-input { border-color: #ef4444; }
.z-input-wrap--error .z-input:focus { box-shadow: 0 0 0 3px rgba(239,68,68,0.12); }
.z-input-wrap--disabled .z-input-label { color: #b0b0ad; }
.z-input-error { margin: 0; font-size: 0.75rem; color: #ef4444; }
.z-input-hint { margin: 0; font-size: 0.75rem; color: #9d9d9a; }
</style>
