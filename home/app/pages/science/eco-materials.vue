<template>
  <div class="eco-page">
    <nav class="eco-nav">
      <NuxtLink to="/" class="eco-nav-back">← DRIIP</NuxtLink>
      <span class="eco-nav-label">SCIENCE OF RESPONSIBILITY</span>
    </nav>

    <section class="eco-hero">
      <div class="eco-hero-inner">
        <p class="eco-eyebrow">DRIIP RESEARCH — ENVIRONMENTAL RESPONSIBILITY</p>
        <h1 class="eco-title">Eco-Friendly<br /><span class="eco-title--muted">Materials</span></h1>
        <p class="eco-hero-lead">Luxury is not just about what something feels like. It's about what it costs the world to exist — and what happens to it when you're done with it.</p>
      </div>
    </section>

    <!-- Manifesto -->
    <section class="eco-section eco-section--dark">
      <div class="eco-inner">
        <div class="eco-manifesto">
          <p class="eco-section-label">OUR POSITION</p>
          <blockquote class="eco-manifesto-text">
            "The fashion industry produces 10% of global CO₂ emissions and is the second largest consumer of the world's water. Most of that cost is invisible — absorbed by rivers, communities, and the atmosphere so that a garment can cost less.
            <br /><br />
            We refuse to participate in that invisibility. Every material decision we make is documented, justified, and measured against an honest environmental standard."
          </blockquote>
          <p class="eco-manifesto-attr">— Driip Brand Philosophy</p>
        </div>
      </div>
    </section>

    <!-- What we use -->
    <section class="eco-section">
      <div class="eco-inner">
        <p class="eco-section-label">MATERIAL CHOICES</p>
        <h2 class="eco-section-title">What we use<br />and why</h2>
        <div class="eco-materials">
          <div class="eco-material">
            <div class="eco-material-head">
              <h3 class="eco-material-name">Sorona® (PTT Biopolymer)</h3>
              <span class="eco-material-badge eco-material-badge--good">37% Bio-based</span>
            </div>
            <p class="eco-material-desc">Derived 37% from corn glucose fermentation. Requires 30% less energy and produces 63% fewer greenhouse gases than nylon 6,6. The spring-coil molecular structure gives inherent stretch recovery, meaning garments hold their shape longer — reducing replacement frequency.</p>
            <div class="eco-material-stats">
              <div class="eco-material-stat">
                <span class="eco-s-val">−30%</span>
                <span class="eco-s-label">Energy vs. nylon</span>
              </div>
              <div class="eco-material-stat">
                <span class="eco-s-val">−63%</span>
                <span class="eco-s-label">GHG vs. nylon</span>
              </div>
            </div>
          </div>

          <div class="eco-material">
            <div class="eco-material-head">
              <h3 class="eco-material-name">Water-based DTG Inks</h3>
              <span class="eco-material-badge eco-material-badge--good">Zero PVC</span>
            </div>
            <p class="eco-material-desc">Conventional screen printing uses plastisol inks — a PVC-based medium that releases phthalates during curing and persists in soil for decades. Our DTG inks are water-based, contain no plasticisers, and break down without leaving petrochemical residue.</p>
            <div class="eco-material-stats">
              <div class="eco-material-stat">
                <span class="eco-s-val">0</span>
                <span class="eco-s-label">PVC content</span>
              </div>
              <div class="eco-material-stat">
                <span class="eco-s-val">0</span>
                <span class="eco-s-label">Phthalates</span>
              </div>
            </div>
          </div>

          <div class="eco-material">
            <div class="eco-material-head">
              <h3 class="eco-material-name">EVA Foam (Slides)</h3>
              <span class="eco-material-badge eco-material-badge--mid">Recyclable</span>
            </div>
            <p class="eco-material-desc">Ethylene-vinyl acetate foam is one of the most recyclable closed-cell foams available. Unlike PU (polyurethane), EVA can be mechanically ground, re-formed, and re-used. It is also free from heavy metals and BPA, making it safe for both skin contact and end-of-life processing.</p>
            <div class="eco-material-stats">
              <div class="eco-material-stat">
                <span class="eco-s-val">✓</span>
                <span class="eco-s-label">Mechanically recyclable</span>
              </div>
              <div class="eco-material-stat">
                <span class="eco-s-val">0</span>
                <span class="eco-s-label">Heavy metals</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Recyclability -->
    <section class="eco-section eco-section--dark">
      <div class="eco-inner">
        <p class="eco-section-label">END OF LIFE</p>
        <h2 class="eco-section-title">What happens<br />when you're done</h2>
        <p class="eco-body eco-body--wide">Most fashion is designed with zero consideration for end-of-life. Mixed-fiber blends, laminated coatings, and multi-material construction make recycling economically impossible. Garments go to landfill by design.</p>

        <div class="eco-lifecycle">
          <div class="eco-lc-step">
            <span class="eco-lc-num">01</span>
            <h4 class="eco-lc-title">Fiber separation</h4>
            <p class="eco-lc-desc">Sorona garments can be mechanically shredded and sorted by fiber type. The PTT biopolymer can be chemically recycled back to its monomer components — 1,3-PDO and terephthalic acid — for reuse in new polymer production.</p>
          </div>
          <div class="eco-lc-step">
            <span class="eco-lc-num">02</span>
            <h4 class="eco-lc-title">EVA reclamation</h4>
            <p class="eco-lc-desc">EVA foam from slides can be ground into crumb rubber substitute, used in playground surfaces, sports flooring, or re-compressed into new foam products. Unlike PU foam, it doesn't require chemical dissolution.</p>
          </div>
          <div class="eco-lc-step">
            <span class="eco-lc-num">03</span>
            <h4 class="eco-lc-title">Return program (coming)</h4>
            <p class="eco-lc-desc">We are building a return-and-recycle program for Driip products. When worn out, send them back. We'll handle reclamation. No landfill by design.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- What we don't use -->
    <section class="eco-section">
      <div class="eco-inner">
        <p class="eco-section-label">WHAT WE AVOID — AND WHY</p>
        <div class="eco-avoid-grid">
          <div class="eco-avoid-item">
            <span class="eco-avoid-name">Virgin polyester</span>
            <p class="eco-avoid-reason">100% petroleum-based. Sheds microplastics on every wash. Non-biodegradable. Cheaper than Sorona — not cheaper for the planet.</p>
          </div>
          <div class="eco-avoid-item">
            <span class="eco-avoid-name">Plastisol screen printing</span>
            <p class="eco-avoid-reason">PVC-based ink with phthalate plasticisers. Off-gasses during production. Cannot be safely separated from fabric at end of life.</p>
          </div>
          <div class="eco-avoid-item">
            <span class="eco-avoid-name">PU foam (slides)</span>
            <p class="eco-avoid-reason">Requires chemical solvents to recycle. Degrades into microparticles. Heavier energy footprint in production vs. EVA.</p>
          </div>
          <div class="eco-avoid-item">
            <span class="eco-avoid-name">Azo dyes</span>
            <p class="eco-avoid-reason">A class of synthetic dyes that can release carcinogenic aromatic amines. Common in low-cost fashion. Not used in any Driip product.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Honest limits -->
    <section class="eco-section eco-section--dark">
      <div class="eco-inner">
        <p class="eco-section-label">HONEST LIMITATIONS</p>
        <h2 class="eco-section-title">What we haven't solved yet</h2>
        <p class="eco-body eco-body--wide">We don't claim to be a fully sustainable brand. That label is, in most cases, a lie. Here is what we are still working on:</p>
        <div class="eco-limits">
          <div class="eco-limit">
            <span class="eco-limit-dot" />
            <div>
              <p class="eco-limit-title">Sorona is 63% petrochemical</p>
              <p class="eco-limit-desc">The terephthalic acid component is still fossil-derived. DuPont is researching bio-based alternatives. We will adopt them when they reach commercial scale.</p>
            </div>
          </div>
          <div class="eco-limit">
            <span class="eco-limit-dot" />
            <div>
              <p class="eco-limit-title">Shipping emissions</p>
              <p class="eco-limit-desc">Every order generates logistics emissions. We are measuring this and investigating carbon offset programs, though we will not use offsets as a substitute for actual reduction.</p>
            </div>
          </div>
          <div class="eco-limit">
            <span class="eco-limit-dot" />
            <div>
              <p class="eco-limit-title">No take-back program yet</p>
              <p class="eco-limit-desc">Our return-and-recycle infrastructure is in development. Until it launches, end-of-life responsibility falls on the customer. We acknowledge this gap.</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA -->
    <section class="eco-cta-section">
      <div class="eco-inner eco-cta-inner">
        <p class="eco-cta-label">CONTINUE READING</p>
        <h2 class="eco-cta-title">The science behind every decision.</h2>
        <div class="eco-cta-links">
          <NuxtLink to="/science/sorona-fabric" class="eco-btn-primary">READ: SORONA FABRIC →</NuxtLink>
          <NuxtLink to="/science/dtg-printing" class="eco-btn-ghost">READ: DTG PRINTING →</NuxtLink>
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
.eco-page {
  background: var(--black);
  color: var(--white);
  min-height: 100dvh;
}

