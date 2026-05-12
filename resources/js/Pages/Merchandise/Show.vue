<template>
  <AppLayout>
    <Head :title="product.name" />
    <div class="min-h-screen bg-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
          <div>
            <div class="bg-light-gray rounded-lg h-96 flex items-center justify-center mb-4 overflow-hidden">
              <img v-if="product.primary_image_url" :src="product.primary_image_url" :alt="product.name" class="w-full h-full object-cover" />
            </div>
            <div v-if="product.images_urls" class="grid grid-cols-4 gap-2">
              <div v-for="(img, idx) in product.images_urls" :key="idx" class="h-20 bg-light-gray rounded cursor-pointer">
                <img :src="img.url" :alt="img.alt" class="w-full h-full object-cover rounded" />
              </div>
            </div>
          </div>
          <div>
            <p class="text-primary text-sm uppercase font-semibold mb-2 capitalize">{{ product.category }}</p>
            <h1 class="text-4xl font-bold text-dark mb-4">{{ product.name }}</h1>
            <p class="text-body leading-relaxed mb-6">{{ product.description }}</p>
            <div class="bg-light-blue rounded-lg p-4 mb-6">
              <p class="text-sm text-primary font-semibold">Design Specifications</p>
              <p class="text-body text-sm mt-2">Reference Price: ₦{{ formatPrice(product.price) }} per unit</p>
            </div>
            <div v-if="product.sizes" class="mb-6">
              <h3 class="font-semibold text-dark mb-3">Sizes</h3>
              <div class="flex gap-2">
                <button v-for="size in product.sizes" :key="size" class="px-4 py-2 border border-light-gray rounded hover:border-primary">{{ size }}</button>
              </div>
            </div>
            <div v-if="product.colors" class="mb-6">
              <h3 class="font-semibold text-dark mb-3">Colors</h3>
              <div class="flex gap-2">
                <button v-for="color in product.colors" :key="color" class="px-4 py-2 border border-light-gray rounded hover:border-primary capitalize">{{ color }}</button>
              </div>
            </div>
            <button class="btn-primary">Request Design Files & Guidelines</button>
            <p class="text-sm text-body mt-4">Contact the campaign office to request design files, brand guidelines, or to order merchandise in bulk for your support group.</p>
          </div>
        </div>
        <div v-if="related.length" class="mt-24">
          <h2 class="text-3xl font-bold text-dark mb-8">Related Products</h2>
          <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <MerchandiseCard v-for="prod in related" :key="prod.id" :product="prod" />
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import MerchandiseCard from '@/Components/MerchandiseCard.vue'

defineProps({
  product: Object,
  related: Array,
})

const formatPrice = (price) => {
  return parseFloat(price).toLocaleString('en-NG')
}
</script>
