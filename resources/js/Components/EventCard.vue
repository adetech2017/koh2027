<template>
  <Link :href="`/events/${event.slug}`" class="group">
    <div class="bg-white rounded-lg overflow-hidden shadow-card hover:shadow-card-hover transition-all">
      <div class="relative h-48 bg-light-gray overflow-hidden">
        <img
          v-if="event.image_url"
          :src="event.image_url"
          :alt="event.title"
          class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300"
        />
        <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
          <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
        </div>
        <div class="absolute top-3 right-3 bg-primary text-white px-3 py-1 rounded-full text-xs font-semibold">
          {{ event.event_type }}
        </div>
      </div>
      <div class="p-4">
        <div class="text-sm text-body mb-2 flex items-center space-x-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          <span>{{ formatDate(event.starts_at) }}</span>
        </div>
        <h3 class="text-lg font-bold text-dark mb-2 group-hover:text-primary transition-colors line-clamp-2">{{ event.title }}</h3>
        <div class="text-sm text-body flex items-center space-x-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
          <span>{{ event.lga }}</span>
        </div>
      </div>
    </div>
  </Link>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
  event: {
    type: Object,
    required: true,
  },
})

const formatDate = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}
</script>
