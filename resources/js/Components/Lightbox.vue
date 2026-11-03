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
        <figcaption v-if="heading || description || meta.length" class="mt-3 max-w-3xl text-center">
          <p v-if="heading" class="text-white font-semibold">{{ heading }}</p>
          <p v-if="description" class="text-gray-200 text-sm mt-1">{{ description }}</p>
          <p v-if="meta.length" class="text-gray-400 text-xs mt-1">
            <template v-for="(item, i) in meta" :key="i">
              <span v-if="i" aria-hidden="true"> · </span>
              <a v-if="item.href" :href="item.href" class="underline hover:text-white">{{ item.text }}</a>
              <span v-else>{{ item.text }}</span>
            </template>
          </p>
        </figcaption>
        <p class="text-gray-400 text-xs mt-2">{{ index + 1 }} / {{ images.length }}</p>
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
  images: { type: Array, default: () => [] }, // [{ image_url, alt_text, title?, event_label?, taken_on?, album? }]
  // Link the album name to its gallery page (off when already viewing that album)
  linkAlbums: { type: Boolean, default: true },
})

const index = ref(null)
const dialog = ref(null)
const closeBtn = ref(null)
let returnFocus = null

const current = computed(() => (index.value === null ? null : props.images[index.value]))
// Older uploads stored the filename as the title or description; never show those
const isFilename = (text) => /\.(jpe?g|png|gif|webp)$/i.test(text || '')
const title = computed(() => (isFilename(current.value?.title) ? '' : current.value?.title || ''))
const alt = computed(() => (isFilename(current.value?.alt_text) ? '' : current.value?.alt_text || ''))
// Caption as the heading with the description under it; a photo with only a description shows that alone
const heading = computed(() => title.value || alt.value)
const description = computed(() => (title.value && alt.value !== title.value ? alt.value : ''))
const meta = computed(() => {
  const img = current.value
  if (!img) return []
  const items = []
  if (img.event_label) items.push({ text: img.event_label })
  if (img.taken_on) items.push({ text: formatDate(img.taken_on) })
  if (img.album?.name && img.album.name !== img.event_label) {
    items.push({ text: img.album.name, href: props.linkAlbums && img.album.slug ? `/gallery/${img.album.slug}` : null })
  }
  return items
})
const formatDate = (iso) => new Date(`${iso}T00:00:00`).toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' })

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
  const focusable = dialog.value?.querySelectorAll('button, a[href]')
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
