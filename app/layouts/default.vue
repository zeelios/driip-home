<template>
  <div class="layout">
    <slot />
    <SharedTrackingDebugPanel />
  </div>
</template>

<style>
.layout {
  min-height: 100dvh;
  display: flex;
  flex-direction: column;
}
</style>
