<template>
  <div
    class="flex flex-col gap-1.5"
    :class="{
      '[&_textarea]:border-red-500 [&_textarea:focus]:shadow-[0_0_0_3px_rgba(239,68,68,0.15)]':
        !!error,
      '[&_label]:text-white/30': disabled,
    }"
  >
    <label
      v-if="label"
      :for="textareaId"
      class="text-xs font-semibold tracking-[0.06em] uppercase text-white/50"
    >
      {{ label }}
    </label>

    <textarea
      :id="textareaId"
      v-bind="$attrs"
      class="w-full py-3 px-4 md:py-3 md:px-3.5 border border-white/12 rounded-lg bg-white/4 font-inherit text-base md:text-sm text-white/90 outline-none transition-all duration-150 min-w-0 resize-y min-h-28 md:min-h-24 focus:border-white/40 focus:shadow-[0_0_0_3px_rgba(255,255,255,0.08)] disabled:bg-white/2 disabled:text-white/35 disabled:cursor-not-allowed touch-manipulation"
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

    <p v-if="error" class="m-0 text-xs text-red-500" role="alert">
      {{ error }}
    </p>
    <p v-else-if="hint" class="m-0 text-xs text-white/45">{{ hint }}</p>
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
