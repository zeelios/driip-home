<template>
  <div class="layout">
    <slot />
    <TrackingDebugPanel />
  </div>
</template>

<style>
.layout {
  min-height: 100dvh;
  display: flex;
  flex-direction: column;
}
</style>
