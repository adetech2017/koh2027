<template>
  <AdminLayout>
    <div class="space-y-6">
      <div>
        <Link href="/admin/platform-pillars" class="inline-flex items-center gap-1 text-sm text-primary hover:text-primary-dark font-medium">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
          All pillars
        </Link>
        <div class="flex flex-wrap items-center justify-between gap-3 mt-2">
          <h1 class="text-2xl font-bold text-dark">Edit pillar</h1>
          <a
            v-if="pillar.is_active"
            :href="`/platforms#${pillar.slug}`"
            target="_blank"
            rel="noopener"
            class="px-4 py-2 border border-gray-300 hover:border-primary hover:text-primary text-dark rounded-lg font-medium text-sm transition-colors"
          >
            View on site ↗
          </a>
        </div>
      </div>
      <PillarForm :pillar="pillar" />
    </div>
  </AdminLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PillarForm from '@/Components/PillarForm.vue'

defineProps({
  pillar: { type: Object, required: true },
})
</script>
