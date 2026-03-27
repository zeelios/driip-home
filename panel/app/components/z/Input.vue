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
    <div class="relative block">
      <span
        v-if="$slots.prefix"
        class="absolute left-0 top-1/2 -translate-y-1/2 flex items-center justify-center w-10 text-white/40 pointer-events-none"
        aria-hidden="true"
      >
        <slot name="prefix" />
      </span>
      <input
        :id="inputId"
        v-bind="$attrs"
        :class="[
          'w-full border border-white/12 rounded-lg bg-white/4 font-inherit text-white/90 outline-none transition-all duration-150 min-w-0 focus:border-white/40 focus:shadow-[0_0_0_3px_rgba(255,255,255,0.08)] disabled:bg-white/2 disabled:text-white/35 disabled:cursor-not-allowed touch-manipulation',
          sizeClass,
          $slots.prefix ? 'pl-10' : '',
          $slots.suffix ? 'pr-10' : '',
        ]"
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
    size?: "sm" | "md" | "lg";
  }>(),
  { type: "text", disabled: false, required: false, size: "md" }
);

const emit = defineEmits<{
  "update:modelValue": [value: string];
  blur: [event: FocusEvent];
}>();

const inputId = computed(
  () => props.id ?? `z-input-${Math.random().toString(36).slice(2, 7)}`
);

const sizeClass = computed((): string => {
  const sizes: Record<"sm" | "md" | "lg", string> = {
    sm: "py-1.5 px-3 text-[0.8125rem] min-h-11 md:min-h-0 leading-none box-border",
    md: "py-2.5 px-4 text-[0.875rem] min-h-11 md:min-h-0 leading-none box-border",
    lg: "py-3 px-5 text-[0.9375rem] min-h-12 md:min-h-0 leading-none box-border",
  };
  return sizes[props.size];
});

function onInput(event: Event): void {
  const target = event.target as HTMLInputElement;
  emit("update:modelValue", target.value);
}
</script>
