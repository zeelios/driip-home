<template>
  <div class="dtg-page">
    <nav class="dtg-nav">
      <NuxtLink to="/" class="dtg-nav-back">← DRIIP</NuxtLink>
      <span class="dtg-nav-label">SCIENCE OF PRINT</span>
    </nav>

    <section class="dtg-hero">
      <div class="dtg-hero-inner">
        <p class="dtg-eyebrow">DRIIP RESEARCH — PRINT TECHNOLOGY</p>
        <h1 class="dtg-title">Direct-to-Garment<br /><span class="dtg-title--muted">Printing</span></h1>
        <p class="dtg-hero-lead">Most brands screen-print. We chose DTG. Here's exactly why that choice costs more — and why it's worth every đồng.</p>
      </div>
    </section>

    <section class="dtg-section">
      <div class="dtg-inner">
        <div class="dtg-split">
          <div class="dtg-split-copy">
            <p class="dtg-section-label">THE PROBLEM WITH SCREEN PRINTING</p>
            <h2 class="dtg-section-title">Ink on top.<br />Not inside.</h2>
            <p class="dtg-body">Traditional screen printing deposits layers of ink on the surface of the fabric. The result is a design that sits on top of the garment — not fused with it. After a few washes, that layer cracks, peels, and flakes.</p>
            <p class="dtg-body">It's fast. It's cheap. It's why mass-market fashion can offer 50,000đ tees with prints. And it's why those tees look destroyed after three months.</p>
          </div>
          <div class="dtg-split-vis">
            <div class="dtg-layer-vis dtg-layer-vis--screen">
              <div class="dtg-layer dtg-layer--ink-thick">
                <span class="dtg-layer-name">Ink layer (surface)</span>
              </div>
              <div class="dtg-layer dtg-layer--fabric">
                <span class="dtg-layer-name">Fabric fibers</span>
              </div>
              <p class="dtg-vis-caption">Screen print: ink sits on top, separates over time</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="dtg-section dtg-section--dark">
      <div class="dtg-inner">
        <div class="dtg-split dtg-split--reverse">
          <div class="dtg-split-copy">
            <p class="dtg-section-label">THE DTG DIFFERENCE</p>
            <h2 class="dtg-section-title">Ink inside.<br />Molecular bond.</h2>
            <p class="dtg-body">DTG uses piezoelectric print heads to fire water-based ink directly into the fiber matrix of the fabric. The ink doesn't coat the top — it saturates the individual threads at the fiber level.</p>
            <p class="dtg-body">The result: print that is structurally part of the garment. It doesn't crack. It doesn't peel. It breathes with the fabric because it <em>is</em> the fabric at the print layer.</p>
          </div>
          <div class="dtg-split-vis">
            <div class="dtg-layer-vis dtg-layer-vis--dtg">
              <div class="dtg-layer dtg-layer--fused">
                <span class="dtg-layer-name">Ink + fiber (fused)</span>
                <div class="dtg-shimmer" />
              </div>
              <p class="dtg-vis-caption">DTG: ink fused into fiber matrix — permanent bond</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="dtg-section">
      <div class="dtg-inner">
        <p class="dtg-section-label">PROCESS — HOW IT WORKS</p>
        <div class="dtg-steps">
          <div class="dtg-step">
            <span class="dtg-step-num">01</span>
            <h3 class="dtg-step-title">Pre-treatment</h3>
            <p class="dtg-step-desc">The garment is pre-treated with a bonding solution that primes the fiber surface — allowing water-based inks to penetrate and lock in. Without this step, the ink washes out.</p>
          </div>
          <div class="dtg-step">
            <span class="dtg-step-num">02</span>
            <h3 class="dtg-step-title">Direct inkjet application</h3>
            <p class="dtg-step-desc">Piezoelectric print heads fire droplets at 2–4 picoliters per drop — finer than a human hair. Each pixel of the design is placed at fiber-level precision across the garment surface.</p>
          </div>
          <div class="dtg-step">
            <span class="dtg-step-num">03</span>
            <h3 class="dtg-step-title">Heat cure</h3>
            <p class="dtg-step-desc">A belt dryer at 165°C cures the ink into the fiber matrix, forming a permanent molecular bond. The design is now structurally integrated — not decorative.</p>
          </div>
        </div>
      </div>
    </section>

    <section class="dtg-section dtg-section--dark">
      <div class="dtg-inner">
        <p class="dtg-section-label">INVESTMENT RATIONALE</p>
        <h2 class="dtg-section-title--lg">Why we pay more for this</h2>
        <div class="dtg-grid-3">
          <div class="dtg-card">
            <h4 class="dtg-card-title">Longevity over economy</h4>
            <p class="dtg-card-desc">A Driip garment with DTG print outlasts 5–7 screen-printed alternatives. Measured over years, the cost-per-wear collapses.</p>
          </div>
          <div class="dtg-card">
            <h4 class="dtg-card-title">Zero plastic inks</h4>
            <p class="dtg-card-desc">Water-based DTG inks contain no PVC, no phthalates, no plastisol. Screen printing relies on petrochemical-based inks that off-gas and persist in landfill.</p>
          </div>
          <div class="dtg-card">
            <h4 class="dtg-card-title">Design integrity</h4>
            <p class="dtg-card-desc">Complex gradients, fine details, and full-color photographic prints are achievable at fiber level. Screen printing requires separate screens per color — limiting complexity.</p>
          </div>
        </div>
      </div>
    </section>

    <section class="dtg-section dtg-cta-section">
      <div class="dtg-inner dtg-cta-inner">
        <p class="dtg-cta-label">SEE IT IN PRACTICE</p>
        <h2 class="dtg-cta-title">The DRIIP Tee uses DTG exclusively.</h2>
        <p class="dtg-cta-sub">Every print on every Driip garment is DTG. No exceptions. No compromises for cost reduction.</p>
        <div class="dtg-cta-links">
          <NuxtLink to="/driip-tee" class="dtg-btn-primary">SHOP DRIIP TEE</NuxtLink>
          <NuxtLink to="/science/sorona-fabric" class="dtg-btn-ghost">READ: SORONA FABRIC →</NuxtLink>
        </div>
      </div>
    </section>

    <SiteFooter />
  </div>
