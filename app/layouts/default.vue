<template>
  <div class="layout">
    <slot />
  </div>
</template>

<style>
.layout {
  min-height: 100dvh;
  display: flex;
  flex-direction: column;
}
</style>
