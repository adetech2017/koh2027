<template>
  <AppLayout>
    <Head title="Materials & Resources" />
    <div class="min-h-screen bg-white">
      <div class="bg-primary text-white py-12 md:py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <h1 class="text-4xl md:text-5xl font-bold mb-4">Campaign Materials</h1>
          <p class="text-xl text-gray-200">Download brochures, policies, and campaign resources</p>
        </div>
      </div>
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="mb-8 flex gap-2 flex-wrap border-b border-light-gray pb-6">
          <button v-for="cat in categories" :key="cat" @click="activeCategory = cat" :class="['px-4 py-2 rounded transition-colors capitalize cursor-pointer font-medium', activeCategory === cat ? 'btn-primary' : 'text-body border border-light-gray hover:border-primary hover:text-primary']">{{ cat }}</button>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <a v-for="material in materialsByCategory" :key="material.id" :href="`/materials/${material.id}/download`" class="bg-white border border-light-gray rounded-lg p-6 hover:shadow-card transition-all group">
            <div class="flex items-start space-x-4">
              <div class="w-12 h-12 bg-light-blue rounded-lg flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4v.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <div class="flex-grow">
                <h3 class="font-bold text-dark group-hover:text-primary transition-colors">{{ material.title }}</h3>
                <p class="text-sm text-body">{{ material.description }}</p>
                <div class="flex items-center justify-between mt-4">
                  <span class="text-xs text-gray-400">{{ formatFileSize(material.file_size) }} • {{ material.file_type.toUpperCase() }}</span>
                  <span class="text-xs text-primary font-semibold">{{ material.download_count }} downloads</span>
                </div>
              </div>
            </div>
          </a>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  materials: Object,
  categories: Array,
})

const activeCategory = ref(props.categories?.[0] || '')

const materialsByCategory = computed(() => {
  return props.materials[activeCategory.value] || []
})

const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i]
}
</script>
