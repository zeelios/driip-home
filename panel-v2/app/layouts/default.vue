<template>
  <div class="shell">
    <PanelNav />
    <main class="shell__main">
      <slot />
    </main>
  </div>
</template>

<style scoped>
.shell { display: flex; min-height: 100dvh; }

.shell__main {
  flex: 1; min-width: 0;
  /* Mobile: full width, pad bottom for nav bar */
  padding: 1rem 1rem calc(64px + env(safe-area-inset-bottom, 0) + 1rem);
}

@media (min-width: 768px) {
  .shell__main {
    margin-left: var(--sidebar-w);
    padding: 1.5rem 1.5rem 2rem;
  }
}
</style>
