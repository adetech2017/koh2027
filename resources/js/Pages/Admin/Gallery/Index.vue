<template>
  <AdminLayout>
    <template #dialogs>
      <ConfirmDialog ref="confirmDialog" />
    </template>
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

      <!-- Images Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div v-for="image in images" :key="image.id" class="bg-white rounded-lg shadow overflow-hidden hover:shadow-lg transition-shadow">
          <!-- Image Container with square aspect ratio -->
          <div class="w-full aspect-square bg-gray-100 overflow-hidden flex items-center justify-center">
            <img
              :src="getImageUrl(image.image_path)"
              :alt="image.title"
              class="w-full h-full object-cover"
              loading="lazy"
            />
          </div>

          <!-- Card Content -->
          <div class="p-3 space-y-2">
            <h3 class="font-semibold text-sm text-dark line-clamp-2">{{ image.title }}</h3>
            <div class="flex justify-between items-center text-xs text-gray-500">
              <span>{{ formatDate(image.created_at) }}</span>
              <div class="flex gap-1">
                <button
                  @click="editImage(image.id)"
                  class="p-1 text-blue-600 hover:text-blue-700 hover:bg-blue-50 rounded"
                  title="Edit"
                >
                  ✏️
                </button>
                <button
                  @click="deleteImage(image.id)"
                  class="p-1 text-red-600 hover:text-red-700 hover:bg-red-50 rounded"
                  title="Delete"
                >
                  🗑️
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div v-if="images.length === 0" class="text-center py-12">
        <p class="text-gray-500">No gallery images yet.</p>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed, ref } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'

const page = usePage()
const confirmDialog = ref(null)

const images = computed(() => {
  const data = page.props.images
  if (!data) return []
  return Array.isArray(data) ? data : (data.data || [])
})

const getImageUrl = (path) => {
  if (!path) return ''
  return `/storage/${path}`
}

const createNew = () => {
  router.visit('/admin/gallery/create')
}

const editImage = (id) => {
  router.visit(`/admin/gallery/${id}/edit`)
}

const deleteImage = async (id) => {
  const confirmed = await confirmDialog.value.open(
    'Delete Image',
    'Are you sure you want to delete this gallery image?',
    { confirmText: 'Delete', isDangerous: true }
  )
  if (confirmed) {
    router.delete(`/admin/gallery/${id}`)
  }
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString()
}
</script>
