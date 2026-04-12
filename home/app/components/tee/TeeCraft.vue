<template>
  <section class="cft-section" id="craft">
    <div class="cft-inner">
      <div class="cft-layout">
        <!-- Left: content -->
        <div class="cft-copy reveal">
          <div class="cft-eyebrow">
            <span class="cft-eyebrow-line" />
            <span>{{ t("tee.craft.sectionLabel") }}</span>
          </div>
          <h2 class="cft-title">{{ t("tee.craft.title") }}</h2>
          <p class="cft-desc">{{ t("tee.craft.description") }}</p>

          <!-- Stats -->
          <div class="cft-stats">
            <div class="cft-stat">
              <span class="cft-stat-value">{{
                t("tee.craft.stats.stitches.value")
              }}</span>
              <span class="cft-stat-label">{{
                t("tee.craft.stats.stitches.title")
              }}</span>
            </div>
            <div class="cft-stat">
              <span class="cft-stat-value">{{
                t("tee.craft.stats.collar.value")
              }}</span>
              <span class="cft-stat-label">{{
                t("tee.craft.stats.collar.title")
              }}</span>
            </div>
            <div class="cft-stat">
              <span class="cft-stat-value">{{
                t("tee.craft.stats.threads.value")
              }}</span>
              <span class="cft-stat-label">{{
                t("tee.craft.stats.threads.title")
              }}</span>
            </div>
          </div>

          <!-- Detail list -->
          <div class="cft-details">
            <div class="cft-detail">
              <span class="cft-detail-dot" />
              <div>
                <p class="cft-detail-title">
                  {{ t("tee.craftsmanship.details.sewing.title") }}
                </p>
                <p class="cft-detail-desc">
                  {{ t("tee.craftsmanship.details.sewing.desc") }}
                </p>
              </div>
            </div>
            <div class="cft-detail">
              <span class="cft-detail-dot" />
              <div>
                <p class="cft-detail-title">
                  {{ t("tee.craftsmanship.details.finish.title") }}
                </p>
                <p class="cft-detail-desc">
                  {{ t("tee.craftsmanship.details.finish.desc") }}
                </p>
              </div>
            </div>
            <div class="cft-detail">
              <span class="cft-detail-dot" />
              <div>
                <p class="cft-detail-title">
                  {{ t("tee.craftsmanship.details.origin.title") }}
                </p>
                <p class="cft-detail-desc">
                  {{ t("tee.craftsmanship.details.origin.desc") }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Right: monogram -->
        <div class="cft-visual reveal">
          <div class="cft-monogram-wrap">
            <div class="cft-corner cft-corner--tl" />
            <div class="cft-corner cft-corner--tr" />
            <div class="cft-corner cft-corner--bl" />
            <div class="cft-corner cft-corner--br" />
            <div class="cft-ring" />
            <div class="cft-monogram-content">
              <div class="cft-monogram-circle">
                <span class="cft-monogram-text">IT</span>
              </div>
              <p class="cft-monogram-label">{{ t("tee.craft.badge") }}</p>
              <p class="cft-monogram-sub">{{ t("tee.craft.tailoring") }}</p>
            </div>
            <span class="cft-watermark">04</span>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
const { t } = useI18n();
</script>

<style scoped>
.cft-section {
  background: var(--black);
  border-top: 1px solid rgba(255, 255, 255, 0.08);
}

.cft-inner {
  width: min(1400px, 100%);
  margin: 0 auto;
  padding: 64px 20px;
}

.cft-layout {
  display: flex;
  flex-direction: column;
  gap: 48px;
}

/* Copy */
.cft-eyebrow {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.3em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.4);
  margin-bottom: 20px;
}

.cft-eyebrow-line {
  display: block;
  width: 28px;
  height: 1px;
  background: rgba(255, 255, 255, 0.3);
  flex-shrink: 0;
}

.cft-title {
  font-family: var(--font-display);
  font-size: clamp(32px, 8vw, 64px);
  font-weight: 700;
  letter-spacing: -0.02em;
  line-height: 0.9;
  margin-bottom: 20px;
}

