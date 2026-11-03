<template>
  <Link
    :href="`/gallery/${album.slug}`"
    class="group block bg-white rounded-lg overflow-hidden shadow-card hover:shadow-card-hover transition-shadow focus:outline-none focus-visible:ring-4 focus-visible:ring-gold"
  >
    <span class="block aspect-[4/3] bg-light-gray overflow-hidden">
      <img
        v-if="album.cover_url"
        :src="album.cover_url"
        alt=""
        loading="lazy"
        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
      />
    </span>
    <span class="block p-3 sm:p-4">
      <span class="block font-semibold text-dark group-hover:text-primary leading-snug line-clamp-2">{{ album.name }}</span>
      <span class="block text-xs sm:text-sm text-body mt-1">
        <template v-if="album.event_date">{{ formatDate(album.event_date) }} · </template>{{ album.count }} photo{{ album.count !== 1 ? 's' : '' }}
      </span>
    </span>
  </Link>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
  album: { type: Object, required: true }, // { name, slug, event_date, count, cover_url }
})

const formatDate = (iso) => new Date(`${iso}T00:00:00`).toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' })
</script>
