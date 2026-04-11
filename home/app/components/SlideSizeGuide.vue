<template>
  <Teleport to="body">
    <Transition name="sg-fade">
      <div v-if="open" class="sg-overlay" @click.self="$emit('update:open', false)">
        <Transition name="sg-slide">
          <div v-if="open" class="sg-card" role="dialog" aria-modal="true">
            <!-- Header -->
            <div class="sg-header">
              <div>
                <p class="sg-label">{{ t('slide.sizechart.label') }}</p>
                <h3 class="sg-title">{{ t('slide.sizechart.title') }}</h3>
              </div>
              <button class="sg-close" type="button" @click="$emit('update:open', false)" aria-label="Close">
                <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
                  <line x1="1" y1="1" x2="17" y2="17" stroke="currentColor" stroke-width="1.5"/>
                  <line x1="17" y1="1" x2="1" y2="17" stroke="currentColor" stroke-width="1.5"/>
                </svg>
              </button>
            </div>

            <!-- Size Chart Table -->
            <div class="sg-table-wrapper">
              <table class="sg-table">
                <thead>
                  <tr>
                    <th>{{ t('slide.sizechart.labelSize') }}</th>
                    <th>{{ t('slide.sizechart.fitSize') }}</th>
                    <th>{{ t('slide.sizechart.insoleLength') }}</th>
                    <th>{{ t('slide.sizechart.heelWidth') }}</th>
                    <th>{{ t('slide.sizechart.heelHeight') }}</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="row in sizeChart" :key="row.labelSize">
                    <td class="sg-size-cell">{{ row.labelSize }}</td>
                    <td>{{ row.fitSize }}</td>
                    <td>{{ row.insoleLength }}</td>
                    <td>{{ row.heelWidth }}</td>
                    <td>{{ row.heelHeight }}</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Unit Note -->
            <p class="sg-unit-note">{{ t('slide.sizechart.unit') }}</p>

            <!-- Foot Type Guide -->
            <div class="sg-foot-guide">
              <p class="sg-foot-title">{{ t('slide.sizechart.footTypeTitle') }}</p>
              <div class="sg-foot-grid">
                <div class="sg-foot-item">
                  <div class="sg-foot-icon">🦶</div>
                  <p class="sg-foot-label">{{ t('slide.sizechart.standardFoot') }}</p>
                  <p class="sg-foot-desc">{{ t('slide.sizechart.recommendedSize') }}</p>
                </div>
                <div class="sg-foot-item">
                  <div class="sg-foot-icon">👣</div>
                  <p class="sg-foot-label">{{ t('slide.sizechart.wideFoot') }}</p>
                  <p class="sg-foot-desc">{{ t('slide.sizechart.sizeUp') }}</p>
                </div>
                <div class="sg-foot-item">
                  <div class="sg-foot-icon">🦶</div>
                  <p class="sg-foot-label">{{ t('slide.sizechart.narrowFoot') }}</p>
                  <p class="sg-foot-desc">{{ t('slide.sizechart.recommendedSize') }}</p>
                </div>
                <div class="sg-foot-item">
                  <div class="sg-foot-icon">👟</div>
                  <p class="sg-foot-label">{{ t('slide.sizechart.highInstep') }}</p>
                  <p class="sg-foot-desc">{{ t('slide.sizechart.sizeUp') }}</p>
                </div>
              </div>
            </div>

            <!-- Footnote -->
            <p class="sg-footnote">{{ t('slide.sizechart.note') }}</p>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
const { t } = useI18n();

defineProps<{
  open: boolean;
}>();

const emit = defineEmits<{
  'update:open': [value: boolean];
}>();

const sizeChart = [
  { labelSize: '36-37', fitSize: '35-36', insoleLength: '25.0', heelWidth: '7.0', heelHeight: '4.5' },
  { labelSize: '38-39', fitSize: '37-38', insoleLength: '26.0', heelWidth: '7.5', heelHeight: '4.5' },
  { labelSize: '40-41', fitSize: '39-40', insoleLength: '27.0', heelWidth: '8.0', heelHeight: '4.5' },
  { labelSize: '42-43', fitSize: '41-42', insoleLength: '28.0', heelWidth: '8.5', heelHeight: '4.5' },
  { labelSize: '44-45', fitSize: '43-44', insoleLength: '29.0', heelWidth: '8.5', heelHeight: '4.5' },
  { labelSize: '46-47', fitSize: '45-46', insoleLength: '30.0', heelWidth: '8.5', heelHeight: '4.5' },
];
</script>

