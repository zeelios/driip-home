<template>
  <Teleport to="body">
    <Transition name="z-modal">
      <div v-if="modelValue" class="z-modal-backdrop" @click.self="onBackdropClick">
        <div
          class="z-modal"
          :class="sizeClass"
          role="dialog"
          aria-modal="true"
          :aria-labelledby="titleId"
        >
          <div class="z-modal__header">
            <h2 :id="titleId" class="z-modal__title">{{ title }}</h2>
            <button
              v-if="closable"
              class="z-modal__close"
              aria-label="Đóng"
              @click="$emit('update:modelValue', false)"
            >
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="M18 6 6 18M6 6l12 12"/>
              </svg>
            </button>
          </div>
          <div class="z-modal__body">
            <slot />
          </div>
          <div v-if="$slots.footer" class="z-modal__footer">
            <slot name="footer" />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { computed } from "vue";

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

const titleId = computed(() => `z-modal-title-${Math.random().toString(36).slice(2, 7)}`);
const sizeClass = computed(() => `z-modal--${props.size}`);

function onBackdropClick(): void {
  if (props.closeOnBackdrop) emit("update:modelValue", false);
}
</script>

<style scoped>
.z-modal-backdrop {
  position: fixed;
  inset: 0;
  z-index: 50;
  background: rgba(0, 0, 0, 0.45);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: flex-end;
  justify-content: center;
  padding: 0;
}

@media (min-width: 640px) {
  .z-modal-backdrop {
    align-items: center;
    padding: 1.5rem;
  }
}

.z-modal {
  width: 100%;
  max-height: 92dvh;
  background: #fff;
  border-radius: 16px 16px 0 0;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  box-shadow: 0 32px 80px rgba(0, 0, 0, 0.18);
}

@media (min-width: 640px) {
  .z-modal {
    border-radius: 14px;
  }
}

.z-modal--sm { max-width: 24rem; }
.z-modal--md { max-width: 32rem; }
.z-modal--lg { max-width: 44rem; }
.z-modal--xl { max-width: 58rem; }

.z-modal__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.125rem 1.375rem;
  border-bottom: 1px solid rgba(0, 0, 0, 0.07);
  flex-shrink: 0;
}

.z-modal__title {
  margin: 0;
  font-size: 1rem;
  font-weight: 650;
  color: #1a1a18;
  line-height: 1.3;
}

.z-modal__close {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 2rem;
  height: 2rem;
  border: 0;
  background: transparent;
  color: #9d9d9a;
  cursor: pointer;
  border-radius: 7px;
  transition: background 130ms, color 130ms;
  flex-shrink: 0;
}
.z-modal__close:hover { background: #f5f5f4; color: #1a1a18; }

.z-modal__body {
  padding: 1.375rem;
  overflow-y: auto;
  flex: 1;
}

.z-modal__footer {
  padding: 1rem 1.375rem;
  border-top: 1px solid rgba(0, 0, 0, 0.07);
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
  flex-shrink: 0;
}

/* Transition */
.z-modal-enter-active { transition: opacity 180ms ease, transform 180ms ease; }
.z-modal-leave-active { transition: opacity 160ms ease, transform 160ms ease; }
.z-modal-enter-from,
.z-modal-leave-to {
  opacity: 0;
}
.z-modal-enter-from .z-modal {
  transform: translateY(20px);
}
@media (min-width: 640px) {
  .z-modal-enter-from .z-modal { transform: translateY(12px) scale(0.98); }
}
</style>
