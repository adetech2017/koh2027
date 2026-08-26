<template>
  <AdminLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold text-dark">Gallery</h2>
        <button
          @click="createNew"
          class="px-6 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium transition-colors"
        >
          Add Image
        </button>
      </div>

      <!-- Category Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div
          v-for="category in categories"
          :key="category.id"
          @click="openCategory(category)"
          class="bg-white rounded-lg shadow overflow-hidden hover:shadow-lg transition-shadow cursor-pointer"
        >
          <div class="w-full aspect-square bg-gray-100 overflow-hidden flex items-center justify-center relative">
            <img
              v-if="category.gallery_images[0] && !failedImages.has(category.id)"
              :src="getImageUrl(category.gallery_images[0].image_path)"
              :alt="category.name"
              class="w-full h-full object-cover"
              loading="lazy"
              @error="failedImages.add(category.id)"
            />
            <span v-else class="text-gray-400 text-xs">No images yet</span>
            <span class="absolute top-2 right-2 bg-black/60 text-white text-xs px-2 py-0.5 rounded-full">
              {{ category.gallery_images.length }}
            </span>
          </div>
          <div class="p-3">
            <h3 class="font-semibold text-sm text-dark truncate">{{ category.name }}</h3>
            <p class="text-xs text-gray-500">
              {{ category.gallery_images.length }} image{{ category.gallery_images.length !== 1 ? 's' : '' }}
            </p>
          </div>
        </div>
      </div>

      <div v-if="categories.length === 0" class="text-center py-12">
        <p class="text-gray-500">No gallery images yet.</p>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed, ref } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const page = usePage()
const failedImages = ref(new Set())

const categories = computed(() => page.props.categories || [])

const getImageUrl = (path) => {
  if (!path) return ''
  return `/storage/${path}`
}

const createNew = () => {
  router.visit('/admin/gallery/create')
}

const openCategory = (category) => {
  if (category.gallery_images.length > 0) {
    router.visit(`/admin/gallery/${category.gallery_images[0].id}/edit`)
  } else {
    router.visit('/admin/gallery/create', { data: { category_id: category.id } })
  }
}
</script>
