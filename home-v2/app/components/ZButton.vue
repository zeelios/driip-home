<template>
  <component
    :is="to ? NuxtLink : tag"
    :to="to"
    :type="!to ? (type ?? 'button') : undefined"
    :disabled="disabled || loading"
    :aria-disabled="disabled || loading"
    class="z-btn"
    :class="[`z-btn--${variant}`, `z-btn--${size}`, { 'z-btn--loading': loading, 'z-btn--block': block }]"
    v-bind="$attrs">
    <!-- Leading icon slot -->
    <span v-if="$slots.leading" class="z-btn__icon z-btn__icon--leading" aria-hidden="true">
      <slot name="leading" />
    </span>

    <!-- Spinner (replaces content while loading) -->
    <span v-if="loading" class="z-btn__spinner" aria-hidden="true">
      <svg class="z-btn__spin-svg" fill="none" viewBox="0 0 24 24">
        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
          stroke-dasharray="32" stroke-dashoffset="12" />
      </svg>
    </span>
    <span :class="{ 'opacity-0': loading }">
      <slot />
    </span>

    <!-- Trailing icon slot -->
    <span v-if="$slots.trailing" class="z-btn__icon z-btn__icon--trailing" aria-hidden="true">
      <slot name="trailing" />
    </span>
  </component>
</template>

<script setup lang="ts">
import { NuxtLink } from '#components'

withDefaults(defineProps<{
  variant?: 'primary' | 'ghost' | 'muted'
  size?: 'sm' | 'md' | 'lg'
  type?: 'button' | 'submit' | 'reset'
  tag?: string
  to?: string
  loading?: boolean
  disabled?: boolean
  block?: boolean
}>(), {
  variant: 'primary',
  size: 'md',
  tag: 'button',
})
</script>

<style scoped>
/* ── Base ──────────────────────────────────────────────────────────── */
.z-btn {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  font-family: "Be Vietnam Pro", sans-serif;
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  white-space: nowrap;
  border: 1px solid transparent;
  border-radius: 0.5rem;
  cursor: pointer;
  transition: background-color 0.18s ease, color 0.18s ease,
              border-color 0.18s ease, opacity 0.18s ease,
              transform 0.12s ease;
  -webkit-tap-highlight-color: transparent;
  touch-action: manipulation;
  text-decoration: none;
}
.z-btn:active:not(:disabled) { transform: scale(0.97); }
.z-btn--block { width: 100%; }

/* ── Sizes ─────────────────────────────────────────────────────────── */
.z-btn--sm { font-size: 0.7rem;  padding: 0.5rem  1rem;    min-height: 32px; }
.z-btn--md { font-size: 0.75rem; padding: 0.7rem  1.25rem; min-height: 40px; }
.z-btn--lg { font-size: 0.8rem;  padding: 0.85rem 1.75rem; min-height: 48px; }

/* ── Primary ───────────────────────────────────────────────────────── */
.z-btn--primary {
  background-color: var(--accent);
  color: var(--accent-fg);
  border-color: var(--accent);
}
.z-btn--primary:hover:not(:disabled) {
  background-color: var(--accent-hover);
  border-color: var(--accent-hover);
}

/* ── Ghost ─────────────────────────────────────────────────────────── */
.z-btn--ghost {
  background-color: transparent;
  color: var(--text);
  border-color: var(--border-hi);
}
.z-btn--ghost:hover:not(:disabled) {
  border-color: var(--border-focus);
  background-color: var(--bg-card);
}

/* ── Muted ─────────────────────────────────────────────────────────── */
.z-btn--muted {
  background-color: var(--bg-card);
  color: var(--text-sub);
  border-color: var(--border);
}
.z-btn--muted:hover:not(:disabled) {
  color: var(--text);
  border-color: var(--border-hi);
}

/* ── Disabled ──────────────────────────────────────────────────────── */
.z-btn:disabled,
.z-btn[aria-disabled="true"] {
  opacity: 0.38;
  cursor: not-allowed;
  transform: none !important;
}

/* ── Loading ───────────────────────────────────────────────────────── */
.z-btn--loading { cursor: wait; }

.z-btn__spinner {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
}
.z-btn__spin-svg {
  width: 1.1em;
  height: 1.1em;
  animation: btn-spin 0.7s linear infinite;
}
@keyframes btn-spin { to { transform: rotate(360deg); } }

/* ── Icons ─────────────────────────────────────────────────────────── */
.z-btn__icon { display: flex; align-items: center; flex-shrink: 0; }
.z-btn__icon--leading { margin-right: -0.1rem; }
.z-btn__icon--trailing { margin-left: -0.1rem; }
</style>
