<template>
  <section class="cft-section" id="craft">
    <div class="cft-img-bg">
      <img
        :src="bgSrc"
        alt=""
        aria-hidden="true"
        class="cft-img-bg-img"
        loading="lazy"
      />
      <div class="cft-img-bg-overlay" />
    </div>
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

        <!-- Right: image -->
        <div class="cft-visual reveal">
          <div class="cft-img-wrap">
            <img
              :src="detailSrc"
              alt="Italian tailoring detail"
              class="cft-img"
              loading="lazy"
            />
            <div class="cft-img-overlay" />
            <div class="cft-img-caption">
              <span class="cft-img-caption-line" />
              <div>
                <p class="cft-img-caption-title">{{ t("tee.craft.badge") }}</p>
                <p class="cft-img-caption-sub">
                  {{ t("tee.craft.tailoring") }}
                </p>
              </div>
            </div>
            <span class="cft-img-watermark">04</span>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
const { t } = useI18n();
const bgSrc = "/tee/craft-bg.jpg";
const detailSrc = "/tee/craft-detail.jpg";
</script>

<style scoped>
.cft-section {
  position: relative;
  background: var(--black);
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  overflow: hidden;
}

.cft-img-bg {
  position: absolute;
  inset: 0;
  z-index: 0;
  pointer-events: none;
}

.cft-img-bg-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center;
  opacity: 0.07;
  filter: grayscale(100%);
  display: block;
}

.cft-img-bg-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(
    90deg,
    rgba(0, 0, 0, 0.85) 0%,
    rgba(0, 0, 0, 0.3) 60%,
    rgba(0, 0, 0, 0.6) 100%
  );
}

.cft-inner {
  position: relative;
  z-index: 1;
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

/* Image Visual */
.cft-img-wrap {
  position: relative;
  aspect-ratio: 4 / 5;
  overflow: hidden;
  border: 1px solid rgba(255, 255, 255, 0.08);
}

.cft-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center top;
  display: block;
  filter: grayscale(20%);
  transition: transform 0.6s ease;
}

.cft-img-wrap:hover .cft-img {
  transform: scale(1.03);
}

.cft-img-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(
    to top,
    rgba(0, 0, 0, 0.75) 0%,
    rgba(0, 0, 0, 0.15) 50%,
    transparent 100%
  );
}

.cft-img-caption {
  position: absolute;
  bottom: 20px;
  left: 20px;
  right: 20px;
  display: flex;
  align-items: flex-start;
  gap: 12px;
}

.cft-img-caption-line {
  display: block;
  width: 20px;
  height: 1px;
  background: rgba(255, 255, 255, 0.4);
  flex-shrink: 0;
  margin-top: 6px;
}

.cft-img-caption-title {
  font-size: 9px;
  font-weight: 600;
  letter-spacing: 0.3em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.7);
  margin-bottom: 4px;
}

.cft-img-caption-sub {
  font-size: 11px;
  color: rgba(255, 255, 255, 0.45);
}

.cft-img-watermark {
  position: absolute;
  top: 12px;
  right: 16px;
  font-family: var(--font-display);
  font-size: 72px;
  font-weight: 700;
  color: rgba(255, 255, 255, 0.06);
  line-height: 1;
  user-select: none;
  pointer-events: none;
}

@media (min-width: 640px) {
  .cft-inner {
    padding: 80px 40px;
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
}
</style>
