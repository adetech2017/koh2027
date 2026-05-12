<template>
  <Link :href="`/platforms#${pillar.slug}`" class="group">
    <div class="bg-white rounded-lg overflow-hidden shadow-card hover:shadow-card-hover transition-all">
      <div class="h-1" :style="{ backgroundColor: pillar.color || '#003D82' }"></div>
      <div class="p-6">
        <div class="w-12 h-12 rounded-lg flex items-center justify-center mb-4" :style="{ backgroundColor: pillar.color + '20' }">
          <component :is="getIcon(pillar.icon)" :style="{ color: pillar.color || '#003D82' }" class="w-6 h-6" />
        </div>
        <h3 class="text-xl font-bold text-dark mb-3 group-hover:text-primary transition-colors">{{ pillar.title }}</h3>
        <p class="text-body text-sm leading-relaxed mb-4">{{ pillar.summary }}</p>
        <span class="inline-block text-primary font-semibold text-sm group-hover:translate-x-1 transition-transform">
          Learn more →
        </span>
      </div>
    </div>
  </Link>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import { BriefcaseIcon, BookOpenIcon, HeartIcon } from '@heroicons/vue/24/outline'

const iconMap = {
  briefcase: BriefcaseIcon,
  'book-open': BookOpenIcon,
  heart: HeartIcon,
}

const getIcon = (iconName) => iconMap[iconName] || BriefcaseIcon

defineProps({
  pillar: {
    type: Object,
    required: true,
  },
})
</script>
