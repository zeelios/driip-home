<template>
  <div
    class="z-textarea-wrap"
    :class="{ 'z-textarea-wrap--error': !!error, 'z-textarea-wrap--disabled': disabled }"
  >
    <label v-if="label" :for="textareaId" class="z-textarea-label">
      {{ label }}
    </label>

    <textarea
      :id="textareaId"
      v-bind="$attrs"
      class="z-textarea"
      :value="modelValue ?? ''"
      :placeholder="placeholder"
      :disabled="disabled"
      :required="required"
      :rows="rows"
      :maxlength="maxlength"
      :autocomplete="autocomplete"
      @input="onInput"
      @blur="$emit('blur', $event)"
    />

    <p v-if="error" class="z-textarea-error" role="alert">{{ error }}</p>
    <p v-else-if="hint" class="z-textarea-hint">{{ hint }}</p>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";

const props = withDefaults(
  defineProps<{
    modelValue?: string | number | null;
    label?: string;
    placeholder?: string;
    disabled?: boolean;
    required?: boolean;
    error?: string | null;
    hint?: string;
    id?: string;
    rows?: number;
    maxlength?: number | string;
    autocomplete?: string;
  }>(),
  { disabled: false, required: false, rows: 4 }
);

const emit = defineEmits<{
  "update:modelValue": [value: string];
  blur: [event: FocusEvent];
}>();

const textareaId = computed(
  () => props.id ?? `z-textarea-${Math.random().toString(36).slice(2, 7)}`
);

function onInput(event: Event): void {
  const target = event.target as HTMLTextAreaElement;
  emit("update:modelValue", target.value);
}
</script>

<style scoped>
.z-textarea-wrap {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}

.z-textarea-label {
  font-size: 0.75rem;
  font-weight: 600;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: #6b6b68;
}

.z-textarea {
  width: 100%;
  padding: 0.75rem 0.875rem;
  border: 1px solid rgba(0, 0, 0, 0.14);
  border-radius: 8px;
  background: #fff;
  font: inherit;
  font-size: 0.875rem;
  color: #1a1a18;
  outline: none;
  transition: border-color 150ms, box-shadow 150ms;
  min-width: 0;
  resize: vertical;
}

.z-textarea::placeholder {
  color: #b0b0ad;
}

.z-textarea:focus {
  border-color: #111110;
  box-shadow: 0 0 0 3px rgba(17, 17, 16, 0.08);
}

.z-textarea:disabled {
  background: #f5f5f4;
  color: #a0a09d;
  cursor: not-allowed;
}

.z-textarea-wrap--error .z-textarea {
  border-color: #ef4444;
}

.z-textarea-wrap--error .z-textarea:focus {
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.12);
}

.z-textarea-wrap--disabled .z-textarea-label {
  color: #b0b0ad;
}

.z-textarea-error {
  margin: 0;
  font-size: 0.75rem;
  color: #ef4444;
}

.z-textarea-hint {
  margin: 0;
  font-size: 0.75rem;
  color: #9d9d9a;
}
</style>