<style scoped>
/* ── Overlay ─────────────────────────────────────────────────────── */
.sg-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.85);
  backdrop-filter: blur(8px);
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
  max-width: 720px;
  max-height: 90vh;
  overflow-y: auto;
  padding: 24px;
}

@media (min-width: 640px) {
  .sg-card {
    border-radius: 16px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    max-height: 85vh;
    padding: 32px;
  }
}

/* ── Header ────────────────────────────────────────────────────────── */
.sg-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 24px;
}

.sg-label {
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.25em;
  color: rgba(255, 255, 255, 0.4);
  text-transform: uppercase;
  margin-bottom: 8px;
}

.sg-title {
  font-size: 20px;
  font-weight: 600;
  color: var(--white);
  margin: 0;
}

.sg-close {
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 50%;
  color: rgba(255, 255, 255, 0.6);
  cursor: pointer;
  transition: all 0.2s ease;
  flex-shrink: 0;
}

.sg-close:hover {
  background: rgba(255, 255, 255, 0.1);
  color: var(--white);
}

/* ── Table ─────────────────────────────────────────────────────────── */
.sg-table-wrapper {
  overflow-x: auto;
  margin-bottom: 16px;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 8px;
}

.sg-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
}

.sg-table th {
  background: rgba(255, 255, 255, 0.04);
  padding: 12px 8px;
  text-align: center;
  font-weight: 600;
  font-size: 10px;
  letter-spacing: 0.05em;
  color: var(--grey-400);
  text-transform: uppercase;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  white-space: nowrap;
}

.sg-table td {
  padding: 12px 8px;
  text-align: center;
  color: var(--grey-300);
  border-bottom: 1px solid rgba(255, 255, 255, 0.04);
}

.sg-table tr:last-child td {
  border-bottom: none;
}

.sg-size-cell {
  font-weight: 600;
  color: var(--white) !important;
}

/* ── Unit Note ────────────────────────────────────────────────────── */
.sg-unit-note {
  font-size: 12px;
  color: var(--grey-500);
  text-align: right;
  margin-bottom: 24px;
}

/* ── Foot Type Guide ──────────────────────────────────────────────── */
.sg-foot-guide {
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 20px;
}

.sg-foot-title {
  font-size: 12px;
  font-weight: 600;
  letter-spacing: 0.1em;
  color: var(--grey-400);
  text-transform: uppercase;
  margin-bottom: 16px;
  text-align: center;
}

.sg-foot-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
}

@media (min-width: 480px) {
  .sg-foot-grid {
    grid-template-columns: repeat(4, 1fr);
  }
}

.sg-foot-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: 6px;
}

.sg-foot-icon {
  font-size: 28px;
  line-height: 1;
  margin-bottom: 4px;
}

.sg-foot-label {
  font-size: 11px;
  font-weight: 600;
  color: var(--grey-300);
  margin: 0;
}

.sg-foot-desc {
  font-size: 10px;
  color: var(--grey-500);
  margin: 0;
}

/* ── Footnote ─────────────────────────────────────────────────────── */
.sg-footnote {
  font-size: 11px;
  color: var(--grey-500);
  text-align: center;
  line-height: 1.6;
  margin: 0;
}

/* ── Transitions ───────────────────────────────────────────────────── */
.sg-fade-enter-active,
.sg-fade-leave-active {
  transition: opacity 0.3s ease;
}

.sg-fade-enter-from,
.sg-fade-leave-to {
  opacity: 0;
}

.sg-slide-enter-active,
.sg-slide-leave-active {
  transition: transform 0.3s ease, opacity 0.3s ease;
}

.sg-slide-enter-from,
.sg-slide-leave-to {
  transform: translateY(20px);
  opacity: 0;
}

@media (min-width: 640px) {
  .sg-slide-enter-from,
  .sg-slide-leave-to {
    transform: translateY(-10px);
  }
}
</style>
