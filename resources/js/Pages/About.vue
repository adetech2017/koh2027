<template>
  <AppLayout>
    <Head title="About" />
    <div class="min-h-screen bg-white">
      <div class="bg-primary text-white py-16 md:py-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <h1 class="text-4xl md:text-5xl font-bold mb-4">About Kadri Obafemi Hamzat</h1>
          <p class="text-xl text-gray-200">Visionary leadership for a progressive Lagos</p>
        </div>
      </div>
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 md:py-24">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center mb-24">
          <div class="h-96 bg-light-gray rounded-lg overflow-hidden flex items-center justify-center text-gray-400">
            <img :src="imageUrl" alt="Kadri Obafemi Hamzat" class="w-full h-full object-cover" />
          </div>
          <div>
            <h2 class="text-3xl font-bold text-dark mb-6">A Leader Committed to Lagos</h2>
            <p class="text-body leading-relaxed mb-4">With over 30 years of experience in public and private sector leadership, Kadri Obafemi Hamzat brings a wealth of knowledge and proven expertise to the table.</p>
            <p class="text-body leading-relaxed mb-4">His career has been defined by a commitment to excellence, transparency, and inclusive governance. He has consistently delivered results that improve the lives of ordinary Lagosians.</p>
            <p class="text-body leading-relaxed">Kadri's vision for Lagos is one of progress, prosperity, and peace—a state where every citizen has access to quality education, healthcare, and economic opportunity.</p>
          </div>
        </div>

        <div>
          <h2 class="text-3xl font-bold text-dark mb-12 text-center">Core Values & Platforms</h2>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <PlatformCard v-for="pillar in pillars" :key="pillar.id" :pillar="pillar" />
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PlatformCard from '@/Components/PlatformCard.vue'

const imageUrl = '/storage/personal/koh1.jpg'

defineProps({
  pillars: Array,
})
</script>
