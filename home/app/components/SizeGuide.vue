<template>
  <Teleport to="body">
    <Transition name="sg-fade">
      <div v-if="open" class="sg-overlay" @click.self="$emit('update:open', false)">
        <Transition name="sg-slide">
          <div v-if="open" class="sg-card" role="dialog" aria-modal="true">

            <!-- Header -->
            <div class="sg-header">
              <div>
                <p class="sg-label">{{ t('ck.sizechart.label') }}</p>
                <h3 class="sg-title">{{ t('ck.sizechart.note') }}</h3>
              </div>
              <button class="sg-close" type="button" @click="$emit('update:open', false)" aria-label="Close">
                <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
                  <line x1="1" y1="1" x2="17" y2="17" stroke="currentColor" stroke-width="1.5"/>
                  <line x1="17" y1="1" x2="1" y2="17" stroke="currentColor" stroke-width="1.5"/>
                </svg>
              </button>
            </div>

            <!-- Weight finder -->
            <div class="sg-finder">
              <label class="sg-finder-label">{{ t('sg.weightLabel') }}</label>
              <div class="sg-finder-row">
                <div class="sg-input-wrap">
                  <input
                    v-model.number="weight"
                    type="number"
                    min="40"
                    max="150"
                    :placeholder="t('sg.weightPlaceholder')"
                    class="sg-input"
                  />
                  <span class="sg-unit">kg</span>
                </div>
                <Transition name="sg-pill">
                  <div v-if="suggested" class="sg-suggestion">
                    <span class="sg-suggestion-size">{{ suggested }}</span>
                    <span class="sg-suggestion-text">{{ t('sg.recommended') }}</span>
                  </div>
                </Transition>
              </div>
            </div>

            <!-- Size chart -->
            <div class="sg-chart">
              <div class="sg-chart-head">
                <span>SIZE</span>
                <span>{{ t('sg.hip') }}</span>
                <span>{{ t('sg.weight') }}</span>
              </div>
              <button
                v-for="row in sizeChart"
                :key="row.label"
                type="button"
                class="sg-row"
                :class="{
                  'sg-row--suggested': suggested === row.label,
                  'sg-row--selected': selectedSize === row.label,
                }"
                @click="select(row.label)"
              >
                <span class="sg-row-size">{{ row.label }}</span>
                <span class="sg-row-hip">{{ row.hip }}</span>
                <span class="sg-row-weight">{{ row.weightRange }}</span>
                <span class="sg-row-check" aria-hidden="true">
                  <svg v-if="selectedSize === row.label" width="12" height="10" viewBox="0 0 12 10" fill="none">
                    <polyline points="1,5 4.5,8.5 11,1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </span>
              </button>
            </div>

            <!-- Footnote -->
            <p class="sg-footnote">{{ t('ck.sizechart.measurement') }}</p>

          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
const { t } = useI18n()

const props = defineProps<{
  open: boolean
  selectedSize: string
}>()

const emit = defineEmits<{
  'update:open': [value: boolean]
  'select': [size: string]
}>()

const sizeChart = [
  { label: 'S',   hip: '86–92 cm',   weightRange: '58–66 kg',   minWeight: 58,  maxWeight: 66  },
  { label: 'M',   hip: '93–98 cm',   weightRange: '67–74 kg',   minWeight: 67,  maxWeight: 74  },
  { label: 'L',   hip: '99–104 cm',  weightRange: '75–84 kg',   minWeight: 75,  maxWeight: 84  },
  { label: 'XL',  hip: '105–110 cm', weightRange: '85–93 kg',   minWeight: 85,  maxWeight: 93  },
  { label: '2XL', hip: '111–116 cm', weightRange: '94–102 kg',  minWeight: 94,  maxWeight: 102 },
]

const weight = ref<number | null>(null)

const suggested = computed<string | null>(() => {
  if (!weight.value) return null
  return sizeChart.find(r => weight.value! >= r.minWeight && weight.value! <= r.maxWeight)?.label ?? null
})

function select(size: string): void {
  emit('select', size)
  emit('update:open', false)
}

// Reset weight input when modal is reopened
watch(() => props.open, (val) => {
  if (val) weight.value = null
})
</script>

<style scoped>
/* ── Overlay ─────────────────────────────────────────────────────── */
.sg-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.72);
  backdrop-filter: blur(4px);
  z-index: 9000;
  display: flex;
  align-items: flex-end;
  justify-content: center;
  padding: 0 0 env(safe-area-inset-bottom, 0);
}

@media (min-width: 640px) {
  .sg-overlay {
    align-items: center;
    padding: 24px;
  }
}

/* ── Card ─────────────────────────────────────────────────────────── */
.sg-card {
  background: #0a0a0a;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-bottom: none;
  border-radius: 16px 16px 0 0;
  width: 100%;
  max-width: 480px;
  padding: 28px 24px 36px;
  display: flex;
  flex-direction: column;
  gap: 24px;
}

@media (min-width: 640px) {
  .sg-card {
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 12px;
    padding: 32px;
  }
}

/* ── Header ──────────────────────────────────────────────────────── */
.sg-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.sg-label {
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.2em;
  color: rgba(255, 255, 255, 0.4);
  margin-bottom: 6px;
}

