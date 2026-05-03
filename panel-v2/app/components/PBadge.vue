<!-- Panel status badge — maps common driip-rust status strings to colours -->
<template>
  <span class="badge" :class="`badge--${variant}`">
    <slot>{{ label }}</slot>
  </span>
</template>

<script setup lang="ts">
type Variant = 'ok' | 'pending' | 'danger' | 'info' | 'muted'

const props = withDefaults(defineProps<{
  status?: string   // auto-map from driip-rust strings
  variant?: Variant
  label?: string
}>(), { variant: 'muted' })

const STATUS_MAP: Record<string, Variant> = {
  // orders
  pending: 'pending', confirmed: 'info', packing: 'info',
  shipped: 'info', delivered: 'ok', cancelled: 'danger',
  // inventory
  available: 'ok', partial: 'pending', low: 'danger',
  // addresses
  active: 'ok', blocked: 'danger', flagged: 'pending',
  // staff/customers
  true: 'ok', false: 'muted',
  // priority
  normal: 'muted', high: 'pending', urgent: 'danger',
}

const variant = computed<Variant>(() =>
  props.variant !== 'muted' ? props.variant : (STATUS_MAP[props.status ?? ''] ?? 'muted')
)
const label = computed(() => props.label ?? props.status ?? '')
</script>

<style scoped>
.badge {
  display: inline-flex; align-items: center;
  padding: 0.2rem 0.55rem;
  font-size: 0.65rem; font-weight: 700;
  letter-spacing: 0.08em; text-transform: uppercase;
  border-radius: 0.25rem;
  white-space: nowrap;
}
.badge--ok      { background: var(--status-ok);      color: var(--status-ok-t); }
.badge--pending { background: var(--status-pending);  color: var(--status-pending-t); }
.badge--danger  { background: var(--status-danger);   color: var(--status-danger-t); }
.badge--info    { background: var(--status-info);     color: var(--status-info-t); }
.badge--muted   { background: var(--status-muted);    color: var(--text-sub); }
</style>