.cft-desc {
  font-size: 14px;
  line-height: 1.8;
  color: rgba(255, 255, 255, 0.5);
  max-width: 420px;
  margin-bottom: 40px;
}

/* Stats */
.cft-stats {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1px;
  background: rgba(255, 255, 255, 0.06);
  margin-bottom: 32px;
}

.cft-stat {
  background: var(--black);
  padding: 20px 16px;
  text-align: center;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.cft-stat-value {
  font-family: var(--font-display);
  font-size: clamp(24px, 5vw, 36px);
  font-weight: 700;
  line-height: 1;
}

.cft-stat-label {
  font-size: 9px;
  font-weight: 600;
  letter-spacing: 0.15em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.35);
  line-height: 1.4;
}

/* Details */
.cft-details {
  display: flex;
  flex-direction: column;
  gap: 20px;
  padding-top: 28px;
  border-top: 1px solid rgba(255, 255, 255, 0.06);
}

.cft-detail {
  display: flex;
  align-items: flex-start;
  gap: 16px;
}

.cft-detail-dot {
  display: block;
  width: 4px;
  height: 4px;
  background: rgba(255, 255, 255, 0.3);
  border-radius: 50%;
  margin-top: 6px;
  flex-shrink: 0;
}

.cft-detail-title {
  font-size: 13px;
  font-weight: 500;
  margin-bottom: 4px;
}

.cft-detail-desc {
  font-size: 12px;
  color: rgba(255, 255, 255, 0.4);
  line-height: 1.6;
}

/* Monogram Visual */
.cft-monogram-wrap {
  position: relative;
  aspect-ratio: 1 / 1;
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid rgba(255, 255, 255, 0.06);
  display: flex;
  align-items: center;
  justify-content: center;
}

.cft-corner {
  position: absolute;
  width: 40px;
  height: 40px;
}

.cft-corner::before,
.cft-corner::after {
  content: "";
  position: absolute;
  background: rgba(255, 255, 255, 0.18);
}

.cft-corner::before {
  width: 100%;
  height: 1px;
  top: 0;
  left: 0;
}
.cft-corner::after {
  width: 1px;
  height: 100%;
  top: 0;
  left: 0;
}

.cft-corner--tl {
  top: 16px;
  left: 16px;
}
.cft-corner--tr {
  top: 16px;
  right: 16px;
  transform: scaleX(-1);
}
.cft-corner--bl {
  bottom: 16px;
  left: 16px;
  transform: scaleY(-1);
}
.cft-corner--br {
  bottom: 16px;
  right: 16px;
  transform: scale(-1);
}

.cft-ring {
  position: absolute;
  inset: 40px;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 50%;
}

.cft-monogram-content {
  text-align: center;
  position: relative;
  z-index: 2;
}

.cft-monogram-circle {
  width: 140px;
  height: 140px;
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 16px;
}

.cft-monogram-text {
  font-family: var(--font-display);
  font-size: 56px;
  font-weight: 700;
  letter-spacing: -0.02em;
}

.cft-monogram-label {
  font-size: 9px;
  font-weight: 600;
  letter-spacing: 0.3em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.35);
  margin-bottom: 4px;
}

.cft-monogram-sub {
  font-size: 11px;
  color: rgba(255, 255, 255, 0.2);
}

.cft-watermark {
  position: absolute;
  bottom: 16px;
  right: 20px;
  font-family: var(--font-display);
  font-size: 72px;
  font-weight: 700;
  color: rgba(255, 255, 255, 0.03);
  line-height: 1;
  user-select: none;
}

@media (min-width: 640px) {
  .cft-inner {
    padding: 80px 40px;
  }
  .cft-monogram-circle {
    width: 180px;
    height: 180px;
  }
  .cft-monogram-text {
    font-size: 72px;
  }
}

@media (min-width: 1024px) {
  .cft-inner {
    padding: 100px 48px;
  }
  .cft-layout {
    flex-direction: row;
    gap: 80px;
    align-items: flex-start;
  }
  .cft-copy {
    flex: 1;
  }
  .cft-visual {
    flex: 1;
  }
  .cft-ring {
    inset: 56px;
  }
}
</style>
