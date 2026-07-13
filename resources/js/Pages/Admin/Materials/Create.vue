<template>
  <AdminLayout>
    <div class="w-full">
      <div class="mb-6">
        <Link href="/admin/materials" class="text-primary hover:text-primary-dark mb-4 inline-block">
          ← Back to Materials
        </Link>
        <h2 class="text-2xl font-bold text-dark">Add Material</h2>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <form @submit.prevent="submitForm" class="grid grid-cols-12 gap-8">
          <!-- Left Column: Details (6 cols) -->
          <div class="col-span-12 md:col-span-6 space-y-6">
            <h3 class="text-lg font-semibold text-dark">Details</h3>

            <!-- Title -->
            <div>
              <label class="block text-sm font-medium text-dark mb-2">Title</label>
              <input
                v-model="form.title"
                type="text"
                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                placeholder="Document title (max 200 chars)"
              />
              <p v-if="form.errors.title" class="text-red-600 text-sm mt-1">{{ form.errors.title }}</p>
            </div>

            <!-- Description -->
            <div>
              <label class="block text-sm font-medium text-dark mb-2">Description</label>
              <textarea
                v-model="form.description"
                rows="4"
                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                placeholder="What is this document about?"
              />
              <p v-if="form.errors.description" class="text-red-600 text-sm mt-1">{{ form.errors.description }}</p>
            </div>

            <!-- Category -->
            <div>
              <label class="block text-sm font-medium text-dark mb-2">Category</label>
              <input
                v-model="form.category"
                type="text"
                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                placeholder="e.g., manifesto, policy, brochure, faq"
              />
              <p v-if="form.errors.category" class="text-red-600 text-sm mt-1">{{ form.errors.category }}</p>
            </div>

            <!-- Active -->
            <label class="flex items-center gap-2 cursor-pointer mt-6">
              <input v-model="form.is_active" type="checkbox" class="w-4 h-4 rounded border-gray-300" />
              <span class="text-sm text-dark font-medium">Active</span>
            </label>
          </div>

          <!-- Right Column: Files (6 cols) -->
          <div class="col-span-12 md:col-span-6 space-y-6">
            <!-- File Upload -->
            <div>
              <h3 class="text-lg font-semibold text-dark mb-2">Document File</h3>
              <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 bg-gray-50 text-center">
                <div v-if="!fileName" class="py-4">
                  <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                  <label class="mt-4 block">
                    <span class="block text-sm font-semibold text-dark mb-1">Upload File</span>
                    <span class="text-xs text-gray-500 block mb-3">PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX, ZIP · Max 100MB</span>
                    <input
                      ref="fileInput"
                      type="file"
                      accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.zip"
                      @change="handleFileUpload"
                      class="hidden"
                    />
                    <span class="inline-block px-6 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium cursor-pointer transition-colors text-sm">
                      Choose File
                    </span>
                  </label>
                </div>
                <div v-else class="py-4">
                  <p class="text-sm font-medium text-dark truncate">{{ fileName }}</p>
                  <label class="inline-block mt-3">
                    <input
                      ref="fileInput"
                      type="file"
                      accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.zip"
                      @change="handleFileUpload"
                      class="hidden"
                    />
                    <span class="inline-block px-4 py-2 bg-gray-200 hover:bg-gray-300 text-dark rounded-lg font-medium cursor-pointer transition-colors text-sm">
                      Change File
                    </span>
                  </label>
                </div>
                <p v-if="form.errors.file_path" class="text-red-600 text-sm mt-3">{{ form.errors.file_path }}</p>
              </div>
            </div>

            <!-- Thumbnail Upload -->
            <div>
              <h3 class="text-lg font-semibold text-dark mb-2">Thumbnail (optional)</h3>
              <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 bg-gray-50 text-center">
                <div v-if="!thumbnailPreview" class="py-4">
                  <label class="block">
                    <span class="text-xs text-gray-500 block mb-3">JPEG, PNG, GIF, WebP · Max 2MB</span>
                    <input
                      ref="thumbnailInput"
                      type="file"
                      accept="image/*"
                      @change="handleThumbnailUpload"
                      class="hidden"
                    />
                    <span class="inline-block px-6 py-2 bg-gray-200 hover:bg-gray-300 text-dark rounded-lg font-medium cursor-pointer transition-colors text-sm">
                      Choose Image
                    </span>
                  </label>
                </div>
                <div v-else class="py-2">
                  <img :src="thumbnailPreview" alt="Thumbnail preview" class="w-full h-32 object-cover rounded-lg shadow mb-3" />
                  <label class="inline-block">
                    <input
                      ref="thumbnailInput"
                      type="file"
                      accept="image/*"
                      @change="handleThumbnailUpload"
                      class="hidden"
                    />
                    <span class="inline-block px-4 py-2 bg-gray-200 hover:bg-gray-300 text-dark rounded-lg font-medium cursor-pointer transition-colors text-sm">
                      Change Image
                    </span>
                  </label>
                </div>
                <p v-if="form.errors.thumbnail_path" class="text-red-600 text-sm mt-3">{{ form.errors.thumbnail_path }}</p>
              </div>
            </div>
          </div>

          <!-- Buttons (Full Width) -->
          <div class="col-span-12 flex gap-4 pt-6 border-t">
            <button
              type="submit"
              :disabled="form.processing"
              class="px-6 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
            >
              <span v-if="form.processing" class="inline-block animate-spin">⟳</span>
              {{ form.processing ? 'Creating...' : 'Create Material' }}
            </button>
            <Link
              href="/admin/materials"
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
const fileName = ref(null)
const thumbnailInput = ref(null)
const thumbnailPreview = ref(null)

const form = useForm({
  title: '',
  description: '',
  category: '',
  file_path: null,
  thumbnail_path: null,
  is_active: true,
})

const MAX_FILE_SIZE = 100 * 1024 * 1024
const MAX_THUMBNAIL_SIZE = 2 * 1024 * 1024

const handleFileUpload = (e) => {
  const file = e.target.files[0]
  if (!file) return
  if (file.size > MAX_FILE_SIZE) {
    form.errors.file_path = `File is too large (${(file.size / 1024 / 1024).toFixed(1)} MB). Maximum size is 100 MB.`
    e.target.value = ''
    return
  }
  form.errors.file_path = null
  form.file_path = file
  fileName.value = file.name
}

const handleThumbnailUpload = (e) => {
  const file = e.target.files[0]
  if (!file) return
  if (file.size > MAX_THUMBNAIL_SIZE) {
    form.errors.thumbnail_path = `Image is too large (${(file.size / 1024 / 1024).toFixed(1)} MB). Maximum size is 2 MB.`
    e.target.value = ''
    return
  }
  form.errors.thumbnail_path = null
  form.thumbnail_path = file
  const reader = new FileReader()
  reader.onload = (event) => {
    thumbnailPreview.value = event.target.result
  }
  reader.readAsDataURL(file)
}

const submitForm = () => {
  form.post('/admin/materials')
}
</script>
