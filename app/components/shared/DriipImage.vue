<template>
  <div
    :class="[
      'driip-image',
      { 'driip-image--stretch': stretch },
      attrs.class,
      wrapperClass,
    ]"
  >
    <Transition name="driip-image-loader" appear>
      <div
        v-if="!isLoaded"
        :class="['driip-image-loader', loaderClass]"
        aria-hidden="true"
      >
        <NuxtImg
          src="/logo.png"
          alt=""
          class="driip-image-loader-logo"
          :width="loaderSize"
          :height="loaderSize"
          quality="70"
          format="webp"
        />
      </div>
    </Transition>

    <NuxtImg
      v-bind="imageAttrs"
      :src="src"
      :alt="alt"
      :class="imgClass"
      :width="width"
      :height="height"
      :loading="loading"
      :quality="quality"
      :format="format"
      :fit="fit"
      @load="onLoad"
      @error="onError"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, ref, useAttrs, watch } from "vue";

defineOptions({ inheritAttrs: false });

type ClassValue =
  | string
  | Record<string, boolean>
  | Array<string | Record<string, boolean>>
  | undefined;

const props = withDefaults(
  defineProps<{
    src: string;
    alt?: string;
    width?: number | string;
    height?: number | string;
    loading?: "lazy" | "eager";
    quality?: number | string;
    format?: string;
    fit?: string;
    imgClass?: ClassValue;
    wrapperClass?: ClassValue;
    loaderClass?: ClassValue;
    loaderSize?: number;
    stretch?: boolean;
  }>(),
  {
    alt: "",
    loading: "lazy",
    loaderSize: 72,
    stretch: false,
  }
);

const emit = defineEmits<{
  load: [payload: string | Event];
  error: [payload: string | Event];
}>();

const attrs = useAttrs();
const isLoaded = ref(false);

const imageAttrs = computed(() => {
  const {
    class: _class,
    style: _style,
    ...rest
  } = attrs as Record<string, unknown>;
  return rest;
});

watch(
  () => props.src,
  () => {
    isLoaded.value = false;
  },
  { immediate: true }
);

function onLoad(event: string | Event): void {
  isLoaded.value = true;
  emit("load", event);
}

function onError(event: string | Event): void {
  isLoaded.value = true;
  emit("error", event);
}
</script>

<style scoped>
.driip-image {
  position: relative;
  display: inline-block;
  width: auto;
  height: auto;
  overflow: hidden;
}

.driip-image--stretch {
  display: block;
  width: 100%;
  height: 100%;
}

.driip-image :deep(img) {
  display: block;
}

.driip-image-loader {
  position: absolute;
  inset: 0;
  z-index: 2;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 0, 0, 0.08);
  pointer-events: none;
}

.driip-image-loader-logo {
  width: 72px;
  height: auto;
  opacity: 0.9;
  animation: driip-logo-pulse 1.2s ease-in-out infinite;
  filter: drop-shadow(0 0 18px rgba(255, 255, 255, 0.16));
}

.driip-image-loader-enter-active,
.driip-image-loader-leave-active {
  transition: opacity 0.3s ease, transform 0.3s ease;
}

.driip-image-loader-enter-from,
.driip-image-loader-leave-to {
  opacity: 0;
  transform: scale(0.98);
}

@keyframes driip-logo-pulse {
  0%,
  100% {
    transform: scale(1);
    opacity: 0.72;
  }
  50% {
    transform: scale(1.06);
    opacity: 1;
  }
}
</style>