.eco-nav {
  position: fixed;
  top: 0; left: 0; right: 0;
  z-index: 50; height: 52px;
  display: flex; align-items: center; justify-content: space-between;
  padding: 0 20px;
  background: rgba(0,0,0,0.92);
  backdrop-filter: blur(20px);
  border-bottom: 1px solid rgba(255,255,255,0.06);
}

.eco-nav-back {
  font-size: 11px; font-weight: 700; letter-spacing: 0.1em;
  color: rgba(255,255,255,0.5); text-decoration: none; transition: color 0.2s;
}
.eco-nav-back:hover { color: var(--white); }

.eco-nav-label {
  font-size: 9px; font-weight: 700; letter-spacing: 0.3em;
  text-transform: uppercase; color: rgba(255,255,255,0.3);
}

/* Hero */
.eco-hero {
  min-height: 60dvh;
  display: flex; align-items: flex-end;
  padding-top: 52px;
  border-bottom: 1px solid rgba(255,255,255,0.06);
}

.eco-hero-inner {
  width: min(1400px, 100%);
  margin: 0 auto;
  padding: 60px 20px 56px;
}

.eco-eyebrow {
  font-size: 9px; font-weight: 700; letter-spacing: 0.3em;
  text-transform: uppercase; color: rgba(255,255,255,0.3); margin-bottom: 24px;
}

