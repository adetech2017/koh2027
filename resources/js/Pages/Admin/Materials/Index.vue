<template>
  <AdminLayout>
    <template #dialogs>
      <ConfirmDialog ref="confirmDialog" />
    </template>
    <div class="space-y-6">
      <!-- Header with Create Button -->
      <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold text-dark">Manifesto</h2>
        <button
          @click="createNew"
          class="px-6 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium transition-colors"
        >
          Add Material
        </button>
      </div>

      <!-- Materials Grid -->
      <div v-if="materials.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div v-for="material in materials" :key="material.id" class="bg-white rounded-lg shadow overflow-hidden hover:shadow-lg transition-shadow">
          <div class="relative h-40 bg-gray-100 overflow-hidden flex items-center justify-center">
            <img
              v-if="material.thumbnail_url"
              :src="material.thumbnail_url"
              :alt="material.title"
              class="w-full h-full object-cover"
            />
            <span v-else class="text-5xl">📄</span>
            <div class="absolute top-2 right-2 flex gap-2">
              <button
                @click="editMaterial(material.id)"
                class="p-2 bg-blue-600 hover:bg-blue-700 text-white rounded transition-colors"
                title="Edit"
              >
                ✏️
              </button>
              <button
                @click="deleteMaterial(material.id)"
                class="p-2 bg-red-600 hover:bg-red-700 text-white rounded transition-colors"
                title="Delete"
              >
                🗑️
              </button>
            </div>
          </div>
          <div class="p-4">
            <h3 class="font-semibold text-dark truncate">{{ material.title }}</h3>
            <p class="text-sm text-gray-600 truncate">{{ material.description }}</p>
            <div class="mt-3 pt-3 border-t flex justify-between items-center text-xs text-gray-500">
              <span class="capitalize bg-gray-100 px-2 py-1 rounded">{{ material.category }}</span>
              <span>{{ material.file_type?.toUpperCase() }} · {{ formatSize(material.file_size) }}</span>
            </div>
            <div class="mt-2 flex justify-between items-center">
              <span v-if="material.is_active" class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded">Active</span>
              <span v-else class="text-xs bg-gray-100 text-gray-800 px-2 py-1 rounded">Inactive</span>
              <span class="text-xs text-gray-500">{{ material.download_count }} downloads</span>
            </div>
          </div>
        </div>
      </div>

      <div v-if="materials.length === 0" class="text-center py-12">
        <p class="text-gray-500">No materials yet. Add one to get started.</p>
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

const materials = computed(() => {
  const data = page.props.materials
  if (!data) return []
  return Array.isArray(data) ? data : (data.data || [])
})

const formatSize = (bytes) => {
  if (!bytes) return ''
  const kb = bytes / 1024
  return kb < 1024 ? `${Math.round(kb * 10) / 10} KB` : `${Math.round((kb / 1024) * 10) / 10} MB`
}

const createNew = () => {
  router.visit('/admin/materials/create')
}

const editMaterial = (id) => {
  router.visit(`/admin/materials/${id}/edit`)
}

const deleteMaterial = async (id) => {
  const confirmed = await confirmDialog.value.open(
    'Delete Material',
    'Are you sure you want to delete this material? This action cannot be undone.',
    { confirmText: 'Delete', isDangerous: true }
  )
  if (confirmed) {
    router.delete(`/admin/materials/${id}`)
  }
}
</script>
