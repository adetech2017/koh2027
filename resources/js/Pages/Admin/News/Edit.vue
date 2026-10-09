<template>
  <AdminLayout>
    <div class="space-y-6">
      <div>
        <Link href="/admin/news" class="inline-flex items-center gap-1 text-sm text-primary hover:text-primary-dark font-medium">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
          All articles
        </Link>
        <div class="flex flex-wrap items-center gap-3 mt-2">
          <h1 class="text-2xl font-bold text-dark">Edit article</h1>
          <span class="text-xs font-medium px-2.5 py-1 rounded-full" :class="statusBadge.class">{{ statusBadge.label }}</span>
        </div>
      </div>
      <!-- Keyed so the form resets cleanly after the server returns fresh data -->
      <NewsForm :key="article.id" :article="article" :categories="categories" />
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import NewsForm from '@/Components/NewsForm.vue'

const props = defineProps({
  article: { type: Object, required: true },
  categories: { type: Array, default: () => [] },
})

const statusBadge = computed(() => ({
  draft: { label: 'Draft', class: 'bg-gray-100 text-gray-700' },
  scheduled: { label: 'Scheduled', class: 'bg-blue-100 text-blue-800' },
  published: { label: 'Published', class: 'bg-green-100 text-green-800' },
}[props.article.status]))
</script>