.eco-title {
  font-family: var(--font-display);
  font-size: clamp(48px, 10vw, 96px);
  font-weight: 700; line-height: 0.9;
  letter-spacing: -0.02em; margin-bottom: 32px;
}

.eco-title--muted { color: rgba(255,255,255,0.2); }

.eco-hero-lead {
  font-size: clamp(15px, 2vw, 18px);
  line-height: 1.7; color: rgba(255,255,255,0.6); max-width: 560px;
}

/* Sections */
.eco-section { border-bottom: 1px solid rgba(255,255,255,0.06); }
.eco-section--dark { background: #080808; }

.eco-inner {
  width: min(1400px, 100%);
  margin: 0 auto;
  padding: 72px 20px;
}

.eco-section-label {
  font-size: 9px; font-weight: 700; letter-spacing: 0.3em;
  text-transform: uppercase; color: rgba(255,255,255,0.3); margin-bottom: 20px;
}

.eco-section-title {
  font-family: var(--font-display);
  font-size: clamp(32px, 6vw, 56px);
  font-weight: 700; line-height: 0.92;
  letter-spacing: -0.02em; margin-bottom: 32px;
}

.eco-body {
  font-size: 15px; line-height: 1.8;
  color: rgba(255,255,255,0.6); margin-bottom: 32px;
}

.eco-body--wide { max-width: 680px; }

/* Manifesto */
.eco-manifesto-text {
  font-family: var(--font-display);
  font-size: clamp(18px, 3vw, 28px);
  font-weight: 700; line-height: 1.4;
  color: rgba(255,255,255,0.75);
  border-left: 2px solid rgba(255,255,255,0.15);
  padding-left: 24px;
  max-width: 800px;
  margin-bottom: 16px;
  font-style: normal;
}

.eco-manifesto-attr {
  font-size: 11px; font-weight: 600;
  letter-spacing: 0.2em; text-transform: uppercase;
  color: rgba(255,255,255,0.3); padding-left: 24px;
}

/* Materials */
.eco-materials {
  display: flex; flex-direction: column;
  gap: 1px; background: rgba(255,255,255,0.06);
  margin-top: 40px;
}

.eco-material {
  background: var(--black);
  padding: 32px 24px;
}

.eco-material-head {
  display: flex; align-items: flex-start;
  justify-content: space-between;
  gap: 16px; margin-bottom: 16px; flex-wrap: wrap;
}

.eco-material-name {
  font-family: var(--font-display);
  font-size: 22px; font-weight: 700;
}

.eco-material-badge {
  font-size: 9px; font-weight: 700;
  letter-spacing: 0.15em; text-transform: uppercase;
  padding: 5px 12px; flex-shrink: 0;
}

.eco-material-badge--good {
  background: rgba(74,222,128,0.1);
  border: 1px solid rgba(74,222,128,0.3);
  color: #4ade80;
}

.eco-material-badge--mid {
  background: rgba(251,191,36,0.1);
  border: 1px solid rgba(251,191,36,0.3);
  color: #fbbf24;
}

.eco-material-desc {
  font-size: 14px; line-height: 1.7;
  color: rgba(255,255,255,0.55); margin-bottom: 20px;
  max-width: 640px;
}

.eco-material-stats {
  display: flex; gap: 32px; flex-wrap: wrap;
}

.eco-material-stat {
  display: flex; flex-direction: column; gap: 4px;
}

.eco-s-val {
  font-family: var(--font-display);
  font-size: 28px; font-weight: 700; line-height: 1;
}

.eco-s-label {
  font-size: 9px; font-weight: 600;
  letter-spacing: 0.15em; text-transform: uppercase;
  color: rgba(255,255,255,0.35);
}

/* Lifecycle */
.eco-lifecycle {
  display: grid; grid-template-columns: 1fr;
  gap: 1px; background: rgba(255,255,255,0.06);
  margin-top: 40px;
}

.eco-lc-step {
  background: #080808; padding: 32px 24px;
}

.eco-lc-num {
  display: block;
  font-family: var(--font-display);
  font-size: clamp(56px, 10vw, 80px);
  font-weight: 700; color: rgba(255,255,255,0.04);
  line-height: 1; margin-bottom: 12px;
}

.eco-lc-title {
  font-family: var(--font-display);
  font-size: 20px; font-weight: 700; margin-bottom: 10px;
}

.eco-lc-desc {
  font-size: 13px; line-height: 1.7; color: rgba(255,255,255,0.5);
}

/* Avoid grid */
.eco-avoid-grid {
  display: grid; grid-template-columns: 1fr;
  gap: 1px; background: rgba(255,255,255,0.06);
  margin-top: 40px;
}

.eco-avoid-item {
  background: var(--black); padding: 28px 24px;
}

.eco-avoid-name {
  display: block;
  font-family: var(--font-display);
  font-size: 18px; font-weight: 700;
  color: rgba(255,255,255,0.5);
  text-decoration: line-through;
  text-decoration-color: rgba(255,255,255,0.2);
  margin-bottom: 10px;
}

.eco-avoid-reason {
  font-size: 13px; line-height: 1.7; color: rgba(255,255,255,0.45);
}

/* Limits */
.eco-limits {
  display: flex; flex-direction: column; gap: 24px; margin-top: 32px;
}

.eco-limit {
  display: flex; align-items: flex-start; gap: 16px;
}

.eco-limit-dot {
  display: block; width: 6px; height: 6px;
  background: rgba(251,191,36,0.6);
  border-radius: 50%; margin-top: 5px; flex-shrink: 0;
}

.eco-limit-title {
  font-size: 14px; font-weight: 600; margin-bottom: 6px;
}

.eco-limit-desc {
  font-size: 13px; line-height: 1.7; color: rgba(255,255,255,0.5);
}

/* CTA */
.eco-cta-section { background: var(--black); border-top: 1px solid rgba(255,255,255,0.06); }

.eco-cta-inner { text-align: center; padding: 80px 20px; }

.eco-cta-label {
  font-size: 9px; font-weight: 700; letter-spacing: 0.3em;
  text-transform: uppercase; color: rgba(255,255,255,0.3); margin-bottom: 20px;
}

.eco-cta-title {
  font-family: var(--font-display);
  font-size: clamp(28px, 6vw, 52px);
  font-weight: 700; letter-spacing: -0.02em; margin-bottom: 36px;
}

.eco-cta-links {
  display: flex; flex-direction: column; align-items: center; gap: 16px;
}

.eco-btn-primary {
  padding: 14px 36px;
  background: var(--white); color: var(--black);
  font-size: 12px; font-weight: 700; letter-spacing: 0.1em;
  text-decoration: none; transition: background 0.2s;
}
.eco-btn-primary:hover { background: rgba(255,255,255,0.88); }

.eco-btn-ghost {
  font-size: 12px; font-weight: 600; letter-spacing: 0.1em;
  color: rgba(255,255,255,0.4); text-decoration: none; transition: color 0.2s;
}
.eco-btn-ghost:hover { color: var(--white); }

@media (min-width: 640px) {
  .eco-lifecycle { grid-template-columns: repeat(3, 1fr); }
  .eco-avoid-grid { grid-template-columns: repeat(2, 1fr); }
  .eco-cta-links { flex-direction: row; justify-content: center; }
}

@media (min-width: 1024px) {
  .eco-inner { padding: 96px 48px; }
  .eco-hero-inner { padding: 80px 48px 72px; }
  .eco-avoid-grid { grid-template-columns: repeat(4, 1fr); }
}
</style>
