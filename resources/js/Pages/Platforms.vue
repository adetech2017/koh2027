<template>
  <AppLayout>
    <Head title="Our Platforms" />
    <div class="min-h-screen bg-white">
      <div class="bg-primary text-white py-16 md:py-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <h1 class="text-4xl md:text-5xl font-bold mb-4">Our Campaign Platforms</h1>
          <p class="text-xl text-gray-200">Detailed vision for a progressive Lagos</p>
        </div>
      </div>
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 md:py-24">
        <div class="space-y-12">
          <div v-for="(pillar, idx) in pillars" :id="pillar.slug" :key="pillar.id" class="border-b pb-12 last:border-b-0 scroll-mt-24">
            <div class="flex items-center space-x-4 mb-6">
              <div class="w-16 h-16 rounded-lg flex items-center justify-center" :style="{ backgroundColor: pillar.color + '20' }">
                <component :is="getIcon(pillar.icon)" :style="{ color: pillar.color }" class="w-8 h-8" />
              </div>
              <div>
                <h2 class="text-3xl font-bold text-dark">{{ pillar.title }}</h2>
                <p class="text-body">{{ pillar.summary }}</p>
              </div>
            </div>
            <div class="prose prose-sm max-w-none text-body leading-relaxed">
              <p>{{ pillar.body }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
  BriefcaseIcon,
  BookOpenIcon,
  HeartIcon,
  BoltIcon,
  BuildingLibraryIcon,
  UserGroupIcon,
  ShieldCheckIcon,
  HomeIcon,
} from '@heroicons/vue/24/outline'

const iconMap = {
  briefcase: BriefcaseIcon,
  'book-open': BookOpenIcon,
  heart: HeartIcon,
  bolt: BoltIcon,
  'building-library': BuildingLibraryIcon,
  'user-group': UserGroupIcon,
  'shield-check': ShieldCheckIcon,
  home: HomeIcon,
}

const getIcon = (iconName) => iconMap[iconName] || BriefcaseIcon

defineProps({
  pillars: Array,
})
</script>
