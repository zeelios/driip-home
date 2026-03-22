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
    <NuxtImg
      v-bind="imageAttrs"
      :src="src"
      :alt="alt"
      :class="['driip-image-img', imgClass]"
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
import { computed, useAttrs } from "vue";

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

function onLoad(event: string | Event): void {
  emit("load", event);
}

function onError(event: string | Event): void {
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
  opacity: 0.95;
  transition: opacity 0.28s ease, transform 0.28s ease;
  animation: fade-in 0.4s ease forwards;
}

.driip-image :deep(img) {
  display: block;
}

@keyframes fade-in {
  from {
    opacity: 0;
  }
  to {
    opacity: 0.95;
  }
}
</style>
