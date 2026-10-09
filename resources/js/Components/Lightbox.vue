<template>
  <Teleport to="body">
    <div
      v-if="index !== null && current"
      ref="dialog"
      class="fixed inset-0 z-[60] bg-black/90 flex items-center justify-center"
      role="dialog"
      aria-modal="true"
      :aria-label="`Photo ${index + 1} of ${images.length}`"
      tabindex="-1"
      @click.self="close"
      @keydown.esc="close"
      @keydown.left="step(-1)"
      @keydown.right="step(1)"
      @keydown.tab="trapFocus"
      @touchstart.passive="touchStart"
      @touchend.passive="touchEnd"
    >
      <figure class="max-w-6xl w-full px-4 sm:px-16 flex flex-col items-center">
        <img :src="current.image_url" :alt="current.alt_text || ''" class="max-h-[80vh] max-w-full object-contain rounded" />
        <figcaption v-if="caption" class="text-gray-200 text-sm mt-3 text-center">{{ caption }}</figcaption>
        <p class="text-gray-400 text-xs mt-1">{{ index + 1 }} / {{ images.length }}</p>
      </figure>

      <button ref="closeBtn" type="button" class="absolute top-4 right-4 w-11 h-11 rounded-full bg-white/10 hover:bg-white/20 text-white text-2xl leading-none" aria-label="Close" @click="close">×</button>
      <template v-if="images.length > 1">
        <button type="button" class="absolute left-2 sm:left-4 top-1/2 -translate-y-1/2 w-11 h-11 rounded-full bg-white/10 hover:bg-white/20 text-white text-xl" aria-label="Previous photo" @click="step(-1)">‹</button>
        <button type="button" class="absolute right-2 sm:right-4 top-1/2 -translate-y-1/2 w-11 h-11 rounded-full bg-white/10 hover:bg-white/20 text-white text-xl" aria-label="Next photo" @click="step(1)">›</button>
      </template>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, nextTick, ref, watch, onBeforeUnmount } from 'vue'

const props = defineProps({
  images: { type: Array, default: () => [] }, // [{ image_url, alt_text, title?, event_label? }]
})

const index = ref(null)
const dialog = ref(null)
const closeBtn = ref(null)
let returnFocus = null

const current = computed(() => (index.value === null ? null : props.images[index.value]))
// Older uploads stored the filename as the title; never show that as a caption
const caption = computed(() => {
  const title = /\.(jpe?g|png|gif|webp)$/i.test(current.value?.title || '') ? null : current.value?.title
  return [title, current.value?.event_label].filter(Boolean).join(' · ')
})

const open = (i) => {
  returnFocus = document.activeElement
  index.value = i
}
const close = () => { index.value = null }
const step = (delta) => {
  if (index.value === null) return
  index.value = (index.value + delta + props.images.length) % props.images.length
}

// Keep focus inside the dialog
const trapFocus = (e) => {
  const focusable = dialog.value?.querySelectorAll('button')
  if (!focusable?.length) return
  const first = focusable[0]
  const last = focusable[focusable.length - 1]
  if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus() }
  else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus() }
}

let touchX = null
const touchStart = (e) => { touchX = e.touches[0]?.clientX ?? null }
const touchEnd = (e) => {
  if (touchX === null) return
  const dx = (e.changedTouches[0]?.clientX ?? touchX) - touchX
  if (Math.abs(dx) > 50) step(dx < 0 ? 1 : -1)
  touchX = null
}

watch(index, async (value, previous) => {
  document.body.style.overflow = value === null ? '' : 'hidden'
  if (value !== null && previous === null) {
    await nextTick()
    closeBtn.value?.focus()
  } else if (value === null) {
    returnFocus?.focus?.()
  }
})

onBeforeUnmount(() => { document.body.style.overflow = '' })

defineExpose({ open })
</script>
