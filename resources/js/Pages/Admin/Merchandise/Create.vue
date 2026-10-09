<template>
  <AdminLayout>
    <div class="max-w-3xl space-y-6">
      <div>
        <Link href="/admin/merchandise" class="inline-flex items-center gap-1 text-sm text-primary hover:text-primary-dark font-medium">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
          All designs
        </Link>
        <h1 class="text-2xl font-bold text-dark mt-2">New design</h1>
        <p class="text-sm text-gray-500 mt-1">You'll add photos on the next screen.</p>
      </div>
      <MerchandiseForm :categories="categories" />
    </div>
  </AdminLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import MerchandiseForm from '@/Components/MerchandiseForm.vue'

defineProps({
  categories: { type: Array, default: () => [] },
})
</script>
