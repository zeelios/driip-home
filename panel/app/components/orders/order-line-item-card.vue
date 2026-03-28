<template>
  <div
    class="flex items-center justify-between rounded-lg border border-white/6 bg-white/4 p-4 transition-all hover:border-white/10"
  >
    <div class="flex items-center gap-3">
      <img
        :src="item.image || '/placeholder.png'"
        :alt="item.name"
        class="h-12 w-12 rounded-lg border border-white/10 object-cover"
      />
      <div>
        <p class="font-medium text-white">{{ item.name }}</p>
        <p class="text-sm text-white/50">
          <span v-if="item.size">{{ item.size }}</span>
          <span v-else>Mặc định</span>
          <span v-if="item.variant"> · {{ item.variant }}</span>
        </p>
      </div>
    </div>
    <div class="flex items-center gap-4">
      <ZInput
        :model-value="item.quantity"
        type="number"
        min="1"
        class="w-20"
        @update:modelValue="emit('update-quantity', index, Number($event))"
      />
      <span class="min-w-24 text-right font-semibold text-white">
        {{ formatVnd(item.unit_price * item.quantity) }}
      </span>
      <button
        class="rounded-lg p-2 text-white/40 transition-colors hover:bg-white/10 hover:text-white/70"
        type="button"
        @click="emit('remove', index)"
      >
        <svg
          width="16"
          height="16"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
        >
          <line x1="18" y1="6" x2="6" y2="18" />
          <line x1="6" y1="6" x2="18" y2="18" />
        </svg>
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { formatVnd } from "~/utils/format";
import type { SelectedItem } from "~/stores/order-create";

const props = defineProps<{
  item: SelectedItem;
  index: number;
}>();

const emit = defineEmits<{
  remove: [index: number];
  "update-quantity": [index: number, quantity: number];
}>();

const { item, index } = props;
</script>
