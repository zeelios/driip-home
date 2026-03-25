<template>
  <div
    class="flex flex-col gap-1.5"
    :class="{
      '[&_input]:border-red-500 [&_input:focus]:shadow-[0_0_0_3px_rgba(239,68,68,0.15)]':
        !!error,
      '[&_label]:text-white/30': disabled,
    }"
  >
    <label
      v-if="label"
      :for="inputId"
      class="text-xs font-semibold tracking-[0.06em] uppercase text-white/50"
      >{{ label }}</label
    >
    <div class="relative flex items-center">
      <span
        v-if="$slots.prefix"
        class="absolute left-0 flex items-center justify-center w-10 text-white/40 pointer-events-none"
        aria-hidden="true"
      >
        <slot name="prefix" />
      </span>
      <input
        :id="inputId"
        v-bind="$attrs"
        class="w-full py-3 px-4 md:py-2.5 md:px-3.5 border border-white/12 rounded-lg bg-white/4 font-inherit text-base md:text-sm text-white/90 outline-none transition-all duration-150 min-w-0 min-h-11 md:min-h-0 focus:border-white/40 focus:shadow-[0_0_0_3px_rgba(255,255,255,0.08)] disabled:bg-white/2 disabled:text-white/35 disabled:cursor-not-allowed touch-manipulation"
        :class="{
          'pl-10': $slots.prefix,
          'pr-10': $slots.suffix,
        }"
        :type="type"
        :value="modelValue"
        :placeholder="placeholder"
        :disabled="disabled"
        :required="required"
        :autocomplete="autocomplete"
        @input="onInput"
        @blur="$emit('blur', $event)"
      />
      <span
        v-if="$slots.suffix"
        class="absolute right-0 flex items-center justify-center w-10 text-white/40 pointer-events-none"
        aria-hidden="true"
      >
        <slot name="suffix" />
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

const inputId = computed(
  () => props.id ?? `z-input-${Math.random().toString(36).slice(2, 7)}`
);

function onInput(event: Event): void {
  const target = event.target as HTMLInputElement;
  emit("update:modelValue", target.value);
}
</script>
