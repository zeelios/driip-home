<template>
  <ZSelect
    :model-value="props.modelValue"
    :options="props.options"
    :label="props.label"
    :placeholder="props.placeholder"
    :loading="props.loading"
    :disabled="props.disabled"
    :error="props.error"
    @update:model-value="emit('update:modelValue', $event)"
  />
</template>

<script setup lang="ts">
import type { SelectOption } from "~/components/z/Select.vue";

const props = withDefaults(
  defineProps<{
    modelValue: string | number | null;
    options: SelectOption[];
    label?: string;
    placeholder?: string;
    loading?: boolean;
    disabled?: boolean;
    error?: string | null;
  }>(),
  {
    label: "Chọn tuỳ chọn",
    placeholder: "Chọn tuỳ chọn phù hợp",
    loading: false,
    disabled: false,
  }
);

const emit = defineEmits<{
  "update:modelValue": [value: string | number];
}>();
</script>
