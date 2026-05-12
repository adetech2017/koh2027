<template>
  <AdminLayout>
    <template #dialogs>
      <ConfirmDialog ref="confirmDialog" />
    </template>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold text-dark">Platform Pillars</h2>
        <button
          @click="createNew"
          class="px-6 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium transition-colors"
        >
          Add Pillar
        </button>
      </div>

      <!-- Pillars Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div v-for="pillar in pillars" :key="pillar.id" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
          <div class="flex items-start justify-between mb-3">
            <div>
              <h3 class="text-lg font-semibold text-dark">{{ pillar.title }}</h3>
              <p class="text-sm text-gray-600 mt-1">Order: {{ pillar.order }}</p>
            </div>
            <div v-if="pillar.icon_url" class="w-12 h-12 flex-shrink-0">
              <img :src="pillar.icon_url" :alt="pillar.title" class="w-full h-full object-contain" />
            </div>
          </div>
          <p class="text-sm text-gray-600 mb-4 line-clamp-2">{{ pillar.description }}</p>
          <div class="flex gap-2 pt-4 border-t">
            <button
              @click="editPillar(pillar.id)"
              class="flex-1 px-3 py-2 text-sm bg-blue-100 hover:bg-blue-200 text-blue-800 rounded transition-colors"
            >
              Edit
            </button>
            <button
              @click="deletePillar(pillar.id)"
              class="flex-1 px-3 py-2 text-sm bg-red-100 hover:bg-red-200 text-red-800 rounded transition-colors"
            >
              Delete
            </button>
          </div>
        </div>
      </div>

      <div v-if="pillars.length === 0" class="text-center py-12">
        <p class="text-gray-500">No platform pillars yet.</p>
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

const pillars = computed(() => {
  const data = page.props.pillars
  if (!data) return []
  return Array.isArray(data) ? data : (data.data || [])
})

const createNew = () => {
  router.visit('/admin/platform-pillars/create')
}

const editPillar = (id) => {
  router.visit(`/admin/platform-pillars/${id}/edit`)
}

const deletePillar = async (id) => {
  const confirmed = await confirmDialog.value.open(
    'Delete Pillar',
    'Are you sure you want to delete this platform pillar?',
    { confirmText: 'Delete', isDangerous: true }
  )
  if (confirmed) {
    router.delete(`/admin/platform-pillars/${id}`)
  }
}
</script>
