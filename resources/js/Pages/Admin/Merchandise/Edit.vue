<template>
  <AdminLayout>
    <div class="space-y-6">
      <div>
        <Link href="/admin/merchandise" class="inline-flex items-center gap-1 text-sm text-primary hover:text-primary-dark font-medium">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
          All designs
        </Link>
        <div class="flex flex-wrap items-center justify-between gap-3 mt-2">
          <div class="flex flex-wrap items-center gap-2">
            <h1 class="text-2xl font-bold text-dark">{{ product.name }}</h1>
            <span v-if="!product.is_active" class="text-xs font-medium px-2 py-0.5 rounded-full bg-gray-100 text-gray-700">Hidden</span>
          </div>
          <a
            v-if="product.is_active"
            :href="`/merchandise/${product.slug}`"
            target="_blank"
            rel="noopener"
            class="px-4 py-2 border border-gray-300 hover:border-primary hover:text-primary text-dark rounded-lg font-medium text-sm transition-colors"
          >
            View on site ↗
          </a>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
          <MerchandiseForm :key="product.updated_at" :product="product" :categories="categories" />
        </div>
        <div class="space-y-6">
          <ProductPhotos :product-id="product.id" :photos="images" />
          <div class="space-y-2">
            <p class="text-sm font-medium text-gray-600">Website card preview</p>
            <div class="pointer-events-none" aria-hidden="true">
              <MerchandiseCard :product="{ ...product, primary_image_url: thumbnail }" />
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import MerchandiseForm from '@/Components/MerchandiseForm.vue'
import MerchandiseCard from '@/Components/MerchandiseCard.vue'
import ProductPhotos from '@/Components/ProductPhotos.vue'

const props = defineProps({
  product: { type: Object, required: true },
  images: { type: Array, default: () => [] },
  categories: { type: Array, default: () => [] },
})

const thumbnail = computed(() => props.images.find(i => i.is_primary)?.image_url || props.images[0]?.image_url || null)
</script>
