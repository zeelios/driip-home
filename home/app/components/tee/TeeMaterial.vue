<template>
  <section class="mat-section" id="material">
    <div class="mat-bg" />
    <div class="mat-img-bg">
      <img
        :src="bgSrc"
        alt=""
        aria-hidden="true"
        class="mat-img-bg-img"
        loading="lazy"
      />
      <div class="mat-img-bg-overlay" />
    </div>
    <div class="mat-inner">
      <!-- Header -->
      <div class="mat-header reveal">
        <div class="mat-header-left">
          <div class="mat-eyebrow">
            <span class="mat-eyebrow-line" />
            <span>{{ t("tee.material.sectionLabel") }}</span>
          </div>
          <h2 class="mat-title">{{ t("tee.material.title") }}</h2>
        </div>
        <div class="mat-header-right">
          <p class="mat-desc">{{ t("tee.material.description") }}</p>
        </div>
      </div>

      <!-- Viz + Stats -->
      <div class="mat-grid reveal">
        <!-- Molecule -->
        <div class="mat-molecule">
          <div class="mat-molecule-rings">
            <div class="mat-ring mat-ring--1" />
            <div class="mat-ring mat-ring--2" />
            <div class="mat-ring mat-ring--3" />
            <div class="mat-ring mat-ring--4" />
            <div class="mat-node mat-node--center" />
            <div class="mat-node mat-node--top" />
            <div class="mat-node mat-node--bottom" />
            <div class="mat-node mat-node--left" />
            <div class="mat-node mat-node--right" />
          </div>
          <div class="mat-molecule-label">
            <span class="mat-molecule-line" />
            <span class="mat-molecule-text">DuPont Sorona® Polymer Chain</span>
          </div>
          <span class="mat-watermark">02</span>
        </div>

        <!-- Stats -->
        <div class="mat-stats">
          <div class="mat-stat-card">
            <span class="mat-stat-value">37%</span>
            <p class="mat-stat-label">
              {{ t("tee.material.stats.plantBased") }}
            </p>
          </div>
          <div class="mat-stat-card">
            <span class="mat-stat-value">280</span>
            <p class="mat-stat-label">{{ t("tee.material.stats.gsm") }}</p>
          </div>
          <div class="mat-stat-card">
            <span class="mat-stat-value">−30%</span>
            <p class="mat-stat-label">{{ t("tee.material.stats.energy") }}</p>
          </div>
          <div class="mat-stat-card">
            <span class="mat-stat-value">∞</span>
            <p class="mat-stat-label">{{ t("tee.material.stats.shape") }}</p>
          </div>
        </div>
      </div>

      <!-- Feature cards -->
      <div class="mat-features reveal">
        <div class="mat-feature">
          <span class="mat-feature-num">01</span>
          <h3 class="mat-feature-title">
            {{ t("tee.material.shapeMemory.title") }}
          </h3>
          <p class="mat-feature-desc">
            {{ t("tee.material.shapeMemory.desc") }}
          </p>
        </div>
        <div class="mat-feature">
          <span class="mat-feature-num">02</span>
          <h3 class="mat-feature-title">
            {{ t("tee.material.bioBased.title") }}
          </h3>
          <p class="mat-feature-desc">{{ t("tee.material.bioBased.desc") }}</p>
        </div>
        <div class="mat-feature">
          <span class="mat-feature-num">03</span>
          <h3 class="mat-feature-title">
            {{ t("tee.material.softer.title") }}
          </h3>
          <p class="mat-feature-desc">{{ t("tee.material.softer.desc") }}</p>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
const { t } = useI18n();
const bgSrc = "/tee/material-bg.jpg";
</script>

<style scoped>
.mat-section {
  position: relative;
  background: var(--black);
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  overflow: hidden;
}

.mat-img-bg {
  position: absolute;
  inset: 0;
  z-index: 0;
  pointer-events: none;
}

.mat-img-bg-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center;
  opacity: 0.12;
  filter: grayscale(100%);
  display: block;
}

.mat-img-bg-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(
    180deg,
    rgba(0, 0, 0, 0.55) 0%,
    rgba(0, 0, 0, 0.35) 50%,
    rgba(0, 0, 0, 0.7) 100%
  );
}

.mat-bg {
  position: absolute;
  top: 0;
  right: 0;
  width: 66%;
  height: 100%;
  background: radial-gradient(
    ellipse at 100% 30%,
    rgba(255, 255, 255, 0.03) 0%,
    transparent 55%
  );
  pointer-events: none;
  z-index: 1;
}

.mat-inner {
  position: relative;
  z-index: 2;
  width: min(1400px, 100%);
  margin: 0 auto;
  padding: 64px 20px;
}

/* Header */
.mat-header {
  display: flex;
  flex-direction: column;
  gap: 24px;
  margin-bottom: 48px;
}

.mat-header-left {
  flex: 1;
}

