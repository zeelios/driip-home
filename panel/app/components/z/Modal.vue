<template>
  <Teleport to="body">
    <Transition name="z-modal">
      <div
        v-if="modelValue"
        class="fixed inset-0 z-50 bg-black/75 backdrop-blur-sm flex items-end justify-center p-0 sm:items-center sm:p-6"
        @click.self="onBackdropClick"
      >
        <div
          class="w-full max-h-[92dvh] bg-[#141414] rounded-t-2xl sm:rounded-xl flex flex-col overflow-hidden shadow-[0_32px_80px_rgba(0,0,0,0.5)] border border-white/8"
          :class="sizeClass"
          role="dialog"
          aria-modal="true"
          :aria-labelledby="titleId"
        >
          <div
            class="flex items-center justify-between py-4.5 px-5.5 border-b border-white/8 shrink-0"
          >
            <h2
              :id="titleId"
              class="m-0 text-base font-semibold text-white/95 leading-tight"
            >
              {{ title }}
            </h2>
            <button
              v-if="closable"
              class="flex items-center justify-center w-8 h-8 border-0 bg-transparent text-white/50 cursor-pointer rounded-[7px] transition-all duration-130 hover:bg-white/8 hover:text-white/90 shrink-0"
              aria-label="Đóng"
              @click="$emit('update:modelValue', false)"
            >
              <svg
                width="16"
                height="16"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2.5"
              >
                <path d="M18 6 6 18M6 6l12 12" />
              </svg>
            </button>
          </div>
          <div class="p-5.5 overflow-y-auto flex-1">
            <slot />
          </div>
          <div
            v-if="$slots.footer"
            class="py-4 px-5.5 border-t border-white/8 flex justify-end gap-2 shrink-0"
          >
            <slot name="footer" />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { computed, watch } from "vue";

type ModalSize = "sm" | "md" | "lg" | "xl";

const props = withDefaults(
  defineProps<{
    modelValue: boolean;
    title: string;
    size?: ModalSize;
    closable?: boolean;
    closeOnBackdrop?: boolean;
  }>(),
  { size: "md", closable: true, closeOnBackdrop: true }
);

const emit = defineEmits<{ "update:modelValue": [value: boolean] }>();

const titleId = computed(
  () => `z-modal-title-${Math.random().toString(36).slice(2, 7)}`
);
const sizeClass = computed(() => {
  const sizes: Record<ModalSize, string> = {
    sm: "max-w-sm",
    md: "max-w-md",
    lg: "max-w-2xl",
    xl: "max-w-4xl",
  };
  return sizes[props.size];
});

function onBackdropClick(): void {
  if (props.closeOnBackdrop) emit("update:modelValue", false);
}

// Lock body scroll when modal is open
watch(
  () => props.modelValue,
  (isOpen) => {
    if (typeof document !== "undefined") {
      if (isOpen) {
        document.body.style.overflow = "hidden";
      } else {
        document.body.style.overflow = "";
      }
    }
  },
  { immediate: true }
);
</script>

<style scoped>
/* Transition - must stay in CSS for Vue transitions */
.z-modal-enter-active {
  transition: opacity 180ms ease, transform 180ms ease;
}
.z-modal-leave-active {
  transition: opacity 160ms ease, transform 160ms ease;
}
.z-modal-enter-from,
.z-modal-leave-to {
  opacity: 0;
}
.z-modal-enter-from > div {
  transform: translateY(20px);
}
@media (min-width: 640px) {
  .z-modal-enter-from > div {
    transform: translateY(12px) scale(0.98);
  }
}
</style>
