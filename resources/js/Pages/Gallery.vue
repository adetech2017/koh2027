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
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="mb-8">
          <label class="block text-dark font-medium mb-4">Filter by Category</label>
          <div class="flex flex-wrap gap-2">
            <button @click="updateFilter(null)" :class="['px-4 py-2 rounded transition-colors', !filters.category ? 'btn-primary' : 'border border-light-gray text-body hover:border-primary']">All</button>
            <button v-for="cat in categories" :key="cat" @click="updateFilter(cat)" :class="['px-4 py-2 rounded transition-colors', filters.category === cat ? 'btn-primary' : 'border border-light-gray text-body hover:border-primary']">{{ cat }}</button>
          </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-12 items-start">
          <div v-for="image in images.data" :key="image.id" class="bg-light-gray rounded-lg overflow-hidden cursor-pointer group" @click="openLightbox(image)">
            <img
              v-if="image.image_url"
              :src="image.image_url"
              :alt="image.alt_text"
              class="w-full h-auto block group-hover:scale-110 transition-transform"
            />
          </div>
        </div>
        <Pagination :links="images.links" />
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Pagination from '@/Components/Pagination.vue'

defineProps({
  images: Object,
  filters: Object,
  categories: Array,
})

const updateFilter = (category) => {
  router.visit('/gallery', {
    method: 'get',
    data: category ? { category } : {},
    preserveScroll: true,
  })
}

const openLightbox = (image) => {
  // Lightbox can be implemented with a modal component
}
</script>
