<template>
  <div
    :class="[
      'driip-image',
      {
        'driip-image--stretch': stretch && !intrinsic,
        'driip-image--intrinsic': intrinsic,
      },
      attrs.class,
      wrapperClass,
    ]"
    :style="wrapperStyle"
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
      :class="['driip-image-img', { 'is-loaded': isLoaded }, imgClass]"
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
import { computed, useAttrs, watch } from "vue";
import { useStableImageLoad } from "~/composables/use-stable-image-load";

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
    intrinsic?: boolean;
  }>(),
  {
    alt: "",
    loading: "lazy",
    loaderSize: 72,
    stretch: false,
    intrinsic: false,
    fit: "contain",
  }
);

const emit = defineEmits<{
  load: [payload: string | Event];
  error: [payload: string | Event];
}>();

const attrs = useAttrs();
const {
  arm: armImageLoad,
  isLoaded,
  settle: settleImageLoad,
} = useStableImageLoad({ minDelayMs: 250, maxWaitMs: 6000 });

const wrapperStyle = computed(() => {
  if (!props.intrinsic) return undefined;

  const width =
    typeof props.width === "number" ? `${props.width}px` : props.width;
  const height =
    typeof props.height === "number" ? `${props.height}px` : props.height;

  return {
    width,
    height,
  };
});

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
    armImageLoad();
  },
  { immediate: true }
);

function onLoad(event: string | Event): void {
  settleImageLoad();
  emit("load", event);
}

function onError(event: string | Event): void {
  settleImageLoad();
  emit("error", event);
}
</script>

<style scoped>
.driip-image {
  position: relative;
  display: block;
  width: 100%;
  height: auto;
  overflow: visible;
}

.driip-image--stretch {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
}

.driip-image--intrinsic {
  display: inline-block;
  width: auto;
  height: auto;
}

.driip-image-img {
  display: block;
  width: 100%;
  height: auto;
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
  object-position: center;
  opacity: 0;
  transition: opacity 0.28s ease, transform 0.28s ease;
}

.driip-image-img.is-loaded {
  opacity: 1;
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
  background: linear-gradient(
    180deg,
    rgba(255, 255, 255, 0.06),
    rgba(255, 255, 255, 0.02)
  );
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  pointer-events: none;
}

.driip-image-loader-logo {
  width: 64px;
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
