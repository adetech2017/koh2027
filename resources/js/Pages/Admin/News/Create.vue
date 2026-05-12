<template>
  <AdminLayout>
    <div class="w-full">
      <div class="mb-6">
        <Link href="/admin/news" class="text-primary hover:text-primary-dark mb-4 inline-block">
          ← Back to News
        </Link>
        <h2 class="text-2xl font-bold text-dark">Create News Article</h2>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <form @submit.prevent="submit" class="grid grid-cols-12 gap-8">
          <!-- Left Column: Content -->
          <div class="col-span-12 lg:col-span-8 space-y-6">
            <div>
              <label class="block text-sm font-medium text-dark mb-2">Title</label>
              <input
                v-model="data.title"
                type="text"
                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                placeholder="Article title"
              />
              <p v-if="errors.title" class="text-red-600 text-sm mt-1">{{ errors.title }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-dark mb-2">Category</label>
              <input
                v-model="data.category"
                type="text"
                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                placeholder="e.g., Updates, Events, Announcements"
              />
              <p v-if="errors.category" class="text-red-600 text-sm mt-1">{{ errors.category }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-dark mb-2">Content</label>
              <Editor v-model="data.body" />
              <p v-if="errors.body" class="text-red-600 text-sm mt-1">{{ errors.body }}</p>
            </div>
          </div>

          <!-- Right Column: Image -->
          <div class="col-span-12 lg:col-span-4">
            <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 bg-gray-50 text-center h-full flex flex-col justify-center sticky top-6">
              <div v-if="!preview" class="py-8">
                <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <label class="mt-6 block">
                  <span class="block text-sm font-semibold text-dark mb-1">Featured Image</span>
                  <span class="text-xs text-gray-500 block mb-4">JPEG, PNG, GIF, WebP (Max 2MB)</span>
                  <input
                    ref="imageInput"
                    type="file"
                    accept="image/*"
                    @change="selectImage"
                    class="hidden"
                  />
                  <span class="inline-block px-6 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium cursor-pointer transition-colors text-sm">
                    Choose Image
                  </span>
                </label>
                <p v-if="errors.image_path" class="text-red-600 text-sm mt-4">{{ errors.image_path }}</p>
              </div>

              <div v-else class="py-4">
                <img :src="preview" alt="Preview" class="w-full h-80 object-cover rounded-lg shadow-lg mb-4" />
                <label class="inline-block">
                  <input
                    ref="imageInput"
                    type="file"
                    accept="image/*"
                    @change="selectImage"
                    class="hidden"
                  />
                  <span class="inline-block px-4 py-2 bg-gray-200 hover:bg-gray-300 text-dark rounded-lg font-medium cursor-pointer transition-colors text-sm">
                    Change Image
                  </span>
                </label>
              </div>
            </div>
          </div>

          <!-- Buttons -->
          <div class="col-span-12 flex gap-4 pt-6 border-t">
            <button
              type="submit"
              :disabled="loading"
              class="px-6 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ loading ? 'Creating...' : 'Create Article' }}
            </button>
            <Link href="/admin/news" class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-dark rounded-lg font-medium transition-colors">
              Cancel
            </Link>
          </div>
        </form>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { usePage, router, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Editor from '@/Components/Editor.vue'

const page = usePage()
const imageInput = ref(null)
const preview = ref(null)
const loading = ref(false)
const errors = reactive({})

const data = reactive({
  title: '',
  category: '',
  body: '',
  image: null,
})

const selectImage = (e) => {
  const file = e.target.files[0]
  if (file) {
    data.image = file
    const reader = new FileReader()
    reader.onload = (event) => {
      preview.value = event.target.result
    }
    reader.readAsDataURL(file)
  }
}

const submit = () => {
  if (!data.title || !data.category || !data.body) {
    alert('Please fill in all fields (title, category, content)')
    return
  }

  if (!data.image) {
    alert('Please select an image')
    return
  }

  loading.value = true
  const form = new FormData()
  form.append('title', data.title)
  form.append('category', data.category)
  form.append('body', data.body)
  form.append('image_path', data.image)

  router.post('/admin/news', form)
}
</script>
