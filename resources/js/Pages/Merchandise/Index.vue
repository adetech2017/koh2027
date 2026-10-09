<template>
  <AppLayout>
    <Head title="Merchandise Designs" />
    <div class="min-h-screen bg-white">
      <div class="bg-primary text-white py-12 md:py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <h1 class="text-4xl md:text-5xl font-bold mb-4">Campaign Merchandise Designs</h1>
          <p class="text-xl text-gray-200">Approved designs for support groups across Lagos</p>
        </div>
      </div>
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div v-if="categories.length > 1" class="mb-8 flex gap-2 flex-wrap" role="group" aria-label="Filter by category">
          <button type="button" :aria-pressed="!filters.category" :class="chipClass(!filters.category)" @click="updateFilter(null)">All</button>
          <button
            v-for="cat in categories"
            :key="cat"
            type="button"
            :aria-pressed="filters.category === cat"
            :class="chipClass(filters.category === cat)"
            @click="updateFilter(cat)"
          >{{ categoryLabel(cat) }}</button>
        </div>

        <div v-if="products.length" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6 mb-12">
          <MerchandiseCard v-for="product in products" :key="product.id" :product="product" />
        </div>
        <div v-else class="text-center py-16">
          <p class="text-lg font-semibold text-dark mb-2">No designs here yet.</p>
          <button v-if="filters.category" type="button" class="text-primary font-semibold hover:underline" @click="updateFilter(null)">See all designs</button>
        </div>

        <div class="bg-light-gray rounded-lg p-6 md:p-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
          <div>
            <p class="font-bold text-dark text-lg">Producing merchandise for your support group?</p>
            <p class="text-body">Use only approved designs. Ask us for the print-ready files and brand guidelines.</p>
          </div>
          <Link :href="`/contact?subject=${encodeURIComponent('Merchandise design files request')}`" class="btn-primary text-center">Contact the campaign</Link>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import MerchandiseCard from '@/Components/MerchandiseCard.vue'
import { categoryLabel } from '@/Utils/format'

defineProps({
  products: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({}) },
  categories: { type: Array, default: () => [] },
})

const chipClass = (active) => [
  'px-4 py-2 rounded-full text-sm font-medium transition-colors',
  active ? 'bg-primary text-white' : 'border border-gray-300 text-body hover:border-primary hover:text-primary',
]

const updateFilter = (category) => {
  router.get('/merchandise', category ? { category } : {}, { preserveScroll: true, preserveState: true, replace: true })
}
</script>
