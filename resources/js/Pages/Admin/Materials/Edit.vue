<template>
  <AdminLayout>
    <div class="w-full">
      <div class="mb-6">
        <Link href="/admin/materials" class="text-primary hover:text-primary-dark mb-4 inline-block">
          ← Back to Materials
        </Link>
        <h2 class="text-2xl font-bold text-dark">Edit Material</h2>
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

            <!-- Stats -->
            <div class="text-sm text-gray-500 border-t pt-4">
              <p v-if="material.file_name">Current file: {{ material.file_name }}</p>
              <p>{{ material.download_count || 0 }} downloads</p>
            </div>

            <!-- Active -->
            <label class="flex items-center gap-2 cursor-pointer mt-2">
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
                <div class="py-4">
                  <p v-if="fileName" class="text-sm font-medium text-dark truncate mb-3">New file: {{ fileName }}</p>
                  <p v-else class="text-xs text-gray-500 mb-3">Leave empty to keep the current file</p>
                  <label class="inline-block">
                    <input
                      ref="fileInput"
                      type="file"
                      accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.zip"
                      @change="handleFileUpload"
                      class="hidden"
                    />
                    <span class="inline-block px-6 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium cursor-pointer transition-colors text-sm">
                      {{ fileName ? 'Change File' : 'Replace File' }}
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
              {{ form.processing ? 'Updating...' : 'Update Material' }}
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
import { ref, computed, watch } from 'vue'
import { usePage, useForm, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const page = usePage()
const fileInput = ref(null)
const fileName = ref(null)
const thumbnailInput = ref(null)
const thumbnailPreview = ref(null)

const material = computed(() => page.props.material || {})

const form = useForm({
  title: '',
  description: '',
  category: '',
  file_path: null,
  thumbnail_path: null,
  is_active: false,
})

watch(material, (newMaterial) => {
  if (newMaterial && newMaterial.id) {
    form.title = newMaterial.title || ''
    form.description = newMaterial.description || ''
    form.category = newMaterial.category || ''
    form.is_active = newMaterial.is_active || false

    if (newMaterial.thumbnail_url) {
      thumbnailPreview.value = newMaterial.thumbnail_url
    }
  }
}, { immediate: true, deep: true })

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
  form.put(`/admin/materials/${material.value.id}`, {
    onSuccess: () => {
      // Success - page redirects automatically
    },
  })
}
</script>