.mat-eyebrow {
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

.mat-eyebrow-line {
  display: block;
  width: 28px;
  height: 1px;
  background: rgba(255, 255, 255, 0.3);
  flex-shrink: 0;
}

.mat-title {
  font-family: var(--font-display);
  font-size: clamp(32px, 8vw, 64px);
  font-weight: 700;
  letter-spacing: -0.02em;
  line-height: 0.9;
}

.mat-desc {
  font-size: 14px;
  line-height: 1.8;
  color: rgba(255, 255, 255, 0.5);
}

/* Viz + Stats Grid */
.mat-grid {
  display: flex;
  flex-direction: column;
  gap: 4px;
  margin-bottom: 4px;
}

/* Molecule */
.mat-molecule {
  position: relative;
  aspect-ratio: 16 / 9;
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid rgba(255, 255, 255, 0.06);
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.mat-molecule-rings {
  position: relative;
  width: 220px;
  height: 220px;
}

.mat-ring {
  position: absolute;
  inset: 0;
  border-radius: 50%;
  border: 1px solid rgba(255, 255, 255, 0.08);
}

.mat-ring--1 {
  inset: 0;
  animation: mat-spin 50s linear infinite;
}
.mat-ring--2 {
  inset: 20px;
  animation: mat-spin 35s linear infinite reverse;
  border-color: rgba(255, 255, 255, 0.05);
}
.mat-ring--3 {
  inset: 44px;
  animation: mat-spin 22s linear infinite;
  border-color: rgba(255, 255, 255, 0.05);
}
.mat-ring--4 {
  inset: 72px;
  animation: mat-spin 14s linear infinite reverse;
}

.mat-node {
  position: absolute;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.5);
}

.mat-node--center {
  width: 14px;
  height: 14px;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  background: var(--white);
  box-shadow: 0 0 20px rgba(255, 255, 255, 0.4);
}

.mat-node--top {
  width: 10px;
  height: 10px;
  top: -5px;
  left: 50%;
  transform: translateX(-50%);
}
.mat-node--bottom {
  width: 10px;
  height: 10px;
  bottom: -5px;
  left: 50%;
  transform: translateX(-50%);
}
.mat-node--left {
  width: 10px;
  height: 10px;
  left: -5px;
  top: 50%;
  transform: translateY(-50%);
}
.mat-node--right {
  width: 10px;
  height: 10px;
  right: -5px;
  top: 50%;
  transform: translateY(-50%);
}

.mat-molecule-label {
  position: absolute;
  bottom: 16px;
  left: 20px;
  display: flex;
  align-items: center;
  gap: 10px;
}

.mat-molecule-line {
  display: block;
  width: 20px;
  height: 1px;
  background: rgba(255, 255, 255, 0.2);
}

.mat-molecule-text {
  font-size: 9px;
  font-weight: 500;
  letter-spacing: 0.2em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.3);
}

.mat-watermark {
  position: absolute;
  top: 12px;
  right: 20px;
  font-family: var(--font-display);
  font-size: 72px;
  font-weight: 700;
  color: rgba(255, 255, 255, 0.03);
  line-height: 1;
  user-select: none;
}

/* Stats */
.mat-stats {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1px;
  background: rgba(255, 255, 255, 0.06);
}

.mat-stat-card {
  background: var(--black);
  padding: 24px 20px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  gap: 16px;
  min-height: 120px;
}

.mat-stat-value {
  font-family: var(--font-display);
  font-size: clamp(36px, 8vw, 56px);
  font-weight: 700;
  line-height: 1;
}

.mat-stat-label {
  font-size: 9px;
  font-weight: 600;
  letter-spacing: 0.2em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.35);
}

/* Feature cards */
.mat-features {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1px;
  background: rgba(255, 255, 255, 0.06);
  margin-top: 4px;
}

.mat-feature {
  background: var(--black);
  padding: 32px 24px;
  border-top: 1px solid rgba(255, 255, 255, 0.06);
}

.mat-feature:first-child {
  border-top: none;
}

.mat-feature-num {
  display: block;
  font-family: var(--font-display);
  font-size: 48px;
  font-weight: 700;
  color: rgba(255, 255, 255, 0.05);
  line-height: 1;
  margin-bottom: 16px;
}

.mat-feature-title {
  font-family: var(--font-display);
  font-size: 20px;
  font-weight: 700;
  margin-bottom: 10px;
}

.mat-feature-desc {
  font-size: 13px;
  line-height: 1.7;
  color: rgba(255, 255, 255, 0.5);
}

/* Responsive */
@media (min-width: 640px) {
  .mat-inner {
    padding: 80px 40px;
  }
  .mat-features {
    grid-template-columns: repeat(3, 1fr);
  }
  .mat-feature {
    border-top: none;
  }
  .mat-feature + .mat-feature {
    border-left: 1px solid rgba(255, 255, 255, 0.06);
  }
}

@media (min-width: 1024px) {
  .mat-inner {
    padding: 100px 48px;
  }
  .mat-header {
    flex-direction: row;
    align-items: flex-end;
    margin-bottom: 64px;
  }
  .mat-header-right {
    max-width: 400px;
  }
  .mat-grid {
    flex-direction: row;
  }
  .mat-molecule {
    flex: 1.4;
    aspect-ratio: 16/10;
  }
  .mat-stats {
    flex: 1;
    grid-template-columns: 1fr 1fr;
  }
  .mat-stat-card {
    min-height: 140px;
  }
}

@keyframes mat-spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}
</style>
