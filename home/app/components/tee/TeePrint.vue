<template>
  <section class="prt-section" id="print">
    <div class="prt-inner">
      <!-- Header -->
      <div class="prt-header reveal">
        <div class="prt-header-left">
          <div class="prt-eyebrow">
            <span class="prt-eyebrow-line" />
            <span>{{ t("tee.print.sectionLabel") }}</span>
          </div>
          <h2 class="prt-title">{{ t("tee.print.title") }}</h2>
        </div>
        <div class="prt-header-right">
          <p class="prt-desc">{{ t("tee.print.description") }}</p>
        </div>
      </div>

      <!-- 3-step process -->
      <div class="prt-steps reveal">
        <div class="prt-step">
          <span class="prt-step-num">01</span>
          <h3 class="prt-step-title">{{ t("tee.print.steps.pre.title") }}</h3>
          <p class="prt-step-desc">{{ t("tee.print.steps.pre.desc") }}</p>
        </div>
        <div class="prt-step">
          <span class="prt-step-num">02</span>
          <h3 class="prt-step-title">{{ t("tee.print.steps.ink.title") }}</h3>
          <p class="prt-step-desc">{{ t("tee.print.steps.ink.desc") }}</p>
        </div>
        <div class="prt-step">
          <span class="prt-step-num">03</span>
          <h3 class="prt-step-title">{{ t("tee.print.steps.heat.title") }}</h3>
          <p class="prt-step-desc">{{ t("tee.print.steps.heat.desc") }}</p>
        </div>
      </div>

      <!-- Comparison -->
      <div class="prt-compare reveal">
        <!-- Traditional -->
        <div class="prt-panel prt-panel--legacy">
          <div class="prt-panel-head">
            <div>
              <span class="prt-panel-label">{{
                t("tee.print.traditional.label")
              }}</span>
              <span class="prt-panel-sublabel">{{
                t("tee.print.traditional.sublabel")
              }}</span>
            </div>
            <span class="prt-panel-tag prt-panel-tag--legacy">Legacy</span>
          </div>
          <div class="prt-layers prt-layers--legacy">
            <div class="prt-layer prt-layer--ink" />
            <div class="prt-layer prt-layer--fabric">
              <span class="prt-layer-label">Fabric surface</span>
            </div>
          </div>
          <p class="prt-panel-text prt-panel-text--muted">
            {{ t("tee.print.traditional.desc") }}
          </p>
        </div>

        <!-- DTG -->
        <div class="prt-panel prt-panel--dtg">
          <div class="prt-panel-head">
            <div>
              <span class="prt-panel-label prt-panel-label--bright">{{
                t("tee.print.dtg.label")
              }}</span>
              <span class="prt-panel-sublabel prt-panel-sublabel--bright"
                >Molecular bond</span
              >
            </div>
            <span class="prt-panel-tag prt-panel-tag--driip">DRIIP</span>
          </div>
          <div class="prt-layers prt-layers--dtg">
            <div class="prt-layer prt-layer--fused">
              <span class="prt-layer-label prt-layer-label--bright"
                >Ink fused into fiber</span
              >
              <div class="prt-shimmer" />
            </div>
          </div>
          <p class="prt-panel-text">{{ t("tee.print.dtg.desc") }}</p>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
const { t } = useI18n();
</script>

<style scoped>
.prt-section {
  background: var(--black);
  border-top: 1px solid rgba(255, 255, 255, 0.08);
}

.prt-inner {
  width: min(1400px, 100%);
  margin: 0 auto;
  padding: 64px 20px;
}

/* Header */
.prt-header {
  display: flex;
  flex-direction: column;
  gap: 24px;
  margin-bottom: 48px;
}

.prt-header-left {
  flex: 1;
}

.prt-eyebrow {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.3em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.4);
  margin-bottom: 16px;
}

.prt-eyebrow-line {
  display: block;
  width: 28px;
  height: 1px;
  background: rgba(255, 255, 255, 0.3);
  flex-shrink: 0;
}

.prt-title {
  font-family: var(--font-display);
  font-size: clamp(32px, 8vw, 64px);
  font-weight: 700;
  letter-spacing: -0.02em;
  line-height: 0.9;
}

