<script setup lang="ts">
import { computed, ref } from 'vue'

type MediaSign = {
  name: string
  public_url: string
  thumbnail_url: string | null
  mime_type: string
  width: number | null
  height: number | null
  column_ratio: number | null
}

const props = defineProps<{
  sign: MediaSign
}>()

const isLoaded = ref(false)
const videoRef = ref<HTMLVideoElement | null>(null)

const isVideo = computed(() => props.sign.mime_type === 'video/webm')
const imageSrc = computed(() => props.sign.thumbnail_url ?? props.sign.public_url)

function aspectRatio(): string {
  if (props.sign.width && props.sign.height) return `${props.sign.width} / ${props.sign.height}`
  if (props.sign.column_ratio) return `${props.sign.column_ratio} / 1`
  return '1 / 1'
}

function markLoaded() {
  isLoaded.value = true
}

async function playVideo() {
  if (!isVideo.value || !videoRef.value) return

  try {
    await videoRef.value.play()
  } catch {
    // Autoplay can be blocked despite mute/inline; leave the first frame visible.
  }
}

function pauseVideo() {
  if (!isVideo.value || !videoRef.value) return

  videoRef.value.pause()
  videoRef.value.currentTime = 0
}
</script>

<template>
  <div
    class="relative w-full overflow-hidden"
    :style="{ aspectRatio: aspectRatio() }"
    @mouseenter="playVideo"
    @mouseleave="pauseVideo"
  >
    <Transition name="skeleton-fade">
      <div v-if="!isLoaded" class="absolute inset-0 animate-pulse rounded bg-zinc-800" />
    </Transition>

    <video
      v-if="isVideo"
      ref="videoRef"
      :src="sign.public_url"
      :aria-label="sign.name"
      muted
      loop
      playsinline
      preload="metadata"
      class="absolute inset-0 block h-full w-full object-contain transition-opacity duration-300 ease-in-out"
      :class="isLoaded ? 'opacity-100' : 'opacity-0'"
      @loadeddata="markLoaded"
    />

    <img
      v-else
      :src="imageSrc"
      :alt="sign.name"
      loading="lazy"
      class="absolute inset-0 block h-full w-full object-contain transition-opacity duration-300 ease-in-out"
      :class="isLoaded ? 'opacity-100' : 'opacity-0'"
      @load="markLoaded"
    />
  </div>
</template>

<style scoped>
.skeleton-fade-leave-active {
  transition: opacity 0.3s ease;
}

.skeleton-fade-leave-to {
  opacity: 0;
}
</style>
