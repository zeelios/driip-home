<template>
  <component
    :is="to ? resolveComponent('NuxtLink') : 'button'"
    :to="to"
    :type="!to ? (type ?? 'button') : undefined"
    :disabled="disabled || loading"
    class="pbtn"
    :class="[`pbtn--${variant}`, `pbtn--${size}`, { 'pbtn--loading': loading, 'pbtn--block': block }]"
    v-bind="$attrs">
    <svg v-if="loading" class="pbtn__spin" fill="none" viewBox="0 0 24 24">
      <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2.5"
        stroke-dasharray="32" stroke-dashoffset="12" stroke-linecap="round" />
    </svg>
    <span :class="{ 'opacity-0': loading }"><slot /></span>
  </component>
</template>

<script setup lang="ts">
withDefaults(defineProps<{
  variant?: 'primary' | 'ghost' | 'danger' | 'muted'
  size?: 'xs' | 'sm' | 'md'
  type?: 'button' | 'submit' | 'reset'
  to?: string
  loading?: boolean
  disabled?: boolean
  block?: boolean
}>(), { variant: 'primary', size: 'sm' })
</script>

<style scoped>
.pbtn {
  position: relative; display: inline-flex; align-items: center; justify-content: center;
  gap: 0.375rem; font-size: 0.75rem; font-weight: 600;
  letter-spacing: 0.04em; border-radius: 0.375rem;
  border: 1px solid transparent; cursor: pointer;
  text-decoration: none;
  transition: background 0.15s, border-color 0.15s, opacity 0.15s, transform 0.1s;
  -webkit-tap-highlight-color: transparent; white-space: nowrap;
}
.pbtn:active:not(:disabled) { transform: scale(0.97); }
.pbtn--block { width: 100%; }
.pbtn--xs  { padding: 0.3rem 0.6rem;  min-height: 26px; font-size: 0.7rem; }
.pbtn--sm  { padding: 0.45rem 0.875rem; min-height: 32px; }
.pbtn--md  { padding: 0.6rem 1.25rem;  min-height: 40px; }

.pbtn--primary { background: var(--accent); color: var(--accent-fg); border-color: var(--accent); }
.pbtn--primary:hover:not(:disabled) { opacity: 0.88; }

.pbtn--ghost   { background: transparent; color: var(--text-sub); border-color: var(--border-hi); }
.pbtn--ghost:hover:not(:disabled) { color: var(--text); border-color: var(--border-focus); background: var(--bg-hover); }

.pbtn--danger  { background: var(--status-danger); color: var(--status-danger-t); border-color: rgba(239,68,68,0.3); }
.pbtn--danger:hover:not(:disabled) { opacity: 0.85; }

.pbtn--muted   { background: var(--bg-hover); color: var(--text-sub); border-color: var(--border); }
.pbtn--muted:hover:not(:disabled) { color: var(--text); border-color: var(--border-hi); }

.pbtn:disabled, .pbtn--loading { opacity: 0.38; cursor: not-allowed; transform: none !important; }
.pbtn--loading { cursor: wait; }

.pbtn__spin {
  position: absolute; width: 1em; height: 1em;
  animation: pbtn-spin 0.7s linear infinite;
}
@keyframes pbtn-spin { to { transform: rotate(360deg); } }
</style>
