<template>
  <div class="flex items-center gap-3 flex-wrap">
    <span class="text-sm font-semibold text-body">Share:</span>

    <!-- Facebook -->
    <a
      :href="facebookUrl"
      target="_blank"
      rel="noopener"
      class="inline-flex items-center gap-2 px-3 py-2 rounded bg-blue-600 text-white text-sm hover:bg-blue-700 transition-colors"
      title="Share on Facebook"
    >
      <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
      <span class="hidden sm:inline">Facebook</span>
    </a>

    <!-- X/Twitter -->
    <a
      :href="twitterUrl"
      target="_blank"
      rel="noopener"
      class="inline-flex items-center gap-2 px-3 py-2 rounded bg-black text-white text-sm hover:bg-gray-800 transition-colors"
      title="Share on X"
    >
      <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24h-6.6l-5.17-6.763-5.91 6.763h-3.308l7.73-8.835L.424 2.25h6.7l4.67 6.168L17.67 2.25h.574zm-1.106 17.92h1.828L5.283 4.126H3.283l13.855 16.044z"/></svg>
      <span class="hidden sm:inline">X</span>
    </a>

    <!-- LinkedIn -->
    <a
      :href="linkedinUrl"
      target="_blank"
      rel="noopener"
      class="inline-flex items-center gap-2 px-3 py-2 rounded bg-blue-700 text-white text-sm hover:bg-blue-800 transition-colors"
      title="Share on LinkedIn"
    >
      <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.475-2.236-1.986-2.236-1.081 0-1.722.722-2.004 1.418-.103.249-.129.597-.129.946v5.441h-3.554s.043-8.789 0-9.707h3.554v1.375c.427-.659 1.191-1.595 2.897-1.595 2.117 0 3.704 1.385 3.704 4.362v5.565zM5.337 8.855c-1.144 0-1.915-.762-1.915-1.715 0-.955.77-1.715 1.914-1.715.944 0 1.915.76 1.915 1.715 0 .953-.971 1.715-1.914 1.715zm1.6 11.597H3.738V9.645h3.199v10.807zM22.224 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.224 0z"/></svg>
      <span class="hidden sm:inline">LinkedIn</span>
    </a>

    <!-- WhatsApp -->
    <a
      :href="whatsappUrl"
      target="_blank"
      rel="noopener"
      class="inline-flex items-center gap-2 px-3 py-2 rounded bg-green-500 text-white text-sm hover:bg-green-600 transition-colors"
      title="Share on WhatsApp"
    >
      <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.447-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.67-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.076 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421-7.403h-.004a6.963 6.963 0 00-7.145 6.944c0 1.488.372 2.897 1.088 4.144L3.821 21.2l4.415-1.158a6.924 6.924 0 004.218 1.345h.005c3.862 0 7.01-3.141 7.01-7.006 0-1.872-.727-3.629-2.048-4.95a7.029 7.029 0 00-4.981-2.051M12.04 2C6.517 2 2.13 6.357 2.13 11.898c0 2.096.547 4.142 1.595 5.945L2 22l5.287-1.387a7.947 7.947 0 003.753.906h.005C17.523 21.519 21.91 17.163 21.91 11.622 21.91 6.357 17.523 2 12.04 2"/></svg>
      <span class="hidden sm:inline">WhatsApp</span>
    </a>

    <!-- Copy Link -->
    <button
      @click="copyToClipboard"
      class="inline-flex items-center gap-2 px-3 py-2 rounded bg-gray-600 text-white text-sm hover:bg-gray-700 transition-colors"
      :title="copyButtonTitle"
    >
      <svg v-if="!copied" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
      <svg v-else class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
      <span class="hidden sm:inline">{{ copied ? 'Copied!' : 'Copy' }}</span>
    </button>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  url: String,
  title: String,
})

const copied = ref(false)

const currentUrl = computed(() =>
  props.url || (typeof window !== 'undefined' ? window.location.href : '')
)

const encodedUrl = computed(() =>
  encodeURIComponent(currentUrl.value || '')
)

const encodedText = computed(() => encodeURIComponent(props.title || 'Check this out'))

const facebookUrl = computed(() => `https://www.facebook.com/sharer/sharer.php?u=${encodedUrl.value}`)
const twitterUrl = computed(() => `https://x.com/intent/tweet?url=${encodedUrl.value}&text=${encodedText.value}`)
const linkedinUrl = computed(() => `https://www.linkedin.com/sharing/share-offsite/?url=${encodedUrl.value}`)
const whatsappUrl = computed(() => `https://wa.me/?text=${encodedText.value} ${encodedUrl.value}`)

const copyButtonTitle = 'Copy link to clipboard'

const copyToClipboard = async () => {
  try {
    await navigator.clipboard.writeText(currentUrl.value)
    copied.value = true
    setTimeout(() => {
      copied.value = false
    }, 2000)
  } catch (err) {
    console.error('Failed to copy:', err)
  }
}
</script>