</template>

<script setup lang="ts">
definePageMeta({ layout: "default" });
</script>

<style scoped>
.dtg-page {
  background: var(--black);
  color: var(--white);
  min-height: 100dvh;
}

.dtg-nav {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 50;
  height: 52px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 20px;
  background: rgba(0,0,0,0.92);
  backdrop-filter: blur(20px);
  border-bottom: 1px solid rgba(255,255,255,0.06);
}

.dtg-nav-back {
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.1em;
  color: rgba(255,255,255,0.5);
  text-decoration: none;
  transition: color 0.2s;
}
.dtg-nav-back:hover { color: var(--white); }

.dtg-nav-label {
  font-size: 9px;
  font-weight: 700;
  letter-spacing: 0.3em;
  text-transform: uppercase;
  color: rgba(255,255,255,0.3);
}

/* Hero */
.dtg-hero {
  min-height: 60dvh;
  display: flex;
  align-items: flex-end;
  padding-top: 52px;
  border-bottom: 1px solid rgba(255,255,255,0.06);
}

.dtg-hero-inner {
  width: min(1400px, 100%);
  margin: 0 auto;
  padding: 60px 20px 56px;
}

.dtg-eyebrow {
  font-size: 9px;
  font-weight: 700;
  letter-spacing: 0.3em;
  text-transform: uppercase;
  color: rgba(255,255,255,0.3);
  margin-bottom: 24px;
}

.dtg-title {
  font-family: var(--font-display);
  font-size: clamp(48px, 10vw, 96px);
  font-weight: 700;
  line-height: 0.9;
  letter-spacing: -0.02em;
  margin-bottom: 32px;
}

.dtg-title--muted { color: rgba(255,255,255,0.2); }

.dtg-hero-lead {
  font-size: clamp(15px, 2vw, 18px);
  line-height: 1.7;
  color: rgba(255,255,255,0.6);
  max-width: 560px;
}

/* Sections */
.dtg-section {
  border-bottom: 1px solid rgba(255,255,255,0.06);
}

