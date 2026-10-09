<template>
  <AppLayout>
    <Head title="Gallery" />
    <div class="min-h-screen bg-white">
      <div class="bg-primary text-white py-12 md:py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <h1 class="text-4xl md:text-5xl font-bold mb-4">Campaign Gallery</h1>
          <p class="text-xl text-gray-200">Moments from the campaign trail</p>
        </div>
      </div>
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div v-if="categories.length > 1" class="mb-8">
          <p id="gallery-filter-label" class="text-dark font-medium mb-3">Filter by category</p>
          <div class="flex flex-wrap gap-2" role="group" aria-labelledby="gallery-filter-label">
            <button
              type="button"
              :aria-pressed="!filters.category"
              :class="chipClass(!filters.category)"
              @click="updateFilter(null)"
            >
              All <span class="opacity-70">{{ total }}</span>
            </button>
            <button
              v-for="cat in categories"
              :key="cat.name"
              type="button"
              :aria-pressed="filters.category === cat.name"
              :class="chipClass(filters.category === cat.name)"
              @click="updateFilter(cat.name)"
            >
              {{ cat.name }} <span class="opacity-70">{{ cat.count }}</span>
            </button>
          </div>
        </div>

        <!-- Even grid of square tiles (photos of different shapes used to leave ragged gaps) -->
        <ul v-if="images.data.length" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 md:gap-4 mb-12">
          <li v-for="(image, i) in images.data" :key="image.id">
            <button
              type="button"
              class="group block w-full aspect-square bg-light-gray rounded-lg overflow-hidden focus:outline-none focus-visible:ring-4 focus-visible:ring-gold"
              :aria-label="`View photo${image.alt_text ? ': ' + image.alt_text : ''}`"
              @click="lightbox.open(i)"
            >
              <img
                v-if="!failedImages.has(image.id)"
                :src="image.thumbnail_url || image.image_url"
                :alt="image.alt_text || ''"
                loading="lazy"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                @error="failedImages.add(image.id)"
              />
              <span v-else class="w-full h-full flex items-center justify-center text-gray-400 text-xs">Image unavailable</span>
            </button>
          </li>
        </ul>
        <div v-else class="text-center py-16">
          <p class="text-lg font-semibold text-dark mb-2">No photos here yet.</p>
          <button v-if="filters.category" type="button" class="text-primary font-semibold hover:underline" @click="updateFilter(null)">See all photos</button>
        </div>

        <Pagination v-if="images.last_page > 1" :links="images.links" />
        <Lightbox ref="lightbox" :images="images.data" />
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import Lightbox from '@/Components/Lightbox.vue'

defineProps({
  images: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
  categories: { type: Array, default: () => [] },
  total: { type: Number, default: 0 },
})

const failedImages = ref(new Set())
const lightbox = ref(null)

const chipClass = (active) => [
  'px-4 py-2 rounded-full text-sm font-medium transition-colors',
  active ? 'bg-primary text-white' : 'border border-gray-300 text-body hover:border-primary hover:text-primary',
]

const updateFilter = (category) => {
  router.get('/gallery', category ? { category } : {}, { preserveScroll: true, preserveState: true, replace: true })
}
</script>
