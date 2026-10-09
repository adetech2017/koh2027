<template>
  <AdminLayout>
    <div class="space-y-6">
      <div>
        <Link href="/admin/gallery" class="inline-flex items-center gap-1 text-sm text-primary hover:text-primary-dark font-medium">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
          Gallery
        </Link>
        <h1 class="text-2xl font-bold text-dark mt-2">Upload photos</h1>
      </div>

      <div class="bg-white rounded-lg shadow p-6 flex flex-col md:flex-row gap-8">
        <GalleryCategoryPicker v-model="categoryId" :categories="categories" />
        <div class="flex-grow min-w-0">
          <GalleryUploader
            :category-id="categoryId"
            :max-upload-bytes="maxUploadBytes"
            :max-upload-files="maxUploadFiles"
          />
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import GalleryCategoryPicker from '@/Components/GalleryCategoryPicker.vue'
import GalleryUploader from '@/Components/GalleryUploader.vue'

const props = defineProps({
  categories: { type: Array, default: () => [] },
  selectedCategoryId: { type: [Number, String], default: null },
  maxUploadBytes: { type: Number, default: 8 * 1024 * 1024 },
  maxUploadFiles: { type: Number, default: 20 },
})

const categoryId = ref(props.selectedCategoryId)
</script>
