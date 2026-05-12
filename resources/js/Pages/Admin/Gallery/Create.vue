<template>
  <AdminLayout>
    <div class="w-full">
      <div class="mb-6">
        <Link href="/admin/gallery" class="text-primary hover:text-primary-dark mb-4 inline-block">
          ← Back to Gallery
        </Link>
        <h2 class="text-2xl font-bold text-dark">Add Gallery Images</h2>
      </div>

      <div class="bg-white rounded-lg shadow p-6 space-y-8">
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

        <!-- Images Grid -->
        <div v-if="previews.length > 0">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-dark">Selected Images ({{ previews.length }})</h3>
          </div>
          <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            <div v-for="(preview, index) in previews" :key="index" class="relative group">
              <img :src="preview.url" :alt="preview.file.name" class="w-full aspect-square object-cover rounded-lg shadow" />
              <button
                type="button"
                @click="removeImage(index)"
                class="absolute top-2 right-2 p-1 bg-red-600 hover:bg-red-700 text-white rounded opacity-0 group-hover:opacity-100 transition-opacity"
                title="Delete"
              >
                🗑️
              </button>
            </div>
          </div>
        </div>

        <!-- Category Form -->
        <form @submit.prevent="submitForm" class="max-w-md space-y-6 border-t pt-8">
          <div>
            <label class="block text-sm font-medium text-dark mb-2">Category</label>
            <input
              v-model="form.category"
              type="text"
              class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
              placeholder="e.g., Event, Team, Activity"
            />
            <p v-if="form.errors.category" class="text-red-600 text-sm mt-1">{{ form.errors.category }}</p>
          </div>

          <!-- Buttons -->
          <div class="flex gap-4 pt-4 border-t">
            <button
              type="submit"
              :disabled="form.processing || previews.length === 0"
              class="px-6 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
            >
              <span v-if="form.processing" class="inline-block animate-spin">⟳</span>
              {{ form.processing ? 'Uploading...' : `Upload ${previews.length} Image${previews.length !== 1 ? 's' : ''}` }}
            </button>
            <Link
              href="/admin/gallery"
              class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-dark rounded-lg font-medium transition-colors"
            >
              Cancel
            </Link>
          </div>
        </form>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const fileInput = ref(null)
const isDragging = ref(false)
const previews = ref([])

const form = useForm({
  images: [],
  category: '',
})

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
        previews.value.push({
          url: event.target.result,
          file: file
        })
      }
      reader.readAsDataURL(file)
    }
  })
}

const removeImage = (index) => {
  form.images.splice(index, 1)
  previews.value.splice(index, 1)
}

const submitForm = () => {
  form.post('/admin/gallery')
}
</script>
