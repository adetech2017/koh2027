<template>
  <Link :href="`/merchandise/${product.slug}`" class="group block h-full">
    <div class="bg-white rounded-lg overflow-hidden shadow-card hover:shadow-card-hover transition-all h-full flex flex-col">
      <div class="relative aspect-square bg-light-gray overflow-hidden">
        <img
          v-if="product.primary_image_url && !imageFailed"
          :src="product.primary_image_url"
          :alt="product.name"
          loading="lazy"
          class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
          @error="imageFailed = true"
        />
        <div v-else class="w-full h-full flex flex-col items-center justify-center text-gray-400 gap-2" aria-hidden="true">
          <PhotoIcon class="w-12 h-12" />
          <span class="text-xs">Design preview coming soon</span>
        </div>
      </div>
      <div class="p-4 flex-grow flex flex-col">
        <p class="text-xs text-body uppercase tracking-wide mb-1">{{ categoryLabel(product.category) }}</p>
        <h3 class="text-lg font-bold text-dark group-hover:text-primary transition-colors line-clamp-2 flex-grow">{{ product.name }}</h3>
      </div>
    </div>
  </Link>
</template>

<script setup>
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import { PhotoIcon } from '@heroicons/vue/24/outline'
import { categoryLabel } from '@/Utils/format'

defineProps({
  product: { type: Object, required: true },
})

const imageFailed = ref(false)
</script>
