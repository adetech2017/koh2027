<template>
  <Link :href="`/merchandise/${product.slug}`" class="group">
    <div class="bg-white rounded-lg overflow-hidden shadow-card hover:shadow-card-hover transition-all">
      <div class="relative h-56 bg-light-gray overflow-hidden">
        <img
          v-if="product.primary_image_url"
          :src="product.primary_image_url"
          :alt="product.name"
          class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300"
        />
        <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
          <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
        </div>
        <div v-if="!product.in_stock" class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center">
          <span class="text-white font-bold text-lg">Out of Stock</span>
        </div>
      </div>
      <div class="p-4">
        <p class="text-xs text-body uppercase tracking-wide mb-2 capitalize">{{ product.category }}</p>
        <h3 class="text-lg font-bold text-dark group-hover:text-primary transition-colors line-clamp-2">{{ product.name }}</h3>
      </div>
    </div>
  </Link>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
  product: {
    type: Object,
    required: true,
  },
})
</script>
