<template>
  <Link :href="`/news/${article.slug}`" class="group">
    <div class="bg-white rounded-lg overflow-hidden shadow-card hover:shadow-card-hover transition-all h-full flex flex-col">
      <div class="relative h-48 bg-light-gray overflow-hidden">
        <img
          v-if="article.image_url && !imageFailed"
          :src="article.image_url"
          :alt="article.image_alt"
          class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300"
          @error="imageFailed = true"
        />
        <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
          <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
        </div>
        <div class="absolute top-3 right-3 bg-primary text-white px-3 py-1 rounded-full text-xs font-semibold capitalize">
          {{ article.category }}
        </div>
      </div>
      <div class="p-4 flex-grow flex flex-col">
        <p class="text-xs text-body mb-2">{{ formatDate(article.published_at) }}</p>
        <h3 class="text-lg font-bold text-dark mb-2 group-hover:text-primary transition-colors line-clamp-2 flex-grow">{{ article.title }}</h3>
        <p class="text-body text-sm line-clamp-2 mb-3">{{ article.excerpt }}</p>
        <span class="inline-block text-primary font-semibold text-sm group-hover:translate-x-1 transition-transform">
          Read more →
        </span>
      </div>
    </div>
  </Link>
</template>

<script setup>
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'

defineProps({
  article: {
    type: Object,
    required: true,
  },
})

const imageFailed = ref(false)

const formatDate = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}
</script>