.sg-title {
  font-family: var(--font-display);
  font-size: 22px;
  font-weight: 700;
  letter-spacing: 0.02em;
  color: #fff;
  line-height: 1.1;
  text-transform: uppercase;
}

.sg-close {
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 50%;
  background: transparent;
  color: rgba(255, 255, 255, 0.5);
  cursor: pointer;
  flex-shrink: 0;
  transition: color 0.15s, border-color 0.15s;
}

.sg-close:hover {
  color: #fff;
  border-color: rgba(255, 255, 255, 0.3);
}

/* ── Finder ──────────────────────────────────────────────────────── */
.sg-finder {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.sg-finder-label {
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.18em;
  color: rgba(255, 255, 255, 0.4);
}

.sg-finder-row {
  display: flex;
  align-items: center;
  gap: 12px;
}

.sg-input-wrap {
  position: relative;
  flex: 0 0 120px;
}

.sg-input {
  width: 100%;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 8px;
  color: #fff;
  font-family: var(--font-body);
  font-size: 15px;
  font-weight: 500;
  padding: 10px 36px 10px 14px;
  outline: none;
  transition: border-color 0.15s;
  appearance: textfield;
  -moz-appearance: textfield;
}

.sg-input::-webkit-inner-spin-button,
.sg-input::-webkit-outer-spin-button {
  -webkit-appearance: none;
}

.sg-input:focus {
  border-color: rgba(255, 255, 255, 0.35);
}

.sg-input::placeholder {
  color: rgba(255, 255, 255, 0.25);
}

.sg-unit {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  font-size: 11px;
  font-weight: 500;
  color: rgba(255, 255, 255, 0.35);
  pointer-events: none;
}

.sg-suggestion {
  display: flex;
  align-items: center;
  gap: 8px;
}

.sg-suggestion-size {
  font-family: var(--font-display);
  font-size: 28px;
  font-weight: 700;
  color: #fff;
  line-height: 1;
  letter-spacing: 0.02em;
}

.sg-suggestion-text {
  font-size: 10px;
  font-weight: 500;
  letter-spacing: 0.12em;
  color: rgba(255, 255, 255, 0.4);
  text-transform: uppercase;
  line-height: 1.3;
}

/* ── Chart ───────────────────────────────────────────────────────── */
.sg-chart {
  display: flex;
  flex-direction: column;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 8px;
  overflow: hidden;
}

.sg-chart-head {
  display: grid;
  grid-template-columns: 52px 1fr 1fr;
  padding: 8px 16px;
  background: rgba(255, 255, 255, 0.04);
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.sg-chart-head span {
  font-size: 9px;
  font-weight: 600;
  letter-spacing: 0.2em;
  color: rgba(255, 255, 255, 0.3);
  text-transform: uppercase;
}

.sg-row {
  display: grid;
  grid-template-columns: 52px 1fr 1fr 20px;
  align-items: center;
  padding: 13px 16px;
  background: transparent;
  border: none;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
  cursor: pointer;
  text-align: left;
  transition: background 0.15s;
  width: 100%;
}

.sg-row:last-child {
  border-bottom: none;
}

.sg-row:hover {
  background: rgba(255, 255, 255, 0.04);
}

.sg-row--suggested {
  background: rgba(255, 255, 255, 0.06);
}

.sg-row--suggested:hover {
  background: rgba(255, 255, 255, 0.09);
}

.sg-row--selected {
  background: rgba(255, 255, 255, 0.08);
}

.sg-row-size {
  font-family: var(--font-display);
  font-size: 20px;
  font-weight: 700;
  letter-spacing: 0.03em;
  color: #fff;
  line-height: 1;
}

.sg-row--suggested .sg-row-size,
.sg-row--selected .sg-row-size {
  color: #fff;
}

.sg-row-hip,
.sg-row-weight {
  font-size: 12px;
  font-weight: 400;
  color: rgba(255, 255, 255, 0.5);
  letter-spacing: 0.02em;
}

.sg-row--suggested .sg-row-hip,
.sg-row--suggested .sg-row-weight,
.sg-row--selected .sg-row-hip,
.sg-row--selected .sg-row-weight {
  color: rgba(255, 255, 255, 0.75);
}

.sg-row-check {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  color: rgba(255, 255, 255, 0.6);
}

/* ── Footnote ────────────────────────────────────────────────────── */
.sg-footnote {
  font-size: 11px;
  color: rgba(255, 255, 255, 0.3);
  line-height: 1.6;
  letter-spacing: 0.01em;
}

/* ── Transitions ─────────────────────────────────────────────────── */
.sg-fade-enter-active,
.sg-fade-leave-active {
  transition: opacity 0.25s ease;
}
.sg-fade-enter-from,
.sg-fade-leave-to {
  opacity: 0;
}

.sg-slide-enter-active,
.sg-slide-leave-active {
  transition: transform 0.3s cubic-bezier(0.32, 0.72, 0, 1), opacity 0.25s ease;
}
.sg-slide-enter-from,
.sg-slide-leave-to {
  transform: translateY(24px);
  opacity: 0;
}

@media (min-width: 640px) {
  .sg-slide-enter-from,
  .sg-slide-leave-to {
    transform: translateY(12px);
  }
}

.sg-pill-enter-active,
.sg-pill-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.sg-pill-enter-from,
.sg-pill-leave-to {
  opacity: 0;
  transform: translateX(-6px);
}
</style>
