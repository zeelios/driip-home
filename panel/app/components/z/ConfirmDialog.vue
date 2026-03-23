<template>
  <ZModal
    :model-value="modelValue"
    :title="title"
    size="sm"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <p class="z-confirm__message">{{ message }}</p>

    <template #footer>
      <ZButton variant="outline" size="sm" :disabled="loading" @click="$emit('update:modelValue', false)">
        {{ cancelLabel }}
      </ZButton>
      <ZButton :variant="dangerous ? 'danger' : 'primary'" size="sm" :loading="loading" @click="$emit('confirm')">
        {{ confirmLabel }}
      </ZButton>
    </template>
  </ZModal>
</template>

<script setup lang="ts">
withDefaults(
  defineProps<{
    modelValue: boolean;
    title?: string;
    message: string;
    confirmLabel?: string;
    cancelLabel?: string;
    dangerous?: boolean;
    loading?: boolean;
  }>(),
  {
    title: "Xác nhận",
    confirmLabel: "Xác nhận",
    cancelLabel: "Hủy",
    dangerous: false,
    loading: false,
  }
);

defineEmits<{
  "update:modelValue": [value: boolean];
  confirm: [];
}>();
</script>

<style scoped>
.z-confirm__message {
  margin: 0;
  font-size: 0.9375rem;
  color: #444;
  line-height: 1.5;
}
</style>
