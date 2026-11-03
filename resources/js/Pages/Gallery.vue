<template>
  <AppLayout>
    <Head :title="album ? `${album.name} · Gallery` : 'Gallery'" />
    <div class="min-h-screen bg-white">
      <div class="bg-primary text-white py-12 md:py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <template v-if="album">
            <Link href="/gallery" class="inline-flex items-center gap-1 text-sm text-gray-200 hover:text-white mb-3">
              <span aria-hidden="true">←</span> All albums
            </Link>
            <h1 class="text-3xl md:text-5xl font-bold mb-3">{{ album.name }}</h1>
            <p class="text-gray-200">
              <template v-if="album.event_date">{{ formatDate(album.event_date) }} · </template>
              {{ album.count }} photo{{ album.count !== 1 ? 's' : '' }}
            </p>
            <p v-if="album.description" class="text-lg text-gray-100 mt-4 max-w-3xl whitespace-pre-line">{{ album.description }}</p>
          </template>
          <template v-else>
            <h1 class="text-4xl md:text-5xl font-bold mb-4">Campaign Gallery</h1>
            <p class="text-xl text-gray-200">Moments from the campaign trail</p>
          </template>
        </div>
      </div>

      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <!-- Albums (gallery home only, first page) -->
        <section v-if="!album && albums.length && images.current_page === 1" class="mb-14" aria-labelledby="albums-heading">
          <h2 id="albums-heading" class="text-2xl font-bold text-dark mb-5">Albums</h2>
          <ul class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
            <li v-for="item in albums" :key="item.slug">
              <AlbumCard :album="item" />
            </li>
          </ul>
        </section>

        <!-- Photos -->
        <section :aria-labelledby="album ? undefined : 'photos-heading'" :aria-label="album ? 'Photos' : undefined">
          <h2 v-if="!album" id="photos-heading" class="text-2xl font-bold text-dark mb-5">Latest photos</h2>

          <!-- Even grid of square tiles (photos of different shapes used to leave ragged gaps) -->
          <ul v-if="images.data.length" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 md:gap-4 mb-12">
            <li v-for="(image, i) in images.data" :key="image.id">
              <button
                type="button"
                class="group relative block w-full aspect-square bg-light-gray rounded-lg overflow-hidden focus:outline-none focus-visible:ring-4 focus-visible:ring-gold"
                :aria-label="`View photo${captionOf(image) ? ': ' + captionOf(image) : ''}`"
                @click="lightbox.open(i)"
              >
                <img
                  v-if="!failedImages.has(image.id)"
                  :src="image.thumbnail_url || image.image_url"
                  :alt="image.alt_text || ''"
                  loading="lazy"
                  class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                  @error="failedImages.add(image.id)"
                />
                <span v-else class="w-full h-full flex items-center justify-center text-gray-400 text-xs">Image unavailable</span>
                <span
                  v-if="captionOf(image)"
                  class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/75 to-transparent px-3 pt-8 pb-2 text-left text-white text-xs sm:text-sm leading-snug line-clamp-2"
                  aria-hidden="true"
                >{{ captionOf(image) }}</span>
              </button>
            </li>
          </ul>
          <div v-else class="text-center py-16">
            <p class="text-lg font-semibold text-dark mb-2">No photos here yet.</p>
          </div>

          <Pagination v-if="images.last_page > 1" :links="images.links" />
        </section>

        <!-- Other albums (album page) -->
        <section v-if="album && otherAlbums.length" class="mt-16" aria-labelledby="more-albums-heading">
          <div class="flex items-end justify-between gap-4 mb-5">
            <h2 id="more-albums-heading" class="text-2xl font-bold text-dark">More albums</h2>
            <Link href="/gallery" class="text-primary font-semibold hover:underline text-sm">See all</Link>
          </div>
          <ul class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6">
            <li v-for="item in otherAlbums" :key="item.slug">
              <AlbumCard :album="item" />
            </li>
          </ul>
        </section>

        <Lightbox ref="lightbox" :images="images.data" :link-albums="!album" />
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import Lightbox from '@/Components/Lightbox.vue'
import AlbumCard from '@/Components/AlbumCard.vue'

const props = defineProps({
  albums: { type: Array, default: () => [] },
  images: { type: Object, required: true },
  album: { type: Object, default: null },
})

const failedImages = ref(new Set())
const lightbox = ref(null)

const otherAlbums = computed(() => props.albums.filter((a) => a.slug !== props.album?.slug).slice(0, 4))

// Caption if set, otherwise the photo's description
const captionOf = (image) => image.title || image.alt_text || ''

const formatDate = (iso) => new Date(`${iso}T00:00:00`).toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' })
</script>
