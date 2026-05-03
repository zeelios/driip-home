<!--
  ZModal — teleported modal dialog.
  Usage:
    <ZModal v-model="open" title="Edit" size="sm|md|lg">
      content
      <template #footer><PBtn>Save</PBtn></template>
    </ZModal>
-->
<template>
  <Teleport to="body">
    <Transition name="zm-bg">
      <div v-if="modelValue" class="zm-overlay" @click.self="close" aria-modal="true" role="dialog">
        <Transition name="zm-panel">
          <div v-if="modelValue" class="zm-panel" :class="`zm-panel--${size}`">
            <!-- Header -->
            <div class="zm-header">
              <h2 class="zm-title">{{ title }}</h2>
              <button class="zm-close" @click="close" aria-label="Close">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <!-- Body -->
            <div class="zm-body">
              <slot />
            </div>

            <!-- Footer -->
            <div v-if="$slots.footer" class="zm-footer">
              <slot name="footer" />
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
const props = withDefaults(defineProps<{
  modelValue: boolean
  title?: string
  size?: 'sm' | 'md' | 'lg'
}>(), { size: 'md' })

const emit = defineEmits<{ 'update:modelValue': [v: boolean] }>()

function close () { emit('update:modelValue', false) }

// Close on Escape
onMounted(() => {
  document.addEventListener('keydown', onKey)
})
onUnmounted(() => {
  document.removeEventListener('keydown', onKey)
})
function onKey (e: KeyboardEvent) {
  if (e.key === 'Escape' && props.modelValue) close()
}
</script>

<style scoped>
.zm-overlay {
  position: fixed; inset: 0; z-index: 200;
  background: rgba(0,0,0,0.65);
  display: flex; align-items: flex-end; justify-content: center;
  padding: 0;
}
@media (min-width: 640px) {
  .zm-overlay { align-items: center; padding: 2rem; }
}

.zm-panel {
  background: var(--bg-raised);
  border: 1px solid var(--border);
  border-radius: 1rem 1rem 0 0;
  width: 100%;
  max-height: 90dvh;
  display: flex; flex-direction: column;
  overflow: hidden;
}
@media (min-width: 640px) {
  .zm-panel { border-radius: 0.875rem; max-height: 85dvh; }
  .zm-panel--sm { max-width: 380px; }
  .zm-panel--md { max-width: 520px; }
  .zm-panel--lg { max-width: 720px; }
}

.zm-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 1rem 1.25rem;
  border-bottom: 1px solid var(--border);
  flex-shrink: 0;
}
.zm-title {
  font-family: "Barlow Condensed", ui-sans-serif, sans-serif;
  font-size: 1.1rem; font-weight: 700;
  text-transform: uppercase; letter-spacing: 0.04em;
  color: var(--text);
}
.zm-close {
  width: 1.75rem; height: 1.75rem;
  display: flex; align-items: center; justify-content: center;
  background: none; border: 1px solid var(--border);
  border-radius: 0.375rem; cursor: pointer; color: var(--text-sub);
  transition: color 0.12s, background 0.12s;
}
.zm-close:hover { color: var(--text); background: var(--bg-hover); }

.zm-body {
  padding: 1.25rem;
  overflow-y: auto;
  flex: 1;
}

.zm-footer {
  padding: 0.875rem 1.25rem;
  border-top: 1px solid var(--border);
  display: flex; justify-content: flex-end; gap: 0.5rem;
  flex-shrink: 0;
}

/* Transitions */
.zm-bg-enter-active, .zm-bg-leave-active { transition: opacity 0.2s ease; }
.zm-bg-enter-from,   .zm-bg-leave-to     { opacity: 0; }

.zm-panel-enter-active { transition: opacity 0.2s ease, transform 0.25s cubic-bezier(0.32,0.72,0,1); }
.zm-panel-leave-active { transition: opacity 0.15s ease, transform 0.2s ease; }
.zm-panel-enter-from   { opacity: 0; transform: translateY(20px) scale(0.98); }
.zm-panel-leave-to     { opacity: 0; transform: translateY(10px) scale(0.99); }
@media (max-width: 639px) {
  .zm-panel-enter-from { transform: translateY(100%); }
  .zm-panel-leave-to   { transform: translateY(100%); }
}
</style>
