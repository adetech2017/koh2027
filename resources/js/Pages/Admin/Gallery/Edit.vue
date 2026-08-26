<template>
  <AdminLayout>
    <div class="w-full">
      <div class="mb-6">
        <Link href="/admin/gallery" class="text-primary hover:text-primary-dark mb-4 inline-block">
          ← Back to Gallery
        </Link>
        <h2 class="text-2xl font-bold text-dark">Gallery: {{ image.category?.name }}</h2>
      </div>

      <form @submit.prevent="submitForm" class="bg-white rounded-lg shadow p-6 flex flex-col md:flex-row gap-8">
        <GalleryCategoryPicker v-model="form.category_id" :categories="categories" />

        <div class="flex-grow space-y-8 min-w-0">
          <!-- Dropzone -->
          <div
            @drop.prevent="handleDrop"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            :class="['border-2 border-dashed rounded-lg p-12 text-center transition-colors', isDragging ? 'border-primary bg-blue-50' : 'border-gray-300 bg-gray-50']"
          >
            <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3v-6" />
            </svg>
            <p class="text-sm font-medium text-dark mb-1">Drag images here or click to select</p>
            <p class="text-xs text-gray-500 mb-4">JPEG, PNG, GIF, WebP (Max 2MB each)</p>
            <input
              ref="fileInput"
              type="file"
              multiple
              accept="image/*"
              @change="handleFileSelect"
              class="hidden"
            />
            <button
              type="button"
              @click="$refs.fileInput.click()"
              class="px-4 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium transition-colors text-sm"
            >
              Select Images
            </button>
          </div>

          <!-- New Images Preview -->
          <div v-if="newPreviews.length > 0">
            <h3 class="text-lg font-semibold text-dark mb-4">New Images ({{ newPreviews.length }})</h3>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
              <div v-for="(preview, index) in newPreviews" :key="`new-${index}`" class="relative group">
                <img :src="preview.url" :alt="preview.file.name" class="w-full aspect-square object-cover rounded-lg shadow" />
                <button
                  type="button"
                  @click="removeNewImage(index)"
                  class="absolute top-2 right-2 p-1 bg-red-600 hover:bg-red-700 text-white rounded opacity-0 group-hover:opacity-100 transition-opacity"
                  title="Delete"
                >
                  ✕
                </button>
              </div>
            </div>
          </div>

          <!-- Existing Images -->
          <div v-if="galleryImages.length > 0">
            <h3 class="text-lg font-semibold text-dark mb-4">Gallery Images ({{ galleryImages.length }})</h3>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
              <div v-for="img in galleryImages" :key="img.id" class="relative group">
                <img
                  v-if="!failedImages.has(img.id)"
                  :src="getImageUrl(img.image_path)"
                  :alt="img.title"
                  class="w-full aspect-square object-cover rounded-lg shadow"
                  @error="failedImages.add(img.id)"
                />
                <div v-else class="w-full aspect-square rounded-lg shadow bg-gray-100 flex items-center justify-center text-gray-400 text-xs text-center px-2">
                  Image unavailable
                </div>
                <button
                  type="button"
                  @click="deleteImage(img.id)"
                  class="absolute top-2 right-2 p-1 bg-red-600 hover:bg-red-700 text-white rounded opacity-0 group-hover:opacity-100 transition-opacity"
                  title="Delete"
                >
                  🗑️
                </button>
              </div>
            </div>
          </div>

          <p v-if="form.errors.category_id" class="text-red-600 text-sm">{{ form.errors.category_id }}</p>

          <!-- Buttons -->
          <div class="flex gap-4 pt-4 border-t">
            <button
              type="submit"
              :disabled="form.processing || !form.category_id"
              class="px-6 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
            >
              <span v-if="form.processing" class="inline-block animate-spin">⟳</span>
              {{ form.processing ? 'Saving...' : 'Save Changes' }}
            </button>
            <Link
              href="/admin/gallery"
              class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-dark rounded-lg font-medium transition-colors"
            >
              Cancel
            </Link>
          </div>
        </div>
      </form>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { usePage, useForm, Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import GalleryCategoryPicker from '@/Components/GalleryCategoryPicker.vue'

defineProps({
  categories: {
    type: Array,
    default: () => [],
  },
})

const page = usePage()
const fileInput = ref(null)
const isDragging = ref(false)
const newPreviews = ref([])
const failedImages = ref(new Set())

const image = computed(() => page.props.image || {})
const galleryImages = computed(() => page.props.galleryImages || [])

const form = useForm({
  images: [],
  category_id: null,
})

watch(image, (newImage) => {
  if (newImage && newImage.id) {
    form.category_id = newImage.category_id || null
  }
}, { immediate: true, deep: true })

const handleDrop = (e) => {
  isDragging.value = false
  const files = Array.from(e.dataTransfer.files)
  processFiles(files)
}

const handleFileSelect = (e) => {
  const files = Array.from(e.target.files)
  processFiles(files)
}

const processFiles = (files) => {
  files.forEach(file => {
    if (file.type.startsWith('image/')) {
      form.images.push(file)
      const reader = new FileReader()
      reader.onload = (event) => {
        newPreviews.value.push({
          url: event.target.result,
          file: file
        })
      }
      reader.readAsDataURL(file)
    }
  })
}

const removeNewImage = (index) => {
  form.images.splice(index, 1)
  newPreviews.value.splice(index, 1)
}

const getImageUrl = (path) => {
  if (!path) return ''
  return `/storage/${path}`
}

const deleteImage = (id) => {
  if (confirm('Delete this image?')) {
    router.delete(`/admin/gallery/${id}`)
  }
}

const submitForm = () => {
  console.log('[gallery-edit] submitting', {
    category_id: form.category_id,
    images_count: form.images.length,
    image_id: image.value.id,
  })
  form.put(`/admin/gallery/${image.value.id}`, {
    onError: (errors) => console.log('[gallery-edit] errors', errors),
  })
}
</script>
