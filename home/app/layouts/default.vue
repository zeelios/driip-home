<template>
  <div class="layout">
    <SharedSiteNav />
    <slot />
    <SharedMessengerChat />
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
