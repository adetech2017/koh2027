<template>
  <AdminLayout>
    <div class="space-y-6">
      <div>
        <Link href="/admin/materials" class="inline-flex items-center gap-1 text-sm text-primary hover:text-primary-dark font-medium">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
          All documents
        </Link>
        <h1 class="text-2xl font-bold text-dark mt-2">Upload document</h1>
      </div>
      <MaterialForm :categories="categories" :max-upload-bytes="maxUploadBytes" />
    </div>
  </AdminLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import MaterialForm from '@/Components/MaterialForm.vue'

defineProps({
  categories: { type: Array, default: () => [] },
  maxUploadBytes: { type: Number, default: 8 * 1024 * 1024 },
})
</script>
