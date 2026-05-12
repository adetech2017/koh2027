<template>
  <AdminLayout>
    <div class="max-w-2xl">
      <div class="mb-6">
        <Link href="/admin/platform-pillars" class="text-primary hover:text-primary-dark mb-4 inline-block">
          ← Back to Platform Pillars
        </Link>
        <h2 class="text-2xl font-bold text-dark">Create Platform Pillar</h2>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <form @submit.prevent="submitForm" class="space-y-6">
          <!-- Title -->
          <div>
            <label class="block text-sm font-medium text-dark mb-2">Title</label>
            <input
              v-model="form.title"
              type="text"
              class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
              placeholder="Pillar title"
            />
            <p v-if="errors.title" class="text-red-600 text-sm mt-1">{{ errors.title }}</p>
          </div>

          <!-- Summary -->
          <div>
            <label class="block text-sm font-medium text-dark mb-2">Summary</label>
            <textarea
              v-model="form.summary"
              rows="3"
              class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
              placeholder="Short description (max 300 chars)"
            />
            <p v-if="errors.summary" class="text-red-600 text-sm mt-1">{{ errors.summary }}</p>
          </div>

          <!-- Body -->
          <div>
            <label class="block text-sm font-medium text-dark mb-2">Full Description</label>
            <textarea
              v-model="form.body"
              rows="6"
              class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary font-mono"
              placeholder="Detailed description"
            />
            <p v-if="errors.body" class="text-red-600 text-sm mt-1">{{ errors.body }}</p>
          </div>

          <!-- Icon -->
          <div>
            <label class="block text-sm font-medium text-dark mb-2">Icon</label>
            <input
              v-model="form.icon"
              type="text"
              class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
              placeholder="Icon name or emoji"
            />
            <p v-if="errors.icon" class="text-red-600 text-sm mt-1">{{ errors.icon }}</p>
          </div>

          <!-- Color -->
          <div>
            <label class="block text-sm font-medium text-dark mb-2">Color</label>
            <input
              v-model="form.color"
              type="text"
              class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
              placeholder="Hex color code or color name"
            />
            <p v-if="errors.color" class="text-red-600 text-sm mt-1">{{ errors.color }}</p>
          </div>

          <!-- Sort Order -->
          <div>
            <label class="block text-sm font-medium text-dark mb-2">Sort Order</label>
            <input
              v-model.number="form.sort_order"
              type="number"
              class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
              placeholder="0"
            />
            <p v-if="errors.sort_order" class="text-red-600 text-sm mt-1">{{ errors.sort_order }}</p>
          </div>

          <!-- Active -->
          <label class="flex items-center gap-2 cursor-pointer">
            <input v-model="form.is_active" type="checkbox" class="w-4 h-4 rounded border-gray-300" />
            <span class="text-sm text-dark font-medium">Active</span>
          </label>

          <!-- Buttons -->
          <div class="flex gap-4 pt-6">
            <button
              type="submit"
              class="px-6 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium transition-colors"
            >
              Create Pillar
            </button>
            <Link
              href="/admin/platform-pillars"
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
import { reactive, ref } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const errors = ref({})

const form = reactive({
  title: '',
  summary: '',
  body: '',
  icon: '',
  color: '',
  is_active: true,
  sort_order: 0,
})

const submitForm = () => {
  errors.value = {}
  router.post('/admin/platform-pillars', form, {
    onError: (err) => {
      errors.value = err
    },
  })
}
</script>
