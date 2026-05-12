<template>
  <AppLayout>
    <Head title="Campaign Merchandise" />
    <div class="min-h-screen bg-white">
      <div class="bg-primary text-white py-12 md:py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <h1 class="text-4xl md:text-5xl font-bold mb-4">Campaign Merchandise Designs</h1>
          <p class="text-xl text-gray-200">Official design guidelines and specifications for campaign merchandise across all support groups</p>
        </div>
      </div>
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="mb-8 flex gap-2 flex-wrap">
          <button @click="updateFilter(null)" :class="['px-4 py-2 rounded transition-colors cursor-pointer font-medium', !filters.category ? 'btn-primary' : 'border border-light-gray text-body hover:border-primary hover:text-primary']">All</button>
          <button v-for="cat in categories" :key="cat" @click="updateFilter(cat)" :class="['px-4 py-2 rounded transition-colors capitalize cursor-pointer font-medium', filters.category === cat ? 'btn-primary' : 'border border-light-gray text-body hover:border-primary hover:text-primary']">{{ cat }}</button>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-12">
          <MerchandiseCard v-for="product in products" :key="product.id" :product="product" />
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import MerchandiseCard from '@/Components/MerchandiseCard.vue'

defineProps({
  products: Array,
  filters: Object,
  categories: Array,
})

const updateFilter = (category) => {
  const params = new URLSearchParams()
  if (category) {
    params.append('category', category)
  }
  const url = params.toString() ? `/merchandise?${params.toString()}` : '/merchandise'
  router.get(url)
}
</script>
