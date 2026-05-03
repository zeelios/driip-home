<template>
  <div class="auth-shell">
    <div class="auth-brand">
      <span class="auth-brand__name">driip-</span>
      <span class="auth-brand__tag">Panel</span>
    </div>
    <slot />
  </div>
</template>

<style scoped>
.auth-shell {
  min-height: 100dvh;
  display: flex; flex-direction: column;
  align-items: center; justify-content: center;
  padding: 2rem 1rem;
  background: var(--bg);
}
.auth-brand {
  display: flex; align-items: baseline; gap: 0.4rem; margin-bottom: 2.5rem;
}
.auth-brand__name {
  font-family: "Barlow Condensed", ui-sans-serif, sans-serif;
  font-size: 1.5rem; font-weight: 700; letter-spacing: 0.12em;
  text-transform: uppercase; color: var(--text);
}
.auth-brand__tag {
  font-size: 0.65rem; font-weight: 600; letter-spacing: 0.15em;
  text-transform: uppercase; color: var(--text-mute);
}
</style>
