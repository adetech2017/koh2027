<template>
  <AdminLayout>
    <div class="max-w-3xl space-y-6">
      <div>
        <Link href="/admin/events" class="inline-flex items-center gap-1 text-sm text-primary hover:text-primary-dark font-medium">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
          All events
        </Link>
        <div class="flex flex-wrap items-center justify-between gap-3 mt-2">
          <h1 class="text-2xl font-bold text-dark break-words">Edit: {{ event.title }}</h1>
          <div class="flex gap-2">
            <Link
              :href="`/admin/events/${event.id}/rsvps`"
              class="px-4 py-2 border border-gray-300 hover:border-primary hover:text-primary text-dark rounded-lg font-medium text-sm transition-colors"
            >
              RSVPs ({{ rsvpCount }})
            </Link>
            <a
              v-if="event.is_active"
              :href="`/events/${event.slug}`"
              target="_blank"
              rel="noopener"
              class="px-4 py-2 border border-gray-300 hover:border-primary hover:text-primary text-dark rounded-lg font-medium text-sm transition-colors"
            >
              View on site ↗
            </a>
          </div>
        </div>
      </div>
      <EventForm :event="event" :lga-options="lgaOptions" :rsvp-count="rsvpCount" />
    </div>
  </AdminLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import EventForm from '@/Components/EventForm.vue'

defineProps({
  event: { type: Object, required: true },
  lgaOptions: { type: Array, default: () => [] },
  rsvpCount: { type: Number, default: 0 },
})
</script>