.prt-desc {
  font-size: 14px;
  line-height: 1.8;
  color: rgba(255, 255, 255, 0.5);
}

/* Steps */
.prt-steps {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1px;
  background: rgba(255, 255, 255, 0.06);
  margin-bottom: 4px;
}

.prt-step {
  background: var(--black);
  padding: 36px 24px;
  border-top: 1px solid rgba(255, 255, 255, 0.06);
}

.prt-step:first-child {
  border-top: none;
}

.prt-step-num {
  display: block;
  font-family: var(--font-display);
  font-size: clamp(64px, 12vw, 100px);
  font-weight: 700;
  color: rgba(255, 255, 255, 0.04);
  line-height: 1;
  margin-bottom: 16px;
}

.prt-step-title {
  font-family: var(--font-display);
  font-size: 20px;
  font-weight: 700;
  margin-bottom: 10px;
}

.prt-step-desc {
  font-size: 13px;
  line-height: 1.7;
  color: rgba(255, 255, 255, 0.5);
}

/* Compare */
.prt-compare {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1px;
  background: rgba(255, 255, 255, 0.06);
}

.prt-panel {
  background: var(--black);
  padding: 32px 24px;
}

.prt-panel--dtg {
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.prt-panel-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  margin-bottom: 28px;
}

.prt-panel-label {
  display: block;
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.25em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.3);
  margin-bottom: 6px;
}

.prt-panel-label--bright {
  color: rgba(255, 255, 255, 0.6);
}

.prt-panel-sublabel {
  display: block;
  font-size: 10px;
  letter-spacing: 0.15em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.2);
}

.prt-panel-sublabel--bright {
  color: rgba(255, 255, 255, 0.4);
}

.prt-panel-tag {
  font-size: 9px;
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  padding: 5px 10px;
}

.prt-panel-tag--legacy {
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: rgba(255, 255, 255, 0.2);
}

.prt-panel-tag--driip {
  background: var(--white);
  color: var(--black);
}

.prt-layers {
  margin-bottom: 24px;
}

.prt-layer {
  display: flex;
  align-items: center;
  padding: 0 16px;
}

.prt-layer--ink {
  height: 20px;
  background: rgba(255, 255, 255, 0.18);
  margin-bottom: 4px;
}

.prt-layer--fabric {
  height: 40px;
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(255, 255, 255, 0.06);
}

.prt-layer--fused {
  height: 44px;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.1);
  position: relative;
  overflow: hidden;
}

.prt-layer-label {
  font-size: 9px;
  font-weight: 600;
  letter-spacing: 0.15em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.2);
}

.prt-layer-label--bright {
  color: rgba(255, 255, 255, 0.4);
}

.prt-shimmer {
  position: absolute;
  inset: 0;
  background: linear-gradient(
    to right,
    transparent,
    rgba(255, 255, 255, 0.08),
    transparent
  );
  animation: prt-shimmer 2s ease-in-out infinite;
}

.prt-panel-text {
  font-size: 13px;
  line-height: 1.7;
  color: rgba(255, 255, 255, 0.55);
}

.prt-panel-text--muted {
  color: rgba(255, 255, 255, 0.3);
}

@media (min-width: 640px) {
  .prt-inner {
    padding: 80px 40px;
  }
  .prt-steps {
    grid-template-columns: repeat(3, 1fr);
  }
  .prt-step {
    border-top: none;
  }
  .prt-step + .prt-step {
    border-left: 1px solid rgba(255, 255, 255, 0.06);
  }
  .prt-compare {
    grid-template-columns: 1fr 1fr;
  }
}

@media (min-width: 1024px) {
  .prt-inner {
    padding: 100px 48px;
  }
  .prt-header {
    flex-direction: row;
    align-items: flex-end;
    margin-bottom: 64px;
  }
  .prt-header-right {
    max-width: 400px;
  }
  .prt-panel {
    padding: 48px 40px;
  }
}

@keyframes prt-shimmer {
  0% {
    transform: translateX(-100%);
  }
  50% {
    transform: translateX(100%);
  }
  100% {
    transform: translateX(100%);
  }
}
</style>