.dtg-section--dark { background: #080808; }

.dtg-inner {
  width: min(1400px, 100%);
  margin: 0 auto;
  padding: 72px 20px;
}

.dtg-section-label {
  font-size: 9px;
  font-weight: 700;
  letter-spacing: 0.3em;
  text-transform: uppercase;
  color: rgba(255,255,255,0.3);
  margin-bottom: 20px;
}

.dtg-section-title {
  font-family: var(--font-display);
  font-size: clamp(32px, 6vw, 56px);
  font-weight: 700;
  line-height: 0.92;
  letter-spacing: -0.02em;
  margin-bottom: 24px;
}

.dtg-section-title--lg {
  font-family: var(--font-display);
  font-size: clamp(36px, 7vw, 64px);
  font-weight: 700;
  line-height: 0.92;
  letter-spacing: -0.02em;
  margin-bottom: 40px;
}

.dtg-body {
  font-size: 15px;
  line-height: 1.8;
  color: rgba(255,255,255,0.6);
  margin-bottom: 16px;
  max-width: 520px;
}

.dtg-body em { color: rgba(255,255,255,0.85); font-style: normal; }

/* Split layout */
.dtg-split {
  display: flex;
  flex-direction: column;
  gap: 48px;
}

.dtg-split-copy { flex: 1; }
.dtg-split-vis { flex: 1; }

/* Layer visualisation */
.dtg-layer-vis {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.dtg-layer {
  display: flex;
  align-items: center;
  padding: 0 16px;
  position: relative;
  overflow: hidden;
}

.dtg-layer--ink-thick {
  height: 28px;
  background: rgba(255,255,255,0.25);
}

.dtg-layer--fabric {
  height: 56px;
  background: rgba(255,255,255,0.05);
  border: 1px solid rgba(255,255,255,0.08);
}

.dtg-layer--fused {
  height: 56px;
  background: rgba(255,255,255,0.08);
  border: 1px solid rgba(255,255,255,0.15);
}

.dtg-layer-name {
  font-size: 9px;
  font-weight: 600;
  letter-spacing: 0.15em;
  text-transform: uppercase;
  color: rgba(255,255,255,0.4);
}

.dtg-vis-caption {
  font-size: 11px;
  color: rgba(255,255,255,0.3);
  margin-top: 12px;
  font-style: italic;
}

.dtg-shimmer {
  position: absolute;
  inset: 0;
  background: linear-gradient(to right, transparent, rgba(255,255,255,0.1), transparent);
  animation: shimmer 2.5s ease-in-out infinite;
}

/* Steps */
.dtg-steps {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1px;
  background: rgba(255,255,255,0.06);
  margin-top: 40px;
}

.dtg-step {
  background: var(--black);
  padding: 36px 24px;
}

.dtg-step-num {
  display: block;
  font-family: var(--font-display);
  font-size: clamp(56px, 10vw, 88px);
  font-weight: 700;
  color: rgba(255,255,255,0.04);
  line-height: 1;
  margin-bottom: 12px;
}

.dtg-step-title {
  font-family: var(--font-display);
  font-size: 22px;
  font-weight: 700;
  margin-bottom: 10px;
}

.dtg-step-desc {
  font-size: 14px;
  line-height: 1.7;
  color: rgba(255,255,255,0.5);
}

/* 3-col grid */
.dtg-grid-3 {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1px;
  background: rgba(255,255,255,0.06);
}

.dtg-card {
  background: #080808;
  padding: 32px 24px;
}

.dtg-card-title {
  font-family: var(--font-display);
  font-size: 20px;
  font-weight: 700;
  margin-bottom: 12px;
}

.dtg-card-desc {
  font-size: 13px;
  line-height: 1.7;
  color: rgba(255,255,255,0.5);
}

/* CTA */
.dtg-cta-section { background: var(--black); }

.dtg-cta-inner {
  text-align: center;
  padding: 80px 20px;
}

.dtg-cta-label {
  font-size: 9px;
  font-weight: 700;
  letter-spacing: 0.3em;
  text-transform: uppercase;
  color: rgba(255,255,255,0.3);
  margin-bottom: 20px;
}

.dtg-cta-title {
  font-family: var(--font-display);
  font-size: clamp(28px, 6vw, 52px);
  font-weight: 700;
  letter-spacing: -0.02em;
  margin-bottom: 16px;
}

.dtg-cta-sub {
  font-size: 14px;
  color: rgba(255,255,255,0.5);
  margin-bottom: 36px;
  max-width: 480px;
  margin-left: auto;
  margin-right: auto;
}

.dtg-cta-links {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
}

.dtg-btn-primary {
  padding: 14px 36px;
  background: var(--white);
  color: var(--black);
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.1em;
  text-decoration: none;
  transition: background 0.2s;
}
.dtg-btn-primary:hover { background: rgba(255,255,255,0.88); }

.dtg-btn-ghost {
  font-size: 12px;
  font-weight: 600;
  letter-spacing: 0.1em;
  color: rgba(255,255,255,0.4);
  text-decoration: none;
  transition: color 0.2s;
}
.dtg-btn-ghost:hover { color: var(--white); }

@media (min-width: 640px) {
  .dtg-steps { grid-template-columns: repeat(3, 1fr); }
  .dtg-grid-3 { grid-template-columns: repeat(3, 1fr); }
  .dtg-cta-links { flex-direction: row; justify-content: center; }
}

@media (min-width: 1024px) {
  .dtg-inner { padding: 96px 48px; }
  .dtg-hero-inner { padding: 80px 48px 72px; }
  .dtg-split { flex-direction: row; align-items: flex-start; gap: 80px; }
  .dtg-split--reverse { flex-direction: row-reverse; }
}

@keyframes shimmer {
  0% { transform: translateX(-100%); }
  50% { transform: translateX(100%); }
  100% { transform: translateX(100%); }
}
</style>
