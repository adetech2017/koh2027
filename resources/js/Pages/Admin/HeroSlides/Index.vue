<template>
  <AdminLayout>
    <template #dialogs>
      <ConfirmDialog ref="confirmDialog" />
    </template>
    <div class="space-y-6">
      <!-- Header with Create Button -->
      <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold text-dark">Hero Slider</h2>
        <button
          @click="createNew"
          class="px-6 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium transition-colors"
        >
          Add Slide
        </button>
      </div>

      <!-- Slides Grid -->
      <div v-if="slides && slides.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div v-for="slide in slides" :key="slide.id" class="bg-white rounded-lg shadow overflow-hidden hover:shadow-lg transition-shadow">
          <div class="relative h-48 bg-gray-200 overflow-hidden">
            <img
              v-if="slide.image_url"
              :src="slide.image_url"
              :alt="slide.title"
              class="w-full h-full object-cover"
            />
            <div class="absolute top-2 right-2 flex gap-2">
              <button
                @click="editSlide(slide.id)"
                class="p-2 bg-blue-600 hover:bg-blue-700 text-white rounded transition-colors"
                title="Edit"
              >
                ✏️
              </button>
              <button
                @click="deleteSlide(slide.id)"
                class="p-2 bg-red-600 hover:bg-red-700 text-white rounded transition-colors"
                title="Delete"
              >
                🗑️
              </button>
            </div>
          </div>
          <div class="p-4">
            <h3 class="font-semibold text-dark truncate">{{ slide.title }}</h3>
            <p class="text-sm text-gray-600 truncate">{{ slide.subtitle }}</p>
            <div class="mt-3 pt-3 border-t flex justify-between items-center">
              <span v-if="slide.is_active" class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded">Active</span>
              <span v-else class="text-xs bg-gray-100 text-gray-800 px-2 py-1 rounded">Inactive</span>
            </div>
          </div>
        </div>
      </div>

      <div v-if="slides.length === 0" class="text-center py-12">
        <p class="text-gray-500">No hero slides yet. Create one to get started.</p>
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

const slides = computed(() => {
  const data = page.props.slides
  if (!data) return []
  return Array.isArray(data) ? data : (data.data || [])
})

const createNew = () => {
  router.visit('/admin/hero-slides/create')
}

const editSlide = (id) => {
  router.visit(`/admin/hero-slides/${id}/edit`)
}

const deleteSlide = async (id) => {
  const confirmed = await confirmDialog.value.open(
    'Delete Slide',
    'Are you sure you want to delete this hero slide? This action cannot be undone.',
    { confirmText: 'Delete', isDangerous: true }
  )
  if (confirmed) {
    router.delete(`/admin/hero-slides/${id}`)
  }
}
</script>
